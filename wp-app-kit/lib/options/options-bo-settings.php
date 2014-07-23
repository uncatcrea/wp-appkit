<?php
/**
 * Options BackOffice forms manager class.
 */
class WpakOptionsBoSettings {
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
	 * Displays options meta box on backoffice form.
	 *
	 * @param WP_Post				$post			The app object.
	 * @param array					$current_box	The box settings.
	 */
	public static function inner_options_box( $post, $current_box ) {
		$options = WpakOptions::get_app_options( $post->ID );
		?>
		<div class="wpak_settings">
			<label for="wpak_app_options_refresh_interval"><?php _e( 'Refresh interval (in seconds)', WpAppKit::i18n_domain ) ?></label> : <br/>
			<input id="wpak_app_options_refresh_interval" type="text" name="wpak_app_options[refresh_interval]" value="<?php echo $options['refresh_interval'] ?>" />
			<span class="description"><?php _e( 'Use 0 to deactivate automatic refreshes. The content will only be refreshed once, at the app launch. (default value)', WpAppKit::i18n_domain ) ?></span>
			<br/><br/>
			<?php wp_nonce_field( 'wpak-options-' . $post->ID, 'wpak-nonce-options' ) ?>
		</div>
		<?php
		/**
		 * Fires when the options meta box is displayed, at the end of WpakOptionsBoSettings::inner_options_box().
		 *
		 * @see WpakOptionsBoSettings::inner_options_box()
		 *
		 * @param WP_Post 	$post 			The app object.
		 * @param array 	$current_box 	The box settings.
		 * @param array 	$options 		The app options.
		 */
		do_action( 'wpak_inner_options_box', $post, $current_box, $options );
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
			WpakOptionsStorage::update_options( $post_id, $_POST['wpak_app_options'] );
		}
	}
}

WpakOptionsBoSettings::hooks();