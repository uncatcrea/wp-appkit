<?php

require_once(dirname( __FILE__ ) . '/themes-storage.php');
require_once(dirname( __FILE__ ) . '/themes-bo-settings.php');

class WpakThemes {

	const themes_directory = 'themes-wp-appkit';

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 5 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}
	
	public static function get_themes_directory(){
		return WP_CONTENT_DIR .'/'. self::themes_directory;
	}
	
	public static function rewrite_rules() {
		add_rewrite_tag( '%wpak_theme_file%', '([^&]+)' );
		$wp_content = str_replace( ABSPATH, '', WP_CONTENT_DIR );
		$url_to_theme_files = plugins_url( 'app/themes', dirname( dirname( __FILE__ ) ) );
		$wp_content_pos = strpos( $url_to_theme_files, $wp_content );
		if ( $wp_content_pos !== false ) {
			$theme_file_prefix = substr( $url_to_theme_files, $wp_content_pos ); //Something like "wp-content/plugins/wp-app-kit/app/themes"
			add_rewrite_rule( '^' . $theme_file_prefix . '/(.*)$', 'index.php?wpak_theme_file=$matches[1]', 'top' );
		}
	}
	
	public static function admin_notices() {
		if( !is_dir( self::get_themes_directory() ) ) {
			if( !self::create_theme_directory() ) {
				?>
				<div class="error">
					<p>
						<?php 
							echo sprintf( __( 'The WP AppKit themes directory %s can\'t be created. <br/>Please check that your WordPress install has the permissions to create this directory.', WpAppKit::i18n_domain ), 
										  basename(WP_CONTENT_DIR) .'/'. self::themes_directory 
							); 
						?>
					</p>
				</div>
				<?php
			}
		}
	}
	
	public static function create_theme_directory() {
		$theme_directory_ok = true;
		if( !is_dir( self::get_themes_directory() ) ) {
			$theme_directory_ok = mkdir( self::get_themes_directory() );
		}
		return $theme_directory_ok;
	}
				
	public static function template_redirect() {
		global $wp_query;

		//The following is only for app simulation in browser
		
		if ( isset( $wp_query->query_vars['wpak_theme_file'] ) && !empty( $wp_query->query_vars['wpak_theme_file'] ) ) {

			$file = $wp_query->query_vars['wpak_theme_file'];

			//For assets files like fonts, images or css we can't 
			//be sure that the wpak_app_id GET arg is there, because they can
			//be included directly in themes sources (CSS/HTML) where the WP AppKit API can't
			//be used. So, we can't check that the file comes from the right app 
			//or theme > we just check that the theme the asset belongs to is a real 
			//WP AppKit theme and that at least one app uses this theme :
			if( self::is_asset_file( $file ) ) {
				if ( preg_match( '/([^\/]+?)\/(.+)$/', $file, $matches ) ) {
					$theme_slug = $matches[1];
					$theme_file = $matches[2];
					
					if ( self::is_theme( $theme_slug ) && self::theme_is_used( $theme_slug ) ) {
						if ( $file_full_path = self::get_theme_file( $theme_slug, $theme_file ) ) {
							self::exit_send_theme_file( $file_full_path );
						}
					} else {
						header("HTTP/1.0 404 Not Found");
						_e( 'Not a valid theme file', WpAppKit::i18n_domain );
						exit();
					}
					
				}else {
					header("HTTP/1.0 404 Not Found");
					_e( 'Not a valid theme file path', WpAppKit::i18n_domain );
					exit();
				}
				
			}else if ( !empty( $_GET['wpak_app_id'] ) ) {
			
				//For non considered asset files (like JS) we check that the file is 
				//asked for the correct app and for the theme of the app:

				$app_id = esc_attr( $_GET['wpak_app_id'] ); //can be ID or slug

				$app = WpakApps::get_app( $app_id );

				if ( !empty( $app ) ) {
					$app_id = $app->ID;

					$default_capability = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';

					$capability = apply_filters( 'wpak_private_simulation_capability', $default_capability, $app_id );

					if ( WpakApps::get_app_simulation_is_secured( $app_id ) && !current_user_can( $capability ) ) {
						wp_nonce_ays( 'wpak-theme-file' );
					}

					if ( preg_match( '/([^\/]+?)\/(.+)$/', $file, $matches ) ) {
						$theme_slug = $matches[1];
						$theme_file = $matches[2];
						$app_theme = WpakThemesStorage::get_current_theme( $app_id );
						if ( $theme_slug == $app_theme ) {
							if ( $file_full_path = self::get_theme_file( $theme_slug, $theme_file ) ) {
								self::exit_send_theme_file($file_full_path);
							} else {
								header("HTTP/1.0 404 Not Found");
								_e( 'Theme file not found', WpAppKit::i18n_domain );
								exit();
							}
						} else {
							header("HTTP/1.0 404 Not Found");
							_e( 'Asked theme is not activated for the given app', WpAppKit::i18n_domain );
							exit();
						}
					} else {
						header("HTTP/1.0 404 Not Found");
						_e( 'Wrong theme file', WpAppKit::i18n_domain );
						exit();
					}
				} else {
					header("HTTP/1.0 404 Not Found");
					_e( 'App not found', WpAppKit::i18n_domain ) . ' : [' . $app_id . ']';
					exit();
				}
			} else {
				header("HTTP/1.0 404 Not Found");
				_e( 'App id not found in _GET parmeters', WpAppKit::i18n_domain );
				exit();
			}
		}
	}
	
	public static function get_available_themes($with_data = false) {
		$available_themes = array();

		$directory = self::get_themes_directory();

		if ( file_exists( $directory ) && is_dir( $directory ) ) {
			if ( $handle = opendir( $directory ) ) {
				while ( false !== ($entry = readdir( $handle )) ) {
					if ( $entry != '.' && $entry != '..' && strpos( $entry, '.' ) !== 0 ) {
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
			$themes_dir = self::get_themes_directory() .'/' . $app_theme . '/php';
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
		
		$themes_dir = self::get_themes_directory() .'/' . $theme_folder;
		$theme_readme = $themes_dir . '/readme.md';
		
		if ( file_exists($theme_readme) ) {
			$theme_data = get_file_data( $theme_readme, $file_headers, 'wp-appkit-theme' );
		}
		
		if( empty($theme_data['Name']) ) {
			$theme_data['Name'] = ucfirst($theme_folder);
		}
		
		return $theme_data;
	}
	
	public static function get_theme_file( $theme_slug, $file_relative_to_theme ) {
		
		$theme_file = self::get_themes_directory() .'/' . $theme_slug .'/'. $file_relative_to_theme;
		
		return file_exists( $theme_file ) ? $theme_file : false;
	}
	
	/**
	 * Serve theme files for app browser simulation.
	 * 
	 * This is not really nice to serve those files via PHP with readfile(), 
	 * but it seems to be the only way that it works on all platforms.
	 * We could use X-Sendfile, but it requires a specific server config.
	 */
	protected static function exit_send_theme_file( $file ) {
		
		$mime_type = self::get_file_mime_type( $file );
		
		if( !empty( $mime_type) ) {
			
			//Assume that text files are encoded in utf-8...
			if( in_array($mime_type, array('text/css', 'text/html', 'text/javascript') ) ) {
				$mime_type .= ';  charset=utf-8';
			}
			
			header( 'Content-Type: ' . $mime_type );
			
			if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ) {
				header( 'Content-Length: ' . filesize( $file ) );
			}
			
		} else {
			header('HTTP/1.0 404 Not Found');
			echo __( 'Theme file mime type not authorized', WpAppKit::i18n_domain ) . ' : ' . basename($file);
			exit();
		}
		
		//Security note : before serving this file with readfile() we checked :
		//- that its mime type is authorized
		//- that it belongs to a valid WP AppKit Theme currently used by an app
		readfile( $file );
		
		exit();
	}
	
	protected static function get_file_mime_type( $file ) {
		$file_mime_type = '';
		$all_mime_types = self::get_allowed_theme_mime_types();
		$file_extension = pathinfo( $file, PATHINFO_EXTENSION );
		foreach( $all_mime_types as $ext => $mime_type ) {
			if( preg_match('/^('. $ext .')$/i', $file_extension) ) {
				$file_mime_type = $mime_type;
				break;
			}
		}
		return $file_mime_type;
	}
	
	protected static function get_allowed_theme_mime_types() {
		/**
		 * Use this filter to set file types that are allowed to be served
		 * to themes for the app simulation in browser :
		 */
		return apply_filters(
			'wpak_theme_allowed_mime_types', 
			array(
				//Most used
				'css' => 'text/css',
				'htm|html' => 'text/html',
				'js' => 'text/javascript',
				//Images
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif' => 'image/gif',
				'png' => 'image/png',
				'bmp' => 'image/bmp',
				'svg' => 'image/svg+xml',
				'tif|tiff' => 'image/tiff',
				'ico' => 'image/x-icon',
				//Fonts
				'eot' => 'application/vnd.ms-fontobject',
				'ttf' => 'font/truetype',
				'woff' => 'application/x-font-woff',
				'otf' => 'font/opentype',
				// Video formats.
				'asf|asx' => 'video/x-ms-asf',
				'wmv' => 'video/x-ms-wmv',
				'wmx' => 'video/x-ms-wmx',
				'wm' => 'video/x-ms-wm',
				'avi' => 'video/avi',
				'divx' => 'video/divx',
				'flv' => 'video/x-flv',
				'mov|qt' => 'video/quicktime',
				'mpeg|mpg|mpe' => 'video/mpeg',
				'mp4|m4v' => 'video/mp4',
				'ogv' => 'video/ogg',
				'webm' => 'video/webm',
				'mkv' => 'video/x-matroska',
				'3gp|3gpp' => 'video/3gpp', // Can also be audio
				'3g2|3gp2' => 'video/3gpp2', // Can also be audio
				// Text formats.
				'txt' => 'text/plain',
				'csv' => 'text/csv',
				'md' => 'text/x-markdown',
				// Audio formats.
				'mp3|m4a|m4b' => 'audio/mpeg',
				'ra|ram' => 'audio/x-realaudio',
				'wav' => 'audio/wav',
				'ogg|oga' => 'audio/ogg',
				'mid|midi' => 'audio/midi',
				'wma' => 'audio/x-ms-wma',
				'wax' => 'audio/x-ms-wax',
				'mka' => 'audio/x-matroska',
			)
		);
	}
	
	protected static function is_asset_file( $file ) {
		$mime_type = self::get_file_mime_type( $file );
		
		$is_asset_file = strpos( $mime_type, 'text/css' ) !== false
			|| strpos( $mime_type, 'image' ) !== false
			|| strpos( $mime_type, 'font' ) !== false 
			|| strpos( $mime_type, 'video' ) !== false;
		
		/**
		 * Use this 'wpak_theme_is_asset_file' filter to make
		 * a file or a mime type accessible by the app without 
		 * checking the app id and the app theme. 
		 * @see self::template_redirect()
		 */
		return apply_filters(
			'wpak_theme_is_asset_file', 
			$is_asset_file,
			$file,
			$mime_type
		);
	}
	
	/**
	 * Checks if the given theme exists and has the mandatory themes files
	 */
	public static function is_theme( $theme_slug ) {
		$is_theme = false;
		$theme_path = self::get_themes_directory() . '/' . $theme_slug .'/';
		if ( is_dir( $theme_path ) ) {
			$is_theme = file_exists($theme_path . 'layout.html') 
						&& file_exists($theme_path . 'js/functions.js');
		}
		return $is_theme;
	}

	/**
	 * Checks if the given theme is used at least by one app
	 */
	public static function theme_is_used( $theme_slug ) {
		$used_themes = WpakThemesStorage::get_used_themes();
		return in_array( $theme_slug, $used_themes );
	}

}

WpakThemes::hooks();