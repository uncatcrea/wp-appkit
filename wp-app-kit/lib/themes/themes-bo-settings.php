<?php

class WpakThemesBoSettings {

	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_post' ) );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'wpak_app_theme', 
			__( 'Theme', WpAppKit::i18n_domain ), 
			array( __CLASS__, 'inner_main_infos_box' ), 
			'wpak_apps', 
			'normal', 
			'default'
		);
	}

	public static function inner_main_infos_box( $post, $current_box ) {
		$available_themes = WpakThemes::get_available_themes(true);
		$current_theme = WpakThemesStorage::get_current_theme( $post->ID );
		$main_infos = WpakApps::get_app_main_infos( $post->ID );
		?>

		<label><?php _e( 'Choose theme', WpAppKit::i18n_domain ) ?> : </label>
		<select name="wpak_app_theme_choice" id="wpak_app_theme_choice">
			<?php foreach ( $available_themes as $theme_slug => $theme_data ): ?>
				<?php $selected = $theme_slug == $current_theme ? 'selected="selected"' : '' ?>
				<option value="<?php echo $theme_slug ?>" <?php echo $selected ?>><?php echo $theme_data['Name'] ?> </option>
			<?php endforeach ?>
		</select>
		
		<?php foreach ( $available_themes as $theme => $theme_data ): ?>
			<div class="wpak-theme-data" id="wpak-theme-data-<?php echo $theme ?>" style="display:none">
				<div class="theme-data-content">
					<?php echo $theme_data['Description'] ?>
					
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
					?>
					
					<?php if( !empty($theme_meta) ): ?>
						<div class="theme-meta-data"><?php echo implode(' | ',$theme_meta) ?></div>
					<?php endif ?>
				</div>
			</div>
		<?php endforeach ?>
		
		<div class="wpak-app-title">
			<label><?php _e( 'Application title (displayed in app top bar)', WpAppKit::i18n_domain ) ?></label> : <br/> 
			<input type="text" name="wpak_app_title" value="<?php echo $main_infos['title'] ?>" />
		</div>
		
		<?php wp_nonce_field( 'wpak-theme-data-' . $post->ID, 'wpak-nonce-theme-data' ) ?>

		<style>
			.wpak-theme-data{ padding:9px 12px; margin-bottom: 10px }
			.theme-data-content{ margin-top: 0 }
			.wpak-app-title{ margin-top: 15px; border-top: 1px solid #ddd; padding-top:10px }
			.theme-meta-data{ margin-top: 7px }
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
