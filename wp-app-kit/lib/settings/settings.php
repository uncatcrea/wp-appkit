<?php

/**
 * Handles the WP AppKit settings panel for general plugin settings.
 * Default settings are defined in WpakSettings::get_settings();
 */
class WpakSettings {

	const menu_item = 'wpak_bo_settings_page';
	const option_id = 'wpak_settings_option';

	public static function hooks() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_panels' ), 20 ); //20 to pass after Simulator
		}
	}

	public static function add_settings_panels() {
		$capability_required = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';
		add_submenu_page( WpakApps::menu_item, __( 'Settings', WpAppKit::i18n_domain ), __( 'Settings', WpAppKit::i18n_domain ), $capability_required, self::menu_item, array( __CLASS__, 'settings_panel' ) );
	}
	
	public static function settings_panel() {

		$result = self::handle_posted_settings();
		$settings = self::get_settings();
		
		?>
		<div class="wrap" id="wpak-settings">
			<h2><?php _e( 'WP AppKit Settings', WpAppKit::i18n_domain ) ?></h2>

			<?php if ( !empty( $result['message'] ) ): ?>
				<div class="<?php echo $result['type'] ?>" ><p><?php echo $result['message'] ?></p></div>
			<?php endif ?>
			
			<form method="post" action="<?php echo add_query_arg( array() ) ?>">
				
				<table>
					<tr>
						<th><?php _e( 'Apps post lists', WpAppKit::i18n_domain ) ?></th>
						<td>
							<label for="posts_per_page"><?php _e('Number of posts per list in WP AppKit apps', WpAppKit::i18n_domain ) ?> : </label><br/>
							<input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo $settings['posts_per_page'] ?>" />
						</td>
					</tr>
					<tr>
						<th><?php _e( 'WP AppKit user role', WpAppKit::i18n_domain ) ?></th>
						<td>
							<input type="checkbox" name="activate_wp_appkit_editor_role" id="activate_wp_appkit_editor_role" <?php echo !empty($settings['activate_wp_appkit_editor_role']) ? 'checked' : ''?> />
							<label for="activate_wp_appkit_editor_role"><?php _e('Activate a "WP AppKit App Editor" role that can only edit WP AppKit apps and no other WordPress contents', WpAppKit::i18n_domain ) ?></label>
						</td>
					</tr>
					
				</table>

				<?php wp_nonce_field( 'wpak_save_settings' ) ?>

				<input type="submit" class="button button-primary" value="<?php _e( 'Save settings', WpAppKit::i18n_domain ) ?>" />

			</form>

		</div>

		<style>
			#wpak-settings table{ margin:2em 0 }
			#wpak-settings table td{ padding: 1em 2em }
			#wpak-settings table th, #wpak-settings table td{ text-align: left }
		</style>
		
		<?php
	}

	public static function get_settings() {
		$default_settings = array(
			'posts_per_page' => get_option( 'posts_per_page' ),
			'activate_wp_appkit_editor_role' => false,
		);
		
		$settings = wp_parse_args( get_option( self::option_id ), $default_settings );
		
		return $settings;
	}
	
	public static function get_setting($setting) {
		$settings = self::get_settings();
		return isset($settings[$setting]) ? $settings[$setting] : false;
	}

	protected static function handle_posted_settings() {
		$result = array(
			'message' => '',
			'type' => 'updated'
		);
		
		if ( isset( $_POST['posts_per_page'] ) && check_admin_referer( 'wpak_save_settings' ) ) {
			$settings = self::get_settings();

			if ( !empty( $_POST['posts_per_page'] ) ) {
				$settings['posts_per_page'] = intval( $_POST['posts_per_page'] );
			}
			
			if ( isset( $_POST['activate_wp_appkit_editor_role'] ) ) {
				WpakUserPermissions::create_wp_appkit_user_role();
				$settings['activate_wp_appkit_editor_role'] = true;
			}else{
				WpakUserPermissions::remove_wp_appkit_user_role();
				$settings['activate_wp_appkit_editor_role'] = false;
			}

			self::save_settings( $settings );
			
			$result['message'] = __( 'Settings saved', WpAppKit::i18n_domain );
		}
		
		return $result;
	}

	protected static function save_settings( $settings ) {
		if ( get_option( self::option_id ) != $settings ) {
			update_option( self::option_id, $settings );
		} else {
			add_option( self::option_id, $settings, '', 'no' );
		}
	}

}

WpakSettings::hooks();