<?php

/**
 * Static class that contains context info and data about the currently
 * called web service.
 */
class WpakWebServiceContext {

	/**
	 * App for which the current web service is called
	 * @var WP post object
	 */
	protected static $app = null;

	/**
	 * Slug of the current web service
	 * @var string
	 */
	protected static $web_service = '';

	/**
	 * Current web service CRUD action : create, read, read_one, update or delete.
	 * @var string
	 */
	protected static $crud_action = '';

	/**
	 * Query data: query vars for read action, id for read_one/delete, posted object for update/create
	 * @var array|int
	 */
	protected static $query_data = '';

	/**
	 * Sets the current web service's crud action
	 * @param string $crud_action 'create', 'read', 'update' or 'delete'
	 */
	public static function set_context( $app_id, $service_slug, $crud_action, $query_data ) {
		$app = WpakApps::get_app( $app_id );
		if( $app ) {
			self::$app = $app;
			self::$web_service = $service_slug;
			self::$crud_action = $crud_action;
			self::$query_data = $query_data;
		}
	}

	/**
	 * Retrieves the app for which the current web service is called
	 * @return WP_Post Current app
	 */
	public static function get_current_app() {
		return self::$app;
	}

	/**
	 * Retrieves the app for which the current web service is called
	 * @return array('web_service', 'crud_action', 'query_data')
	 */
	public static function get_current_web_service_info() {
		return array( 'web_service' => self::$web_service, 'crud_action' => self::$crud_action, 'query_data' => self::$query_data);
	}

	/**
	 * Retrieves params that are sent by the client app when it calls a webservice.
	 *
	 * @return Array Params sent by the client app when calling the web service.
	 */
	public static function getClientAppParams() {
		$client_app_params = array();

		//For now we only handle params for "read" type web services :
		if ( self::$crud_action == 'read' || self::$crud_action == 'read_one' ) {
			$client_app_params = array();

			if ( !empty( $_GET ) && is_array( $_GET ) ) {
				foreach ( $_GET as $key => $value ) {
					$client_app_params[$key] = self::getClientAppParam( $key );
				}
			}

		}

		return $client_app_params;
	}

	/**
	 * Retrieves a given params that is sent by the client app when it calls
	 * a webservice.
	 * @return Array Param sent by the client app when calling the web service
	 */
	public static function getClientAppParam( $key ) {
		$client_app_param_value = null;

		//For now we only handle params for "read" type web services :
		if ( self::$crud_action == 'read' || self::$crud_action == 'read_one' ) {
			if ( isset( $_GET[$key] ) ) {
				if ( is_numeric( $_GET[$key] ) ) {
					$client_app_param_value = intval( $_GET[$key] );
				} else {
					$client_app_param_value = $_GET[$key];
					//Sanitizing $_GET[$key] here makes authentication fail, because secret key must not be sanitized.
                    //More sanitization can and should be made in functions that use getClientAppParam(),
					//as getClientAppParam() is designed to allow the app theme developer to retrieve on server side (via hooks)
					//the data (int/string/array) he sent from the app.
				}
			}
		}

		return $client_app_param_value;
	}

	public static function isWebServiceQuery() {
		return !empty( self::$app );
	}

}

/********************************************************************************
 * Global functions to retrieve info about current web service context
 */

/**
 * Returns true if we're currently retrieving data for an app, false otherwise.
 * If you pass an app id (or slug) this will also check that the given app is displayed.
 *
 * Useful for example when you retrieve data for a post, to know if you're doing it
 * for an app or for WP's standard front end (WP theme).
 *
 * @param int|string $app_id_or_slug (Optional) App ID (or slug) to test
 * @return boolean
 */
function wpak_is_app( $app_id_or_slug = 0 ) {
	$is_app = WpakWebServiceContext::isWebServiceQuery();

	if( $is_app && !empty( $app_id_or_slug ) ) {
		$current_app = WpakWebServiceContext::get_current_app();
		$app_to_check = WpakApps::get_app( $app_id_or_slug );
		$is_app = !empty( $app_to_check ) && $app_to_check->ID === $current_app->ID;
	}

	return $is_app;
}

/**
 * If currently retrieving content for an app (web service), returns the corresponding app.
 * @return WP_Object|null App object, or null if not currently in a web service context.
 */
function wpak_get_current_app() {
	return WpakWebServiceContext::get_current_app();
}

/**
 * If currently retrieving content for an app (web service), returns the corresponding app id.
 * @return int App id, or 0 if not currently in a web service context.
 */
function wpak_get_current_app_id() {
	$current_app = WpakWebServiceContext::get_current_app();
	return !empty( $current_app ) ? $current_app->ID : 0;
}

/**
 * If currently retrieving content for an app (web service), returns the corresponding app slug.
 * @return string App slug, or empty string if not currently in a web service context.
 */
function wpak_get_current_app_slug() {
	$current_app = WpakWebServiceContext::get_current_app();
	return !empty( $current_app ) ? $current_app->post_name : '';
}
