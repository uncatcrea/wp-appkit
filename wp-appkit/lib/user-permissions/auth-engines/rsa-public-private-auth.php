<?php

require_once( dirname( __FILE__ ) .'/abstract-auth-engine.php' );

class WpakRsaPublicPrivateAuth extends WpakAuthEngine {
	
	const auth_meta_id = '_wpak_rsa_auth_settings';
	
	public function log_user_in() {
		
	}
	
	public function settings_meta_box_content( $post, $current_box ) {
		
		$auth_settings = $this->get_authentication_settings( $post->ID );
		
		if( !empty( $auth_settings['private_key'] ) ) {
			$private_key_ok = $this->check_private_key( $auth_settings['private_key'] );
			if ( !$private_key_ok ) {
				?>
				<div class="error"><?php _e( "Private key not valid", WpAppKit::i18n_domain ) ?></div>
				<div class="wpak-error"><?php _e( "Private key not valid", WpAppKit::i18n_domain ) ?></div>
				<?php
			}
		}
		
		?>
		<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
		<div class="wpak_settings">
			<label><?php _e( 'App Private Key', WpAppKit::i18n_domain ) ?></label>
			<textarea name="wpak_app_private_key" id="wpak_app_private_key" style="height:23em"><?php echo esc_textarea( $auth_settings['private_key'] ) ?></textarea>
		</div>
		<?php wp_nonce_field( 'wpak-rsa-auth-settings-' . $post->ID, 'wpak-nonce-rsa-auth-settings' ) ?>
		<?php
	}
	
	protected function get_authentication_settings( $app_id ) {
		$auth_settings = get_post_meta( $app_id, self::auth_meta_id, true );
		$auth_settings = wp_parse_args(
            $auth_settings,
            array(
                'private_key' => '',
            )
        );
        return $auth_settings;
	}
	
	protected function set_authentication_settings( $app_id, $auth_settings ) {
		update_post_meta( $app_id, self::auth_meta_id, $auth_settings );
	}
	
	public function save_posted_settings( $post_id ) {

		$current_settings = $this->get_authentication_settings( $post_id );

		if ( isset( $_POST['wpak_app_private_key'] ) ) {
			$current_settings['private_key'] = addslashes( $_POST['wpak_app_private_key'] ); //sanitize_text_field removes \r\n which invalidates the private key
		}
		
		$this->set_authentication_settings( $post_id, $current_settings );
	}

	protected function check_private_key( $private_key ) {
		//Check if public key can be extracted from private key :
		//if so, the private key is ok :
		return !empty( $this->get_public_key_from_private_key( $private_key ) );
	}
	
	protected function get_public_key_from_private_key( $private_key ) {
		$public_key = '';
		$private_key = openssl_pkey_get_private( $private_key );
		if ( $private_key !== false ) {
			$key_data = openssl_pkey_get_details( $private_key );
			if ( $key_data !== false && !empty( $key_data['key'] ) ) {
				$public_key = $key_data['key'];
			}
		}
		return $public_key;
	}
	
	protected function get_app_public_key( $app_id ) {
		return $this->get_public_key_from_private_key( $this->get_app_private_key( $app_id ) );
	}
	
	protected function get_app_private_key( $app_id ) {
		$private_key = '';
		$auth_settings = $this->get_authentication_settings( $app_id );
		if ( !empty( $auth_settings['private_key'] ) ) {
			$private_key = $auth_settings['private_key'];
		}
		return $private_key;
	}
	
	protected function decrypt( $app_id, $encrypted ) {
		$decrypted = '';
		
		$encrypted = base64_decode( $encrypted );
		
		$private_key = openssl_pkey_get_private( $this->get_app_private_key( $app_id ) );
		
		if ( !openssl_private_decrypt( $encrypted, $decrypted, $private_key, OPENSSL_PKCS1_PADDING) ) {
			$decrypted = false;
		} else {
			$decrypted = (array)json_decode($decrypted);
		}
		
		return $decrypted;
	}
	
	public function get_webservice_answer( $app_id ) {
		$service_answer = array();
		
		$auth_params = WpakWebServiceContext::getClientAppParams();
		
		$debug_mode = WpakBuild::get_app_debug_mode( $app_id ) === 'on';
		
		switch( $auth_params['auth_action'] ) {
			
			case "get_public_key":
				if ( !empty( $auth_params['user'] ) ) {
					
					$user = $auth_params['user'];
					$user_wp = get_user_by( 'login', $user );

					if ( $user_wp ) {
					
						//Check the user is not banned :
						if ( $this->check_user_is_allowed_to_authenticate( $user_wp->ID, $app_id ) ) {

							if ( !empty( $auth_params['control'] )
								 && !empty( $auth_params['control_key'] )
								 && !empty( $auth_params['timestamp'] )
								) {

								$user = $auth_params['user'];
								$control = $auth_params['control'];
								$control_key = $auth_params['control_key'];
								$timestamp = $auth_params['timestamp'];

								//First, check that sent data has not been modified :
								if ( $this->check_hmac( $auth_params['auth_action'] . $user . $timestamp, $control_key, $control ) ) {

									if ( $this->check_query_time( $timestamp ) ) {

										$public_key = $this->get_app_public_key( $app_id );
										if ( !empty( $public_key ) ) {

											//Return public key :
											$service_answer['public_key'] = $public_key;

											//Add control key :
											$service_answer['control'] = $this->generate_hmac( $public_key . $user, $control_key );
											
										} else {
											//If not in debug mode, don't give error details for security concern :
											$service_answer['auth_error'] = $debug_mode ? 'empty-public-key' : 'auth-error'; //Don't give more details for security concern
										}

									} else {
										//If not in debug mode, don't give error details for security concern :
										$service_answer['auth_error'] = $debug_mode ? 'wrong-query-time' : 'auth-error'; //Don't give more details for security concern
									}
									
								} else {
									//If not in debug mode, don't give error details for security concern :
									$service_answer['auth_error'] = $debug_mode ? 'wrong-hmac' : 'auth-error'; //Don't give more details for security concern
								}

							} else {
								//If not in debug mode, don't give error details for security concern :
								$service_answer['auth_error'] = $debug_mode ? 'wrong-data' : 'auth-error'; //Don't give more details for security concern
							}
							
						} else {
							$service_answer['auth_error'] = 'user-banned';
						}
						
					} else {
						$service_answer['auth_error'] = 'wrong-user';
					}
					
				} else {
					$service_answer['auth_error'] = 'empty-user';
				}
				break;
				
			case "connect_user":
				if ( !empty( $auth_params['user'] ) 
					 && !empty( $auth_params['control'] )
					 && !empty( $auth_params['timestamp'] )
					 && !empty( $auth_params['encrypted'] )
					) {
					
					$service_answer = array( 'authenticated' => 0 );
					
					$user = $auth_params['user'];
					$control = $auth_params['control'];
					$timestamp = $auth_params['timestamp'];
					$encrypted = $auth_params['encrypted'];
					
					//Decrypt data to retrieve user HMAC secret key :
					$decrypted = $this->decrypt( $app_id, $encrypted );
					
					if ( is_array( $decrypted ) && !empty( $decrypted['secret'] ) ) {
						
						$user_secret_key = $control_key = $decrypted['secret'];
						
						if ( $this->check_secret_format( $user_secret_key ) ) {
							
							if ( $this->check_hmac( $auth_params['auth_action'] . $user . $timestamp . $encrypted, $control_key, $control ) 
								 && $user == $decrypted['user']
								) {

								if ( $this->check_query_time( $timestamp ) ) {

									//Check user data :

									$user = $decrypted['user'];
									$user_wp = get_user_by( 'login', $user );

									if ( $user_wp ) {

										//Check the user is not banned :
										if ( $this->check_user_is_allowed_to_authenticate( $user_wp->ID, $app_id ) ) {

											//Check password :
											$pass = $decrypted['pass'];
											if ( wp_check_password( $pass, $user_wp->data->user_pass, $user_wp->ID ) ) {

												//Memorize user as registered and store its secret control key
												$this->authenticate_user( $user_wp->ID, $user_secret_key, $app_id );

												//Return authentication result to client :
												$service_answer['authenticated'] = 1; 

												//Get user permissions :
												$service_answer['permissions'] = $this->get_user_permissions( $user_wp->ID, $app_id );
												
												//Add control key :
												$service_answer['control'] = $this->generate_hmac( 'authenticated' . $user, $user_secret_key );

											} else {
												$service_answer['auth_error'] = 'wrong-pass';
											}

										} else {
											$service_answer['auth_error'] = 'user-banned';
										}

									} else {
										$service_answer['auth_error'] = 'wrong-user';
									}

								} else {
									//If not in debug mode, don't give error details for security concern :
									$service_answer['auth_error'] = $debug_mode ? 'wrong-query-time' : 'auth-error'; 
								}

							} else {
								//If not in debug mode, don't give error details for security concern :
								$service_answer['auth_error'] = $debug_mode ? 'wrong-hmac' : 'auth-error'; //Don't give more details for security concern
							}
							
						} else {
							//If not in debug mode, don't give error details for security concern :
							$service_answer['auth_error'] = $debug_mode ? 'wrong-secret' : 'auth-error'; //Don't give more details for security concern
						}
						
					} else {
						//If not in debug mode, don't give error details for security concern :
						$service_answer['auth_error'] = $debug_mode ? 'wrong-decryption' : 'auth-error'; //Don't give more details for security concern
					}
					
				} else {
					$service_answer['auth_error'] = 'empty-user';
				}
				break;
				
			default:
				$service_answer = array( 'error' => 'wrong-action' ); //This will set webservice answer status to 0.
				break;
				
		}
		
		//Note : deliberately don't add hook here to filter $service_answer
		//so that malicious code can not modify authentication process.
		
		return $service_answer;
	}
	
	/**
	 * Generates HMAC control code from passed $data using $secret as secret key
	 * 
	 * @param String $data
	 * @param String $secret
	 * @return String
	 */
	protected function generate_hmac( $data, $secret ) {
		return hash( 'sha256', $data .'|'. $secret );
	}
	
	protected function check_hmac( $data, $secret, $to_check ) {
		return $this->generate_hmac( $data, $secret ) === $to_check;
	}
	
	protected function check_query_time( $query_timestamp ) {
		$diff = time() - $query_timestamp;
		$acceptable = apply_filters( 'wpak-auth-acceptable-delay', 60); //seconds
		return $diff <= $acceptable;
	}
	
	protected function check_secret_format( $user_secret_key ) {
		$allowed = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-=!@#$%^&*()_+:<>{}[]";
		return strlen( $user_secret_key ) == 50 && preg_match( '|^['. preg_quote($allowed) .']+$|', $user_secret_key );
	}
	
	/**
	 * Stores locally (user meta) that the user is authenticated to access
	 * the given app.
	 */
	protected function authenticate_user( $user_id, $user_secret_key, $app_id ) {
		
		$user_meta = '_wpak_auth_'. $app_id;
		
		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) && array_key_exists( 'key', $user_auth_data ) ) {
			$user_auth_data['key'] = $user_secret_key;
			$user_auth_data['time'] = time();
		} else {
			$user_auth_data = array( 
				'key' => $user_secret_key,
				'time' => time()
			);
		}
		
		update_user_meta( $user_id, $user_meta, $user_auth_data );
	}
	
	/**
	 * Revokes access to the given user for the given app
	 */
	protected function unauthenticate_user( $user_id, $app_id ) {
		$user_meta = '_wpak_auth_'. $app_id;
		delete_user_meta( $user_id, $user_meta );
	}
	
	/**
	 * Retrieves user secret key used to connect to the given app
	 */
	protected function get_user_secret( $user_id, $app_id ) {
		$user_secret = '';
		$user_meta = '_wpak_auth_'. $app_id;
		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) && array_key_exists( 'key', $user_auth_data ) ) {
			$user_secret = $user_auth_data['key'];
		} 
		return $user_secret;
	}
	
	/**
	 * Retrieves user permisions (roles and capabilities) to return to the app.
	 */
	protected function get_user_permissions( $user_id, $app_id ) {
		$user_permissions = array( 'capabilities' => array(), 'roles' => array() );
		
		$user = get_userdata( $user_id ); 
		if ( $user ) {
			foreach( $user->allcaps as $cap => $has_cap ) {
				if ( $has_cap === true ) {
					$user_permissions['capabilities'][] = $cap;
				}
			}
			foreach( $user->roles as $role ) {
				$user_permissions['roles'][] = $role;
			}
		}
		
		$user_permissions = apply_filters( 'wpak_auth_user_permissions', $user_permissions, $user_id, $app_id );
		
		return $user_permissions;
	}
	
	protected function check_user_is_allowed_to_authenticate( $user_id, $app_id ) {
		/**
		 * Filter 'wpak_auth_user_is_allowed_to_authenticate' : 
		 * use this to ban specific users.
		 * 
		 * @param $is_allowed Boolean Whether the user is allowed to authenticate (default true)
		 * @param $user_id Integer User ID
		 * @param $app_id Integer Application ID
		 */
		return apply_filters( 'wpak_auth_user_is_allowed_to_authenticate', true, $user_id, $app_id );
	}
}
