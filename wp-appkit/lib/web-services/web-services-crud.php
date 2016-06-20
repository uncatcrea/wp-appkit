<?php

require_once( dirname( __FILE__ ) . '/core-web-services/synchronization.php' );
require_once( dirname( __FILE__ ) . '/core-web-services/comments.php' );
require_once( dirname( __FILE__ ) . '/core-web-services/component.php' );
require_once( dirname( __FILE__ ) . '/core-web-services/live-query.php' );
require_once( dirname( __FILE__ ) . '/core-web-services/authentication.php' );

class WpakWebServiceCrud {
	
	public static function create( $app_id, $service_slug, $data ) {

		WpakWebServiceContext::set_context( $app_id, $service_slug, 'create', $data );
				
		self::before_webservice( $app_id, $service_slug, 'create', $data );
		
		$service_answer = array();
		$service_answer = apply_filters( 'wpak_create_' . $service_slug, $service_answer, $data, $app_id );
		$service_answer = apply_filters( 'wpak_create', $service_answer, $service_slug, $data, $app_id );

		$service_answer = self::after_webservice( $service_answer, $app_id, $service_slug, 'create', $data );
		
		return $service_answer;
	}

	public static function read( $app_id, $service_slug, $query_vars ) {

		WpakWebServiceContext::set_context( $app_id, $service_slug, 'read', $query_vars );
		
		self::before_webservice( $app_id, $service_slug, 'read', $query_vars );
		
		$service_answer = array();
		$service_answer = apply_filters( 'wpak_read_' . $service_slug, $service_answer, $query_vars, $app_id );
		$service_answer = apply_filters( 'wpak_read', $service_answer, $service_slug, $query_vars, $app_id );

		$service_answer = self::after_webservice( $service_answer, $app_id, $service_slug, 'read', $query_vars );
		
		return $service_answer;
	}

	public static function read_one( $app_id, $service_slug, $id ) {

		WpakWebServiceContext::set_context( $app_id, $service_slug, 'read_one', $id );
		
		self::before_webservice( $app_id, $service_slug, 'read_one', $id );
		
		$service_answer = array();
		$service_answer = apply_filters( 'wpak_read_one_' . $service_slug, $service_answer, $id, $app_id );
		$service_answer = apply_filters( 'wpak_read_one', $service_answer, $service_slug, $id, $app_id );

		$service_answer = self::after_webservice( $service_answer, $app_id, $service_slug, 'read_one', $id );
		
		return $service_answer;
	}

	public static function update( $app_id, $service_slug, $data ) {

		WpakWebServiceContext::set_context( $app_id, $service_slug, 'update', $data );
		
		self::before_webservice( $app_id, $service_slug, 'update', $data );
		
		$service_answer = array();
		$service_answer = apply_filters( 'wpak_update_' . $service_slug, $service_answer, $data, $app_id );
		$service_answer = apply_filters( 'wpak_update', $service_answer, $service_slug, $data, $app_id );

		$service_answer = self::after_webservice( $service_answer, $app_id, $service_slug, 'update', $data );
		
		return $service_answer;
	}

	public static function delete( $app_id, $service_slug, $id ) {

		WpakWebServiceContext::set_context( $app_id, $service_slug, 'delete', $id );
		
		self::before_webservice( $app_id, $service_slug, 'delete', $id );
		
		$service_answer = array();
		$service_answer = apply_filters( 'wpak_delete_' . $service_slug, $service_answer, $id, $app_id );
		$service_answer = apply_filters( 'wpak_delete', $service_answer, $service_slug, $id, $app_id );

		$service_answer = self::after_webservice( $service_answer, $app_id, $service_slug, 'delete', $id );
		
		return $service_answer;
	}

	protected static function before_webservice( $app_id, $service_slug, $crud_action, $data ) {
		
		/**
		 * 'wpak_before_webservice' action hook.
		 * Use this to do some treaments (like user authentication) before web 
		 * services are processed.
		 * 
		 * @param $service_slug    string       Webservice slug ('synchronization', 'live-query', 'authentication' etc)
		 * @param $app_id          int          Application ID
		 * @param $crud_action     string       Webservice CRUD action ('create', 'read', 'read_one', 'update', 'delete')
		 * @param $data            array|int    Webservice query data (or item id for 'read_one')
		 */
		do_action( 'wpak_before_webservice', $service_slug, $app_id, $crud_action, $data );
		
	}
	
	protected static function after_webservice( $service_answer, $app_id, $service_slug, $crud_action, $data ) {
		
		/**
		 * Use this 'wpak_webservice_answer' to customize the web service answer 
		 * just before it is sent to app.
		 * 
		 * @param $service_answer  Object (StdClass)   Webservice answer to filter
		 * @param $service_slug    string              Webservice slug ('synchronization', 'live-query', 'authentication' etc)
		 * @param $app_id          int                 Application ID
		 * @param $crud_action     string              Webservice CRUD action ('create', 'read', 'read_one', 'update', 'delete')
		 * @param $data            array|int           Webservice query data (or item id for 'read_one')
		 */
		$service_answer = apply_filters( 'wpak_webservice_answer', $service_answer, $service_slug, $app_id, $crud_action, $data );
		
		return $service_answer;
	}
	
}
