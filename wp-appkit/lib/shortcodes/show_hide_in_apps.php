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
	 * Shortcode [show_only_in_apps container="p|span|div|none"]content[/show_only_in_apps]
	 * Will show the content only when displaying the post in apps.
	 * 
	 * When in app, shortcode content is displayed wrapped in a <p> tag by default, but this can be  
	 * changed to <span> (pass 'span'), <div> (pass 'div') or none (pass empty string '' or 'none') using the 'container' optional setting.
	 */
	public static function shortcode_show_only_in_apps( $atts, $content = null ) {
		
		$settings = shortcode_atts( array(
			'container' => 'p'
		), $atts );
		
		$original_content = $content;
		
		//Remove content if not retrieving content for an app:
		$is_app = wpak_is_app();
		if( !$is_app ) {
			$content = '';
		} else {
			$container = $settings['container'];
			if ( !empty( $container ) && preg_match( '#^[a-z]+$#i', $container ) && $container !== 'none' ) {
				$content = "<$container>$content</$container>";
			}
		}
		
		/**
		* Use this filter to customize "show_only_in_apps" shortcode output
		* 
		* @param string  $content             Returned shorcode HTML
		* @param boolean $is_app			  Whether we're in an app or not
		* @param string  $original_content    Content originally inside of the shortcode
		* @param array   $settings            Shortcode settings (container: 'p'|'span'|'div'|'none')
		*/
		$content = apply_filters( 'wpak-shortcode-show-only-in-apps', $content, $is_app, $original_content, $settings );
		
		return $content;
	}
	
	/**
	 * Shortcode [hide_from_apps notify="yes|no" container="p|span|div|none"]content[/hide_from_apps]
	 * 
	 * Will remove the content when displaying the post in apps.
	 * 
	 * If the optional setting 'notify' is set to 'yes', content is replaced by a placeholder that can be
	 * styled to show some sort of "Content unavailable" message on app side.
	 * This placeholder can be fully customized using the 'wpak-shortcode-hide-from-apps-placeholder' filter.
	 * 
	 * When not in app, shortcode content is displayed wrapped in a <p> tag by default, but this can be  
	 * changed to <span> (pass 'span'), <div> (pass 'div') or none (pass empty string '' or 'none') using the 'container' optional setting.
	 */
	public static function shortcode_hide_from_apps( $atts, $content = null ) {
		
		$settings = shortcode_atts( array(
			'notify' => 'no',
			'container' => 'p'
		), $atts );
		
		$original_content = $content;
		
		$is_app = wpak_is_app();
		if( $is_app ) {
			
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
			 * @param string $new_content          Placeholder content to display in app: customize this.
			 * @param string $original_content     Content originally inside of the shortcode (maybe you'll want to wrap this in a custom div of yours).
			 * @param array  $settings             Shortcode settings (notify: 'yes'|'no', container: 'p'|'span'|'div'|'none')
			 */
			$new_content = apply_filters( 'wpak-shortcode-hide-from-apps-placeholder', $new_content, $original_content, $settings );
			
			$content = $new_content;
			
		} else {
			
			$container = $settings['container'];
			if ( !empty( $container ) && preg_match( '#^[a-z]+$#i', $container ) && $container !== 'none' ) {
				$content = "<$container>$content</$container>";
			}
			
		}
		
		/**
		* Use this filter to customize "hide_from_apps" shortcode output
		* 
		* @param string  $content             Returned shorcode HTML
		* @param boolean $is_app			  Whether we're in an app or not
		* @param string  $original_content    Content originally inside of the shortcode
		* @param array   $settings            Shortcode settings (notify: 'yes'|'no', container: 'p'|'span'|'div'|'none')
		*/
		$content = apply_filters( 'wpak-shortcode-hide-from-apps', $content, $is_app, $original_content, $settings );
		
		return $content;
	}
	
}

WpakShortcodeShowHideInApps::hooks();