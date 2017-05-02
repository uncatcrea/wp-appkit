<?php

class WpakLicenses {
   
    protected static $licences = null;
   
    public static function tab_licenses() {
        $result = self::handle_posted_licenses();
        $licenses = self::get_licenses();
       
        $licence_check = self::check_licence( 'WP-AppKit Google Analytics Addon', 'c5d2414250c59bb718885ca9d7517111' );
        var_dump($licence_check);
       
        ?>
       
        <?php if ( !empty( $result['message'] ) ): ?>
            <div class="<?php echo $result['type'] ?>" ><p><?php echo $result['message'] ?></p></div>
        <?php endif ?>
           
        <?php if ( !empty( $licenses ) ): ?>
           
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
                        <?php foreach( $licenses as $license ): ?>

                            <?php $license_form_id = 'license_key_'. $license->item_shortname; ?>

                            <tr>
                                <th scope="row"><?php echo $license->item_name; ?></th>
                                <td>
                                    <input class="regular-text" id="<?php echo esc_attr( $license_form_id ); ?>" name="wpak_licences[<?php echo esc_attr( $license_form_id ); ?>]" value="<?php echo ''; ?>" type="text" placeholder="<?php _e( 'License key', WpAppKit::i18n_domain ); ?>">
                                    <label for="<?php echo esc_attr( $license_form_id ); ?>"> </label>
                                    <div class="edd-license-data edd-license-empty  ">
                                        <p><?php echo sprintf( __('To receive support and updates, please enter your valid <em>%s</em> license key.', WpAppKit::i18n_domain ), $license->item_name ); ?></p>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php wp_nonce_field( 'wpak_save_licenses' ) ?>
               
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php echo esc_attr( __( 'Save changes', WpAppKit::i18n_domain ) ); ?>" />
                </p>

            </form>
           
        <?php else: ?>
           
            <p><?php _e( 'No WP-AppKit Theme or Addon requiring a license found.', WpAppKit::i18n_domain ); ?></p>
           
        <?php endif; ?>
           
        <?php
    }
   
    protected static function get_licenses() {
       
        if ( self::$licences === null ) {
            
            self::$licences = array();
            
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
                
                $api_url = !empty( $license_raw['api_url'] ) ? $license_raw['api_url'] : '';
                $item_id = !empty( $license_raw['item_id'] ) ? $license_raw['item_id'] : '';
                        
                $license = new WpakLicence( $license_raw['file'], $license_raw['item_name'], $license_raw['version'], $license_raw['author'], $api_url, $item_id );
               
                self::$licences[$license->item_shortname] = $license;
               
            }
        }
       
        return self::$licences;
    }

    protected static function handle_posted_licenses() {
       
    }
   
    protected static function check_licence( $product_id, $licence_key ) {
        $result = array( 'ok' => false, 'answer' => array(), 'message' => '' );
       
        $answer = self::licence_remote_call( 'check_license', $product_id, $licence_key );
        var_dump( $answer );
       
        return $result;
    }
   
    protected static function licence_remote_call( $action, $product_id, $licence_key, $api_url = '' ) {
        $result = array( 'ok' => false, 'answer' => array(), 'message' => '' );
       
        $api_url = empty( $api_url ) ? 'https://local.uncategorized-creations.com' : $api_url;
       
        $url = add_query_arg( array(
            'edd_action' => $action,
            'item_name' => $product_id,
            'license' => $licence_key,
            'url' => site_url(),
        ), $api_url );
       
        $api_result = wp_remote_post( $url, array( 'sslverify' => false ) );
       
        if ( !is_wp_error( $api_result ) ) {
            if ( !empty( $api_result['response']['code'] ) && $api_result['response']['code'] === 200 ) {
                $answer_json = json_decode( $api_result['body'] );
                if ( !empty( $answer_json ) ) {
                    $result['answer'] = $answer_json;
                    $result['ok'] = true;
                }
            }
        }
       
        return $result;
    }
}

class WpakLicence {
   
    protected $file;
    protected $license;
    protected $item_name;
    protected $item_id;
    protected $item_shortname;
    protected $version;
    protected $author;
    protected $api_url = '';
    
    const licences_option_id = 'wpak_licenses';
   
    public function __construct( $file, $item_name, $version, $author, $api_url = null, $item_id = null ) {
        $this->file = $file;
        $this->item_name = $item_name;

        if ( is_numeric( $item_id ) ) {
            $this->item_id = absint( $item_id );
        }

        $this->item_shortname = 'edd_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
        $this->version        = $version;
        $this->license        = trim( $this->get_local_data( $this->item_shortname . '_license_key', '' ) );
        $this->author         = $author;
        $this->api_url        = is_null( $api_url ) ? $this->api_url : $api_url;
    }
   
    public function __get( $field ) {
        return isset( $this->{$field} ) ? $this->{$field} : null;
    }
    
    /**
     * Retrieve licence data stored in options
     * 
     * @param type $licence_shortname
     * @return type
     */
    protected function get_local_data( $key ) {
        $licence_data = null;
        $licences_raw = get_option( self::licences_option_id );
        if ( !empty( $licences_raw ) ) {
            if ( !empty( $this->item_shortname ) && isset( $licences_raw[$this->item_shortname][$key] ) ) {
                $licence_data = $licences_raw[$this->item_shortname][$key];
            }
        }
        return $licence_data;
    }
}