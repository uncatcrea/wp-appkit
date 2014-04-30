<?php

class WpakNavigationBoSettings{
	
	const menu_item = 'wpak_navigation_bo_settings';
	
	public static function hooks(){
		if( is_admin() ){
			add_action('add_meta_boxes', array(__CLASS__,'add_meta_boxes'));
			add_action('admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'));
			add_action('wp_ajax_wpak_edit_navigation', array(__CLASS__,'ajax_wpak_edit_navigation'));
		}
	}
	
	public static function admin_enqueue_scripts(){
		global $pagenow, $typenow;
		if( ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'wpak_apps' ){
			wp_enqueue_script('wpak_navigation_bo_settings_js',plugins_url('lib/navigation/navigation-bo-settings.js', dirname(dirname(__FILE__))),array('jquery','jquery-ui-sortable'),WpakAppKit::resources_version);
		}
	}
	
	public static function add_meta_boxes(){
		add_meta_box(
			'wpak_app_navigation',
			__('App navigation'),
			array(__CLASS__,'inner_components_box'),
			'wpak_apps',
			'normal',
			'default'
		);
	}
	
	public static function inner_components_box($post,$current_box){
		$nav_items = WpakNavigationItemsStorage::get_navigation_items($post->ID);
		?>
		<div id="navigation-wrapper">
			<a href="#" class="add-new-h2" id="add-new-item"><?php _e('Add new component to navigation') ?></a>
			
			<div id="navigation-feedback" style="display:none"></div>
			
			<div id="new-item-form" style="display:none">
				<h4><?php _e('New navigation item') ?></h4>
				<?php self::echo_item_form($post->ID) ?>
			</div>
			
			<table class="wp-list-table widefat fixed" id="navigation-items-table" data-post-id="<?php echo $post->ID ?>">
				<thead>
					<tr>
						<th><?php _e('Navigation components') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if( !empty($nav_items) ): ?>
					<?php $i = 0 ?>
					<?php foreach($nav_items as $nav_item_id => $nav_item): ?>
						<?php echo self::get_navigation_row($post->ID,$i++,$nav_item_id,$nav_item) ?>
					<?php endforeach ?>
				<?php else: ?>
					<tr class="no-component-yet"><td><?php _e('No navigation item yet!') ?></td></tr>
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
			</style>
		</div>
		<?php 
	}
	
	private static function get_navigation_row($post_id,$i,$nav_item_id,$nav_item){
		ob_start();
		?>
		<?php $alternate_class = $i%2 ? '' : 'alternate' ?>
		<?php $component = WpakComponentsStorage::get_component($post_id,$nav_item->component_id) ?>
		<?php if( !empty($component) ): ?>
			<tr class="ui-state-default <?php echo $alternate_class ?>" data-id="<?php echo $nav_item_id ?>" id="navigation-item-row-<?php echo $nav_item_id ?>">
				<td>
					<?php echo $component->label ?> (<?php echo $component->slug ?>)
					<input type="hidden" id="position-<?php echo $nav_item_id ?>" name="positions[<?php echo $nav_item_id ?>]" value="<?php echo $nav_item->position ?>" />
					<div class="row-actions">
						<span class="trash"><a class="submitdelete delete_navigation_item" href="#" data-post-id="<?php echo $post_id ?>" data-id="<?php echo $nav_item_id ?>"><?php _e('Delete')?></a></span>
					</div>
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
			<div id="navigation-item-form-<?php echo $navigation_item_id ?>" class="navigation-item-form">
				<table class="form-table">
					<tr valign="top">
						<?php if( !empty($components) ): ?>
							<th scope="row"><?php _e('Component') ?></th>
					        <td>
					        	<select name="component_id">
					        		<?php foreach($components as $component_id => $component): ?>
					        			<option value="<?php echo $component_id ?>"><?php echo $component->label ?></option>
					        		<?php endforeach ?>
					        	</select>
					        </td>
					     <?php else: ?>
					     	<td>
				        		<?php _e('All components are already in navigation!') ?>
				        	</td>
				        <?php endif ?>
				    </tr>
				</table>
				<input type="hidden" name="position" value="0" />
				<input type="hidden" name="navigation_post_id" value="<?php echo $post_id ?>" />
				<p class="submit">
					<a class="button-secondary alignleft cancel" title="<?php _e('Cancel') ?>" href="#" id="cancel-new-item"><?php _e('Cancel') ?></a>&nbsp;
					<?php if( !empty($components) ): ?>
						<a class="button button-primary navigation-form-submit" data-id="<?php echo $navigation_item_id ?>"><?php _e('Add component to navigation') ?></a>
					<?php endif ?>
				</p>
			</div>
		<?php else: ?>
			<div>
				<?php _e('No component found!') ?> : <?php _e('Please create a component!') ?>
			</div>
		<?php endif ?>
		<?php 
	}
	
	public static function ajax_wpak_edit_navigation(){
		//TODO : add nonce!
		
		$answer = array('ok' => 0, 'message' => '', 'type' => 'error', 'html' => '');
		
		//TODO : nonce!
		$action = $_POST['wpak_action'];
		$data = $_POST['data'];
		
		if( $action == 'add_or_update' ){
	
			$post_id = $data['navigation_post_id'];
			
			$nav_item_component_id = $data['component_id'];
			$nav_item_position = !empty($data['position']) && is_numeric($data['position']) ? $data['position'] : 0;
				
			if( empty($nav_item_component_id) ){
				$answer['message'] = __('You must choose a component!');
				self::exit_sending_json($answer);
			}
				
			if( !WpakComponentsStorage::component_exists($post_id,$nav_item_component_id) ){
				$answer['message'] = __("This component doesn't exist!");
				self::exit_sending_json($answer);
			}
			
			if( WpakNavigationItemsStorage::navigation_item_exists_by_component($post_id,$nav_item_component_id) ){
				$answer['message'] = __('This component is already in navigation!');
				self::exit_sending_json($answer);
			}
				
			$navigation_item = new WpakNavigationItem($nav_item_component_id, $nav_item_position);
			$navigation_item_id = WpakNavigationItemsStorage::add_or_update_navigation_item($post_id,$navigation_item);
				
			$answer['html'] = self::get_navigation_row($post_id, WpakNavigationItemsStorage::get_nb_navigation_items($post_id), $navigation_item_id, $navigation_item);
				
			$answer['ok'] = 1;
			$answer['type'] = 'updated';
			$answer['message'] = __('New navigation item created successfuly');
			
			self::exit_sending_json($answer);
			
		}elseif( $action == 'delete' ){

			$nav_item_id = $data['navigation_item_id'];
			$post_id = $data['post_id'];
			if( WpakNavigationItemsStorage::navigation_item_exists($post_id,$nav_item_id) ){
				if( !WpakNavigationItemsStorage::delete_navigation_item($post_id,$nav_item_id) ){
					$answer['message'] = __('Could not delete navigation item');
				}else{
					$answer['ok'] = 1;
					$answer['type'] = 'updated';
					$answer['message'] = __('Navigation item deleted successfuly');
				}
			}else{
				$answer['message'] = __('Navigation item to delete not found');
			}
			self::exit_sending_json($answer);
			
		}elseif( $action == 'move' ){

			if( !empty($data['positions']) && is_array($data['positions']) ){
				WpakNavigationItemsStorage::update_items_positions($data['post_id'],$data['positions']);
				$answer['message'] = __('Navigation order updated successfuly');
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
			}
		
		}
		
		//We should not arrive here, but just in case :
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