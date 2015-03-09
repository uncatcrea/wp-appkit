<?php

require_once( dirname( __FILE__ ) . '/core-web-services/synchronization.php' );
require_once( dirname( __FILE__ ) . '/core-web-services/comments.php' );
require_once( dirname( __FILE__ ) . '/core-web-services/component.php' );

class WpakWebServiceCrud {

	public static function create( $app_id, $service_slug, $data ) {

		$service_answer = array();
		$service_answer = apply_filters( 'wpak_create_' . $service_slug, $service_answer, $data, $app_id );
		$service_answer = apply_filters( 'wpak_create', $service_answer, $service_slug, $data, $app_id );

		return $service_answer;
	}

	public static function read( $app_id, $service_slug, $query_vars ) {

		$service_answer = array();
		$service_answer = apply_filters( 'wpak_read_' . $service_slug, $service_answer, $query_vars, $app_id );
		$service_answer = apply_filters( 'wpak_read', $service_answer, $service_slug, $query_vars, $app_id );

		return $service_answer;
	}

	public static function read_one( $app_id, $service_slug, $id ) {

		$service_answer = array();
		$service_answer = apply_filters( 'wpak_read_one_' . $service_slug, $service_answer, $id, $app_id );
		$service_answer = apply_filters( 'wpak_read_one', $service_answer, $service_slug, $id, $app_id );

		return $service_answer;
	}

	public static function update( $app_id, $service_slug, $data ) {

		$service_answer = array();
		$service_answer = apply_filters( 'wpak_update_' . $service_slug, $service_answer, $id, $app_id );
		$service_answer = apply_filters( 'wpak_update', $service_answer, $service_slug, $id, $app_id );

		return $service_answer;
	}

	public static function delete( $app_id, $service_slug, $id ) {

		$service_answer = array();
		$service_answer = apply_filters( 'wpak_delete_' . $service_slug, $service_answer, $id, $app_id );
		$service_answer = apply_filters( 'wpak_delete', $service_answer, $service_slug, $id, $app_id );

		return $service_answer;
	}

}
