<?php

class WpakAddon {

	protected $name = '';
	protected $slug = '';
	protected $directory = '';
	protected $url = '';
	protected $js_files = array();
	protected $css_files = array();

	public function __construct( $name ) {
		$this->name = $name;
		$this->slug = sanitize_title_with_dashes( remove_accents( $name ) );
	}

	public function __get( $property ) {
		if ( in_array( $property, array( 'name', 'slug' ) ) ) {
			return $this->{$property};
		}
		return null;
	}

	public function set_location( $addon_file ) {
		$this->directory = untrailingslashit( dirname( $addon_file ) );
		$this->url = plugins_url( '', $addon_file ); // > An addon must be a plugin
	}

	public function add_js( $js_file, $type = 'theme', $is_amd = true ) {
		$full_js_file = '';

		if ( strpos( $js_file, $this->directory ) !== false ) {
			$full_js_file = $js_file;
			$js_file = ltrim( str_replace( $this->directory, '', $js_file ), '/\\' );
		} else {
			$js_file = ltrim( $js_file, '/\\' );
			$full_js_file = $this->directory . '/' . $js_file;
		}

		if ( file_exists( $full_js_file ) ) {
			if ( !in_array( $js_file, $this->js_files ) ) {
				$this->js_files[] = array( 'file' => $js_file, 'type' => $type, 'is_amd' => $is_amd );
			}
		}
	}

	public function add_css( $css_file ) {

		$full_css_file = '';

		if ( strpos( $css_file, $this->directory ) !== false ) {
			$full_css_file = $css_file;
			$css_file = ltrim( str_replace( $this->directory, '', $css_file ), '/\\' );
		} else {
			$css_file = ltrim( $css_file, '/\\' );
			$full_css_file = $this->directory . '/' . $css_file;
		}

		if ( file_exists( $full_css_file ) ) {
			if ( !in_array( $css_file, $this->css_files ) ) {
				$this->css_files[] = array( 'file' => $css_file );
			}
		}
	}

	public function get_asset_file( $file_relative_to_addon ) {

		$file_type = pathinfo( $file_relative_to_addon, PATHINFO_EXTENSION );
		if ( isset( $this->{$file_type . '_files'} ) ) {
			foreach ( $this->{$file_type . '_files'} as $file ) {
				if ( $file_relative_to_addon == $file['file'] ) {
					$found = true;
					break;
				}
			}
		}

		$file_full_path = $this->directory . '/' . $file_relative_to_addon;

		return $found && file_exists( $file_full_path ) ? $file_full_path : false;
	}

	public function check_exists() {
		return file_exists( $this->directory );
	}

	public function to_config_object() {
		return ( object ) array(
					'name' => $this->name,
					'slug' => $this->slug,
					'url' => $this->url,
					'js_files' => $this->js_files,
					'css_files' => $this->css_files,
		);
	}

}
