<?php

abstract class WpakAuthEngine {
	
	abstract public function log_user_in();
	
	abstract public function settings_meta_box_content( $post, $current_box );
	
	abstract public function save_posted_settings( $app_id );
	
	abstract public function get_webservice_answer( $app_id );

}

