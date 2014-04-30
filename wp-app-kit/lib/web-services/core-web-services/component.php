<?php

class WpakWebServiceComponent{

	public static function hooks(){
		add_filter('wpak_read_one_component',array(__CLASS__,'read_one'),10,3);
	}
	
	public function read_one($service_answer,$id,$app_id){
		$service_answer = array();
		
		$app_id = WpakApps::get_app_id($app_id);
		$component_slug = addslashes($id);
		
		$args = array();
		
		if( isset($_GET['before_item']) && !empty($_GET['before_item']) ){
			$args['before_item'] = addslashes($_GET['before_item']);
		}
		
		$service_answer = WpakComponents::get_component_data($app_id,$component_slug,$args);
		
		return (object)$service_answer;
	}
	
}

WpakWebServiceComponent::hooks();