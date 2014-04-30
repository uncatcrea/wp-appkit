<?php
class WpakComponentsUtils{
	
	public static function get_formated_content(){
		global $post;
		
		$content = get_the_content();
	
		$replacement_image = self::get_unavailable_media_img();
	
		//Convert dailymotion video
		$content = preg_replace('/\[dailymotion\](.*?)(\[\/dailymotion\])/is','<div class="video">$1</div>',$content);
		$content = preg_replace('/<iframe (.*?)(src)="(.*?(www.dailymotion.com).*?)".*?>\s*<\/iframe>/is','<div class="video">$3</div>',$content);
	
		//Youtube and mp3 inserted via <a> :
		$content = preg_replace('/<a[^>]*href="[^"]*youtube.com.*?".*?>.*?(<\/a>)/is',$replacement_image,$content);
		$content = preg_replace('/<a[^>]*href="[^"]*(\.mp3).*?".*?>.*?(<\/a>)/is',$replacement_image,$content);
	
		//Delete [embed]
		$content = preg_replace('/\[embed .*?\](.*?)(\[\/embed\\])/is',$replacement_image,$content);
	
		//Replace iframes (slideshare etc...) by default image :
		$content = preg_replace('/<iframe([^>]*?)>.*?(<\/iframe>)/is',$replacement_image,$content);
	
		//Apply "the_content" filter : formats shortcodes etc... :
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
	
		$content = strip_tags($content,'<br/><br><p><div><h1><h2><h3><h4><h5><h6><a><span><sup><sub><img><i><em><strong><b><ul><ol><li><blockquote>');
	
		//Use this "wpak_post_content_format" filter to add your own formating to
		//apps posts and pages.
		//To overide (relace) this default formating completely, use the "wpak_posts_list_post_content"
		//and "wpak_page_content" hooks. 
		$content = apply_filters('wpak_post_content_format',$content,$post);
		
		return $content;
	}
	
	public static function get_post_excerpt($post){
		add_filter('excerpt_length',array('WpakComponentsUtilsHooksCallbacks','excerpt_length'));
		add_filter('excerpt_more',array('WpakComponentsUtilsHooksCallbacks','excerpt_more'));
		$post_excerpt = apply_filters('get_the_excerpt', $post->post_excerpt);
		return apply_filters('wpak_post_excerpt',$post_excerpt,$post);
	}
	
	public static function get_unavailable_media_img(){
		
		$params = array(
				'src' => get_bloginfo('wpurl') .'/wp-content/uploads/wpak_unavailable_media.png',
				'width' => 604,
				'height' => 332
		);
		
		$params = apply_filters('wpak_unavailable_media_img',$params);
		
		$img = '<img class="unavailable" alt="'. __('Unavailable content') .'" src="'. $params['src'] .'" width="'. $params['width'] .'" height="'. $params['height'] .'" />';
		
		return $img;
	}
	
}

class WpakComponentsUtilsHooksCallbacks{
	
	public static function excerpt_more($default_wp_excerpt_more){
		$excerpt_more = apply_filters('wpak_excerpt_more',' ...',$default_wp_excerpt_more);
		return $excerpt_more;
	}
	
	public static function excerpt_length($default_wp_excerpt_length){
		$excerpt_length = apply_filters('wpak_excerpt_length',30,$default_wp_excerpt_length);
		return $excerpt_length;
	}
	
}
