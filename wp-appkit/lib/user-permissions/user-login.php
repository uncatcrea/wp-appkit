<?php

require_once( dirname( __FILE__ ) . '/authentication-settings.php' );

/**
 */
class WpakUserLogin {
	
	protected static $current_user = null;
	
	public static function is_user_logged_in() {
		return !empty( self::$current_user );
	}
	
	public static function log_user_from_authenticated_action( $app_id, $action, $auth_data, $to_check ) {
		$result = array( 'ok' => false, 'auth_error' => '' );
		
		$auth_engine = AuthenticationSettings::get_auth_engine_instance();
		
		//First check the validity of what was sent :
		$result = $auth_engine->check_authenticated_action( $app_id, $action, $auth_data, $to_check );
		if ( $result['ok'] ) {
			self::$current_user = '';
		}
		
		return $result;
	}
	
}
