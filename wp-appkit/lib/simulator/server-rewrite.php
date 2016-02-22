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
		$htaccess_file = get_home_path() . '.htaccess';
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
</IfModule>
# END WP-AppKit Rules\n\n";
			
		}
		
		return $wpak_rules;
	}

}