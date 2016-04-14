<?php

/*
  Plugin Name: WP AppKit
  Description: Build Phonegap Mobile apps based on your WordPress content
  Version: 0.5
 */

if ( !class_exists( 'WpAppKit' ) ) {

	class WpAppKit {

		const resources_version = '0.5';
		// Holds the WP-AppKit DB revision, increments when changes are made that need an upgrade routine to run
		const db_version = 42;
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
			require_once(dirname( __FILE__ ) . '/lib/addons/addons.php');
			require_once(dirname( __FILE__ ) . '/lib/user-permissions/user-login.php');
			require_once(dirname( __FILE__ ) . '/lib/web-services/web-services.php');
			require_once(dirname( __FILE__ ) . '/lib/apps/apps.php');
			require_once(dirname( __FILE__ ) . '/lib/apps/build.php');
			require_once(dirname( __FILE__ ) . '/lib/themes/themes.php');
			require_once(dirname( __FILE__ ) . '/lib/themes/upload-themes.php');
			require_once(dirname( __FILE__ ) . '/lib/user-permissions/user-permissions.php');
			require_once(dirname( __FILE__ ) . '/lib/settings/settings.php');
			require_once(dirname( __FILE__ ) . '/lib/components/components.php');
			require_once(dirname( __FILE__ ) . '/lib/navigation/navigation.php');
			require_once(dirname( __FILE__ ) . '/lib/options/options.php');
			require_once(dirname( __FILE__ ) . '/lib/simulator/simulator.php');
			require_once(dirname( __FILE__ ) . '/lib/simulator/server-rewrite.php');
		}

		public static function plugins_loaded() {
			load_plugin_textdomain( self::i18n_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
			self::lib_require();
		}

		public static function on_activation() {
			self::lib_require();

			self::add_rewrite_rules();
			flush_rewrite_rules();

			WpakThemes::create_theme_directory();

			//If WordPress Network, add WP-AppKit custom rewrite rules to htacces,
			//required for apps' preview to work.
			if( is_multisite() ) {
				WpakServerRewrite::prepend_wp_network_wpak_rules_to_htaccess();
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
			$current_version = get_option( 'wpak_version', 0 );

			// We are up-to-date. Nothing to do.
			if ( self::db_version == $current_version )
				return;

			if( $current_version < 42 ) {
				self::upgrade_060();
			}
		}

		/**
		 * Execute changes made in WP-AppKit 0.6.0.
		 *
		 * @TODO Complete the migration script for 0.6.0
		 */
		protected static function upgrade_060() {
			// Everything went fine, update DB version not to run this script once again
			update_option( 'wpak_version', 42 );
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
							_e( 'WP AppKit requires WordPress permalinks to be activated: '
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
	 * WP AppKit WP CLI commands :
	 */
	if ( defined('WP_CLI') && WP_CLI ) {
		require_once( dirname( __FILE__ ) .'/wp-cli-commands/wpak-commands.php' );
	}
}
