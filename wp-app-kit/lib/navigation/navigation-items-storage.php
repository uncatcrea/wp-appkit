<?php
class WpakNavigationItemsStorage{
	
	const meta_id = '_wpak_navigation_items';
	
	public static function get_navigation_items($post_id){
		$navigation_items = self::get_navigation_items_raw($post_id);
		$navigation_items = self::order_items($navigation_items);
		return !empty($navigation_items) ? $navigation_items : array();
	}
	
	private static function get_navigation_items_raw($post_id){
		$navigation_items = get_post_meta($post_id,self::meta_id,true);
		return !empty($navigation_items) ? $navigation_items : array();
	}
	
	public static function get_nb_navigation_items($post_id){
		return count(self::get_navigation_items_raw($post_id));
	}
	
	public static function navigation_item_exists_by_component($post_id,$component_id){
		$navigation_items = self::get_navigation_items_raw($post_id);
		if( !empty($navigation_items) ){
			foreach($navigation_items as $navigation_item_id => $item){
				if( $item->component_id == $component_id ){
					return $navigation_item_id;
				}
			}
		}
		return false;
	}
	
	public static function navigation_item_exists($post_id,$navigation_item_id){
		$navigation_items = self::get_navigation_items_raw($post_id);
		return !empty($navigation_items) ? array_key_exists($navigation_item_id, $navigation_items) : false;
	}
	
	public static function add_or_update_navigation_item($post_id,WpakNavigationItem $navigation_item){
		$navigation_item_id = 0;
		
		if( WpakComponentsStorage::component_exists($post_id,$navigation_item->component_id) ){
			$navigation_items = self::get_navigation_items_raw($post_id);
			if( !($navigation_item_id = self::navigation_item_exists_by_component($post_id,$navigation_item->component_id)) ){
				$navigation_item_id = self::generate_navigation_item_id($post_id);
			}
			$navigation_items[$navigation_item_id] = $navigation_item;
			self::update_navigation_items($post_id,$navigation_items);
		}
	
		return $navigation_item_id;
	}
	
	public static function delete_navigation_item($post_id,$navigation_item_id){
		$deleted_ok = true;
		$navigation_items = self::get_navigation_items_raw($post_id);
		if( array_key_exists($navigation_item_id,$navigation_items) ){
			unset($navigation_items[$navigation_item_id]);
			self::update_navigation_items($post_id,$navigation_items);
		}else{
			$deleted_ok = false;
		}
		return $deleted_ok;
	}
	
	public static function get_navigation_indexed_by_components_slugs($post_id,$only_nav_items_options=false){
		$navigation_indexed_by_components = array();
		$navigation_items = self::get_navigation_items_raw($post_id);
		if( !empty($navigation_items) ){
			$navigation_items = self::order_items($navigation_items);
			foreach($navigation_items as $nav_item_id => $nav_item){
				if( WpakComponentsStorage::component_exists($post_id,$nav_item->component_id) ){
					$component = WpakComponentsStorage::get_component($post_id,$nav_item->component_id);
					$navigation_indexed_by_components[$component->slug] = $only_nav_items_options ? $nav_item->options : $nav_item;
				}
			}
		}
		return $navigation_indexed_by_components;
	}
	
	public static function get_navigation_components($post_id){
		$components = array();
		$navigation_items = self::get_navigation_items_raw($post_id);
		if( !empty($navigation_items) ){
			$navigation_items = self::order_items($navigation_items);
			foreach($navigation_items as $nav_item_id => $nav_item){
				if( WpakComponentsStorage::component_exists($post_id,$nav_item->component_id) ){
					$components[$nav_item->component_id] = WpakComponentsStorage::get_component($post_id,$nav_item->component_id);
				}
			}
		}
		return $components;
	}
	
	public static function component_in_navigation($post_id,$component_id){
		$navigation_items = self::get_navigation_items_raw($post_id);
		if( !empty($navigation_items) ){
			foreach($navigation_items as $nav_item_id => $nav_item){
				if( $nav_item->component_id == $component_id ){
					return $nav_item_id;
				}
			}
		}
		return false;
	}
	
	public static function get_navigation_item_id($post_id,$navigation_item){
		$nav_item_id = self::navigation_item_exists_by_component($post_id,$navigation_item->component_id);
		return $nav_item_id === false ? 0 : $nav_item_id;
	}
	
	public static function update_items_positions($post_id,$items_positions){
		if( !empty($items_positions) ){
			$navigation_items = self::get_navigation_items_raw($post_id);
			foreach($items_positions as $nav_item_id => $nav_item_position){
				if( array_key_exists($nav_item_id,$navigation_items) ){
					$navigation_items[$nav_item_id]->set_position($nav_item_position);
				}
			}
			self::update_navigation_items($post_id,$navigation_items);
		}
	}
	
	private static function update_navigation_items($post_id,$navigation_items){
		$navigation_items = self::order_items($navigation_items);
		update_post_meta( $post_id, self::meta_id, $navigation_items );
	}
	
	private static function order_items($navigation_items){
		$ordered = array();
		
		if( !empty($navigation_items) ){
			$to_order = array();
			foreach($navigation_items as $navigation_item_id => $item){
				$to_order[$navigation_item_id] = $item->position;
			}
			asort($to_order);
			$i=1;
			foreach(array_keys($to_order) as $navigation_item_id){
				$nav_item = $navigation_items[$navigation_item_id];
				$nav_item->set_position($i);
				$ordered[$navigation_item_id] = $nav_item;
				$i++;
			}
		}
		
		return $ordered;
	}
	
	private static function generate_navigation_item_id($post_id){
		$nav_items = self::get_navigation_items_raw($post_id);
		$id = 1;
		if( !empty($nav_items) ){
			$id = max(array_keys($nav_items)) + 1;
		}
		return $id;
	}
	
}