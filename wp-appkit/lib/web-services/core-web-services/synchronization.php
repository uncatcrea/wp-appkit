<?php

class WpakWebServiceSynchronization {

	public static function hooks() {
		add_filter( 'wpak_read_synchronization', array( __CLASS__, 'read' ), 10, 3 );
	}

	public static function read( $service_answer, $query_vars, $app_id ) {
		$service_answer = array();

		$app_id = WpakApps::get_app_id( $app_id );

        WpakAddons::require_app_addons_php_files( $app_id );

		$service_answer = WpakComponents::get_components_synchro_data( $app_id );

        $service_answer['addons'] = WpakAddons::get_app_addons_dynamic_data( $app_id );

        $service_answer['dynamic_data'] = self::get_dynamic_data( $app_id );

		return ( object ) $service_answer;
	}

    /**
     * Handle dynamic data that are passed through synchronization webservice
     * and are refreshed at each app content refresh.
     */
    public static function get_dynamic_data( $app_id ) {
        $dynamic_data = [];

        //Send gmt_offset to the app so that local times can be computed properly:
        $dynamic_data['gmt_offset'] = get_option( 'gmt_offset' );

        /**
         * Use this wpak_dynamic_data filter to add your custom dynamic data to
         * the synchronization web service.
         * 
         * @var $dynamic_data Array   Array of dynamic data
         * @var $appid        Int     Application ID
         */
        $dynamic_data = apply_filters( 'wpak_dynamic_data', $dynamic_data, $app_id );

        return $dynamic_data;
    }
}

WpakWebServiceSynchronization::hooks();
