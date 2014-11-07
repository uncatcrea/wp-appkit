<?php

class WpakComponentTypeFavorites extends WpakComponentType {

	protected function compute_data( $component, $options, $args = array() ) {
		global $wpdb;

		do_action( 'wpak_before_component_favorites', $component, $options );

		$before_post_date = '';
		if ( !empty( $args['before_item'] ) && is_numeric( $args['before_item'] ) ) {
			$before_post = get_post( $args['before_item'] );
			if ( !empty( $before_post ) ) {
				$before_post_date = $before_post->post_date;
			}
		}

		$ids = !empty( $args['ids'] ) && is_array( $args['ids'] ) ? $args['ids'] : array();

		$query = array( 'post_type' => 'any', 'favorites' => $ids );
		$query_args = array( 'post_type' => 'any', 'id' => $ids );

		/**
		 * Filter the number of posts displayed into a favorites component.
		 *
		 * @param int 			    					Default number of posts.
		 * @param WpakComponent 	$component 			The component object.
		 * @param array 			$options 			An array of options.
		 * @param array 			$args 				An array of complementary arguments.
		 */
		$query_args['posts_per_page'] = apply_filters('wpak_favorites_posts_per_page', WpakSettings::get_setting( 'posts_per_page' ), $component, $options, $args );

		if ( !empty( $before_post_date ) ) {
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
		 * Filter args used for the query made into a favorites component.
		 *
		 * @param array 			$query_args    		An array of default args.
		 * @param WpakComponent 	$component 			The component object.
		 * @param array 			$options 			An array of options.
		 * @param array 			$args 				An array of complementary arguments.
		 * @param array 			$query 				Data about the query to retrieve on the app side.
		 */
		$query_args = apply_filters( 'wpak_favorites_query_args', $query_args, $component, $options, $args, $query );

		$posts_query = new WP_Query( $query_args );

		if ( !empty( $before_post_date ) ) {
			remove_filter( 'posts_where', $posts_where_callback );
			$query['is_last_page'] = $posts_query->found_posts <= count( $posts_query->posts );
		}

		$posts = $posts_query->posts;
		$total = $posts_query->found_posts;

		$posts_by_ids = array();
		foreach ( $posts as $post ) {
			$posts_by_ids[$post->ID] = self::get_post_data( $component, $post );
		}

		$this->set_specific( 'ids', array_keys( $posts_by_ids ) );
		$this->set_specific( 'total', $total );
		$this->set_specific( 'query', $query );
		$this->set_globals( 'posts', $posts_by_ids );
	}

	protected static function get_post_data( $component, $_post ) {
		global $post;
		$post = $_post;
		setup_postdata( $post );

		$post_data = array(
			'id' => $post->ID,
			'post_type' => $post->post_type,
			'date' => strtotime( $post->post_date ),
			'title' => $post->post_title,
			'content' => '',
			'excerpt' => '',
			'thumbnail' => '',
			'author' => get_the_author_meta( 'nickname' ),
			'nb_comments' => ( int ) get_comments_number()
		);

		/**
		 * Filter post content into a favorites component. Use this to format app posts content your own way.
		 *
		 * To apply the default App Kit formating to the content and add only minor modifications to it,
		 * use the "wpak_post_content_format" filter instead.
		 *
		 * @see WpakComponentsUtils::get_formated_content()
		 *
		 * @param string 			''    			The post content: an empty string by default.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$content = apply_filters( 'wpak_favorites_post_content', '', $post, $component );
		if ( empty( $content ) ) {
			$content = WpakComponentsUtils::get_formated_content();
		}
		$post_data['content'] = $content;

		$post_data['excerpt'] = WpakComponentsUtils::get_post_excerpt( $post );

		$post_featured_img_id = get_post_thumbnail_id( $post->ID );
		if ( !empty( $post_featured_img_id ) ) {
			$featured_img_src = wp_get_attachment_image_src( $post_featured_img_id, 'mobile-featured-thumb' );
			@$post_data['thumbnail']['src'] = $featured_img_src[0];
			$post_data['thumbnail']['width'] = $featured_img_src[1];
			$post_data['thumbnail']['height'] = $featured_img_src[2];
		}

		/**
		 * Filter post data sent to the app from a favorites component.
		 *
		 * Use this for example to add a post meta to the default post data.
		 *
		 * @param array 			$post_data    	The default post data sent to an app.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$post_data = apply_filters( 'wpak_favorites_post_data', $post_data, $post, $component );

		return ( object ) $post_data;
	}

	public function has_options( $component ) {
		return false;
	}

	public function get_options_to_display( $component ) {
		return array();
	}

	public function echo_form_fields( $component ) {
	}

	public function echo_form_javascript() {
	}

	public function get_ajax_action_html_answer( $action, $params ) {
	}

	protected static function echo_sub_options_html( $current_post_type, $current_taxonomy = '', $current_term = '', $current_hook = '' ) {
	}

	public function get_options_from_posted_form( $data ) {
		return array();
	}

}

WpakComponentsTypes::register_component_type( 'favorites', array( 'label' => __( 'Favorites', WpAppKit::i18n_domain ) ) );
