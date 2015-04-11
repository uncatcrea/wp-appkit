<?php
require_once(dirname( __FILE__ ) . '/config-file.php');

class WpakSimulator {

	public static function hooks() {
		if ( is_admin() ) {
			add_filter( 'post_row_actions', array( __CLASS__, 'add_action_link' ), 10, 2 );
		}
	}

	public static function add_action_link( $actions ) {
		global $post;

		if ( $post->post_type == 'wpak_apps' ) {
			if ( array_key_exists( 'trash', $actions ) ) {
				$trash_mem = $actions['trash'];
				unset( $actions['trash'] );
				$actions['wpak-view-app-in-browser'] = '<a href="' . WpakBuild::get_appli_index_url( $post->ID ) . '" target="_blank">' . __( 'View in browser', WpAppKit::i18n_domain ) . '</a>';
				$actions['trash'] = $trash_mem;
			}else{
				$actions['wpak-view-app-in-browser'] = '<a href="' . WpakBuild::get_appli_index_url( $post->ID ) . '" target="_blank">' . __( 'View in browser', WpAppKit::i18n_domain ) . '</a>';
			}
		}
		return $actions;
	}
}

WpakSimulator::hooks();
