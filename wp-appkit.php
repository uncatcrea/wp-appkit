<?php
/*
Plugin Name: WP-AppKit
Plugin URI:  https://github.com/uncatcrea/wp-appkit
Description: Build mobile apps and PWA based on your WordPress content.
Version:     1.5.2
Author:      Uncategorized Creations
Author URI:  http://getwpappkit.com
Text Domain: wp-appkit
Domain Path: /lang
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Copyright:   2013-2018 Uncategorized Creations

This plugin, like WordPress, is licensed under the GPL.
Use it to make something cool, have fun, and share what you've learned with others.
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