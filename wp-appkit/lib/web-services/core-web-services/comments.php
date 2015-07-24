<?php

class WpakWebServiceComments {

	public static function hooks() {
		add_filter( 'wpak_read_one_comments-post', array( __CLASS__, 'read_one' ), 10, 3 );
		add_filter( 'wpak_create_comments-post', array( __CLASS__, 'create' ), 10, 3 );
	}

	public static function read_one( $service_answer, $id, $app_id ) {
		$service_answer = array();

		$post_id = $id;

		$max = 50;

		$query_args = array(
			'post_id' => $post_id,
			'status' => 'approve',
			'number' => $max,
		);

		$comments = get_comments( $query_args );
		$comment_tree = self::get_comments_tree( $comments );

		foreach ( $comment_tree as $comment_node ) {

			$data = self::get_comment_web_service_data( $comment_node );

			if ( !empty( $data ) ) {
				$service_answer[] = $data;
			}
		}

		return $service_answer;
	}

	private static function get_comment_web_service_data( $comment_node ) {
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

	private static function get_comments_tree( $comments ) {

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
		
		$service_answer['comment_ok'] = false;
		
		if ( !empty( $data['comment'] )  ) {
			
			$comment = $data['comment'];
			
			//Check authentication
			if ( !empty( $data['auth'] ) ) {
				
				if ( is_array( $comment ) && !empty( $comment['content'] )  ) {
					
					$to_check = array( $comment['content'] );
					//TODO we could add a filter on this to add more comment data to control field 
					//(and same must be applied on app side).

					$result = WpakUserLogin::log_user_from_authenticated_action( $app_id, "comment-POST", $data['auth'], $to_check );
 
					if ( $result['ok'] ) {
						
						//Save comment to database :
						$service_answer['comment'] = base64_decode( $comment['content'] );
						
						if ( ! empty( $comment['id'] ) ) {
							$service_answer['comment_error'] = 'comment-already-exists';
						}
						
						$post = get_post( $comment['post'] );
						if ( empty( $post ) ) {
							$service_answer['comment_error'] = 'comment-post-not-found';
						}
						
						/*$prepared_comment = $this->prepare_item_for_database( $comment );
						// Setting remaining values before wp_insert_comment so we can
						// use wp_allow_comment().
						$prepared_comment['comment_author_IP'] = '127.0.0.1';
						$prepared_comment['comment_agent'] = '';
						$prepared_comment['comment_approved'] = wp_allow_comment( $prepared_comment );
						$prepared_comment = apply_filters( 'rest_pre_insert_comment', $prepared_comment, $comment );
						$comment_id = wp_insert_comment( $prepared_comment );
						if ( ! $comment_id ) {
							return new WP_Error( 'rest_comment_failed_create', __( 'Creating comment failed.' ), array( 'status' => 500 ) );
						}
						if ( isset( $comment['status'] ) ) {
							$comment = get_comment( $comment_id );
							$this->handle_status_param( $comment['status'], $comment );
						}
						$this->update_additional_fields_for_object( get_comment( $comment_id ), $comment );
						$context = current_user_can( 'moderate_comments' ) ? 'edit' : 'view';
						$response = $this->get_item( array(
							'id'      => $comment_id,
							'context' => $context,
						) );
						$response = rest_ensure_response( $response );
						if ( is_wp_error( $response ) ) {
							return $response;
						}
						$response->set_status( 201 );
						$response->header( 'Location', rest_url( '/wp/v2/comments/' . $comment_id ) );
						return $response; */
						
						$service_answer['comment_ok'] = true;
						
					} else {
						$service_answer['comment_error'] = $result['auth_error'];
					}
					
				} else {
					$service_answer['comment_error'] = 'empty-comment';
				}
				
			} else {
				$service_answer['comment_error'] = 'no-auth';
			}
			
		} else {
			$service_answer['comment_error'] = 'no-comment';
		}
		
		return (object)$service_answer;
	}
	
	protected function prepare_comment_for_database( $comment ) {
		$prepared_comment = array();
		if ( isset( $comment['content'] ) ) {
			$prepared_comment['comment_content'] = $comment['content'];
		}
		if ( isset( $comment['post'] ) ) {
			$prepared_comment['comment_post_ID'] = (int) $comment['post'];
		}
		if ( isset( $comment['parent'] ) ) {
			$prepared_comment['comment_parent'] = $comment['parent'];
		}
		if ( isset( $comment['author'] ) ) {
			$prepared_comment['user_id'] = $comment['author'];
		}
		if ( isset( $comment['author_name'] ) ) {
			$prepared_comment['comment_author'] = $comment['author_name'];
		}
		if ( isset( $comment['author_email'] ) ) {
			$prepared_comment['comment_author_email'] = $comment['author_email'];
		}
		if ( isset( $comment['author_url'] ) ) {
			$prepared_comment['comment_author_url'] = $comment['author_url'];
		}
		if ( isset( $comment['type'] ) ) {
			$prepared_comment['comment_type'] = $comment['type'];
		}
		if ( isset( $comment['karma'] ) ) {
			$prepared_comment['comment_karma'] = $comment['karma'] ;
		}
		if ( ! empty( $comment['date'] ) ) {
			$date_data = rest_get_date_with_gmt( $comment['date'] );
			if ( ! empty( $date_data ) ) {
				list( $prepared_comment['comment_date'], $prepared_comment['comment_date_gmt'] ) =
					$date_data;
			} else {
				return new WP_Error( 'rest_invalid_date', __( 'The date you provided is invalid.' ), array( 'status' => 400 ) );
			}
		} elseif ( ! empty( $comment['date_gmt'] ) ) {
			$date_data = rest_get_date_with_gmt( $comment['date_gmt'], true );
			if ( ! empty( $date_data ) ) {
				list( $prepared_comment['comment_date'], $prepared_comment['comment_date_gmt'] ) = $date_data;
			} else {
				return new WP_Error( 'rest_invalid_date', __( 'The date you provided is invalid.' ), array( 'status' => 400 ) );
			}
		}
		return apply_filters( 'rest_preprocess_comment', $prepared_comment, $comment );
	}
	
}

WpakWebServiceComments::hooks();
