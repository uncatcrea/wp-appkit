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
	 * @param array				$default	Default options values.
	 * @return array						App options.
	 */
	public static function get_options( $post_id, $default = array() ) {
		$options = get_post_meta( $post_id, self::meta_id );

		if( !empty( $options ) ) {
			$options = array_shift( $options );
		}

		if( !empty( $options ) || !empty( $default ) ) {
			$options = array_merge( (array)$default, (array)$options );
		}

		return $options;
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
			$options['refresh_interval'] = abs( intval( $options['refresh_interval'] ) ); // Positive integer
		}

		/**
		 * Filter options values just before it is inserted into the database.
		 *
		 * @param array $options    An array of options values.
		 * @param int 	$post_id 	The app ID.
		 */
		$options = apply_filters( 'wpak_update_options_data', $options, $post_id );

		return update_post_meta( $post_id, self::meta_id, $options );
	}
}