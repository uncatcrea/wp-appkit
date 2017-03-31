<?php

class WpakThemesBoSettings {
	const menu_item = 'wpak_bo_themes';

	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 11 );
		add_action( 'save_post', array( __CLASS__, 'save_post' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'wpak_app_theme',
			__( 'Appearance', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_main_infos_box' ),
			'wpak_apps',
			'normal',
			'default'
		);
	}

	public static function admin_menu() {
		$capability_required = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';
		add_submenu_page(
			WpakApps::menu_item,
			__( 'WP-AppKit Themes', WpAppKit::i18n_domain ),
			__( 'Themes', WpAppKit::i18n_domain ),
			$capability_required,
			self::menu_item,
			array( __CLASS__, 'themes_page' )
		);
	}

	public static function admin_enqueue_scripts() {
		global $plugin_page;

		if( $plugin_page != self::menu_item ) {
			return;
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'wpak-theme', plugins_url( 'lib/themes/themes' . $suffix . '.js', dirname( dirname( __FILE__ ) ) ), array( 'wp-backbone', 'wp-a11y' ), WpAppKit::resources_version, true );
	}

	public static function admin_body_class( $class ) {
		// Fake themes.css stylesheet to make some "theme.php" specific styles work on our page as well
		return $class . ' themes-php';
	}

	public static function themes_page() {
		require_once 'themes-page-template.php';
	}

	public static function inner_main_infos_box( $post, $current_box ) {
		$available_themes = WpakThemes::get_available_themes(true);
		$current_theme = WpakThemesStorage::get_current_theme( $post->ID );
		$main_infos = WpakApps::get_app_main_infos( $post->ID );
		?>
		<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>

		<?php if ( !empty($available_themes) ): ?>
			<label><?php _e( 'Choose theme', WpAppKit::i18n_domain ) ?> : </label>
			<select name="wpak_app_theme_choice" id="wpak_app_theme_choice">
				<?php foreach ( $available_themes as $theme_slug => $theme_data ): ?>
					<?php $selected = $theme_slug == $current_theme ? 'selected="selected"' : '' ?>
					<option value="<?php echo esc_attr( $theme_slug ) ?>" <?php echo $selected ?>><?php echo esc_html( $theme_data['Name'] ) ?> </option>
				<?php endforeach ?>
			</select>
			<p class="description"><?php echo esc_html( sprintf( __( 'Choose a theme for your app. Theme defines how your app will look and behave. Themes can be uploaded in the Upload Themes panel. Themes are stored in the %s folder of your WordPress install.', WpAppKit::i18n_domain ), basename(WP_CONTENT_DIR) .'/'. WpakThemes::themes_directory ) ) ?></p>
		<?php else: ?>
			<div class="wpak_no_theme">
				<strong><?php _e( 'No WP-AppKit theme found!', WpAppKit::i18n_domain ) ?></strong>
				<br/>
				<?php echo sprintf( __('Please upload a WP-AppKit theme from the "<a href="%s" >Upload Themes</a>" panel or copy a theme directly to the %s directory.', WpAppKit::i18n_domain ),
									admin_url('admin.php?page=wpak_bo_upload_themes'),
									basename(WP_CONTENT_DIR) .'/'. WpakThemes::themes_directory
							)
				?>
			</div>
		<?php endif ?>

		<?php foreach ( $available_themes as $theme => $theme_data ): ?>
			<div class="wpak-theme-data" id="wpak-theme-data-<?php echo esc_attr( $theme ) ?>" style="display:none">
				<div class="theme-data-content">
					<?php echo esc_html( $theme_data['Description'] ) ?>

					<?php
						$theme_meta = array();
						if ( !empty( $theme_data['Version'] ) ) {
							$theme_meta[] = sprintf( __( 'Version %s' ), $theme_data['Version'] );
						}
						if ( !empty( $theme_data['Author'] ) ) {
							$author = $theme_data['Author'];
							if ( !empty( $theme_data['AuthorURI'] ) ) {
								$author = '<a href="' . $theme_data['AuthorURI'] . '">' . $theme_data['Author'] . '</a>';
							}
							$theme_meta[] = sprintf( __( 'By %s' ), $author );
						}
						if ( ! empty( $theme_data['ThemeURI'] ) ) {
							$theme_meta[] = sprintf( '<a href="%s">%s</a>',
								esc_url( $theme_data['ThemeURI'] ),
								__( 'Visit theme site' )
							);
						}
						if ( ! empty( $theme_data['WpakVersionRequired'] ) ) {
							$theme_meta[] = sprintf( __( 'Requires WP-AppKit version %s', WpAppKit::i18n_domain ), $theme_data['WpakVersionRequired'] );
						}
					?>

					<?php if( !empty($theme_meta) ): ?>
						<div class="theme-meta-data"><?php echo implode(' | ',$theme_meta) ?></div>
					<?php endif ?>
				</div>
			</div>
		<?php endforeach ?>

		<div class="wpak-app-title wpak_settings">
			<label><?php _e( 'Application Title (displayed in app top bar)', WpAppKit::i18n_domain ) ?></label><br/>
			<input id="wpak_app_title" type="text" name="wpak_app_title" value="<?php echo esc_attr( $main_infos['title'] ) ?>" />
		</div>

		<?php wp_nonce_field( 'wpak-theme-data-' . $post->ID, 'wpak-nonce-theme-data' ) ?>

		<?php // TODO: put this style in an external file ?>
		<style>
			.wpak-theme-data{ padding:9px 12px; margin-bottom: 10px }
			.theme-data-content{ margin-top: 0 }
			.wpak-app-title{ margin-top: 15px; border-top: 1px solid #ddd; padding-top:10px }
			.theme-meta-data{ margin-top: 7px }
			.wpak_no_theme{ text-align: center; font-size:120%; line-height: 2em; margin:30px }
		</style>

		<script>
			(function(){
				var $ = jQuery;
				$('#wpak_app_theme_choice').change(function(){
					$('.wpak-theme-data').hide();
					var theme = this.value;
					$('#wpak-theme-data-'+ theme).show();
				});
				$('#wpak_app_theme_choice').change();
			})();
		</script>

		<?php
		do_action( 'wpak_inner_main_infos_box', $post, $current_box );
	}

	public static function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( empty( $_POST['post_type'] ) || $_POST['post_type'] != 'wpak_apps' ) {
			return;
		}

		if ( !current_user_can( 'edit_post', $post_id ) && !current_user_can( 'wpak_edit_apps', $post_id ) ) {
			return;
		}

		if ( !check_admin_referer( 'wpak-theme-data-' . $post_id, 'wpak-nonce-theme-data' ) ) {
			return;
		}

		if ( isset( $_POST['wpak_app_title'] ) ) {
			update_post_meta( $post_id, '_wpak_app_title', sanitize_text_field( $_POST['wpak_app_title'] ) );
		}

		if ( isset( $_POST['wpak_app_theme_choice'] ) ) {
			WpakThemesStorage::set_current_theme( $post_id, $_POST['wpak_app_theme_choice'] );
		}
	}

}

WpakThemesBoSettings::hooks();
