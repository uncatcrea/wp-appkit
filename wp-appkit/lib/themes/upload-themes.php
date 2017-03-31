<?php

/**
 * Handles the WP-AppKit BO panel for uploading themes
 */
class WpakUploadThemes {

	const menu_item = 'wpak_bo_upload_themes';

	public static function hooks() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_panels' ), 30 ); //30 to pass after Settings
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_theme_upload_styles' ) );
		}
	}

	public static function add_settings_panels() {
		$capability_required = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';
		add_submenu_page( WpakApps::menu_item, __( 'Upload Themes', WpAppKit::i18n_domain ), __( 'Upload Themes', WpAppKit::i18n_domain ), $capability_required, self::menu_item, array( __CLASS__, 'settings_panel' ) );
	}

	public static function add_theme_upload_styles() {
		global $pagenow, $plugin_page;
		if ( ($pagenow === 'admin.php' ) && $plugin_page === 'wpak_bo_upload_themes' ) {
			wp_enqueue_style( 'add_theme_upload_styles', plugins_url( 'lib/themes/upload-themes.css', dirname( dirname( __FILE__ ) ) ), array(), WpAppKit::resources_version );
		}
	}

	public static function settings_panel() {
		$action = !empty( $_GET['wpak_action'] ) ? $_GET['wpak_action'] : '';

		switch( $action ) {
			case 'upload-theme':
				if ( ! current_user_can( 'upload_plugins' ) && ! current_user_can( 'wpak_edit_apps' ) ) {
					wp_die( __( 'You do not have sufficient permissions to install WP-AppKit themes on this site.', WpAppKit::i18n_domain ) );
				}

				check_admin_referer( 'wpak-theme-upload' );

				include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

				$file_upload = new File_Upload_Upgrader('themezip', 'package');

				$file_type = wp_check_filetype( $file_upload->filename );
				if( $file_type['ext'] == 'zip' && $file_type['type'] == 'application/zip') {

					$title = sprintf( __('Installing WP-AppKit from uploaded file: %s', WpAppKit::i18n_domain), esc_html( basename( $file_upload->filename ) ) );
					$nonce = 'wpak-theme-upload';
					$url = add_query_arg( array('package' => $file_upload->id) );

					// A nonce is passed to WP_Upgrader_Skin class, so wp_nonce_url() is called and url is escaped there...
					$upgrader = new WP_Upgrader( new WP_Upgrader_Skin( compact('title', 'nonce', 'url') ) );

					$destination_folder_name = basename(sanitize_file_name($file_upload->filename), ".zip");

					$result = $upgrader->run( array(
						'package' => $file_upload->package,
						'destination' => WpakThemes::get_themes_directory() .'/'. $destination_folder_name,
						'clear_destination' => true, // overwrite files.
						'clear_working' => true,
						'hook_extra' => array()
					) );

					if ( $result || is_wp_error($result) ){
						$file_upload->cleanup();
					}

					if( !is_wp_error($result) ){
						echo sprintf( __("WP-AppKit theme '%s' installed successfully!", WpAppKit::i18n_domain), $destination_folder_name );
					}else{
						_e('An error occured', WpAppKit::i18n_domain);
						echo ' : '. $result->get_error_message();
					}

					echo '<br/><br/><a href="'. esc_url ( remove_query_arg('wpak_action') ) .'">'. __('Back to theme upload form', WpAppKit::i18n_domain) .'</a>';

					echo '<br/><br/><a href="'. admin_url() .'/edit.php?post_type=wpak_apps">'. __('Go to my WP-AppKit app list', WpAppKit::i18n_domain) .'</a>';

				}else{
					_e("Uploaded file must be a valid zip file", WpAppKit::i18n_domain);
				}
				break;
			case 'reinstall-default-themes':
				if ( ! current_user_can( 'upload_plugins' ) && ! current_user_can( 'wpak_edit_apps' ) ) {
					wp_die( __( 'You do not have sufficient permissions to install WP-AppKit themes on this site.', WpAppKit::i18n_domain ) );
				}

				check_admin_referer( 'wpak-reinstall-default-themes' );

				$proceed = !empty( $_GET['wpak_confirm'] );

				if( !$proceed ) {
					// Check if there is already at least 1 default theme available, so that we can warn the user it will be erased
					$check = WpakThemes::check_default_themes();

					$proceed = count( $check ) == count( WpakThemes::get_default_themes() );

					foreach( $check as $result ) {
						if( $result != 'unavailable' ) {
							$proceed = false;
							break;
						}
					}
				}

				if( $proceed ) {
					// It's confirmed, we can copy
					$ok = WpakThemes::copy_default_themes();

					if( $ok ) {
						$result = array(
							'type' => 'updated',
							'message' => __( 'Default themes have correctly been reinstalled.', WpAppKit::i18n_domain ),
						);
					}
					else {
						$result = array(
							'type' => 'error',
							'message' => __( 'An error occured while reinstalling default themes. Please do it manually by downloading the packages below.', WpAppKit::i18n_domain ),
						);
					}
				}
				else {
					// Warn the user
					?>
					<div class="wrap" id="wpak-settings">
						<h2><?php _e( 'Reinstall Default Themes', WpAppKit::i18n_domain ); ?></h2>

						<div class="error">
							<p>
								<?php _e( 'We found at least one theme that is already installed. This process will override all existing files. Are you sure you want to continue?', WpAppKit::i18n_domain ); ?>
							</p>
						</div>

						<div>
							<a class="button button-primary button-large" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wpak_action' => 'reinstall-default-themes', 'wpak_confirm' => 1 ) ), 'wpak-reinstall-default-themes' ) ); ?>"><?php _e( 'I understand and want to continue', WpAppKit::i18n_domain); ?></a>
							<a class="button button-large" href="<?php echo esc_url( remove_query_arg( 'wpak_action' ) ); ?>"><?php _e( 'Back to theme upload form', WpAppKit::i18n_domain ); ?></a>
						</div>
					</div>
					<?php
					break;
				}
			default:
				$default_themes = WpakThemes::get_default_themes();
				?>
				<div class="wrap" id="wpak-settings">
					<h2>
						<?php
						_e( 'WP-AppKit Themes upload', WpAppKit::i18n_domain );

						if( !empty( $default_themes ) ):
						?>
							<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpak_action', 'reinstall-default-themes', remove_query_arg( 'wpak_confirm' ) ), 'wpak-reinstall-default-themes' ) ); ?>" class="hide-if-no-js page-title-action"><?php _e( 'Reinstall Default Themes', WpAppKit::i18n_domain ); ?></a>
						<?php endif; ?>
					</h2>

					<?php if ( !empty( $result['message'] ) ): ?>
						<div class="<?php echo esc_attr( $result['type'] ) ?>" ><p><?php echo $result['message'] ?></p></div>
					<?php endif ?>

					<div class="upload-plugin">
						<p class="install-help"><?php _e( 'If you have a WP-AppKit theme in a .zip format, you may install it by uploading it here.', WpAppKit::i18n_domain ); ?></p>
						<form method="post" enctype="multipart/form-data" class="wp-upload-form" action="<?php echo esc_url( add_query_arg( array( 'wpak_action' => 'upload-theme' ) ) ) ?>">
							<?php wp_nonce_field( 'wpak-theme-upload' ); ?>
							<label class="screen-reader-text" for="themezip"><?php _e('WP-AppKit Theme zip file', WpAppKit::i18n_domain); ?></label>
							<input type="file" id="themezip" name="themezip" />
							<?php submit_button( __( 'Install Now' ), 'button', 'install-theme-submit', false ); ?>
						</form>
					</div>

					<?php if( !empty( $default_themes ) ): $default_themes_exist = false; ?>
						<h2><?php _e( 'Download Default Themes Packages', WpAppKit::i18n_domain ); ?></h2>
						<div>
							<ul>
								<?php foreach( $default_themes as $slug => $theme ):
									$filename = WpakThemes::get_default_theme_filename( $slug, $theme );
									if( !is_file( WpakThemes::get_default_themes_directory() . '/' . $filename ) ) {
										continue;
									}
									$default_themes_exist = true;
									?>
									<li>
										<a href="<?php echo esc_url( WpakThemes::get_default_themes_directory_uri() . '/' . $filename ); ?>"><?php echo esc_html( $theme['name'] . ' (' . $theme['version'] . ')' ); ?></a>
									</li>
								<?php endforeach; ?>
                                <?php if( !$default_themes_exist ): ?>
                                    <li>
                                        <?php echo sprintf( __( 'No packages available, you can find default themes under %s and copy them directly.', WpAppKit::i18n_domain ), WpakThemes::get_default_themes_directory() ); ?>
                                    </li>
                                <?php endif; ?>
							</ul>
						</div>
					<?php endif; ?>

				</div>
				<?php
				break;
		}
	}
}

WpakUploadThemes::hooks();