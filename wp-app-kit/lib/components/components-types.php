<?php

abstract class WpakComponentType {

	private $data = array( 'specific' => array(), 'globals' => array() );

	abstract protected function compute_data( $component, $options, $args = array() );

	abstract public function get_options_to_display( $component );

	abstract public function get_ajax_action_html_answer( $action, $params );

	abstract public function echo_form_fields( $component );

	abstract public function echo_form_javascript();

	abstract public function get_options_from_posted_form( $data );

	/**
	 * Returns true when a content refresh is needed for the given component regarding the passed args.
	 * Called on several hooks (@see WpakApps::hooks())
	 *
	 * @param	WpakComponent	$component		The component object.
	 * @param	array			$args			The hook's name and params.
	 *
	 * @return	boolean							Whether a refresh may be needed or not.
	 */
	abstract public function maybe_refresh_content( $component, $args );

	public function get_data( WpakComponent $component, $globals, $args = array() ) {
		$this->data['globals'] = $globals;
		$this->data['specific']['label'] = $component->label;
		$this->data['specific']['type'] = $component->type;
		$this->data['specific']['slug'] = $component->slug;
		$this->compute_data( $component, $component->options, $args );
		return $this->data;
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

	public static function get_component_data( WpakComponent $component, $globals, $args = array() ) {
		$data = null;
		if ( self::component_type_exists( $component->type ) ) {
			$data = self::factory( $component->type )->get_data( $component, $globals, $args );
		}
		return $data;
	}

	public static function get_options_to_display( $component ) {
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

	/**
	 * Returns true when a content refresh is needed for the given component regarding the passed args.
	 * Called on several hooks (@see WpakApps::hooks())
	 *
	 * @param	WpakComponent	$component		The component object.
	 * @param	array			$args			The hook's name and params.
	 *
	 * @return	boolean							Whether a refresh may be needed or not.
	 */
	public static function maybe_refresh_content( $component, $args ) {
		$return = false;

		if ( self::component_type_exists( $component->type ) ) {
			$return = self::factory( $component->type )->maybe_refresh_content( $component, $args );
		}

		return $return;
	}

}

//Include native component types :
require_once(dirname( __FILE__ ) . '/components-types/posts-list.php');
require_once(dirname( __FILE__ ) . '/components-types/page.php');
require_once(dirname( __FILE__ ) . '/components-types/hooks.php');
