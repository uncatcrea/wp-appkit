<?php

/**
 * Handles the WP-AppKit settings panel for general plugin settings.
 * Default settings are defined in WpakSettings::get_settings();
 */
class WpakSettings {

	const menu_item = 'wpak_bo_settings_page';
	const option_id = 'wpak_settings_option';

	public static function hooks() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_panels' ), 20 ); //20 to pass after Simulator
			add_action( 'admin_print_styles', array( __CLASS__, 'admin_print_styles' ) );
		}
	}
	
	public static function admin_print_styles() {
		global $pagenow;
		if ( $pagenow == 'admin.php'  && !empty( $_GET['page'] ) && $_GET['page'] === 'wpak_bo_settings_page' ) {
			wp_enqueue_style( 'wpak_settings_css', plugins_url( 'lib/settings/settings.css', dirname( dirname( __FILE__ ) ) ), array(), WpAppKit::resources_version );
		}
	}

	public static function add_settings_panels() {
		$capability_required = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';
		add_submenu_page( WpakApps::menu_item, __( 'Settings', WpAppKit::i18n_domain ), __( 'Settings', WpAppKit::i18n_domain ), $capability_required, self::menu_item, array( __CLASS__, 'settings_panel' ) );
	}
	
	public static function settings_panel() {

		$active_tab = !empty( $_GET['wpak_settings_page'] ) ? sanitize_key( $_GET['wpak_settings_page'] ) : 'general';
		
		if ( !in_array( $active_tab, array( 'general', 'licenses' ) ) ) {
			$active_tab = 'general';
		}
		
		$settings_base_url = self::get_settings_base_url();
		
		?>
		<div class="wrap" id="wpak-settings">
			<h2><?php _e( 'WP-AppKit Settings', WpAppKit::i18n_domain ) ?></h2>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( array( 'wpak_settings_page' => 'general' ), $settings_base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e( 'General', WpAppKit::i18n_domain ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( array( 'wpak_settings_page' => 'licenses' ), $settings_base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'licenses' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Licenses', WpAppKit::i18n_domain ); ?></a>
			</h2>
			
			<div class="wrap-<?php echo $active_tab; ?>">
				<?php 
					$content_function = 'tab_' . $active_tab;
					if( method_exists( __CLASS__, $content_function ) ) {
						self::$content_function();
					}
				?>
			</div>

		</div>

		<?php
	}
	
	protected static function tab_general() {
		$result = self::handle_posted_settings();
		$settings = self::get_settings();
		?>
		
		<?php if ( !empty( $result['message'] ) ): ?>
			<div class="<?php echo esc_attr( $result['type'] ) ?>" ><p><?php echo $result['message'] ?></p></div>
		<?php endif ?>

		<form method="post" action="<?php echo esc_url( add_query_arg( array() ) ) ?>">

			<table>
				<tr>
					<th><?php _e( 'Apps post lists', WpAppKit::i18n_domain ) ?></th>
					<td>
						<label for="posts_per_page"><?php _e('Number of posts per list in WP-AppKit apps', WpAppKit::i18n_domain ) ?> : </label><br/>
						<input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_attr( $settings['posts_per_page'] ) ?>" />
					</td>
				</tr>
				<tr>
					<th><?php _e( 'App modification alerts', WpAppKit::i18n_domain ) ?></th>
					<td>
						<input type="checkbox" name="activate_wp_appkit_app_modif_alerts" id="activate_wp_appkit_app_modif_alerts" <?php echo !empty($settings['activate_wp_appkit_app_modif_alerts']) ? 'checked' : ''?> />
						<label for="activate_wp_appkit_app_modif_alerts"><?php _e('Show a confirmation dialog in app edition panel when modifying apps\' components or navigation', WpAppKit::i18n_domain ) ?></label>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'WP-AppKit user role', WpAppKit::i18n_domain ) ?></th>
					<td>
						<input type="checkbox" name="activate_wp_appkit_editor_role" id="activate_wp_appkit_editor_role" <?php echo !empty($settings['activate_wp_appkit_editor_role']) ? 'checked' : ''?> />
						<label for="activate_wp_appkit_editor_role"><?php _e('Activate a "WP-AppKit App Editor" role that can only edit WP-AppKit apps and no other WordPress contents', WpAppKit::i18n_domain ) ?></label>
					</td>
				</tr>

			</table>

			<?php wp_nonce_field( 'wpak_save_settings' ) ?>

			<input type="submit" class="button button-primary" value="<?php _e( 'Save settings', WpAppKit::i18n_domain ) ?>" />

		</form>
		<?php
	}
	
	protected static function tab_licenses() {
		WpakLicenses::tab_licenses();
	}

	public static function get_settings() {
		$default_settings = array(
			'posts_per_page' => get_option( 'posts_per_page' ),
			'activate_wp_appkit_editor_role' => false,
			'activate_wp_appkit_app_modif_alerts' => true,
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

			if ( isset( $_POST['activate_wp_appkit_app_modif_alerts'] ) ) {
				$settings['activate_wp_appkit_app_modif_alerts'] = true;
			} else {
				$settings['activate_wp_appkit_app_modif_alerts'] = false;
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
	
    protected static function get_settings_base_url() {
        return admin_url( 'admin.php?page=wpak_bo_settings_page' );
    }
}

WpakSettings::hooks();