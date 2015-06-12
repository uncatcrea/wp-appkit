<?php

require_once( dirname( __FILE__ ) . '/login-engines/http-basic-auth.php');

/**
 */
class WpakUserLogin {
	
	protected $login_engine = null;
	protected $default_login_engine_class = 'WpakHttpBasicAuth';
	
	protected $current_user = null;
	
	protected static function load_login_engine() {
		if ( self::$login_connector === null ) {
			$login_engine_class = apply_filters( 'wpak_login_engine', self::$default_login_engine_class );
			if ( class_exists( $login_engine_class ) && is_subclass_of( 'WpakHttpBasicAuth', 'WpakLoginEngine' ) ) {
				self::$login_engine = new $login_engine_class();
			}
		}
	}
	
	public static function log_user_in() {
		self::load_login_engine();
		self::$login_engine->log_user_in();
	}
	
	public static function is_user_logged_in() {
		self::load_login_engine();
	}
	
}
