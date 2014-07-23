<?php
require_once(dirname(__FILE__) .'/options-bo-settings.php');
require_once(dirname(__FILE__) .'/options-storage.php');

/**
 * Options manager class.
 */
class WpakOptions {
	/**
	 * Default options values.
	 *
	 * @access private
	 * @var array
	 */
	private static $default = array(
		'refresh_interval' => 0,
	);

	/**
	 * Returns options for a given app.
	 *
	 * @param	int			$app_post_id	The app ID.
	 *
	 * @return	array		$options		The app options.
	 */
	public static function get_app_options( $app_post_id ) {
		/**
		 * Filter the default options values.
		 *
		 * @param array $default   		Default options values.
		 * @param int   $app_post_id 	The app ID.
		 */
		$default = apply_filters( 'wpak_default_options', self::$default, $app_post_id );

		$options = WpakOptionsStorage::get_options( $app_post_id, $default );

		return $options;
	}
}