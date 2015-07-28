<?php

class WpakWebServiceAuthentication {

	public static function hooks() {
		add_filter( 'wpak_read_authentication', array( __CLASS__, 'read' ), 10, 3 );
	}

	public static function read( $service_answer, $query_vars, $app_id ) {
		$service_answer = array();

		$auth_params = WpakWebServiceContext::getClientAppParams();
		if ( !empty( $auth_params['auth_action'] ) ) {
			$app_id = WpakApps::get_app_id( $app_id );
			$auth_engine = AuthenticationSettings::get_auth_engine_instance();
			$service_answer = $auth_engine->get_webservice_answer( $app_id );
		} else {
			$service_answer = array( 'error' => 'no-auth-action' ); //This will set webservice answer status to 0.
		}

		return ( object ) $service_answer;
	}

}

WpakWebServiceAuthentication::hooks();
