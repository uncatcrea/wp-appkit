<?php

class WpakApps {

	const menu_item = 'wpak_main_bo_settings';

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'apps_custom_post_type' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_panels' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( __CLASS__, 'admin_print_styles' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_main_meta_box' ), 29 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_secondary_meta_boxes' ), 30 ); //30 to pass after the "Simulation" and "Export" boxes (see WpakBuild)
			add_action( 'postbox_classes_wpak_apps_wpak_app_export_phonegap_build', array( __CLASS__, 'add_platform_specific_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_export_phonegap_build', array( __CLASS__, 'add_ios_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_export_phonegap_build', array( __CLASS__, 'add_android_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_phonegap_data', array( __CLASS__, 'add_platform_specific_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_phonegap_data', array( __CLASS__, 'add_ios_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_phonegap_data', array( __CLASS__, 'add_android_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_deep_linking', array( __CLASS__, 'add_platform_specific_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_deep_linking', array( __CLASS__, 'add_ios_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_deep_linking', array( __CLASS__, 'add_android_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_export_pwa', array( __CLASS__, 'add_platform_specific_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_export_pwa', array( __CLASS__, 'add_pwa_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_pwa_data', array( __CLASS__, 'add_platform_specific_class' ) );
			add_action( 'postbox_classes_wpak_apps_wpak_app_pwa_data', array( __CLASS__, 'add_pwa_class' ) );
			add_action( 'wpak_inner_simulation_box', array( __CLASS__, 'inner_security_box' ), 10, 2 );
			add_action( 'save_post', array( __CLASS__, 'save_post' ) );
			add_filter( 'post_row_actions', array( __CLASS__, 'remove_quick_edit' ), 10, 2 );
			add_action( 'admin_head', array( __CLASS__, 'add_icon' ) );
			add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );
			add_filter( 'manage_wpak_apps_posts_columns' , array( __CLASS__, 'add_platform_column' ) );
			add_action( 'manage_wpak_apps_posts_custom_column' , array( __CLASS__, 'platform_column_content' ), 10, 2 );

			add_filter( 'sanitize_post_meta__wpak_app_pwa_path', array( __CLASS__, 'sanitize_pwa_path' ) );
		}
	}

	public static function add_platform_specific_class( $classes ) {
		$classes[] = 'platform-specific';

		return $classes;
	}

	public static function add_ios_class( $classes ) {
		$classes[] = 'ios';

		return $classes;
	}

	public static function add_android_class( $classes ) {
		$classes[] = 'android';

		return $classes;
	}

	public static function add_pwa_class( $classes ) {
		$classes[] = 'pwa';

		return $classes;
	}

	public static function admin_enqueue_scripts() {
		global $pagenow, $typenow;
		if ( ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'wpak_apps' ) {
			wp_enqueue_script( 'wpak_apps_js', plugins_url( 'lib/apps/apps.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery' ), WpAppKit::resources_version );
			$localize = array(
				'phonegap_mandatory' => self::get_phonegap_mandatory_fields(),
				'i18n' => array(
					'show_help' => __( 'Help me', WpAppKit::i18n_domain ),
					'hide_help' => __( 'Hide help texts', WpAppKit::i18n_domain ),
				),
			);
			wp_localize_script( 'wpak_apps_js', 'Apps', $localize );

			global $post;
			wp_enqueue_script( 'wpak_apps_pwa_js', plugins_url( 'lib/apps/pwa.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery', 'wp-color-picker' ), WpAppKit::resources_version );
			wp_localize_script( 'wpak_apps_pwa_js', 'wpak_pwa_export', array(
				'app_id' => $post->ID,
				'nonce' => wp_create_nonce( 'wpak_build_app_sources_' . $post->ID ),
				'icons_nonce' => wp_create_nonce( 'wpak_get_pwa_icons_' . $post->ID ),
				'messages' => array(
					'install_successfull' => __( 'Progressive Web App installed successfully', WpAppKit::i18n_domain ),
					'see_pwa' => __( 'View Progressive Web App', WpAppKit::i18n_domain ),
					'pwa_icons_detected' => __( 'We detected the following icons in your theme (in %s). They will be automatically used by the PWA:', WpAppKit::i18n_domain ),
					'pwa_no_icons' => sprintf( __( 'We didn\'t detect any icons in your theme. You can add them by following <a href="%s" target="_blank">our tutorial</a>. If you don\'t provide icons, default WP-AppKit icons will be used.', WpAppKit::i18n_domain ), WpakConfig::pwa_icons_tutorial ),
					'install_server_error' => __( 'A network or server error occured', WpAppKit::i18n_domain )
				)
			));
		}
	}

	public static function admin_print_styles() {
		global $pagenow, $typenow;
		if ( ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'wpak_apps' ) {
			wp_enqueue_style( 'wpak_apps_css', plugins_url( 'lib/apps/apps.css', dirname( dirname( __FILE__ ) ) ), array(), WpAppKit::resources_version );
		}
	}

	public static function apps_custom_post_type() {

		//Handle specific capabilities for the custom "WP AppKit App Editor" role only.
		//With special case for multisite admin who has all caps by default, so has the
		//'wpak_edit_apps' capability (See WP_User::has_cap()).
		$capability = current_user_can('wpak_edit_apps') && !( is_multisite() && is_super_admin() ) ? 'wpak_app' : 'post';

		register_post_type(
			'wpak_apps',
			array(
				'label' => __( 'Applications', WpAppKit::i18n_domain ),
				'description' => '',
				'public' => false,
				'show_ui' => true,
				'show_in_menu' => self::menu_item,
				'exclude_from_search' => true,
				'publicly_queryable' => false,
				'show_in_nav_menus' => false,
				'capability_type' => $capability,
				'hierarchical' => false,
				'rewrite' => false,
				'query_var' => false,
				'has_archive' => false,
				'supports' => array( 'title' ),
				'labels' => array(
					'name' => __( 'Applications', WpAppKit::i18n_domain ),
					'singular_name' => __( 'Application', WpAppKit::i18n_domain ),
					'menu_name' => __( 'Applications', WpAppKit::i18n_domain ),
					'add_new' => __( 'Add New', WpAppKit::i18n_domain ),
					'add_new_item' => __( 'Add an application', WpAppKit::i18n_domain ),
					'edit' => __( 'Edit', WpAppKit::i18n_domain ),
					'edit_item' => __( 'Edit Application', WpAppKit::i18n_domain ),
					'new_item' => __( 'New application', WpAppKit::i18n_domain ),
					'not_found' => __( 'No application found', WpAppKit::i18n_domain ),
				)
			)
		);
	}

	public static function updated_messages( $messages ) {
		$messages['wpak_apps'] = array(
			 0 => '', // Unused. Messages start at index 1.
			 1 => __( 'Application project saved.', WpAppKit::i18n_domain ),
			 4 => __( 'Application project saved.', WpAppKit::i18n_domain ),
			 6 => __( 'Application project saved.', WpAppKit::i18n_domain ),
			 7 => __( 'Application project saved.', WpAppKit::i18n_domain ),
			 8 => __( 'Application project saved.', WpAppKit::i18n_domain ),
			 9 => __( 'Application project saved.', WpAppKit::i18n_domain ),
			10 => __( 'Application project saved.', WpAppKit::i18n_domain ),
		);

		return $messages;
	}

	public static function add_icon() {
		global $pagenow, $typenow;

		//TODO : use an external CSS instead of writing style directly in <head>...

		if ( $typenow == 'wpak_apps' && in_array( $pagenow, array( 'edit.php', 'post-new.php', 'post.php' ) ) ) {
			?>
			<style>
				#icon-wpak_main_bo_settings{
					background-image: url(<?php echo admin_url() ?>/images/icons32.png);
					background-position: -552px -5px;
				}
			</style>
			<?php
		}
	}

	/**
	 * Add platfrom column after 'title' column
	 */
	public static function add_platform_column( $columns ) {

		$additionnal_column = array( 'wpak_platform' => __( 'Platform', WpAppKit::i18n_domain ) );

		$title_index = array_search( 'title', array_keys( $columns ) ) + 1;

		$columns = array_slice( $columns, 0, $title_index, true ) + $additionnal_column + array_slice( $columns, $title_index, count( $columns ) - $title_index, true );

		return $columns;
	}

	public static function platform_column_content( $column, $post_id ) {
		if ( $column === 'wpak_platform' ) {
			$app_info = self::get_app_main_infos( $post_id );
			$available_platforms = self::get_platforms();
			if ( array_key_exists( $app_info['platform'], $available_platforms ) ) {
				echo esc_html( $available_platforms[$app_info['platform']] );
			}
		}
	}

	public static function add_settings_panels() {
		$capability_required = current_user_can( 'wpak_edit_apps' ) ? 'wpak_edit_apps' : 'manage_options';
		add_menu_page(
			__( 'WP-AppKit', WpAppKit::i18n_domain ),
			__( 'WP-AppKit', WpAppKit::i18n_domain ),
			$capability_required,
			self::menu_item,
			array( __CLASS__, 'settings_panel' ),
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+PHN2ZyAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIiAgIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyIgICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiICAgdmVyc2lvbj0iMS4xIiAgIGlkPSJMYXllcl8xIiAgIHg9IjBweCIgICB5PSIwcHgiICAgdmlld0JveD0iMCAwIDE4IDE4IiAgIHhtbDpzcGFjZT0icHJlc2VydmUiICAgaW5rc2NhcGU6dmVyc2lvbj0iMC45MSByMTM3MjUiICAgc29kaXBvZGk6ZG9jbmFtZT0iaWNvbi1taW5pLTIuc3ZnIiAgIHdpZHRoPSIxOCIgICBoZWlnaHQ9IjE4Ij48bWV0YWRhdGEgICAgIGlkPSJtZXRhZGF0YTE1Ij48cmRmOlJERj48Y2M6V29yayAgICAgICAgIHJkZjphYm91dD0iIj48ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD48ZGM6dHlwZSAgICAgICAgICAgcmRmOnJlc291cmNlPSJodHRwOi8vcHVybC5vcmcvZGMvZGNtaXR5cGUvU3RpbGxJbWFnZSIgLz48ZGM6dGl0bGU+PC9kYzp0aXRsZT48L2NjOldvcms+PC9yZGY6UkRGPjwvbWV0YWRhdGE+PGRlZnMgICAgIGlkPSJkZWZzMTMiIC8+PHNvZGlwb2RpOm5hbWVkdmlldyAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIiAgICAgYm9yZGVyY29sb3I9IiM2NjY2NjYiICAgICBib3JkZXJvcGFjaXR5PSIxIiAgICAgb2JqZWN0dG9sZXJhbmNlPSIxMCIgICAgIGdyaWR0b2xlcmFuY2U9IjEwIiAgICAgZ3VpZGV0b2xlcmFuY2U9IjEwIiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiICAgICBpbmtzY2FwZTpwYWdlc2hhZG93PSIyIiAgICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIxNjgwIiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTAyOCIgICAgIGlkPSJuYW1lZHZpZXcxMSIgICAgIHNob3dncmlkPSJmYWxzZSIgICAgIGlua3NjYXBlOnNob3dwYWdlc2hhZG93PSJmYWxzZSIgICAgIGlua3NjYXBlOnpvb209IjIuOTUiICAgICBpbmtzY2FwZTpjeD0iNDAiICAgICBpbmtzY2FwZTpjeT0iNDAiICAgICBpbmtzY2FwZTp3aW5kb3cteD0iLTgiICAgICBpbmtzY2FwZTp3aW5kb3cteT0iLTgiICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIxIiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0iTGF5ZXJfMSIgLz48c3R5bGUgICAgIHR5cGU9InRleHQvY3NzIiAgICAgaWQ9InN0eWxlMyI+LnN0MHtmaWxsOiMxRTIzMkQ7fTwvc3R5bGU+PGcgICAgIGlkPSJnNSIgICAgIHN0eWxlPSJmaWxsOiNhMGE1YWE7ZmlsbC1vcGFjaXR5OjEiICAgICB0cmFuc2Zvcm09Im1hdHJpeCgwLjIwNTEyODIxLDAsMCwwLjIwNTEyODIxLDEuNzk0ODcxNCwwLjc5NDg3MTYpIj48cGF0aCAgICAgICBjbGFzcz0ic3QwIiAgICAgICBkPSJNIDQxLjUsMSA1LjUsMSBDIDMsMSAxLDMgMSw1LjUgbCAwLDY5IEMgMSw3NyAzLDc5IDUuNSw3OSBsIDM2LDAgQyA0NCw3OSA0Niw3NyA0Niw3NC41IGwgMCwtNjkgQyA0NiwzIDQ0LDEgNDEuNSwxIFogTSAyOSw3NCAxOCw3NCBjIC0xLjEsMCAtMiwtMC45IC0yLC0yIDAsLTEuMSAwLjksLTIgMiwtMiBsIDExLDAgYyAxLjEsMCAyLDAuOSAyLDIgMCwxLjEgLTAuOSwyIC0yLDIgeiBNIDQwLDY0IGMgMCwwLjYgLTAuNCwxIC0xLDEgTCA4LDY1IEMgNy40LDY1IDcsNjQuNSA3LDY0IEwgNyw4IEMgNyw3LjQgNy40LDcgOCw3IGwgMzEsMCBjIDAuNiwwIDEsMC41IDEsMSBsIDAsNTYgeiIgICAgICAgaWQ9InBhdGg3IiAgICAgICBzdHlsZT0iZmlsbDojYTBhNWFhO2ZpbGwtb3BhY2l0eToxIiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAvPjxwYXRoICAgICAgIGNsYXNzPSJzdDAiICAgICAgIGQ9Ik0gNzQuNywzNSBDIDcyLjYsMzQuNSA3MSwzMyA3MC4yLDMxIDY5LjUsMjkgNjkuOCwyNi44IDcxLDI1LjEgbCAyLjYsLTMuNiAtNS4xLC02LjEgLTQsMS45IGMgLTEuOSwwLjkgLTQuMiwwLjkgLTYsLTAuMiAtMS44LC0xLjEgLTMsLTMgLTMuMiwtNS4xIEwgNTUsNy42IGwgLTYuMiwtMSAwLDE4LjUgYyA2LjcsMS43IDEyLDcuNyAxMiwxNC45IDAsNy4yIC01LjEsMTMuMiAtMTEuOCwxNSBsIDAsMTguNSA2LjIsLTEgMC4zLC00LjQgYyAwLjEsLTIuMSAxLjMsLTQgMy4yLC01LjEgMS44LC0xLjEgNC4xLC0xLjEgNiwtMC4yIGwgNCwxLjkgNS4xLC02LjEgLTIuNywtMy42IGMgLTEuMywtMS43IC0xLjYsLTMuOSAtMC44LC01LjkgMC43LC0yIDIuNCwtMy41IDQuNSwtNCBMIDc5LDQ0IDc5LDM2IDc0LjcsMzUgWiIgICAgICAgaWQ9InBhdGg5IiAgICAgICBzdHlsZT0iZmlsbDojYTBhNWFhO2ZpbGwtb3BhY2l0eToxIiAgICAgICBpbmtzY2FwZTpjb25uZWN0b3ItY3VydmF0dXJlPSIwIiAvPjwvZz48L3N2Zz4='
		);
	}

	public static function add_main_meta_box() {
		remove_meta_box( 'submitdiv', 'wpak_apps', 'side' );

		add_meta_box(
			'wpak_app_publish',
			__( 'Save & Preview', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_publish_box' ),
			'wpak_apps',
			'side',
			'high'
		);

		add_meta_box(
			'wpak_app_project',
			__( 'My Project', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_project_box' ),
			'wpak_apps',
			'side',
			'high'
		);

		add_meta_box(
			'wpak_app_main_infos',
			__( 'Platform', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_main_infos_box' ),
			'wpak_apps',
			'normal',
			'default'
		);

		add_meta_box(
			'wpak_app_deep_linking',
			__( 'Deep Linking', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_deep_linking_box' ),
			'wpak_apps',
			'side',
			'default'
		);

	}

	public static function add_secondary_meta_boxes() {

		add_meta_box(
			'wpak_app_phonegap_data',
			__( 'PhoneGap Build', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_phonegap_infos_box' ),
			'wpak_apps',
			'normal',
			'default'
		);

		add_meta_box(
			'wpak_app_pwa_data',
			__( 'Progressive Web App', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_pwa_infos_box' ),
			'wpak_apps',
			'normal',
			'default'
		);

	}

	public static function get_phonegap_mandatory_fields() {
		return array(
			'name',
			'app_phonegap_id',
			'version',
			'desc',
			'author',
			'author_website',
			'author_email',
		);
	}

	public static function inner_publish_box( $post, $current_box ) {
		?>
		<div class="submitbox" id="submitpost">
			<div style="display:none;">
				<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
			</div>

			<div id="minor-publishing">
				<div id="minor-publishing-actions">
					<div id="preview-action">
						<a href="<?php echo esc_url( WpakBuild::get_appli_index_url( $post->ID ) ); ?>" class="preview button" target="_blank"><?php _e( 'Preview', WpAppKit::i18n_domain ) ?></a>
					</div>
					<div class="clear"></div>
				</div>
			</div>

			<div id="misc-publishing-actions">
				<?php
				/* translators: Publish box date format, see http://php.net/date */
				$datef = __( 'M j, Y @ H:i' );
				if ( 0 != $post->ID ) {
					if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
						$stamp = __( 'Last saved on: <b>%1$s</b>', WpAppKit::i18n_domain );
					} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
						$stamp = __( 'Not saved yet', WpAppKit::i18n_domain );
					}
					$date = date_i18n( $datef, strtotime( $post->post_modified ) );
				} else { // draft (no saves, and thus no date specified)
					$stamp = __( 'Not saved yet', WpAppKit::i18n_domain );
					$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
				}
				?>
				<div class="misc-pub-section curtime misc-pub-curtime">
					<span id="timestamp">
					<?php printf($stamp, $date); ?></span>
				</div>
			</div>

			<div id="major-publishing-actions">
				<div id="delete-action">
					<?php
					if ( current_user_can( "delete_post", $post->ID ) ) {
						if ( !EMPTY_TRASH_DAYS )
							$delete_text = __( 'Delete Permanently' );
						else
							$delete_text = __( 'Move to Trash' );
						?>
					<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a><?php
					} ?>
				</div>

				<div id="publishing-action">
					<span class="spinner"></span>
					<?php
					if ( !self::isSaved( $post ) ) { ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish' ) ?>" />
						<?php submit_button( __( 'Save' ), 'primary button-large', 'publish', false, array( 'accesskey' => 'p' ) );
					} else { ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ) ?>" />
						<?php submit_button( __( 'Save' ), 'primary button-large', 'save', false, array( 'accesskey' => 'p', 'id' => 'publish' ) ); ?>
					<?php
					} ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public static function inner_project_box( $post, $current_box ) {
		$components = WpakComponents::get_app_components( $post->ID );
		$navigation = WpakNavigation::get_app_navigation( $post->ID );
		$checked = array(
			'title' => !empty( $post->post_title ),
			'components' => !empty( $components ),
			'navigation' => !empty( $navigation ),
			'save' => self::isSaved( $post ),
			'phonegap' => true,
			'pwa' => true,
		);

		// Update phonegap checked value thanks to mandatory fields
		$main_infos = self::get_app_main_infos( $post->ID );
		$mandatory = self::get_phonegap_mandatory_fields();

		foreach( $mandatory as $key ) {
			if( '' === $main_infos[$key] ) {
				$checked['phonegap'] = false;
				break;
			}
		}

		// PWA related things
		$pwa_uri = WpakBuild::get_pwa_directory_uri( $post->ID );
		$pwa_installed = WpakBuild::app_pwa_is_installed( $post->ID );
		$post_saved = self::isSaved( $post );

		$pwa_export_types = array(
			'pwa-install' => $post_saved,
			'pwa' => $post_saved,
		);
		?>

		<div class="submitbox">
			<div id="wpak_publish_box">
				<ul id="wpak_app_wizard" class="list-group">
					<li id="wpak_app_wizard_title" class="list-group-item <?php echo $checked['title'] ? 'list-group-item-success' : ''; ?>">
						<span class="glyphicon glyphicon-<?php echo $checked['title'] ? 'check' : 'unchecked'; ?>"></span>
						<?php _e( 'Define a title', WpAppKit::i18n_domain ); ?>
					</li>
					<li id="wpak_app_wizard_components" class="list-group-item <?php echo $checked['components'] ? 'list-group-item-success' : ''; ?>">
						<span class="glyphicon glyphicon-<?php echo $checked['components'] ? 'check' : 'unchecked'; ?>"></span>
						<?php _e( 'Add components', WpAppKit::i18n_domain ); ?>
					</li>
					<li id="wpak_app_wizard_navigation" class="list-group-item <?php echo $checked['navigation'] ? 'list-group-item-success' : ''; ?>">
						<span class="glyphicon glyphicon-<?php echo $checked['navigation'] ? 'check' : 'unchecked'; ?>"></span>
						<?php _e( 'Setup appearance and navigation', WpAppKit::i18n_domain ); ?>
					</li>
					<li id="wpak_app_wizard_save" class="list-group-item <?php echo $checked['save'] ? 'list-group-item-success' : ''; ?>">
						<span class="glyphicon glyphicon-<?php echo $checked['save'] ? 'check' : 'unchecked'; ?>"></span>
						<?php _e( 'Save your app', WpAppKit::i18n_domain ); ?>
					</li>
                    <li id="wpak_app_wizard_phonegap" class="list-group-item platform-specific android ios <?php echo $checked['phonegap'] ? 'list-group-item-success' : ''; ?>">
                        <span class="glyphicon glyphicon-<?php echo $checked['phonegap'] ? 'check' : 'unchecked'; ?>"></span>
			    <?php _e( 'Setup PhoneGap config', WpAppKit::i18n_domain ); ?>
                    </li>
                    <li id="wpak_app_wizard_pwa" class="list-group-item platform-specific pwa <?php echo $checked['pwa'] ? 'list-group-item-success' : ''; ?>">
                        <span class="glyphicon glyphicon-<?php echo $checked['pwa'] ? 'check' : 'unchecked'; ?>"></span>
			    <?php _e( 'Setup Progressive Web App config', WpAppKit::i18n_domain ); ?>
                    </li>
				</ul>
			</div>

            <div class="export-action platform-specific android ios">
                <?php _e( 'PhoneGap Build', WpAppKit::i18n_domain ); ?><a id="wpak_export_link" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wpak_download_app_sources' ) ), 'wpak_download_app_sources' ) ); ?>" class="button" target="_blank"><?php _e( 'Export', WpAppKit::i18n_domain ) ?></a>

                <?php
                /*
                     * 2016-03-05: Export type select commented for now as we have to stabilize export features other
                     * than PhoneGap Build before releasing it.
                     * Was added in https://github.com/uncatcrea/wp-appkit/commit/ac4af270f8ea6273f4d653878c69fceec85a9dd8 along with
                     * the corresponding JS in apps.js.
                     *
                    <?php $default_export_type = 'phonegap-build'; ?>
                    <select name="export_type" id="wpak_export_type" >
                        <?php foreach( WpakBuild::get_allowed_export_types() as $export_type => $label ): ?>
                        <option value="<?php echo esc_attr( $export_type ) ?>" <?php selected( $export_type === $default_export_type )?>><?php echo esc_html( $label ) ?></option>
                        <?php endforeach ?>
                    </select>
                    <a id="wpak_export_link" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wpak_download_app_sources', 'export_type' => $default_export_type ) ), 'wpak_download_app_sources' ) ) ?>" class="button" target="_blank"><?php _e( 'Export', WpAppKit::i18n_domain ) ?></a>
                    */
                ?>
            </div>

            <?php if ( $pwa_installed ): ?>
                <a href="<?php echo $pwa_uri ?>" target="_blank" class="view-app-pwa button platform-specific pwa"><?php _e( 'View Progressive Web App', WpAppKit::i18n_domain ) ?></a>
            <?php endif ?>

            <div class="export-action platform-specific pwa">
                <span class="spinner"></span>
                <?php if( !empty( $pwa_export_types['pwa-install'] ) ): ?>
                    <a class="wpak_export_link_pwa pwa-install button" href="#"><?php echo !$pwa_installed ? __( 'Install PWA', WpAppKit::i18n_domain ) : __( 'Update PWA', WpAppKit::i18n_domain ); ?></a><br/>
                <?php endif; ?>
                <?php if( !empty( $pwa_export_types['pwa'] ) ): ?>
                    <a class="wpak_export_link_pwa" href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'wpak_download_app_sources', 'export_type' => 'pwa' ) ), 'wpak_download_app_sources' ) ?>" target="_blank"><?php _e( 'Download PWA sources', WpAppKit::i18n_domain ) ?></a>
                <?php endif; ?>

                <div class="wpak_export_pwa_feedback"></div>

            </div>

		</div>

		<?php
	}

	public static function inner_main_infos_box( $post, $current_box ) {
		$main_infos = self::get_app_main_infos( $post->ID );
		?>
		<div class="wpak_settings">
			<select id="wpak_app_platform" name="wpak_app_platform">
				<?php foreach ( self::get_platforms() as $value => $label ): ?>
					<?php $selected = $value == $main_infos['platform'] ? 'selected="selected"' : '' ?>
					<option value="<?php echo esc_attr( $value ) ?>" <?php echo $selected ?>><?php echo esc_html( $label ) ?></option>
				<?php endforeach ?>
			</select>
			<?php wp_nonce_field( 'wpak-main-infos-' . $post->ID, 'wpak-nonce-main-infos' ) ?>
		</div>
		<?php
	}

	public static function inner_deep_linking_box( $post, $current_box ) {
		$main_infos = self::get_app_main_infos( $post->ID );
		?>
		<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
		<div class="wpak_setting field-group">
			<p class="description"><?php _e( 'Deep Linking allows you to create links to open the app. Enter here the custom scheme you want to use (e.g. "urlscheme" lets you create links like urlscheme://mylink).', WpAppKit::i18n_domain ) ?></p>
			<label for="wpak_app_url_scheme"><?php _e( 'Custom URL Scheme', WpAppKit::i18n_domain ) ?></label>
			<input id="wpak_app_url_scheme" type="text" name="wpak_app_url_scheme" value="<?php echo esc_attr( $main_infos['url_scheme'] ); ?>" />
			<span class="description"><?php _e( 'If empty, deep linking feature won\'t be available for this app', WpAppKit::i18n_domain ) ?></span>
			<?php wp_nonce_field( 'wpak-deep-linking-' . $post->ID, 'wpak-nonce-deep-linking' ) ?>
		</div>
		<?php
	}

	public static function inner_phonegap_infos_box( $post, $current_box ) {
		$main_infos = self::get_app_main_infos( $post->ID );
		?>
		<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
		<div class="wpak_settings">
			<p class="description"><?php _e( 'Information will be used when compiling your app and may be displayed in app stores. It will be stored in the config.xml file of your project.', WpAppKit::i18n_domain ) ?></p>
			<fieldset>
				<legend><?php _e( 'Application', WpAppKit::i18n_domain ); ?></legend>
				<div class="field-group">
					<label><?php _e( 'Name', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_name" value="<?php echo esc_attr( $main_infos['name'] ) ?>" id="wpak_app_name" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Description', WpAppKit::i18n_domain ) ?></label>
					<textarea name="wpak_app_desc" id="wpak_app_desc"><?php echo esc_textarea( $main_infos['desc'] ) ?></textarea>
				</div>
				<div class="field-group">
					<label><?php _e( 'ID', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_phonegap_id" value="<?php echo esc_attr( $main_infos['app_phonegap_id'] ) ?>" id="wpak_app_app_phonegap_id" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Version', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_version" value="<?php echo esc_attr( $main_infos['version'] ) ?>" id="wpak_app_version" />
				</div>
				<div class="field-group platform-specific android">
					<label><?php _e( 'VersionCode (Android only)', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_version_code" value="<?php echo esc_attr( $main_infos['version_code'] ) ?>" id="wpak_app_version_code" />
				</div>
				<div class="field-group platform-specific android">
					<label><?php _e( 'Target Architecture (Android only)', WpAppKit::i18n_domain ) ?></label><br>
					<select name="wpak_app_target_architecture">
						<option value="arm" <?php selected( $main_infos['target_architecture'], 'gradle' ) ?>><?php echo esc_html( __( 'ARM' ), WpAppKit::i18n_domain ) ?></option>
						<option value="x86" <?php selected( $main_infos['target_architecture'], 'x86' ) ?>><?php echo esc_html( __( 'x86' ), WpAppKit::i18n_domain ) ?></option>
					</select>
				</div>
				<div class="field-group platform-specific android">
					<label><?php _e( 'Build Tool (Android only)', WpAppKit::i18n_domain ) ?></label><br>
					<select name="wpak_app_build_tool">
						<option value="gradle" <?php selected( $main_infos['build_tool'], 'gradle' ) ?>><?php echo esc_html( __( 'Gradle' ), WpAppKit::i18n_domain ) ?></option>
						<option value="ant" <?php selected( $main_infos['build_tool'], 'ant' ) ?>><?php echo esc_html( __( 'Ant' ), WpAppKit::i18n_domain ) ?></option>
					</select>
				</div>
				<div class="field-group">
					<label><?php _e( 'Icons and Splashscreens', WpAppKit::i18n_domain ) ?></label>
					<textarea name="wpak_app_icons" id="wpak_app_icons"><?php echo esc_textarea( $main_infos['icons'] ) ?></textarea>
					<span class="description"><?php printf( __( 'Add here the tags defining where are the app icons and splashscreens.<br/>Example: %s', WpAppKit::i18n_domain ), '&lt;icon src="icons/ldpi.png" gap:platform="android" gap:qualifier="ldpi" /&gt;' ) ?><br><br></span>
					<br>
					<input type="checkbox" id="wpak_use_default_icons_and_splash" name="wpak_use_default_icons_and_splash" <?php checked( $main_infos['use_default_icons_and_splash'] ) ?> />
					<label for="wpak_use_default_icons_and_splash"><?php _e( 'Use default WP-AppKit Icons and Splashscreens', WpAppKit::i18n_domain ) ?></label>
					<span class="description"><?php _e( 'If checked and "Icons and Splashscreens" is empty, the app export will embed the default WP-AppKit Icons and Splashscreens.', WpAppKit::i18n_domain )?></span>
				</div>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'Author', WpAppKit::i18n_domain ) ?></legend>
				<div class="field-group">
					<label><?php _e( 'Name', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_author" value="<?php echo esc_attr( $main_infos['author'] ) ?>" id="wpak_app_author" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Website', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_author_website" value="<?php echo esc_attr( $main_infos['author_website'] ) ?>" id="wpak_app_author_website" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Email', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_author_email" value="<?php echo esc_attr( $main_infos['author_email'] ) ?>" id="wpak_app_author_email" />
				</div>
			</fieldset>
			<fieldset>
				<legend><?php _e( 'PhoneGap', WpAppKit::i18n_domain ) ?></legend>
				<div class="field-group">
					<label><?php _e( 'Version', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_phonegap_version" value="<?php echo esc_attr( $main_infos['phonegap_version'] ) ?>" id="wpak_app_phonegap_version" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Plugins', WpAppKit::i18n_domain ) ?></label>
					<textarea name="wpak_app_phonegap_plugins" id="wpak_app_phonegap_plugins"><?php echo esc_textarea( $main_infos['phonegap_plugins'] ) ?></textarea>
					<span class="description"><?php __( 'Add here the tags defining the plugins you want to include in your app. Before adding a plugin, check which one is included by default.', WpAppKit::i18n_domain ) ?></span>
				</div>
			</fieldset>
			<div class="field-group wpak_phonegap_links">
				<a href="<?php echo esc_url( WpakBuild::get_appli_dir_url() . '/config.xml?wpak_app_id=' . self::get_app_slug( $post->ID ) ) ?>" target="_blank"><?php _e( 'View config.xml', WpAppKit::i18n_domain ) ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wpak_download_app_sources' ) ), 'wpak_download_app_sources' ) ) ?>" class="button wpak_phonegap_export" target="_blank"><?php _e( 'Export', WpAppKit::i18n_domain ) ?></a>
			</div>
			<?php wp_nonce_field( 'wpak-phonegap-infos-' . $post->ID, 'wpak-nonce-phonegap-infos' ) ?>
		</div>
		<?php
	}

	public static function inner_pwa_infos_box( $post, $current_box ) {
		$main_infos = self::get_app_main_infos( $post->ID );
		$pwa_uri = WpakBuild::get_pwa_directory_uri( $post->ID );
		$pwa_dir = WpakBuild::get_pwa_directory( $post->ID );
		$pwa_installed = WpakBuild::app_pwa_is_installed( $post->ID );
		?>
		<?php /* <a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a> */ ?>
		<div class="wpak_settings">
			<p class="description"><?php _e( '', WpAppKit::i18n_domain ) ?></p>
			<?php if( !is_ssl() ): ?>
				<p class="notice notice-warning notice-alt"><?php _e( 'Your website doesn\'t seem to be secured by HTTPS. Progressive Web Apps completely work only with HTTPS sites. You will have to enable it if you want to fully benefit from their power.', WpAppKit::i18n_domain ); ?></p>
			<?php endif; ?>
			<fieldset>
				<legend><?php _e( 'Install', WpAppKit::i18n_domain ); ?></legend>
				<div class="field-group">

					<div class="pwa_installed">
						<?php if ( $pwa_installed ): ?>
								<?php _e( "Progressive Web App <strong>installed</strong> in:", WpAppKit::i18n_domain ); ?><br><?php echo $pwa_dir ?>
						<?php else: ?>
								<?php _e( "Progressive Web App not installed.", WpAppKit::i18n_domain ); ?>
						<?php endif ?>
					</div>

					<?php if ( $pwa_installed ): ?>
						<a href="<?php echo $pwa_uri ?>" class="button" target="_blank"><?php _e( 'View Progressive Web App', WpAppKit::i18n_domain ) ?></a>
					<?php endif ?>

					<div class="pwa-infos-install">
						<span class="spinner"></span>

						<?php
						if( self::isSaved( $post ) ): ?>
                            <a class="wpak_export_link_pwa pwa-install button" href="#"><?php echo !$pwa_installed ? __( 'Install PWA', WpAppKit::i18n_domain ) : __( 'Update PWA', WpAppKit::i18n_domain ); ?></a>
                            <a class="wpak_export_link_pwa" href="<?php echo wp_nonce_url( add_query_arg( array( 'action' => 'wpak_download_app_sources', 'export_type' => 'pwa' ) ), 'wpak_download_app_sources' ) ?>" target="_blank"><?php _e( 'Download PWA sources', WpAppKit::i18n_domain ) ?></a>
						<?php endif; ?>

						<div class="wpak_export_pwa_feedback"></div>

					</div>

				</div>
			</fieldset>
			<?php if( self::isSaved( $post ) ): ?>
				<fieldset>
					<legend><?php _e( 'Paths', WpAppKit::i18n_domain ); ?></legend>
						<div class="field-group">
						<label><?php _e( 'Install Progressive Web App to:', WpAppKit::i18n_domain ) ?></label>
						<br><span><?php echo get_option( 'siteurl' ) .'/'; ?></span>
						<input type="text" name="wpak_app_pwa_path" value="<?php echo esc_attr( $main_infos['pwa_path'] ) ?>" id="wpak_app_pwa_path" />
					</div>
				</fieldset>
			<?php endif; ?>
			<fieldset>
				<legend><?php _e( 'Manifest', WpAppKit::i18n_domain ); ?></legend>
				<div class="field-group">
					<label><?php _e( 'Name (used in the banner)', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_pwa_name" value="<?php echo esc_attr( $main_infos['pwa_name'] ) ?>" id="wpak_app_pwa_name" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Short Name (used on the home screen)', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_pwa_short_name" value="<?php echo esc_attr( $main_infos['pwa_short_name'] ) ?>" id="wpak_app_pwa_short_name" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Description', WpAppKit::i18n_domain ) ?></label>
					<textarea name="wpak_app_pwa_desc" id="wpak_app_pwa_desc"><?php echo esc_textarea( $main_infos['pwa_desc'] ) ?></textarea>
				</div>
				<div class="field-group">
					<label><?php _e( 'Icons', WpAppKit::i18n_domain ) ?></label>
					<div class="wpak-pwa-icons">
						<div class="hide-if-js"><?php _e( 'If your theme already embeds icons, we will automatically take them for your PWA. If not, we will take WP-AppKit default icons.' ) ?></div>
					</div>
				</div>
				<div class="field-group">
					<label><?php _e( 'Background Color', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_pwa_background_color" value="<?php echo esc_attr( $main_infos['pwa_background_color'] ) ?>" id="wpak_pwa_background_color" class="color-field" />
				</div>
				<div class="field-group">
					<label><?php _e( 'Theme Color', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_pwa_theme_color" value="<?php echo esc_attr( $main_infos['pwa_theme_color'] ) ?>" id="wpak_pwa_theme_color" class="color-field" />
				</div>
				<?php if( $pwa_installed ): ?>
					<div class="field-group">
						<a href="<?php echo $pwa_uri . '/manifest.json' ?>" target="_blank"><?php _e( 'View manifest.json', WpAppKit::i18n_domain ) ?></a>
					</div>
				<?php endif; ?>
			</fieldset>
			
			<fieldset>
				<legend><?php _e( 'Version', WpAppKit::i18n_domain ); ?></legend>
				<div class="field-group">
					<label><?php _e( 'Progressive Web App version', WpAppKit::i18n_domain ) ?></label>
					<input type="text" name="wpak_app_pwa_version" value="<?php echo esc_attr( $main_infos['pwa_version'] ) ?>" id="wpak_app_pwa_version" />
				</div>
			</fieldset>

			<?php wp_nonce_field( 'wpak-phonegap-infos-' . $post->ID, 'wpak-nonce-phonegap-infos' ) ?>
		</div>
		<?php
	}

	public static function inner_security_box( $post, $current_box ) {
		$secured = self::get_app_is_secured( $post->ID );
		$simulation_secured = self::get_app_simulation_is_secured( $post->ID );
		?>
		<div class="field-group">
			<label><?php _e( 'App Simulation Visibility', WpAppKit::i18n_domain ) ?></label><br/>
		</div>
		<div class="field-group">
			<select name="wpak_app_simulation_secured">
				<option value="1" <?php echo $simulation_secured ? 'selected="selected"' : '' ?>><?php _e( 'Private', WpAppKit::i18n_domain ) ?></option>
				<option value="0" <?php echo!$simulation_secured ? 'selected="selected"' : '' ?>><?php _e( 'Public', WpAppKit::i18n_domain ) ?></option>
			</select>
			<span class="description"><?php _e( 'Private means that only logged in users with the right permissions can access the browser simulation. When public, anyone can access browser simulation. That includes the config.js and config.xml files which may contain sensitive data.', WpAppKit::i18n_domain ) ?></span>
		</div>
		<?php wp_nonce_field( 'wpak-security-infos-' . $post->ID, 'wpak-nonce-security-infos' ) ?>
		<?php
	}

	public static function remove_quick_edit( $actions ) {
		global $post;
		if ( $post->post_type == 'wpak_apps' ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	public static function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( empty( $_POST['post_type'] ) || $_POST['post_type'] != 'wpak_apps' ) {
			return;
		}

		if ( !current_user_can( 'edit_post', $post_id ) && !current_user_can( 'wpak_edit_apps', $post_id ) ) {
			return;
		}

		if ( !check_admin_referer( 'wpak-main-infos-' . $post_id, 'wpak-nonce-main-infos' ) || !check_admin_referer( 'wpak-phonegap-infos-' . $post_id, 'wpak-nonce-phonegap-infos' ) || !check_admin_referer( 'wpak-security-infos-' . $post_id, 'wpak-nonce-security-infos' ) || !check_admin_referer( 'wpak-deep-linking-' . $post_id, 'wpak-nonce-deep-linking' )
		) {
			return;
		}

		if ( isset( $_POST['wpak_app_name'] ) ) {
			update_post_meta( $post_id, '_wpak_app_name', sanitize_text_field( $_POST['wpak_app_name'] ) );
		}

		if ( isset( $_POST['wpak_app_phonegap_id'] ) ) {
			update_post_meta( $post_id, '_wpak_app_phonegap_id', sanitize_text_field( $_POST['wpak_app_phonegap_id'] ) );
		}

		if ( isset( $_POST['wpak_app_desc'] ) ) {
			update_post_meta( $post_id, '_wpak_app_desc', sanitize_text_field( $_POST['wpak_app_desc'] ) );
		}

		if ( isset( $_POST['wpak_app_version'] ) ) {
			$app_version = self::sanitize_app_version( $_POST['wpak_app_version'] );
			update_post_meta( $post_id, '_wpak_app_version', $app_version );
		}

		if ( isset( $_POST['wpak_app_version_code'] ) ) {
			update_post_meta( $post_id, '_wpak_app_version_code', sanitize_text_field( $_POST['wpak_app_version_code'] ) );
		}

		if ( isset( $_POST['wpak_app_target_architecture'] ) ) {
			update_post_meta( $post_id, '_wpak_app_target_architecture', sanitize_text_field( $_POST['wpak_app_target_architecture'] ) );
		}

		if ( isset( $_POST['wpak_app_build_tool'] ) ) {
			update_post_meta( $post_id, '_wpak_app_build_tool', sanitize_text_field( $_POST['wpak_app_build_tool'] ) );
		}

		if ( isset( $_POST['wpak_app_phonegap_version'] ) ) {
			update_post_meta( $post_id, '_wpak_app_phonegap_version', sanitize_text_field( $_POST['wpak_app_phonegap_version'] ) );
		}

		if ( isset( $_POST['wpak_app_platform'] ) ) {
			update_post_meta( $post_id, '_wpak_app_platform', sanitize_text_field( $_POST['wpak_app_platform'] ) );
		}

		if ( isset( $_POST['wpak_app_author'] ) ) {
			update_post_meta( $post_id, '_wpak_app_author', sanitize_text_field( $_POST['wpak_app_author'] ) );
		}

		if ( isset( $_POST['wpak_app_author_website'] ) ) {
			update_post_meta( $post_id, '_wpak_app_author_website', sanitize_text_field( $_POST['wpak_app_author_website'] ) );
		}

		if ( isset( $_POST['wpak_app_author_email'] ) ) {
			update_post_meta( $post_id, '_wpak_app_author_email', sanitize_text_field( $_POST['wpak_app_author_email'] ) );
		}

		if ( isset( $_POST['wpak_app_phonegap_plugins'] ) ) {
			$phonegap_plugins = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $_POST['wpak_app_phonegap_plugins'] );
			update_post_meta( $post_id, '_wpak_app_phonegap_plugins', trim( $phonegap_plugins ) );
		}

		if ( isset( $_POST['wpak_app_icons'] ) ) {
			$app_icons = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $_POST['wpak_app_icons'] );
			$app_icons = trim( $app_icons );
			update_post_meta( $post_id, '_wpak_app_icons', $app_icons );

			//Use default app icons and splash only if none is provided manually:
			if ( empty( $app_icons ) ) {
				//App that have no existent '_wpak_use_default_icons_and_splash' meta must
				//be considered as using the default icons and splash. So it is important
				//that we set it to 'off' and not delete the meta.
				$use_default = !empty( $_POST['wpak_use_default_icons_and_splash'] ) ? 'on' : 'off';
				update_post_meta( $post_id, '_wpak_use_default_icons_and_splash', $use_default );
			} else {
				update_post_meta( $post_id, '_wpak_use_default_icons_and_splash', 'off' );
			}

		}

		if ( isset( $_POST['wpak_app_simulation_secured'] ) ) {
			update_post_meta( $post_id, '_wpak_app_simulation_secured', sanitize_text_field( $_POST['wpak_app_simulation_secured'] ) );
		}

		if ( isset( $_POST['wpak_app_url_scheme'] ) ) {
			update_post_meta( $post_id, '_wpak_app_url_scheme', sanitize_text_field( $_POST['wpak_app_url_scheme'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_path'] ) ) {
			update_post_meta( $post_id, '_wpak_app_pwa_path', sanitize_text_field( $_POST['wpak_app_pwa_path'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_name'] ) ) {
			update_post_meta( $post_id, '_wpak_app_pwa_name', sanitize_text_field( $_POST['wpak_app_pwa_name'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_short_name'] ) ) {
			update_post_meta( $post_id, '_wpak_app_pwa_short_name', sanitize_text_field( $_POST['wpak_app_pwa_short_name'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_desc'] ) ) {
			update_post_meta( $post_id, '_wpak_app_pwa_desc', sanitize_text_field( $_POST['wpak_app_pwa_desc'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_background_color'] ) ) {
			update_post_meta( $post_id, '_wpak_app_pwa_background_color', sanitize_text_field( $_POST['wpak_app_pwa_background_color'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_theme_color'] ) ) {
			update_post_meta( $post_id, '_wpak_app_pwa_theme_color', sanitize_text_field( $_POST['wpak_app_pwa_theme_color'] ) );
		}

		if ( isset( $_POST['wpak_app_pwa_version'] ) ) {
			$app_version = self::sanitize_app_version( $_POST['wpak_app_pwa_version'] );
			update_post_meta( $post_id, '_wpak_app_pwa_version', $app_version );
		}
	}

	/**
	 * Checks that the app version is in the 1.2.3 format.
	 *
	 * @param string $app_version_raw App version to sanitize
	 * @return string Sanitized app version
	 */
	public static function sanitize_app_version( $app_version_raw ) {
		$app_version = array();
		$app_version_raw = sanitize_text_field( $app_version_raw );
		$app_version_split = explode( '.', $app_version_raw );
		foreach ( $app_version_split as $version_part ) {
			if ( is_numeric( $version_part ) ) {
				$app_version[] = intval( $version_part );
			}
		}
		$app_version = implode( '.', $app_version );
		return $app_version;
	}

	public static function get_platforms() {
		return array(
			'ios' => __( 'iOS - Native', WpAppKit::i18n_domain ),
			'android' => __( 'Android - Native', WpAppKit::i18n_domain ),
			'pwa' => __( 'Progressive Web App', WpAppKit::i18n_domain ),
		);
	}

	public static function get_apps() {
		$args = array(
			'post_type' => 'wpak_apps',
			'post_status' => 'publish',
			'numberposts' => -1
		);

		$apps_raw = get_posts( $args );

		$apps = array();
		foreach ( $apps_raw as $app ) {
			$apps[$app->ID] = $app;
		}

		return $apps;
	}

	public static function get_app( $app_id_or_slug, $no_meta = false ) {
		$app = null;

		$app_id = self::get_app_id( $app_id_or_slug );

		if ( !empty( $app_id ) ) {
			$app = get_post( $app_id );
			if ( !$no_meta ) {
				if ( !empty( $app ) ) {
					$app->main_infos = self::get_app_main_infos( $app_id );
					$app->components = WpakComponents::get_app_components( $app_id );
					$app->navigation = WpakNavigation::get_app_navigation( $app_id );
				} else {
					$app = null;
				}
			}
		}

		return $app;
	}

	public static function app_exists( $app_id_or_slug ) {
		return self::get_app_id( $app_id_or_slug ) != 0;
	}

	public static function get_app_id( $app_id_or_slug ) {

		if ( is_numeric( $app_id_or_slug ) ) {
			return intval( $app_id_or_slug );
		}

		$args = array(
			'name' => $app_id_or_slug,
			'post_type' => 'wpak_apps',
			'post_status' => 'publish',
			'numberposts' => 1
		);

		$apps = get_posts( $args );

		return !empty( $apps ) ? $apps[0]->ID : 0;
	}

	public static function get_app_slug( $app_id_or_slug ) {
		$app = self::get_app( $app_id_or_slug, true );
		return !empty( $app ) ? $app->post_name : '';
	}

	public static function get_app_main_infos( $post_id ) {
		$platform = get_post_meta( $post_id, '_wpak_app_platform', true );
		$title = get_post_meta( $post_id, '_wpak_app_title', true ); //handled in WpakThemesBoSettings
		$app_phonegap_id = get_post_meta( $post_id, '_wpak_app_phonegap_id', true );
		$name = get_post_meta( $post_id, '_wpak_app_name', true );
		$desc = get_post_meta( $post_id, '_wpak_app_desc', true );
		$version = get_post_meta( $post_id, '_wpak_app_version', true );
		$version_code = get_post_meta( $post_id, '_wpak_app_version_code', true );
		$phonegap_version = get_post_meta( $post_id, '_wpak_app_phonegap_version', true );
		$author = get_post_meta( $post_id, '_wpak_app_author', true );
		$author_website = get_post_meta( $post_id, '_wpak_app_author_website', true );
		$author_email = get_post_meta( $post_id, '_wpak_app_author_email', true );
		$icons = get_post_meta( $post_id, '_wpak_app_icons', true );
		$url_scheme = get_post_meta( $post_id, '_wpak_app_url_scheme', true );

		$use_default_icons_and_splash = get_post_meta( $post_id, '_wpak_use_default_icons_and_splash', true );
		$use_default_icons_and_splash = ( empty( $use_default_icons_and_splash ) && empty( $icons ) ) || $use_default_icons_and_splash === 'on';

        $target_architecture = get_post_meta( $post_id, '_wpak_app_target_architecture', true );
		$target_architecture = empty( $target_architecture ) ? 'arm' : $target_architecture; //Set amd as default Android build type

		$pwa_path = get_post_meta( $post_id, '_wpak_app_pwa_path', true );

		$pwa_name = get_post_meta( $post_id, '_wpak_app_pwa_name', true );
		$pwa_name = empty( $pwa_name ) ? empty( $title ) ? '' : $title : $pwa_name;
		$pwa_short_name = get_post_meta( $post_id, '_wpak_app_pwa_short_name', true );
		$pwa_short_name = empty( $pwa_short_name ) ? $pwa_name : $pwa_short_name;

		$pwa_description = get_post_meta( $post_id, '_wpak_app_pwa_desc', true );

		$pwa_icons = WpakThemes::get_pwa_icons( WpakThemesStorage::get_current_theme( $post_id ) );

		$pwa_background_color = get_post_meta( $post_id, '_wpak_app_pwa_background_color', true );
		if( empty( $pwa_background_color ) ) {
			$pwa_background_color = '#65c4ee'; // Default background color
		}
		$pwa_theme_color = get_post_meta( $post_id, '_wpak_app_pwa_theme_color', true );
		if( empty( $pwa_theme_color ) ) {
			$pwa_theme_color = '#122e4f'; // Default theme color
		}

		$pwa_version = get_post_meta( $post_id, '_wpak_app_pwa_version', true );

		$build_tool = get_post_meta( $post_id, '_wpak_app_build_tool', true );
		$build_tool = empty( $build_tool ) ? 'gradle' : $build_tool; //Set gradle as default Android build tool

		$phonegap_plugins = '';
		if ( metadata_exists( 'post', $post_id, '_wpak_app_phonegap_plugins' ) ) {
			$phonegap_plugins = get_post_meta( $post_id, '_wpak_app_phonegap_plugins', true );
		}

		return array(
			'title' => $title,
			'name' => $name,
			'app_phonegap_id' => $app_phonegap_id,
			'desc' => $desc,
			'version' => $version,
			'version_code' => $version_code,
			'target_architecture' => $target_architecture,
			'build_tool' => $build_tool,
			'phonegap_version' => $phonegap_version,
			'platform' => !empty( $platform ) ? $platform : '',
			'author' => $author,
			'author_website' => $author_website,
			'author_email' => $author_email,
			'phonegap_plugins' => $phonegap_plugins,
			'icons' => $icons,
			'use_default_icons_and_splash' => $use_default_icons_and_splash,
			'url_scheme' => $url_scheme,
			'pwa_path' => !empty( $pwa_path ) ? $pwa_path : WpakBuild::get_default_pwa_path( $post_id ) .'/'. self::get_app_slug( $post_id ),
			'pwa_icons' => $pwa_icons,
			'pwa_name' => $pwa_name,
			'pwa_short_name' => $pwa_short_name,
			'pwa_desc' => !empty( $pwa_description ) ? $pwa_description : '',
			'pwa_use_default_icons_and_splash' => empty( $pwa_icons ),
			'pwa_background_color' => $pwa_background_color,
			'pwa_theme_color' => $pwa_theme_color,
			'pwa_version' => $pwa_version
		);
	}

	public static function get_app_info( $post_id, $info ) {
		$main_infos = self::get_app_main_infos( $post_id );
		return isset( $main_infos[$info] ) ? $main_infos[$info] : null;
	}

	public static function get_app_is_secured( $post_id ) {
		return apply_filters( 'wpak_app_secured', true );
	}

	public static function get_app_simulation_is_secured( $post_id ) {
		$secured_raw = get_post_meta( $post_id, '_wpak_app_simulation_secured', true );
		$secured_raw = $secured_raw === '' || $secured_raw === false ? 1 : $secured_raw;
		return intval( $secured_raw ) == 1;
	}

	/**
	 * Add/merge WP-AppKit default Phonegap Build plugins to those set in BO and return
	 * them as config.xml ready XML.
	 *
	 * @param int $app_id Application ID
	 * @param string $bo_plugins_xml Optional. Pass this if the BO plugins XML has already be computed.
	 * @return string Merged BO and default plugins XML.
	 */
	public static function get_merged_phonegap_plugins_xml( $app_id, $export_type, $bo_plugins_xml = '' ) {

		$merged_plugins = self::get_merged_phonegap_plugins( $app_id, $export_type, $bo_plugins_xml );

		return self::get_plugins_xml($merged_plugins);
	}

	public static function get_merged_phonegap_plugins( $app_id, $export_type, $bo_plugins_xml = '' ) {
		if ( empty( $bo_plugins_xml ) ) {
			$app_main_infos = WpakApps::get_app_main_infos( $app_id );
			$bo_plugins_xml = $app_main_infos['phonegap_plugins'];
		}

		$bo_plugins_array = self::parse_plugins_from_xml( $bo_plugins_xml );

		$merged_plugins = array_merge( self::get_default_phonegap_plugins( $app_id, $export_type ), $bo_plugins_array );

		return $merged_plugins;
	}

	public static function is_crosswalk_activated( $app_id ) {
		/**
		 * Crosswalk is deactivated by default as of WP-AppKit version 1.5.2.
		 * Use this 'wpak_crosswalk_activated' filter to reactivate it: 
		 * Usage example: add_filter( 'wpak_crosswalk_activated', '__return_true' );
		 */
		return apply_filters( 'wpak_crosswalk_activated', false, $app_id );
	}

	protected static function get_default_phonegap_plugins( $app_id, $export_type = 'phonegap-build' ) {

		$default_plugins = array(
			'cordova-plugin-inappbrowser' => array( 'spec' => '', 'source' => 'npm' ),
			'cordova-plugin-network-information' => array( 'spec' => '', 'source' => 'npm' ),
			'cordova-plugin-whitelist' => array( 'spec' => '', 'source' => 'npm' ),
			'cordova-plugin-splashscreen' => array( 'spec' => '', 'source' => 'npm' ),
			'cordova-plugin-device' => array( 'spec' => '', 'source' => 'npm' ),
			'cordova-plugin-statusbar' => array( 'spec' => '', 'source' => 'npm' ),
		);

		$app_main_infos = WpakApps::get_app_main_infos( $app_id );
		if( $app_main_infos['platform'] == 'ios' ) {
			if ( $export_type == 'phonegap-build' ) {
				unset( $default_plugins['cordova-plugin-whitelist'] );
			}
		}

		if( $app_main_infos['platform'] == 'android' ) {

			if ( self::is_crosswalk_activated( $app_id ) ) {
				// Add CrossWalk Cordova plugin.
				// This is useful to have a consistent behaviour between all Android webviews, and to have better performance as well. Especially with animations.
				// Drawbacks are the app's weight and memory footprint that are higher than without the plugin.
				// Currently we include stable version 2.3.0 which is the one supported by the current version of PhoneGap Build (v6.50):
				$default_plugins['cordova-plugin-crosswalk-webview'] = array( 'spec' => '2.3.0', 'source' => 'npm' );
			}

			//Add "cordova-build-architecture" plugin
			//https://github.com/MBuchalik/cordova-build-architecture
			//This is to allow to choose between ARM/x86 compilation, as both ARM and x86 APK are needed to release apps on PlayStore.
			//See https://github.com/uncatcrea/wp-appkit/issues/275 and https://github.com/uncatcrea/wp-appkit/issues/322
			$default_plugins['cordova-build-architecture'] = array( 'spec' => 'https://github.com/MBuchalik/cordova-build-architecture.git#v1.0.1', 'source' => 'git' );
		}

		// Activate Deep Linking if a Custom URL Scheme is present
		if( !empty( $app_main_infos['url_scheme'] ) ) {
			$default_plugins['cordova-plugin-customurlscheme'] = array(
				'spec' => '4.2.0',
				'params' => array(
					array(
						'name' => 'URL_SCHEME',
						'value' => $app_main_infos['url_scheme'],
					),
				),
			);
		}

		/**
		 * Filter the Phonegap Build plugins that are included by default by WP-AppKit
		 *
		 * @param array		$default_plugins	Array of default Phonegap plugins.
		 * @param string    $export_type        Export type : 'phonegap-build', 'phonegap-cli' or 'webapp'
		 * @param int		$app_id				Application id
		 */
		$default_plugins = apply_filters( 'wpak_default_phonegap_build_plugins', $default_plugins, $export_type, $app_id );

		return $default_plugins;
	}

	protected static function get_plugins_xml( $plugins ) {
		$plugins_xml = '';

		if ( is_array( $plugins ) ) {
			$plugins_xml_array = array();
			foreach ( $plugins as $plugin_name => $plugin_data ) {
				$plugin_xml = '<plugin name="' . $plugin_name . '"';
				$xml_end = ' />';
				if ( !empty( $plugin_data['spec'] ) ) {
					$plugin_xml .= ' spec="'. $plugin_data['spec'] .'"';
				}
				if ( !empty( $plugin_data['source'] ) ) {
					$plugin_xml .= ' source="'. $plugin_data['source'] .'"';
				}
				if( !empty( $plugin_data['params'] ) ) {
					$param_xml = array();
					foreach( $plugin_data['params'] as $param ) {
						if( !isset( $param['name'] ) || !isset( $param['value'] ) ) {
							continue;
						}
						$param_xml[] = '<param name="' . $param['name'] . '" value="' . $param['value'] . '" />';
					}
					$plugin_xml.= " >\n\t" . implode( "\n\t", $param_xml ) . "\n";
					$xml_end = '</plugin>';
				}
				$plugin_xml .= $xml_end;
				$plugins_xml_array[] = $plugin_xml;
			}
			$plugins_xml = implode( "\n", $plugins_xml_array );
		}

		return $plugins_xml;
	}

	protected static function parse_plugins_from_xml( $plugins_xml ) {
		$plugins_array = array();

		if ( preg_match_all( '/(<(gap:)?plugin [^>]+)(\/>|>(.*)<\/(gap:)?plugin>)/sU', $plugins_xml, $matches ) ) {
			foreach ( $matches[1] as $i => $match ) {
				$spec = '';
				$source = '';
				if ( preg_match( '/name="([^"]+)"/', $match, $name_match ) && strlen( $name_match[1] ) > 0 ) {
					if ( preg_match( '/(version|spec)="([^"]+)"/', $match, $version_match ) && strlen( $version_match[2] ) > 0 ) {
						$spec = $version_match[2];
					}
					if ( preg_match( '/source="([^"]+)"/', $match, $source_match ) && strlen( $source_match[1] ) > 0 ) {
						$source = $source_match[1];
					}

					// Include params if any
					$params = array();
					if( !empty( $matches[4][$i] ) && preg_match_all( '/<param ([^>]+)>/U', $matches[4][$i], $param_matches ) ) {
						foreach( $param_matches[1] as $param_match ) {
							if( preg_match( '/name="([^"]+)"/', $param_match, $param_name_match ) && strlen( $param_name_match[1] ) > 0
							 && preg_match( '/value="([^"]+)"/', $param_match, $param_value_match ) && strlen( $param_value_match[1] ) > 0 ) {
								$params[$param_name_match[1]] = array(
									'name' => $param_name_match[1],
									'value' => $param_value_match[1],
								);
							}
						}
					}

					$plugins_array[$name_match[1]] = array(
						'spec' => $spec,
						'source' => $source,
						'params' => array_values( $params ),
					);
				}
			}
		}

		return $plugins_array;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public static function sanitize_pwa_path( $path ) {
		// Remove unwanted characters from the beginning of the path, to avoid values like '../../whatever'
		$path = ltrim( $path, './\\' );

		$realpath = self::realpath( ABSPATH . '/' . $path );

		if(
			strpos( $realpath, untrailingslashit( ABSPATH ) ) === false || // Avoid paths like 'folder/../../../whatever' by ensuring the value is under ABSPATH
			strpos( $path, 'wp-admin' ) === 0 || // WordPress core directories are forbidden
			strpos( $path, 'wp-includes' ) === 0
		) {
			return ''; // An empty value will lead to a default one
		}

		return $path;
	}

	/**
	 * A replacement to PHP's realpath to handle non-existent paths as well.
	 *
	 * @param string $value
	 *
	 * @return bool|string
	 */
	public static function realpath( $value ) {
		$path     = (string) $value;
		$realpath = @realpath( $path );
		if( $realpath ) {
			return $realpath;
		}
		$drive = '';
		if( substr( PHP_OS, 0, 3 ) == 'WIN' ) {
			$path = preg_replace( '/[\\\\\/]/', DIRECTORY_SEPARATOR, $path );
			if( preg_match( '/([a-zA-Z]\:)(.*)/', $path, $matches ) ) {
				list( $fullMatch, $drive, $path ) = $matches;
			} else {
				$cwd   = getcwd();
				$drive = substr( $cwd, 0, 2 );
				if( substr( $path, 0, 1 ) != DIRECTORY_SEPARATOR ) {
					$path = substr( $cwd, 3 ) . DIRECTORY_SEPARATOR . $path;
				}
			}
		} elseif( substr( $path, 0, 1 ) != DIRECTORY_SEPARATOR ) {
			$path = getcwd() . DIRECTORY_SEPARATOR . $path;
		}
		$stack = array();
		$parts = explode( DIRECTORY_SEPARATOR, $path );
		foreach( $parts as $dir ) {
			if( strlen( $dir ) && $dir !== '.' ) {
				if( $dir == '..' ) {
					array_pop( $stack );
				} else {
					array_push( $stack, $dir );
				}
			}
		}

		return $drive . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $stack );
	}

	/**
	 * @param int|WP_Post $post
	 *
	 * @return bool
	 */
	public static function isSaved( $post ) {
		$post = get_post( $post );

		return in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) && 0 != $post->ID;
	}

}

WpakApps::hooks();
