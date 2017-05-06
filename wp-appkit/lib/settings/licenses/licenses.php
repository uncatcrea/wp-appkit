<?php

require_once( dirname( __FILE__ ) . '/license.php' );

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
    require_once( dirname( __FILE__ ) . '/edd-plugin-updater.php' );
}

class WpakLicenses {
   
    protected static $registered_licences = null;
   
    public static function hooks() {
        add_action( 'admin_init', array( __CLASS__, 'edd_plugin_updater' ), 0 );
    }
    
    public static function tab_licenses() {
        $result = self::handle_posted_licenses();
        $licenses_registered = self::get_registered_licenses();
        ?>
       
        <?php if ( !empty( $result['message'] ) ): ?>
            <div class="<?php echo $result['ok'] ? 'updated' : 'error' ?>" ><p><?php echo $result['message'] ?></p></div>
        <?php endif ?>
           
        <?php if ( !empty( $licenses_registered ) ): ?>
           
            <form method="post" action="<?php echo esc_url( add_query_arg( array() ) ) ?>">
               
                <p>
                    <?php
                        echo sprintf(
                            __( 'Enter your addon or theme license keys here to receive support and updates for purchased items. If your license key has expired, please <a href="%s" target="_blank">renew your license</a>.', WpAppKit::i18n_domain ),
                            '//TODO' //TODO
                        );
                    ?>
                </p>

                <table class="form-table">
                    <tbody>
                        <?php foreach( $licenses_registered as $license ): var_dump($license);?>

                            <?php $license_form_id = 'license_key_'. $license->item_shortname; ?>

                            <tr>
                                <th scope="row"><?php echo $license->item_name; ?></th>
                                <td>
                                    <input class="regular-text" id="<?php echo esc_attr( $license_form_id ); ?>" name="wpak_licenses[<?php echo esc_attr( $license->item_shortname ); ?>]" value="<?php echo esc_attr( $license->license_key ); ?>" type="text" placeholder="<?php _e( 'License key', WpAppKit::i18n_domain ); ?>">
                                   
                                    <?php if ( ( is_object( $license->license_active ) && 'valid' == $license->license_active->license ) ): ?>
                                        <input type="submit" class="button-secondary" name="<?php echo esc_attr( $license->item_shortname ); ?>_license_deactivate" value="<?php _e( 'Deactivate License',  WpAppKit::i18n_domain ); ?>"/>
                                    <?php endif; ?>
                                    
                                    <label for="<?php echo esc_attr( $license_form_id ); ?>"> </label>

                                    <?php $license_status = $license->get_status(); ?>
                                    <?php if ( ! empty( $license_status['message'] ) ): ?>
                                        <div class="edd-license-data edd-license-<?php echo $license_status['type']; ?> <?php echo $license_status['status']; ?>">
                                            <p><?php echo $license_status['message']; ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php wp_nonce_field( 'wpak_save_licenses', 'wpak_save_licenses_nonce' ) ?>
               
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Save changes', WpAppKit::i18n_domain ) ); ?>" />
                </p>

            </form>
           
        <?php else: ?>
           
            <p><?php _e( 'No WP-AppKit Theme or Addon requiring a license found.', WpAppKit::i18n_domain ); ?></p>
           
        <?php endif; ?>
           
        <?php
    }
   
    protected static function get_registered_licenses() {
       
        if ( self::$registered_licences === null ) {
            
            self::$registered_licences = array();
            
            /**
             * Use this from premium addons and premium themes to add EDD licenses management
             * Each added licence should be formated like so:
             * $licence = array(
             *        'file',
             *        'item_name',
             *        'version',
             *        'author',
             *        'api_url' = null,
             *        'item_id' = null
             * )
             */
            $licenses_raw = apply_filters( 'wpak_licenses', array() );
           
            foreach( $licenses_raw as $license_raw ) {
                
                $api_url = !empty( $license_raw['api_url'] ) ? $license_raw['api_url'] : null;
                $item_id = !empty( $license_raw['item_id'] ) ? $license_raw['item_id'] : null;
                        
                $license = new WpakLicense( $license_raw['file'], $license_raw['item_name'], $license_raw['version'], $license_raw['author'], $api_url, $item_id );
               
                self::$registered_licences[$license->item_shortname] = $license;
               
            }
        }
       
        return self::$registered_licences;
    }

    protected static function handle_posted_licenses() {
        $result = array( 'ok' => false, 'message' => '' );
        
        if ( ! isset( $_POST['wpak_licenses'] ) ) {
            $result['ok'] = true; //Nothing posted, return ok
			return $result;
		}

		if ( ! isset( $_REQUEST[ 'wpak_save_licenses_nonce' ] ) || ! wp_verify_nonce( $_REQUEST[ 'wpak_save_licenses_nonce' ], 'wpak_save_licenses' ) ) {
            $result['message'] = __( 'An error occurred: wrong security token', WpAppKit::i18n_domain );
			return $result;
		}
        
        if ( !current_user_can( 'manage_options' ) && !current_user_can( 'wpak_edit_apps' ) ) {
            $result['message'] = __( 'An error occurred: wrong permissions', WpAppKit::i18n_domain );
			return $result;
		}

        $current_licenses = self::get_registered_licenses();
        
        foreach ( $_POST as $key => $value ) {
			if( strpos( $key, '_license_deactivate' ) !== false ) {
                
                //Retrieve license to deactivate:
                $license_shortname = str_replace( '_license_deactivate', '', $key );
                
                if ( isset( $current_licenses[$license_shortname] ) ) {
                    $deactivation_result = $current_licenses[$license_shortname]->deactivate();
                    if( $deactivation_result['ok'] ) {
                        $result['ok'] = true;
                        $result['message'] = __( 'Licenses saved', WpAppKit::i18n_domain );
                    } else {
                        //If a network error occured during activation, abort and display error:
                        $result['message'] = $deactivation_result['message'];
                    }
                }
                
                // Return anyway because we can't activate a key when deactivating a different key
				return $result;
			}
		}
        
        foreach( $_POST['wpak_licenses'] as $license_shortname => $license_key ) {
            
            if ( !isset( $current_licenses[$license_shortname] ) ) {
                continue;
            }
            
            $current_license = $current_licenses[$license_shortname];
            
            //If the license is already memorized as active, do nothing.
            //(License active state is checked when doing the weekly check)
            $license_active = $current_license->license_active;
            if ( is_object( $license_active ) && $license_active->license === 'valid' ) {
                continue;
            }
            
            //Memorize entered license key, whatever the activation process result is:
            $current_license->set_license_key( $license_key );

            $license_key = sanitize_text_field( $license_key );

            if( empty( $license_key ) ) {
                //If we enter an empty key for an invalid license, empty license_active data
                //so that we can have a fresh start with this license.
                if( is_object( $license_active ) && $license_active->license !== 'valid' ) {
                    $current_license->empty_license_active();
                }
                continue;
            }
            
            $activation_result = $current_license->activate( $license_key );
            if( !$activation_result['ok'] ) {
                //If a network error occured during activation, abort and display error:
                $result['message'] = $activation_result['message'];
                return $result;
            }
        }
        
        //If we reach this point, it means there was no Network error.
        //(License errors are handle independently in each license_active data)
        $result['ok'] = true;
        $result['message'] = __( 'Licenses saved', WpAppKit::i18n_domain );
        
        return $result;
    }
    
    /*protected static function check_licence_zombies() {
        //foreach licence check if in $_POST and delete it if not
        if ( empty( $_POST['wpak_licences'][ $this->item_shortname . '_license_key'] ) ) {
			delete_option( $this->item_shortname . '_license_active' );
			return;
		}
    }*/
    
    /**
     * Activate EDD auto check for items updates
     */
    public static function edd_plugin_updater() {

        $licenses_registered = self::get_registered_licenses();
        
        foreach( $licenses_registered as $license ) {

            $edd_updater = new EDD_SL_Plugin_Updater( WpakConfig::uncatcrea_website_url, $license->file, array(
                    'version' 	=> $license->version,
                    'license' 	=> $license->license_key,
                    'item_name' => $license->item_name,
                    'author' 	=> $license->author,
                    'beta'		=> false
                )
            );
            
        }

    }
   
}

WpakLicenses::hooks();

add_filter( 'http_request_host_is_external', 'allow_my_custom_host', 10, 3 );
function allow_my_custom_host( $allow, $host, $url ) {
  if ( $host === 'local.uncategorized-creations.com' )
    $allow = true;
  return $allow;
}