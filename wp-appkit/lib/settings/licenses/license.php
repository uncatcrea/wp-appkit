<?php

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

            // Tell WordPress to look for updates, so that edd plugin update check is triggered (triggers EDD_SL_Plugin_Updater::check_update())
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
    
    public function check() {

        $result = array( 'ok' => false, 'message' => '' );

		if( empty( $this->license_key ) ) {
            $result['message'] = __( 'No license key to check', WpAppKit::i18n_domain );
			return $result;
		}

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'check_license',
			'license' 	=> $this->license_key,
			'item_name' => urlencode( $this->item_name ),
			'url'       => home_url()
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

		// make sure the response came back okay
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            
			//Network error: return error message:
            if ( is_wp_error( $response ) ) {
                $result['message'] = $response->get_error_message();
            } else {
                $result['message'] = __( 'An error occurred, please try again.', WpAppKit::i18n_domain );
            }
            
		} else {

            $license_data = json_decode( wp_remote_retrieve_body( $response ) );

            //Memorize returned license data
            $this->set_local_data( 'license_active', $license_data );
            
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

						$type = 'valid';

						$now        = current_time( 'timestamp' );
						$expiration = strtotime( $this->license_active->expires, current_time( 'timestamp' ) );

						if( 'lifetime' === $this->license_active->expires ) {

							$message = __( 'License key never expires.', WpAppKit::i18n_domain );

							$status = 'license-lifetime-notice';

						} elseif( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

							$message = sprintf(
								__( 'Your license key expires soon! It expires on %s. <a href="%s" target="_blank">Renew your license key</a>, then <a href="%s">re-check your license validity</a>.', WpAppKit::i18n_domain ),
								date_i18n( get_option( 'date_format' ), strtotime( $this->license_active->expires, current_time( 'timestamp' ) ) ),
								WpakConfig::checkout_url .'?edd_license_key='. $this->license_key,
                                WpakLicenses::get_force_refresh_link( array( 'licenses' ) )
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
                        
                    default:
                        //We have a $this->license_active->success === true, but no valid $this->license_active->license
                        //A re-sync is welcome.
                        
                        $type = 'error';
                        
                        $message = __( 'Your license state is not synchronized with UncatCrea server. Please re-save your licenses to synchronize it.', WpAppKit::i18n_domain );

                        $status = 'license-error-notice';
                        
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

