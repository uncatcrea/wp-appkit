<?php
class WpakThemesStorage{
	
	const meta_id = '_wpak_app_theme_choice';
	
	public static function get_current_theme_options($post_id){
		return self::get_theme_options($post_id,self::get_current_theme($post_id));
	}
	
	public static function get_current_theme($post_id){
		$themes = self::get_themes_raw($post_id);
		return $themes['current_theme'];
	}
	
	public static function get_theme_options($post_id,$theme_slug){
		$themes = self::get_themes_raw($post_id);
		return  isset($themes['themes'][$theme_slug]) ? $themes['themes'][$theme_slug] : false;
	}
	
	public static function set_current_theme($post_id,$theme_slug){
		$themes = self::get_themes_raw($post_id);
		$themes['current_theme'] = $theme_slug;
		self::update_themes($post_id,$themes);
	}
	
	public static function set_theme_options($post_id,$theme_slug,$options){
		$themes = self::get_themes_raw($post_id);
		@$themes['themes'][$theme_slug]['options'] = $options;
		self::update_themes($post_id,$themes);
	}
	
	private static function update_themes($post_id,$new_themes){
		update_post_meta( $post_id, self::meta_id, $new_themes );
	}
	
	private static function get_themes_raw($post_id){
		$themes = get_post_meta($post_id,self::meta_id,true);
		if( !isset($themes['current_theme'] )){
			$themes['current_theme'] = 'default';
		}
		return $themes;
	}
}