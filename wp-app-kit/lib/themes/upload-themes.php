<?php

/**
 * Handles the WP AppKit BO panel for uploading themes
 */
class WpakUploadThemes {

	const menu_item = 'wpak_bo_upload_themes';

	public static function hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_panels' ), 30 ); //30 to pass after Settings
	}

	public static function add_settings_panels() {
		$capability_required = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';
		add_submenu_page( WpakApps::menu_item, __( 'Upload themes', WpAppKit::i18n_domain ), __( 'Upload themes', WpAppKit::i18n_domain ), $capability_required, self::menu_item, array( __CLASS__, 'settings_panel' ) );
	}
	
	public static function settings_panel() {

		if( isset($_GET['wpak_action']) && $_GET['wpak_action'] == 'upload-theme' ){
			
			if ( ! current_user_can( 'upload_plugins' ) && ! current_user_can( 'wpak_edit_apps' ) ) {
				wp_die( __( 'You do not have sufficient permissions to install WP AppKit themes on this site.', WpAppKit::i18n_domain ) );
			}
			
			check_admin_referer( 'wpak-theme-upload' );			

			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
			
			$file_upload = new File_Upload_Upgrader('themezip', 'package');
			
			$file_type = wp_check_filetype( $file_upload->filename );
			if( $file_type['ext'] == 'zip' && $file_type['type'] == 'application/zip') {

				$title = sprintf( __('Installing WP AppKit from uploaded file: %s', WpAppKit::i18n_domain), esc_html( basename( $file_upload->filename ) ) );
				$nonce = 'wpak-theme-upload';
				$url = add_query_arg( array('package' => $file_upload->id) );

				$upgrader = new WP_Upgrader( new WP_Upgrader_Skin( compact('title', 'nonce', 'url') ) );

				$destination_folder_name = basename(sanitize_file_name($file_upload->filename), ".zip");

				$result = $upgrader->run( array(
					'package' => $file_upload->package,
					'destination' => WpakThemes::get_theme_directory() .'/'. $destination_folder_name,
					'clear_destination' => true, // overwrite files.
					'clear_working' => true,
					'hook_extra' => array()
				) );

				if ( $result || is_wp_error($result) ){
					$file_upload->cleanup();
				}
				
				if( !is_wp_error($result) ){
					echo sprintf( __("WP AppKit theme '%s' installed successfully!", WpAppKit::i18n_domain), $destination_folder_name );
				}else{
					_e('An error occured', WpAppKit::i18n_domain);
					echo ' : '. $result->get_error_message();
				}
				
				echo '<br/><br/><a href="'. remove_query_arg('wpak_action') .'">'. __('Back to theme upload form', WpAppKit::i18n_domain) .'</a>';
				
				echo '<br/><br/><a href="'. admin_url() .'/edit.php?post_type=wpak_apps">'. __('Go to my WP AppKit app list', WpAppKit::i18n_domain) .'</a>';
				
			}else{
				_e("Uploaded file must be a valid zip file", WpAppKit::i18n_domain);
			}
			
		}else{
			?>
			<div class="wrap" id="wpak-settings">
				<h2><?php _e( 'WP AppKit Themes upload', WpAppKit::i18n_domain ) ?></h2>

				<?php if ( !empty( $result['message'] ) ): ?>
					<div class="<?php echo $result['type'] ?>" ><p><?php echo $result['message'] ?></p></div>
				<?php endif ?>

				<div class="upload-plugin">
					<p class="install-help"><?php _e('If you have a WP AppKit theme in a .zip format, you may install it by uploading it here.'); ?></p>
					<form method="post" enctype="multipart/form-data" class="wp-upload-form" action="<?php echo add_query_arg(array('wpak_action'=>'upload-theme')) ?>">
						<?php wp_nonce_field( 'wpak-theme-upload' ); ?>
						<label class="screen-reader-text" for="themezip"><?php _e('WP AppKit Theme zip file', WpAppKit::i18n_domain); ?></label>
						<input type="file" id="themezip" name="themezip" />
						<?php submit_button( __( 'Install Now' ), 'button', 'install-theme-submit', false ); ?>
					</form>
				</div>

			</div>
			<?php
		}
	}

}

WpakUploadThemes::hooks();
