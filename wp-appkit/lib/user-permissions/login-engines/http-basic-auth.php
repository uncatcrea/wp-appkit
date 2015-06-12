<?php

require_once( dirname( __FILE__ ) . '/login-engines/abstract-login-engine.php');

class WpakHttpBasicAuth extends WpakLoginEngine {
	
	public function log_user_in() {
		if ( isset($_SERVER['PHP_AUTH_USER'] ) ) {
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];
			var_dump($username,$password);
		}
	}
	
}


