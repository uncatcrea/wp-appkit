<?php

require_once( dirname( __FILE__ ) .'/abstract-auth-engine.php' );

class WpakRsaPublicPrivateAuth extends WpakAuthEngine {

	const auth_meta_id = '_wpak_rsa_auth_settings';

	public function settings_meta_box_content( $post, $current_box ) {

		$auth_settings = $this->get_authentication_settings( $post->ID );

		$error_message = '';
		$display_error_top = false;
		$open_ssl_installed = false;

		if ( !function_exists( 'openssl_pkey_get_private' ) ) {
			$error_message = __( "OpenSSL PHP extension not found.<br>Please install the OpenSSL PHP extension to use WP-AppKit secure authentication",
							 WpAppKit::i18n_domain );
		} else {

			$open_ssl_installed = true;

			if ( !empty( $auth_settings['private_key'] ) ) {
				$private_key_ok = $this->check_private_key( $auth_settings['private_key'] );
				if ( !$private_key_ok ) {
					$error_message = __( "Private key not valid", WpAppKit::i18n_domain );
					$display_error_top = true;
				}
			}

		}

		if ( !empty( $error_message ) ) {
			$error_message = __( 'Secure authentication error', WpAppKit::i18n_domain ) . ' : '. $error_message;
			?>
			<?php if ( $display_error_top ): //WP moves .error to the top of edit pages ?>
				<div class="error"><?php echo $error_message ?></div>
			<?php endif ?>
			<div class="wpak-error"><?php echo $error_message ?></div>
			<?php
		}

		if ( $open_ssl_installed ) {
			?>
			<a href="#" class="hide-if-no-js wpak_help"><?php _e( 'Help me', WpAppKit::i18n_domain ); ?></a>
			<div class="wpak_settings">
				<label><?php _e( 'Private Key', WpAppKit::i18n_domain ) ?></label>
				<textarea name="wpak_app_private_key" id="wpak_app_private_key" style="height:23em"><?php echo esc_textarea( $auth_settings['private_key'] ) ?></textarea>
				<p class="description">
					<?php _e( 'Copy here an OpenSSL RSA Encryption Key to secure logins from your app.', WpAppKit::i18n_domain ) ?>
					<?php //TODO : add a link to our soon coming tutorial about this :) ?>
				</p>
			</div>
			<?php
		}
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

		//(Security nonce checked in mother class)

		$current_settings = $this->get_authentication_settings( $post_id );

		if ( isset( $_POST['wpak_app_private_key'] ) ) {
			$current_settings['private_key'] = addslashes( $_POST['wpak_app_private_key'] ); //sanitize_text_field removes \r\n which invalidates the private key
		}

		$this->set_authentication_settings( $post_id, $current_settings );
	}

	protected function check_private_key( $private_key ) {
		//Check if public key can be extracted from private key :
		//if so, the private key is ok :
		$public_key = $this->get_public_key_from_private_key( $private_key );
		return !empty( $public_key );
	}

	protected function get_public_key_from_private_key( $private_key ) {
		$public_key = '';
		if ( function_exists( 'openssl_pkey_get_private' ) ) {
			$private_key = openssl_pkey_get_private( $private_key );
			if ( $private_key !== false ) {
				$key_data = openssl_pkey_get_details( $private_key );
				if ( $key_data !== false && !empty( $key_data['key'] ) ) {
					$public_key = $key_data['key'];
				}
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

		if ( function_exists( 'openssl_pkey_get_private' ) ) {

			$private_key = openssl_pkey_get_private( $this->get_app_private_key( $app_id ) );

			if ( !openssl_private_decrypt( $encrypted, $decrypted, $private_key, OPENSSL_PKCS1_PADDING) ) {
				$decrypted = false;
			} else {
				$decrypted = (array)json_decode($decrypted);
			}

		}

		return $decrypted;
	}
	
	protected function get_wp_user( $user_login ) {
		
		$user_login = sanitize_user( $user_login );
		
		$user_wp = get_user_by( 'login', $user_login );

		if ( !$user_wp && strpos( $user_login, '@' ) ) {
			$user_wp = get_user_by( 'email', $user_login );
		}
		
		return !empty( $user_wp ) ? $user_wp : false;
	}

	public function get_webservice_answer( $app_id ) {
		$service_answer = array();

		$auth_params = WpakWebServiceContext::getClientAppParams();

		$debug_mode = WpakBuild::get_app_debug_mode( $app_id ) === 'on';

		switch( $auth_params['auth_action'] ) {

			case "get_public_key":
				if ( !empty( $auth_params['user'] ) ) {

					$user_wp = $this->get_wp_user( $auth_params['user'] );

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

										if ( function_exists( 'openssl_pkey_get_private' ) ) {

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
											$service_answer['auth_error'] = $debug_mode ? 'php-openssl-not-found' : 'auth-error'; //Don't give more details for security concern
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

									$user_wp = $this->get_wp_user( $user );

									if ( $user_wp ) {

										//Check the user is not banned :
										if ( $this->check_user_is_allowed_to_authenticate( $user_wp->ID, $app_id ) ) {

											//Check password :
											$pass = $decrypted['pass'];
											if ( wp_check_password( $pass, $user_wp->data->user_pass, $user_wp->ID ) ) {

												if ( $this->check_user_permissions( $user_wp->ID, $app_id ) ) {
													
													//Memorize user as registered and store its secret control key
													$this->authenticate_user( $user_wp->ID, $user_secret_key, $app_id );

													//Return authentication result to client :
													$service_answer['authenticated'] = 1;

													//Get user permissions :
													$service_answer['permissions'] = $this->get_user_permissions( $user_wp->ID, $app_id );
													
													//Get user info :
													$service_answer['info'] = $this->get_user_info( $user_wp->ID, $app_id );

													//Add control key :
													$service_answer['control'] = $this->generate_hmac( 'authenticated' . $user, $user_secret_key );
													
												} else {
													$service_answer['auth_error'] = 'wrong-permissions';
												}
												
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
					$service_answer['auth_error'] = 'wrong-auth-data';
				}
				break;

			case 'check_user_auth':
				$service_answer['user_auth_ok'] = 0;
				//Check authentication
				if ( !empty( $auth_params['user'] )
					 && !empty( $auth_params['control'] )
					 && !empty( $auth_params['timestamp'] )
					) {
					
					$user_wp = $this->get_wp_user( $auth_params['user'] );

					if ( $user_wp ) {
						if ( !empty( $auth_params['hash'] ) && !empty( $auth_params['hasher'] ) ) {
							$result = $this->check_authenticated_action( $app_id, 'check_user_auth', $auth_params, array( $auth_params['hash'], $auth_params['hasher'] ) );
							if ( $result['ok'] ) {
								//Means that the user is authenticated ok on server side, with secret ok.
								//Now, check that the public key has not changed :
								$app_public_key = $this->get_app_public_key( $app_id );
								$hash = $this->generate_hmac( $app_public_key, $auth_params['hasher'] );
								if ( $auth_params['hash'] === $hash ) {
									
									//Check if user permissions have not changed:
									if ( $this->check_user_permissions( $user_wp->ID, $app_id ) ) {
										
										$service_answer['user_auth_ok'] = 1;

										//Re-send updated user permissions and info so that it can be checked on app side:
										$service_answer['permissions'] = $this->get_user_permissions( $user_wp->ID, $app_id );
										$service_answer['info'] = $this->get_user_info( $user_wp->ID, $app_id );
								
									} else {
										$service_answer['auth_error'] = 'wrong-permissions';
									}
									
								} else {
									$service_answer['auth_error'] = 'wrong-public-key';
								}
							} else {
								//Depending on $result['auth_error'], can mean that the user
								//is not authenticated or that his secret has changed (if hmac check failed).
								$service_answer['auth_error'] = $result['auth_error'];
							}
						} else {
							$service_answer['auth_error'] = 'no-hash';
						}
					} else {
						$service_answer['auth_error'] = 'wrong-user';
					}
				}else {
					$service_answer['auth_error'] = 'wrong-auth-data';
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
	 * Checks that control data sent is valid.
	 * User authentication.getActionAuthData() on server side to generate $auth_data.
	 *
	 * @param int $app_id App id
	 * @param string $action Authentication action name
	 * @param array $auth_data Authentication data (user, control, timestamp)
	 * @param array $to_check Data we have to check validity for
	 */
	public function check_authenticated_action( $app_id, $action, $auth_data, $to_check ) {

		$result = array( 'ok' => false, 'auth_error' => '', 'user' => '' );

		$debug_mode = WpakBuild::get_app_debug_mode( $app_id ) === 'on';

		//First check user validity
		if ( !empty( $auth_data['user'] ) ) {

			//Check user exists
			$user = $auth_data['user'];
			$user_wp = $this->get_wp_user( $user );
			
			if ( $user_wp ) {

				//Check the user is not banned :
				if ( $this->check_user_is_allowed_to_authenticate( $user_wp->ID, $app_id ) ) {

					//Check if the user is authenticated for the given app :
					if ( $this->user_is_authenticated( $user_wp->ID, $app_id ) ) {

						if ( !empty( $auth_data['control'] ) && !empty( $auth_data['timestamp'] ) ) {

							$control_key = $this->get_user_secret( $user_wp->ID, $app_id ); //If the user is authenticated, he has a secret key

							$control = $auth_data['control'];

							$timestamp = $auth_data['timestamp'];

							$control_string = '';
							foreach( $to_check as $value ) {
								if ( is_string($value) || is_numeric( $value ) ) {
									$control_string .= $value;
								} elseif( is_bool( $value ) ) {
									$control_string .= $value ? '1' : '0';
								}
							}

							//Check control data :
							if ( $this->check_hmac( $action . $user . $timestamp . $control_string, $control_key, $control ) ) {

								if ( $this->check_query_time( $timestamp ) ) {

									$result['ok'] = true;
									$result['user'] = $user;

								} else {
									//If not in debug mode, don't give error details for security concern :
									$result['auth_error'] = $debug_mode ? 'wrong-query-time' : 'auth-error'; //Don't give more details for security concern
								}
							} else {
								//If not in debug mode, don't give error details for security concern :
								$result['auth_error'] = $debug_mode ? 'wrong-hmac' : 'auth-error'; //Don't give more details for security concern
							}
						} else {
							//If not in debug mode, don't give error details for security concern :
							$result['auth_error'] = $debug_mode ? 'wrong-auth-data' : 'auth-error'; //Don't give more details for security concern
						}
					} else {
						$connection_validity = $this->get_user_connection_validity( $user_wp->ID, $app_id );
						$result['auth_error'] = $connection_validity === 0 ? 'user-not-authenticated' : 'user-connection-expired';
					}
				} else {
					$result['auth_error'] = 'user-banned';
				}
			} else {
				$result['auth_error'] = 'wrong-user';
			}
		} else {
			$result['auth_error'] = 'no-user';
		}

		return $result;
	}

	/**
	 * Checks if the user is stored as authenticated on server side,
	 * which means he has a valid user secret in its meta.
	 *
	 * @param int|WP_User|string    $user       User to check
	 * @param int                   $app_id     App id to check the user for
	 * @return boolean True if user is authenticated
	 */
	public function user_is_authenticated( $user, $app_id ) {
		$user_is_authenticated = false;

		$user_id = 0;
		if( is_numeric( $user ) ) {
			$user_id = $user;
		}elseif ( is_a( $user, 'WP_User' ) ) {
			$user_id = $user->ID;
		} elseif( is_string( $user ) ) {
			if ( $user_wp = $this->get_wp_user( $user ) ) {
				$user_id = $user_wp->ID;
			}
		}

		if ( $user_id ) {
			$user_is_authenticated = ( $this->get_user_connection_validity( $user_id, $app_id ) > 0 );
		}

		return $user_is_authenticated;
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
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) && !empty( $user_auth_data['key'] ) ) {
			$user_secret = $user_auth_data['key'];
		}
		return $user_secret;
	}

	/**
	 * Get the current user connection validity : return values :
	 * -1 : connection has expired
	 * 0  : not connected at all
	 * 1  : connected ok
	 *
	 * Use the "wpak_auth_connection_expiration_time" filter to set the expiration time.
	 *
	 * @param int  $user_id              User id
	 * @param int  $app_id               App id
	 * @return int $connection_validity  -1:expired, 0:not_connected, 1:connected
	 */
	protected function get_user_connection_validity( $user_id, $app_id ) {
		$connection_validity = 0;

		$user_meta = '_wpak_auth_'. $app_id;

		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data )
			 && !empty( $user_auth_data['key'] )
			 &&	!empty( $user_auth_data['time'] )
			) {

			$user_secret_time = (int)$user_auth_data['time'];

			$default_expiration_time = 3600*24*3; //3 days

			/**
			* Filter 'wpak_auth_connection_expiration_time' :
			* use this to set the user connection expiration time.
			* Defaults is 3 days. Set -1 for no connection expiration.
			*
			* @param $expiration_time     int    Connection duration (in seconds). Defaults to 3 days. Return -1 for no expiration.
			* @param $user_id             int    User ID
			* @param $app_id              int    Application ID
			*/
			$expiration_time = apply_filters( 'wpak_auth_connection_expiration_time', $default_expiration_time, $user_id, $app_id );

			if ( $expiration_time === -1 ) {
				$connection_validity = 1;
			} else {
				$connection_validity = ( ( time() - $user_secret_time ) <= $expiration_time ) ? 1 : -1;
			}

		}

		return $connection_validity;
	}

	/**
	 * Retrieves user permisions (roles and capabilities) to return to the app when a user logs in.
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

		/**
		 * 'wpak_auth_user_permissions' filter: use this to add custom permissions info
		 * (like membership levels from a membership level for example) to default WP permissions data.
		 * 
		 * @param array $user_permissions WP roles and capabilities by default. Add your own custom permissions to that array.
		 * @param int   $user_id          User's WP ID
		 * @param int   $app_id           The app we're retrieving user's permissions for.
		 */
		$user_permissions = apply_filters( 'wpak_auth_user_permissions', $user_permissions, $user_id, $app_id );

		return $user_permissions;
	}
	
	/**
	 * Retrieves user info (login etc) to return to the app when a user logs in.
	 */
	protected function get_user_info( $user_id, $app_id ) {
		
		$wp_user = get_user_by( 'id', $user_id );
		
		if ( $wp_user ) {
			
			$user_info = array(
				'login' => $wp_user->user_login
			);

			/**
			 * 'wpak_auth_user_info' filter: use this to return custom user info to the app at login.
			 * 
			 * @param array $user_info  WP user info (by default, just user's login). Add your own custom user info to that array.
			 * @param int   $user_id    User's WP ID
			 * @param int   $app_id     The app we're retrieving user's info for.
			 */
			$user_info = apply_filters( 'wpak_auth_user_info', $user_info, $user_id, $app_id );
			
		}
		
		return $user_info;
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
	
	protected function check_user_permissions( $user_id, $app_id ) {
		
		$user_permissions = $this->get_user_permissions( $user_id, $app_id );
				
		/**
		 * Filter 'wpak_auth_user_permissions_ok' :
		 * this filter triggers when log in is ok, to allow further tests on user's permissions
		 * before considering the user as authenticated. Use this to base user log in on
		 * specific user permissions (for example user capabilities set by membership plugins).
		 *
		 * @param $user_permissions_ok Boolean Whether the user is allowed to authenticate (default true)
		 * @param $user_permissions array User permissions retrieved by self::get_user_permissions();
		 * @param $user_id Integer User ID
		 * @param $app_id Integer Application ID
		 */
		return apply_filters( 'wpak_auth_user_permissions_ok', true, $user_permissions, $user_id, $app_id );
	}
}
