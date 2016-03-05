<?php
/*
  Plugin Name: WP AppKit
  Description: Build Phonegap Mobile apps based on your WordPress content
  Version: 0.5
 */

/**
 * This is a bootstrap so that WP-AppKit git repository can be cloned directly in
 * a previously created "wp-content/plugins/wp-appkit" directory 
 * with "git clone https://github.com/uncatcrea/wp-appkit ."
 */

require_once(dirname(__FILE__) .'/wp-appkit/wp-appkit.php');

//We have to register activation/deactivation hooks on the current file:
register_activation_hook( __FILE__, array( 'WpAppKit', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'WpAppKit', 'on_deactivation' ) );