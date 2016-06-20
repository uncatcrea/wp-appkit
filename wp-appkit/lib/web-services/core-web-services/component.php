<?php

class WpakWebServiceComponent {

	public static function hooks() {
		add_filter( 'wpak_read_one_component', array( __CLASS__, 'read_one' ), 10, 3 );
	}

	public static function read_one( $service_answer, $id, $app_id ) {
		$service_answer = array();

		$app_id = WpakApps::get_app_id( $app_id );
		$component_slug = addslashes( $id );

		$args = $_GET; //Check on data sent through $_GET is made inside of each component.

		$service_answer = WpakComponents::get_component_data( $app_id, $component_slug, $args );

		return ( object ) $service_answer;
	}

}

WpakWebServiceComponent::hooks();
