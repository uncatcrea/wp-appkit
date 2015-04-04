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
		//But the answer structure can be totally overriden, provided it is understood on
		//app side using the dedicated hooks.
		$service_answer = array(
			'globals' => array(), //array of items with global ('posts', 'pages' etc...) as key
			'component' => array(),
		);

		$app_id = WpakApps::get_app_id( $app_id );

		$component_slug = WpakWebServiceContext::getClientAppParam( 'component_slug' );
		if ( !empty( $component_slug ) ) {

			$args = WpakWebServiceContext::getClientAppParams();

			if ( is_array( $component_slug ) ) {
				//Retrieve data for all given components and merge globals :
				unset( $service_answer['component'] );
				$service_answer['components'] = array();
				foreach ( $component_slug as $slug ) {
					$component_data = WpakComponents::get_component_data( $app_id, $slug, $args );
					if ( !empty( $component_data ) ) {
						foreach ( $component_data['globals'] as $global => $items ) {
							foreach ( $items as $k => $item ) {
								@$service_answer['globals'][$global][$k] = $item;
							}
						}
						$service_answer['components'][$slug] = $component_data['component'];
					}
				}
			} else {
				//Only one component given : simply retrieve its data :
				$service_answer = WpakComponents::get_component_data( $app_id, $component_slug, $args );
			}
		}

		$service_answer = apply_filters( 'wpak_live_query', $service_answer, $app_id, $query_vars );

		return ( object ) $service_answer;
	}

}

WpakWebServiceLiveQuery::hooks();
