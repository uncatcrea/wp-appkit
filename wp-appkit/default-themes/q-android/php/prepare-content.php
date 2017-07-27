<?php
/*
 * @desc Make any necessary modifications to post content before the web service returns it to the application
 * @param $content
 * @param $post
 */
function wpak_prepare_content($content,$post){

	// Bail in case of empty content
	if( empty( $content ) ) {
		return $content;
	}
	
    libxml_use_internal_errors(true);

    // Create a DOM document from the post content
    $dom = new domDocument;
    $dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
  
    // Empty all iframes src attribute to defer iframes loading
    // Done notably for videos
    $iframeCollection = $dom->getElementsByTagName("iframe");
    
    foreach($iframeCollection as $iframe) {
        $iframe->setAttribute('data-src',$iframe->getAttribute('src'));
        $iframe->setAttribute('src','');
    }

    // Get all clickable images in the post content
    // Deactivate image hyperlinks (stripping href)
    // Add class to identify content image
    $xpath = new DOMXPath($dom);
    
    $imgCollection1 = $xpath->query("//a/img");
    
    foreach($imgCollection1 as $img) {
        $img->parentNode->removeAttribute('href');
        $img->parentNode->setAttribute('class','content-image-link');
    }

    // Get all images in the post content
    // Strip all attibutes that may cause problem when handling responsive images
    // Note: srcset and sizes are not supported yet (iOS9)
    // Add onerror event handler to be able to display a default image when a problem occurs loading images (most of the time being offline)
    $imgCollection2 = $xpath->query("//img");
    
    foreach($imgCollection2 as $img) {
        $img->removeAttribute('height'); // Strip height attribute
        $img->removeAttribute('width');  // Strip width attribute
        $img->removeAttribute('srcset'); // Strip srcset attribute
        $img->removeAttribute('sizes');  // Strip sizes attribute
        $img->removeAttribute('style');  // strip style attribute
        $img->setAttribute('onerror','displayDefaultImage(this);'); // Add onerror event handler
    }

    // Get all caption elements added by WordPress
    $eCollection = $xpath->query("//*[contains(@class,'wp-caption')]");

    foreach($eCollection as $e) {
        $e->removeAttribute('style'); // Strip style attribute
    }
    
    $content = $dom->saveHTML();
    $content = preg_replace( '/\s*<!DOCTYPE .*?'.'>\s*/','',$content);
    $content = preg_replace( '/\s*<\?xml encoding="utf-8" \?'.'>\s*/','',$content);
    $content = preg_replace( '/\s*<\/?(html|body)>/','',$content);
    
    libxml_use_internal_errors(false);

    return $content; // Return modified post content
}

add_filter('wpak_post_content_format','wpak_prepare_content',10,2);
?>