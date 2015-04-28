<?php

class WpakComponentsUtils {

	public static function get_formated_content() {
		$post = get_post();

		$content = get_the_content();

		$replacement_image = self::get_unavailable_media_img();

		//Convert dailymotion video
		$content = preg_replace( '/\[dailymotion\](.*?)(\[\/dailymotion\])/is', $replacement_image, $content );

		//Youtube :
		$content = preg_replace( '/<a[^>]*href="[^"]*youtube.com.*?".*?>.*?(<\/a>)/is', $replacement_image, $content );
		$content = preg_replace( '/\[youtube\](.*?)(\[\/youtube\])/is', $replacement_image, $content );
		
		//Mp3 :
		$content = preg_replace( '/<a[^>]*href="[^"]*(\.mp3).*?".*?>.*?(<\/a>)/is', $replacement_image, $content );
		
		//Delete [embed]
		$content = preg_replace( '/\[embed .*?\](.*?)(\[\/embed\\])/is', $replacement_image, $content );

		//Replace iframes (slideshare etc...) by default image :
		$content = preg_replace( '/<iframe([^>]*?)>.*?(<\/iframe>)/is', $replacement_image, $content );

		//Apply "the_content" filter : formats shortcodes etc... :
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		$allowed_tags = '<br/><br><p><div><h1><h2><h3><h4><h5><h6><a><span><sup><sub><img><i><em><strong><b><ul><ol><li><blockquote><pre>';

		/**
		 * Filter allowed HTML tags for a given post.
		 *
		 * @param string 	$allowed_tags   A string containing the concatenated list of default allowed HTML tags.
		 * @param WP_Post 	$post 			The post object.
		 */
		$allowed_tags = apply_filters( 'wpak_post_content_allowed_tags', $allowed_tags, $post );

		$content = strip_tags( $content, $allowed_tags );

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

	public static function get_unavailable_media_img() {

		$upload_dir = wp_upload_dir();
		if ( !empty( $upload_dir['error'] ) ) {
			return '';
		}

		$params = array(
			'src' => $upload_dir['baseurl'] . '/wpak_unavailable_media.png',
			'width' => 604,
			'height' => 332
		);

		/**
		 * Filter parameters of the default image showed when a media is unavailable.
		 *
		 * @param array 	$params   The default parameters.
		 */
		$params = apply_filters( 'wpak_unavailable_media_img', $params );

		$img = '<img class="unavailable" alt="' . __( 'Unavailable content', WpAppKit::i18n_domain ) . '" src="' . $params['src'] . '" width="' . $params['width'] . '" height="' . $params['height'] . '" />';

		return $img;
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
