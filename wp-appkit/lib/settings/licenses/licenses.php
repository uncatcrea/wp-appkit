<?php

require_once( dirname( __FILE__ ) . '/license.php' );

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
    require_once( dirname( __FILE__ ) . '/edd-plugin-updater.php' );
}

class WpakLicenses {
   
    protected static $registered_licences = null;
    protected static $auto_updaters = array();
   
    public static function hooks() {
        add_action( 'admin_init', array( __CLASS__, 'edd_plugin_updater' ), 0 );
        add_action( 'admin_init', array( __CLASS__, 'handle_forced_updates' ), 10 );
        
        //Set the weekly license check:
        add_filter( 'cron_schedules', array( __CLASS__, 'add_weekly_schedule' ) );
		add_action( 'wp', array( __CLASS__, 'add_weekly_license_check' ) );
        add_action( 'wpak_weekly_license_check', array( __CLASS__, 'check_licenses' ) );
    }
    
    public static function add_weekly_schedule( $schedules = array() ) {
        
		// Adds "Once weekly" to the existing core schedules ('hourly', 'twicedaily', 'daily').
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly', WpAppKit::i18n_domain )
		);

		return $schedules;
	}
    
    public static function add_weekly_license_check() {
		if ( ! wp_next_scheduled( 'wpak_weekly_license_check' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'weekly', 'wpak_weekly_license_check' );
		}
	}
    
    public static function tab_licenses() {
        
        $result = array();
        
        $result = self::handle_posted_licenses();
        
        $licenses_registered = self::get_registered_licenses();
        
        ?>
       
        <?php if ( !empty( $result['message'] ) ): ?>
            <div class="<?php echo $result['ok'] ? 'updated' : 'error' ?>" ><p><?php echo $result['message'] ?></p></div>
        <?php endif ?>
           
        <?php if ( !empty( $licenses_registered ) ): ?>
           
            <form method="post" action="<?php echo esc_url( self::get_licenses_base_url() ) ?>">
               
                <p>
                    <?php
                        echo sprintf(
                            __( 'Enter your support, addon or theme license keys here to receive support and updates for purchased items. If your license key has expired, please <a href="%s" target="_blank">renew your license</a>.', WpAppKit::i18n_domain ),
                            WpakConfig::license_renewal_url
                        );
                    ?>
                </p>
                
                <p>
                    <?php
                        echo sprintf(
                            __( 'Themes, addons and licenses updates are checked automatically every day. To check for last versions and last license state now, <a href="%s">click here</a>.', WpAppKit::i18n_domain ),
                            self::get_force_refresh_link( array( 'licenses', 'updates' ) )
                        );
                    ?>
                </p>

                <table class="form-table">
                    <tbody>
                        <?php foreach( $licenses_registered as $license ): ?>

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
            
        <?php $next_check_timestamp = wp_next_scheduled( 'wpak_weekly_license_check' ); ?>
        <!-- 
            WP-AppKit Licenses debug infos:
            Next weekly license check: <?php echo $next_check_timestamp ? date( 'Y-m-d H:i:s', $next_check_timestamp ) : 'never'; ?>
        -->
        
        <?php
    }
   
    protected static function get_registered_licenses() {
       
        if ( self::$registered_licences === null ) {
            
            self::$registered_licences = array();

            //One default license for pro support:
            $default_licenses = array(
                array( 
                    'file' => '', //Empty because this is not an addon or theme, just support
                    'item_name' => '1 year Pro Support',
                    'version' => '',
                    'author' => 'Uncategorized Creations',
                )
            );
            
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
            $licenses_raw = apply_filters( 'wpak_licenses', $default_licenses );
           
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
    
    public static function check_licenses() {
        $result = array( 'ok' => true, 'message' => '' );
        
        $current_licenses = self::get_registered_licenses();
        $messages = array();
        foreach( $current_licenses as $license ) {
            $license_key = $license->license_key;
            if( !empty( $license_key ) ) {
                $check_result = $license->check();
                $result['ok'] = $result['ok'] && $check_result['ok'];
                $messages[] = $license->item_name .': '. $check_result['message'];
            }
        }
        
        if ( !empty( $messages ) ) {
            $result['message'] = implode( '<br>', $messages );
        }
        
        return $result;
    }
    
    /**
     * Activate EDD auto check for items updates
     */
    public static function edd_plugin_updater() {

        $licenses_registered = self::get_registered_licenses();
        
        foreach( $licenses_registered as $license ) {

            $license_file = $license->file;
            
            if ( !empty( $license_file ) ) { //To exclude support licenses
                
                self::$auto_updaters[] = new EDD_SL_Plugin_Updater( WpakConfig::uncatcrea_website_url, $license_file, array(
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
    
    public static function handle_forced_updates() {
        
        global $pagenow;
       
        if ( $pagenow === 'admin.php' 
             && !empty( $_GET['page'] ) && $_GET['page'] === 'wpak_bo_settings_page'
             && !empty( $_GET['wpak_settings_page'] ) && $_GET['wpak_settings_page'] === 'licenses'
            ) {

            $redirect = false;

            if ( isset( $_GET['wpak_force_licenses_check'] ) && $_GET['wpak_force_licenses_check'] == 1 ) {
                $result = self::check_licenses();
                $redirect = true;
            }

            if ( isset( $_GET['wpak_force_updates_check'] ) && $_GET['wpak_force_updates_check'] == 1 ) {
                //To force check of addons versions (EDD_SL_Plugin_Updater::check_update()):
                set_site_transient( 'update_plugins', null );
                //Plus, there's a 3 hours cache on addons version check. Flush it:
                foreach( self::$auto_updaters as $auto_updater ) {
                    $auto_updater->flush_caches();
                }
                $redirect = true;
            }

            if ( $redirect ) {
                wp_safe_redirect( self::get_licenses_base_url() );
                exit();
            }
            
        }
        
    }
    
    public static function get_force_refresh_link( $refresh_types ) {
        
        if ( is_string( $refresh_types ) ) {
            $refresh_types = array( $refresh_types );
        }
        
        $query_args = array();
        if ( in_array( 'licenses', $refresh_types ) ) { //To refresh license validity state
            $query_args['wpak_force_licenses_check'] = 1;
        }
        if ( in_array( 'updates', $refresh_types ) ) { //To force addons/themes update check
            $query_args['wpak_force_updates_check'] = 1;
        }
        
        return add_query_arg( $query_args, self::get_licenses_base_url() );
    }
    
    public static function get_licenses_base_url() {
        $base_url = admin_url( 'admin.php?page=wpak_bo_settings_page&wpak_settings_page=licenses' );
        return remove_query_arg( array('wpak_force_licenses_check','wpak_force_updates_check'), $base_url );
    }
   
}

WpakLicenses::hooks();
