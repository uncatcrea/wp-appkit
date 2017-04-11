<?php

class WpakComponentTypePostsList extends WpakComponentType {

	protected function compute_data( $component, $options, $args = array() ) {
		global $wpdb;

		do_action( 'wpak_before_component_posts_list', $component, $options );
		
		/**
		 * Pagination:
		 * 2 kinds of pagination supported: 
		 * 
		 * - "Infinite Scroll pagination": we retrieve posts before the given "before_item".
		 *   It avoids post duplication when getting page>1 and a new post was created in the meantime.
		 *   This is the default behaviour for the "Get More Posts" button in WP-AppKit's post lists.
		 * 
		 * - "Standard pagination": corresponds to the standard use of "paged" param in WP_Query.
		 *   To use this standard pagination for a post list component you'll have to manually set it
		 *   on appside using the 
		 * 
		 * Those 2 pagination types are exclusive: you can't use both at the same time.
		 * If a standard pagination page is provided, infinite scroll pagination is ignored.
		 */
		$before_post_date = ''; //"Infinite Scroll" pagination type
		$pagination_page = 0; //Standard pagination type
		if ( !empty( $args['pagination_page'] ) && is_numeric( $args['pagination_page'] ) ) {
			
			$pagination_page = (int)$args['pagination_page'];
			
		} else if ( !empty( $args['before_item'] ) && is_numeric( $args['before_item'] ) ) {
			
			$before_post = get_post( $args['before_item'] );
			if ( !empty( $before_post ) ) {
				$before_post_date = $before_post->post_date;
			}
			
		}
		
		//WP_Query args
		$query_args = array();

		if( $options['post-type'] == 'custom' ) {

			//Custom posts list generated via hook :
			//Choose "Custom, using hook" when creating the component in BO, and use the following
			//hook "wpak_posts_list_custom-[your-hook]" to set the component posts.
			//The wpak_posts_list_custom-[your-hook] filter must return the given $posts_list_data filled in with
			//your custom data :
			//- posts : array of the posts retrieved by your component, in the same format as a "get_posts()" or "new WP_Query($query_args)"
			//- total : total number of those posts (not only those retrieved in posts, taking pagination into account)
			//- query : data about your query that you want to retrieve on the app side.

			$posts_list_data = array(
				'posts' => array(),
				'total' => 0,
				'query' => array( 'type' => 'custom-posts-list', 'taxonomy' => '', 'terms' => array(), 'is_last_page' => true, 'before_item' => 0, 'pagination_page' => $pagination_page )
			);

			/**
			 * Filter data from a posts list component.
			 *
			 * @param array 			$posts_list_data    	An array of default data.
			 * @param WpakComponent 	$component 				The component object.
			 * @param array 			$options 				Options set in BO for the component.
			 * @param array 			$args 					Args that can be passed via webservice's url $_GET parameters.
			 * @param array 			$before_post_date 		The publication date of the last displayed post.
			 */
			$posts_list_data = apply_filters( 'wpak_posts_list_custom-' . $options['hook'], $posts_list_data, $component, $options, $args, $before_post_date, $pagination_page );

			$posts = $posts_list_data['posts'];
			$total = !empty( $posts_list_data['total'] ) ? $posts_list_data['total'] : count( $posts );
			$query = $posts_list_data['query'];

		} else { //WordPress Post type or "Latest posts"

			$is_last_posts = $options['post-type'] == 'last-posts';

			$post_type = !empty( $options['post-type'] ) && !$is_last_posts ? $options['post-type'] : 'post';

			$query = array( 'post_type' => $post_type, 'pagination_page' => $pagination_page );

			$query_args = array( 'post_type' => $post_type );

			/**
			 * Filter the number of posts displayed into a posts list component.
			 *
			 * @param int 			    					Default number of posts.
			 * @param WpakComponent 	$component 			The component object.
			 * @param array 			$options 			Options set in BO for the component.
			 * @param array 			$args 				Args that can be passed via webservice's url $_GET parameters.
			 */
			$query_args['posts_per_page'] = apply_filters('wpak_posts_list_posts_per_page', WpakSettings::get_setting( 'posts_per_page' ), $component, $options, $args );

			if( $is_last_posts ){

				$query['type'] = 'last-posts';

			}elseif ( !empty( $options['taxonomy'] ) ) {

				if ( $options['taxonomy'] === 'wpak-none' ) {
					
					$query['type'] = 'post-type';
					
				} elseif ( !empty( $options['term'] ) ) {
					
					$query_args['tax_query'] = array(
						array(
							'taxonomy' => $options['taxonomy'],
							'field' => 'slug',
							'terms' => $options['term']
						)
					);

					$query['type'] = 'taxonomy';
					$query['taxonomy'] = $options['taxonomy'];
					$query['terms'] = is_array( $options['term'] ) ? $options['term'] : array( $options['term'] );
					
				}
				
			}

			if ( !empty( $pagination_page ) ) {
				
				$query_args['paged'] = $pagination_page;
				
			} else if ( !empty( $before_post_date ) ) {

				if ( is_numeric( $before_post_date ) ) { //timestamp
					$before_post_date = date( 'Y-m-d H:i:s', $before_post_date );
				}
				
				if ( preg_match( '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $before_post_date ) ) {
					$query['before_item'] = intval( $args['before_item'] );
					$posts_where_callback = create_function( '$where', 'return $where .= " AND post_date < \'' . $before_post_date . '\'";' );
					add_filter( 'posts_where', $posts_where_callback );
				} else {
					$before_post_date = '';
				}
				
			}
			
			/**
			 * Filter args used for the query made into a posts list component.
			 *
			 * @param array 			$query_args    		WP_Query query args to filter
			 * @param WpakComponent 	$component 			The component object.
			 * @param array 			$options 			Options set in BO for the component.
			 * @param array 			$args 				Args that can be passed via webservice's url $_GET parameters.
			 * @param array 			$query 				Summary data about the "post list" query. To be retrieved on app side.
			 */
			$query_args = apply_filters( 'wpak_posts_list_query_args', $query_args, $component, $options, $args, $query );

			$posts_query = new WP_Query( $query_args );

			if ( !empty( $before_post_date ) ) {
				remove_filter( 'posts_where', $posts_where_callback );
				$query['is_last_page'] = $posts_query->found_posts <= count( $posts_query->posts );
			}

			$posts = $posts_query->posts;
			$total = $posts_query->found_posts;
		}

		$posts_by_ids = array();
		foreach ( $posts as $post ) {
			$posts_by_ids[$post->ID] = self::get_post_data( $component, $post );
		}
		
		//Allow to return custom meta data (like term meta):
		$post_list_meta = array();
		
		/**
		 * Use this 'wpak_posts_list_meta' filter to send custom meta data along with the web service answer.
		 * Can be used to add terms meta for example.
		 * 
		 * @param array             $post_list_meta     Set your meta here (default empty)
		 * @param WpakComponent 	$component 			The component object.
		 * @param array 			$query 				Summary data ('type', 'post_type', 'taxonomy', 'terms') about the current "post list" query.
		 * @param array 			$query_args    		WP_Query query args
		 * @param array 			$options 			Options set in BO for the component.
		 * @param array 			$args 				Args that can be passed via webservice's url $_GET parameters.
		 */
		$post_list_meta = apply_filters( 'wpak_posts_list_meta', $post_list_meta, $component, $query, $query_args, $options, $args );

		$this->set_specific( 'ids', array_keys( $posts_by_ids ) );
		$this->set_specific( 'total', $total );
		$this->set_specific( 'query', $query );
		
		if ( !empty( $post_list_meta ) ) {
			$this->set_specific( 'meta', $post_list_meta );
		}
		
		$this->set_globals( 'posts', $posts_by_ids );
	}
	
	/**
	 * To retrieve only items given in $items_ids
	 */
	protected function get_items_data( $component, $options, $items_ids, $args = array() ) {
		$items = array( 'posts' => array() );
		
		$posts_by_ids = array();
		foreach ( $items_ids as $post_id ) {
			$post = get_post( $post_id );
			if( !empty($post) ) {
				$posts_by_ids[$post_id] = self::get_post_data( $component, $post );
				$posts_by_ids[$post_id]->title = $posts_by_ids[$post_id]->title;
			}
		}
		
		if( $options['post-type'] == 'custom' ) {
			$posts_by_ids = apply_filters( 'wpak_posts_list_custom_items-' . $options['hook'], $posts_by_ids, $component, $options, $items_ids, $args );
		} 
		
		$items['posts'] = $posts_by_ids;
		
		return $items;
	}

	protected static function get_post_data( $component, $post ) {
		
		$post_data = WpakComponentsUtils::get_post_data( $post, $component );
		
		/**
		 * Filter post content for posts list components. 
		 * Use this to format app posts content your own way only for posts list component.
		 *
		 * To apply a custom content to all component types, use the "wpak_post_data_post_content" filter instead.
		 *
		 * @see WpakComponentsUtils::get_formated_content()
		 *
		 * @param string 			$post_content   The default post content.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$post_data['content'] = apply_filters( 'wpak_posts_list_post_content', $post_data['content'], $post, $component );

		/**
		 * Filter post data sent to the app from a post list component.
		 *
		 * Use this for example to add a post meta to the default post data only for posts list components.
		 *
		 * @param array 			$post_data    	The default post data sent to an app.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$post_data = apply_filters( 'wpak_posts_list_post_data', $post_data, $post, $component );
		
		return ( object ) $post_data;
	}

	public function get_options_to_display( $component ) {
		$options = array();
		if ( $component->options['post-type'] == 'custom' ) {
			$options = array(
				'hook' => array( 'label' => __( 'Hook', WpAppKit::i18n_domain ), 'value' => $component->options['hook'] ),
			);
		} elseif ( $component->options['post-type'] == 'last-posts'  ) {
			$options = array(
				'post-type' => array( 'label' =>  __( 'List type', WpAppKit::i18n_domain ), 'value' => __( 'Latest posts', WpAppKit::i18n_domain ) )
			);
		} elseif ( !empty ( $component->options['post-type'] ) ) {
			$post_type = get_post_type_object( $component->options['post-type'] );
			$taxo_name = '';
			$term_name = '';
			if ( !empty( $component->options['taxonomy'] ) && $component->options['taxonomy'] !== 'wpak-none' ) {
				$taxonomy = get_taxonomy( $component->options['taxonomy'] );
				$term = get_term_by( 'slug', $component->options['term'], $component->options['taxonomy'] );
				if ( !is_wp_error( $term ) ) {
					$taxo_name = $taxonomy->labels->name;
					$term_name = $term->name;
				}
			}
			$options = array(
				'post-type' => array( 'label' => __( 'Post type' ), 'value' => $post_type->labels->name )
			);
			if ( !empty( $taxo_name ) ) {
				$options['taxonomy'] = array( 'label' => __( 'Taxonomy' ), 'value' => $taxo_name );
				$options['term'] = array( 'label' => __( 'Term' ), 'value' => $term_name );
			}
		}
		return $options;
	}

	public function echo_form_fields( $component ) {
		
		$post_types_slugs = array_keys( get_post_types( array( 'public' => true ), 'names' ) ); 
		if ( in_array( 'attachment', $post_types_slugs ) ) {
			unset( $post_types_slugs[array_search( 'attachment', $post_types_slugs )] );
		}

		/**
		 * Use this "wpak_posts_list_post_types" to customize which post types are
		 * available in post list components.
		 * 
		 * @param $post_types_slugs     array              Array of post types slugs to be filtered
		 * @param $component            WpakComponent      Current "post list" component
		 */
		$post_types_slugs = apply_filters( 'wpak_posts_list_post_types', $post_types_slugs, $component );
		
		//Retrieve post types objects
		$post_types = array();
		foreach( $post_types_slugs as $post_type_slug ) {
			$post_type_object = get_post_type_object( $post_type_slug );
			if ( $post_type_object ) {
				$post_types[$post_type_slug] = $post_type_object;
			}
		}
		
		$has_options = !empty( $component ) && !empty( $component->options );

		reset( $post_types );
		$first_post_type = key( $post_types );

		$current_post_type = $first_post_type;
		$current_taxonomy = '';
		$current_term = '';
		$current_hook = '';
		if ( $has_options ) {
			$options = $component->options;
			$current_post_type = $options['post-type'];
			$current_taxonomy = $options['taxonomy'];
			$current_term = $options['term'];
			$current_hook = !empty( $options['hook'] ) ? $options['hook'] : '';
		}

		?>
		<div class="component-params">
			<label><?php _e( 'List type', WpAppKit::i18n_domain ) ?> : </label>
			<select name="post-type" class="posts-list-post-type">
				<?php foreach ( $post_types as $post_type => $post_type_object ): ?>
					<?php $selected = $post_type == $current_post_type ? 'selected="selected"' : '' ?>
					<option value="<?php echo esc_attr( $post_type ) ?>" <?php echo $selected ?>><?php echo esc_html( $post_type_object->labels->name ) ?></option>
				<?php endforeach ?>
				<option value="last-posts" <?php echo 'last-posts' == $current_post_type ? 'selected="selected"' : '' ?>><?php _e( 'Latest posts', WpAppKit::i18n_domain ) ?></option>
				<option value="custom" <?php echo 'custom' == $current_post_type ? 'selected="selected"' : '' ?>><?php _e( 'Custom, using hooks', WpAppKit::i18n_domain ) ?></option>
			</select>
		</div>

		<div class="ajax-target">
			<?php self::echo_sub_options_html( $current_post_type, $current_taxonomy, $current_term, $current_hook ) ?>
		</div>

		<?php
	}

	public function echo_form_javascript() {
		?>
		<script type="text/javascript">
			(function() {
				var $ = jQuery;
				$('.wrap').delegate('.posts-list-post-type', 'change', function() {
					var post_type = $(this).find(":selected").val();
					WpakComponents.ajax_update_component_options(this, 'posts-list', 'change-post-list-option', {taxonomy: '', post_type: post_type});
				});
				$('.wrap').delegate('.posts-list-taxonomies', 'change', function() {
					var post_type = $(this).closest('.ajax-target').prev('div.component-params').find('select.posts-list-post-type').eq(0).find(":selected").val();
					var taxonomy = $(this).find(":selected").val();
					WpakComponents.ajax_update_component_options(this, 'posts-list', 'change-post-list-option', {taxonomy: taxonomy, post_type: post_type});
				});
			})();
		</script>
		<?php
	}

	public function get_ajax_action_html_answer( $action, $params ) {
		switch ( $action ) {
			case 'change-post-list-option':
				$post_type = $params['post_type'];
				$taxonomy = $params['taxonomy'];
				self::echo_sub_options_html( $post_type, $taxonomy );
				break;
		}
	}

	protected static function echo_sub_options_html( $current_post_type, $current_taxonomy = '', $current_term = '', $current_hook = '' ) {
		?>
		<?php if( $current_post_type == 'last-posts' ) : //Custom posts list ?>
			<?php //no sub option for now ?>
		<?php elseif( $current_post_type == 'custom' ): //Custom posts list ?>
			<label><?php _e( 'Hook name', WpAppKit::i18n_domain ) ?></label> : <input type="text" name="hook" value="<?php echo esc_attr( $current_hook ) ?>" />
		<?php else: //Post type ?>

			<?php
				$taxonomies = get_object_taxonomies( $current_post_type );
				$taxonomies = array_diff( $taxonomies, array( 'nav_menu', 'link_category' ) );

				/**
				 * Filter taxonomies list displayed into a "Posts list" component select field.
				 *
				 * @param array 	$taxonomies    	The default taxonomies list to display.
				 */
				$taxonomies = apply_filters( 'wpak_component_type_posts_list_form_taxonomies', $taxonomies );

				$first_taxonomy = !empty( $taxonomies ) ? reset( $taxonomies ) : '';
				$current_taxonomy = empty( $current_taxonomy ) ? empty( $first_taxonomy ) ? 'wpak-none' : $first_taxonomy : $current_taxonomy;
			?>
			<label><?php _e( 'Taxonomy', WpAppKit::i18n_domain ) ?> : </label>
			<?php if ( !empty( $taxonomies ) ): ?>
				<select name="taxonomy" class="posts-list-taxonomies">
					<option value="wpak-none" <?php echo $current_taxonomy == 'wpak-none' ? 'selected="selected"' : '' ?>><?php _e( 'No taxonomy', WpAppKit::i18n_domain ) ?></option>
					<?php foreach ( $taxonomies as $taxonomy_slug ): ?>
						<?php $taxonomy = get_taxonomy( $taxonomy_slug ) ?>
						<?php $selected = $taxonomy_slug == $current_taxonomy ? 'selected="selected"' : '' ?>
						<option value="<?php echo esc_attr( $taxonomy_slug ) ?>" <?php echo $selected ?>><?php echo esc_html( $taxonomy->labels->name ) ?></option>
					<?php endforeach ?>
				</select>
				<br/>
				<?php if ( $current_taxonomy !== 'wpak-none' ) : ?>
					<?php
						$taxonomy_obj = get_taxonomy( $current_taxonomy );
						$terms = get_terms( $current_taxonomy );
					?>
					<label><?php echo esc_html( $taxonomy_obj->labels->name ) ?> : </label>
					<?php if ( !empty( $terms ) ): ?>
						<select name="term">
							<?php foreach ( $terms as $term ): ?>
								<?php $selected = $term->slug == $current_term ? 'selected="selected"' : '' ?>
								<option value="<?php echo esc_attr( $term->slug ) ?>" <?php echo $selected ?>><?php echo esc_html( $term->name ) ?></option>
							<?php endforeach ?>
						</select>
					<?php else: ?>
						<?php echo sprintf( __( 'No %s found', WpAppKit::i18n_domain ), $taxonomy_obj->labels->name ); ?>
					<?php endif ?>
				<?php endif ?>
			<?php else: ?>
				<?php echo sprintf( __( 'No taxonomy found for post type %s', WpAppKit::i18n_domain ), $current_post_type ); ?>
			<?php endif ?>
		<?php endif ?>
		<?php
	}

	public function get_options_from_posted_form( $data ) {
		$post_type = !empty( $data['post-type'] ) ? sanitize_key( $data['post-type'] ) : '';
		$taxonomy = !empty( $data['taxonomy'] ) ? sanitize_key( $data['taxonomy'] ) : '';
		$term = !empty( $data['term'] ) ? sanitize_key( $data['term'] ) : '';
		$hook = !empty( $data['hook'] ) ? sanitize_key( $data['hook'] ) : '';
		$options = array( 'post-type' => $post_type, 'taxonomy' => $taxonomy, 'term' => $term, 'hook' => $hook );
		return $options;
	}

}

WpakComponentsTypes::register_component_type( 'posts-list', array( 'label' => __( 'Posts list', WpAppKit::i18n_domain ) ) );
