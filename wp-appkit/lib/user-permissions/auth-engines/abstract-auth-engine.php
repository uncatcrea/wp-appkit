<?php

abstract class WpakAuthEngine {
	
	abstract public function settings_meta_box_content( $post, $current_box );
	
	abstract public function save_posted_settings( $app_id );
	
	abstract public function get_webservice_answer( $app_id );
	
	abstract public function check_authenticated_action( $app_id, $action, $auth_data, $to_check );

}

