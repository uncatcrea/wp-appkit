<?php

class WpakWebServiceComments {

	public static function hooks() {
		add_filter( 'wpak_read_one_comments-post', array( __CLASS__, 'read_one' ), 10, 3 );
		add_filter( 'wpak_create_comments-post', array( __CLASS__, 'create' ), 10, 3 );
	}

	public static function read_one( $service_answer, $id, $app_id ) {
		$service_answer = array();

		$post_id = $id;

		$comment_tree = self::get_post_comments( $post_id, $app_id );

		foreach ( $comment_tree as $comment_node ) {

			$data = self::get_comment_web_service_data( $comment_node );

			if ( !empty( $data ) ) {
				$service_answer[] = $data;
			}
		}

		return $service_answer;
	}

	protected static function get_post_comments( $post_id, $app_id, $only_approved = true ) {
		
		$max_comments = 50;
		
		/**
		 * Filter max number of comments to retrieve per post
		 *
		 * @param int 			$max_comments    	Maximum number of comments
		 * @param int 	        $post_id 			ID of the post we're commenting on
		 * @param int 			$app_id 			ID of the app
		 */
		$max_comments = apply_filters( 'wpak_max_comments', $max_comments, $post_id, $app_id );

		$query_args = array(
			'post_id' => $post_id,
			'number' => $max_comments,
		);
		
		if ( $only_approved ) {
			$query_args['status'] = 'approve';
		}

		$comments = get_comments( $query_args );
		$comment_tree = self::get_comments_tree( $comments );
		
		return $comment_tree;
	}
	
	protected static function get_comment_web_service_data( $comment_node ) {
		$comment_data = array();

		$id = $comment_node['id'];
		$depth = $comment_node['depth'];
		$comment = $comment_node['comment'];

		$comment_data_raw = get_comment( $id, ARRAY_A );
		foreach ( $comment_data_raw as $k => $v ) {
			$k = strtolower( str_replace( 'comment_', '', $k ) );
			$comment_data[$k] = $v;
		}

		$comment_data['depth'] = $depth;

		$comment_data['date'] = strtotime( $comment_data['date'] );

		$post_id = $comment_data['post_id'];

		$to_remove = array( 'karma', 'approved', 'agent', 'type', 'date_gmt', 'author_ip',
			'author_email', 'author_url', 'parent', 'user_id', 'post_id' );
		foreach ( $to_remove as $field ) {
			unset( $comment_data[$field] );
		}

		$comment_data = apply_filters( 'wpak_comments_data', $comment_data, $comment_data_raw, $post_id );

		return $comment_data;
	}

	protected static function get_comments_tree( $comments ) {

		$tree = array();

		if ( !empty( $comments ) ) {

			$comments_by_id = array();
			foreach ( $comments as $comment ) {
				$comments_by_id[$comment->comment_ID] = $comment;
			}

			ob_start();
			$wp_list_comments_args = array();
			wp_list_comments( apply_filters( 'wpak_comments_list_args', $wp_list_comments_args ), $comments );
			$comments_list = ob_get_contents();
			ob_end_clean();

			//TODO : find another way to retrieve depths and ids than parsing the html (which can change!!!)

			$depths_found = preg_match_all( '/depth-(\d+)/', $comments_list, $matches_depths );
			$ids_found = preg_match_all( '/id="comment-(\d+)"/', $comments_list, $matches_ids );

			if ( !empty( $depths_found ) && !empty( $ids_found ) ) {
				foreach ( $matches_depths[1] as $k => $depth ) {
					$comment_id = $matches_ids[1][$k];
					$tree[$comment_id] = array(
						'id' => $comment_id,
						'depth' => ( int ) $depth,
						'comment' => $comments_by_id[$comment_id]
					);
				}
			}
		}

		return $tree;
	}

	public static function create( $service_answer, $data, $app_id ) {
		$service_answer = array();
		
		$service_answer['comment_ok'] = 0;
		
		if ( !empty( $data['comment'] )  ) {
			
			$comment = $data['comment'];
			
			//Check authentication
			if ( !empty( $data['auth'] ) ) {
				
				if ( is_array( $comment ) ) {
					
					$comment_content = trim( base64_decode( $comment['content'] ) );
					
					if ( !empty( $comment_content ) ) {
					
						$to_check = array( $comment['content'], $comment['post'] );
						//TODO we could add a filter on this to add more comment data to control field 
						//(and same must be applied on app side).

						$result = WpakUserLogin::log_user_from_authenticated_action( $app_id, "comment-POST", $data['auth'], $to_check );

						if ( $result['ok'] ) {

							if ( empty( $comment['id'] ) ) {
								
								if ( !empty( $comment['post'] ) ) {
									
									$post = get_post( $comment['post'] );
									if ( ! empty( $post ) ) {

										if ( $post->post_status === 'publish' ) {
											
											//Comments must be open for the given post:
											if ( comments_open( $post->ID ) ) {

												$post_type = get_post_type_object( $post->post_type );

												//The logged in user must be able to read the post he's commenting on :
												if ( current_user_can( $post_type->cap->read_post, $post->ID ) ) {

													$comment['content'] = $comment_content;

													$logged_in_user = WpakUserLogin::get_current_user();
													$comment['author'] = $logged_in_user->ID;
													$comment['author_name'] = $logged_in_user->user_login;
													$comment['author_email'] = $logged_in_user->user_email;
													$comment['author_url'] = $logged_in_user->user_url;

													//The following comment insertion is inspired from the WP API v2 :)

													$prepared_comment = self::prepare_comment_for_database( $comment );
													if ( is_array( $prepared_comment ) ) {

														//Don't post the same comment twice :
														if ( !self::is_duplicate( $prepared_comment ) ) {
														
															$prepared_comment['comment_approved'] = wp_allow_comment( $prepared_comment );

															/**
															 * Use this filter to edit the comment fields before inserting it to database.
															 * 
															 * @param array     $prepared_comment       Comment that is going to be inserted into db
															 * @param WP_User   $logged_in_user         Currently logged in user
															 * @param int       $app_id                 Id of the current app
															 */
															$prepared_comment = apply_filters( 'wpak_comments_before_insert', $prepared_comment, $logged_in_user, $app_id );
															
															$comment_id = wp_insert_comment( $prepared_comment );
															if ( $comment_id ) {

																$inserted_comment = get_comment( $comment_id );
																if ( $inserted_comment->comment_approved ) {
																
																	$comment_tree = self::get_post_comments( $post->ID, $app_id );
																	if ( !empty( $comment_tree[$comment_id] ) ) {

																		$service_answer['comment'] = self::get_comment_web_service_data( $comment_tree[$comment_id] );
																		$service_answer['comments'] = self::read_one( array(), $post->ID, $app_id );

																		$service_answer['comment_ok'] = 1;
																		$service_answer['waiting_approval'] = 0;

																	} else {
																		$service_answer['comment_error'] = 'wrong-comment-tree';
																	}
																	
																} else {
																	
																	$comment_tree = self::get_post_comments( $post->ID, $app_id, false ); //false to get non approved comments too
																	if ( !empty( $comment_tree[$comment_id] ) ) {

																		$service_answer['comment'] = self::get_comment_web_service_data( $comment_tree[$comment_id] );
																		
																		$service_answer['comments'] = self::read_one( array(), $post->ID, $app_id ); 
																		//Note : $service_answer['comments'] will not contain the inserted comment as 
																		//it is waiting for approval.

																		$service_answer['comment_ok'] = 1;
																		$service_answer['waiting_approval'] = 1;

																	} else {
																		$service_answer['comment_error'] = 'wrong-comment-tree';
																	}
																	
																}
															} else {
																$service_answer['comment_error'] = 'wp-insert-comment-failed';
															}
														} else {
															$service_answer['comment_error'] = 'already-said-that';
														}
													} else {
														$service_answer['comment_error'] = $prepared_comment; //Contains error string
													}
												} else {
													$service_answer['comment_error'] = 'user-cant-comment-this-post';
												}
											} else {
												$service_answer['comment_error'] = 'comments-closed';
											}
										} else {
											$service_answer['comment_error'] = 'post-not-published';
										}
									} else {
										$service_answer['comment_error'] = 'comment-post-not-found';
									}
								} else {
									$service_answer['comment_error'] = 'no-comment-post';
								}
							} else {
								$service_answer['comment_error'] = 'comment-already-exists';
							}
						} else {
							$service_answer['comment_error'] = $result['auth_error'];
						}
					} else {
						$service_answer['comment_error'] = 'content-empty';
					}
				} else {
					$service_answer['comment_error'] = 'wrong-comment-format';
				}
			} else {
				$service_answer['comment_error'] = 'no-auth';
			}
		} else {
			$service_answer['comment_error'] = 'no-comment';
		}
		
		return (object)$service_answer;
	}
	
	protected static function prepare_comment_for_database( $comment ) {
		$prepared_comment = array();
		
		if ( isset( $comment['content'] ) ) {
			$prepared_comment['comment_content'] = $comment['content'];
		} else {
			return 'no-content';
		}
		
		if ( isset( $comment['post'] ) ) {
			$prepared_comment['comment_post_ID'] = (int) $comment['post'];
		} else {
			return 'no-comment-post';
		}
		
		if ( isset( $comment['author'] ) ) {
			$prepared_comment['user_id'] = (int) $comment['author'];
		} else {
			return 'no-author';
		}
		
		$prepared_comment['comment_parent'] = isset( $comment['parent'] ) ? (int)$comment['parent'] : 0;
		$prepared_comment['comment_author'] = isset( $comment['author_name'] ) ? sanitize_text_field( $comment['author_name'] ) : '';
		$prepared_comment['comment_author_email'] = isset( $comment['author_email'] ) ? sanitize_email( $comment['author_email'] ) : '';
		$prepared_comment['comment_author_url'] = isset( $comment['author_url'] ) ? esc_url_raw( $comment['author_url'] ) : '';
		
		$prepared_comment['comment_type'] = isset( $comment['type'] ) ? sanitize_key( $comment['type'] ) : '';
		
		$prepared_comment['comment_date'] = isset( $request['date'] ) ? $request['date'] : current_time( 'mysql' );
		$prepared_comment['comment_date_gmt'] = isset( $request['date_gmt'] ) ? $request['date_gmt'] : current_time( 'mysql', 1 );
				
		// Setting remaining values before wp_insert_comment so we can use wp_allow_comment() :
		$prepared_comment['comment_author_IP'] = '127.0.0.1';
		$prepared_comment['comment_agent'] = '';
											
		return $prepared_comment;
	}
	
	/**
	 * We have to rewrite wp-includes/comment.php::wp_allow_comment()'s duplicate
	 * check because the original function dies if duplicate comment, which we
	 * don't want, as we need to return a valide web service error answer.
	 */
	protected static function is_duplicate( $commentdata ) {
		global $wpdb;
		
		$duplicate = false;

		// Simple duplicate check
		// expected_slashed ($comment_post_ID, $comment_author, $comment_author_email, $comment_content)
		$dupe = $wpdb->prepare(
			"SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_parent = %s AND comment_approved != 'trash' AND ( comment_author = %s ",
			wp_unslash( $commentdata['comment_post_ID'] ),
			wp_unslash( $commentdata['comment_parent'] ),
			wp_unslash( $commentdata['comment_author'] )
		);
		if ( $commentdata['comment_author_email'] ) {
			$dupe .= $wpdb->prepare(
				"OR comment_author_email = %s ",
				wp_unslash( $commentdata['comment_author_email'] )
			);
		}
		$dupe .= $wpdb->prepare(
			") AND comment_content = %s LIMIT 1",
			wp_unslash( $commentdata['comment_content'] )
		);
		if ( $wpdb->get_var( $dupe ) ) {
			$duplicate = true;
		}
		
		return $duplicate;
	}
}

WpakWebServiceComments::hooks();
