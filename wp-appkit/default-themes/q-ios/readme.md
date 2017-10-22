<!--
Theme Name: Q for iOS
Description: A clean and simple iOS app news theme featuring: back button, content refresh, custom post types, embeds, infinite list, network detection, off-canvas menu, offline content, pages, posts, responsive, touch, transitions
Version: 1.0.6
Theme URI: https://github.com/uncatcrea/q-ios/
Author: Uncategorized Creations			
Author URI: http://uncategorized-creations.com	
WP-AppKit Version Required: >= 0.6
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Copyright: 2016 Uncategorized Creations	
-->

**Q for iOS** is a demo theme for mobile apps built with WP-AppKit, a WordPress plugin to create mobile apps connected to WordPress (more on that at http://getwpappkit.com).

**Please note that beginning with WP-AppKit 0.6, Q for iOS is pre-installed on plugin activation as the default theme for iOS applications.**

[![Q for iOS screencast](https://cloud.githubusercontent.com/assets/6179747/16109069/3ce3516c-33a7-11e6-8b90-507d661a3ffc.png)](https://www.youtube.com/watch?v=jkjtkH6wDys)

You might want to check **Q for Android**, another fine theme for WP-AppKit: [Q for Android](https://github.com/uncatcrea/q-android)

# Installation

**Please note that beginning with WP-AppKit 0.6, Q for iOS is pre-installed on plugin activation as the default theme for iOS applications.**

* Download WP-AppKit: [https://github.com/uncatcrea/wp-appkit/releases](https://github.com/uncatcrea/wp-appkit/releases)
* Install WP-AppKit as you would do for any other WordPress plugins (ie. drop the plugin folder in */wp-content/plugins*)
* Activate WP-AppKit using the _Plugins_ WordPress admin panel. (Browse the *Installed Plugins* list and click the *Activate* link of WP-AppKit.)
* Now you should have a brand new */wp-content/themes-wp-appkit* folder (yes, this is where app themes are stored)
* Download the Q for iOS from [https://github.com/uncatcrea/q-ios/releases](this repository) and drop its folder in */wp-content/themes-wp-appkit*
* In WordPress, use the *WP-AppKit* admin panel to create a new app and choose one of the themes in the *Appearance* box
* From there you're free to test in your browser (using the Chrome's Emulation Mode) or directly try to compile

If new to WP-AppKit, you might want to read this article: http://uncategorized-creations.com/1212/compiling-app-using-wp-appkit-phonegap-build/.

# Theme's Overview

## What's implemented
At the moment, Q for iOS theme implements:
* A clean iOS UI (including transitions)
* Off-canvas menu (you can update it in the app's edit panel)
* Archive template (eg. post list) with infinite post list
* Single template (eg. a post) with the most common HTML elements (eg. strong, em, blockquote...)
* Responsive embeds
* The refresh process
* Offline cache (meaning that you can read loaded posts offline)
* Post thumbnail captions and subhead support
* The iOS back button
* The iOS status bar 
* A responsive interface

## iOS UI
* Back button when displaying the single view (ie. a post)
* In app browser to open external links
* Status bar color matches the theme
* iOS spinner

## Cordova Plugins
Q for iOS relies on Cordova plugins to:
* Customize the iOS status bar: [https://github.com/apache/cordova-plugin-statusbar](https://github.com/apache/cordova-plugin-statusbar)
* Open external links in an in app browser: [https://github.com/apache/cordova-plugin-inappbrowser](https://github.com/apache/cordova-plugin-inappbrowser)

WP-AppKit export function adds these plugins automatically to your *config.xml* file. If you don't use the export, make sure to add them in order the theme to be able to work properly.

## Responsive Images
Q for iOS hooks into the WP-AppKit web services to modify the source code of images in the post content. It eases the way to style the responsive images later. See *prepare-content.php* in the *php* folder of the themes.

## Thumbnail Captions and Subheads
Q for iOS hooks into the WP-AppKit web services to modify add thumbnail captions and subheads (if available) in the post content. See *add-custom-data.php* in the *php* folder of the themes. Note that subheads are expected as a post custom field.
