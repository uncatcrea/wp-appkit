<?php

/**
 * Allows to create a "WP-AppKit App Editor" user role so that we can create
 * users that can only edit WP-AppKit apps and no other contents.
 * 
 * Used in WpakSettings.
 */
class WpakUserPermissions {

	const wpak_editor_role = 'wpak_app_editor';

	public static function create_wp_appkit_user_role() {

		remove_role( self::wpak_editor_role );

		$capabilities = array(
			'wpak_edit_apps' => true,
			'read' => true,
			'level_0' => true,
			'subscriber' => true,
			'edit_wpak_app' => true,
			'edit_wpak_apps' => true,
			'read_wpak_apps' => true,
			'delete_wpak_apps' => true,
			'delete_others_wpak_apps' => true,
			'delete_published_wpak_apps' => true,
			'edit_others_wpak_apps' => true,
			'publish_wpak_apps' => true,
		);
		
		$capabilities = apply_filters( 'wpak_editor_role_capabilities', $capabilities );
		
		$wpak_role = add_role(
			self::wpak_editor_role, 
			__( 'WP-AppKit App Editor', WpAppKit::i18n_domain ), 
			$capabilities
		);
		
	}
	
	public static function remove_wp_appkit_user_role() {
		remove_role( self::wpak_editor_role );
	}

}
