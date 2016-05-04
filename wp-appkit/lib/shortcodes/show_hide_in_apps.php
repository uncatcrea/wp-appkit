<?php
/**
 * Defines 2 shortcodes:
 * - [show_only_in_apps]content[/show_only_in_apps]
 * - [hide_from_apps notify="yes/no"]content[/hide_from_apps]
 */
class WpakShortcodeShowHideInApps {
	
	public static function hooks() {
		add_shortcode( 'show_only_in_apps', array( __CLASS__, 'shortcode_show_only_in_apps' ) );
		add_shortcode( 'hide_from_apps', array( __CLASS__, 'shortcode_hide_from_apps' ) );
	}
	
	/**
	 * Shortcode [show_only_in_apps]content[/show_only_in_apps]
	 * Will show the content only when displaying the post in apps.
	 */
	public static function shortcode_show_only_in_apps( $atts, $content = null ) {
		
		//Remove content if not retrieving content for an app:
		if( !wpak_is_app() ) {
			$content = '';
		}
		
		return $content;
	}
	
	/**
	 * Shortcode [hide_from_apps notify="yes/no"]content[/hide_from_apps]
	 * Will remove the content when displaying the post in apps.
	 * If the optional setting 'notify' is set to 'yes', content is replaced by a placeholder that can be
	 * styled to show some sort of "Content unavailable" message on app side.
	 */
	public static function shortcode_hide_from_apps( $atts, $content = null ) {
		
		$settings = shortcode_atts( array(
			'notify' => 'no',
		), $atts );
		
		if( wpak_is_app() ) {
			
			if ( $settings['notify'] === 'yes' ) {
				//The following placeholder can be customized using the 'wpak-shortcode-hide-from-apps-placeholder' filter.
				$new_content = '<div class="wpak-content-not-available"></div>';
			} else {
				$new_content = '';
			}
			
			/**
			 * Use this filter to customize the placeholder displayed when a content
			 * is stripped out from post content in app's display.
			 * 
			 * @param string $new_content Placeholder content to display in app: customize this.
			 * @param string $content     Content originally inside of the shortcode (maybe you'll want to wrap this in a custom div of yours).
			 * @param array  $settings    Shortcode settings (notify: 'yes'/'no')
			 */
			$new_content = apply_filters( 'wpak-shortcode-hide-from-apps-placeholder', $new_content, $content, $settings );
			
			$content = $new_content;
		}
		
		return $content;
	}
	
}

WpakShortcodeShowHideInApps::hooks();