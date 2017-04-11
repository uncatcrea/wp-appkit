<?php

class WpakWebServicesBoSettings {

    public static function hooks() {
        if ( is_admin() ) {
            add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        }
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'wpak_app_synchronization',
            __( 'Synchronization', WpAppKit::i18n_domain ),
            array( __CLASS__, 'inner_synchronization_box' ),
            'wpak_apps',
            'side',
            'default'
        );
    }

    public static function inner_synchronization_box( $post, $current_box ) {
        $wp_ws_url = WpakWebServices::get_app_web_service_url( $post->ID, 'synchronization' );
        ?>
        <a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
        <div class="field-group">
            <a class="button" href="<?php echo esc_url( $wp_ws_url ) ?>" target="_blank"><?php _e( 'Show Me Web Service Data', WpAppKit::i18n_domain ) ?></a>
        </div>
        <?php
        do_action( 'wpak_inner_synchronization_box', $post, $current_box );
    }
}

WpakWebServicesBoSettings::hooks();
