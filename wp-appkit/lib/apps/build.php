<?php

class WpakBuild {

	const export_file_memory = 10;

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
			<label><?php _e( 'Debug Mode', WpAppKit::i18n_domain ) ?> : </label>
			<select name="wpak_app_debug_mode">
				<option value="on" <?php echo $debug_mode == 'on' ? 'selected="selected"' : '' ?>><?php _e( 'On', WpAppKit::i18n_domain ) ?></option>
				<option value="off" <?php echo $debug_mode == 'off' ? 'selected="selected"' : '' ?>><?php _e( 'Off', WpAppKit::i18n_domain ) ?></option>
				<option value="wp" <?php echo $debug_mode == 'wp' ? 'selected="selected"' : '' ?>><?php _e( 'Same as WordPress WP_DEBUG', WpAppKit::i18n_domain ) ?></option>
			</select>
			<span class="description"><?php _e( 'If activated, echoes debug infos in the browser javascript console while simulating the app.', WpAppKit::i18n_domain ) ?></span>
		</div>
		<div class="field-group">
			<a href="<?php echo self::get_appli_dir_url() . '/config.js?wpak_app_id=' . WpakApps::get_app_slug( $post->ID ) ?>" target="_blank"><?php _e( 'View config.js', WpAppKit::i18n_domain ) ?></a>
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

	public static function download_app_sources() {

		if ( !check_admin_referer( 'wpak_download_app_sources' ) || !isset( $_GET['post'] ) ) {
			return;
		}

		$app_id = intval( $_GET['post'] );

		if( $app_id <= 0 ) {
			return;
		}

		// Re-build sources
		$answer = self::build_app_sources( $app_id );

		if( 1 === $answer['ok'] && !empty( $answer['export'] ) ) {
			$filename = $answer['export'] . '.zip';
			$filename_full = self::get_export_files_path() . "/" . $filename;
		}
		else {
			echo $answer['msg'];
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
			ob_end_flush();
			@readfile( $filename_full );
			exit;
		} else {
			echo sprintf( __( 'Error: Could not find zip export file [%s]', WpAppKit::i18n_domain ), $filename_full );
			exit;
		}
	}

	public static function build_app_sources( $app_id, $export_type = 'phonegap-build' ) {
		$answer = array();

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

		$current_theme = WpakThemesStorage::get_current_theme( $app_id );

		$plugin_dir = plugin_dir_path( dirname( dirname( __FILE__ ) ) );
		$appli_dir = $plugin_dir . 'app';

		$export_filename = self::get_export_file_base_name( $app_id );
		$export_filename_full = self::get_export_files_path() . "/" . $export_filename . '.zip';

		$answer = self::build_zip( $app_id, $appli_dir, $export_filename_full, array( $current_theme ), WpakAddons::get_app_addons( $app_id ), $export_type );

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

		$answer = self::build_app_sources( $app_id );

		self::exit_sending_json( $answer );
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

	private static function get_export_file_base_name( $app_id ) {
		return 'phonegap-export-' . WpakApps::get_app_slug( $app_id );
	}

	private static function create_export_directory_if_doesnt_exist() {
		$export_directory = self::get_export_files_path();
		$ok = true;
		if ( !file_exists( $export_directory ) ) {
			$ok = mkdir( $export_directory, 0777, true );
		}
		return $ok;
	}

	private static function build_zip( $app_id, $source, $destination, $themes, $addons, $export_type ) {

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

		if ( is_dir( $source ) === true ) {

			$source_root = '';
			
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
				
				if ( is_dir( $file ) === true ) {
					
					if ( !$zip->addEmptyDir( $zip_filename ) ) {
						$answer['msg'] = sprintf( __( 'Could not add directory [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
						$answer['ok'] = 0;
						return $answer;
					}
					
				} elseif ( is_file( $file ) === true ) {

					if ( $filename == 'index.html' ) {

						$index_content = self::filter_index( file_get_contents( $file ) );

						if ( !$zip->addFromString( $zip_filename, $index_content ) ) {
							$answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
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

						$filename = 'themes/'. $filename;
						
						$zip_filename = $source_root . $filename;
						
						if ( is_dir( $file ) === true ) {
							if ( !$zip->addEmptyDir( $zip_filename ) ) {
								$answer['msg'] = sprintf( __( 'Could not add directory [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
								$answer['ok'] = 0;
								return $answer;
							}
						} elseif ( is_file( $file ) === true ) {
							if ( !$zip->addFile( $file, $zip_filename ) ) {
								$answer['msg'] = sprintf( __( 'Could not add file [%s] to zip archive', WpAppKit::i18n_domain ), $zip_filename );
								$answer['ok'] = 0;
								return $answer;
							}
						}
					}
				}
			}

			//Add addons files :
			if ( !empty( $addons ) ) {
				foreach ( $addons as $addon ) {
					$addon_files = $addon->get_all_files();
					foreach ( $addon_files as $addon_file ) {
						$zip_filename = $source_root .'addons/'. $addon->slug .'/'. $addon_file['relative'];
						$zip->addFile( $addon_file['full'], $zip_filename );
					}
				}
			}

			//Create config.js file :
			$zip->addFromString( $source_root .'config.js', WpakConfigFile::get_config_js( $app_id ) );
			
			//Create config.xml file (stays at zip root) :
			$zip->addFromString( 'config.xml', WpakConfigFile::get_config_xml( $app_id, false, $export_type ) );
			
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

	private static function filter_index( $index_content ) {

		//Add cordova.js script (set cordova.js instead of phonegap.js, because PhoneGap Developer App doesn't seem
		//to support phonegap.js). PhoneGap Build can use indifferently cordova.js or phonegap.js.
		$index_content = str_replace( '<head>', "<head>\r\n\t\t<script src=\"cordova.js\"></script>\r\n\t\t", $index_content );

		//Remove script used only for app simulation in web browser :
		$index_content = preg_replace( '/<script[^>]*>[^<]*var query[^<]*<\/script>\s*<script/is', '<script', $index_content );

		return $index_content;
	}

}

WpakBuild::hooks();
