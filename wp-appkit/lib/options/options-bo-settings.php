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
			add_action( 'wpak_inner_synchronization_box', array( __CLASS__, 'inner_synchronization_options_box' ), 10, 2 );
			add_action( 'save_post', array( __CLASS__, 'save_post' ) );
		}
	}

	/**
	 * Displays options meta box on backoffice form.
	 *
	 * @param WP_Post				$post			The app object.
	 * @param array					$current_box	The box settings.
	 */
	public static function inner_synchronization_options_box( $post, $current_box ) {
		$options = WpakOptions::get_app_options( $post->ID );
		?>
		<div class="wpak_settings field-group">
			<label for="wpak_app_options_refresh_interval"><?php _e( 'Refresh Interval (in seconds)', WpAppKit::i18n_domain ) ?></label>
			<input id="wpak_app_options_refresh_interval" type="text" name="wpak_app_options[refresh_interval]" value="<?php echo esc_attr( $options['refresh_interval'] ) ?>" />
			<span class="description"><?php _e( 'Interval in seconds between attempts to refresh app\'s content. 0 means that content will be refreshed at each app\'s launch.', WpAppKit::i18n_domain ) ?></span>
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

		if( !current_user_can( 'edit_post', $post_id ) && !current_user_can( 'wpak_edit_apps', $post_id ) ) {
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