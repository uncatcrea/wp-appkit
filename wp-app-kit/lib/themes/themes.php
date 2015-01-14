<?php

require_once(dirname( __FILE__ ) . '/themes-storage.php');
require_once(dirname( __FILE__ ) . '/themes-bo-settings.php');

class WpakThemes {

	const appli_theme_directory = 'themes-wp-appkit';

	public static function get_theme_directory(){
		return WP_CONTENT_DIR .'/'. self::appli_theme_directory;
	}
	
	public static function get_available_themes($with_data = false) {
		$available_themes = array();

		$directory = self::get_theme_directory();

		if ( file_exists( $directory ) && is_dir( $directory ) ) {
			if ( $handle = opendir( $directory ) ) {
				while ( false !== ($entry = readdir( $handle )) ) {
					if ( $entry != '.' && $entry != '..' ) {
						$entry_full_path = $directory . '/' . $entry;
						if ( is_dir( $entry_full_path ) ) {
							if( $with_data ){
								$available_themes[$entry] = WpakThemes::get_theme_data($entry);
							}else{
								$available_themes[] = $entry;
							}
							
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
	
	public static function get_theme_data( $theme_folder ) {
		
		$file_headers = array(
			'Name'              => 'Theme Name',
			'ThemeURI'          => 'Theme URI',
			'Description'       => 'Description',
			'Author'            => 'Author',
			'AuthorURI'         => 'Author URI',
			'Version'           => 'Version'
		);
		
		$theme_data = array();
		foreach( array_keys($file_headers) as $key ) {
			$theme_data[$key] = '';
		}
		
		$themes_dir = self::get_theme_directory() .'/' . $theme_folder;
		$theme_readme = $themes_dir . '/readme.md';
		
		if ( file_exists($theme_readme) ) {
			$theme_data = get_file_data( $theme_readme, $file_headers, 'wp-appkit-theme' );
		}
		
		if( empty($theme_data['Name']) ) {
			$theme_data['Name'] = ucfirst($theme_folder);
		}
		
		return $theme_data;
	}

}
