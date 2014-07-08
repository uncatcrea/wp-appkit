<?php
/**
 * Options manager class.
 */
class WpakOptions {
	/**
	 * Main entry point.
	 *
	 * Adds needed callbacks to some hooks.
	 */
	public static function hooks() {
		if( is_admin() ){
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
			add_action( 'save_post', array( __CLASS__, 'save_post' ) );
		}
	}

	/**
	 * Attached to 'add_meta_boxes' hook.
	 *
	 * Adds meta boxes to backoffice forms.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'wpak_options',
			__( 'Options', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_options_box' ),
			'wpak_apps',
			'side',
			'default'
		);
	}

	/**
	 * Returns options for a given app.
	 *
	 * @param int				$post_id	The app ID.
	 * @return array						App options.
	 */
	public static function get( $post_id ) {
		$options = (array)get_post_meta( $post_id, '_wpak_app_options', true );

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
	public static function set( $post_id, $options ) {
		// Sanitization
		if( isset( $options['refresh_interval'] ) ) {
			$options['refresh_interval'] = intval( $options['refresh_interval'] ); // Positive integer
		}

		return update_post_meta( $post_id, '_wpak_app_options', $options );
	}

	/**
	 * Displays options meta box on backoffice form.
	 *
	 * @param WP_Post				$post			The app object.
	 * @param array					$current_box	The box settings.
	 */
	public static function inner_options_box( $post, $current_box ) {
		$options = self::get( $post->ID );
		?>
		<div class="wpak_settings">
			<label for="wpak_app_options_refresh_interval"><?php _e( 'Refresh interval (in seconds)', WpAppKit::i18n_domain ) ?></label> : <br/>
			<input id="wpak_app_options_refresh_interval" type="text" name="wpak_app_options[refresh_interval]" value="<?php echo $options['refresh_interval'] ?>" />
			<span class="description"><?php _e( 'Use 0 to deactivate automatic refreshes. The content will only be refreshed once, at the app launch. (default value)', WpAppKit::i18n_domain ) ?></span>
			<br/><br/>
			<?php wp_nonce_field( 'wpak-options-' . $post->ID, 'wpak-nonce-options' ) ?>
		</div>
		<?php
	}

	/**
	 * Attached to 'save_post' hook.
	 *
	 * Handles options saving for a given app.
	 *
	 * @param int				$post_id			The app ID.
	 */
	public static function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if( empty( $_POST['post_type'] ) || $_POST['post_type'] != 'wpak_apps' ) {
			return;
		}

		if( !current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if( !check_admin_referer( 'wpak-options-' . $post_id, 'wpak-nonce-options' ) ) {
			return;
		}

		if( !empty( $_POST['wpak_app_options'] ) ) {
			self::set( $post_id, $_POST['wpak_app_options'] );
		}
	}
}

WpakOptions::hooks();