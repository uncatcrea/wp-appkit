<?php

class WpakComponentTypePage extends WpakComponentType {

	protected function compute_data( $component, $options, $args = array() ) {
		global $wpdb;

		if ( !empty( $options['page'] ) ) {

			do_action( 'wpak_before_component_page', $component, $options );

			$page = get_post( $options['page'] );

			if ( !empty( $page ) && $page->post_status == 'publish' ) {

				$all_pages_by_ids = array();

				if ( !empty( $options['with_subtree'] ) ) {
					$tree = array();
					self::build_tree( $tree, $page->ID, $page->post_parent );

					$all_pages = new WP_Query( array( 'post__in' => array_keys( $tree ), 'post_type' => 'any', 'posts_per_page' => -1 ) );

					foreach ( $all_pages->posts as $_page ) {
						if ( isset( $tree[$_page->ID] ) ) {
							$all_pages_by_ids[$_page->ID] = self::get_page_data( $component, $_page, $tree[$_page->ID] );
						}
					}

					//Handle internal links:
					$all_pages_ids = array_keys( $all_pages_by_ids );
					foreach ( $all_pages_by_ids as $page_id => $page_data ) {
						$content_with_internal_links = WpakComponentsUtils::handle_internal_links( $page_data->content, $all_pages_ids, array( __CLASS__, 'build_page_link' ), array( $component->slug ) );
						$all_pages_by_ids[$page_id]->content = $content_with_internal_links;
					}

					$this->set_specific( 'is_tree', true );
				} else {
					//Important : Include page tree data here too for consistency with the "with_subtree" case :
					//pages referenced in the tree data wont be  included in the global 'pages' if they
					//are not included in another page tree in the app.
					$all_pages_by_ids = array( $page->ID => self::get_page_data( $component, $page, self::get_page_tree_data( $page->ID, $page->post_parent ) ) );

					$this->set_specific( 'is_tree', false );
				}

				$this->set_specific( 'root_id', $page->ID );

				$root_depth = 0;
				if ( !empty( $all_pages_by_ids ) && isset( $all_pages_by_ids[$page->ID] ) ) {
					$tree_data = $all_pages_by_ids[$page->ID]->tree_data;
					$root_depth = $tree_data[3];
				}

				$this->set_specific( 'root_depth', $root_depth );

				$this->set_globals( 'pages', $all_pages_by_ids );
			}
		}
	}

	protected static function build_tree( &$tree, $page_id, $parent_id, $depth = 0, $ariane = array() ) {
		$tree_data = self::get_page_tree_data( $page_id, $parent_id, $depth, $ariane );
		$siblings = $tree_data[1];
		$children = $tree_data[2];
		$depth = $tree_data[3];
		$ariane = $tree_data[4];

		$tree[$page_id] = array( $parent_id, $siblings, $children, $depth, $ariane );

		if ( !empty( $children ) && $depth < 100 ) { //$depth < 100 to prevent infinite recursion in case of bad page tree.
			foreach ( $children as $child_id ) {
				$child_ariane = $ariane;
				array_push( $child_ariane, $page_id );
				self::build_tree( $tree, $child_id, $page_id, $depth + 1, $child_ariane );
			}
		}
	}

	protected static function get_page_tree_data( $page_id, $parent_id, $depth = 0, $ariane = array() ) {
		$siblings = !empty( $parent_id ) ? self::get_page_children( $parent_id ) : array( $page_id );
		$children = self::get_page_children( $page_id );

		if ( $depth === 0 || $ariane === array() ) {
			$depth_and_ariane = self::get_page_depth_and_ariane( $page_id );
			$depth = $depth_and_ariane[0];
			$ariane = $depth_and_ariane[1];
		}

		return array( $parent_id, $siblings, $children, $depth, $ariane );
	}

	protected static function get_page_depth_and_ariane( $page_id, $depth = 0, $ariane = array() ) {
		global $wpdb;

		$parent_id = $wpdb->get_var( "SELECT post_parent FROM $wpdb->posts WHERE ID='" . intval( $page_id ) . "' LIMIT 1" );
		$parent_id = intval( $parent_id );
		if ( empty( $parent_id ) ) {
			$parent_id = 0;
		}

		if ( $depth > 0 ) { //ariane doesn't include the page itself
			array_unshift( $ariane, $page_id );
		}

		$depth_and_ariane = array( $depth, $ariane );

		if ( $parent_id != 0 && $depth < 100 ) { //$depth < 100 to prevent infinite recursion in case of bad page tree.
			$depth_and_ariane = self::get_page_depth_and_ariane( $parent_id, $depth + 1, $ariane );
		}

		return $depth_and_ariane;
	}

	protected static function get_page_children( $parent_id ) {
		global $wpdb;
		$children = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_status='publish' AND post_parent='" . intval( $parent_id ) . "' ORDER BY menu_order ASC" );
		return !empty( $children ) ? array_map( 'intval', $children ) : array();
	}

	/**
	 * To retrieve only pages given in $items_ids
	 */
	protected function get_items_data( $component, $options, $items_ids, $args = array() ) {
		$items = array( 'pages' => array() );
		
		$posts_by_ids = array();
		foreach ( $items_ids as $post_id ) {
			$post = get_post( $post_id );
			if( !empty($post) && $post->post_status == 'publish' && $post->post_type == 'page' ) {
				$posts_by_ids[$post_id] = self::get_page_data( $component, $post );
			}
		}
		
		$items['pages'] = $posts_by_ids;
		
		return $items;
	}
	
	protected static function get_page_data( $component, $post, $tree_data = array() ) {
		
		$post_data = WpakComponentsUtils::get_post_data( $post, $component );
		
		/**
		 * Filter page content for page components. 
		 * Use this to format app pages content your own way only for page component.
		 *
		 * To apply a custom content to all component types, use the "wpak_post_data_post_content" filter instead.
		 *
		 * @see WpakComponentsUtils::get_formated_content()
		 *
		 * @param string 			$post_content   The default post content.
		 * @param WP_Post 			$post 			The post object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$post_data['content'] = apply_filters( 'wpak_page_content', $post_data['content'], $post, $component );

		$post_data['tree_data'] = !empty( $tree_data ) ? $tree_data : array();
		
		/**
		 * Filter page data sent to the app from a page component.
		 *
		 * Use this for example to add a page meta to the default page data only for page component.
		 *
		 * @param array 			$post_data    	The default page data sent to an app.
		 * @param WP_Post 			$post 			The page object.
		 * @param WpakComponent 	$component		The component object.
		 */
		$post_data = apply_filters( 'wpak_page_data', $post_data, $post, $component );

		return ( object ) $post_data;
	}

	/**
	 * Builds an internal link that point to a page available in the app.
	 * Called in self::compute_data() as a callback of WpakComponentsUtils::handle_internal_links().
	 */
	public static function build_page_link( $page_id, $component_slug ) {
		return '#page/' . $component_slug . '/' . $page_id;
	}

	public function get_options_to_display( $component ) {

		$page = get_post( $component->options['page'] );
		$with_subtree = $component->options['with_subtree'];
		$page_title = !empty( $page ) ? $page->post_title . ' (ID=' . $page->ID . ')' : __( 'Page not found', WpAppKit::i18n_domain );
		$post_type = $component->options['post_type'];

		$options = array(
			'post_type' => array( 'label' => __( 'Post type' ), 'value' => $post_type ),
			'page' => array( 'label' => __( 'Page' ), 'value' => $page_title ),
			'with_subtree' => array( 'label' => __( 'Include sub pages', WpAppKit::i18n_domain ), 'value' => $with_subtree ? __( 'Yes' ) : __( 'No' ) ),
		);

		return $options;
	}

	public function echo_form_fields( $component ) {

		$all_post_types = self::get_hierarchical_post_types();

		$current_post_type = 'page';
		$current_page_id = 0;
		$with_subtree = false;
		if ( !empty( $component ) ) {
			$options = $component->options;
			$current_post_type = !empty( $options['post_type'] ) ? $options['post_type'] : '';
			$current_page_id = !empty( $options['page'] ) ? $options['page'] : 0;
			$with_subtree = !empty( $options['with_subtree'] );
		}

		$current_post_type_object = get_post_type_object( $current_post_type );
		if ( $current_post_type_object == null ) {
			$current_post_type = 'page';
			$current_post_type_object = get_post_type_object( 'page' );
		}

		if ( !empty( $current_page_id ) ) {
			$current_page = get_post( $current_page_id );
			if ( empty( $current_page ) || $current_page->post_type != $current_post_type ) {
				$current_post_type = 'page';
				$current_page_id = 0;
			}
		}
		?>
		<div>
			<label for="post_type"><?php _e( 'Post type', WpAppKit::i18n_domain ) ?> : </label>
			<select id="post_type" name="post_type" class="post-type-list">
				<?php foreach ( $all_post_types as $post_type ): ?>
					<?php $selected = $post_type->name == $current_post_type ? 'selected="selected"' : '' ?>
					<option value="<?php echo esc_attr( $post_type->name ) ?>" <?php echo $selected ?>><?php echo esc_html( $post_type->labels->name ) ?></option>
				<?php endforeach ?>
			</select>
		</div>

		<div class="ajax-target">
			<?php self::echo_sub_options_html( $current_post_type_object, $current_page_id, $with_subtree ) ?>
		</div>

		<?php
	}

	protected static function echo_sub_options_html( $current_post_type_object, $current_page_id = 0, $with_subtree = false ) {
		$pages = get_posts( array( 'post_type' => $current_post_type_object->name, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );

		/**
		 * Filter pages list displayed into a "Page" component select field.
		 *
		 * @param array 			$pages    							The default pages list to display.
		 * @param string 			$current_post_type_object->name 	The name of the post type being displayed.
		 */
		$pages = apply_filters( 'wpak_component_type_page_form_pages', $pages, $current_post_type_object->name );
		?>
		<br/>
		<label for="page_id"><?php _e( $current_post_type_object->labels->name ) ?> : </label>
		<select id="page_id" name="page_id" class="page-pages">
			<?php foreach ( $pages as $page ): ?>
				<?php $selected = $page->ID == $current_page_id ? 'selected="selected"' : '' ?>
				<option value="<?php echo esc_attr( $page->ID ) ?>" <?php echo $selected ?>><?php echo esc_html( $page->post_title ) ?></option>
			<?php endforeach ?>
		</select>
		<br/><br/>
		<label for="with-subtree"><?php _e( 'Include sub pages' ) ?></label>&nbsp;<input type="checkbox" name="with_subtree" <?php echo $with_subtree ? 'checked="checked"' : '' ?> id="with-subtree" />
		<?php
	}

	public function echo_form_javascript() {
		?>
		<script type="text/javascript">
			(function() {
				var $ = jQuery;
				$('.wrap').delegate('.post-type-list', 'change', function() {
					var post_type = $(this).find(":selected").val();
					WpakComponents.ajax_update_component_options(this, 'page', 'change-page-option', {post_type: post_type});
				});
			})();
		</script>
		<?php
	}

	public function get_ajax_action_html_answer( $action, $params ) {
		switch ( $action ) {
			case 'change-page-option':
				$post_type = $params['post_type'];
				$post_type_object = get_post_type_object( $post_type );
				if ( $post_type_object == null ) {
					$post_type_object = get_post_type_object( 'page' );
				}
				self::echo_sub_options_html( $post_type_object );
				break;
		}
	}

	public function get_options_from_posted_form( $data ) {
		$post_type = !empty( $data['post_type'] ) ? $data['post_type'] : 'page';

		$page_id = !empty( $data['page_id'] ) ? $data['page_id'] : 0;

		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type_object == null ) {
			$post_type = '';
			$page_id = 0;
		}

		$with_subtree = !empty( $data['with_subtree'] );

		$options = array(
			'post_type' => $post_type,
			'page' => $page_id,
			'with_subtree' => $with_subtree,
		);

		return $options;
	}

	protected static function get_hierarchical_post_types() {
		$post_types = get_post_types( array( 'hierarchical' => true ), 'objects' );
		return $post_types;
	}

	protected static function hierachical_post_type_exists( $post_type ) {
		$post_types = get_post_types( array( 'hierarchical' => true ), 'names' );
		return in_array( $post_type, $post_types );
	}

}

WpakComponentsTypes::register_component_type( 'page', array( 'label' => __( 'WordPress page', WpAppKit::i18n_domain ) ) );
