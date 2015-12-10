<?php

require_once( dirname( __FILE__ ) . '/authentication-settings.php' );

/**
 */
class WpakUserLogin {
	
	protected static $current_user = null;
	
	public static function is_user_logged_in() {
		return !empty( self::$current_user );
	}
	
	public static function log_user_from_authenticated_action( $app_id, $action, $auth_data, $to_check = array() ) {
		$result = array( 'ok' => false, 'auth_error' => '' );
		
		$auth_engine = AuthenticationSettings::get_auth_engine_instance();
		
		//First check the validity of what was sent :
		$result = $auth_engine->check_authenticated_action( $app_id, $action, $auth_data, $to_check );
		if ( $result['ok'] ) {
			//OK, log the user in for the current script execution :
			$user_wp = get_user_by( 'login', $result['user'] );
			self::$current_user = wp_set_current_user( $user_wp->ID );
		}
		
		return $result;
	}
	
	public static function get_current_user() {
		return self::$current_user;
	}
	
}
