<?php
require_once(dirname(__FILE__) .'/options-bo-settings.php');
require_once(dirname(__FILE__) .'/options-storage.php');

/**
 * Options manager class.
 */
class WpakOptions {
	/**
	 * Returns options for a given app.
	 *
	 * @param	int		$app_post_id	The app ID.
	 *
	 * @return	array					The app options.
	 */
	public static function get_app_options( $app_post_id ) {
		return WpakOptionsStorage::get_options( $app_post_id );
	}
}