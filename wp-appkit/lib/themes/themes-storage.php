<?php

class WpakThemesStorage {

	const meta_id = '_wpak_app_theme_choice';

	public static function get_current_theme_options( $post_id ) {
		return self::get_theme_options( $post_id, self::get_current_theme( $post_id ) );
	}

	public static function get_current_theme( $post_id ) {
		$themes = self::get_themes_raw( $post_id );
		return $themes['current_theme'];
	}

	public static function get_theme_options( $post_id, $theme_slug ) {
		$themes = self::get_themes_raw( $post_id );
		return isset( $themes['themes'][$theme_slug]['options'] ) ? $themes['themes'][$theme_slug]['options'] : array();
	}

	public static function set_current_theme( $post_id, $theme_slug ) {
		$themes = self::get_themes_raw( $post_id );
		$themes['current_theme'] = $theme_slug;
		self::update_themes( $post_id, $themes );
	}

	public static function set_theme_options( $post_id, $theme_slug, $options ) {
		$themes = self::get_themes_raw( $post_id );
		@$themes['themes'][$theme_slug]['options'] = $options;
		self::update_themes( $post_id, $themes );
	}

	/**
	 * Get the themes currently used in all apps.
	 * As this is used very often while apps simulation (see WpakThemes::theme_is_used()),
	 * we use a transient to cache the value. The transient is re-computed each time
	 * we set a theme to an app (see self::update_themes());
	 */
	public static function get_used_themes( $force_compute = false, $with_apps = false ) {

		$used_themes = array();

		if ( (false === ( $used_themes = get_transient( 'wpak_used_themes' ) ) ) || $force_compute ) {
			$used_themes = self::compute_used_theme_transient();
		}

		if( !$with_apps ) {
			$used_themes = array_keys( $used_themes );
		}

		return $used_themes;
	}

	private static function update_themes( $post_id, $new_themes ) {
		delete_transient('wpak_used_themes');
		update_post_meta( $post_id, self::meta_id, $new_themes );
		self::compute_used_theme_transient();
	}
	
	private static function compute_used_theme_transient() {

		$used_themes = array();

		$all_apps_ids = get_posts( array(
			'numberposts' => -1,
			'fields' => 'ids',
			'post_type' => 'wpak_apps',
			'post_status' => 'publish'
		) );

		if ( !empty( $all_apps_ids ) ) {
			foreach ( $all_apps_ids as $app_id ) {
				$app_theme = self::get_current_theme( $app_id );
				if ( !empty( $app_theme ) ) {
					if( !isset( $used_themes[$app_theme] ) ) {
						$used_themes[$app_theme] = array( $app_id );
					}
					else {
						$used_themes[$app_theme][] = $app_id;
					}
				}
			}
		}
		
		//No expiration : the transient is deleted in self::update_themes()
		set_transient( 'wpak_used_themes', $used_themes, 0 );
		
		return $used_themes;
	}

	private static function get_themes_raw( $post_id ) {
		$themes = get_post_meta( $post_id, self::meta_id, true );
		
		if( empty($themes) || !isset( $themes['current_theme'] ) ) {
            if ( !is_array( $themes ) ) {
                $themes = array();
            }
			$themes['current_theme'] = '';
		}
		
		return $themes;
	}

}
