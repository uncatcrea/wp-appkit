<!--
Theme Name: Q for Android
Description:  A clean and simple Android app news theme featuring: back button, comments, content refresh, custom post types, embeds, infinite list, latest posts, native sharing, network detection, off-canvas menu, offline content, pages, posts, pull to refresh, responsive, status bar, touch, transitions
Version: 1.1.2
Theme URI: https://github.com/uncatcrea/q-android
Author: Uncategorized Creations			
Author URI: http://uncategorized-creations.com	
WP-AppKit Version Required: >= 0.6
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Copyright: 2016 Uncategorized Creations	
-->

**Q for Android** is a demo theme for mobile apps and Progressive Web Apps (PWA) built with WP-AppKit, a WordPress plugin to create mobile apps connected to WordPress (more on that at http://getwpappkit.com).

**Please note that beginning with WP-AppKit 0.6, Q for Android is pre-installed on plugin activation as the default theme for Android applications.**

[![Screencast of Q for Android](https://cloud.githubusercontent.com/assets/7415862/16109551/c05a183a-33a9-11e6-868f-bcc1c23df5da.png)](https://www.youtube.com/watch?v=fSQVx8-rqCY)

You might want to check **Q for iOS**, another fine theme for WP-AppKit: [Q for iOS](https://github.com/uncatcrea/q-ios)

# Installation

**Please note that beginning with WP-AppKit 0.6, Q for Android is pre-installed on plugin activation as the default theme for Android applications.**

* Download WP-AppKit: [https://github.com/uncatcrea/wp-appkit/releases](https://github.com/uncatcrea/wp-appkit/releases)
* Install WP-AppKit as you would do for any other WordPress plugins (ie. drop the plugin folder in */wp-content/plugins*)
* Activate WP-AppKit using the _Plugins_ WordPress admin panel. (Browse the *Installed Plugins* list and click the *Activate* link of WP-AppKit.)
* Now you should have a brand new */wp-content/themes-wp-appkit* folder (yes, this is where app themes are stored)
* Download the Q for Android from [this repository](https://github.com/uncatcrea/q-android/releases) and drop its folder in */wp-content/themes-wp-appkit*
* In WordPress, use the *WP-AppKit* admin panel to create a new app and choose one of the themes in the *Appearance* box
* From there you're free to test in your browser (using the Chrome's Emulation Mode) or directly try to compile

If new to WP-AppKit, you might want to read this article: http://uncategorized-creations.com/1212/compiling-app-using-wp-appkit-phonegap-build/.

# Theme's Overview

## What's implemented
At the moment, Q for Android theme implements:
* A shiny material design UI (including transitions and ripple effect)
* Off-canvas menu (you can update it in the app's edit panel)
* Archive template (eg. post list) with infinite post list
* Single template (eg. a post) with the most common HTML elements (eg. strong, em, blockquote...)
* Responsive embeds
* The refresh process
* Offline cache (meaning that you can read loaded posts offline)
* Post thumbnail captions and subhead support
* A responsive interface

## Android UI
* Ripple effect
* Status bar color matches the theme
* Android spinner

## Cordova Plugins
Q for Android relies on Cordova plugins to:
* Customize the iOS status bar: [https://github.com/apache/cordova-plugin-statusbar](https://github.com/apache/cordova-plugin-statusbar)
* Open external links in default browser (ie. Chrome): [https://github.com/apache/cordova-plugin-inappbrowser](https://github.com/apache/cordova-plugin-inappbrowser)

WP-AppKit export function adds these plugins automatically to your *config.xml* file. If you don't use the export, make sure to add them in order the theme to be able to work properly.

## Responsive Images
Q for Android hooks into the WP-AppKit web services to modify the source code of images in the post content. It eases the way to style the responsive images later. See *prepare-content.php* in the *php* folder of the themes.

## Thumbnail Captions and Subheads
Q for Android hooks into the WP-AppKit web services to modify add thumbnail captions and subheads (if available) in the post content. See *add-custom-data.php* in the *php* folder of the themes. Note that subheads are expected as a post custom field.
