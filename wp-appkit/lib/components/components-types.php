<?php

abstract class WpakComponentType {

	private $data = array( 'specific' => array(), 'globals' => array() );

	abstract protected function compute_data( $component, $options, $args = array() );

	abstract public function get_options_to_display( $component );

	abstract public function get_ajax_action_html_answer( $action, $params );

	abstract public function echo_form_fields( $component );

	abstract public function echo_form_javascript();

	abstract public function get_options_from_posted_form( $data );

	public function get_data( WpakComponent $component, $component_globals, $args = array() ) {
		$this->data['globals'] = $component_globals;
		$this->data['specific']['label'] = $component->label;
		$this->data['specific']['type'] = $component->type;
		$this->data['specific']['slug'] = $component->slug;
		
		/**
		* Filter component's options before data is retrieved for the component.
		* Can be used for example to change post list components' queries, page components' ids etc...
		* 
		* @param array    $options               Component option that can be customized, coming from options set in Back Office for the component
		* @param object   $component             Component we're going to retrieved data for
		* @param array    $args                  Arguments comming from the request
		*/
		$options = apply_filters( 'wpak_component_options', $component->options, $component, $args );
		
		$this->compute_data( $component, $options, $args );
		return $this->data;
	}

	/**
	 * Retrieves a subset ($items_ids) of component items
	 */
	public function get_items( WpakComponent $component, $items_ids, $args = array() ) {
		$items_by_global = array();
		if ( method_exists( $this, 'get_items_data' ) ) {
			//The get_items_data() method is optionnal.
			//It must return an array of component items indexed by global :
			$items_by_global = $this->get_items_data( $component, $component->options, $items_ids, $args );
		}
		return $items_by_global;
	}

	protected function set_specific( $specific_key, $values ) {
		@$this->data['specific']['data'][$specific_key] = $values;
	}

	/**
	 * Only one global will be taken into account. If more than one are set, only the last one will count.
	 */
	protected function set_globals( $globals_key, $values ) {
		@$this->data['specific']['global'] = $globals_key;
		foreach ( $values as $k => $v ) {
			@$this->data['globals'][$globals_key][$k] = $v;
		}
	}

}

class WpakComponentsTypes {

	private static $component_types = array();

	public static function register_component_type( $component_type_slug, $component_type_display_info ) {
		self::$component_types[$component_type_slug] = $component_type_display_info;
	}

	public static function get_available_components_types() {
		return self::$component_types;
	}

	public static function get_component_data( WpakComponent $component, $component_globals, $args = array() ) {
		$data = null;
		if ( self::component_type_exists( $component->type ) ) {
			$data = self::factory( $component->type )->get_data( $component, $component_globals, $args );
			
			/**
			 * Filter component's data before it is returned by webservice.
			 * Can be used for example to change component's label.
			 * 
			 * @param array    $data                  Component data that can be customized
			 * @param object   $component             Component we retrieved data for
			 * @param int      $app_id                Id of the app the component belongs to
			 * @param object   $component_globals     Global items passed to the component
			 * @param array    $args                  Arguments comming from the request
			 */
			$data = apply_filters( 'wpak_component_data', $data, $component, wpak_get_current_app_id(), $component_globals, $args );
			
		}
		return $data;
	}

	public static function get_component_items( WpakComponent $component, $items_ids, $args = array() ) {
		$items = null;
		if ( self::component_type_exists( $component->type ) ) {
			$items = self::factory( $component->type )->get_items( $component, $items_ids, $args );
		}
		return $items;
	}

	public static function get_options_to_display( WpakComponent $component ) {
		$options = array();
		if ( self::component_type_exists( $component->type ) ) {
			$options = self::factory( $component->type )->get_options_to_display( $component );
		}
		return $options;
	}

	public static function get_ajax_action_html_answer( $component_type, $action, $params ) {
		$result = '';
		if ( self::component_type_exists( $component_type ) ) {
			$result = self::factory( $component_type )->get_ajax_action_html_answer( $action, $params );
		}
		return $result;
	}

	public static function echo_form_fields( $component_type, WpakComponent $component = null ) {
		if ( self::component_type_exists( $component_type ) ) {
			self::factory( $component_type )->echo_form_fields( $component );
		}
	}

	public static function echo_components_javascript() {
		foreach ( array_keys( self::get_available_components_types() ) as $component_type ) {
			$result = self::factory( $component_type )->echo_form_javascript();
		}
	}

	public static function get_component_type_options_from_posted_form( $component_type, $data ) {
		if ( self::component_type_exists( $component_type ) ) {
			return self::factory( $component_type )->get_options_from_posted_form( $data );
		}
	}

	public static function component_type_exists( $component_type ) {
		return array_key_exists( $component_type, self::get_available_components_types() );
	}

	public static function get_component_type( $component_type_slug ) {
		$available_components_types = self::get_available_components_types();
		return self::component_type_exists( $component_type_slug ) ? $available_components_types[$component_type_slug] : null;
	}

	public static function get_label( $component_type_slug ) {
		$ct = self::get_component_type( $component_type_slug );
		return !empty( $ct ) ? $ct['label'] : '';
	}

	private static function factory( $component_type ) {
		$words = explode( '-', $component_type );
		$class = 'WpakComponentType';
		foreach ( $words as $word ) {
			$class .= ucfirst( $word );
		}
		return class_exists( $class ) ? new $class : null;
	}

}

//Include native component types :
require_once(dirname( __FILE__ ) . '/components-types/posts-list.php');
require_once(dirname( __FILE__ ) . '/components-types/page.php');
require_once(dirname( __FILE__ ) . '/components-types/hooks.php');