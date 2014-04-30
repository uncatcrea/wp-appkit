<?php
class WpakComponentsBoSettings{

	public static function hooks(){
		if( is_admin() ){
			add_action('add_meta_boxes', array(__CLASS__,'add_meta_boxes'));
			add_action('admin_enqueue_scripts', array(__CLASS__,'admin_enqueue_scripts'));
			add_action('wp_ajax_wpak_update_component_type', array(__CLASS__,'ajax_update_component_type'));
			add_action('wp_ajax_wpak_update_component_options', array(__CLASS__,'ajax_update_component_options'));
			add_action('wp_ajax_wpak_edit_component', array(__CLASS__,'ajax_wpak_edit_component'));
		}
	}

	public static function admin_enqueue_scripts(){
		global $pagenow, $typenow;
		if( ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'wpak_apps' ){
			wp_enqueue_script('wpak_components_bo_settings_js',plugins_url('lib/components/components-bo-settings.js', dirname(dirname(__FILE__))),array('jquery'),WpakAppKit::resources_version);
		}
	}
	
	public static function add_meta_boxes(){
		add_meta_box(
			'wpak_app_components',
			__('App components'),
			array(__CLASS__,'inner_components_box'),
			'wpak_apps',
			'normal',
			'default'
		);
	}
	
	public static function inner_components_box($post,$current_box){
		$components = WpakComponentsStorage::get_components($post->ID);
		?>
		
		<div id="components-wrapper">
		
			<a href="#" class="add-new-h2" id="add-new-component">Add New</a>
				
			<div id="components-feedback" style="display:none"></div>
			
			<div id="new-component-form" style="display:none">
				<h4><?php _e('New Component') ?></h4>
				<?php self::echo_component_form($post->ID) ?>
			</div>
			
			<table id="components-table" class="wp-list-table widefat fixed" >
				<thead>
					<tr>
						<th><?php _e('Name') ?></th>
						<th><?php _e('Slug') ?></th>
						<th><?php _e('Type') ?></th>
						<th><?php _e('Options') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if( !empty($components) ): ?>
					<?php $i = 0 ?>
					<?php foreach($components as $id => $component): ?>
						<?php echo self::get_component_row($post->ID,$i++,$id,$component) ?>
					<?php endforeach ?>
				<?php else: ?>
					<tr class="no-component-yet"><td colspan="4"><?php _e('No Component yet!') ?></td></tr>
				<?php endif ?>
				</tbody>
			</table>
			
			<?php WpakComponentsTypes::echo_components_javascript() ?>
					
		</div>
		
		<style>
			#components-wrapper{ margin-top:1em }
			#components-table{ margin-top:5px }
			#new-component-form{ margin-bottom: 4em }
			#components-wrapper #components-feedback{ padding:1em; margin:5px }
		</style>
		
		<?php
	}
	
	private static function get_component_row($post_id,$i,$component_id,WpakComponent $component){
		ob_start();
		?>
		<?php $alternate_class = $i%2 ? '' : 'alternate' ?>
		<tr class="component-row <?php echo $alternate_class ?>" id="component-row-<?php echo $component_id?>">
			<td>
				<?php echo $component->label ?>
				<div class="row-actions">
					<span class="inline hide-if-no-js"><a class="editinline" href="#" data-edit-id="<?php echo $component_id ?>"><?php _e('Edit') ?></a> | </span>
					<span class="trash"><a class="submitdelete delete_component" href="#" data-post-id="<?php echo $post_id ?>" data-id="<?php echo $component_id ?>"><?php _e('Delete')?></a></span>
				</div>
			</td>
			<td><?php echo $component->slug ?></td>
			<td><?php echo WpakComponentsTypes::get_label($component->type) ?></td>
			<td>
				<?php $options = WpakComponentsTypes::get_options_to_display($component) ?>
				<?php foreach($options as $option): ?>
					<?php echo $option['label'] ?> : <?php echo $option['value'] ?><br/>
				<?php endforeach ?>
			</td>
		</tr>
		<tr class="edit-component-wrapper" id="edit-component-wrapper-<?php echo $component_id ?>" style="display:none" <?php echo $alternate_class ?>>
			<td colspan="4">
				<?php self::echo_component_form($post_id,$component) ?>
			</td>
		</tr>
		<?php 	
		$component_row_html = ob_get_contents();
		ob_end_clean();
		return $component_row_html;
	}
	
	private static function echo_component_form($post_id,$component=null){
		
		$edit = !empty($component);
	
		if( !$edit ){
			$component = new WpakComponent('','','posts-list');
		}
		
		$component_id = $edit ? WpakComponentsStorage::get_component_id($post_id,$component) : '0';
	
		$components_types = WpakComponentsTypes::get_available_components_types();
	
		?>
		<div id="component-form-<?php echo $component_id ?>" class="component-form">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Component label') ?></th>
			        <td><input type="text" name="component_label" value="<?php echo $component->label ?>" /></td>
			    </tr>
			    <tr valign="top">
					<th scope="row"><?php _e('Component slug') ?></th>
			        <td><input type="text" name="component_slug" value="<?php echo $component->slug ?>" /></td>
			    </tr>
		        <tr valign="top">
		        	<th scope="row"><?php _e('Component type') ?></th>
		        	<td>
		        		<select type="text" name="component_type" class="component-type">
		        			<?php foreach($components_types as $type => $data): ?>
		        				<?php $selected = $type == $component->type ? 'selected="selected"' : '' ?>
		        				<option value="<?php echo $type ?>" <?php echo $selected ?> ><?php echo $data['label'] ?></option>
		        			<?php endforeach ?>
		        		</select>
		        	</td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row"><?php _e('Component options') ?></th>
		        	<td class="component-options-target">
		        		<?php WpakComponentsTypes::echo_form_fields($component->type,$edit ? $component : null) ?>
		        	</td>
		        </tr>
			</table>
			<input type="hidden" name="component_id" value="<?php echo $component_id ?>"/>
			<input type="hidden" name="component_post_id" value="<?php echo $post_id ?>" />
			<p class="submit">
				<a class="button-secondary alignleft cancel" title="<?php _e('Cancel') ?>" href="#" <?php echo !$edit ? 'id="cancel-new-component"' : '' ?>><?php _e('Cancel') ?></a>&nbsp;
				<a class="button button-primary component-form-submit" data-id="<?php echo $component_id ?>"><?php echo $edit ? __('Save Changes') : 'Save new component'?></a>
			</p>
		</div>
		<?php 
	}
	
	public static function ajax_update_component_options(){
		
		//TODO : nonce!
		$component_type = $_POST['component_type'];
		$action = $_POST['wpak_action'];
		$params = $_POST['params'];
		
		echo WpakComponentsTypes::get_ajax_action_html_answer($component_type, $action, $params);
		exit();
	}
	
	public static function ajax_update_component_type(){
	
		//TODO : nonce!
		$component_type = $_POST['component_type'];
		
		WpakComponentsTypes::echo_form_fields($component_type);
		exit();
	}
	
	public static function ajax_wpak_edit_component(){
	
		$answer = array('ok' => 0, 'message' => '', 'type' => 'error', 'html' => '');

		//TODO : nonce!
		$action = $_POST['wpak_action'];
		$data = $_POST['data'];
		
		if( $action == 'add_or_update' ){
			
			$post_id = $data['component_post_id'];
			if( empty($post_id) ){
				$answer['message'] = __("Application not found.");
				self::exit_sending_json($answer);
			}

			$edit = !empty($data['component_id']);
			$edit_id = $edit ? intval($data['component_id']) : 0;
		
			$component_label = trim($data['component_label']);
			$component_slug = trim($data['component_slug']);
			$component_type = $data['component_type'];
				
			if( empty($component_label) ){
				$answer['message'] = __('You must provide a name for the component!');
				self::exit_sending_json($answer);
			}
			
			if( empty($component_slug) ){
				$answer['message'] = __("You must provide a slug for the component.");
				self::exit_sending_json($answer);
			}
			
			if( is_numeric($component_slug) ){
				$answer['message'] = __("The component slug can't be numeric.");
				self::exit_sending_json($answer);
			}
		
			if( !$edit ){
				if( WpakComponentsStorage::component_exists($post_id,$component_slug) ){
					$answer['message'] = sprintf(__('A component with the slug "%s" already exists!'),$component_slug);
					self::exit_sending_json($answer);
				}
			}
				
			$component_options = WpakComponentsTypes::get_component_type_options_from_posted_form($component_type,$data);
		
			$component = new WpakComponent($component_slug, $component_label, $component_type, $component_options);
			$component_id = WpakComponentsStorage::add_or_update_component($post_id,$component,$edit_id);
			
			$answer['html'] = self::get_component_row($post_id, WpakComponentsStorage::get_nb_components($post_id), $component_id, $component);
			
			if( $edit ){
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
				$answer['message'] = sprintf(__('Component "%s" updated successfuly'),$component_label);
				
			}else{
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
				$answer['message'] = sprintf(__('Component "%s" created successfuly'),$component_label);
			}
			
			self::exit_sending_json($answer);
			
		}elseif( $action == 'delete' ){
			$id = $data['component_id'];
			$post_id = $data['post_id'];
			if(  is_numeric($id) && is_numeric($post_id) ){
				if( $component = WpakComponentsStorage::component_exists($post_id,$id) ){
					if( !WpakComponentsStorage::delete_component($post_id,$id) ){
						$answer['message'] = __('Could not delete component');
					}else{
						$answer['ok'] = 1;
						$answer['type'] = 'updated';
						$answer['message'] = __('Component deleted successfuly');
					}
				}else{
					$answer['message'] = __('Component to delete not found');
				}
				
			}
			self::exit_sending_json($answer);
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

WpakComponentsBoSettings::hooks();