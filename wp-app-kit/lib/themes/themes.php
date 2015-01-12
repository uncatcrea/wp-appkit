<?php

require_once(dirname( __FILE__ ) . '/themes-storage.php');
require_once(dirname( __FILE__ ) . '/themes-bo-settings.php');

class WpakThemes {

	const appli_theme_directory = 'themes-wp-appkit';

	public static function get_theme_directory(){
		return WP_CONTENT_DIR .'/'. self::appli_theme_directory;
	}
	
	public static function get_available_themes() {
		$available_themes = array();

		$directory = self::get_theme_directory();

		if ( file_exists( $directory ) && is_dir( $directory ) ) {
			if ( $handle = opendir( $directory ) ) {
				while ( false !== ($entry = readdir( $handle )) ) {
					if ( $entry != '.' && $entry != '..' ) {
						$entry_full_path = $directory . '/' . $entry;
						if ( is_dir( $entry_full_path ) ) {
							$available_themes[] = $entry;
						}
					}
				}
				closedir( $handle );
			}
		}

		return $available_themes;
	}

	public static function include_app_theme_php( $app_id ) {
		$app_theme = WpakThemesStorage::get_current_theme( $app_id );
		if ( !empty( $app_theme ) ) {
			$themes_dir = self::get_theme_directory() .'/' . $app_theme . '/php';
			if ( file_exists( $themes_dir ) && is_dir( $themes_dir ) ) {
				foreach ( glob( $themes_dir . "/*.php" ) as $file ) {
					include_once($file);
				}
			}
		}
	}

}
