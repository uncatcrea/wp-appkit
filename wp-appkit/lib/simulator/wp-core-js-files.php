<?php

/**
 * To follow directives from WordPress.org, even the app part of the plugin 
 * must use WordPress core's Javascript libraries when available. 
 * So we implement here the rewrite rules that allow to redirect 
 * app/vendor/[jquery,underscore,backbone].js files to corresponding files 
 * in core (in wp-includes/js).
 */
class WpakWpCoreJsFiles {

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
	}
	
	public static function rewrite_rules() {
		$home_url = home_url(); //Something like "http://my-site.com"
		$url_to_vendor_files = plugins_url( 'app/vendor', dirname( dirname( __FILE__ ) ) ); //Something like "http://my-site.com/wp-content/plugins/wp-appkit/app/vendor"
		$vendor_file_prefix = str_replace( trailingslashit($home_url), '', $url_to_vendor_files ); //Something like "wp-content/plugins/wp-appkit/app/vendor"
		
		add_rewrite_rule( '^' . $vendor_file_prefix . '/jquery.js$', WPINC .'/js/jquery/jquery.js', 'top' );
		add_rewrite_rule( '^' . $vendor_file_prefix . '/underscore.js$', WPINC .'/js/underscore.min.js', 'top' );
		add_rewrite_rule( '^' . $vendor_file_prefix . '/backbone.js$', WPINC .'/js/backbone.min.js', 'top' );
	}
	
}

WpakWpCoreJsFiles::hooks();