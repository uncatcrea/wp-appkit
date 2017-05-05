<?php

class WpakLicenses {
   
    protected static $registered_licences = null;
   
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
   
}

class WpakLicense {
   
    protected $file;
    protected $license_key;
    protected $license_active;
    protected $item_name;
    protected $item_id;
    protected $item_shortname;
    protected $version;
    protected $author;
    protected $api_url = WpakConfig::uncatcrea_website_url;
    
    const licences_option_id = 'wpak_licenses';
   
    public function __construct( $file, $item_name, $version, $author, $api_url = null, $item_id = null ) {
        $this->file = $file;
        $this->item_name = $item_name;

        if ( is_numeric( $item_id ) ) {
            $this->item_id = absint( $item_id );
        }
        
        $this->item_shortname = 'wpak_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
        $this->version        = $version;
        $this->license_key    = trim( $this->get_local_data( 'license_key' ) );
        $this->license_active = $this->get_local_data( 'license_active' );
        $this->author         = $author;
        $this->api_url        = is_null( $api_url ) ? $this->api_url : $api_url;
    }
   
    public function __get( $field ) {
        return isset( $this->{$field} ) ? $this->{$field} : null;
    }
    
    public function set_license_key( $license_key ) {
         $this->set_local_data( 'license_key', $license_key );
    }
    
    public function empty_license_active() {
         $this->set_local_data( 'license_active', null );
    }
    
    public function activate( $license_key ) {
        $result = array( 'ok' => false, 'message' => '' );
        
		// Data to send to the API
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license_key,
			'item_name'  => urlencode( $this->item_name ),
			'url'        => home_url()
		);

		// Call the API
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			)
		);
        
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            
            //Network error: return error message:
			if ( is_wp_error( $response ) ) {
				$result['message'] = $response->get_error_message();
			} else {
				$result['message'] = __( 'An error occurred, please try again.', WpAppKit::i18n_domain );
			}
            
		} else {
            
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            // Tell WordPress to look for updates
            set_site_transient( 'update_plugins', null );

            //Memorize license key
            $this->set_local_data( 'license_key', $license_key );
            
            //Memorize returned license data
            $this->set_local_data( 'license_active', $license_data );
            
            $result['ok'] = true;
        }

        return $result;
	}
    
    public function deactivate() {
        $result = array( 'ok' => false, 'message' => '' );
        
        // Data to send to the API
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $this->license_key,
            'item_name'  => urlencode( $this->item_name ),
            'url'        => home_url()
        );

        // Call the API
        $response = wp_remote_post(
            $this->api_url,
            array(
                'timeout'   => 15,
                'sslverify' => false,
                'body'      => $api_params
            )
        );

        // Make sure there are no errors
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            
            //Network error: return error message:
            if ( is_wp_error( $response ) ) {
                $result['message'] = $response->get_error_message();
            } else {
                $result['message'] = __( 'An error occurred, please try again.', WpAppKit::i18n_domain );
            }
            
        } else {

            //Empty license key
            $this->set_local_data( 'license_key', '' );
            
            //Empty license data
            $this->empty_license_active();
            
            $result['ok'] = true;
        }
        
        return $result;
    }
    
    /*protected static function check_licence( $product_id, $licence_key ) {
        $result = array( 'ok' => false, 'answer' => array(), 'message' => '' );
        return $result;
    }*/
    
    public function get_status() {
        $status = '';
        $message = '';
        $type = '';
        
        if( !empty( $this->license_active ) ) {
            
            if ( $this->license_active->success === false ) {

                switch( $this->license_active->error ) {

                    case 'expired' :
                        $message = sprintf(
                            __( 'Your license key expired on %s.', WpAppKit::i18n_domain ),
                            date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                        );
                        $status = 'license-expired-notice';
                        $type = 'expired';
                        break;

                    case 'revoked' :
                        $message = sprintf( __( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', WpAppKit::i18n_domain ), WpakConfig::support_url );
                        $status = 'license-error-notice';
                        $type = 'error';
                        break;

                    case 'missing' :
                        $message = sprintf( __( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', WpAppKit::i18n_domain ), WpakConfig::my_account_url );
                        $status = 'license-error-notice';
                        $type = 'error';
                        break;

                    case 'invalid' :
                    case 'site_inactive' :
                        $message = sprintf( __( 'Your license is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', WpAppKit::i18n_domain ), WpakConfig::my_account_url );
                        $status = 'license-error-notice';
                        $type = 'error';
                        break;

                    case 'item_name_mismatch' :
                        $message = sprintf( __( 'This appears to be an invalid license key for %s.', WpAppKit::i18n_domain ), $this->item_name );
                        $status = 'license-error-notice';
                        $type = 'error';
                        break;

                    case 'no_activations_left':
                        $message = __( 'Your license key has reached its activation limit.', WpAppKit::i18n_domain );
                        $message = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', WpAppKit::i18n_domain ),  WpakConfig::my_account_url );
                        $status = 'license-error-notice';
                        $type = 'error';
                        break;

                    default :
                        $error = ! empty(  $this->license_active->error ) ?  $this->license_active->error : 'unknown_error';
						$message = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', WpAppKit::i18n_domain ), $error, WpakConfig::support_url );
                        $status = 'license-error-notice';
                        $type = 'error';
                        break;
                }

            } else {

                //We have license_active info and no error
                switch( $this->license_active->license ) {

					case 'valid' :
					default:

						$type = 'valid';

						$now        = current_time( 'timestamp' );
						$expiration = strtotime( $this->license_active->expires, current_time( 'timestamp' ) );

						if( 'lifetime' === $this->license_active->expires ) {

							$message = __( 'License key never expires.', WpAppKit::i18n_domain );

							$status = 'license-lifetime-notice';

						} elseif( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

							$message = sprintf(
								__( 'Your license key expires soon! It expires on %s. <a href="%s" target="_blank">Renew your license key</a>.', WpAppKit::i18n_domain ),
								date_i18n( get_option( 'date_format' ), strtotime( $this->license_active->expires, current_time( 'timestamp' ) ) ),
								WpakConfig::checkout_url .'?edd_license_key='. $this->license_key
							);

							$status = 'license-expires-soon-notice';

						} else {

							$message = sprintf(
								__( 'Your license key expires on %s.', WpAppKit::i18n_domain ),
								date_i18n( get_option( 'date_format' ), strtotime( $this->license_active->expires, current_time( 'timestamp' ) ) )
							);

							$status = 'license-expiration-date-notice';

						}
						break;

				}
            }
            
        } else {
            
			$type = 'empty';

			$message = sprintf( __('To receive support and updates, please enter your valid <em>%s</em> license key.', WpAppKit::i18n_domain ), $this->item_name );

			$status = null;
		    
        }
        
        return array( 'type' => $type, 'status' => $status, 'message' => $message );
    }
    
    /**
     * Retrieve licence data stored in options
     * 
     * @param  string   $key            Key to set
     * @return object   $licence_data   Licence data corresponding to the given key
     */
    protected function get_local_data( $key ) {
        $licence_data = null;
        $licenses_local = get_option( self::licences_option_id );
        if ( !empty( $licenses_local ) ) {
            if ( !empty( $this->item_shortname ) && isset( $licenses_local[$this->item_shortname][$key] ) ) {
                $licence_data = $licenses_local[$this->item_shortname][$key];
            }
        }
        return $licence_data;
    }
    
    protected function set_local_data( $key, $value ) {
        $licenses_local = get_option( self::licences_option_id );
        $licenses_local = !empty( $licenses_local ) ? $licenses_local : array();
        if ( !empty( $this->item_shortname ) ) {
            
            $licenses_local[$this->item_shortname][$key] = $value;
            update_option( self::licences_option_id, $licenses_local );
            
            //After persisting data, also update current object if the corresponding property exists:
            if ( property_exists( $this, $key ) ) {
                $this->{$key} = $value;
            }
        }
    }
}