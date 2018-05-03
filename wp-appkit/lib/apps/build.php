<?php

require_once( dirname( __FILE__ ) . '/minify.php' );

class WpakBuild {

	const export_file_memory = 10;
    const layout_file_name = 'layout.html';
    const launch_content_file_name = 'launch-content.html';
    const launch_head_file_name = 'launch-head.html';

	public static function hooks() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_wpak_build_app_sources', array( __CLASS__, 'ajax_build_app_sources' ) );
			add_action( 'admin_action_wpak_download_app_sources', array( __CLASS__, 'download_app_sources' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 20 );
			add_action( 'save_post', array( __CLASS__, 'save_post' ) );
		}
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'wpak_simulation_box',
			__( 'Dev Tools', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_simulation_box' ),
			'wpak_apps',
			'side',
			'default'
		);
	}

	public static function inner_simulation_box( $post, $current_box ) {
		$debug_mode = self::get_app_debug_mode_raw( $post->ID );
		?>
		<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
		<div class="field-group">
			<label><?php _e( 'Debug Mode', WpAppKit::i18n_domain ) ?></label>
			<select name="wpak_app_debug_mode">
				<option value="on" <?php echo $debug_mode == 'on' ? 'selected="selected"' : '' ?>><?php _e( 'On', WpAppKit::i18n_domain ) ?></option>
				<option value="off" <?php echo $debug_mode == 'off' ? 'selected="selected"' : '' ?>><?php _e( 'Off', WpAppKit::i18n_domain ) ?></option>
				<option value="wp" <?php echo $debug_mode == 'wp' ? 'selected="selected"' : '' ?>><?php _e( 'Same as WordPress WP_DEBUG', WpAppKit::i18n_domain ) ?></option>
			</select>
			<span class="description"><?php _e( 'If activated, echoes debug information in the browser JavasSript console while simulating the app.', WpAppKit::i18n_domain ) ?></span>
		</div>
		<div class="field-group">
			<a href="<?php echo esc_url( self::get_appli_dir_url() . '/config.js?wpak_app_id=' . WpakApps::get_app_slug( $post->ID ) ) ?>" target="_blank"><?php _e( 'View config.js', WpAppKit::i18n_domain ) ?></a>
		</div>
		<?php wp_nonce_field( 'wpak-simulation-data-' . $post->ID, 'wpak-nonce-simulation-data' ) ?>
		<?php
		do_action( 'wpak_inner_simulation_box', $post, $current_box );
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

		if ( !check_admin_referer( 'wpak-simulation-data-' . $post_id, 'wpak-nonce-simulation-data' ) ) {
			return;
		}

		if ( isset( $_POST['wpak_app_debug_mode'] ) ) {
			update_post_meta( $post_id, '_wpak_app_debug_mode', $_POST['wpak_app_debug_mode'] );
		}
	}

	private static function get_app_debug_mode_raw( $app_id ) {
		$debug_mode = get_post_meta( $app_id, '_wpak_app_debug_mode', true );
		return empty( $debug_mode ) ? 'off' : $debug_mode;
	}

    public static function is_app_in_debug_mode( $app_id ) {
        $debug_mode = self::get_app_debug_mode( $app_id );
        return $debug_mode === 'on';
    }

	public static function get_app_debug_mode( $app_id ) {
		$debug_mode = self::get_app_debug_mode_raw( $app_id );
		return $debug_mode == 'wp' ? (WP_DEBUG ? 'on' : 'off') : $debug_mode;
	}

	public static function get_appli_dir_url() {
		return plugins_url( 'app', dirname( dirname( __FILE__ ) ) );
	}

	public static function get_appli_index_url( $app_id ) {
		return self::get_appli_dir_url() . '/index.html?wpak_app_id=' . WpakApps::get_app_slug( $app_id );
	}

	public static function get_allowed_export_types() {
		$allowed_export_types = array(
			'phonegap-build' => __( 'PhoneGap Build', WpAppKit::i18n_domain ),
			'phonegap-cli' => __( 'PhoneGap CLI', WpAppKit::i18n_domain ),
			'webapp' => __( 'WebApp', WpAppKit::i18n_domain ),
			//'webapp-appcache' => __( 'WebApp avec Manifest AppCache', WpAppKit::i18n_domain ), //AppCache is Deprectated
			'pwa' => __( 'Progressive Web App Sources', WpAppKit::i18n_domain ),
			'pwa-install' => __( 'Progressive Web App Install', WpAppKit::i18n_domain ),
		);
		return $allowed_export_types;
	}

	public static function is_allowed_export_type( $export_type ) {
		return array_key_exists( $export_type, self::get_allowed_export_types() );
	}

	public static function download_app_sources() {

		if ( !check_admin_referer( 'wpak_download_app_sources' ) || !isset( $_GET['post'] ) ) {
			return;
		}

		$app_id = intval( $_GET['post'] );

		if( $app_id <= 0 ) {
			return;
		}

		$export_type = isset( $_GET['export_type'] ) && self::is_allowed_export_type( $_GET['export_type'] ) ? $_GET['export_type'] : 'phonegap-build';

		// Re-build sources
		$answer = self::build_app_sources ($app_id, $export_type );

		if( 1 === $answer['ok'] && !empty( $answer['export'] ) ) {
			$filename = $answer['export'] . '.zip';
			$filename_full = self::get_export_files_path() . "/" . $filename;
		}
		else {
			echo esc_html( $answer['msg'] );
			exit;
		}

		if ( file_exists( $filename_full ) ) {
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
			header( "Content-Description: File Transfer" );
			header( "Content-type: application/octet-stream" );
			header( "Content-Disposition: attachment; filename=\"" . $filename . "\"" );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Length: " . filesize( $filename_full ) );
			ob_end_clean();
			@readfile( $filename_full );
			exit;
		} else {
			echo sprintf( __( 'Error: Could not find zip export file [%s]', WpAppKit::i18n_domain ), $filename_full );
			exit;
		}
	}

	/**
	* Retrieves export data and launch zip built.
	*
	* @param int		$app_id				Application id
	* @param string     $export_type        Export type : 'phonegap-build' (default), 'phonegap-cli' or 'webapp'
	*/
	public static function build_app_sources( $app_id, $export_type = 'phonegap-build' ) {
		$answer = array();

		if ( $export_type === 'pwa-install' ) {
			$export_type = 'pwa';
		}

		if ( !self::is_allowed_export_type( $export_type ) ) {
			$answer['ok'] = 0;
			$answer['msg'] = __( 'Unknown export type', WpAppKit::i18n_domain ) . ": '$export_type'";
			return $answer;
		}

		if ( !extension_loaded( 'zip' ) ) {
			$answer['ok'] = 0;
			$answer['msg'] = __( 'Zip PHP extension is required to run file export. See http://www.php.net/manual/fr/book.zip.php.', WpAppKit::i18n_domain );
			return $answer;
		}

		if ( !self::create_export_directory_if_doesnt_exist() ) {
			$export_directory = self::get_export_files_path();
			$answer['ok'] = 0;
			$answer['msg'] = sprintf( __( 'The export directory [%s] could not be created. Please check that you have the right permissions to create this directory.', WpAppKit::i18n_domain ), $export_directory );
			return $answer;
		}

		//If the app current theme has some PHP (hooks!) to be executed before
		//we build the export, include it here :
		WpakThemes::include_app_theme_php( $app_id );

		//Include PHP files required by addons activated for this app :
		WpakAddons::require_app_addons_php_files( $app_id );

		$current_theme = WpakThemesStorage::get_current_theme( $app_id );

		$plugin_dir = plugin_dir_path( dirname( dirname( __FILE__ ) ) );
		$appli_dir = $plugin_dir . 'app';

		$export_filename = self::get_export_file_base_name( $app_id, $export_type );
		$export_filename_full = self::get_export_files_path() . "/" . $export_filename . '.zip';

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_platform = $app_main_infos['platform'];

		$answer = self::build_zip(
				$app_id,
				$appli_dir,
				$export_filename_full,
				array( $current_theme ),
				WpakAddons::get_app_addons( $app_id ),
				WpakConfigFile::get_platform_icons_and_splashscreens_files( $app_id, $app_platform, $export_type ),
				$export_type
		);

		$answer['export'] = $export_filename;
		$answer['export_full_name'] = $export_filename_full;

		return $answer;
	}

	public static function ajax_build_app_sources() {
		$answer = array( 'ok' => 1, 'msg' => '' );

		if ( empty( $_POST ) || empty( $_POST['app_id'] ) || !is_numeric( $_POST['app_id'] ) ) {
			$answer['ok'] = 0;
			$answer['msg'] = __( 'Wrong application ID', WpAppKit::i18n_domain );
			self::exit_sending_json( $answer );
		}

		$app_id = addslashes( $_POST['app_id'] );

		if ( !check_admin_referer( 'wpak_build_app_sources_' . $app_id, 'nonce' ) ) {
			return;
		}

		$export_type = isset( $_POST['export_type'] ) ? addslashes( $_POST['export_type'] ) : 'phonegap-build';

		$answer = self::build_app_sources( $app_id, $export_type );

		if ( $answer['ok'] === 1 && $export_type === 'pwa-install' ) {

			if( !WpakApps::isSaved( $app_id ) ) {
				$answer['ok'] = 0;
				$answer['msg'] = __( 'Please save the application project before installing PWA.', WpAppKit::i18n_domain );
				self::exit_sending_json( $answer );
			}

			$answer['export_uri'] = self::get_pwa_directory_uri( $app_id );

			//Extract sources to Progressive Web App folder:

			$zip_file = $answer['export_full_name'];

			$target_directory = self::get_pwa_directory( $app_id );
			$check = self::check_pwa_directory( $app_id );
			if ( ! $check['ok'] ) {
				$answer['ok'] = 0;
				$answer['msg'] = $check['message'];
				self::exit_sending_json( $answer );
			}

			WP_Filesystem();
			$result = unzip_file( $zip_file, $target_directory );
			if ( is_wp_error( $result ) ) {
				$answer['ok'] = 0;
				$answer['msg'] = __( 'Could not extract ZIP export to : '. $target_directory, WpAppKit::i18n_domain );
				self::exit_sending_json( $answer );
			}
		}

		self::exit_sending_json( $answer );
	}

	public static function get_default_pwa_path( $app_id ) {
		$default_pwa_path = 'pwa';
		return apply_filters( 'wpak_default_pwa_path', $default_pwa_path, $app_id );
	}

	public static function get_pwa_directory_uri( $app_id ) {
		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		return apply_filters( 'wpak_pwa_uri', get_option('siteurl') . '/' . $app_main_infos['pwa_path'], $app_id );
	}

	public static function get_pwa_directory( $app_id ){
		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		return apply_filters( 'wpak_pwa_path', ABSPATH . $app_main_infos['pwa_path'], $app_id );
	}

	public static function app_pwa_is_installed( $app_id ) {
		return file_exists( self::get_pwa_directory( $app_id ) .'/index.html' );
	}

	private static function create_pwa_directory( $app_id ) {
		$ok = true;
		$app_pwa_directory = self::get_pwa_directory( $app_id );
		if( !is_dir( $app_pwa_directory ) ) {
			$ok = mkdir( $app_pwa_directory, 0777, true );
		}
		return $ok;
	}

	private static function delete_pwa_directory( $app_id ) {
		$target_directory = self::get_pwa_directory( $app_id );
		$files = glob( $target_directory . '/*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( !unlink( $file ) ) {
					return false;
				}
			}
		}
		return true;
	}

	private static function check_pwa_directory( $app_id ) {
		$result = array( 'ok' => 1, 'msg' => '' );

		if ( !self::create_pwa_directory( $app_id ) ) {
			$app_pwa_directory = self::get_pwa_directory( $app_id );
			$result['ok'] = 0;
			$result['message'] = sprintf(
				__( 'The Progressive Web App directory %s can\'t be created. <br/>Please check that your WordPress install has the permissions to create this directory.', WpAppKit::i18n_domain ),
		  		$app_pwa_directory
			);
		}

		return $result;
	}

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

	private static function get_export_files_path() {
		$upload_dir = wp_upload_dir();
		return empty( $upload_dir['error'] ) ? $upload_dir['basedir'] . '/wpak-export' : '';
	}

	private static function get_export_file_base_name( $app_id, $export_type ) {
		return $export_type .'-export-' . WpakApps::get_app_slug( $app_id );
	}

	private static function create_export_directory_if_doesnt_exist() {
		$export_directory = self::get_export_files_path();
		$ok = true;
		if ( !file_exists( $export_directory ) ) {
			$ok = mkdir( $export_directory, 0777, true );
		}
		return $ok;
	}

	private static function build_zip( $app_id, $source, $destination, $themes, $addons, $icons_and_splashscreens, $export_type ) {

		$answer = array( 'ok' => 1, 'msg' => '' );

		if ( !extension_loaded( 'zip' ) || !file_exists( $source ) ) {
			$answer['msg'] = sprintf( __( 'The Zip archive file [%s] could not be created. Please check that you have the permissions to write to this directory.', WpAppKit::i18n_domain ), $destination );
			$answer['ok'] = 0;
			return $answer;
		}

		$zip = new ZipArchive();

		//
		// ZipArchive::open() returns TRUE on success and an error code on failure, not FALSE
		// All other used ZipArchive methods return FALSE on failure
		//
		// Apparently ZipArchive::OVERWRITE is not sufficient for recent PHP versions (>= 5.2.8, cf. comments here: http://fr.php.net/manual/en/ziparchive.open.php)
		//

		if ( true !== ( $error_code = $zip->open( $destination, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) ) {
			switch( $error_code ) {
				case ZipArchive::ER_EXISTS:
					$error = _x( 'File already exists', 'ZipArchive::ER_EXISTS error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_INCONS:
					$error = _x( 'Zip archive inconsistent', 'ZipArchive::ER_INCONS error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_INVAL:
					$error = _x( 'Invalid argument', 'ZipArchive::ER_INVAL error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_MEMORY:
					$error = _x( 'Malloc failure', 'ZipArchive::ER_MEMORY error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_NOENT:
					$error = _x( 'No such file', 'ZipArchive::ER_NOENT error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_NOZIP:
					$error = _x( 'Not a zip archive', 'ZipArchive::ER_NOZIP error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_OPEN:
					$error = _x( 'Can\'t open file', 'ZipArchive::ER_OPEN error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_READ:
					$error = _x( 'Read error', 'ZipArchive::ER_READ error', WpAppKit::i18n_domain );
					break;
				case ZipArchive::ER_SEEK:
					$error = _x( 'Seek error', 'ZipArchive::ER_SEEK error', WpAppKit::i18n_domain );
					break;
				default:
					$error = '';
			}
			$answer['msg'] = sprintf( __( 'The Zip archive file [%s] could not be opened (%s). Please check that you have the permissions to write to this directory.', WpAppKit::i18n_domain ), $destination, $error );
			$answer['ok'] = 0;
			return $answer;
		}

        $minifier = new WpakMinify();

		if ( is_dir( $source ) === true ) {

			$webapp_files = array();

			$source_root = '';
			$sw_cache_file_data = array();

			if ( $export_type === 'phonegap-cli' ) {
				//PhoneGap CLI export is made in www subdirectory
				//( only config.xml stays at zip root )
				$source_root = 'www';
				if ( !$zip->addEmptyDir( $source_root ) ) {
					$answer['msg'] = sprintf( __( 'Could not add directory [%s] to zip archive', WpAppKit::i18n_domain ), $source_root );
					$answer['ok'] = 0;
					return $answer;
				}
			}

			if ( !empty( $source_root ) ) {
				$source_root .= '/';
			}

			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );

			foreach ( $files as $file ) {
				$filename = str_replace( $source, '', $file );
				$filename = wp_normalize_path( $filename );
				$filename = ltrim( $filename, '/\\' );

				//Themes are included separately from the wpak themes directory
				if ( preg_match( '|themes[/\\\].+|', $filename ) ) {
					continue;
				}

				$zip_filename = $source_root . $filename;

                $file_extension = pathinfo( $file, PATHINFO_EXTENSION );

				if ( is_dir( $file ) === true ) {

					if ( !$zip->addEmptyDir( $zip_filename ) ) {
						$answer['msg'] = sprintf( __( 'Could not add directory [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
						$answer['ok'] = 0;
						return $answer;
					}

				} elseif ( is_file( $file ) === true ) {

                    $minify_file = self::is_file_to_minify( $file, $export_type, $app_id );

					if ( $filename === 'index.html' ) {

						$index_content = self::filter_index( file_get_contents( $file ), $app_id, $export_type );

						if ( !$zip->addFromString( $zip_filename, $index_content ) ) {
							$answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
							$answer['ok'] = 0;
							return $answer;
						}

					} else if ( $filename === 'service-worker-cache.js' ) {

						//Only include 'service-worker-cache.js' for progressive web apps:
						if ( $export_type === 'pwa' ) {
							//We add the service worker file at the end of the export because we need $webapp_files to be all set.
							//Here we just memorize info about service worker file:
							$sw_cache_file_data = array( 'file' => $file, 'filename' => $filename, 'zip_filename' => $zip_filename );
						}

						//Even if progressive web app, don't include service worker in cached files
						continue;

					} else {

                        if ( $minify_file ) {

                            $file_content = file_get_contents( $file );

                            $file_content = $minifier->minify( $file_extension, $file_content );

                            if ( !$zip->addFromString( $zip_filename, $file_content ) ) {
                                $answer['msg'] = sprintf( __( 'Could not add minified file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
                                $answer['ok'] = 0;
                                return $answer;
                            }

                        } else {

                            if ( !$zip->addFile( $file, $zip_filename ) ) {
                                $answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
                                $answer['ok'] = 0;
                                return $answer;
                            }

                        }

					}

					$webapp_files[] = $zip_filename;
				}
			}

			//Add themes files :
			if( !empty( $themes ) ) {

				$themes_directory = WpakThemes::get_themes_directory();
				if( is_dir( $themes_directory ) ) {

					$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $themes_directory ), RecursiveIteratorIterator::SELF_FIRST );

					foreach ( $files as $file ) {
						$filename = str_replace( $themes_directory, '', $file );
						$filename = wp_normalize_path( $filename );
						$filename = ltrim( $filename, '/\\' );

						//Filter themes :
						$theme = preg_replace( '|([^/\\\]*)[/\\\].*|', '$1', $filename );

						if ( !in_array( $theme, $themes ) ) {
							continue;
						}

						//Filter php directory
						if ( preg_match( '|'. $theme .'[/\\\]php|', $filename ) ) {
							continue;
						}

						//Filter git files
						if ( strpos( $filename, '.git' ) !== false ) {
							continue;
						}

						$theme_filename = str_replace( $theme .'/', '', $filename );

						$filename = 'themes/'. $filename;

						$zip_filename = $source_root . $filename;

                        $file_extension = pathinfo( $file, PATHINFO_EXTENSION );

						if ( is_dir( $file ) === true ) {
							if ( !$zip->addEmptyDir( $zip_filename ) ) {
								$answer['msg'] = sprintf( __( 'Could not add directory [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
								$answer['ok'] = 0;
								return $answer;
							}
						} elseif ( is_file( $file ) === true ) {

							//Include PWA icons only for PWA export:
							if ( $export_type !== 'pwa' && strpos( $theme_filename, 'icons/pwa-icon-' ) === 0 ) {
								continue;
							}

							if ( $theme_filename === 'head.html' ) {

								$head_content = self::filter_head_template_content( file_get_contents( $file ), $app_id, $export_type );

								if ( !$zip->addFromString( $zip_filename, $head_content ) ) {
									$answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
									$answer['ok'] = 0;
									return $answer;
								}

							} else {

	                            $minify_file = self::is_file_to_minify( $file, $export_type, $app_id );

	                            if ( $minify_file ) {

	                                $file_content = file_get_contents( $file );

	                                $file_content = $minifier->minify( $file_extension, $file_content );

	                                if ( !$zip->addFromString( $zip_filename, $file_content ) ) {
	                                    $answer['msg'] = sprintf( __( 'Could not add minified file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
	                                    $answer['ok'] = 0;
	                                    return $answer;
	                                }

	                            } else {

	                                if ( !$zip->addFile( $file, $zip_filename ) ) {
	                                    $answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
	                                    $answer['ok'] = 0;
	                                    return $answer;
	                                }

	                            }

	                        }

							$webapp_files[] = $zip_filename;

						}
					}
				}
			}

			//Add addons files :
			if ( !empty( $addons ) ) {
				foreach ( $addons as $addon ) {
					$addon_files = $addon->get_all_files( $app_id );
					foreach ( $addon_files as $addon_file ) {
						$zip_filename = $source_root .'addons/'. $addon->slug .'/'. $addon_file['relative'];

                        $file_extension = pathinfo( $addon_file['full'], PATHINFO_EXTENSION );

                        $minify_file = self::is_file_to_minify( $addon_file['full'], $export_type, $app_id );

                        if ( $minify_file ) {

                            $file_content = file_get_contents( $addon_file['full'] );

                            $file_content = $minifier->minify( $file_extension, $file_content );

                            if ( !$zip->addFromString( $zip_filename, $file_content ) ) {
                                $answer['msg'] = sprintf( __( 'Could not add minified file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
                                $answer['ok'] = 0;
                                return $answer;
                            }

                        } else {
                            $zip->addFile( $addon_file['full'], $zip_filename );
                        }

						$webapp_files[] = $zip_filename;
					}
				}
			}

			//Add icons and splashscreens files :
			if ( !empty( $icons_and_splashscreens )
				 && array_key_exists( 'icons', $icons_and_splashscreens )
				 && array_key_exists( 'splashscreens', $icons_and_splashscreens )
				) {

				$icons = $icons_and_splashscreens['icons'];
				foreach ( $icons as $icon ) {
					$zip_filename = $source_root . $icon['src'];
					$zip->addFile( $icon['full_path'], $zip_filename );
				}

				if ( $export_type !== 'pwa' ) { //Don't need splashscreens for progressive web apps
					$splashscreens = $icons_and_splashscreens['splashscreens'];
					foreach ( $splashscreens as $splashscreen ) {
						$zip_filename = $source_root . $splashscreen['src'];
						$zip->addFile( $splashscreen['full_path'], $zip_filename );
					}
				}

			}

			//Create config.js file :
            $config_js_file = $source_root .'config.js';
            $config_js_file_content = WpakConfigFile::get_config_js( $app_id, false, $export_type );
            $minify_file = self::is_file_to_minify( $config_js_file, $export_type, $app_id );
            if ( $minify_file ) {
                $config_js_file_content = $minifier->minify( 'js', $config_js_file_content );
            }
			$zip->addFromString( $config_js_file, $config_js_file_content );
			$webapp_files[] = $config_js_file;

			if ( !in_array( $export_type, array( 'webapp', 'webapp-appcache', 'pwa' ) ) ) {
				//Create config.xml file (stays at zip root) :
				$zip->addFromString( 'config.xml', WpakConfigFile::get_config_xml( $app_id, false, $export_type ) );
			}

			if ( $export_type === 'webapp-appcache' ) {
				//Create html cache manifest file
				$cache_manifest_content = self::get_cache_manifest_content( $webapp_files );
				$zip->addFromString( 'wpak.appcache', $cache_manifest_content );
			}

			if ( $export_type === 'pwa' && !empty( $sw_cache_file_data ) ) {

				//Add manifest:
				$zip->addFromString( $source_root .'manifest.json', self::get_pwa_manifest( $app_id ) );

                if ( self::pwa_pretty_slugs_on( $app_id ) ) {
                    //Add htaccess (required to handle pretty slugs with HTML5 pushstate):
                    $zip->addFromString( $source_root .'.htaccess', self::get_pwa_htaccess( $app_id ) );
                }

				//Add service worker:
				$sw_cache_content = self::build_pwa_service_worker_cache( $app_id, file_get_contents( $sw_cache_file_data['file'] ), $webapp_files, $export_type );

				if ( !$zip->addFromString( $sw_cache_file_data['zip_filename'], $sw_cache_content ) ) {
					$answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $sw_cache_file_data['zip_filename'] );
					$answer['ok'] = 0;
					return $answer;
				}

			}

		} else {
			$answer['msg'] = sprintf( __( 'Zip archive source directory [%s] could not be found.', WpAppKit::i18n_domain ), $source );
			$answer['ok'] = 0;
			return $answer;
		}

		if ( !$zip->close() ) {
			$answer['msg'] = __( 'Error during archive creation', WpAppKit::i18n_domain );
			$answer['ok'] = 0;
			return $answer;
		}

		return $answer;
	}

    private static function is_file_to_minify( $file, $export_type, $app_id ) {

        $file_extension = pathinfo( $file, PATHINFO_EXTENSION );

        //By default, minify all js and css files that are not already minified, if we're not in debug mode:
        $minify_file = $export_type === 'pwa' && !self::is_app_in_debug_mode( $app_id ) 
        				&& in_array( $file_extension, array( 'js', 'css' ) ) && strpos( $file, '.min.' ) === false;

        /**
         * Use this "wpak_minify_app_file" filter to decide whether a given file or file extension
         * should be minified or not. By default minification is only enabled on PWA exports, with
         * all JS and CSS files minified.
         *
         * @param boolean		$minify_file        return true to minify, false to not minify file.
         * @param string        $file_extension     file extension
         * @param string        $file               file path
         * @param string        $export_type        current export type
         * @param integer       $app_id             current app id
         */
        $minify_file = apply_filters( 'wpak_minify_app_file', $minify_file, $file_extension, $file, $export_type, $app_id );

        return $minify_file;
    }

    private static function pwa_pretty_slugs_on( $app_id ) {
        /**
         * Use this filter to deactivate pretty slugs on Progressive Web App and
         * go back to standard #fragment navigation.
         */
        return apply_filters( 'wpak_pwa_pretty_slugs_on', true, $app_id );
    }

	private static function filter_index( $index_content, $app_id, $export_type ) {

		if ( $export_type === 'webapp-appcache' ) {

			//Add reference to the AppCache manifest to the <html> tag:
			$index_content = str_replace( '<html>', '<html manifest="wpak.appcache" >', $index_content );

			//Remove script used only for app simulation in web browser:
			$index_content = preg_replace( '/<script[^>]*>[^<]*var require[^<]*?(<\/script>)\s*/is', '', $index_content );

		} else if ( $export_type === 'webapp' ) {

			//Remove script used only for app simulation in web browser:
			$index_content = preg_replace( '/<script[^>]*>[^<]*var require[^<]*?(<\/script>)\s*/is', '', $index_content );

		} else if ( $export_type === 'pwa' ) {

			//Add page title:
			$app_main_infos = WpakApps::get_app_main_infos( $app_id );
			$app_title = $app_main_infos['title'];
			$app_title_html = "<title>". esc_html( $app_title ) ."</title>\n";

			$app_description = $app_main_infos['pwa_desc'];
			$app_description = apply_filters( 'wpak_meta_description', $app_description, $app_id, $export_type );
			$app_description_html = "<meta name=\"description\" content=\"". esc_html( $app_description ) ."\" >\n";

            if ( self::pwa_pretty_slugs_on( $app_id ) ) {
                //Add "base" (required to handle pretty slugs with HTML5 pushstate):
                $pwa_base_suburl = !empty(  $app_main_infos['pwa_path'] ) ? $app_main_infos['pwa_path'] : '';

                $pwa_base_suburl = self::add_subdir_prefix( $pwa_base_suburl );

                $base_html = "<base href=\"/". trailingslashit( ltrim( $pwa_base_suburl, '/' ) )  ."\" >\n";
            }

			//Add manifest link:
			$manifest_html = '<link rel="manifest" href="./manifest.json">'."\n";

			// Add theme color meta
			$theme_color = '';
			if( !empty( $app_main_infos['pwa_theme_color'] ) ) {
				$theme_color = '<meta name="theme-color" content="' . $app_main_infos['pwa_theme_color'] . '">' . "\n";
			}

			$index_content = preg_replace( '/<head>(\s*?)<link/is', "<head>$1". $app_title_html ."$1". $app_description_html ."$1". $manifest_html ."$1". $base_html ."$1". $theme_color ."$1<link", $index_content );

			//Remove script used only for app simulation in web browser:
			$index_content = preg_replace( '/<script[^>]*>[^<]*var require[^<]*?(<\/script>)\s*/is', '', $index_content );

		} else {

			//Add cordova.js script (set cordova.js instead of phonegap.js, because PhoneGap Developer App doesn't seem
			//to support phonegap.js). PhoneGap Build can use indifferently cordova.js or phonegap.js.
			$index_content = str_replace( '<head>', "<head>\r\n\t\t<script src=\"cordova.js\"></script>\r\n\t\t", $index_content );

		}

        //Remove debug CSS if not in debug mode:
        if ( !self::is_app_in_debug_mode( $app_id ) ) {
            $index_content = preg_replace( '|<link [^>]*?href=\"core/css/debug.css\">\s*|is', "", $index_content );
        }

        //Remove script used only for app simulation in web browser :
		$index_content = preg_replace( '/<script[^>]*>[^<]*var query[^<]*<\/script>\s*<script/is', '<script', $index_content );

		//Add HTML lang attribute:
		$lang = apply_filters( 'wpak_html_lang', get_bloginfo( 'language' ), $app_id, $export_type );
		$index_content = preg_replace( '/<html>/i', '<html lang="'. esc_attr( $lang ) .'">', $index_content );

        //Insert launch content in index.html for very fast "First Meaningful Paint" (only used for pwa by default) :
        $launch_content_data = self::get_launch_content_data( $app_id, $export_type );

        if ( !empty( $launch_content_data['content'] ) ) {
            //Here we pre-render app's layout so that we don't have to wait for layout rendering in app.
            //Also dynamic app rendering empties app content during content load, which we want to avoid for PWAs.

            //Replace content tag in layout.html by launch content:
            $launch_layout = preg_replace('/<%= content %>/',"\r\n<div id=\"app-content-wrapper\">\r\n<div class=\"app-screen\">". $launch_content_data['content'] ."\r\n</div></div>\t\t", $launch_content_data['layout'] );

            //Replace menu tag in layout.html by "#app-menu" div:
            $launch_layout = preg_replace('/<%= menu %>/',"<div id=\"app-menu\"></div>", $launch_layout );

            //Insert pre-rendered layout in index.html:
            $index_content = preg_replace('/(<div[^>]+id="app-layout"[^>]*>)/',"$1\r\n". $launch_layout ."\r\n\t\t", $index_content );
        }

        //Also include in head what's needed by pre-rendered layout to render well:
        if ( !empty( $launch_content_data['head'] ) ) {
            $index_content = preg_replace('/(<\/head>)/',"\r\n". $launch_content_data['head'] ."\r\n\r\n$1", $index_content );
        }

        $index_content = apply_filters( 'wpak_app_index_content', $index_content, $app_id, $export_type );

		return $index_content;
	}

    public static function add_subdir_prefix( $slug ) {
        //Prefix slug with subdirectory if WP is installed in subdirectory:
        $subdir = parse_url( get_option( 'siteurl' ), PHP_URL_PATH );
        if ( $subdir && $subdir !== '/' ) {
            $left_slash = strpos( $slug, '/' ) === 0;
            $slug = trailingslashit( ltrim( $subdir, '/' ) ) . ltrim( $slug, '/' );
            if ( $left_slash ) {
                $slug = '/'. $slug;
            }
        }
        return $slug;
    }

	private static function build_pwa_service_worker_cache( $app_id, $content, $webapp_files, $export_type ) {

		$cache_version = 'wpak-app-'. $app_id .'-'. date( 'Ymd-his');

		$cache_files = '';
		if ( !empty( $webapp_files ) ) {
			$cache_files .= "'/',\n";
			foreach( $webapp_files as $file ) {
				$cache_files .= "'/". $file ."',\n";
			}
			$cache_files = rtrim( $cache_files, ",\n" );
		}

		//Set cache version and cached files:
		$content = preg_replace( "/(var cacheName = ')(';).*/", "$1". $cache_version ."$2", $content );
		$content = preg_replace( "/(var filesToCache = \[)(\];).*/", "$1". $cache_files ."$2", $content );

		$content = apply_filters( 'wpak_pwa_service_worker', $content, $app_id, $webapp_files, $export_type );

		return $content;
	}

	private static function get_pwa_manifest( $app_id ) {

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );

		$pwa_name = !empty( $app_main_infos['pwa_name'] ) ? $app_main_infos['pwa_name'] : $app_main_infos['title'];
		$pwa_short_name = !empty( $app_main_infos['pwa_short_name'] ) ? $app_main_infos['pwa_short_name'] : $pwa_name;

		$manifest = array(
			"name" => $pwa_name,
			"short_name" => $pwa_short_name,
			"description" => !empty( $app_main_infos['pwa_desc'] ) ? $app_main_infos['pwa_desc'] : '',
			"icons" => self::get_pwa_manifest_icons( $app_id ),
			"start_url" => "./",
			"display" => "standalone",
		);

		if ( !empty( $app_main_infos['pwa_background_color'] ) ) {
			$manifest['background_color'] = $app_main_infos['pwa_background_color'];
		}

		if ( !empty( $app_main_infos['pwa_theme_color'] ) ) {
			$manifest['theme_color'] = $app_main_infos['pwa_theme_color'];
		}

		$manifest = apply_filters( 'wpak_pwa_manifest', $manifest, $app_id );

		return json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

    private static function get_pwa_htaccess( $app_id ) {

        $htaccess = ""
                . "#WP-AppKit rewrite rules\n"
                . "#Redirect all urls to index.html to allow deeplinks\n"
                . "#and pretty slugs using HTML5 pushstate\n"
                . "<IfModule mod_rewrite.c>\n"
                . "RewriteEngine On\n";

                /**
                 * PWA requirements include redirecting http to https.
                 * Use this 'wpak_pwa_htaccess_redirect_https' to deactivate http to https redirections
                 * (for example if working on a local non-https environment).
                 *
                 * @param $redirect boolean   Whether to redirect http to https or not (defaults to is_ssl()). Return false to deactivate http to https redirection.
                 * @param $app_id   int       App id
                 */
                if ( apply_filters( 'wpak_pwa_htaccess_redirect_https', is_ssl(), $app_id ) ) {
					$htaccess .= ""
					. "RewriteCond %{HTTPS} off\n"
					. "RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R,L]\n";
				}

                $htaccess .= ""
                . "RewriteCond %{REQUEST_FILENAME} !-f\n"
                . "RewriteCond %{REQUEST_FILENAME} !-d\n"
                . "RewriteCond %{REQUEST_URI} !.html\n"
                . "RewriteCond %{REQUEST_URI} !.js\n"
                . "RewriteRule (.*) index.html [L]\n"
                . "</IfModule>\n";

        $htaccess = apply_filters( 'wpak_pwa_htaccess', $htaccess, $app_id );

        return $htaccess;
    }

	private static function get_pwa_manifest_icons( $app_id ) {
		$manifest_icons = array();

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_icons = $app_main_infos['pwa_icons'];
		if ( empty( $app_icons ) ) {

			$icons_and_splash = WpakConfigFile::get_platform_icons_and_splashscreens_files( $app_id, $app_main_infos['platform'], 'pwa' );
			$icons = $icons_and_splash['icons'];

			if ( !empty( $icons ) ) {
				foreach( $icons as $icon ) {
					$manifest_icons[] = array(
						'src' => $icon['src'],
						'sizes' => $icon['width'] .'x'. $icon['height'],
						'type' => $icon['type'],
					);
				}
			}

		} else {
		    foreach( $app_icons as $icon ) {
		        $src = str_replace( WpakThemes::get_themes_directory(), 'themes', $icon['path'] );
		        $manifest_icons[] = array(
		            'src' => $src,
		            'sizes' => $icon['size'][0] . 'x' . $icon['size'][1],
		            'type' => 'image/png',
		        );
		    }
		}

		return $manifest_icons;
	}

    /**
     * Get launch content data from theme's files
     */
    private static function get_launch_content_data( $app_id, $export_type ) {
        $launch_content_data = [
            'content' => '',
            'head' => ''
        ];

        //Only activate launch content for PWAs by default
        $activate_launch_content = apply_filters( 'wpak_setup_launch_content', $export_type === 'pwa', $export_type, $app_id );
        if ( $activate_launch_content ) {

	        $current_theme = WpakThemesStorage::get_current_theme( $app_id );
	        $themes_directory = WpakThemes::get_themes_directory();
	        $current_theme_directory = $themes_directory .'/'. $current_theme;
			if( is_dir( $current_theme_directory ) ) {
	            $layout_file = $current_theme_directory .'/'. self::layout_file_name;
	            if ( file_exists( $layout_file ) ) {
	                $launch_content_data['layout'] = file_get_contents( $layout_file );
	            }
	            $launch_content_file = $current_theme_directory .'/'. self::launch_content_file_name;
	            if ( file_exists( $launch_content_file ) ) {
	                $launch_content_data['content'] = file_get_contents( $launch_content_file );
	            }
	            $launch_head_file = $current_theme_directory .'/'. self::launch_head_file_name;
	            if ( file_exists( $launch_head_file ) ) {
	            	$launch_head_content = file_get_contents( $launch_head_file );

	            	//Set assets urls:
	            	//repace JS calls (TemplateTags.getThemeAssetUrl()) by hardcoded theme paths:
	            	$launch_head_content = preg_replace('/<%=\s*TemplateTags.getThemeAssetUrl\(\s*[\'"]([^\'"]+)[\'"]\s*\);?\s*%>/',
	            										'themes/'. $current_theme .'/$1', 
	            										$launch_head_content );

	                $launch_content_data['head'] = $launch_head_content;
	            }
	        }

	        $launch_content_data = apply_filters( 'wpak_launch_content_data', $launch_content_data, $app_id, $current_theme, $current_theme_directory );

	    }

        return $launch_content_data;
    }

    private static function filter_head_template_content( $head_template_content, $app_id, $export_type ) {

    	$launch_content_data = self::get_launch_content_data( $app_id, $export_type );
    	if ( !empty( $launch_content_data['head'] ) ) {
    		if ( apply_filters( 'wpak_clean_head_template_if_launch_head', true, $app_id, $export_type ) ) {
	    		//If we have a launch head content defined, it replaces default head.html template.
	    		// > We empty head.html template to avoid loading assets twice:
				$head_template_content = '<!-- head.html is replaced by launch-head.html to handle launch content rendering -->';    		
			}
    	}

		$head_template_content = apply_filters( 'wpak_head_template_content', $head_template_content, $app_id, $export_type );    	

    	return $head_template_content;
    }

	public static function pwa_icons_json_to_array( $app_icons_json ) {
		$app_icons_json = stripslashes( $app_icons_json );
		$app_icons_array = json_decode( $app_icons_json, false );
		return !empty( $app_icons_array ) && is_array( $app_icons_array ) ? $app_icons_array : array();
	}

	private static function get_cache_manifest_content( $webapp_files ) {
		$cache_manifest = '';

		if ( !empty( $webapp_files ) ) {
			$cache_manifest .= "CACHE MANIFEST\n";

			$cache_manifest .= "# v". date( 'Y-m-d H:i:s' ) ."\n\n";

			foreach( $webapp_files as $file ) {
				$cache_manifest .= $file ."\n";
			}

			$cache_manifest .= "\nNETWORK:\n";
			$cache_manifest .= "/wp-appkit-api\n";
			$cache_manifest .= "/wp-content/uploads\n";
		}

		return $cache_manifest;
	}

}

WpakBuild::hooks();
