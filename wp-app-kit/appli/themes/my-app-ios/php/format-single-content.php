<?php
add_filter('wpak_post_content_format','wpak_theme_my_app_ios_format_content',10,2);
function wpak_theme_my_app_ios_format_content($content,$post){
	$content = preg_replace('/(<a [^>]*>\s*<img [^>]*>\s*<\/a>)(?!<p class="wp-caption-text">)/is','<div class="content-image">$1</div>',$content);
	$content = preg_replace('/(<img [^>]*>)(?!(<\/a>)|(<p class="wp-caption-text">))/is','<div class="content-image">$1</div>',$content);
	$content = preg_replace('/(<div [^>]*class="wp-caption)/is','$1 content-image',$content);
	return $content;
}