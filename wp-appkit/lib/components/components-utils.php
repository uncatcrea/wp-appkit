<?php

class WpakComponentsUtils {

	/**
	 * Default function to retrieve web service formated data for a post
	 */
	public static function get_post_data( $wp_post, $component = null ) {

		if ( $component === null ) {
			$component = new WpakComponent( 'wpak-internal', 'Internal', 'wpak-internal' );
		}

		global $post;
		$post = $wp_post;
		setup_postdata( $post );

		$post_data = array(
			'id' => $post->ID,
			'post_type' => $post->post_type,
			'date' => strtotime( $post->post_date ),
			'title' => apply_filters( 'the_title', $post->post_title, $post->ID ),
			'content' => '',
			'excerpt' => '',
			'thumbnail' => '',
			'author' => get_the_author_meta( 'nickname' ),
			'nb_comments' => ( int ) get_comments_number(),
			'permalink' => get_permalink( $post ),
			'slug' => $post->post_name
		);

		/**
		 * Filter post content. Use this to format app posts content your own way.
		 *
		 * To apply the default WP-AppKit formating to the content and add only minor modifications to it,
		 * use the "wpak_post_content_format" filter instead.
		 *
		 * @see WpakComponentsUtils::get_formated_content()
		 *
		 * @param string 			''    			The post content: an empty string by default.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$content = apply_filters( 'wpak_post_content', '', $post, $component );
		if ( empty( $content ) ) {
			$content = WpakComponentsUtils::get_formated_content();
		}
		$post_data['content'] = $content;

		$post_data['excerpt'] = WpakComponentsUtils::get_post_excerpt( $post );

		$post_featured_img_id = get_post_thumbnail_id( $post->ID );
		if ( !empty( $post_featured_img_id ) ) {

			/**
			 * Use this 'wpak_post_featured_image_size' to define a specific image
			 * size to pass to the web service for posts.
			 * By default the full (original) image size is used.
			 */
			$featured_image_size = apply_filters( 'wpak_post_featured_image_size', 'full', $post, $component );

			$featured_img_src = wp_get_attachment_image_src( $post_featured_img_id, $featured_image_size );
            $post_data['thumbnail'] = array();
			$post_data['thumbnail']['src'] = $featured_img_src[0];
			$post_data['thumbnail']['width'] = $featured_img_src[1];
			$post_data['thumbnail']['height'] = $featured_img_src[2];
		}

		/**
		 * Filter post data sent in web service.
		 *
		 * Use this for example to add a post meta to the default post data.
		 *
		 * @param array 			$post_data    	The default post data sent to an app.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$post_data = apply_filters( 'wpak_post_data', $post_data, $post, $component );

		return $post_data;
	}

	public static function get_formated_content() {

		//Set global $more to 1 so that get_the_content() behaves correctly with <!-- more --> tag:
		//(See wp-includes/class-wp.php::register_globals() and get_the_content())
		global $more;
		$more = 1;

		$post = get_post();

		$content = get_the_content();

		//Apply "the_content" filter : formats shortcodes etc... :
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		$allowed_tags = '';

		/**
		 * Filter that allows to set the HTML tags allowed for a given post.
		 * By default $allowed_tags is empty, meaning that all tags are allowed.
		 *
		 * @param string 	$allowed_tags   A string containing the concatenated list of allowed HTML tags.
		 * @param WP_Post 	$post 			The post object.
		 */
		$allowed_tags = apply_filters( 'wpak_post_content_allowed_tags', $allowed_tags, $post );

		if ( !empty( $allowed_tags ) ) {
			$content = strip_tags( $content, $allowed_tags );
		}

		/**
		 * Replace internal links in post content so that they open in app and not in browser.
		 * Only for PWA by default but can be customized with the "wpak_post_content_replace_internal_links" filter.
		 */
		$replace_internal_links = wpak_get_current_app_info( 'platform' ) === 'pwa';

		/**
		 * Use this filter to enable/disable internal links replacement in post content.
		 *
		 * @param bool     $replace_internal_links    Whether to replace internal links or not.
		 * @param WP_Post  $current_app               Current app.
		 * @param WP_Post  $post                      Post we are replacing internal links for.
		 */
		$replace_internal_links = apply_filters( 'wpak_post_content_replace_internal_links', $replace_internal_links, wpak_get_current_app(), $post );

		if ( $replace_internal_links ) {
			$content = self::replace_internal_links( $content, $post );
		}

		/**
		 * Filter a single post content.
		 *
		 * To override (replace) this default formatting completely, use
		 * "wpak_posts_list_post_content" and "wpak_page_content" filters.
		 *
		 * @param string 	$content   	The post content.
		 * @param WP_Post 	$post 		The post object.
		 */
		$content = apply_filters( 'wpak_post_content_format', $content, $post );

		return $content;
	}

	public static function get_post_excerpt( $post ) {
		add_filter( 'excerpt_length', array( 'WpakComponentsUtilsHooksCallbacks', 'excerpt_length' ) );
		add_filter( 'excerpt_more', array( 'WpakComponentsUtilsHooksCallbacks', 'excerpt_more' ) );
		$post_excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt );

		/**
		 * Filter a single post excerpt.
		 *
		 * @param string 	$post_excerpt   The post excerpt.
		 * @param WP_Post 	$post 			The post object.
		 */
		return apply_filters( 'wpak_post_excerpt', $post_excerpt, $post );
	}

	/**
	 * Used to replace urls that link to content that is also available in the app
	 * (used in "page" component for example).
	 *
	 * @param string $content Post content
	 * @param array $internal_ids IDs of posts considered as being available in the app
	 * @param callback $build_link_callback Function to call to build the link
	 * @param array $callback_args Args that are passed to the callback. NOTE : "post_id" is prepended to this array.
	 * @return string Modified content with filtered links
	 */
	public static function handle_internal_links( $content, $internal_ids, $build_link_callback, $callback_args ) {
		if ( preg_match_all( '/<a .*?(href="(.*?)").*?>/', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				if ( $post_id = url_to_postid( $match[2] ) ) {
					if ( in_array( $post_id, $internal_ids ) ) {
						$args = $callback_args;
						array_unshift( $args, $post_id );
						$content = str_replace( $match[1], 'href="' . call_user_func_array( $build_link_callback, $args ) . '"', $content );
					}
				}
			}
		}
		return $content;
	}

	/**
	 * Replace internal links in post content so that they open in app and not in browser.
	 */
	protected static function replace_internal_links( $content, $post ) {
		//Find all internal urls:
	    preg_match_all( '#(["\'])'. get_option('siteurl') .'/([^"\']+)#', $content, $internal_urls  );

	    if ( !empty( $internal_urls ) ) {

	        //For each internal url, find the matching wp entity (post, page etc).
	        foreach( $internal_urls[2] as $key => $internal_url ) {

	            $wp_query = self::get_wp_query_from_url( $internal_url );

	            if ( $wp_query && !empty( $wp_query->post ) ) {
	                //If the url correspond to a post or a page, replace the url by the corresponding internal app route:
	                if ( $wp_query->is_single() ) {
	                    $content = str_replace( $internal_urls[0][$key], $internal_urls[1][$key] .'#single/posts/'. $wp_query->post->ID .'/', $content );
	                } else if ( $wp_query->is_page() ) {
	                    $content = str_replace( $internal_urls[0][$key], $internal_urls[1][$key] .'#page/'. $wp_query->post->ID .'/', $content );
	                }
	            }

	        }

	    }

	    return $content;
	}

	/**
	 * Get WP_Query object from the given url.
	 * Url parsing logic inspired from WP::parse_request() (wp-includes/class-wp.php).
	 */
	protected static function get_wp_query_from_url( $url ) {
		global $wp_rewrite;

	    $wp_query = null;

	    $rewrite = $wp_rewrite->wp_rewrite_rules();

	    $match_found = false;
	    foreach ( (array) $rewrite as $match => $query ) {
	        if ( preg_match( "#^$match#", $url, $matches ) ) {
	            $query = preg_replace("!^.+\?!", '', $query);

	            // Substitute the substring matches into the query.
	            $query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

	            $wp_query = new WP_Query( $query );
	            break;
	        }
	    }

	    return $wp_query;
	}

}

class WpakComponentsUtilsHooksCallbacks {

	public static function excerpt_more( $default_wp_excerpt_more ) {
		/**
		 * Filter the string showed when a content is troncated to make an excerpt.
		 *
		 * @param string 	' ...'    					The default value overriden by this plugin.
		 * @param string 	$default_wp_excerpt_more   	The default value provided by WordPress.
		 */
		$excerpt_more = apply_filters( 'wpak_excerpt_more', ' ...', $default_wp_excerpt_more );
		return $excerpt_more;
	}

	public static function excerpt_length( $default_wp_excerpt_length ) {
		/**
		 * Filter the number of words included into an excerpt.
		 *
		 * @param int 	30    							The default value overriden by this plugin.
		 * @param int 	$default_wp_excerpt_length   	The default value provided by WordPress.
		 */
		$excerpt_length = apply_filters( 'wpak_excerpt_length', 30, $default_wp_excerpt_length );
		return $excerpt_length;
	}

}
