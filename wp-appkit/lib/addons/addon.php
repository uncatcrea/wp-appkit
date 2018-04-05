<?php

class WpakAddon {

	protected $name = '';
	protected $slug = '';
	protected $platforms = [];
	protected $directory = '';
	protected $url = '';
	protected $js_files = array();
	protected $css_files = array();
	protected $html_files = array();
	protected $php_files = array();
	protected $template_files = array();
	protected $app_static_data_callback = null;
	protected $app_static_data = null;
	protected $app_dynamic_data_callback = null;
	protected $app_dynamic_data = null;

	public function __construct( $name, $slug = '', $platforms = ['ios','android'] ) {
		$this->name = $name;
		$this->slug = sanitize_title_with_dashes( remove_accents( empty($slug) ? $name : $slug ) );
		$this->platforms = is_array( $platforms ) ? $platforms : [];
	}

	public function __get( $property ) {
		if ( in_array( $property, array( 'name', 'slug', 'platforms' ) ) ) {
			return $this->{$property};
		}
		return null;
	}

	public function set_location( $addon_file ) {
		$this->directory = untrailingslashit( dirname( $addon_file ) );
		$this->url = plugins_url( '', $addon_file ); // > An addon must be a plugin
	}

	public function add_js( $js_file, $type = 'module', $position = '', $platforms = [] ) {
		
		$file_type = pathinfo( $js_file, PATHINFO_EXTENSION );
		if( $file_type !== 'js' ){
			return;
		}
		
		$full_js_file = '';
		
		if( $type == 'theme' && $position === '' ){
			$position = 'after';
		}
		
		if( $type == 'init' ){
			$position = 'before'; //for now, only init+before is handled
		}

		if ( strpos( $js_file, $this->directory ) !== false ) {
			$full_js_file = $js_file;
			$js_file = ltrim( str_replace( $this->directory, '', $js_file ), '/\\' );
		} else {
			$js_file = ltrim( $js_file, '/\\' );
			$full_js_file = $this->directory . '/' . $js_file;
		}

		if ( file_exists( $full_js_file ) ) {
			if ( !in_array( $js_file, $this->js_files ) ) {
				$this->js_files[] = array( 
					'file' => $js_file, 
					'type' => $type, 
					'position' => $position,
					'platforms' => !empty( $platforms ) && is_array( $platforms ) ? $platforms : ['android', 'ios', 'pwa']
				);
			}
		}
	}

	public function add_css( $css_file, $position = 'after', $platforms = [] ) {

		$file_type = pathinfo( $css_file, PATHINFO_EXTENSION );
		if( $file_type !== 'css' ){
			return;
		}
		
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
				$this->css_files[] = array( 
					'file' => $css_file, 
					'type' => 'theme',
					'position' => $position,
					'platforms' => !empty( $platforms ) && is_array( $platforms ) ? $platforms : ['android', 'ios', 'pwa']
				);
			}
		}
	}
	
	public function add_html( $html_file, $type = 'layout', $position = 'after', $data = array(), $platforms = [] ) {

		$file_type = pathinfo( $html_file, PATHINFO_EXTENSION );
		if( $file_type !== 'html' ){
			return;
		}
		
		$full_html_file = '';

		if ( strpos( $html_file, $this->directory ) !== false ) {
			$full_html_file = $html_file;
			$html_file = ltrim( str_replace( $this->directory, '', $html_file ), '/\\' );
		} else {
			$html_file = ltrim( $html_file, '/\\' );
			$full_html_file = $this->directory . '/' . $html_file;
		}

		if ( file_exists( $full_html_file ) ) {
			if ( !in_array( $html_file, $this->html_files ) ) {
				$this->html_files[] = array( 
					'file' => $html_file, 
					'type' => $type ,
					'position' => $position,
					'data' => $data,
					'platforms' => !empty( $platforms ) && is_array( $platforms ) ? $platforms : ['android', 'ios', 'pwa']
				);
			}
		}
	}
	
	public function add_template( $template_file, $platforms = [] ) {

		$file_type = pathinfo( $template_file, PATHINFO_EXTENSION );
		if( $file_type !== 'html' ){
			return;
		}
		
		$full_template_file = '';

		if ( strpos( $template_file, $this->directory ) !== false ) {
			$full_template_file = $template_file;
			$template_file = ltrim( str_replace( $this->directory, '', $template_file ), '/\\' );
		} else {
			$template_file = ltrim( $template_file, '/\\' );
			$full_template_file = $this->directory . '/' . $template_file;
		}

		if ( file_exists( $full_template_file ) ) {
			if ( !in_array( $template_file, $this->template_files ) ) {
				$this->template_files[] = array( 
					'file' => $template_file,
					'platforms' => !empty( $platforms ) && is_array( $platforms ) ? $platforms : ['android', 'ios', 'pwa']
				);
			}
		}
	}
	
	/**
	 * PHP files that are included only if the addon is activated
	 * for a given app.
	 */
	public function require_php( $php_file, $platforms = [] ) {
		$file_type = pathinfo( $php_file, PATHINFO_EXTENSION );
		if( $file_type !== 'php' ){
			return;
		}
		
		$full_php_file = '';

		if ( strpos( $php_file, $this->directory ) !== false ) {
			$full_php_file = $php_file;
			$php_file = ltrim( str_replace( $this->directory, '', $php_file ), '/\\' );
		} else {
			$php_file = ltrim( $php_file, '/\\' );
			$full_php_file = $this->directory . '/' . $php_file;
		}
		
		if ( file_exists( $full_php_file ) ) {
			if ( !in_array( $php_file, $this->php_files ) ) {
				$this->php_files[] = array( 
					'file' => $php_file,
					'platforms' => !empty( $platforms ) && is_array( $platforms ) ? $platforms : ['android', 'ios', 'pwa']
				);
			}
		}
	}
	
	public function require_php_files( $app_id ) {
		$app_platform = WpakApps::get_app_info( $app_id, 'platform' );
		foreach ( $this->php_files as $php_file ) {
			if ( !in_array( $app_platform, $php_file['platforms'] ) ) {
				continue;
			}
			$full_php_file = $this->directory . '/' . $php_file['file'];
			if ( file_exists( $full_php_file ) ) {
				require_once( $full_php_file );
			}
		}
	}

	/**
	 * Set the addon callback that will retrieve additionnal addon static data 
	 * (added to config.js) specific to a given app.
	 * @param type $callback Should be a function that takes $app_id as argument and returns an associative array
	 */
	public function add_app_static_data( $callback ){
		$this->app_static_data_callback = $callback;
	}
	
	public function set_app_static_data( $app_id ){
		if( $this->app_static_data_callback !== null && is_callable($this->app_static_data_callback) ){
			$app_data = call_user_func( $this->app_static_data_callback, $app_id );
			if( $app_data !== false && is_array($app_data) ){
				$this->app_static_data = $app_data;
			}
		}
	}
	
	/**
	 * Set the addon callback that will retrieve additionnal addon dynamic data 
	 * (added to the synchronization web service) specific to a given app.
	 * @param type $callback Should be a function that takes $app_id as argument and returns an associative array
	 */
	public function add_app_dynamic_data( $callback ){
		$this->app_dynamic_data_callback = $callback;
	}
	
	public function set_app_dynamic_data( $app_id ){
		if( $this->app_dynamic_data_callback !== null && is_callable($this->app_dynamic_data_callback) ){
			$app_data = call_user_func( $this->app_dynamic_data_callback, $app_id );
			if( $app_data !== false && is_array($app_data) ){
				$this->app_dynamic_data = $app_data;
			}
		}
	}

	public function get_asset_file( $file_relative_to_addon ) {

		$found = false;
				
		$file_type = pathinfo( $file_relative_to_addon, PATHINFO_EXTENSION );
		if ( isset( $this->{$file_type . '_files'} ) ) {
			foreach ( $this->{$file_type . '_files'} as $file ) {
				if ( $file_relative_to_addon == $file['file'] ) {
					$found = true;
					break;
				}
			}
		}

		//html files can also be templates :
		if( !$found && $file_type == 'html' ) {
			foreach ( $this->{'template_files'} as $file ) {
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

	/**
	 * Export data for config.js file
	 */
	public function to_config_object( $app_id ) {
		return ( object ) array(
			'name' => $this->name,
			'slug' => $this->slug,
			'url' => $this->url,
			'js_files' => $this->filter_files_by_platform( $this->js_files, $app_id ),
			'css_files' => $this->filter_files_by_platform( $this->css_files, $app_id ),
			'html_files' => $this->filter_files_by_platform( $this->html_files, $app_id ),
			'template_files' => $this->filter_files_by_platform( $this->template_files, $app_id ),
			'app_data' => $this->app_static_data
		);
	}

	protected function filter_files_by_platform( $files, $app_id ) {
		$filtered_files = [];

		$app_platform = WpakApps::get_app_info( $app_id, 'platform' );

		foreach( $files as $file ) {
			if ( in_array( $app_platform, $file['platforms'] ) ) {
				$filtered_files[] = $file;
			}
		}

		return $filtered_files;
	}
	
	public function get_all_files( $app_id, $indexed_by_type = false ) {
		$all_files = array();

		$file_types = array( 'js', 'css', 'html', 'template' );

		$app_platform = WpakApps::get_app_info( $app_id, 'platform' );

		foreach ( $file_types as $file_type ) {
			if ( isset( $this->{$file_type . '_files'} ) ) {
				foreach ( $this->{$file_type . '_files'} as $file ) {
					if ( !in_array( $app_platform, $file['platforms'] ) ) {
						continue;
					}
					$file_full_path = $this->directory . '/' . $file['file'];
					if ( file_exists( $file_full_path ) ) {
						$file_paths = array( 'full' => $file_full_path, 'relative' => $file['file'] );
						if ( $indexed_by_type ) {
							$all_files[$file_type] = $file_paths;
						} else {
							$all_files[] = $file_paths;
						}
					}
				}
			}
		}

		return $all_files;
	}

	/**
	 * Retrieves dynamic data to be passed to the synchronization web service
	 */
	public function get_dynamic_data() {
		return ( object ) $this->app_dynamic_data;
	}

}
