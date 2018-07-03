<?php
/*
Plugin Name: WP-AppKit
Plugin URI:  https://github.com/uncatcrea/wp-appkit
Description: Build mobile apps and PWA based on your WordPress content.
Version:     1.5.2
Author:      Uncategorized Creations
Author URI:  http://getwpappkit.com
Text Domain: wp-appkit
Domain Path: /lang
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Copyright:   2013-2018 Uncategorized Creations

This plugin, like WordPress, is licensed under the GPL.
Use it to make something cool, have fun, and share what you've learned with others.
*/

if ( !class_exists( 'WpAppKit' ) ) {

	class WpAppKit {

		const resources_version = '1.5.2';
		const i18n_domain = 'wp-appkit';
        
		public static function hooks() {
			add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );

			register_activation_hook( __FILE__, array( __CLASS__, 'on_activation' ) );
			register_deactivation_hook( __FILE__, array( __CLASS__, 'on_deactivation' ) );

			add_action( 'init', array( __CLASS__, 'init' ) );
			add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 5 );

			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
			add_action( 'admin_init', array( __CLASS__, 'upgrade' ) );
		}

		protected static function lib_require() {
            require_once(dirname( __FILE__ ) . '/lib/config.php');
			require_once(dirname( __FILE__ ) . '/lib/addons/addons.php');
			require_once(dirname( __FILE__ ) . '/lib/user-permissions/user-login.php');
			require_once(dirname( __FILE__ ) . '/lib/web-services/web-services.php');
			require_once(dirname( __FILE__ ) . '/lib/apps/apps.php');
			require_once(dirname( __FILE__ ) . '/lib/apps/build.php');
			require_once(dirname( __FILE__ ) . '/lib/themes/themes.php');
			require_once(dirname( __FILE__ ) . '/lib/themes/upload-themes.php');
			require_once(dirname( __FILE__ ) . '/lib/user-permissions/user-permissions.php');
			require_once(dirname( __FILE__ ) . '/lib/settings/licenses/licenses.php');
			require_once(dirname( __FILE__ ) . '/lib/settings/settings.php');
			require_once(dirname( __FILE__ ) . '/lib/components/components.php');
			require_once(dirname( __FILE__ ) . '/lib/navigation/navigation.php');
			require_once(dirname( __FILE__ ) . '/lib/options/options.php');
			require_once(dirname( __FILE__ ) . '/lib/simulator/simulator.php');
			require_once(dirname( __FILE__ ) . '/lib/simulator/server-rewrite.php');
			require_once(dirname( __FILE__ ) . '/lib/shortcodes/show_hide_in_apps.php');
		}

		public static function plugins_loaded() {
			load_plugin_textdomain( self::i18n_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
			self::lib_require();
		}

		public static function on_activation() {
			self::lib_require();

			self::add_rewrite_rules();
			flush_rewrite_rules();

			$directory = WpakThemes::create_theme_directory();
			if( !empty( $directory ) ) {
				WpakThemes::install_default_themes();
			}

			//If WordPress Network, add WP-AppKit custom rewrite rules to htacces,
			//required for apps' preview to work.
			if( is_multisite() ) {
				WpakServerRewrite::prepend_wp_network_wpak_rules_to_htaccess();
			}
			
			//If WP-AppKit custom user role was activated before deactivating 
			//the plugin, reactivate it:
			$settings = WpakSettings::get_settings();
			if ( $settings['activate_wp_appkit_editor_role'] ) {
				WpakUserPermissions::create_wp_appkit_user_role();
			}
		}

		public static function on_deactivation( $network_wide ) {
			flush_rewrite_rules();

			//If this is a network wide uninstall, we can remove WP-AppKit rules.
			//If this is not a network wide uninstall, we can't remove our rules
			//as they may be needed by another site of the network. We could
			//check all sites to see if WP-AppKit is installed somewhere, but this would
			//require making switch_to_blog() for every sites, which we consider
			//dangerous performance-wise, knowing that we can have > 10000 websites...
			//So, unless we find a better solution, we don't remove our rules
			//if this is not a network wide uninstall. (This should not cause any
			//problem as WP-AppKit rules only apply to WP-AppKit plugin
			//and can't interfer with other rules).
			if( is_multisite() && $network_wide ) {
				WpakServerRewrite::delete_wp_network_wpak_rules_from_htaccess();
			}

			//Remove WP-AppKit custom user role on deactivation (if it was 
			//activated in settings panel):
			WpakUserPermissions::remove_wp_appkit_user_role();
		}

		public static function init() {
			self::add_rewrite_rules();
			WpakComponents::handle_images_sizes();
		}

		public static function template_redirect() {
			WpakWebServices::template_redirect();
		}

		protected static function add_rewrite_rules() {
			WpakWebServices::add_rewrite_tags_and_rules();
			WpakConfigFile::rewrite_rules();
			WpakThemes::rewrite_rules();
		}

		/**
		 * Check if WP-AppKit Plugin has been upgraded, and run corresponding upgrade routine(s).
		 *
		 * Contains conditional checks to determine which upgrade scripts to run,
		 * based on database version and WP-AppKit version being updated-to.
		 */
		public static function upgrade() {
			$db_version = get_option( 'wpak_version', null );
			$data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
			$plugin_file_version = $data['version'];

			// /!\ NOTE
			//version_compare() considers 1 != 1.0 != 1.0.0
			//-> it considers: 1 < 1.0 < 1.0.0
			//So for example if we have 1.0 in plugin file we should compare it to 1.0, not 1.0.0
			
			if ( $db_version === null ) {
				//This is a first install, no need for upgrade routine.
				//Just set db version:
				update_option( 'wpak_version', $plugin_file_version );
				
				//And, if network install, save rewrite rules because on_activation hook
				//is not executed when network activating a plugin. (See notes here: 
				//https://codex.wordpress.org/Function_Reference/register_activation_hook )
				if ( is_multisite() ) {
					self::add_rewrite_rules();
					flush_rewrite_rules();
				}
				return;
			}
			
			if ( version_compare( $plugin_file_version, $db_version, '=' ) ) {
				// We are up-to-date. Nothing to do.
				return;
			}

			if( version_compare( $db_version, '0.6', '<' ) ) {
				self::upgrade_060();
			}
			
			if( version_compare( $db_version, '1.0', '<' ) ) {
				self::upgrade_100();
			}
			
			if( version_compare( $db_version, '1.1', '<' ) ) {
				self::upgrade_110();
			}
			
			if ( !version_compare( $plugin_file_version, $db_version, '=' ) ) {
				//Update db version not to run update scripts again and so that
				//db version is up to date:
				update_option( 'wpak_version', $plugin_file_version );
			}
			
		}

		/**
		 * Execute changes made in WP-AppKit 0.6.0
		 */
		protected static function upgrade_060() {
			// Remove 'wpak_used_themes' transient since its value's structure changed with this version
			delete_transient( 'wpak_used_themes' );
			
			//Memorize we've gone that far successfully, to not re-run this routine 
			//in case something goes wrong in next upgrade routines:
			update_option( 'wpak_version', '0.6' );
		}
		
		/**
		 * Execute changes made in WP-AppKit 1.0
		 */
		protected static function upgrade_100() {
			//We have to flush rewrite rules to take into account new rules to use WP core's versions
			//of jQuery, undercore and backbone:
			self::add_rewrite_rules();
			flush_rewrite_rules();
			
			//If WordPress Network, add WP-AppKit custom rewrite rules to htacces,
			//required for apps' preview to work.
			if( is_multisite() ) {
				WpakServerRewrite::prepend_wp_network_wpak_rules_to_htaccess();
			}
			
			//Memorize we've gone that far successfully, to not re-run this routine 
			//in case something goes wrong in next upgrade routines:
			update_option( 'wpak_version', '1.0' );
		}
		
		/**
		 * Execute changes made in WP-AppKit 1.1
		 */
		protected static function upgrade_110() {
			//New rewrite rules to take into account new management of WP core's assets
			self::add_rewrite_rules();
			flush_rewrite_rules();
			if( is_multisite() ) {
				WpakServerRewrite::prepend_wp_network_wpak_rules_to_htaccess();
			}
			
			//Create new default themes version zips (v1.0.5) so that they're available as download packages:
			WpakThemes::create_themes_zip();
			
			//Memorize we've gone that far successfully, to not re-run this routine 
			//in case something goes wrong in next upgrade routines:
			update_option( 'wpak_version', '1.1' );
		}
		
		/**
		 * If permalinks are not activated, send an admin notice
		 */
		public static function admin_notices() {
			if ( !get_option( 'permalink_structure' ) ) {
				?>
				<div class="error">
					<p>
						<?php
							_e( 'WP-AppKit requires WordPress permalinks to be activated: '
								. 'see the <a href="http://codex.wordpress.org/Using_Permalinks#Choosing_your_permalink_structure">"Using permalink" Codex section</a> '
								. 'for more info about how to activate permalinks.',
								WpAppKit::i18n_domain
							);
						?>
					</p>
				</div>
				<?php
			}
		}
	}

	WpAppKit::hooks();

	/**
	 * WP-AppKit WP CLI commands :
	 */
	if ( defined('WP_CLI') && WP_CLI ) {
		require_once( dirname( __FILE__ ) .'/wp-cli-commands/wpak-commands.php' );
	}
}
