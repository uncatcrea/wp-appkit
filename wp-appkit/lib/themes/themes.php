<?php

require_once(dirname( __FILE__ ) . '/themes-storage.php');
require_once(dirname( __FILE__ ) . '/themes-bo-settings.php');
require_once(dirname( __FILE__ ) . '/themes-configjs-settings.php');

class WpakThemes {

	const themes_directory = 'themes-wp-appkit';
	const default_themes_directory = 'default-themes';
	protected static $default_themes = array(
		/**
		 * Key is used for:
		 *  - theme slug (to check if an installed theme is this one)
		 *  - filename into default themes folder (key-version.zip)
		 * Key should be the same as the theme's folder name
		 */

		'q-ios' => array(
			'name' => 'Q for iOS',
			'version' => '1.0.7',
		),

		'q-android' => array(
			'name' => 'Q for Android',
			'version' => '1.1.2',
		),

	);

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 5 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		if( is_admin() ) {
		    add_action( 'wp_ajax_wpak_get_pwa_icons', array( __CLASS__, 'ajax_get_pwa_icons' ) );
		}
	}


	public static function get_themes_directory(){
		return WP_CONTENT_DIR .'/'. self::themes_directory;
	}

	public static function get_themes_directory_uri() {
		return WP_CONTENT_URL . '/' . self::themes_directory;
	}

	public static function get_default_themes_directory(){
	 	return plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . self::default_themes_directory;
	}

	public static function get_default_themes_directory_uri(){
	 	return plugins_url( self::default_themes_directory, dirname( dirname( __FILE__ ) ) );
	}

	/**
     * Return the name of the default theme's file.
     * This file should be located under the default themes directory, see WpakThemes::get_default_themes_directory
     *
	 * @param string $slug
	 * @param array  $theme
	 *
	 * @return string
	 */
	public static function get_default_theme_filename( $slug, $theme ) {
		// This file might not exist if $slug doesn't exist into default themes, but if you call this method, you'd have to check if the file exists anyway
		return $slug . '-' . $theme['version'] . '.zip';
	}

	public static function get_default_themes() {
		return self::$default_themes;
	}

	public static function rewrite_rules() {
		add_rewrite_tag( '%wpak_theme_file%', '([^&]+)' );

		$home_url = home_url(); //Something like "http://my-site.com"
		$url_to_theme_files = plugins_url( 'app/themes', dirname( dirname( __FILE__ ) ) ); //Something like "http://my-site.com/wp-content/plugins/wp-appkit/app/themes"
		$theme_file_prefix = str_replace( trailingslashit($home_url), '', $url_to_theme_files ); //Something like "wp-content/plugins/wp-appkit/app/themes"

		add_rewrite_rule( '^' . $theme_file_prefix . '/(.*)$', 'index.php?wpak_theme_file=$matches[1]', 'top' );
	}

	public static function admin_notices() {
		$error = '';

		// Try to create WP-AppKit themes directory if it doesn't exist
		if( !is_dir( self::get_themes_directory() ) && !self::create_theme_directory() ) {
			$error = sprintf( __( 'The WP-AppKit themes directory %s can\'t be created. <br/>Please check that your WordPress install has the permissions to create this directory.', WpAppKit::i18n_domain ),
		  		self::get_themes_directory()
			);
		}

		// Copy default themes to WP-AppKit themes directory, only if it exists
		if( empty( $error ) && !self::install_default_themes() ) {
			$error = sprintf( __( 'We tried copying default themes into %s directory, and it seems it didn\'t work. You can do it manually by <a href="%s">downloading these default themes and uploading them through the dedicated page</a>.',
				WpAppKit::i18n_domain ),
				self::get_themes_directory(),
				menu_page_url( WpakUploadThemes::menu_item, false )
			);
		}

		if( !empty( $error ) ) {
			?>
			<div class="error">
				<p>
					<?php echo $error; ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Check what are the installed themes compared to provided default ones: check if the theme is installed and is up-to-date.
	 *
	 * @return 	array 	$result 	Empty if everything is OK (all default themes installed and up-to-date), or containing a value for each theme: either 'unavailable' (not installed) or 'needs_upgrade' (if the installed version is lower than the embedded one).
	 */
	public static function check_default_themes() {
		$available_themes = self::get_available_themes( true );
		$default_themes = self::get_default_themes();

		// Pre-fill results: each theme is unavailable
		$result = array_fill_keys( array_keys( $default_themes ), 'unavailable' );

		foreach( $available_themes as $slug => $data ) {
			// This theme is unavailable
			if( !isset( $default_themes[$slug] ) ) {
				continue;
			}

			// The theme is a default one: check if it needs an upgrade
			if( version_compare( $data['Version'], $default_themes[$slug]['version'], '<' ) ) {
				$result[$slug] = 'needs_upgrade';
			}
			else {
				// This theme is available and up-to-date
				unset( $result[$slug] );
			}
		}

		return $result;
	}

	public static function create_theme_directory() {
		$theme_directory_ok = true;
		if( !is_dir( self::get_themes_directory() ) ) {
			$theme_directory_ok = mkdir( self::get_themes_directory() );
		}
		return $theme_directory_ok;
	}

	/**
	 * Copy default themes provided with this plugin into 'wp-content/themes-wp-appkit/' folder.
	 * This method will erase existing files, so it should be called after having checked and validated what's needed.
	 *
	 * TODO: add a param to choose which theme(s) should be copied, instead of all of them.
	 *
	 * @return 	bool 	$result 	Whether the copy is complete or not. It's considered OK when *all* themes have been copied.
	 */
	public static function copy_default_themes() {
	    // Make sure we have something to copy
		self::create_themes_zip();

		$access_type = get_filesystem_method( array(), self::get_themes_directory() );

		if( $access_type != 'direct' || !WP_Filesystem( array(), self::get_themes_directory() ) ) {
			return false;
		}

		// WP_Filesystem initialization ran OK, we can perform our copy
		global $wp_filesystem;
		$default_themes = self::get_default_themes();
		$result = empty( $default_themes );

		foreach( $default_themes as $slug => $theme ) {
			$filename = self::get_default_theme_filename( $slug, $theme );
			if( !$wp_filesystem->is_file( self::get_default_themes_directory() . '/' . $filename ) ) {
				continue;
			}

			$result = true;

			// Warning: this will erase existing files, it's intended since this method should be called after some checks and validation about that fact
			// TODO: use WP_Upgrader API
			$res = unzip_file( self::get_default_themes_directory() . '/' . $filename, self::get_themes_directory() );

			if( is_wp_error( $res ) ) {
				$result = false;
			}
		}

		return $result;
	}

	/**
     * Remove useless files from the default themes directory.
     *
	 * @return bool
	 */
	protected static function _clean_default_themes_directory() {
	    global $wp_filesystem;

		$possible_names = array();
		$default_themes = self::get_default_themes();
		foreach( $default_themes as $slug => $theme ) {
		    $possible_names[] = self::get_default_theme_filename( $slug, $theme );
        }

	    $files = glob( self::get_default_themes_directory() . '/*.zip' );

		foreach( $files as $file ) {
		    $filename = basename( $file );
		    if( in_array( $filename, $possible_names ) ) {
		        continue;
            }

			$wp_filesystem->delete( $file, false, 'f' );
        }

        return true;
    }

	/**
     * Build a zip file from a directory into a given filename.
     *
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 */
    protected static function _build_zip( $source, $destination ) {
        if( !extension_loaded( 'zip' ) ) {
	        return false;
        }

	    $zip = new ZipArchive();

	    //
	    // ZipArchive::open() returns TRUE on success and an error code on failure, not FALSE
	    // All other used ZipArchive methods return FALSE on failure
	    //
	    // Apparently ZipArchive::OVERWRITE is not sufficient for recent PHP versions (>= 5.2.8, cf. comments here: http://fr.php.net/manual/en/ziparchive.open.php)
	    //

	    if( true !== $zip->open( $destination, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		    return false;
	    }

	    try {
		    $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
	    }
	    catch( Exception $e ) {
	        return false;
        }

        $dirname = basename( $source );
	    if( !$zip->addEmptyDir( $dirname ) ) {
	        return false;
        }

	    foreach( $files as $file ) {
	        if( $file->getFilename() == '.' || $file->getFilename() == '..' ) {
		        continue;
	        }

	        $filename = str_replace( $source, $dirname . '/', $file->getPathname() );
		    $filename = wp_normalize_path( $filename );
		    $filename = ltrim( $filename, '/\\' );

            if( $file->isDir() ) {
	            if( !$zip->addEmptyDir( $filename ) ) {
                    return false;
                }
            }
            else if( $file->isFile() ) {
	            if( !$zip->addFile( $file, $filename ) ) {
	                return false;
                }
            }
        }

	    return $zip->close();
    }

	/**
     * Create zip files from default themes included as folders into default themes directory.
     *
	 * @return bool
	 */
	public static function create_themes_zip() {
		global $wp_filesystem;

		$access_type = get_filesystem_method( array(), self::get_default_themes_directory() );

		if( $access_type != 'direct' || !WP_Filesystem( array(), self::get_default_themes_directory() ) ) {
			return false;
		}

		self::_clean_default_themes_directory();

	    foreach( self::get_default_themes() as $slug => $theme ) {
	        $filename = self::get_default_theme_filename( $slug, $theme );
		    if(
			    $wp_filesystem->is_file( self::get_default_themes_directory() . '/' . $filename ) ||
			    !$wp_filesystem->is_dir( self::get_default_themes_directory() . '/' . $slug )
		    ) {
			    continue;
		    }

            self::_build_zip( self::get_default_themes_directory() . '/' . $slug, self::get_default_themes_directory() . '/' . $filename );
        }

        return true;
    }

	/**
	 * Install default themes provided with this plugin into 'wp-content/themes-wp-appkit/' folder.
	 * This method will check what's needed, and won't copy default themes if at least one is detected in the
	 * destination directory.
	 *
	 * @return 	bool 	$result 	Whether the copy is complete or not. It's considered OK when *all* themes have been copied.
	 */
	public static function install_default_themes() {
		$return = true;
		$check = self::check_default_themes();

		// If we have something wrong with all themes, do something. Otherwise, we consider everything is OK if we have at least 1 default theme available
		// TODO: remove this check to handle theme upgrades
		if( count( $check ) == count( self::get_default_themes() ) ) {
			$ok = false;
			foreach( $check as $theme_key => $result ) {
				// $result can be 'needs_upgrade', but we don't handle this case for now
				// TODO: handle 'needs_upgrade' case
				if( $result != 'unavailable' ) {
					$ok = true;
				}
			}

			// If all themes are unavailable, try to copy them
			if ( !$ok && !self::copy_default_themes() ) {
				$return = false;
			}
		}

		return $return;
	}

	public static function template_redirect() {
		global $wp_query;

		//The following is only for app simulation in browser

		if ( isset( $wp_query->query_vars['wpak_theme_file'] ) && !empty( $wp_query->query_vars['wpak_theme_file'] ) ) {

			$file = $wp_query->query_vars['wpak_theme_file'];

			//For assets files like fonts, images or css we can't
			//be sure that the wpak_app_id GET arg is there, because they can
			//be included directly in themes sources (CSS/HTML) where the WP-AppKit API can't
			//be used. So, we can't check that the file comes from the right app
			//or theme > we just check that the theme the asset belongs to is a real
			//WP-AppKit theme and that at least one app uses this theme :
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
		$default_themes_names = wp_list_pluck( self::get_default_themes(), 'name' );

		if ( file_exists( $directory ) && is_dir( $directory ) ) {
			if ( $handle = opendir( $directory ) ) {
				while ( false !== ($entry = readdir( $handle )) ) {
					if ( $entry != '.' && $entry != '..' && strpos( $entry, '.' ) !== 0 ) {
						$entry_full_path = $directory . '/' . $entry;
						if ( is_dir( $entry_full_path ) ) {
							if( $with_data ){
								$available_themes[$entry] = WpakThemes::get_theme_data($entry);

								// Update the name if this one has the same name as a default one: add the slug as a suffix
								$default_slug = array_search( $available_themes[$entry]['Name'], $default_themes_names );
								if( $default_slug !== false && $default_slug != $entry ) {
									$available_themes[$entry]['Name'].= '/' . $entry;
								}
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
			'Name'                => 'Theme Name',
			'ThemeURI'            => 'Theme URI',
			'Description'         => 'Description',
			'Author'              => 'Author',
			'AuthorURI'           => 'Author URI',
			'Version'             => 'Version',
			'WpakVersionRequired' => 'WP-AppKit Version Required'
		);

		$theme_data = array();
		foreach( array_keys($file_headers) as $key ) {
			$theme_data[$key] = '';
		}

		$themes_dir = self::get_themes_directory() .'/' . $theme_folder;
		$theme_readme = $themes_dir . '/readme.md';

		if ( file_exists($theme_readme) ) {
			$theme_data = get_file_data( $theme_readme, $file_headers, 'wp-appkit-theme' );
		} else {
			//Try with upper case :
			$theme_readme = $themes_dir . '/README.md';
			if ( file_exists($theme_readme) ) {
				$theme_data = get_file_data( $theme_readme, $file_headers, 'wp-appkit-theme' );
			}
		}

		$theme_data['screenshot'] = false;
		foreach ( array( 'png', 'gif', 'jpg', 'jpeg' ) as $ext ) {
			if ( file_exists( $themes_dir . "/screenshot.$ext" ) ) {
				$theme_data['screenshot'] = 'screenshot.' . $ext;
				break;
			}
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

	public static function get_theme_file_uri( $theme_slug, $file_relative_to_theme ) {
		return self::get_themes_directory_uri() .'/' . $theme_slug .'/'. $file_relative_to_theme;
	}

	/**
	 * Serve theme files for app browser simulation.
	 *
	 * This is not really nice to serve those files via PHP with readfile(),
	 * but it seems to be the only way that it works on all platforms.
	 * We could use X-Sendfile, but it requires a specific server config.
	 */
	protected static function exit_send_theme_file( $file ) {

		//Remove any previous output before serving file:
		$content_already_echoed = ob_get_contents();
		if ( !empty( $content_already_echoed ) ) {
			ob_end_clean();
		}

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
		//- that it belongs to a valid WP-AppKit Theme currently used by an app
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
				'woff2' => 'application/font-woff2',
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

	public static function get_apps_for_theme( $theme_slug, $return_objects = false ) {
		$return = array();

		$used_themes = WpakThemesStorage::get_used_themes( false, true );

		if( !empty( $used_themes[$theme_slug] ) ) {
			$return = $used_themes[$theme_slug];
		}

		if( $return_objects ) {
			$return = array_map( 'get_post', $return );

			// Post title may be empty
			foreach( $return as &$post ) {
				$post->post_title = _draft_or_post_title( $post->ID );
			}
		}

		return $return;
	}

	/**
	 * Return PWA icons available in a theme, if any.
	 * These icons must be provided as PNG images into the 'icons/' theme's directory.
	 *
	 * @param string $theme_slug
	 *
	 * @return array
	 */
	public static function get_pwa_icons( $theme_slug ) {
	    if( !self::is_theme( $theme_slug ) ) {
	        return array();
	    }

	    $icons_dir = self::get_themes_directory() . '/' . $theme_slug . '/icons/';
	    $files = glob( $icons_dir . '*.png' );
	    $icons = array();
	    $widths = array(); // Used for sorting
	    $heights = array(); // Used for sorting
	    foreach( $files as $file ) {
	        $filename = basename( $file );
	        $size = self::get_pwa_icon_size( $filename );

	        if( empty( $size ) ) {
	            continue;
	        }

	        $icons[] = array(
		        'name' => $filename,
		        'dir'  => str_replace( ABSPATH, '/', $icons_dir ),
		        'path' => $file,
		        'url'  => self::get_themes_directory_uri() . '/' . $theme_slug . '/icons/' . $filename,
		        'size' => $size,
	        );
	        $widths[] = $size[0];
	        $heights[] = $size[1];
	    }

	    array_multisort( $heights, SORT_ASC, $widths, SORT_ASC, $icons );

	    return $icons;
	}

	/**
	 * Handle AJAX request to get PWA icons for a given theme/app.
	 */
	public static function ajax_get_pwa_icons() {
	    $answer = array( 'icons' => array() );

	    if ( empty( $_GET ) || empty( $_GET['app_id'] ) || !is_numeric( $_GET['app_id'] ) ) {
		    self::exit_sending_json( $answer );
	    }

	    $app_id = addslashes( $_GET['app_id'] );

	    if ( !check_admin_referer( 'wpak_get_pwa_icons_' . $app_id, 'nonce' ) ) {
		    return;
	    }

	    $theme = !empty( $_GET['theme'] ) ? addslashes( $_GET['theme'] ) : WpakThemesStorage::get_current_theme( $app_id );

	    $icons = self::get_pwa_icons( $theme );

	    foreach( $icons as $icon ) {
	        $answer['icons'][] = array(
	            'dir' => $icon['dir'],
	            'url' => $icon['url'],
	            'width' => $icon['size'][0],
	            'height' => $icon['size'][1],
	        );
	    }

	    self::exit_sending_json( $answer );
	}

	/**
	 * Get the size of a PWA icon given its file name.
	 * Size must be provided in the filename as follow: icon-name-width-height.png
	 *
	 * @param string $filename
	 *
	 * @return array
	 */
	public static function get_pwa_icon_size( $filename ) {
	    preg_match( '/-([0-9]+)x([0-9]+)\.png/U', $filename, $matches );

	    if( empty( $matches[2] ) || empty( $matches[1] ) ) {
	        return array();
	    }

	    return array( $matches[1], $matches[2] );
	}

	/**
	 * @todo: maybe refactor this method and all similar ones around the plugin (e.g. into a WpakAjax class)?
	 *
	 * @param $answer
	 */
	private static function exit_sending_json( $answer ) {
	    //If something was displayed before, clean it so that our answer can
	    //be valid json (and store it in an "echoed_before_json" answer key
	    //so that we can warn the user about it) :
	    $content_already_echoed = ob_get_contents();
	    if ( !empty( $content_already_echoed ) ) {
		    $answer['echoed_before_json'] = $content_already_echoed;
		    ob_end_clean();
	    }

	    header( 'Content-type: application/json' );
	    echo json_encode( $answer );
	    exit();
	}
}

WpakThemes::hooks();