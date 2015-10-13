<?php

require_once(dirname( __FILE__ ) . '/components-utils.php');
require_once(dirname( __FILE__ ) . '/components-bo-settings.php');
require_once(dirname( __FILE__ ) . '/components-storage.php');
require_once(dirname( __FILE__ ) . '/components-types.php');

class WpakComponents {

	public static function get_app_components( $app_post_id ) {
		return WpakComponentsStorage::get_components( $app_post_id );
	}

	public static function get_components_synchro_data( $app_id ) {

		$components = array();
		$components_data = array();

		WpakAddons::require_app_addons_php_files( $app_id );

		$components_raw = WpakComponentsStorage::get_components( $app_id );

		if ( !empty( $components_raw ) ) {
			$globals = array();
			foreach ( $components_raw as $component ) {
				$component_data = WpakComponentsTypes::get_component_data( $component, $globals );

				//
				// Don't include null component into the webservice's return
				// Component data could be null if an addon's component has been added to the app and the addon isn't activated anymore
				// An addon could be seen as deactivated either if the corresponding plugin is deactivated, or if the corresponding checkbox is unchecked for the given app
				//

				if( null === $component_data ) {
					continue;
				}

				$globals = $component_data['globals'];
				$components[$component->slug] = $component_data['specific'];
			}

			$navigation_items = WpakNavigationItemsStorage::get_navigation_indexed_by_components_slugs( $app_id, true );

			$navigation_items = apply_filters( 'wpak_navigation_items', $navigation_items, WpakApps::get_app_slug( $app_id ) );

			$components_data['navigation'] = $navigation_items;
			$components_data['components'] = $components;
			$components_data['globals'] = $globals;

			$components_data['addons'] = WpakAddons::get_app_addons_dynamic_data( $app_id );
			
		} else {
			
			//No component : return empty components, navigation, globals and 
			//addons arrays so that the web service answer is considered valid :
			
			$components_data['navigation'] = array();
			$components_data['components'] = array();
			$components_data['globals'] = array();

			$components_data['addons'] = WpakAddons::get_app_addons_dynamic_data( $app_id );
			
		}

		return $components_data;
	}

	public static function get_component_data( $app_id, $component_slug, $args = array() ) {
		$component_data = array();

		if ( WpakComponentsStorage::component_exists( $app_id, $component_slug ) ) {
			$component = WpakComponentsStorage::get_component( $app_id, $component_slug );
			$component_data_raw = WpakComponentsTypes::get_component_data( $component, array(), $args );
			$component_data['component'] = $component_data_raw['specific'];
			$component_data['globals'] = $component_data_raw['globals'];
		}

		return $component_data;
	}

	public static function get_component_items( $app_id, $component_slug, $items_ids, $args = array() ) {
		$component_items = array();

		if ( WpakComponentsStorage::component_exists( $app_id, $component_slug ) ) {
			$component = WpakComponentsStorage::get_component( $app_id, $component_slug );
			$component_items_by_global = WpakComponentsTypes::get_component_items( $component, $items_ids, $args );
			foreach( $component_items_by_global as $global => $items ) {
				if ( is_string( $global ) ) { //to be sure that we did not forget to set a global as key
					$component_items['globals'][$global] = $items;
				}
			}
		}

		return $component_items;
	}

	/**
	 * Adds custom mobile image sizes only if activated via hook
	 */
	public static function handle_images_sizes() {

		//Handle specific mobile images sizes :
		$mobile_images_sizes_default = array(
			//Example : array( 'name' => 'mobile-featured-thumb', 'width' => 327, 'height' => 218 )
		);

		/**
		 * Use this 'wpak_mobile_images_sizes' filter to add custom mobile images sizes
		 */
		$mobile_images_sizes = apply_filters( 'wpak_mobile_images_sizes', $mobile_images_sizes_default );
		if ( !empty( $mobile_images_sizes ) ) {
			foreach ( $mobile_images_sizes as $image_size ) {
				add_image_size( $image_size['name'], $image_size['width'], $image_size['height'] );
			}
		}
	}
}

class WpakComponent {

	protected $slug = '';
	protected $label = '';
	protected $type = '';
	protected $options = array();

	public function __construct( $slug, $label, $type, $options = array() ) {
		$this->slug = $slug;
		$this->label = $label;
		$this->type = $type;
		$this->options = $options;
	}

	public function __get( $attribute ) {
		return property_exists( __CLASS__, $attribute ) ? $this->$attribute : null;
	}

	public function __isset( $attribute ) {
		return isset( $this->$attribute );
	}

}
