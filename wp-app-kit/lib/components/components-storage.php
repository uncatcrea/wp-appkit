<?php
class WpakComponentsStorage{
	
	const meta_id = '_wpak_components';
	
	public static function get_components($app_id){
		$components = get_post_meta($app_id,self::meta_id,true);
		return !empty($components) ? $components : array();
	}
	
	public static function get_nb_components($app_id){
		$components = self::get_components($app_id);
		return !empty($components) ? count($components) : 0;
	}
	
	public static function get_component($app_id,$component_slug_or_id){
		$components = self::get_components($app_id);
		
		$is_slug = !is_numeric($component_slug_or_id);
		
		foreach($components as $id => $component){
			if( $is_slug && $component_slug_or_id == $component->slug || $component_slug_or_id == $id){
				return $component;
			}
		}
		
		return null;
	}
	
	public static function component_exists($app_id,$component_slug_or_id){
		$components = self::get_components($app_id);
		
		$is_slug = !is_numeric($component_slug_or_id);
		
		foreach($components as $id => $component){
			if( $is_slug && $component_slug_or_id == $component->slug || $component_slug_or_id == $id){
				return $id;
			}
		}
		return false;
	}
	
	public static function add_or_update_component($app_id,WpakComponent $component,$component_id=0){
		
		if( empty($component_id) ){
			$component_id = self::generate_component_id($app_id);
		}else{
			$component_id = self::component_exists($app_id,$component_id);
		}
		
		if( !empty($component_id) ){
			$components = self::get_components($app_id);
			$components[$component_id] = $component;
			self::update_components($app_id,$components);
		}
		
		return $component_id;
	}
	
	public static function delete_component($app_id,$component_id){
		$deleted_ok = true;
		$components = self::get_components($app_id);
		if( array_key_exists($component_id,$components) ){
			unset($components[$component_id]);
			self::update_components($app_id,$components);
		}else{
			$deleted_ok = false;
		}
		return $deleted_ok;
	}
	
	public static function get_component_id($app_id,WpakComponent $component){
		return self::component_exists($app_id,$component->slug);
	}
	
	private static function update_components($app_id,$components){
		update_post_meta( $app_id, self::meta_id, $components );
	}
	
	private static function generate_component_id($app_id){
		$components = self::get_components($app_id);
		$id = 1;
		if( !empty($components) ){
			$id = max(array_keys($components)) + 1;
		}
		return $id;
	}
}