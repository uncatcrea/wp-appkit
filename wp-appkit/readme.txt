=== WP-AppKit - build mobile apps with WordPress ===
Contributors: Uncategorized Creations
Tags: Mobile, App, PhoneGap
Requires at least: 4.0
Tested up to: 4.7.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A great way to build mobile apps for your WordPress site using your favorite technologies: JavaScript, HTML and CSS.

== Description ==

Applications are a great way to offer an outstanding mobile experience for your users. Use push notifications, let users read offline, use saved accounts to let users share content are among many wonderful things you can do **only** with mobile applications.

With WP-AppKit, we're committed to alleviate as much as we can the work necessary to build a mobile app. for that:
* We offer a native support of WordPress (including custom post types, custom fields, custom taxonomies, comments, secured authentication and [many more features](https://uncategorized-creations.com/features/))
* We use the PhoneGap/Cordova technology which allows to build apps using JavaScript, HTML and CSS
* We support the PhoneGap Build online service for easy compilation
* We provide a simple API to create app themes
* Developers can hook into our API to add new great features


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-appkit` directory, or install the plugin through the WordPress 'Plugins' screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Click the "WP-AppKit" menu entry and start creating your App!

== Frequently Asked Questions ==

You'll find [an always up to date FAQ](https://uncategorized-creations.com/frequently-asked-questions/) on our website.

- For how much time does WP-AppKit exist? =

WP-AppKit has been [available on GitHub](https://github.com/uncatcrea/wp-appkit) since 2013. It has been released on WordPress.org at the beginning of 2017.

= Is WP-AppKit an app builder? =

**No** however.... As soon as WP-AppKit is installed, you'll be able to create an app's project: pick contents, create a menu, choose a theme (ie. an app theme)... Then you'll be able to preview your app in the browser (using the Chrome's mobile emulation mode). In that sense you may consider WP-AppKit as an app builder but it is not our goal to let users build the whole app in the WordPress admin. Instead we provide an API to let you extend and customize your app. So you will need technical skills (notably JavaScript skills) to get your app done.

= Do I need to be a developer to use WP-AppKit? =

**Yes**. Our goal is to provide as much as possible an easy way to get you into the business of building your Cordova based apps. For example, we provide a micro-framework to create app themes, the plugin is bundled with default themes, we create PhoneGap Build ready projects, we support by default WordPress authentication... However, as soon as you want to customize and add new features, you'll have to put your developper's hat. In that case, you'll need a good knowledge of PHP, WordPress, JavaScript, HTML and CSS.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

Also see [changelog on github](https://github.com/uncatcrea/wp-appkit/blob/master/CHANGELOG.md) for full details.

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


== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above (between FAQ and Changelog).
