<?php

class WpakUserRegistration {

	/**
	 * Registers a new user from app's registration form.
	 * 
	 * @param array $user_data  Array of user's data fields coming from the app's registration form.
	 *                          By default, only "user_email" is required.
	 *							'user_login', 'user_pass' and 'user_pass_confirm' are also interpreted in this function.
	 *							And you can handle your own additionnal fields with the 'wpak_user_registration_fields_check' and 
	 *							'wpak_register_user_data' filters.
	 * 
	 * @param int   $app_id     Current app ID.
	 * 
	 * @return array (
	 *		'ok'              => boolean
	 *		'error'           => array of error strings
	 *      'registered_user' => array: registered user's data
	 * )
	 */
	public static function register_user( $user_data, $app_id ) {

		$result = array(
			'ok' => false,
			'errors' => array(),
			'registered_user' => array()
		);

		if ( !get_option( 'users_can_register' ) ) {
			$result['error'] = 'users-registration-disabled';
			return $result;
		}

		$user_fields_check = array( 
			'ok' => true, 
			'errors' => array()
		);
				
		/**
		 * Use this 'wpak_user_registration_fields_check' filter to check that user fields passed by the app's register form are valid.
		 * 
		 * Note : user email does not need to be checked here because it is mandatory no matter what for WP user registration,
		 * so we check it hereunder. 
		 * Email is the only registration field that we consider as mandatory. 
		 * If only 'email' is provided, then WP-AppKit sets login = email.
		 * For any other field that you want to consider required, you must define it using this 'wpak-user-registration-fields-check' filter.
		 * 
		 * $user_fields_check   array     Set $user_fields_check['ok'] = false and custom errors ids in $user_fields_check['errors']
		 *                                to return errors.
		 * $user_data           array     User data coming from the app's registration form.
		 * $app_id              int       Current app id.
		 */
		$user_fields_check = apply_filters( 'wpak_user_registration_fields_check', $user_fields_check, $user_data, $app_id );
		
		if ( !$user_fields_check['ok'] ) {
			$result['errors'] = $user_fields_check['errors'];
			return $result;
		}

		//Check user email:
		$user_email = isset( $user_data['user_email'] ) ? trim( $user_data['user_email'] ) : '';
		if ( !empty( $user_email ) ) {

			if ( is_email( $user_email ) ) {

				if ( !email_exists( $user_email ) ) {

					//Check user login:
					$user_login = isset( $user_data['user_login'] ) ? trim( $user_data['user_login'] ) : '';
					if ( empty( $user_login ) ) {
						$user_login = $user_email;
					}
					
					$user_login = sanitize_user( $user_login );
					
					if ( !empty( $user_login ) && validate_username( $user_login ) ) {
						
						if ( !username_exists( $user_login ) ) {
							
							//Check password:
							$user_pass = isset( $user_data['user_pass'] ) ? trim( $user_data['user_pass'] ) : '';
							$user_pass_confirm = isset( $user_data['user_pass_confirm'] ) ? trim( $user_data['user_pass_confirm'] ) : '';
							
							if ( empty( $user_pass ) ) {
								$user_pass = wp_generate_password( 12, false ); //Same as default WP registration
							}
							
							if ( empty( $user_pass_confirm ) || $user_pass === $user_pass_confirm ) {
								
								$final_user_data = array(
									'user_login' => $user_login,
									'user_email' => $user_email,
									'user_pass' => $user_pass,
									'user_registered' => date( 'Y-m-d H:i:s' ),
									'role' => get_option( 'default_role' )
								);

								/**
								 * Use this 'wpak_register_user_data' filter to add any custom data to the registerd user 
								 * (for example 'first_name', 'last_name' etc...)
								 * See the WordPress wp_insert_user() function for all possible fields.
								 * 
								 * $final_user_data    array    User data to be filtered. Add your custom data to this array.
								 * $user_data          array    Original user data coming from the app's registration form.
								 * $app_id             int      Current App ID
								 */
								$final_user_data = apply_filters( 'wpak_register_user_data', $final_user_data, $user_data, $app_id );

								// Insert new user
								$user_id = wp_insert_user( $final_user_data );
								
								// Validate inserted user
								if ( !is_wp_error( $user_id ) ){
									
									/**
									 * User this 'wpak_user_registered' filter to set additionnal user (meta) data
									 * once the user was created successfully. This is a filter and not an action so
									 * that errors can be returned.
									 * 
									 * Return errors array if an error occured, or empty array if no error.
									 * 
									 * $user_id            int      ID of the newly created user
									 * $final_user_data    array    User data that was passed to wp_insert_user
									 * $user_data          array    Original user data coming from the app's registration form.
									 * $app_id             int      Current App ID
									 */
									$errors = apply_filters( 'wpak_user_registered', array(), $user_id, $final_user_data, $user_data, $app_id );
									
									if ( empty( $errors ) ) {
										
										//Send notification emails:
									
										/**
										 * Use this 'wpak_registration_notification' to choose whether or not
										 * sending notification to admin and to the registered user.
										 * 
										 * Default type = 'both'.
										 * Return false for no notification.
										 * See wp_new_user_notification() for possible other notifications type values.
										 */
										$notification_type = apply_filters( 'wpak_registration_notification', 'both', $user_id, $app_id );
										if ( $notification_type !== false ) {
											wp_new_user_notification( $user_id, null, $notification_type );
										}
										
										$result['ok'] = true;
										$result['registered_user'] = $final_user_data + array( 'user_id' => $user_id );
										
									} else {
										$result['errors'] = $errors;
									}
									
								} else {
									$result['errors'][] = 'insert-user-error';
								}
								
							} else {
								$result['errors'][] = 'password-mismatch';
							}
							
						} else {
							$result['errors'][] = 'user-login-exists';
						}
						
					} else {
						$result['errors'][] = 'invalid-user-login';
					}
					
				} else {
					$result['errors'][] = 'email-exists';
				}
				
			} else {
				$result['errors'][] = 'invalid-email';
			}
			
		} else {
			$result['errors'][] = 'no-email';
		}

		return $result;
	}

}
