<?php

class WpakComponentTypeHooks extends WpakComponentType {

	protected function compute_data( $component, $options, $args = array() ) {

		do_action( 'wpak_before_component_hooks', $component, $options );

		$component_default_data = array();

		/**
		 * Filter data from a custom component.
		 *
		 * @param array 			$component_default_data    	Put your custom component data here.
		 * @param WpakComponent 	$component 					The component object.
		 * @param array 			$options 					Component options.
		 * @param array 			$args 						Component's complementary arguments.
		 */
		$component_data = apply_filters( 'wpak_custom_component-' . $options['hook'], $component_default_data, $component, $options, $args );

		if ( isset( $component_data['global-items'] ) && isset( $component_data['global-items-ids'] ) ) {
			
			$global = !empty( $component_data['global'] ) ? $component_data['global'] : 'custom-global-' . $component->slug;
			
			$this->set_specific( 'ids', $component_data['global-items-ids'] );
			
			$this->set_globals( $global, $component_data['global-items'] );
			
			$total = isset( $component_data['total'] ) && is_numeric( $component_data['total'] ) ? $component_data['total'] : count( $component_data['global-items-ids'] );
			$this->set_specific( 'total', $total );
			
			if ( isset( $component_data['global'] ) ) {
				unset( $component_data['global'] );
			}
			if ( isset( $component_data['total'] ) ) {
				unset( $component_data['total'] );
			}
			unset( $component_data['global-items'] );
			unset( $component_data['global-items-ids'] );
		}

		foreach ( $component_data as $key => $value ) {
			$this->set_specific( $key, $value );
		}
	}
	
	/**
	 * To retrieve only items given in $items_ids
	 * If the component is linked to globals, must return an items array  
	 * indexed on globals.
	 */
	protected function get_items_data( $component, $options, $items_ids, $args = array() ) {
		$items = array();
		$items = apply_filters( 'wpak_custom_component_get_items-' . $options['hook'], $items, $component, $options, $items_ids, $args );
		return $items;
	}

	public function get_options_to_display( $component ) {

		$options = array(
			'hook' => array( 'label' => __( 'Hook', WpAppKit::i18n_domain ), 'value' => $component->options['hook'] ),
		);

		return $options;
	}

	public function echo_form_fields( $component ) {
		$has_options = !empty( $component ) && !empty( $component->options );
		$current_hook = '';
		if ( $has_options ) {
			$options = $component->options;
			$current_hook = $options['hook'];
		}
		?>
		<div class="component-params">
			<label><?php _e( 'Hook name', WpAppKit::i18n_domain ) ?> : </label>
			<input type="text" name="hook" value="<?php echo esc_attr( $current_hook ) ?>" />
		</div>
		<?php
	}

	public function echo_form_javascript() {

	}

	public function get_ajax_action_html_answer( $action, $params ) {

	}

	public function get_options_from_posted_form( $data ) {
		$hook = !empty( $data['hook'] ) ? sanitize_key( $data['hook'] ) : '';
		$options = array( 'hook' => $hook );
		return $options;
	}

}

WpakComponentsTypes::register_component_type( 'hooks', array( 'label' => __( 'Custom component, using hooks', WpAppKit::i18n_domain ) ) );
