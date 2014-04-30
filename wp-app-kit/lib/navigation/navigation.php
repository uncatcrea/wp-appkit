<?php
require_once(dirname(__FILE__) .'/navigation-items-storage.php');
require_once(dirname(__FILE__) .'/navigation-bo-settings.php');

class WpakNavigation{
	
	public static function get_app_navigation($app_post_id){
		return WpakNavigationItemsStorage::get_navigation_items($app_post_id);
	}
	
}

class WpakNavigationItem{

	protected $component_id = '';
	protected $position = 0;
	protected $options = array();

	public function __construct($component_id,$position,$options=array()){
		$this->component_id = $component_id;
		$this->position = $position;
		$this->options = $options;
	}

	public function __get($attribute){
		return property_exists(__CLASS__, $attribute) ? $this->$attribute : null;
	}

	public function __isset($attribute){
		return isset($this->$attribute);
	}
	
	public function set_position($position){
		$this->position = $position;
	}
	
	public function to_array(){
		return array('component_id'=>$this->component_id,
					 'position'=>$this->position,
					 'options'=>$this->options
				    );
	}

}