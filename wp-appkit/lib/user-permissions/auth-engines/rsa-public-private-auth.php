<?php

require_once( dirname( __FILE__ ) .'/abstract-auth-engine.php' );

class WpakRsaPublicPrivateAuth extends WpakAuthEngine {

	const auth_meta_id = '_wpak_rsa_auth_settings';

	public function settings_meta_box_content( $post, $current_box ) {

		//Include theme and addons php files so that authentication hooks are applied
		WpakThemes::include_app_theme_php( $post->ID );
		WpakAddons::require_app_addons_php_files( $post->ID );

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
					<br>
					<?php _e( 'See our user authentication tutorial here:', WpAppKit::i18n_domain ) ?> <a href="https://uncategorized-creations.com/2323/wordpress-user-authentication-in-wp-appkit/">https://uncategorized-creations.com/2323/wordpress-user-authentication-in-wp-appkit/</a>
					<br>
					<?php _e( 'And our user authentication demo theme here:', WpAppKit::i18n_domain ) ?> <a href="https://github.com/mleroi/q-android/tree/feat-authentication">https://github.com/mleroi/q-android/tree/feat-authentication</a>
				</p>
			</div>
			<?php
		}

		if ( !empty( $auth_settings['private_key'] ) ) {
			?><h4><?php _e( 'User connections', WpAppKit::i18n_domain ) ?></h4>
			<?php
			$current_connections = $this->get_current_connections( $post->ID );
			?>
			<p class="description">
				<?php _e( "Here are the users that are currently having an active connection from their app.
						   <br>Note that it does not necessarily mean that they are currently connected right now.
				           It means that they last accessed the app while being connected on one of their device less than 'connection_expiration_time' ago.
				           <br>The connection is persistent accross devices: when the user accesses the app on one device it extends its connection validity on the other devices he/she is connected on.
				           <br>Users are automatically logged out from a device if they:
				           <br>- did not access the app from the device for a time longer than 'purge_time' (customizable with 'wpak_auth_purge_time' hook).
				           <br>- did not access the app from any of their devices for a time longer than 'connection_expiration_time' (customizable with 'wpak_auth_connection_expiration_time' hook).
				           ", WpAppKit::i18n_domain ) ?>
			</p>
			<table class="wp-list-table widefat fixed">
				<thead>
					<tr>
						<th style="width:25%"><?php _e( 'User', WpAppKit::i18n_domain ) ?></th>
						<th style="width:25%"><?php _e( 'Device ID', WpAppKit::i18n_domain ) ?></th>
						<th style="width:25%"><?php _e( 'Login time', WpAppKit::i18n_domain ) ?></th>
						<th style="width:25%"><?php _e( 'Last access time', WpAppKit::i18n_domain ) ?></th>
						<th style="width:25%"><?php _e( 'Validity', WpAppKit::i18n_domain ) ?></th>
						<th style="width:25%"><?php _e( 'Expiration time', WpAppKit::i18n_domain ) ?></th>
					</tr>
				</thead>
				<tbody>

			<?php
			if ( !empty( $current_connections ) ) {
				$cpt=0;
				foreach( $current_connections as $user_id => $connections ) {
					$alternate_class = $cpt%2 ? '' : 'alternate';
					$user = get_user_by( 'id', $user_id );
					?>
					<tr class="component-row <?php echo $alternate_class; ?>">
						<td>
							<a href="<?php echo get_edit_user_link( $user_id ) ?>"><?php echo $user->user_login; ?></a>
							<br>
							<?php $nb_devices = count($connections); ?>
							<?php echo $nb_devices .' '. _n( 'active device', 'active devices', $nb_devices, WpAppKit::i18n_domain ); ?>
						</td>
						<td colspan="3" style="padding:0">
							<table style="width:100%">
							<?php foreach( $connections as $device_id => $connection ): ?>
								<tr>
									<td style="width:33%"><?php echo $device_id !== 0 ? $device_id : __( 'Unknown (old app version)', WpAppKit::i18n_domain ) ?></td>
									<td style="width:33%"><?php echo get_date_from_gmt( date( 'Y-m-d H:i:s', $connection['login_time'] ) ) ?></td>
									<td style="width:33%"><?php echo get_date_from_gmt( date( 'Y-m-d H:i:s', $connection['last_access_time'] ) ) ?></td>
								</tr>
							<?php endforeach; ?>
							</table>
						</td>
						<td>
							<?php
								$user_last_time = $this->get_user_last_time( $user_id, $post->ID );
								if ( $user_last_time ) {
									$expiration_type = $this->get_expiration_type($user_id, $post->ID);
									$validity_duration = $this->get_expiration_time($user_id, $post->ID);
									echo human_time_diff( 0, $validity_duration ) .' ';
									echo $expiration_type == 'last_access_time' ? __( 'from last access time', WpAppKit::i18n_domain ) : __( 'from login time', WpAppKit::i18n_domain );
								}
							?>
						</td>
						<td>
							<?php
								if ( $user_last_time ) {
									echo get_date_from_gmt( date( 'Y-m-d H:i:s', $user_last_time + $validity_duration ) );
								}
							?>
						</td>
					</tr>
					<?php
					$cpt++;
				}
			} else {
				?>
				<tr class="component-row alternate">
					<td colspan="2"><?php _e( 'No active user connections for now', WpAppKit::i18n_domain ); ?></td>
				</tr>
				<?php
			}
			?>
				</tbody>
			</table>
			<?php
		}

	}

	protected function get_current_connections( $app_id ) {
		global $wpdb;
		$user_meta = '_wpak_auth_'. $app_id;
		$current_connections = [];
		$current_connections_raw = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key = '$user_meta'", ARRAY_A );
		if ( !empty( $current_connections_raw ) ) {
			foreach( $current_connections_raw as $current_connection ) {
				$user_auth_data = unserialize($current_connection['meta_value']);
				if( !empty( $user_auth_data['key'] ) ) {
					$user_auth_data = ['legacy' => [
						'key' => $user_auth_data['key'],
						'login_time' => $user_auth_data['time'],
						'last_access_time' => $user_auth_data['time'],
					]];
				}
				$current_connections[$current_connection['user_id']] = $user_auth_data;
			}
		}
		return $current_connections;
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
					$device_id = !empty( $auth_params['device_id'] ) ? $auth_params['device_id'] : 0; //Have to handle possible empty device_id for legacy

					//Decrypt data to retrieve user HMAC secret key :
					$decrypted = $this->decrypt( $app_id, $encrypted );

					if ( is_array( $decrypted ) && !empty( $decrypted['secret'] ) ) {

						$user_secret_key = $control_key = $decrypted['secret'];

						if ( $this->check_secret_format( $user_secret_key ) ) {

							if ( $this->check_hmac( $auth_params['auth_action'] . $user . $timestamp . $encrypted . (!empty( $device_id) ? $device_id : ''), $control_key, $control )
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

													//Purge old user sessions
													$this->clean_user_auth_data( $user_wp->ID, $app_id );

													//Memorize user as registered and store its secret control key
													$this->authenticate_user( $user_wp->ID, $user_secret_key, $device_id, $app_id );

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

							$device_id = !empty( $auth_params['device_id'] ) ? $auth_params['device_id'] : 0; //Have to handle possible empty device_id for legacy

							$to_check = array( $auth_params['hash'], $auth_params['hasher'] );

							if ( !empty( $device_id ) ) {
								$to_check[] = $device_id;
							}

							$result = $this->check_authenticated_action( $app_id, 'check_user_auth', $auth_params, $to_check );
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

					//Purge old user sessions
					$this->clean_user_auth_data( $user_wp->ID, $app_id );

					//Check if the user is authenticated for the given app :
					if ( $this->user_is_authenticated( $user_wp->ID, $app_id ) ) {

						if ( !empty( $auth_data['control'] ) && !empty( $auth_data['timestamp'] ) ) {

							$device_id = !empty( $auth_data['device_id'] ) ? $auth_data['device_id'] : 0;

							//For legacy: if the user was connected to server with old meta format:
							$user_meta = '_wpak_auth_'. $app_id;
							$user_auth_data = get_user_meta( $user_wp->ID, $user_meta, true );
							if ( !empty( $user_auth_data ) && is_array( $user_auth_data )
								 && !empty( $user_auth_data['key'] ) && !empty( $user_auth_data['time'] ) ) {
								//Replace old format by new one:
								$user_auth_data = [
									'key' => $user_auth_data['key'],
									'login_time' => $user_auth_data['time'],
									'last_access_time' => $user_auth_data['time'],
								];
								$user_auth_data = [$device_id => $user_auth_data];
								update_user_meta( $user_wp->ID, $user_meta, $user_auth_data );
							}

							$control_key = $this->get_user_secret( $user_wp->ID, $device_id, $app_id ); //If the user is authenticated, he has a secret key

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

							if ( $this->check_hmac( $action . $user . $timestamp . $control_string, $control_key, $control ) ) {

								if ( $this->check_query_time( $timestamp ) ) {

									$result['ok'] = true;
									$result['user'] = $user;

									$this->update_user_access_time( $user_wp->ID, $device_id, $app_id );

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
	protected function authenticate_user( $user_id, $user_secret_key, $device_id, $app_id ) {

		$user_meta = '_wpak_auth_'. $app_id;

		//For legacy:
		if ( empty( $device_id ) ) {
			$device_id = 0;
		}

		$user_auth_data = get_user_meta( $user_id, $user_meta, true );

		$time = time();
		$auth_data = [
			'key' => $user_secret_key,
			'login_time' => $time,
			'last_access_time' => $time,
		];

		$allow_multiple_login = apply_filters( 'wpak_auth_allow_multiple_login', true, $user_id, $app_id );

		if ( $allow_multiple_login && !empty( $user_auth_data ) && is_array( $user_auth_data ) ) {
			if ( !empty( $user_auth_data['key'] ) ) {
				//Legacy
				$user_auth_data = [$device_id => $auth_data];
			} else {
				//Register user connection for the given device id:
				$user_auth_data[$device_id] = $auth_data;
			}
		} else {
			$user_auth_data = [$device_id => $auth_data];
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
	protected function get_user_secret( $user_id, $user_device_id, $app_id ) {
		$user_secret = '';
		$user_meta = '_wpak_auth_'. $app_id;
		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) && !empty( $user_auth_data[$user_device_id]['key'] ) ) {
			$user_secret = $user_auth_data[$user_device_id]['key'];
		}
		return $user_secret;
	}

	/**
	 * Updates user's last access time for the given secret key
	 */
	protected function update_user_access_time( $user_id, $device_id, $app_id ) {
		$user_meta = '_wpak_auth_'. $app_id;
		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) && !empty( $user_auth_data[$device_id]['key'] ) ) {
			$user_auth_data[$device_id]['last_access_time'] = time();
			update_user_meta( $user_id, $user_meta, $user_auth_data );
		}
	}

	/**
	 * Removes expired devices from given auth_data
	 *
	 * Note: a device can have a "last_access_time" > "connection_expiration_time" but its connection is still
	 * valid if at least one other device from the same user accessed the app less than "connection_expiration_time" ago (see get_user_connection_validity()).
	 * Here we remove the devices that have not accessed the app longer than "purge_time" ago, so that unused devices are removed from the list.
	 * We also remove all the user devices if none has access the app for a time longer than "expiration_time".
	 */
	protected function clean_user_auth_data( $user_id, $app_id ) {
		$user_meta = '_wpak_auth_'. $app_id;
		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) ) {

			//For legacy:
			if ( !empty( $user_auth_data['key'] ) ) {
				return;
			}

			$expiration_type = $this->get_expiration_type( $user_id, $app_id );
			$expiration_time = $this->get_expiration_time( $user_id, $app_id );

			//Purge time. 1 month by default :
			$purge_time = apply_filters( 'wpak_auth_purge_time', 30*24*3600, $user_id, $app_id );
			if ( $purge_time < $expiration_time ) {
				$purge_time = $expiration_time; //Purge time must be >= to expiration time
			}

			$time = time();
			$changed = false;
			$all_expired = true;
			foreach( $user_auth_data as $device_id => $auth_data ) {
				//Purge devices that did not access the app more than "purge_time" ago
				if ( $time - $auth_data['last_access_time'] > $purge_time ) {
					unset( $user_auth_data[$device_id] );
					$changed = true;
				}
				if ( $time - $auth_data[$expiration_type] < $expiration_time ) {
					unset( $user_auth_data[$device_id] );
					$all_expired = false;
				}
			}

			if ( $all_expired ) {
				$user_auth_data = [];
				$changed = true;
			}

			if ( $changed ) {
				update_user_meta( $user_id, $user_meta, $user_auth_data );
			}
		}
	}

	protected function get_expiration_type( $user_id, $app_id ) {
		/**
		* Filter 'wpak_auth_connection_expiration_type' :
		* use this to choose the type of connection expiration: login_time or last_access_time
		* Defaults is 'last_access_time'.
		* 'login_time': connection expires when last user login happened more than $expiration_time ago
		* 'last_access_time': connection expires when last user authenticated access happened more than $expiration_time ago
		*
		* @param $expiration_type     int    Expiration type
		* @param $user_id             int    User ID
		* @param $app_id              int    Application ID
		*/
		return apply_filters( 'wpak_auth_connection_expiration_type', 'last_access_time', $user_id, $app_id );
	}

	protected function get_expiration_time( $user_id, $app_id ) {

		$default_connection_expiration_time = 3600*24*3; //3 days

		/**
		* Filter 'wpak_auth_connection_expiration_time' :
		* use this to set the user connection expiration time.
		* Default is 3 days. Set -1 for no connection expiration.
		*
		* @param $expiration_time     int    Connection duration (in seconds). Defaults to 3 days. Return -1 for no expiration.
		* @param $user_id             int    User ID
		* @param $app_id              int    Application ID
		*/
		return apply_filters( 'wpak_auth_connection_expiration_time', $default_connection_expiration_time, $user_id, $app_id );
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

		$user_last_time = $this->get_user_last_time( $user_id, $app_id );
		if ( $user_last_time ) {

			$expiration_time = $this->get_expiration_time( $user_id, $app_id );

			if ( $expiration_time === -1 ) {
				$connection_validity = 1;
			} else {
				$connection_validity = ( ( time() - $user_last_time ) <= $expiration_time ) ? 1 : -1;
			}
		}

		return $connection_validity;
	}

	/**
	 * Get the given user last login or access time (according to expiration_type)
	 *
	 * @param int  $user_id              User id
	 * @param int  $app_id               App id
	 * @return int $user_last_time       Timestamp or 0 if no valid last time found
	 */
	protected function get_user_last_time( $user_id, $app_id ) {
		$user_last_time = 0;

		$user_meta = '_wpak_auth_'. $app_id;

		$user_auth_data = get_user_meta( $user_id, $user_meta, true );
		if ( !empty( $user_auth_data ) && is_array( $user_auth_data ) ) {

			//For legacy
			if ( !empty( $user_auth_data['key'] ) && !empty( $user_auth_data['time'] ) ) {
				$user_auth_data = [[
					'key' => $user_auth_data['key'],
					'login_time' => $user_auth_data['time'],
					'last_access_time' => $user_auth_data['time'],
				]];
			}

			$last_login_time = 0;
			$last_access_time = 0;
			foreach( $user_auth_data as $auth_data ) {
				if ( empty( $auth_data['key'] ) || empty( $auth_data['login_time'] ) || empty( $auth_data['last_access_time'] ) ) {
					continue;
				}
				$auth_data_login_time = (int)$auth_data['login_time'];
				if ( $auth_data_login_time > $last_login_time ) {
					$last_login_time = $auth_data_login_time;
				}
				$auth_data_access_time = (int)$auth_data['last_access_time'];
				if ( $auth_data_access_time > $last_access_time ) {
					$last_access_time = $auth_data_access_time;
				}
			}

			if ( empty( $last_login_time ) ) {
				return 0; //Not connected
			}

			$expiration_type = $this->get_expiration_type( $user_id, $app_id );

			if ( $expiration_type === 'login_time' ) {
				$user_last_time = $last_login_time;
			} else if ( $expiration_type === 'last_access_time' ) {
				$user_last_time = $last_access_time;
			}

		}

		return $user_last_time;
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
