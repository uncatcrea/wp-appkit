<?php

class WpakToken {

	public static function get_token( $app_id, $service_slug ) {
		$hooked_token = apply_filters( 'wpak_generate_token', '', $service_slug, $app_id );
		$token = !empty( $hooked_token ) ? $hooked_token : self::generate_token( $app_id );
		return $token;
	}

	public static function check_token( $token, $app_id, $service_slug ) {
		$right_token = self::get_token( $app_id, $service_slug );

		$token_ok = apply_filters( 'wpak_check_token', null, $token, $right_token, $app_id, $service_slug );
		if ( $token_ok === null ) {
			$token_ok = ($token == $right_token);
		}

		return $token_ok;
	}

	private static function generate_token( $salt ) {
		$hash_key = self::get_hash_key() . $salt . date( 'Y-m-d' );
		return base64_encode( hash( 'sha256', $hash_key ) );
	}

	public static function get_hash_key() {
		$hash_key = '';
		
		//WPAK_HASH_KEY can be defined in wp-config.php
		if ( defined( 'WPAK_HASH_KEY' ) ) {
			$hash_key = WPAK_HASH_KEY;
		} else {
			$hash_key = get_option( 'wpak_hash_key' );
			if ( empty( $hash_key ) ) {
				$hash_key = wp_generate_password( 64, true, true );
			}
		}

		$hash_key = apply_filters( 'wpak_hash_key', $hash_key );

		//Remove \ from hash key as it makes the JS crash on app side :
		$hash_key = str_replace( '\\', '', $hash_key );
		
		//Remove simple quote (') from hash key to avoid JS strings problems on app side :
		$hash_key = str_replace( "'", '', $hash_key );

		return $hash_key;
	}

}
