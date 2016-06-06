<?php

class WpakWebServiceLiveQuery {

	public static function hooks() {
		add_filter( 'wpak_read_live-query', array( __CLASS__, 'read' ), 10, 3 );
	}

	/**
	 * Retrieve "Live query" web service answer.
	 * 
	 * Any custom input params passed through the web service GET data can be
	 * retrieved via WpakWebServiceContext::getClientAppParam( 'my_param' );
	 * 
	 * The following input params are automatically recognized and interpreted :
	 * - 'wpak_component_slug' : if present, the WS automatically retrieve data
	 *   about the given component
	 * - 'wpak_query_action' : optionnaly use along with 'wpak_component_slug'. Can be :
	 *     -- 'get-component' : default value : retrieves default component data
	 *     -- 'get-items' : retrieves only the 'wpak_items_ids' items 
	 * - 'wpak_items_ids' : array of items ids to retrieve (when wpak_query_action = get-items)
	 * 
	 * @return array $service_answer Web service answer : Advised answer structure :
	 * By default, app core automatically knows what to do with an answer containing 
	 * the following keys :
	 * - 'globals'
	 * - 'component' or 'components'
	 * But the answer structure can be totally overriden, provided it is understood on
	 * app side using the dedicated hooks.
	 */
	public static function read( $service_answer, $query_vars, $app_id ) {

		$app_id = WpakApps::get_app_id( $app_id );

		$component_slug = WpakWebServiceContext::getClientAppParam( 'wpak_component_slug' );
		$action = WpakWebServiceContext::getClientAppParam( 'wpak_query_action' );
		$action = empty( $action ) || !in_array( $action, array('get-component', 'get-items') ) ? 'get-component' : $action;
		
		if ( !empty( $component_slug ) ) {

			$service_answer = array(
				'globals' => array(), //array of items with global ('posts', 'pages' etc...) as key
				'component' => array(),
			);
			
			if ( is_array( $component_slug ) ) {
				
				//The only valid action is 'get-component' if $component_slug is an array :
				if ( $action == 'get-component' ) {
					
					//Retrieve data for all given components and merge globals :
					unset( $service_answer['component'] );
					$service_answer['components'] = array();
					foreach ( $component_slug as $slug ) {
						$component_data = WpakComponents::get_component_data( $app_id, $slug );
						if ( !empty( $component_data ) ) {
							foreach ( $component_data['globals'] as $global => $items ) {
								foreach ( $items as $k => $item ) {
									$service_answer['globals'][$global][$k] = $item;
								}
							}
							$service_answer['components'][$slug] = $component_data['component'];
						}
					}
					
				}
				
			} else {
				//Only one component given : simply retrieve its data :
				switch ( $action ) {
					case 'get-component' :
						$service_answer = WpakComponents::get_component_data( $app_id, $component_slug );
						break;
					case 'get-items' :
						$items_ids = WpakWebServiceContext::getClientAppParam( 'wpak_items_ids' );
						if ( !empty( $items_ids ) ) {
							$items_ids = !is_array( $items_ids ) && is_numeric( $items_ids ) ? array( intval( $items_ids ) ) : array_map( 'intval', $items_ids );
							$service_answer = WpakComponents::get_component_items( $app_id, $component_slug, $items_ids );
						}
						break;
				}
			}
		}

		$query_params = WpakWebServiceContext::getClientAppParams();

		$service_answer = apply_filters( 'wpak_live_query', $service_answer, $query_params, $app_id, $query_vars );

		return ( object ) $service_answer;
	}

}

WpakWebServiceLiveQuery::hooks();
