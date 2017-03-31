<?php
/*
 * @desc Add custom data to what is returned by the web services. All custom data will be available to the JS API.
 * @param $post_data
 * @param $post
 * @param $component
 */
function wpak_add_custom_data( $post_data, $post, $component ) {
    
    // Add subhead. Expected as a post custom field.
    // Usage in app's templates: <%= post.subhead %>
    $post_data['subhead'] = get_post_meta($post->ID, 'subhead', true);

    // Add post thumbnail caption.
    // Usage in app's templates: <%= post.thumbnail.caption %>
    $thumbnail_id = get_post_thumbnail_id( $post->ID );
	if ( $thumbnail_id ) {
		$image_post = get_post( $thumbnail_id );
		if ( $image_post ) {
			if ( !empty( $post_data['thumbnail'] ) ) {
				$post_data['thumbnail']['caption'] = $image_post->post_excerpt;
			}
		}
	}
    
    return $post_data; // Return the modified $post_data

}

add_filter( 'wpak_post_data', 'wpak_add_custom_data', 10, 3 );
?>