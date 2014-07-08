<?php
require_once(dirname(__FILE__) .'/options-bo-settings.php');
require_once(dirname(__FILE__) .'/options-storage.php');

/**
 * Options manager class.
 */
class WpakOptions {
	/**
	 * String representations of static and dynamic options types.
	 */
	const static_type = 'static';
	const dynamic_type = 'dynamic';

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
	 * Static options keys.
	 *
	 * @access private
	 * @var array
	 */
	private static $static_options = array(
		'refresh_interval' => true,
	);

	/**
	 * Dynamic options keys.
	 *
	 * @access private
	 * @var array
	 */
	private static $dynamic_options = array();

	/**
	 * Returns options for a given app.
	 *
	 * @param	int			$app_post_id	The app ID.
	 * @param	string		$type			The options type (WpakOptions::static_type or WpakOptions::dynamic_type).
	 *
	 * @return	array		$options		The app options.
	 */
	public static function get_app_options( $app_post_id, $type = null ) {
		/**
		 * Filter the default options values.
		 *
		 * @param array $default   		Default options values.
		 * @param int   $app_post_id 	The app ID.
		 */
		$default = apply_filters( 'wpak_default_options', self::$default, $app_post_id );

		$options = WpakOptionsStorage::get_options( $app_post_id, $default );

		if( !in_array( $type, array( self::static_type, self::dynamic_type ) ) ) {
			return $options;
		}

		$filter_keys = array_keys( $options );
		switch( $type ) {
			case self::static_type:
				/**
				 * Filter the keys of static options.
				 *
				 * @param array $static_options   Default static options keys.
				 */
				$filter_keys = apply_filters( 'wpak_static_options', self::$static_options );
				break;
			case self::dynamic_type:
				/**
				 * Filter the keys of dynamic options.
				 *
				 * @param array $dynamic_options   Default dynamic options keys.
				 */
				$filter_keys = apply_filters( 'wpak_dynamic_options', self::$dynamic_options );
				break;
		}
		$options = array_intersect_key( $options, $filter_keys );

		return $options;
	}
}