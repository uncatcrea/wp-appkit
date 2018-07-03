<?php

class WpakNavigationBoSettings{

	const menu_item = 'wpak_navigation_bo_settings';

	public static function hooks(){
		if( is_admin() ){
			add_action( 'wpak_inner_main_infos_box', array(__CLASS__,'inner_navigation_box'), 10, 2 );
			add_action('admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'));
			add_action('wp_ajax_wpak_edit_navigation', array(__CLASS__,'ajax_wpak_edit_navigation'));
			add_action('wp_ajax_wpak_update_available_components', array(__CLASS__,'ajax_wpak_update_available_components'));
		}
	}

	public static function admin_enqueue_scripts(){
		global $pagenow, $typenow;
		if( ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'wpak_apps' ){
			global $post;
			wp_enqueue_script('wpak_navigation_bo_settings_js',plugins_url('lib/navigation/navigation-bo-settings.js', dirname(dirname(__FILE__))),array('jquery','jquery-ui-sortable'),WpAppKit::resources_version);
			wp_localize_script('wpak_navigation_bo_settings_js', 'wpak_navigation', array(
				'post_id'=>$post->ID,
				'nonce'=>wp_create_nonce('wpak-navigation-data-'. $post->ID),
				'display_modif_alerts' => WpakSettings::get_setting( 'activate_wp_appkit_app_modif_alerts' ),
				'messages'=>array(
					'confirm_delete' => __('Deleting a navigation item will remove it from all existing instances of your app (even those already built and running on real phones). Are you sure you want to remove this item from your app navigation?',WpAppKit::i18n_domain),
					'confirm_edit' => __('Modifying a navigation item will affect it on all existing instances of your app (even those already built and running on real phones). Are you sure you want to modify this navigation item?',WpAppKit::i18n_domain),
					'confirm_add' => __('Adding a navigation item will add it on all existing instances of your app (even those already built and running on real phones). Are you sure you want to add this navigation item?',WpAppKit::i18n_domain),
				 )
			));
		}
	}

	public static function inner_navigation_box($post,$current_box){
		$nav_items = WpakNavigationItemsStorage::get_navigation_items($post->ID);
		?>
		<div id="navigation-wrapper">
			<p>Menu</p>
			<p class="description"><?php _e( 'Click Add Component to Menu to reference a component in the app\'s menu.', WpAppKit::i18n_domain ) ?></p>
			<a href="#" class="add-new-h2" id="add-new-item"><?php _e('Add Component to Menu',WpAppKit::i18n_domain) ?></a>

			<div id="navigation-feedback" style="display:none"></div>

			<div id="new-item-form" style="display:none">
				<h4><?php _e('New Menu Item',WpAppKit::i18n_domain) ?></h4>
				<div id="components-available-for-navigation"><?php self::echo_item_form($post->ID) ?></div>
			</div>

			<table class="wp-list-table widefat fixed" id="navigation-items-table" data-post-id="<?php echo $post->ID ?>">
				<thead>
					<tr>
						<th><?php _e('Menu Item',WpAppKit::i18n_domain) ?></th>
						<th><?php /* Icon slug deactivated for now _e('Icon Slug',WpAppKit::i18n_domain) */ ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if( !empty($nav_items) ): ?>
					<?php $i = 0 ?>
					<?php foreach($nav_items as $nav_item_id => $nav_item): ?>
						<?php echo self::get_navigation_row($post->ID,$i++,$nav_item_id,$nav_item) ?>
					<?php endforeach ?>
				<?php else: ?>
					<tr class="no-component-yet"><td><?php _e('No menu item yet!',WpAppKit::i18n_domain) ?></td></tr>
				<?php endif ?>
				</tbody>
			</table>

			<style>
				#navigation-wrapper { margin-top:1em }
				#navigation-items-table{ margin:5px 0 }
				#new-item-form{ margin-bottom:4em }
				.ui-sortable-placeholder{ height: 60px; border-bottom:1px solid #dfdfdf; }
				.ui-sortable-helper{ width:100%; background:#fff; }
				.ui-state-default{ cursor:move }
				#navigation-wrapper #components-feedback{ padding:1em; margin:5px }
				#navigation-feedback { margin-top:15px; margin-bottom:17px; padding-top:12px; padding-bottom:12px; }
			</style>
		</div>
		<?php
	}

	private static function get_navigation_row($post_id,$i,$nav_item_id,$nav_item){

		$error_class = '';
		$alternate_class = $i%2 ? '' : 'alternate';

		$component = WpakComponentsStorage::get_component($post_id,$nav_item->component_id);

		//
		// Component type could be unknown if an addon's component has been added to the app and the addon isn't activated anymore
		// An addon could be seen as deactivated either if the corresponding plugin is deactivated, or if the corresponding checkbox is unchecked for the given app
		//

		if( !WpakComponentsTypes::component_type_exists( $component->type ) ) {
			$error_class =  ' error';
			$error_message = __( 'Component type doesn\'t exist, this component won\'t be included into the app', WpAppKit::i18n_domain );
		}

		ob_start();

		?>
		<?php if( !empty($component) ): ?>
			<tr class="ui-state-default <?php echo $alternate_class . $error_class  ?> navigation-item navigation-item-component-<?php echo esc_attr( $nav_item->component_id ); ?>" data-id="<?php echo esc_attr( $nav_item_id ) ?>" id="navigation-item-row-<?php echo esc_attr( $nav_item_id ) ?>">
				<td>
					<span class="label"><?php echo esc_html( $component->label ) ?></span> (<span class="slug"><?php echo esc_html( $component->slug ) ?></span>)
					<input type="hidden" id="position-<?php echo esc_attr( $nav_item_id ) ?>" name="positions[<?php echo esc_attr( $nav_item_id ) ?>]" value="<?php echo esc_attr( $nav_item->position ) ?>" />
					<div class="row-actions">
						<span class="trash"><a class="submitdelete delete_navigation_item" href="#" data-post-id="<?php echo esc_attr( $post_id ) ?>" data-id="<?php echo esc_attr( $nav_item_id ) ?>"><?php _e('Delete',WpAppKit::i18n_domain)?></a></span>
					</div>
				</td>
				<td>

					<?php if ( empty( $error_message ) ): ?>

						<?php /* Icon slug deactivated for now

						<?php $icon_value = $nav_item->options['icon_slug'] ?>

						<div id="nav-item-value-<?php echo esc_attr( $nav_item_id ) ?>">
							<span id="span-<?php echo esc_attr( $nav_item_id ) ?>"><?php echo !empty($icon_value) ? esc_html( $icon_value ) : __('none', WpAppKit::i18n_domain) ?></span>
							<div class="row-actions">
								<a href="#" class="change-icon-slug" data-id="<?php echo esc_attr( $nav_item_id ) ?>"><?php _e('Edit icon', WpAppKit::i18n_domain) ?></a>
							</div>
						</div>


						<div id="nav-item-input-<?php echo esc_attr( $nav_item_id ) ?>" style="display:none" >
							<input type="text" id="icon-<?php echo esc_attr( $nav_item_id ) ?>" class="menu-item-icon-input" data-id="<?php echo esc_attr( $nav_item_id ) ?>" value="<?php echo esc_attr( $nav_item->options['icon_slug'] ) ?>"/>
							&nbsp;<a href="#" id="change-icon-slug-ok-<?php echo esc_attr( $nav_item_id ) ?>" class="change-icon-slug-ok" data-id="<?php echo esc_attr( $nav_item_id ) ?>" data-post-id="<?php echo esc_attr( $post_id ) ?>" ><?php _e('Ok', WpAppKit::i18n_domain) ?></a>
							&nbsp;&nbsp;<a href="#" class="change-icon-slug-cancel" data-id="<?php echo esc_attr( $nav_item_id ) ?>"><?php _e('Cancel', WpAppKit::i18n_domain) ?></a>
						</div>

						*/ ?>

					<?php else: ?>

						<div id="nav-item-value-<?php echo esc_attr( $nav_item_id ) ?>">
							<span id="span-<?php echo esc_attr( $nav_item_id ) ?>"><?php echo $error_message ?></span>
						</div>

					<?php endif ?>
				</td>
			</tr>
		<?php endif ?>
		<?php
		$navigation_row_html = ob_get_contents();
		ob_end_clean();
		return $navigation_row_html;
	}

	private static function echo_item_form($post_id){

		$components = WpakComponentsStorage::get_components($post_id);

		$no_components = empty($components);

		foreach(array_keys($components) as $component_id){
			if( WpakNavigationItemsStorage::component_in_navigation($post_id,$component_id) ){
				unset($components[$component_id]);
			}
		}

		$navigation_item_id = 0;
		if( !$no_components ){
			$navigation_item = new WpakNavigationItem(0,0);
		}

		?>
		<?php if( !$no_components ): ?>
			<div id="navigation-item-form-<?php echo esc_attr( $navigation_item_id ) ?>" class="navigation-item-form">
				<table class="form-table">
					<tr valign="top">
						<?php if( !empty($components) ): ?>
							<th scope="row"><?php _e('Component',WpAppKit::i18n_domain) ?></th>
					        <td>
					        	<select name="component_id">
					        		<?php foreach($components as $component_id => $component): ?>
					        			<option value="<?php echo esc_attr( $component_id ) ?>"><?php echo esc_html( $component->label ) ?></option>
					        		<?php endforeach ?>
					        	</select>
					        </td>
					     <?php else: ?>
					     	<td>
				        		<?php _e('All components are already in navigation!',WpAppKit::i18n_domain) ?>
				        	</td>
				        <?php endif ?>
				    </tr>
				</table>
				<input type="hidden" name="position" value="0" />
				<input type="hidden" name="navigation_post_id" value="<?php echo esc_attr( $post_id ) ?>" />
				<p class="submit">
					<span class="spinner"></span>
					<a class="button-secondary alignleft cancel" title="<?php _e('Cancel',WpAppKit::i18n_domain) ?>" href="#" id="cancel-new-item"><?php _e('Cancel',WpAppKit::i18n_domain) ?></a>&nbsp;
					<?php if( !empty($components) ): ?>
						<a class="button button-primary navigation-form-submit" data-id="<?php echo esc_attr( $navigation_item_id ) ?>"><?php _e('Add component to navigation',WpAppKit::i18n_domain) ?></a>
					<?php endif ?>
				</p>
			</div>
		<?php else: ?>
			<div>
				<?php _e('No component found!',WpAppKit::i18n_domain) ?> : <?php _e('Please create a component!',WpAppKit::i18n_domain) ?>
			</div>
		<?php endif ?>
		<?php
	}

	public static function ajax_wpak_edit_navigation(){

		$answer = array('ok' => 0, 'message' => '', 'type' => 'error', 'html' => '', 'data' => '');

		if( empty($_POST['post_id'])
				|| !is_numeric($_POST['post_id'])
				|| empty($_POST['nonce'])
				|| !check_admin_referer('wpak-navigation-data-'. $_POST['post_id'],'nonce') ){
			exit();
		}

		$post_id = intval( $_POST['post_id'] );
		$action = sanitize_key( $_POST['wpak_action'] );
		$data = is_array( $_POST['data'] ) ? $_POST['data'] : array(); //Each data value is sanitized hereunder before being used

		WpakAddons::require_app_addons_php_files( $post_id );

		if( $action == 'add_or_update' ){

			$post_id = intval( $data['navigation_post_id'] );

			$nav_item_component_id = intval( $data['component_id'] );

			$new_item_position = WpakNavigationItemsStorage::get_nb_navigation_items($post_id) + 1;
			$nav_item_position = !empty($data['position']) && is_numeric($data['position']) ? intval( $data['position'] ) : $new_item_position;

			if( empty($nav_item_component_id) ){
				$answer['message'] = __('You must choose a component!',WpAppKit::i18n_domain);
				self::exit_sending_json($answer);
			}

			if( !WpakComponentsStorage::component_exists($post_id,$nav_item_component_id) ){
				$answer['message'] = __("This component doesn't exist!",WpAppKit::i18n_domain);
				self::exit_sending_json($answer);
			}

			if( WpakNavigationItemsStorage::navigation_item_exists_by_component($post_id,$nav_item_component_id) ){
				$answer['message'] = __('This component is already in navigation!',WpAppKit::i18n_domain);
				self::exit_sending_json($answer);
			}

			$navigation_item = new WpakNavigationItem($nav_item_component_id, $nav_item_position);
			$navigation_item_id = WpakNavigationItemsStorage::add_or_update_navigation_item($post_id,$navigation_item);

			$answer['html'] = self::get_navigation_row($post_id, WpakNavigationItemsStorage::get_nb_navigation_items($post_id), $navigation_item_id, $navigation_item);

			$answer['ok'] = 1;
			$answer['type'] = 'updated';
			$answer['message'] = __('New navigation item created successfuly',WpAppKit::i18n_domain);

			self::exit_sending_json($answer);

		}elseif( $action == 'delete' ){

			$nav_item_id = intval( $data['navigation_item_id'] );
			$post_id = intval( $data['post_id'] );
			if( WpakNavigationItemsStorage::navigation_item_exists($post_id,$nav_item_id) ){
				if( !WpakNavigationItemsStorage::delete_navigation_item($post_id,$nav_item_id) ){
					$answer['message'] = __('Could not delete navigation item',WpAppKit::i18n_domain);
				}else{
					$answer['ok'] = 1;
					$answer['type'] = 'updated';
					$answer['message'] = __('Navigation item deleted successfuly',WpAppKit::i18n_domain);
				}
			}else{
				$answer['message'] = __('Navigation item to delete not found',WpAppKit::i18n_domain);
			}
			self::exit_sending_json($answer);

		}elseif( $action == 'move' ){

			if( !empty($data['positions']) && is_array($data['positions']) ){
				WpakNavigationItemsStorage::update_items_positions($data['post_id'],$data['positions']);
				$answer['message'] = __('Navigation order updated successfuly',WpAppKit::i18n_domain);
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
			}

		}elseif( $action == 'edit_option' ) {

			if( !empty($data['navigation_item_id']) && !empty($data['option']) ) {
				$value = sanitize_text_field( $data['value'] );

				if( $data['option'] == 'icon_slug' ){
					$value = sanitize_file_name( remove_accents( $value ) );
				}

				WpakNavigationItemsStorage::update_item_option($post_id, intval( $data['navigation_item_id'] ), sanitize_key( $data['option'] ),$value);

				$answer['message'] = __('Navigation item updated successfuly',WpAppKit::i18n_domain);
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
				$answer['data'] = $value;
			}

		}

		//We should not arrive here, but just in case :
		self::exit_sending_json($answer);
	}

	public static function ajax_wpak_update_available_components(){
		$answer = array('ok' => 0, 'message' => '', 'type' => 'error', 'html' => '');

		if( empty($_POST['post_id'])
				|| !is_numeric($_POST['post_id'])
				|| empty($_POST['nonce'])
				|| !check_admin_referer('wpak-navigation-data-'. $_POST['post_id'],'nonce') ){
			exit();
		}

		$post_id = intval( $_POST['post_id'] );

		WpakAddons::require_app_addons_php_files( $post_id );

		ob_start();
		self::echo_item_form($post_id);
		$html = ob_get_contents();
		ob_end_clean();

		if( !empty($html) ){
			$answer['html'] = $html;
			$answer['ok'] = 1;
			$answer['type'] = 'updated';
		}else{
			$answer['message'] = __('Error while retrieving components available for navigation',WpAppKit::i18n_domain);
		}

		self::exit_sending_json($answer);
	}

	private static function exit_sending_json($answer){
		if( !WP_DEBUG ){
			$content_already_echoed = ob_get_contents();
			if( !empty($content_already_echoed) ){
				//TODO : allow to add $content_already_echoed in the answer as a JSON data for debbuging
				ob_end_clean();
			}
		}

		header('Content-type: application/json');
		echo json_encode($answer);
		exit();
	}
}

WpakNavigationBoSettings::hooks();