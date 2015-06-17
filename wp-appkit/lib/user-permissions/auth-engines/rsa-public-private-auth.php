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
		$public_key = '';
		$auth_settings = $this->get_authentication_settings( $app_id );
		if ( !empty( $auth_settings['private_key'] ) ) {
			$public_key = $this->get_public_key_from_private_key( $auth_settings['private_key'] );
		}
		return $public_key;
	}
	
	public function get_webservice_answer( $app_id ) {
		$service_answer = array();
		
		$auth_params = WpakWebServiceContext::getClientAppParams();
		
		switch( $auth_params['auth_action'] ) {
			case "get_public_key":
				if ( !empty( $auth_params['user'] ) 
					 && !empty( $auth_params['control'] )
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
						
							$public_key = self::get_app_public_key( $app_id );
							if ( !empty( $public_key ) ) {
								$service_answer = array( 'public_key' => $public_key );
							}
							
						}
					}
				}
				break;
		}
		
		return $service_answer;
	}
	
	protected function check_hmac( $data, $secret, $to_check ) {
		$hmac = hash( 'sha256', $data .'|'. $secret );
		return $hmac === $to_check;
	}
	
	protected function check_query_time( $query_timestamp ) {
		$diff = time() - $query_timestamp;
		$acceptable = apply_filters( 'wpak-auth-acceptable-delay', 60); //seconds
		return $diff <= $acceptable;
	}
}
