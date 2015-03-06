<?php

class WpakConfigFile {

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 1 );
	}

	public static function rewrite_rules() {
		add_rewrite_tag( '%wpak_appli_file%', '([^&]+)' );
		$wp_content = str_replace( ABSPATH, '', WP_CONTENT_DIR );
		$url_to_config_file = plugins_url( 'app', dirname( dirname( __FILE__ ) ) );
		$wp_content_pos = strpos( $url_to_config_file, $wp_content );
		if ( $wp_content_pos !== false ) {
			$config_file_prefix = substr( $url_to_config_file, $wp_content_pos ); //Something like "wp-content/plugins/wp-app-kit/app"
			add_rewrite_rule( '^' . $config_file_prefix . '/(config\.js)$', 'index.php?wpak_appli_file=$matches[1]', 'top' );
			add_rewrite_rule( '^' . $config_file_prefix . '/(config\.xml)$', 'index.php?wpak_appli_file=$matches[1]', 'top' );
		}
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

					$file = $wp_query->query_vars['wpak_appli_file'];

					switch ( $file ) {
						case 'config.js':
							header( "Content-type: text/javascript;  charset=utf-8" );
							echo "/* Wp App Kit simulator config.js */\n";
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
				_e( 'App id not found in _GET parmeters', WpAppKit::i18n_domain );
				exit();
			}
		}
	}

	public static function get_config_js( $app_id, $echo = false ) {
		$wp_ws_url = WpakWebServices::get_app_web_service_base_url( $app_id );
		$theme = WpakThemesStorage::get_current_theme( $app_id );

		$app_slug = WpakApps::get_app_slug( $app_id );

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_title = $app_main_infos['title'];
		
		$app_version = WpakApps::sanitize_app_version( $app_main_infos['version'] );

		$debug_mode = WpakBuild::get_app_debug_mode( $app_id );

		$auth_key = WpakApps::get_app_is_secured( $app_id ) ? WpakToken::get_hash_key() : '';
		//TODO : options to choose if the auth key is displayed in config.js.

		$options = WpakOptions::get_app_options( $app_id );
		
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
		theme : '<?php echo addslashes($theme) ?>',
		version : '<?php echo $app_version ?>',
		app_title : '<?php echo addslashes($app_title) ?>',
		debug_mode : '<?php echo $debug_mode ?>'<?php
		if( !empty( $auth_key ) ):
		?>,
		auth_key : '<?php echo $auth_key ?>'<?php
		endif
		?>,
		options : <?php echo json_encode( $options ); ?>,
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

	public static function get_config_xml( $app_id, $echo = false ) {
		
		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		$app_name = $app_main_infos['name'];
		$app_description = $app_main_infos['desc'];
		$app_phonegap_id = $app_main_infos['app_phonegap_id'];
		$app_version = $app_main_infos['version'];
		$app_version_code = $app_main_infos['version_code'];
		$app_phonegap_version = $app_main_infos['phonegap_version'];
		$app_author = $app_main_infos['author'];
		$app_author_email = $app_main_infos['author_email'];
		$app_author_website = $app_main_infos['author_website'];
		$app_platform = $app_main_infos['platform'];
		$app_icons = $app_main_infos['icons'];
		
		//Merge our default Phonegap Build plugins to those set in BO :
		$app_phonegap_plugins = WpakApps::get_merged_phonegap_plugins_xml($app_id, $app_main_infos['phonegap_plugins']);

		$xmlns = 'http://www.w3.org/ns/widgets';
		$xmlns_gap = 'http://phonegap.com/ns/1.0';

		if ( !$echo ) {
			ob_start();
		}

		echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<widget xmlns       = "<?php echo $xmlns ?>"
        xmlns:gap   = "<?php echo $xmlns_gap ?>"
        id          = "<?php echo $app_phonegap_id ?>"
        versionCode = "<?php echo $app_version_code ?>"
        version     = "<?php echo $app_version ?>" >

	<name><?php echo $app_name ?></name>

	<description><?php echo $app_description ?></description>

	<author href="<?php echo $app_author_website ?>" email="<?php echo $app_author_email ?>"><?php echo $app_author ?></author>

	<gap:platform name="<?php echo $app_platform ?>" />
<?php if( !empty( $app_phonegap_version ) ): ?>
	
	<preference name="phonegap-version" value="<?php echo $app_phonegap_version ?>" />
<?php endif ?>
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
	
	<!-- Add Icon, Splash screen and any PhoneGap plugin declaration here -->
<?php if( !empty( $app_icons ) ): ?>

	<?php echo str_replace( "\n", "\n\t", $app_icons ) ?>

<?php endif ?>
<?php if( !empty( $app_phonegap_plugins ) ): ?>

	<?php echo str_replace( "\n", "\n\t", $app_phonegap_plugins ) ?>

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
