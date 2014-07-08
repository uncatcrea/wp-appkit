<?php
/**
 * Options manager class.
 */
class WpakOptionsStorage {
	/**
	 * Meta key used to store options.
	 */
	const meta_id = '_wpak_options';

	/**
	 * Returns options for a given app.
	 *
	 * @param int				$post_id	The app ID.
	 * @return array						App options.
	 */
	public static function get_options( $post_id ) {
		$options = (array)get_post_meta( $post_id, self::meta_id, true );

		$default = array(
			'refresh_interval' => 0,
		);

		return array_merge( $default, $options );
	}

	/**
	 * Update options for a given app.
	 *
	 * @param int				$post_id	The app ID.
	 * @param array				$options	The options to set.
	 * @return int|bool						Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public static function update_options( $post_id, $options ) {
		// Sanitization
		if( isset( $options['refresh_interval'] ) ) {
			$options['refresh_interval'] = intval( $options['refresh_interval'] ); // Positive integer
		}

		return update_post_meta( $post_id, self::meta_id, $options );
	}
}