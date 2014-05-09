<?php
class WpakThemesBoSettings{
	
	public static function hooks(){
		add_action('add_meta_boxes', array(__CLASS__,'add_meta_boxes'));
		add_action('save_post', array(__CLASS__,'save_post'));
	}
	
	public static function add_meta_boxes(){
		add_meta_box(
			'wpak_app_theme',
			__('Theme',WpAppKit::i18n_domain),
			array(__CLASS__,'inner_main_infos_box'),
			'wpak_apps',
			'normal',
			'default'
		);
	}
	
	public static function inner_main_infos_box($post,$current_box){
		$available_themes = WpakThemes::get_available_themes();
		$current_theme = WpakThemesStorage::get_current_theme($post->ID);
		?>
		
		<label><?php _e('Choose theme',WpAppKit::i18n_domain) ?> : </label>
		<select name="wpak_app_theme_choice">
			<?php foreach($available_themes as $theme): ?>
				<?php $selected = $theme == $current_theme ? 'selected="selected"' : '' ?>
				<option value="<?php echo $theme ?>" <?php echo $selected ?>><?php echo ucfirst($theme)?> </option>
			<?php endforeach ?>
		</select>
		
		<?php wp_nonce_field('wpak-theme-data-'. $post->ID,'wpak-nonce-theme-data') ?>
		
		<?php
	}
	
	public static function save_post($post_id){
	
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}
	
		if( empty($_POST['post_type']) || $_POST['post_type'] != 'wpak_apps' ){
			return;
		}
	
		if( !current_user_can('edit_post', $post_id) ){
			return;
		}
	
		if( !check_admin_referer('wpak-theme-data-'. $post_id, 'wpak-nonce-theme-data') ){
			return;
		}
		
		if ( isset( $_POST['wpak_app_theme_choice'] ) ) {
			WpakThemesStorage::set_current_theme($post_id,$_POST['wpak_app_theme_choice']);
		}
		
	}
}

WpakThemesBoSettings::hooks();