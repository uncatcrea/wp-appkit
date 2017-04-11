<?php

/**
 * Handle htaccess specific rules required to preview Apps on WP Networks
 */
class WpakServerRewrite {

	/**
	 * Prepend WPAK rules needed for Apps preview on WP Networks to htaccess file
	 */ 
	public static function prepend_wp_network_wpak_rules_to_htaccess() {
		global $is_apache;
		
		//Only support auto rules insertion for Apache for now:
		if ( !$is_apache ) {
			return;
		}
		
		//Add WPAK rules to htaccess if writable:
		$htaccess_file = self::get_htaccess_file();
		if ( is_writable( $htaccess_file ) ) {
			
			self::delete_wp_network_wpak_rules_from_htaccess();
			
			$current_file_content = file_get_contents( $htaccess_file );
			file_put_contents( $htaccess_file, self::get_wpak_rules() . $current_file_content );
		}
	}
	
	/**
	 * Remove WPAK rules from htaccess file
	 */
	public static function delete_wp_network_wpak_rules_from_htaccess() {
		global $is_apache;
		
		//Only support auto rules insertion for Apache for now:
		if ( !$is_apache ) {
			return;
		}
		
		//Remove WPAK rules from htaccess if writable:
		$htaccess_file = self::get_htaccess_file();
		if ( is_writable( $htaccess_file ) ) {
			$file_content = file_get_contents( $htaccess_file );
			if ( preg_match( '/\R*\# BEGIN WP-AppKit Rules.*END WP-AppKit Rules\R*/is', $file_content, $matches ) ) {
				$file_content = str_replace( $matches[0], '', $file_content );
				file_put_contents( $htaccess_file, $file_content );
			}
		}
	}
	
	/**
	 * Get htaccess file path
	 */
	protected static function get_htaccess_file() {
		
		//Inspired from WordPress network_step2() to fix some issues on
		//get_home_path() on some WordPress Networks configs:
		$slashed_home = trailingslashit( get_option( 'home' ) );
		$base = parse_url( $slashed_home, PHP_URL_PATH );
		$document_root_fix = str_replace( '\\', '/', realpath( $_SERVER['DOCUMENT_ROOT'] ) );
		$abspath_fix = str_replace( '\\', '/', ABSPATH );
		$home_path = 0 === strpos( $abspath_fix, $document_root_fix ) ? $document_root_fix . $base : get_home_path();
		
		$htaccess_file = $home_path . '.htaccess';
		
		return $htaccess_file;
	}

	/**
	 * Get rules to add to htaccess depending on subdomain or subdirectory Network config
	 */
	protected static function get_wpak_rules() {
		
		$wpak_rules = '';
		
		if( is_multisite() ) {
			
			$prefix = !is_subdomain_install() ? "([_0-9a-zA-Z-]+/)?" : "";
			
			$wpak_rules = "\n# BEGIN WP-AppKit Rules
# Allow App preview on WP Network:
<IfModule mod_rewrite.c>
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]
RewriteRule ^$prefix.*/wp-appkit/app/config.(js|xml) index.php [L]
RewriteRule ^$prefix.*/wp-appkit/app/themes/.* index.php [L]
RewriteRule ^$prefix.*/wp-appkit/app/addons/.* index.php [L]
RewriteRule ^$prefix.*/wp-appkit/app/vendor/jquery.js /". WPINC ."/js/jquery/jquery.js [L]
RewriteRule ^$prefix.*/wp-appkit/app/vendor/underscore.js /". WPINC ."/js/underscore.min.js [L]
RewriteRule ^$prefix.*/wp-appkit/app/vendor/backbone.js /". WPINC ."/js/backbone.min.js [L]
</IfModule>
# END WP-AppKit Rules\n\n";
			
		}
		
		return $wpak_rules;
	}

}