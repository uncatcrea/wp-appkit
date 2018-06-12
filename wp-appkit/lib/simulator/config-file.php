<?php

class WpakConfigFile {

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 1 );
	}

	public static function rewrite_rules() {
		add_rewrite_tag( '%wpak_appli_file%', '([^&]+)' );

		$home_url = home_url(); //Something like "http://my-site.com"
		$url_to_config_file = plugins_url( 'app', dirname( dirname( __FILE__ ) ) ); //Something like "http://my-site.com/wp-content/plugins/wp-appkit/app"
		$config_file_prefix = str_replace( trailingslashit($home_url), '', $url_to_config_file ); //Something like "wp-content/plugins/wp-appkit/app"

		add_rewrite_rule( '^' . $config_file_prefix . '/(config\.js)$', 'index.php?wpak_appli_file=$matches[1]', 'top' );
		add_rewrite_rule( '^' . $config_file_prefix . '/(config\.xml)$', 'index.php?wpak_appli_file=$matches[1]', 'top' );
	}

	public static function template_redirect() {
		global $wp_query;

		if ( isset( $wp_query->query_vars['wpak_appli_file'] ) && !empty( $wp_query->query_vars['wpak_appli_file'] ) ) {

			if ( !empty( $_GET['wpak_app_id'] ) ) {

				$app_id = esc_attr( $_GET['wpak_app_id'] ); //can be ID or slug

				$app = WpakApps::get_app( $app_id );

				if ( !empty( $app ) ) {
					$app_id = $app->ID;

					$default_capability = current_user_can('wpak_edit_apps') ? 'wpak_edit_apps' : 'manage_options';

					$capability = apply_filters( 'wpak_private_simulation_capability', $default_capability, $app_id );

					if ( WpakApps::get_app_simulation_is_secured( $app_id ) && !current_user_can( $capability ) ) {
						wp_nonce_ays( $action );
					}

					//If the app current theme has some PHP (hooks!) to be executed before
					//config files are generated, include it here :
					WpakThemes::include_app_theme_php( $app_id );

					//Include PHP files required by addons activated for this app :
					WpakAddons::require_app_addons_php_files( $app_id );

					$file = $wp_query->query_vars['wpak_appli_file'];

					switch ( $file ) {
						case 'config.js':
							header( "Content-type: text/javascript;  charset=utf-8" );
							echo "/* Wp AppKit simulator config.js */\n";
							self::get_config_js( $app_id, true );
							exit();
						case 'config.xml':
							header( "Content-type: text/xml;  charset=utf-8" );
							self::get_config_xml( $app_id, true );
							exit();
						default:
							exit();
					}
				} else {
					echo __( 'App not found', WpAppKit::i18n_domain ) . ' : [' . $app_id . ']';
					exit();
				}
			} else {
				_e( 'App id not found in _GET parameters', WpAppKit::i18n_domain );
				exit();
			}
		}
	}

	public static function get_config_js( $app_id, $echo = false, $export_type = '' ) {
		$wp_ws_url = WpakWebServices::get_app_web_service_base_url( $app_id );
		$theme = WpakThemesStorage::get_current_theme( $app_id );

		$app_slug = WpakApps::get_app_slug( $app_id );

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_title = $app_main_infos['title'];

		$app_platform = $app_main_infos['platform'];
		$app_platform = empty( $app_platform ) ? 'all' : $app_platform;
        
        $pwa_path = !empty( $app_main_infos['pwa_path'] ) ? trailingslashit( $app_main_infos['pwa_path'] ) : '';
        $pwa_path = WpakBuild::add_subdir_prefix( $pwa_path );
        $app_path = $app_platform === 'pwa' ? '/'. trailingslashit( ltrim( $pwa_path, '/' ) ) : '';

		$app_version = WpakApps::sanitize_app_version( $app_platform === 'pwa' ? $app_main_infos['pwa_version'] : $app_main_infos['version'] );

		$gmt_offset = (int)get_option( 'gmt_offset' );

		$debug_mode = WpakBuild::get_app_debug_mode( $app_id );

		$auth_key = WpakApps::get_app_is_secured( $app_id ) ? WpakToken::get_hash_key() : '';
		//TODO : options to choose if the auth key is displayed in config.js.

		$options = WpakOptions::get_app_options( $app_id );

		$theme_settings = WpakThemesConfigJsSettings::get_theme_settings( $app_id );

		$addons = WpakAddons::get_app_addons_for_config( $app_id );

		if ( !$echo ) {
			ob_start();
		}
//Indentation is a bit funky here so it appears ok in the config.js file source:
?>
define( function ( require ) {

	"use strict";

	return {
		app_slug : '<?php echo $app_slug ?>',
		wp_ws_url : '<?php echo $wp_ws_url ?>',
		wp_url : '<?php echo home_url() ?>',
		theme : '<?php echo addslashes($theme) ?>',
		version : '<?php echo $app_version ?>',
		app_type : '<?php echo !empty( $export_type ) ? $export_type : 'preview' ?>',
		app_title : '<?php echo addslashes($app_title) ?>',
		app_platform : '<?php echo addslashes($app_platform) ?>',
		app_path: '<?php echo addslashes($app_path) ?>',
		gmt_offset : <?php echo addslashes($gmt_offset) ?>,
		debug_mode : '<?php echo $debug_mode ?>'<?php
		if( !empty( $auth_key ) ):
		?>,
		auth_key : '<?php echo $auth_key ?>'<?php
		endif
		?>,
		options : <?php echo json_encode( $options ); ?>,
		theme_settings : <?php echo json_encode( $theme_settings ); ?>,
		addons : <?php echo json_encode( $addons ) ?>

	};

});
<?php
		$content = '';
		if ( !$echo ) {
			$content = ob_get_contents();
			ob_end_clean();
		}

		return !$echo ? $content : '';
	}

	/**
	 * Retrieves whitelist settings.
	 * Will be active only if the "cordova-plugin-whitelist" plugin is added to config.xml.
	 * (see https://github.com/apache/cordova-plugin-whitelist).
	 *
	 * @param int $app_id Application ID
	 * @param string $app_platform Platform (see WpakApps::get_platforms to get the list)
	 * @param string $export_type 'phonegap-build' (default), 'phonegap-cli' or 'webapp'
	 */
	protected static function get_whitelist_settings( $app_id, $app_platform, $export_type = 'phonegap-build' ) {

		//By default we allow everything :
		$whitelist_settings = array(
			'access' => array( 'origin' => '*' ),
			'allow-intent' => array( 'href' => '*' ),
			'allow-navigation' => array( 'href' => '*' )
		);

		//No whitelist setting if the 'cordova-plugin-whitelist' plugin is not here :
		$whitelist_plugin_here = true;
		$current_phonegap_plugins = WpakApps::get_merged_phonegap_plugins( $app_id, $export_type );
		if ( !array_key_exists( 'cordova-plugin-whitelist', $current_phonegap_plugins ) ) {
			$whitelist_settings = array();
			$whitelist_plugin_here = false;
		}

		/**
		 * Filter : allows to modify config.xml whitelist configuration.
		 * See https://github.com/apache/cordova-plugin-whitelist for detailed info.
		 *
		 * @param array  	$whitelist_settings     Array of whitelist settings : contains :
		 *											 'access' => array( 'origin' => '*' ),
		 *											 'allow-intent' => array( 'href' => '*' ),
		 *											 'allow-navigation' => array( 'href' => '*' )
		 * @param int		$app_id		            Application id
		 * @param string    $app_platform           Application platform (ios, android ...)
		 * @param string    $export_type            'phonegap-build' (default), 'phonegap-cli' or 'webapp'
		 * @param boolean   $whitelist_plugin_here  Whether or not the whitelist plugin is activated for this app.
		 */
		$whitelist_settings = apply_filters( 'wpak_config_xml_whitelist', $whitelist_settings, $app_id, $app_platform, $export_type, $current_phonegap_plugins, $whitelist_plugin_here );

		return $whitelist_settings;
	}

	/**
	 * Return settings useful to configure splashscreen in a PhoneGap Build config.xml file.
	 * This shouldn't be used for a platform that won't need PhoneGap Build (ex: pwa).
	 *
	 * @param int       $app_id         Application ID.
	 * @param string    $app_platform   App platform (see WpakApps::get_platforms to get the list).
	 * @param string    $export_type    Export type.
	 *
	 * @return array $splashcreen_settings
	 */
	protected static function get_splashscreen_settings( $app_id, $app_platform, $export_type ) {
		$splashcreen_settings = array( 'preferences' => array(), 'gap:config-file' => array() );

		switch ( $app_platform ) {
			case 'ios':
				$splashcreen_settings['preferences']['AutoHideSplashScreen'] = 'false';
				$splashcreen_settings['preferences']['FadeSplashScreenDuration'] = '1000';
				$splashcreen_settings['gap:config-file']['UIStatusBarHidden'] = 'true';
				$splashcreen_settings['gap:config-file']['UIViewControllerBasedStatusBarAppearance'] = 'false';
				break;
			case 'android':
				$splashcreen_settings['preferences']['SplashScreen'] = 'splash';
				$splashcreen_settings['preferences']['SplashScreenDelay'] = '10000';
				$splashcreen_settings['preferences']['FadeSplashScreenDuration'] = '300';
				//Auto hiding doesn't work on Android (https://issues.apache.org/jira/browse/CB-8396).
				//So the plan is to have a very long delay for the splashscreen and let Javascript hiding the splashscreen
				break;
		}

		// For all platforms, hide the spinner
		$splashcreen_settings['preferences']['ShowSplashScreenSpinner'] = 'false';

		//No splashscreen setting if the 'cordova-plugin-splashscreen' plugin is not here :
		$splashcreen_plugin_here = true;
		$current_phonegap_plugins = WpakApps::get_merged_phonegap_plugins( $app_id, $export_type );
		if ( !array_key_exists( 'cordova-plugin-splashscreen', $current_phonegap_plugins ) ) {
			$splashcreen_settings = array();
			$splashcreen_plugin_here = false;
		}

		/**
		 * Filter : allows to modify config.xml splashscreen configuration.
		 * See https://github.com/apache/cordova-plugin-splashscreen for detailed info.
		 *
		 * @param array  	$splashcreen_settings     Array of whitelist settings
		 * @param int		$app_id		              Application id
		 * @param string    $app_platform             Application platform (ios, android ...)
		 * @param string    $export_type              'phonegap-build' (default), 'phonegap-cli' or 'webapp'
		 * @param boolean   $splashcreen_plugin_here  Whether or not the splashscreen plugin is activated for this app.
		 */

		$splashcreen_settings = apply_filters( 'wpak_config_xml_splashscreen', $splashcreen_settings, $app_id, $app_platform, $export_type, $current_phonegap_plugins, $splashcreen_plugin_here );

		return $splashcreen_settings;
	}

	protected static function get_default_icons_and_splashscreens( $app_id, $export_type ) {

		$default_icons = array(
			'android' => array (
				array( 'src' => 'icon.png', 'qualifier' => '', 'width' => '', 'height' => '' ),
				array( 'src' => 'icons/icon-wp-appkit-ldpi.png', 'qualifier' => 'ldpi', 'width' => '', 'height' => ''  ),
				array( 'src' => 'icons/icon-wp-appkit-mdpi.png', 'qualifier' => 'mdpi', 'width' => '', 'height' => ''  ),
				array( 'src' => 'icons/icon-wp-appkit-hdpi.png', 'qualifier' => 'hdpi', 'width' => '', 'height' => ''  ),
				array( 'src' => 'icons/icon-wp-appkit-xhdpi.png', 'qualifier' => 'xhdpi', 'width' => '', 'height' => ''  ),
				array( 'src' => 'icons/icon-wp-appkit-xxhdpi.png', 'qualifier' => 'xxhdpi', 'width' => '', 'height' => ''  ),
				array( 'src' => 'icons/icon-wp-appkit-xxxhdpi.png', 'qualifier' => 'xxxhdpi', 'width' => '', 'height' => ''  ),
			),
			'ios' => array(
				array( 'src' => 'icons/icon-60@3x.png', 'qualifier' => '', 'width' => '180', 'height' => '180' ),
				array( 'src' => 'icons/icon-60@2x.png', 'qualifier' => '', 'width' => '120', 'height' => '120'  ),
				array( 'src' => 'icons/icon@2x.png', 'qualifier' => '', 'width' => '114', 'height' => '114'  ),
				array( 'src' => 'icons/icon-40@2x.png', 'qualifier' => '', 'width' => '80', 'height' => '80'  ),
				array( 'src' => 'icons/icon-60.png', 'qualifier' => '', 'width' => '60', 'height' => '60'  ),
				array( 'src' => 'icons/icon-small@2x.png', 'qualifier' => '', 'width' => '58', 'height' => '58'  ),
				array( 'src' => 'icons/icon.png', 'qualifier' => '', 'width' => '57', 'height' => '57'  ),
				array( 'src' => 'icons/icon-40.png', 'qualifier' => '', 'width' => '40', 'height' => '40'  ),
				array( 'src' => 'icons/icon-small.png', 'qualifier' => '', 'width' => '29', 'height' => '29'  ),
			),
			'pwa' => array(
				array( 'src' => 'icons/pwa-icon-48x48.png', 'width' => '48', 'height' => '48', 'type' => 'image/png'  ),
				array( 'src' => 'icons/pwa-icon-96x96.png', 'width' => '96', 'height' => '96', 'type' => 'image/png'  ),
				array( 'src' => 'icons/pwa-icon-144x144.png', 'width' => '144', 'height' => '144', 'type' => 'image/png' ),
				array( 'src' => 'icons/pwa-icon-192x192.png', 'width' => '192', 'height' => '192', 'type' => 'image/png' ),
				array( 'src' => 'icons/pwa-icon-512x512.png', 'width' => '512', 'height' => '512', 'type' => 'image/png' ),
			)
		);

		$default_splashscreens = array(
			'android' => array (
				array( 'src' => 'splash.9.png', 'qualifier' => '', 'width' => '', 'height' => '' ),
				array( 'src' => 'splashscreens/splashscreen-wp-appkit-ldpi.9.png', 'qualifier' => 'ldpi', 'width' => '', 'height' => '' ),
				array( 'src' => 'splashscreens/splashscreen-wp-appkit-mdpi.9.png', 'qualifier' => 'mdpi', 'width' => '', 'height' => '' ),
				array( 'src' => 'splashscreens/splashscreen-wp-appkit-hdpi.9.png', 'qualifier' => 'hdpi', 'width' => '', 'height' => '' ),
				array( 'src' => 'splashscreens/splashscreen-wp-appkit-xhdpi.9.png', 'qualifier' => 'xhdpi', 'width' => '', 'height' => '' ),
				array( 'src' => 'splashscreens/splashscreen-wp-appkit-xxhdpi.9.png', 'qualifier' => 'xxhdpi', 'width' => '', 'height' => '' ),
				array( 'src' => 'splashscreens/splashscreen-wp-appkit-xxxhdpi.9.png', 'qualifier' => 'xxxhdpi', 'width' => '', 'height' => '' ),
			),
			'ios' => array(
				array( 'src' => 'splashscreens/Default-736h-Lanscape@3x~iphone.png', 'qualifier' => '', 'width' => '2208', 'height' => '1242' ),
				array( 'src' => 'splashscreens/Default-736h-Portrait@3x~iphone.png', 'qualifier' => '', 'width' => '1242', 'height' => '2208' ),
				array( 'src' => 'splashscreens/Default-667h-Landscape@2x~iphone.png', 'qualifier' => '', 'width' => '1334', 'height' => '750' ),
				array( 'src' => 'splashscreens/Default-667h-Portrait@2x~iphone.png', 'qualifier' => '', 'width' => '750', 'height' => '1334' ),
				array( 'src' => 'splashscreens/Default-568h-Landscape@2x~iphone.png', 'qualifier' => '', 'width' => '1136', 'height' => '640' ),
				array( 'src' => 'splashscreens/Default-568h-Portrait@2x~iphone.png', 'qualifier' => '', 'width' => '640', 'height' => '1136' ),
				array( 'src' => 'splashscreens/Default-Landscape@2x~iphone.png', 'qualifier' => '', 'width' => '960', 'height' => '640' ),
				array( 'src' => 'splashscreens/Default-Portrait@2x~iphone.png', 'qualifier' => '', 'width' => '640', 'height' => '960' ),
				array( 'src' => 'splashscreens/Default-Landscape~iphone.png', 'qualifier' => '', 'width' => '480', 'height' => '320' ),
				array( 'src' => 'splashscreens/Default-Portrait~iphone.png', 'qualifier' => '', 'width' => '320', 'height' => '480' ),
			),
		);

		$icons_and_splashscreens = array( 'icons' => $default_icons, 'splashscreens' => $default_splashscreens );

		/**
		 * 'wpak_default_icons_and_splashscreens' filter.
		 * Use this filter to customize icons and splashscreens file names or attributes
		 *
		 * @param $icons_and_splashscreens    array    Icon and splashscreens to modify
		 * @param $app_id                     int      App id
		 * @param $export_type                string   'phonegap-build' (default), 'phonegap-cli' or 'webapp'
		 */
		$icons_and_splashscreens = apply_filters( 'wpak_default_icons_and_splashscreens', $icons_and_splashscreens, $app_id, $export_type );

		return $icons_and_splashscreens;
	}

	protected static function get_icons_splashscreens_dir( $app_id, $app_platform, $export_type ) {

		$default_icons_splashscreens_dir = dirname( __FILE__ ) .'/../../images/icons-splashscreens';

		/**
		 * 'wpak_icons_and_splashscreens_dir' filter.
		 * Use this filter to customize icons and splashscreens files directory
		 *
		 * @param $icons_and_splashscreens    array    Icon and splashscreens to modify
		 * @param $app_id                     int      App id
		 * @param $app_platform               string   App platform
		 * @param $export_type                string   'phonegap-build' (default), 'phonegap-cli' or 'webapp'
		 */
		$default_icons_splashscreens_dir = apply_filters( 'wpak_icons_and_splashscreens_dir', $default_icons_splashscreens_dir, $app_id, $app_platform, $export_type );

		return $default_icons_splashscreens_dir;
	}

	public static function get_platform_icons_and_splashscreens_files( $app_id, $app_platform, $export_type ) {

		$app_icons_and_splashscreens_files = array( 'icons' => array(), 'splashscreens' => array() );

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );

		if ( ( $export_type !== 'pwa' && $app_main_infos['use_default_icons_and_splash'] )
			 || ( $export_type === 'pwa' && $app_main_infos['pwa_use_default_icons_and_splash'] )
			) {

			$default_icons_and_splash = self::get_default_icons_and_splashscreens( $app_id, $export_type );
			$default_icons = $default_icons_and_splash['icons'];
			$default_splashscreens = $default_icons_and_splash['splashscreens'];

			//Handle universal platform (case empty( $app_platform ) ):
			$platforms = $app_platform === '' ? array( 'ios', 'android' ) : array( $app_platform );

			if ( $export_type === 'pwa' ) {
				$platforms = array( 'pwa' );
			}
			
			$icons_splashscreens_dir = self::get_icons_splashscreens_dir( $app_id, $app_platform, $export_type );

			foreach( $platforms as $platform ) {

				if ( !empty( $default_icons[$platform] ) ) {
					foreach( $default_icons[$platform] as $icon ) {
						$file_path = $icons_splashscreens_dir .'/'. $platform .'/'. $icon['src'];
						if ( file_exists( $file_path ) ) {
							$icon['platform'] = $platform;
							$icon['full_path'] = $file_path;
							$app_icons_and_splashscreens_files['icons'][] = $icon;
						}
					}
				}

				if ( !empty( $default_splashscreens[$platform] ) ) {
					foreach( $default_splashscreens[$platform] as $splashscreen ) {
						$file_path = $icons_splashscreens_dir .'/'. $platform .'/'. $splashscreen['src'];
						if ( file_exists( $icons_splashscreens_dir .'/'. $platform .'/'. $splashscreen['src'] ) ) {
							$splashscreen['platform'] = $platform;
							$splashscreen['full_path'] = $file_path;
							$app_icons_and_splashscreens_files['splashscreens'][] = $splashscreen;
						}
					}
				}

			}

		}

		return $app_icons_and_splashscreens_files;
	}

	/**
	 * Retrieves icons and splashscreens for build export. This returns XML string to be used inside PhoneGap Build config.xml file.
	 * This shouldn't be called for a platform that doesn't need PhoneGap Build (ex: pwa).
	 *
	 * @param int       $app_id         Application ID.
	 * @param string    $app_platform   App platform (see WpakApps::get_platforms to get the list).
	 * @param string    $export_type    Export type.
	 *
	 * @return string $app_icons_and_splashscreens_files
	 */
	protected static function get_icons_and_splashscreens_xml( $app_id, $app_platform, $export_type ) {

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_icons_and_splashscreens_files = $app_main_infos['icons'];
		$app_use_default_icons_and_splashscreens = $app_main_infos['use_default_icons_and_splash'];

		if ( empty( $app_icons_and_splashscreens_files ) && $app_use_default_icons_and_splashscreens ) {

			$icons_and_splash = self::get_platform_icons_and_splashscreens_files( $app_id, $app_platform, $export_type );
			$icons = $icons_and_splash['icons'];
			$splashscreens = $icons_and_splash['splashscreens'];

			$icons_str = '';
			if ( !empty( $icons ) ) {
				foreach( $icons as $icon ) {
					$icons_str .= '<icon '
							. 'src="'. $icon['src'] .'" '
							. 'gap:platform="'. $icon['platform'] .'" '
							. ( !empty( $icon['qualifier']  ) ? 'gap:qualifier="'. $icon['qualifier'] .'" ' : '' )
							. ( !empty( $icon['width']  ) ? 'width="'. $icon['width'] .'" ' : '' )
							. ( !empty( $icon['height']  ) ? 'height="'. $icon['height'].'" ' : '' )
					.'/>'."\n";
				}
			}

			$splashscreens_str = '';
			if ( !empty( $splashscreens) ) {
				foreach( $splashscreens as $splashscreen ) {
					$splashscreens_str .= '<gap:splash '
								. 'src="'. $splashscreen['src'] .'" '
								. 'gap:platform="'. $splashscreen['platform'] .'" '
								. ( !empty( $splashscreen['qualifier']  ) ? 'gap:qualifier="'. $splashscreen['qualifier'] .'" ' : '' )
								. ( !empty( $splashscreen['width']  ) ? 'width="'. $splashscreen['width'] .'" ' : '' )
								. ( !empty( $splashscreen['height']  ) ? 'height="'. $splashscreen['height'] .'" ' : '' )
						.'/>'."\n";
				}
			}

			$app_icons_and_splashscreens_files = $icons_str ."\n". $splashscreens_str;
		}

		return $app_icons_and_splashscreens_files;
	}

	protected static function get_target_sdk_version( $app_id, $app_platform, $export_type ) {

		$default_target_sdk_version = 26;

		/**
		 * 'wpak_config_xml_target_sdk_version' filter. 
		 * Allows to set the "android-targetSdkVersion" preference. 
		 * Return an empty value to avoid forcing any targetSdkVersion value.
		 * (This filters only applies to non PWA exports).
		 * 
		 * @param int 		Value of android-targetSdkVersion preference.   
		 * @param int       $app_id         Application ID.
	 	 * @param string    $app_platform   App platform (see WpakApps::get_platforms to get the list).
	 	 * @param string    $export_type    Export type.
		 */
		return apply_filters( 'wpak_config_xml_target_sdk_version', $default_target_sdk_version, $app_id, $app_platform, $export_type );
	}

	protected static function get_custom_preferences( $app_id, $app_platform, $export_type ) {
		/**
		 * 'wpak_config_xml_custom_preferences' filter. 
		 * Allows to add custom preferences to config.xml file.
		 * (This filters only applies to non PWA exports).
		 * 
		 * @param array     custom preferences to add: array of [ 'name' => 'preferenceName', 'value' => 'preferenceValue' ]
		 * @param int       $app_id         Application ID.
	 	 * @param string    $app_platform   App platform (see WpakApps::get_platforms to get the list).
	 	 * @param string    $export_type    Export type.
		 */
		return apply_filters( 'wpak_config_xml_custom_preferences', [], $app_id, $app_platform, $export_type );
	}

	public static function get_config_xml( $app_id, $echo = false, $export_type = 'phonegap-build' ) {

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_name = $app_main_infos['name'];
		$app_description = $app_main_infos['desc'];
		$app_phonegap_id = $app_main_infos['app_phonegap_id'];
		$app_version = $app_main_infos['version'];
		$app_version_code = $app_main_infos['version_code'];
		$app_target_architecture = $app_main_infos['target_architecture'];
		$app_build_tool = $app_main_infos['build_tool'];
		$app_phonegap_version = $app_main_infos['phonegap_version'];
		$app_author = $app_main_infos['author'];
		$app_author_email = $app_main_infos['author_email'];
		$app_author_website = $app_main_infos['author_website'];
		$app_platform = $app_main_infos['platform'];
		$app_icons_splashscreens = self::get_icons_and_splashscreens_xml( $app_id, $app_platform, $export_type );

		$whitelist_settings = self::get_whitelist_settings( $app_id, $app_platform, $export_type );
		$splashscreen_settings = self::get_splashscreen_settings( $app_id, $app_platform, $export_type );

		//Target sdk version
		$target_sdk_version = self::get_target_sdk_version( $app_id, $app_platform, $export_type );

		//Custom preferences (added via hook):
		$custom_preferences = self::get_custom_preferences( $app_id, $app_platform, $export_type );

		//Merge our default Phonegap Build plugins to those set in BO :
		$app_phonegap_plugins = WpakApps::get_merged_phonegap_plugins_xml( $app_id, $export_type, $app_main_infos['phonegap_plugins'] );

		$xmlns = 'http://www.w3.org/ns/widgets';
		$xmlns_gap = 'http://phonegap.com/ns/1.0';

		if ( !$echo ) {
			ob_start();
		}

		echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<widget xmlns       = "<?php echo esc_url( $xmlns ); ?>"
        xmlns:gap   = "<?php echo esc_url( $xmlns_gap ); ?>"
        id          = "<?php echo esc_attr( $app_phonegap_id ); ?>"
        versionCode = "<?php echo esc_attr( $app_version_code ); ?>"
        version     = "<?php echo esc_attr( $app_version ); ?>" >

	<name><?php echo ent2ncr( htmlentities( $app_name, ENT_QUOTES, 'UTF-8', false ) ); ?></name>

	<description><?php echo ent2ncr( htmlentities( $app_description, ENT_QUOTES, 'UTF-8', false ) ); ?></description>

	<author href="<?php echo esc_url( $app_author_website ) ?>" email="<?php echo esc_attr( $app_author_email ) ?>"><?php echo ent2ncr( htmlentities( $app_author, ENT_QUOTES, 'UTF-8', false ) ); ?></author>

	<gap:platform name="<?php echo esc_attr( $app_platform ); ?>" />
	
	<!-- General preferences -->
<?php if( !empty( $target_sdk_version ) && $app_platform == 'android' ): ?>
	<preference name="android-targetSdkVersion" value="<?php echo esc_attr( $target_sdk_version ); ?>" />
<?php endif; ?>
<?php if( !empty( $app_target_architecture ) && $app_platform == 'android' ): ?>
	<preference name="buildArchitecture" value="<?php echo esc_attr( $app_target_architecture ); ?>" />
<?php if( WpakApps::is_crosswalk_activated( $app_id ) ): ?>
	<preference name="xwalkMultipleApk" value="true" />
<?php endif; ?>
<?php endif ?>
<?php if( !empty( $app_build_tool ) && $app_platform == 'android' ): ?>
	<preference name="android-build-tool" value="<?php echo esc_attr( $app_build_tool ); ?>" />
<?php endif ?>
<?php if( !empty( $app_phonegap_version ) ): ?>

	<preference name="phonegap-version" value="<?php echo esc_attr( $app_phonegap_version ); ?>" />
<?php endif ?>
	<preference name="permissions" value="none"/>
<?php
	/**
	* Filter to handle the  "Webview bounce effect" on devices.
	* Pass false to this filter to allow the "Webview bounce effect".
	*
	* @param boolean	true		Bounce effect is disallowed by default.
	* @param int		$app_id		Application id
	*/
	if( apply_filters('wpak_config_xml_preference_disallow_overscroll', true, $app_id ) ): ?>

	<preference name="<?php echo $app_platform == 'android' ? 'd' : 'D' ?>isallowOverscroll" value="true" />
	<preference name="webviewbounce" value="false" />
<?php endif ?>

	<!-- Custom preferences -->
<?php if( !empty( $custom_preferences ) ) : ?>
<?php foreach( $custom_preferences as $preference ): ?>
	<preference name="<?php echo esc_attr( $preference['name'] ); ?>" value="<?php echo esc_attr( $preference['value'] ); ?>" />
<?php endforeach ?>
<?php endif ?>

	<!-- PhoneGap plugin declaration -->
<?php if( !empty( $app_phonegap_plugins ) ): ?>
	<?php echo str_replace( "\n", "\n\t", $app_phonegap_plugins ) ?>

<?php endif ?>
<?php if ( !empty( $whitelist_settings ) ): ?>

	<!-- Whitelist policy  -->
<?php foreach( $whitelist_settings as $whitelist_setting => $attributes ): ?>
<?php
		if ( empty( $attributes ) || !is_array( $attributes ) ) {
			continue;
		}

		$attributes_str = '';
		foreach( $attributes as $attribute => $value ) {
			$attributes_str .= ' ' . $attribute .'="'. $value .'"';
		}
?>
	<<?php echo $whitelist_setting ?><?php echo $attributes_str ?> />
<?php endforeach ?>
<?php endif ?>
<?php if( !empty( $splashscreen_settings ) ): ?>

	<!-- SplashScreen configuration -->
<?php if( !empty( $splashscreen_settings['preferences'] ) ) : ?>
<?php foreach( $splashscreen_settings['preferences'] as $splashscreen_setting => $value ): ?>
	<preference name="<?php echo esc_attr( $splashscreen_setting ); ?>" value="<?php echo esc_attr( $value ); ?>" />
<?php endforeach ?>
<?php endif ?>
<?php if( !empty( $splashscreen_settings['gap:config-file'] ) ) : ?>
<?php foreach( $splashscreen_settings['gap:config-file'] as $splashscreen_setting => $value ): ?>
	<gap:config-file platform="<?php echo esc_attr( $app_platform ); ?>" parent="<?php echo esc_attr( $splashscreen_setting ); ?>"><<?php echo $value ?>/></gap:config-file>
<?php endforeach ?>
<?php endif ?>
<?php endif ?>

	<!-- Icons and Splashscreens declaration -->
<?php if( !empty( $app_icons_splashscreens ) ): ?>

	<?php echo str_replace( "\n", "\n\t", $app_icons_splashscreens ) ?>

<?php endif ?>
</widget>
<?php
		$content = '';
		if ( !$echo ) {
			$content = ob_get_contents();
			ob_end_clean();
		}

		return !$echo ? $content : '';
	}

}

WpakConfigFile::hooks();
