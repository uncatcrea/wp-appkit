<?php
class WpakComponentTypeHooks extends WpakComponentType{
	
	protected function compute_data($component,$options,$args=array()){
			
		$component_default_data = array(
			'query' => array('type'=>'custom-component'),
			'total' => 0,
			'global' => 'custom-global-'. $component->slug,
			'global-items' => array(),
			'global-items-ids' => array()
		);
		
		$component_data = apply_filters('wpak_custom_component-'. $options['hook'],$component_default_data,$component,$options,$args);
		
		if( isset($component_data['global']) && isset($component_data['global-items']) && isset($component_data['global-items-ids']) ){
			$this->set_specific('ids',$component_data['global-items-ids']);
			$this->set_globals($component_data['global'],$component_data['global-items']);
			unset($component_data['global']);
			unset($component_data['global-items']);
			unset($component_data['global-items-ids']);
		}
			
		foreach($component_data as $key => $value){
			$this->set_specific($key,$value);
		}
		
	} 
	
	public function get_options_to_display($component){

		$options = array(
			'hook' => array('label'=>__('Hook'),'value'=>$component->options['hook']),
		);

		return $options;
	}
	
	public function echo_form_fields($component){
		$has_options = !empty($component) && !empty($component->options);
		$current_hook = '';
		if( $has_options ){
			$options = $component->options;
			$current_hook = $options['hook'];
		}
		?>
		<div class="component-params">
			<label><?php _e('Hook name') ?> : </label>
			<input type="text" name="hook" value="<?php echo $current_hook ?>" />
		</div>
		<?php
	}
	
	public function echo_form_javascript(){
	}

	public function get_ajax_action_html_answer($action,$params){
	}
	
	public function get_options_from_posted_form($data){
		$hook = !empty($data['hook']) ? $data['hook'] : '';
		$options = array('hook'=> $hook);
		return $options;
	}
	
}

WpakComponentsTypes::register_component_type('hooks', array('label'=> __('Custom component, using hooks')));