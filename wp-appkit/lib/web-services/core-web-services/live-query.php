<?php

class WpakWebServiceLiveQuery {

	public static function hooks() {
		add_filter( 'wpak_read_live-query', array( __CLASS__, 'read' ), 10, 3 );
	}

	public static function read( $service_answer, $query_vars, $app_id ) {
		
		//Advised answer structure :
		//By default, app core automatically knows what to do with an answer containing 
		//the following keys :
		//- 'globals'
		//- 'component'
		//- 'type' ('merge' or 'replace')
		//But the answer structure can be totally overriden, provided it is understood on
		//app side using the dedicated hooks.
		$service_answer = array(
			'globals' => array(), //array of items with global ('posts', 'pages' etc...) as key
			'component' => array(),
			'type' => 'replace'
		);

		$app_id = WpakApps::get_app_id( $app_id );
		
		$component = WpakWebServiceContext::getClientAppParam('component');

		$service_answer = apply_filter( 'wpak_live_query', $service_answer, $app_id, $query_vars );
		
		return ( object ) $service_answer;
	}

}

WpakWebServiceLiveQuery::hooks();
