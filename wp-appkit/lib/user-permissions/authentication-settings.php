<?php

class AuthenticationSettings {

	protected static $auth_engine = null;

	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_auth_settings_meta_box' ), 50 );
		add_action( 'save_post', array( __CLASS__, 'save_post' ) );
	}

	protected static function get_auth_engine() {

		$default_auth_engine = array(
			'file' => dirname(__FILE__) . '/auth-engines/rsa-public-private-auth.php',
			'class' => 'WpakRsaPublicPrivateAuth',
		);

		$auth_engine = apply_filters( 'wpak_auth_engine', $default_auth_engine );

		return $auth_engine;
	}

	public static function get_auth_engine_instance() {

		if ( self::$auth_engine === null ) {

			$auth_engine = self::get_auth_engine();
			$auth_engine_file = $auth_engine['file'];
			$auth_engine_class = $auth_engine['class'];

			if ( file_exists( $auth_engine_file ) ) {
				require_once( $auth_engine_file );
				if ( class_exists( $auth_engine_class ) && is_subclass_of( $auth_engine_class, 'WpakAuthEngine' ) ) {
					self::$auth_engine = new $auth_engine_class();
				}
			}
		}

		return self::$auth_engine;
	}

	public static function add_auth_settings_meta_box() {
		add_meta_box(
			'wpak_app_auth_settings',
			__( 'Authentication', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_auth_settings_box' ),
			'wpak_apps',
			'normal',
			'low'
		);
	}

	public static function inner_auth_settings_box( $post, $current_box ) {
		$auth_engine = self::get_auth_engine_instance();
		$auth_engine->settings_meta_box_content( $post, $current_box );
		wp_nonce_field( 'wpak-app-auth-settings-' . $post->ID, 'wpak-nonce-app-auth-settings' );
	}

	public static function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( empty( $_POST['post_type'] ) || $_POST['post_type'] != 'wpak_apps' ) {
			return;
		}

		if ( !current_user_can( 'edit_post', $post_id ) && !current_user_can( 'wpak_edit_apps', $post_id ) ) {
			return;
		}

		if ( !check_admin_referer( 'wpak-app-auth-settings-' . $post_id, 'wpak-nonce-app-auth-settings' ) ) {
			return;
		}

		$auth_engine = self::get_auth_engine_instance();
		$auth_engine->save_posted_settings( $post_id );
	}
}

AuthenticationSettings::hooks();

