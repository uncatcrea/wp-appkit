=== WP-AppKit - Mobile apps and PWA for WordPress ===
Contributors: uncategorized-creations, benjaminlupu, lpointet, mleroi
Tags: pwa, mobile app, android, ios, progressive web app, phonegap build
Requires at least: 4.0
Tested up to: 4.9.6
Stable tag: 1.5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**NEW FEATURE - Progressive web apps**: support for progressive web applications (PWA) has been added to 1.5 release. PWA are a new way to deliver fast, reliable and great experience on the web notably for mobiles. They allow to create apps accessible as websites that you can install and access as traditionnal mobile apps.

A great way to build progressive web apps (PWA) and mobile apps for your WordPress site using your favorite technologies: JavaScript, HTML and CSS.

Progressive web apps (PWA) and mobile applications are a great way to offer an outstanding mobile experience for your users. Using push notifications, letting users read offline, using saved accounts to share content are among many wonderful things you can do with mobile applications.

= The Toolkit You Need to Build Your App =

* **Native support of WordPress**: custom post types, custom fields, custom taxonomies, comments, secured authentication
* **Full support of progressive web apps (PWA)**
* **iOS and Android support**
* **PhoneGap/Cordova**: use JavaScript, HTML and CSS to build apps
* **PhoneGap Build**: Easy online compilation
* **Themes**: create app themes
* **Customizable**: hook into our API to add the features you need

Get a look at all [available features](https://uncategorized-creations.com/features/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=plugin_readme)

= Getting Started =

Even if you are familiar with development, building your first PWA or mobile app can be intimidating. We know that, we’ve been there before :-)

For that, we have tutorials and documentation that will guide you through the process:
* to create your first PWA
* to have your first app connected to your WordPress site installed on your phone.

[WP-AppKit Documentation](https://goo.gl/3yed8t)

You can also check this video that shows in 5 minutes what you will achieve thanks to the *Getting Started* tutorial.

https://www.youtube.com/watch?v=t6KwLxuoZ2g

= App Themes: a Flexible Way To Build Apps =

WP-AppKit supports JavaScript based PWA and app themes. We provide 2 default sister themes named *Q for iOS* and *Q for Android*. These themes are installed with the plugin. You can  also develop your own themes.

* [Q for Android](https://www.youtube.com/watch?v=fSQVx8-rqCY) (video)
* [Q for iOS](https://www.youtube.com/watch?v=jkjtkH6wDys) (video)

= Documentation and Tutorials =

* [Complete documentation](https://goo.gl/6EW93W).
* [Tutorials](https://goo.gl/vKxBFD).
* [GitHub repository](https://github.com/uncatcrea/wp-appkit)

> **Pro Support and add-ons for WP-AppKit** If you need to get further with WP-AppKit, we offer a [paid support](https://goo.gl/pqfNjm) for advanced topics and convenient [add-ons](https://goo.gl/5oisKB) to integrate specific features (eg. push notifications).

**More information at [getwpappkit.com](https://goo.gl/pEYAE4).**

== Frequently Asked Questions ==

You'll find [an always up to date FAQ](https://goo.gl/cgQWyA) on our website.

= How do I install WP-AppKit? =

Like any other WordPress plugin, install the plugin through the WordPress `Plugins` screen directly (or upload plugin files to the `/wp-content/plugins/wp-appkit` directory), then activate it through the `Plugins` screen in WordPress and click the `WP-AppKit` menu entry to start creating your app!

**Note:** WP-AppKit uses a special folder called `themes-wp-appkit` to store the app themes. This folder is automatically created in `/wp-content` at plugin installation.

**Github version:** if you have the [github version of WP-AppKit](https://github.com/uncatcrea/wp-appkit) already installed on your WordPress, please **unsintall** it and **delete** the `/plugins/wp-appkit` folder before installing the version from WordPress.org's plugin repository.
Your current WP-AppKit apps and themes (in `wp-content/themes-wp-appkit`) won't be affected by this operation.

= How many applications can I create? =

You can create an **unlimited** number of applications and an unlimited number of versions of each application. That includes native apps as well as progressive web apps.

= Can I send push notifications? =

**Yes.** It is not implemented by default. We recommend that you use a service such as PushWoosh or OneSignal to implement push notifications for your app.

= Do you support Custom Fields? =

**Yes.** By default, we don’t send Custom Fields to apps (to avoid performance issues). However, you can easily add the custom fields you need. By the way we have [a nice tutorial](https://uncategorized-creations.com/1712/display-wordpress-custom-fields-app/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=plugin_readme) about that. You can also use [Advanced Custom Fields](https://www.advancedcustomfields.com/).

= Do you support WordPress comments? =

**Yes.** You can display WordPress comments (including threaded comments). However, posting comments requires development.

= Do you use the WP REST API? =

We use a homemade (extendable) REST API. As the WP REST API is now integrated to WordPress core, we’ll probably rely on it in the future.

= Is WP-AppKit compatible with WooCommerce? = 

You can display WooCommerce content (ie. products) in your themes as any other WordPress content. However, you’ll have to (re)develop functionalities such as cart or checkout.

= Do you compile native applications for me? =

**No.** You can use PhoneGap Build, an easy to use Adobe’s cloud compilation service or the classic Cordova CLI.

= Do you deploy progressive web apps for me? =

**Yes.** Progressive web apps are not distributed through app stores. They are freely available on the web. WP-AppKit allows to deploy and update PWA on your site.

= Do you release native applications in app stores for me? =

**No.** This is something you’ll have to do by yourself when your application is ready and compiled.

== Screenshots ==

1. Create and configure apps from the WordPress admin
2. Choose which content should appear in app
3. Manage app menu
4. Create app themes. Two themes are pre-installed to get you started in no time
5. Preview in the browser (using Chrome's emulation mode). Debug with the tools you know (Chrome's dev tools)
6. Compile online (with PhoneGap build)
7. Create a progressive web application (PWA)

== Changelog ==

Also see [changelog on github](https://github.com/uncatcrea/wp-appkit/blob/master/CHANGELOG.md) for full details.

= 1.5.2 (2018-07-02) =

*Features*

* Deactivate CrossWalk by default (fixes PhoneGap Build "FontFamilyFont" error)
* Force android-targetSdkVersion to 26
* Allow addon activation for PWA
* Allow to add custom preferences to config.xml

*Fixes*

* Fix launch routing for deeplinks
* Fix error when inserting custom component created by addons in navigation

*Default themes update*

* q-android version 1.1.2, q-ios vesion 1.0.7: fix link opening in post content

= 1.5.1 (2018-03-06) =

*Minor fixes*

* Fix error message when wrong permissions on PWA export directory
* Fix HTTPS warning message
* Enable translation on PWA installation messages
* Redirect http to https only if site is https
* q-android version 1.1.1: fix app version display
* Remove empty 'help me' messages

= 1.5 (2018-03-04) =

*Features*

* Progressive Web App (PWA) support!

*Default themes update*

* Embed last version (1.1.0) of [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.1.0) default app themes, which is compatible with PWAs.

*Main evolutions for PWA*

* Add Progressive Web App export type
* Add pwa manifest
* Icones PWA
* Update available platforms to add PWA
* Show/Hide PhoneGap/PWA metaboxes when needed
* Check/Sanitize PWA install path
* Allow going directly to url fragment at app launch
* Add pretty url support to PWA
* Update Export/Install UI
* JS Minification
* CSS Minification
* Add default background/theme colors and a color picker
* Set auth key from option instead of WP const
* First Launch Content
* Add-ons compatibility
* Handle Internal links

*Bugfixes and evolutions*

* App last modification date stays to creation date
* Add option to disable warning alerts when modifying components and navigation

= 1.2 (2017-10-22) =

*Features*

* Automatically retrieve posts and pages from server if not in the app
* Allow easy comment screen refresh from theme

*Default themes update*

* Embed last version (1.0.6) of [Q for iOS](https://github.com/uncatcrea/q-ios/releases/tag/v1.0.6) and [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.0.6) default app themes

*Bugfixes*

* Better history management when re-triggering same route
* Can't go back from custom page
* Component's label can't be numeric
* Apply "the_title" filter on post title returned in webservice
* Warning: Illegal string offset 'current_theme'
* Warning on post's thumbnail array

= 1.1 (2017-07-26) =

*License Management*

* Pro Support license keys can now be registered directly from WP-AppKit settings panel

*Default themes update*

* Embed last version (1.0.5) of [Q for iOS](https://github.com/uncatcrea/q-ios/releases/tag/v1.0.5) and [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.0.5) default app themes

*Bugfixes*

* Add x86/ARM compilation choice in PhoneGap Build export settings
* Theme error when empty post content
* Custom WP-AppKit user role stays even after deactivation
* getCurrentScreenObject() error on some custom screens
* Wrong routing initialization when no network at first app launch

= 1.0.2 (2017-05-05) = 

* Bugfix: User authentication fails randomly

= 1.0.1 (2017-04-11) = 

* Update readme file

= 1.0 (2017-03-24) = 

*Release on WordPress.org!*

* Readme.txt for WordPress.org
* Add Domain Path header to plugin's file headers
* New WP-AppKit menu icon
* Comply to WordPress.org repository requirements

*Default themes update*

* Embed last version (1.0.4) of [Q for iOS](https://github.com/uncatcrea/q-ios/releases/tag/v1.0.4) and [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.0.4) default app themes

*Core evolutions*

* Add new hooks to allow component customizations
* Allow re-rendering menu from theme
* Allow to customize current_screen data on app side
* Create global functions to retrieve current app slug and id
* Include addons php files where we include themes php files
* Include theme's php files before export

*Bugfixes*

* Wrong Items Backbone Collections initializations
* Malformed config.xml in PhoneGap Build

*Backward compatibility*

* No change in this version that affect backward compatibility with previous WP-AppKit version or already deployed apps.

= 0.6.2 (2017-02-06) =

*Bugfixes*

* Update Crosswalk plugin version from 1.5.0 to 2.3.0

= 0.6.1 (2016-11-29) =

*Bugfixes*

* WP-AppKit Upload Theme panel hidden in last WP version

= 0.6 (2016-06-20) =

*Features / Evolutions*

* Demo themes included in core & New theme library!
* Deep Links
* Authentication module: be compatible with WP 4.5 authentication using email
* Shortcodes to show/hide app specific content
* Allow themes to add custom theme settings to config.js
* Add WordPress url in config.js
* Include theme's PHP folder in the config.js/config.xml process
* Allow using standard pagination for post lists

*Cordova / Phonegap*

* CrossWalk support
* Gradle support
* App permissions
* Status bar support for Android
* Missing splashscreen fading delay
* Hide splashscreen spinner on Android

*Better Theme and Plugin API*

* Add upgrade routines
* Better history info in custom screen transitions
* Remove ThemeApp.setAutoScreenTransitions()
* Fix argument names and order in 'screen-transition' action
* Remove 'screen:before-transition' event
* Homogenize web service event types
* Better "preloaded-templates" filter
* JS action hooks clarification
* Unused or misused events
* Enhance web service context info retrieval
* Make ThemeApp.refreshComponentItems() more flexible
* Create ThemeApp.refreshComponent()

*Bugfixes*

* Script localization and escaping, remove esc_js() calls
* Metaboxes help texts
* Display comments directly: add parent post/page to history
* Reset component form
* Default liveQuery type should be 'replace-keep-global-items' and not 'update'
* LiveQuery error when type=update
* Back action broken for pages

*Backward compatibility note regarding Theme API*

* Argument names and order changed in 'screen-transition' action
* ThemeApp.setAutoScreenTransitions() removed, replaced by manual hooks
* Changes on 3 asynchronous actions: "pre-start-router", "get-more-component-items", "debug-panel-render"
* Simplification of template format passed to "preloaded-templates" filter: "single" instead of "text!theme/single.html"
* Events prefix homogenization: use of "ws-data" replaced by "web-service"
* 'screen:before-transition' event removed because not usable as is, potentially leading to errors
* Unused events removed: 'menu:refresh',’header:render','waiting:start','waiting:stop'
* LiveQuery webservice default type is now 'replace-keep-global-items' instead of 'update'

= 0.5.1 (2016-05-23) =

* Add GPL mention in plugin main file

= 0.5 (2016-03-07) =

*Better Theme and Plugin API*

* Allow adding custom meta data to post list components
* Allow platform detection from app
* Check If We're In Default Screen
* Preload templates at app launch
* Allow to retrieve components and component links from theme
* Add timezone offset to config.js
* Add the minimum required WP-AppKit version to theme's readme header
* Add a Theme API function that retrieves the current screen object in a standardized format
* Better comments screen display error management
* Add post slug and permalink to default web service data
* Post data into comment:posted info event
* Create a ThemeApp.navigateToPreviousScreen() function
* Add error callback to ThemeApp.getMoreComponentItems()
* Trigger a "component:get-more" info event in App.getMoreOfComponent()
* Create a ThemeApp.getGlobalItem() method that allows to retrieve a specific item from local storage
* "Post list" component : add a hook to allow filtering the available post types
* Rename default transitions in App.getTransitionDirection()
* Replace "data" property by "core_data" in format_theme_event_data()
* Page screens : rename "item" to "post" in current_screen.data

*UI*

* Platform column on application list
* Add spinner to Save new component button
* Add spinner to Add component to navigation button
* Translations and cosmetics
* Platform specific fields, be able to show/hide some metaboxes or fields depending on the selected platform
* Hide menu icons management

*Icons & Splashscreens*

* Embed WP-AppKit icons and splashscreens by default
* Better support for splashscreens
* Splashscreen fading delay to 300ms

*Bugfixes*

* Error navigating to a comments screen from a page screen
* Fix Default to single for page appears to be broken
* Default embedded Android splashscreen raises error in Phonegap Build
* Fix 404 error for "Upload Theme" link
* Fix Handle the case where the app has no component more gracefully
* Fix Problem with read-more on singular post. Thanks Willy! :)
* Translation : include texts
* TemplateTags.isTreePage() called with wrong arguments

*Evolutions*

* Allow Web service authentication (Add an action hook that fires just before web services dispatch)
* Finish testing iOS9 compatibility by making https tests
* Add WP Network specific htaccess rules automatically at WP-AppKit installation
* Config.xml plugin declarations
* Activate whitelist plugin by default for iOS builds with Phonegap CLI
* Allow all HTML tags in post content by default
* Allow to git checkout directly the root of wp-appkit repository

*Backward compatibility note*

* ThemeApp.getGlobalItems() renamed ThemeApp.getItems()
* Screen transitions renamed: left > next-screen, right > previous-screen, replace > default
* Error and info events: event.data renamed event.core_data
* Removed ThemeApp.setAutoBackButton() and ThemeApp.updateBackButtonEvents()
* Config.xml: ```<gap:plugin>``` replaced by ```<plugin>``` + 'version' attribute replaced by 'spec'
* TemplateTags.isTreePage( page_id, screen ) replaced by TemplateTags.isTreePage( screen )
* current_screen.data.item replaced by current_screen.data.post for page screens

= 0.4.1 (2015-09-30) =

*Bugfixes*

* Fix setAutoScreenTransitions() following hooks implementation evolution
* Support for the Whitelist Cordova plugin
* Plugin parameters

= 0.4 (2015-08-03) =

*Features*

* Create a new template tag to retrieve a component's items
* Add new filter "redirect" to allow to force redirection to a different screen than the queried one
* Allow users to comment securely from apps
* User login : allow users to authenticate securely from apps
* Extract favorites feature from core
* WP CLI command to export WP-AppKit apps
* "Live query" web service
* Add an easier way (template tag?) to retrieve the template used for the current page
* Remove unused code following the Zip export history simplification in 0.3
* "Post list" component : don't force to choose a taxonomy term

*Bugfixes*

* Collections items not removed from local storage when the collection is empty in webservice
* Themes' readme files not supported if filename upper case
* Plugins field doesn't allow "source" parameter
* Setup appearance and navigation checkbox wrongly checked
* wpak_unavailable_media.png appears unexpectedly in a single post
* PhoneGap plugins duplicates in config.xml

= 0.3.1 (2015-06-09) =

*Bugfixes*

* Export zip creation with PHP >= 5.2.8

= 0.3 (2015-05-04) =

*Features*

* Help buttons in metaboxes
* Remove "Simulator" entry in WordPress menu
* Thoughts about the new publish metabox
* PhoneGap metabox fieldsets
* Better menu items filtering : make "menu-items" js filter more general
* Allow to pass any custom data along with web services from app themes and to filter server answer accordingly : new “web-service-params” js filter
* Pass any custom param to app templates : new “template-args” js filter
* Allow to customize web services jQuery ajax calls : new “ajax-args” js filter
* WordPress 4.2 Compatibility
* Feedback message when saving
* New Appearance Metabox
* New My Project Metabox

*Bugfixes*

* Edit panel title capitalization
* Edit panel add new button label
* Components metabox title
* PhoneGap Build metabox title
* PhoneGap Build metabox - mandatory fields
* Components not immediatly updated in nav when component changes
* "Increment JS/CSS resources version
* "Help me" displays above the web service link in the "Synchronization"
* Export returns always the same .zip
* Fatal Error on Plugin Activation
* Windows zip creation
* Access rights problem when trying uploading a theme

*Security*

* More security for web services calls by checking that the corresponding app is valid

= 0.2 (2015-03-08) =

*Features*

* Persistent storage
* New theme directory
* Theme's metadata
* Sample themes migration
* Allow to add custom routes
* theme templates files with add-ons
* Geolocation module
* Static views
* Filter app history management
* Create components with add-ons
* Permalinks activation warning

*Bugfixes*

* Secure PhoneGap meta box
* Remove default mobile image size
* Woff2 files not accepted in themes
