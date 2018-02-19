<?php

class WpakComponentsBoSettings {

	public static function hooks() {
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			add_action( 'wp_ajax_wpak_update_component_type', array( __CLASS__, 'ajax_update_component_type' ) );
			add_action( 'wp_ajax_wpak_update_component_options', array( __CLASS__, 'ajax_update_component_options' ) );
			add_action( 'wp_ajax_wpak_edit_component', array( __CLASS__, 'ajax_wpak_edit_component' ) );
		}
	}

	public static function admin_enqueue_scripts() {
		global $pagenow, $typenow;
		if ( ($pagenow == 'post.php' || $pagenow == 'post-new.php') && $typenow == 'wpak_apps' ) {
			global $post;
			wp_enqueue_script( 'wpak_components_bo_settings_js', plugins_url( 'lib/components/components-bo-settings.js', dirname( dirname( __FILE__ ) ) ), array( 'jquery' ), WpAppKit::resources_version );
			wp_localize_script( 'wpak_components_bo_settings_js', 'wpak_components', array(
				'post_id' => $post->ID,
				'nonce' => wp_create_nonce( 'wpak-component-data-' . $post->ID ),
				'display_modif_alerts' => WpakSettings::get_setting( 'activate_wp_appkit_app_modif_alerts' ),
				'messages' => array(
					'confirm_delete' => __( 'Deleting a component will remove it from all existing instances of your app (even those already built and running on real phones). Are you sure you want to delete this component?', WpAppKit::i18n_domain ),
					'confirm_edit' => __( 'Modifying a component will affect it on all existing instances of your app (even those already built and running on real phones). Are you sure you want to modify this component?', WpAppKit::i18n_domain ),
					'confirm_add' => __( 'Creating a component will create it on all existing instances of your app (even those already built and running on real phones). Are you sure you want to create this component?', WpAppKit::i18n_domain ),
				)
			) );
		}
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'wpak_app_components',
			__( 'Components', WpAppKit::i18n_domain ),
			array( __CLASS__, 'inner_components_box' ),
			'wpak_apps',
			'normal',
			'default'
		);
	}

	public static function inner_components_box( $post, $current_box ) {
		$components = WpakComponentsStorage::get_components( $post->ID );
		?>
		<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
		<p class="description"><?php _e( 'Click Add New to add the different components that compose your app. Most components are data sources and correspond to app\'s screens. They can be referenced in the app\'s menu.', WpAppKit::i18n_domain ) ?></p>

		<div id="components-wrapper">

			<a href="#" class="add-new-h2" id="add-new-component"><?php _ex( 'Add New', 'Add new component', WpAppKit::i18n_domain ); ?></a>

			<div id="components-feedback" style="display:none"></div>

			<div id="new-component-form" style="display:none">
				<h4><?php _e( 'New Component', WpAppKit::i18n_domain ) ?></h4>
				<?php self::echo_component_form( $post->ID ) ?>
			</div>

			<table id="components-table" class="wp-list-table widefat fixed" >
				<thead>
					<tr>
						<th><?php _e( 'Name', WpAppKit::i18n_domain ) ?></th>
						<th><?php _e( 'Slug', WpAppKit::i18n_domain ) ?></th>
						<th><?php _e( 'Type', WpAppKit::i18n_domain ) ?></th>
						<th><?php _e( 'Options', WpAppKit::i18n_domain ) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( !empty( $components ) ): ?>
						<?php $i = 0 ?>
						<?php foreach ( $components as $id => $component ): ?>
							<?php echo self::get_component_row( $post->ID, $i++, $id, $component ) ?>
						<?php endforeach ?>
					<?php else: ?>
						<tr class="no-component-yet"><td colspan="4"><?php _e( 'No Component yet!', WpAppKit::i18n_domain ) ?></td></tr>
					<?php endif ?>
				</tbody>
			</table>
			
			<?php 
				/**
				 * 'wpak_components_list_after' action.
				 * Use this action to insert custom content after components list
				 * 
				 * @param    array    $components    List of components
				 * @param    int      $app_id        Application id
				 */
				do_action( 'wpak_components_list_after', $components, $post->ID ); 
			?>

			<?php WpakComponentsTypes::echo_components_javascript() ?>

		</div>

		<?php // TODO: Use an external CSS file ?>

		<style>
			#components-wrapper{ margin-top:1em }
			#components-table{ margin-top:5px }
			#new-component-form{ margin-bottom: 4em }
			#components-wrapper #components-feedback{ margin-top:15px; margin-bottom:17px; padding-top:12px; padding-bottom:12px; }
		</style>

		<?php
	}

	private static function get_component_row( $post_id, $i, $component_id, WpakComponent $component ) {
		$alternate_class = $i % 2 ? '' : 'alternate';
		$error_class = '';
		$label = WpakComponentsTypes::get_label( $component->type );

		//
		// Component type could be unknown if an addon's component has been added to the app and the addon isn't activated anymore
		// An addon could be seen as deactivated either if the corresponding plugin is deactivated, or if the corresponding checkbox is unchecked for the given app
		//

		if( !WpakComponentsTypes::component_type_exists( $component->type ) ) {
			$error_class =  ' error';
			$label = __( 'Component type doesn\'t exist, this component won\'t be included into the app', WpAppKit::i18n_domain );
		}

		ob_start();
		?>
		<tr class="component-row <?php echo $alternate_class . $error_class ?>" id="component-row-<?php echo $component_id ?>">
			<td>
				<?php echo esc_html( $component->label ) ?>
				<div class="row-actions">
					<span class="inline hide-if-no-js"><a class="editinline" href="#" data-edit-id="<?php echo esc_attr( $component_id ) ?>"><?php _e( 'Edit', WpAppKit::i18n_domain ) ?></a> | </span>
					<span class="trash"><a class="submitdelete delete_component" href="#" data-post-id="<?php echo esc_attr( $post_id ) ?>" data-id="<?php echo esc_attr( $component_id ) ?>"><?php _e( 'Delete', WpAppKit::i18n_domain ) ?></a></span>
				</div>
			</td>
			<td><?php echo esc_html( $component->slug ) ?></td>
			<td><?php echo esc_html( $label ) ?></td>
			<td>
				<?php $options = WpakComponentsTypes::get_options_to_display( $component ) ?>
				<?php foreach ( $options as $option ): ?>
					<?php echo esc_html( $option['label'] ) ?> : <?php echo esc_html( $option['value'] ) ?><br/>
				<?php endforeach ?>
			</td>
		</tr>
		<tr class="edit-component-wrapper" id="edit-component-wrapper-<?php echo esc_attr( $component_id ) ?>" style="display:none" <?php echo $alternate_class ?>>
			<td colspan="4">
				<?php self::echo_component_form( $post_id, $component ) ?>
			</td>
		</tr>
		<?php
		$component_row_html = ob_get_contents();
		ob_end_clean();
		return $component_row_html;
	}

	private static function echo_component_form( $post_id, $component = null ) {

		$edit = !empty( $component );

		if ( !$edit ) {
			$component = new WpakComponent( '', '', 'posts-list' );
		}

		$component_id = $edit ? WpakComponentsStorage::get_component_id( $post_id, $component ) : '0';

		$components_types = WpakComponentsTypes::get_available_components_types();

		?>
		<div id="component-form-<?php echo esc_attr( $component_id ) ?>" class="component-form">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Component label', WpAppKit::i18n_domain ) ?></th>
					<td>
						<input class="can-reset" type="text" name="component_label" value="<?php echo esc_attr( $component->label ) ?>" />
						<?php 
							/**
							 * 'wpak_component_form_label' action
							 * Use this action to display something after component's label field.
							 * 
							 * @param   int      $app_id      Application id
							 * @param   bool     $edit        Whether editing or creating the component
							 * @param   object   $component   Component object
							 */
							do_action( 'wpak_component_form_label', $post_id, $edit, $component ); 
						?>
					</td>
				</tr>
				<?php if ( $edit ): ?>
					<tr valign="top">
						<th scope="row"><?php _e( 'Component slug', WpAppKit::i18n_domain ) ?></th>
						<td><input class="can-reset" type="text" name="component_slug" value="<?php echo esc_attr( $component->slug ) ?>" /></td>
					</tr>
				<?php endif ?>
				<tr valign="top">
					<th scope="row"><?php _e( 'Component type', WpAppKit::i18n_domain ) ?></th>
					<td>
						<select type="text" name="component_type" class="component-type">
							<?php foreach ( $components_types as $type => $data ): ?>
								<?php $selected = $type == $component->type ? 'selected="selected"' : '' ?>
								<option value="<?php echo esc_attr( $type ) ?>" <?php echo $selected ?> ><?php echo esc_html( $data['label'] ) ?></option>
							<?php endforeach ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Component options', WpAppKit::i18n_domain ) ?></th>
					<td class="component-options-target">
						<?php WpakComponentsTypes::echo_form_fields( $component->type, $edit ? $component : null ) ?>
					</td>
				</tr>
			</table>
			<input type="hidden" name="component_id" value="<?php echo esc_attr( $component_id ) ?>"/>
			<input type="hidden" name="component_post_id" value="<?php echo esc_attr( $post_id ) ?>" />
			<p class="submit">
				<span class="spinner"></span>
				<a class="button-secondary alignleft cancel" title="<?php _e( 'Cancel', WpAppKit::i18n_domain ) ?>" href="#" <?php echo!$edit ? 'id="cancel-new-component"' : '' ?>><?php _e( 'Cancel', WpAppKit::i18n_domain ) ?></a>&nbsp;
				<a class="button button-primary component-form-submit" data-id="<?php echo esc_attr( $component_id ) ?>"><?php echo $edit ? __( 'Save Changes', WpAppKit::i18n_domain ) : 'Save new component' ?></a>
			</p>
		</div>
		<?php
	}

	public static function ajax_update_component_options() {

		if ( empty( $_POST['post_id'] ) || empty( $_POST['nonce'] ) || !check_admin_referer( 'wpak-component-data-' . $_POST['post_id'], 'nonce' ) ) {
			exit();
		}

		$component_type = sanitize_key( $_POST['component_type'] );
		$action = sanitize_key( $_POST['wpak_action'] );
		$params = is_array( $_POST['params'] ) ? array_map( 'sanitize_key', $_POST['params'] ) : array();

		WpakAddons::require_app_addons_php_files( intval($_POST['post_id']) );

		echo WpakComponentsTypes::get_ajax_action_html_answer( $component_type, $action, $params );
		exit();
	}

	public static function ajax_update_component_type() {

		$component_type = sanitize_key( $_POST['component_type'] );

		if ( empty( $_POST['post_id'] ) || empty( $_POST['nonce'] ) || !check_admin_referer( 'wpak-component-data-' . $_POST['post_id'], 'nonce' ) ) {
			exit();
		}

		WpakAddons::require_app_addons_php_files( intval($_POST['post_id']) );

		WpakComponentsTypes::echo_form_fields( $component_type );
		exit();
	}

	public static function ajax_wpak_edit_component() {

		$answer = array( 'ok' => 0, 'message' => '', 'type' => 'error', 'html' => '', 'component' => array() );

		if ( empty( $_POST['post_id'] ) || empty( $_POST['nonce'] ) || !check_admin_referer( 'wpak-component-data-' . $_POST['post_id'], 'nonce' ) ) {
			exit( 'bad nonce' );
		}

		$action = sanitize_key( $_POST['wpak_action'] );
		$data = is_array( $_POST['data'] ) ? $_POST['data'] : array(); //Each data value is sanitized hereunder before being used

		WpakAddons::require_app_addons_php_files( intval($_POST['post_id']) );

		if ( $action == 'add_or_update' ) {

			// Unslash POST data before manipulating DB
			$data = wp_unslash( $data );

			$post_id = intval( $data['component_post_id'] );

			if ( empty( $post_id ) ) {
				$answer['message'] = __( "Application not found.", WpAppKit::i18n_domain );
				self::exit_sending_json( $answer );
			}

			$edit = !empty( $data['component_id'] );
			$edit_id = $edit ? intval( $data['component_id'] ) : 0;

			$component_label = sanitize_text_field( trim( $data['component_label'] ) );
			$component_type = sanitize_key( $data['component_type'] );
			
			/**
			 * 'wpak_default_component_label' filter
			 * Allows to customize posted component label. Useful to allow default label value
			 * in case no label was entered by the user.
			 * 
			 * @param   string   $component_label   Component label sent by the user
			 * @param   array    $data              Data submited by the user
			 * @param   bool     $edit              Whether editing or creating the component
			 * @param   int      $edit_id           Component id for edition
			 */
			$component_label = apply_filters( 'wpak_default_component_label', $component_label, $data, $edit, $edit_id );

			if ( empty( $component_label ) ) {
				$answer['message'] = __( 'You must provide a label for the component!', WpAppKit::i18n_domain );
				self::exit_sending_json( $answer );
			}

			$component_slug = $edit ? trim( $data['component_slug'] ) : ( !is_numeric( $component_label ) ? $component_label : 'slug-'. $component_label );
			$component_slug = sanitize_title_with_dashes( remove_accents( $component_slug ) );

			if ( empty( $component_slug ) ) {
				$answer['message'] = __( "You must provide a slug for the component.", WpAppKit::i18n_domain );
				self::exit_sending_json( $answer );
			}

			if ( is_numeric( $component_slug ) ) {
				$answer['message'] = __( "The component slug can't be numeric.", WpAppKit::i18n_domain );
				self::exit_sending_json( $answer );
			}

			if ( WpakComponentsStorage::component_exists( $post_id, $component_slug, $edit_id ) ) {
				$i = 0;
				do {
					$component_index = intval( preg_replace( '/.*-(\d+)$/', '$1', $component_slug ) );
					$component_index++;
					$component_slug = preg_replace( '/-(\d+)$/', '', $component_slug ) . '-' . $component_index;
					if ( $i++ > 100 ) {
						break;
					}
				} while ( WpakComponentsStorage::component_exists( $post_id, $component_slug, $edit_id ) );
			}

			$component_options = WpakComponentsTypes::get_component_type_options_from_posted_form( $component_type, $data );

			/**
			 * 'wpak_component_save_options' filter
			 * Use this filter to cusomize options saved for the component.
			 * Allows to add any custom fields to component options.
			 * 
			 * @param   array   $component_options   Component options to be customized
			 * @param   array   $data                All component data sent by the user
			 * @param   in      $app_id              Application id
			 * @param   string  $component_slug		 Component slug
			 * @param   string  $component_label     Component label
			 * @param   string  $component_type      Component type
			 * @param   bool    $edit                Whether editing or creating the component
			 * @param   int     $edit_id             Component id for edition
			 */
			$component_options = apply_filters( 'wpak_component_save_options', $component_options, $data, $post_id, $component_slug, $component_label, $component_type, $edit, $edit_id );
			
			$component = new WpakComponent( $component_slug, $component_label, $component_type, $component_options );
			$component_id = WpakComponentsStorage::add_or_update_component( $post_id, $component, $edit_id );

			$answer['component'] = array(
				'id' => $component_id,
				'slug' => $component_slug,
				'label' => $component_label,
			);
			$answer['html'] = self::get_component_row( $post_id, WpakComponentsStorage::get_nb_components( $post_id ), $component_id, $component );

			if ( $edit ) {
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
				$answer['message'] = sprintf( __( 'Component "%s" updated successfuly', WpAppKit::i18n_domain ), $component_label );
			} else {
				$answer['ok'] = 1;
				$answer['type'] = 'updated';
				$answer['message'] = sprintf( __( 'Component "%s" created successfuly', WpAppKit::i18n_domain ), $component_label );
			}

			self::exit_sending_json( $answer );
		} elseif ( $action == 'delete' ) {
			$id = intval( $data['component_id'] );
			$post_id = intval( $data['post_id'] );
			if ( is_numeric( $id ) && is_numeric( $post_id ) ) {
				if ( $component_id = WpakComponentsStorage::component_exists( $post_id, $id ) ) {
					if ( WpakNavigationItemsStorage::navigation_item_exists_by_component( $post_id, $component_id ) ) {
						$answer['message'] = __( 'The component to delete is in the app navigation. Please remove the component from app navigation before deleting it.', WpAppKit::i18n_domain );
					} else {
						if ( !WpakComponentsStorage::delete_component( $post_id, $id ) ) {
							$answer['message'] = __( 'Could not delete component', WpAppKit::i18n_domain );
						} else {
							$answer['ok'] = 1;
							$answer['type'] = 'updated';
							$answer['message'] = __( 'Component deleted successfuly', WpAppKit::i18n_domain );
						}
					}
				} else {
					$answer['message'] = __( 'Component to delete not found', WpAppKit::i18n_domain );
				}
			}
			self::exit_sending_json( $answer );
		}

		//We should not arrive here, but just in case :
		self::exit_sending_json( $answer );
	}

	private static function exit_sending_json( $answer ) {
		if ( !WP_DEBUG ) {
			$content_already_echoed = ob_get_contents();
			if ( !empty( $content_already_echoed ) ) {
				//TODO : allow to add $content_already_echoed in the answer as a JSON data for debbuging
				ob_end_clean();
			}
		}
		header( 'Content-type: application/json' );
		echo json_encode( $answer );
		exit();
	}

}

WpakComponentsBoSettings::hooks();
