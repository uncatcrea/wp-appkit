<?php
/**
 * Handles settings used as "theme_settings" in config.js 
 * TODO : for now those settings are only handled via "wpak_theme_settings" hook but
 *        we will certainly add a way for themes to handle their settings in App edition directly.
 */
class WpakThemesConfigJsSettings {
	
	/**
	 * Retrieve theme settings used as "theme_settings" entry in config.js 
	 * 
	 * @param  int     $app_id    Id of the app to retrieve theme settings for
	 * @return array   Key => value array of settings
	 */
	public static function get_theme_settings( $app_id ) {
		$theme_settings = array();
		
		$theme_slug = WpakThemesStorage::get_current_theme( $app_id );
		
		/**
		 * Use this 'wpak_theme_settings' filter to add custom settings for your theme.
		 * Those settings are added to config.js under "theme_settings".
		 * They're available on app side with Config.theme_settings.
		 * 
		 * @param    array     $config_js_settings    Key => value array of settings
		 * @param    string    $theme_slug            Slug of the theme to retrieve settings for
		 * @param    int       $app_id                Id of the app we're retrieving theme's settings for
		 */
		$theme_settings = apply_filters( 'wpak_theme_settings', $theme_settings, $theme_slug, $app_id );
		
		return $theme_settings;
	}
	
}

