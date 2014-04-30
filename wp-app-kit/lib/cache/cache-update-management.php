<?php
//TODO_WPAK

class WpakCacheUpdateManagement{
	
	public static function hooks(){
		add_action('transition_post_status', array(__CLASS__,'transition_post_status'),10,3);
		add_action('wp_update_comment_count', array(__CLASS__, 'wp_update_comment_count'),10,3);
		add_action('transition_comment_status', array(__CLASS__, 'transition_comment_status'),10,3);
		add_action('wp_insert_comment', array(__CLASS__, 'wp_insert_comment'),10,2);
	}
	
	//Triggered when a post is saved > do something on post cache
	public static function transition_post_status($new_status, $old_status, $post){
		global $wp_filter;
		if( isset($wp_filter['wpak_cache_update_post_changed']) ){
			do_action('wpak_cache_update_post_changed',$new_status,$old_status,$post);
		}else{
			WpakCache::delete_web_service_cache('wpak-delete-all-caches');
		}
	}
	
	//Triggered on post when it has a new comment > do something on post cache
	public static function wp_update_comment_count($post_id, $new, $old){
		global $wp_filter;
		if( isset($wp_filter['wpak_cache_update_post_comment_changed']) ){ 
			do_action('wpak_cache_update_post_comment_changed',$post_id,$new,$old);
		}else{
			WpakCache::delete_web_service_cache('wpak-delete-all-caches');
		}
	}
	
	//Triggered when a comment is inserted by a WP user > do something on comments cache
	public static function wp_insert_comment($comment_id, $comment){
		global $wp_filter;
		if( $comment->comment_approved == 1 ){ //posted by a WP user
			if( isset($wp_filter['wpak_cache_update_new_comment']) ){ 
				do_action('wpak_cache_update_new_comment',$comment);
			}else{
				WpakCache::delete_web_service_cache('wpak-delete-all-caches');
			}
		}
	}
	
	//Triggered when a comment is approved or rejected > do something on comments cache
	public static function transition_comment_status($new_status, $old_status, $comment){
		global $wp_filter;
		if( $new_status == 'approved' ){
			do_action('wpak_cache_update_new_comment',$comment);
			//WpakCache::delete_web_service_cache('wpak-delete-all-caches'); is done in wp_update_comment_count
		}else{
			do_action('wpak_cache_update_delete_comment',$new_status,$old_status,$comment);
			//WpakCache::delete_web_service_cache('wpak-delete-all-caches'); is done in wp_update_comment_count
		}
	}
}

WpakCacheUpdateManagement::hooks();