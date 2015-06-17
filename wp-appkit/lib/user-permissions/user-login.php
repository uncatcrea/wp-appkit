<?php

require_once( dirname( __FILE__ ) . '/authentication-settings.php' );

/**
 */
class WpakUserLogin {
	
	protected $current_user = null;
	
	public static function log_user_in() {
		$auth_engine = AuthenticationSettings::get_auth_engine_instance();
		self::$current_user = $auth_engine->log_user_in();
	}
	
	public static function is_user_logged_in() {
		return !empty( self::$current_user );
	}
	
}
