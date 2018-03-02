## 1.5 (2018-03-04)

### Features
- **Progressive Web App (PWA) support!**

### Default themes update
- **Embed last version (1.1.0) of [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.1.0) default app themes, which is compatible with PWAs.**

### Main commits and issues 
_See Pull Request [#361](https://github.com/uncatcrea/wp-appkit/pull/361) to see all version 1.5 related commits_

- **Add Progressive Web App export type** ([135f927](https://github.com/uncatcrea/wp-appkit/commit/135f927f4e2a7404b66b0b0e8ed3a30bffc412ad), [@mleroi](https://github.com/mleroi))
- **Add pwa manifest** ([3372106](https://github.com/uncatcrea/wp-appkit/commit/337210620c5a99e734ecda7ad6313e020cc5974b), [@mleroi](https://github.com/mleroi))
- **Icones PWA** ([f177fee](https://github.com/uncatcrea/wp-appkit/commit/f177fee65aa96173954588d60fbffb48a48cfaa5), [@mleroi](https://github.com/mleroi))
- **Update available platforms to add PWA** ([#298](https://github.com/uncatcrea/wp-appkit/issues/298), [@lpointet](https://github.com/lpointet))
- **Show/Hide PhoneGap/PWA metaboxes when needed** ([#299](https://github.com/uncatcrea/wp-appkit/issues/299), [@lpointet](https://github.com/lpointet))
- **Check/Sanitize PWA install path** ([#300](https://github.com/uncatcrea/wp-appkit/issues/300), [@lpointet](https://github.com/lpointet))
- **Allow going directly to url fragment at app launch** ([47ac168](https://github.com/uncatcrea/wp-appkit/pull/361/commits/47ac1683c8ca6932f30966310c483de1659f1a54), [@mleroi](https://github.com/mleroi))
- **Add pretty url support to PWA** ([713d72f](https://github.com/uncatcrea/wp-appkit/pull/361/commits/713d72fb26d219ced9cfddcadf07b3a1946b1184), [@mleroi](https://github.com/mleroi))
- **Update Export/Install UI** ([#317](https://github.com/uncatcrea/wp-appkit/issues/317), [@lpointet](https://github.com/lpointet))
- **JS Minification** ([6965425](https://github.com/uncatcrea/wp-appkit/pull/361/commits/6965425edac231fcabb33bcb03b6cd1687859950), [@mleroi](https://github.com/mleroi))
- **CSS Minification** ([f49ee5e](https://github.com/uncatcrea/wp-appkit/pull/361/commits/f49ee5e21a3f1be56235aa6fdf76ba56f703423b), [@mleroi](https://github.com/mleroi))
- **Add a warning about https in the PWA box** ([#313](https://github.com/uncatcrea/wp-appkit/issues/313), [@lpointet](https://github.com/lpointet))
- **Add default background/theme colors and a color picker** ([#314](https://github.com/uncatcrea/wp-appkit/issues/314), [@lpointet](https://github.com/lpointet))
- **Set auth key from option instead of WP const** ([3ab7720](https://github.com/uncatcrea/wp-appkit/pull/361/commits/3ab772053c9fbd84450338ff8166a03a5fc01b91), [@mleroi](https://github.com/mleroi))
- **First Launch Content** ([#344](https://github.com/uncatcrea/wp-appkit/issues/344), [@mleroi](https://github.com/mleroi))
- **Add-ons compatibility** ([#354](https://github.com/uncatcrea/wp-appkit/issues/354), [@lpointet](https://github.com/lpointet))
- **Handle Internal links** ([#358](https://github.com/uncatcrea/wp-appkit/issues/358), [@mleroi](https://github.com/mleroi))

## 1.2 (2017-10-22)

### Features
- **Automatically retrieve posts and pages from server if not in the app**([#327](https://github.com/uncatcrea/wp-appkit/issues/330), [@mleroi](https://github.com/mleroi))
- **Allow easy comment screen refresh from theme** ([#327](https://github.com/uncatcrea/wp-appkit/issues/327), [@mleroi](https://github.com/mleroi))

### Default themes update
- **Embed last version (1.0.6) of [Q for iOS](https://github.com/uncatcrea/q-ios/releases/tag/v1.0.6) and [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.0.6) default app themes**

### Bugfixes
- **Better history management when re-triggering same route** ([#262](https://github.com/uncatcrea/wp-appkit/issues/262), [@mleroi](https://github.com/mleroi))
- **Can't go back from custom page** ([#332](https://github.com/uncatcrea/wp-appkit/issues/332), [@mleroi](https://github.com/mleroi))
- **Component's label can't be numeric** ([#265](https://github.com/uncatcrea/wp-appkit/issues/265), [@mleroi](https://github.com/mleroi))
- **Apply "the_title" filter on post title returned in webservice** ([#266](https://github.com/uncatcrea/wp-appkit/issues/266), [@lpointet](https://github.com/lpointet))
- **Warning: Illegal string offset 'current_theme'** ([#331](https://github.com/uncatcrea/wp-appkit/issues/331), [@mleroi](https://github.com/mleroi))
- **Warning on post's thumbnail array** ([#337](https://github.com/uncatcrea/wp-appkit/issues/337), [@mleroi](https://github.com/mleroi))

## 1.1 (2017-07-26)

### License Management 
- **Pro Support license keys can now be registered directly from WP-AppKit settings panel**

### Default themes update
- **Embed last version (1.0.5) of [Q for iOS](https://github.com/uncatcrea/q-ios/releases/tag/v1.0.5) and [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.0.5) default app themes**

### Bugfixes
- **Add x86/ARM compilation choice in PhoneGap Build export settings** ([#275](https://github.com/uncatcrea/wp-appkit/issues/275), [@mleroi](https://github.com/mleroi))
- **Theme error when empty post content** ([#321](https://github.com/uncatcrea/wp-appkit/issues/321), [@mleroi](https://github.com/mleroi))
- **Custom WP-AppKit user role stays even after deactivation** ([#320](https://github.com/uncatcrea/wp-appkit/issues/320), [@mleroi](https://github.com/mleroi))
- **getCurrentScreenObject() error on some custom screens** ([#273](https://github.com/uncatcrea/wp-appkit/issues/273), [@mleroi](https://github.com/mleroi))
- **Wrong routing initialization when no network at first app launch** ([#323](https://github.com/uncatcrea/wp-appkit/issues/323), [@mleroi](https://github.com/mleroi))

## 1.0.2 (2017-05-05)

- Bugfix: User authentication fails randomly ([#303](https://github.com/uncatcrea/wp-appkit/issues/303), [@mleroi](https://github.com/mleroi))

## 1.0.1 (2017-04-11)

- Update WordPress.org's readme file

## 1.0 (2017-03-24)

### Release on WordPress.org!
- **Readme.txt for WordPress.org** ([@blupu](https://github.com/blupu))
- **Add Domain Path header to plugin's file headers** ([#a733050](https://github.com/uncatcrea/wp-appkit/commit/a733050e607a1abbdd440ac5d27fa044cb151123), [@lpointet](https://github.com/lpointet))
- **New WP-AppKit menu icon** ([#06c589b](https://github.com/uncatcrea/wp-appkit/commit/06c589b16bfffc5ac9b023fdda4c2545cefffc9c), [@mleroi](https://github.com/mleroi))
- **Comply to WordPress.org repository requirements** ([Pull Request #297](https://github.com/uncatcrea/wp-appkit/pull/297))

### Default themes update
- **Embed last version (1.0.4) of [Q for iOS](https://github.com/uncatcrea/q-ios/releases/tag/v1.0.4) and [Q for Android](https://github.com/uncatcrea/q-android/releases/tag/v1.0.4) default app themes**

### Core evolutions
- **Add new hooks to allow component customizations** ([#288](https://github.com/uncatcrea/wp-appkit/issues/288), [@mleroi](https://github.com/mleroi))
- **Allow re-rendering menu from theme** ([#290](https://github.com/uncatcrea/wp-appkit/issues/290), [@mleroi](https://github.com/mleroi))
- **Allow to customize current_screen data on app side** ([#289](https://github.com/uncatcrea/wp-appkit/issues/289), [@mleroi](https://github.com/mleroi))
- **Create global functions to retrieve current app slug and id** ([#292](https://github.com/uncatcrea/wp-appkit/issues/292), [@mleroi](https://github.com/mleroi))
- **Include addons php files where we include themes php files** ([#291](https://github.com/uncatcrea/wp-appkit/issues/291), [@mleroi](https://github.com/mleroi))
- **Include theme's php files before export** ([#285](https://github.com/uncatcrea/wp-appkit/issues/285), [@mleroi](https://github.com/mleroi))

### Bugfixes
- **Wrong Items Backbone Collections initializations** ([#282](https://github.com/uncatcrea/wp-appkit/issues/282), [@mleroi](https://github.com/mleroi))
- **Malformed config.xml in PhoneGap Build** ([#248](https://github.com/uncatcrea/wp-appkit/issues/248), [@lpointet](https://github.com/lpointet))

### Backward compatibility
- No change in this version that affect backward compatibility with previous WP-AppKit version or already deployed apps.

## 0.6.2 (2017-02-06)

### Bugfixes
- **Update Crosswalk plugin version from 1.5.0 to 2.3.0** ([#281](https://github.com/uncatcrea/wp-appkit/issues/281), [@blupu](https://github.com/blupu))

## 0.6.1 (2016-11-29)

### Bugfixes
- **WP-AppKit Upload Theme panel hidden in last WP version** ([#271](https://github.com/uncatcrea/wp-appkit/issues/271), [@mleroi](https://github.com/mleroi))

## 0.6 (2016-06-20)

### Demo themes included in core & New theme library!
- **Include demo themes in default WP-AppKit plugin package** ([#152](https://github.com/uncatcrea/wp-appkit/issues/152), [@lpointet](https://github.com/lpointet))
- **New UI for WP-AppKit themes browsing, based on WP Themes Library** ([#152](https://github.com/uncatcrea/wp-appkit/issues/152), [@lpointet](https://github.com/lpointet))
- **Be able to add a screenshot to a theme** ([#192](https://github.com/uncatcrea/wp-appkit/issues/192), [@lpointet](https://github.com/lpointet))
- **Default theme for iOS** ([#195](https://github.com/uncatcrea/wp-appkit/issues/195), [@blupu](https://github.com/blupu))
- **Default theme for Android** ([#196](https://github.com/uncatcrea/wp-appkit/issues/196), [@blupu](https://github.com/blupu))

### Features / Evolutions
- **Deep Links** ([#215](https://github.com/uncatcrea/wp-appkit/issues/215), [@lpointet](https://github.com/lpointet))
- **Authentication module: be compatible with WP 4.5 authentication using email** ([#210](https://github.com/uncatcrea/wp-appkit/issues/210), [@mleroi](https://github.com/mleroi))
- **Shortcodes to show/hide app specific content** ([#211](https://github.com/uncatcrea/wp-appkit/issues/211), [@mleroi](https://github.com/mleroi))
- **Allow themes to add custom theme settings to config.js** ([#208](https://github.com/uncatcrea/wp-appkit/issues/208), [@mleroi](https://github.com/mleroi))
- **Add WordPress url in config.js** ([#207](https://github.com/uncatcrea/wp-appkit/issues/207), [@mleroi](https://github.com/mleroi))
- **Include theme's PHP folder in the config.js/config.xml process** ([#209](https://github.com/uncatcrea/wp-appkit/issues/209), [@mleroi](https://github.com/mleroi))
- **Allow using standard pagination for post lists** ([#231](https://github.com/uncatcrea/wp-appkit/issues/231), [@mleroi](https://github.com/mleroi))

### Cordova / Phonegap
- **CrossWalk support** ([#188](https://github.com/uncatcrea/wp-appkit/issues/188), [@lpointet](https://github.com/lpointet))
- **Gradle support** ([#187](https://github.com/uncatcrea/wp-appkit/issues/187), [@mleroi](https://github.com/mleroi))
- **App permissions** ([#181](https://github.com/uncatcrea/wp-appkit/issues/181), [@lpointet](https://github.com/lpointet))
- **Status bar support for Android** ([#190](https://github.com/uncatcrea/wp-appkit/issues/190), [@mleroi](https://github.com/mleroi))
- **Missing splashscreen fading delay** ([#206](https://github.com/uncatcrea/wp-appkit/issues/206), [@mleroi](https://github.com/mleroi))
- **Hide splashscreen spinner on Android** ([#191](https://github.com/uncatcrea/wp-appkit/issues/191), [@lpointet](https://github.com/lpointet))

### Better Theme and Plugin API
- **Add upgrade routines** ([#193](https://github.com/uncatcrea/wp-appkit/issues/193), [@lpointet](https://github.com/lpointet))
- **Better history info in custom screen transitions** ([#219](https://github.com/uncatcrea/wp-appkit/issues/219), [@mleroi](https://github.com/mleroi))
- **Remove ThemeApp.setAutoScreenTransitions()** ([#198](https://github.com/uncatcrea/wp-appkit/issues/198), [@mleroi](https://github.com/mleroi))
- **Fix argument names and order in 'screen-transition' action** ([#197](https://github.com/uncatcrea/wp-appkit/issues/197), [@mleroi](https://github.com/mleroi))
- **Remove 'screen:before-transition' event** ([#202](https://github.com/uncatcrea/wp-appkit/issues/202), [@mleroi](https://github.com/mleroi))
- **Homogenize web service event types** ([#201](https://github.com/uncatcrea/wp-appkit/issues/201), [@mleroi](https://github.com/mleroi))
- **Better "preloaded-templates" filter** ([#200](https://github.com/uncatcrea/wp-appkit/issues/200), [@mleroi](https://github.com/mleroi))
- **JS action hooks clarification** ([#199](https://github.com/uncatcrea/wp-appkit/issues/199), [@mleroi](https://github.com/mleroi))
- **Unused or misused events** ([#203](https://github.com/uncatcrea/wp-appkit/issues/203), [@mleroi](https://github.com/mleroi))
- **Enhance web service context info retrieval** ([#217](https://github.com/uncatcrea/wp-appkit/issues/217), [@mleroi](https://github.com/mleroi))
- **Make ThemeApp.refreshComponentItems() more flexible** ([#229](https://github.com/uncatcrea/wp-appkit/issues/229), [@mleroi](https://github.com/mleroi))
- **Create ThemeApp.refreshComponent()** ([#230](https://github.com/uncatcrea/wp-appkit/issues/230), [@mleroi](https://github.com/mleroi))

### Bugfixes
- **Script localization and escaping, remove esc_js() calls** ([#180](https://github.com/uncatcrea/wp-appkit/issues/180), [@lpointet](https://github.com/lpointet))
- **Metaboxes help texts** ([#57](https://github.com/uncatcrea/wp-appkit/issues/57), [@lpointet](https://github.com/lpointet))
- **Display comments directly: add parent post/page to history** ([#216](https://github.com/uncatcrea/wp-appkit/issues/216), [@mleroi](https://github.com/mleroi))
- **Reset component form** ([#96](https://github.com/uncatcrea/wp-appkit/issues/96), [@lpointet](https://github.com/lpointet))
- **Default liveQuery type should be 'replace-keep-global-items' and not 'update'** ([#227](https://github.com/uncatcrea/wp-appkit/issues/227), [@mleroi](https://github.com/mleroi))
- **LiveQuery error when type=update** ([#228](https://github.com/uncatcrea/wp-appkit/issues/228), [@mleroi](https://github.com/mleroi))
- **Back action broken for pages** ([#221](https://github.com/uncatcrea/wp-appkit/issues/221), [@mleroi](https://github.com/mleroi))

### Backward compatibility note regarding Theme API
- [#197](https://github.com/uncatcrea/wp-appkit/issues/197) **Argument names and order changed in 'screen-transition' action**
- [#198](https://github.com/uncatcrea/wp-appkit/issues/198) **ThemeApp.setAutoScreenTransitions() removed, replaced by manual hooks**
- [#199](https://github.com/uncatcrea/wp-appkit/issues/199) **Changes on 3 asynchronous actions: "pre-start-router", "get-more-component-items", "debug-panel-render"**
- [#200](https://github.com/uncatcrea/wp-appkit/issues/200) **Simplification of template format passed to "preloaded-templates" filter: "single" instead of "text!theme/single.html"**
- [#201](https://github.com/uncatcrea/wp-appkit/issues/201) **Events prefix homogenization: use of "ws-data" replaced by "web-service"**
- [#202](https://github.com/uncatcrea/wp-appkit/issues/202) **'screen:before-transition' event removed because not usable as is, potentially leading to errors**
- [#203](https://github.com/uncatcrea/wp-appkit/issues/203) **Unused events removed: 'menu:refresh',’header:render','waiting:start','waiting:stop'**
- [#227](https://github.com/uncatcrea/wp-appkit/issues/227) **LiveQuery webservice default type is now 'replace-keep-global-items' instead of 'update'**

## 0.5.1 (2016-05-23)

- Add GPL mention in plugin main file

## 0.5 (2016-03-07)

### Better Theme and Plugin API
- **Allow adding custom meta data to post list components** (allows passing WP4.4 terms meta) ([#150](https://github.com/uncatcrea/wp-appkit/issues/150), [@mleroi](https://github.com/mleroi))
- **Allow platform detection from app** ([#146](https://github.com/uncatcrea/wp-appkit/issues/146), [@mleroi](https://github.com/mleroi))
- **Check If We're In Default Screen** ([#16](https://github.com/uncatcrea/wp-appkit/issues/16), [@lpointet](https://github.com/lpointet))
- **Preload templates at app launch** ([#149](https://github.com/uncatcrea/wp-appkit/issues/149), [@mleroi](https://github.com/mleroi))
- **Allow to retrieve components and component links from theme** ([#157](https://github.com/uncatcrea/wp-appkit/issues/157), [@mleroi](https://github.com/mleroi))
- **Add timezone offset to config.js** ([#158](https://github.com/uncatcrea/wp-appkit/issues/158), [@mleroi](https://github.com/mleroi))
- **Add the minimum required WP-AppKit version to theme's readme header** ([#159](https://github.com/uncatcrea/wp-appkit/issues/159), [@mleroi](https://github.com/mleroi))
- **Add a Theme API function that retrieves the current screen object in a standardized format** ([#153](https://github.com/uncatcrea/wp-appkit/issues/153), [@mleroi](https://github.com/mleroi))
- **Better comments screen display error management** ([#156](https://github.com/uncatcrea/wp-appkit/issues/156), [@mleroi](https://github.com/mleroi))
- **Add post slug and permalink to default web service data** ([#162](https://github.com/uncatcrea/wp-appkit/issues/162), [@mleroi](https://github.com/mleroi))
- **Post data into comment:posted info event** ([#163](https://github.com/uncatcrea/wp-appkit/issues/163), [@mleroi](https://github.com/mleroi))
- **Create a ThemeApp.navigateToPreviousScreen() function** ([#168](https://github.com/uncatcrea/wp-appkit/issues/168), [@lpointet](https://github.com/lpointet))
- **Add error callback to ThemeApp.getMoreComponentItems()** ([#154](https://github.com/uncatcrea/wp-appkit/issues/154), [@mleroi](https://github.com/mleroi))
- **Trigger a "component:get-more" info event in App.getMoreOfComponent()** ([#169](https://github.com/uncatcrea/wp-appkit/issues/169), [@lpointet](https://github.com/lpointet))
- **Create a ThemeApp.getGlobalItem() method that allows to retrieve a specific item from local storage** ([#139](https://github.com/uncatcrea/wp-appkit/issues/139), [@mleroi](https://github.com/mleroi))
- **"Post list" component : add a hook to allow filtering the available post types** ([#141](https://github.com/uncatcrea/wp-appkit/issues/141), [@mleroi](https://github.com/mleroi))
- **Rename default transitions in App.getTransitionDirection()** ([#155](https://github.com/uncatcrea/wp-appkit/issues/155), [@mleroi](https://github.com/mleroi))
- **Replace "data" property by "core_data" in format_theme_event_data()** ([#164](https://github.com/uncatcrea/wp-appkit/issues/164), [@mleroi](https://github.com/mleroi))
- **Page screens : rename "item" to "post" in current_screen.data** ([#177](https://github.com/uncatcrea/wp-appkit/issues/177), [@mleroi](https://github.com/mleroi))

### UI
- **Platform column on application list** ([#142](https://github.com/uncatcrea/wp-appkit/issues/142), [@mleroi](https://github.com/mleroi))
- **Add spinner to Save new component button** ([#97](https://github.com/uncatcrea/wp-appkit/issues/97), [@lpointet](https://github.com/lpointet))
- **Add spinner to Add component to navigation button** ([#98](https://github.com/uncatcrea/wp-appkit/issues/98), [@lpointet](https://github.com/lpointet))
- **Translations and cosmetics** ([#126](https://github.com/uncatcrea/wp-appkit/issues/126) to [#136](https://github.com/uncatcrea/wp-appkit/issues/136), [@lpointet](https://github.com/lpointet))
- **Platform specific fields, be able to show/hide some metaboxes or fields depending on the selected platform** ([#58](https://github.com/uncatcrea/wp-appkit/issues/58), [@lpointet](https://github.com/lpointet))
- **Hide menu icons management** ([#165](https://github.com/uncatcrea/wp-appkit/issues/165), [@mleroi](https://github.com/mleroi))

### Icons & Splashscreens
- **Embed WP-AppKit icons and splashscreens by default** ([#147](https://github.com/uncatcrea/wp-appkit/issues/147), [@mleroi](https://github.com/mleroi))
- **Better support for splashscreens** ([#107](https://github.com/uncatcrea/wp-appkit/issues/107), [@mleroi](https://github.com/mleroi))
- **Splashscreen fading delay to 300ms** ([#160](https://github.com/uncatcrea/wp-appkit/issues/160), [@mleroi](https://github.com/mleroi))

### Bugfixes:
- **Error navigating to a comments screen from a page screen** ([#117](https://github.com/uncatcrea/wp-appkit/issues/117), [@mleroi](https://github.com/mleroi))
- **Fix Default to single for page appears to be broken** ([#18](https://github.com/uncatcrea/wp-appkit/issues/18), [@mleroi](https://github.com/mleroi))
- **Default embedded Android splashscreen raises error in Phonegap Build** ([#173](https://github.com/uncatcrea/wp-appkit/issues/173), [@lpointet](https://github.com/lpointet)) 
- **Fix 404 error for "Upload Theme" link** ([PR#151](https://github.com/uncatcrea/wp-appkit/pull/151), [@petitphp](https://github.com/petitphp)) 
- **Fix Handle the case where the app has no component more gracefully** ([#116](https://github.com/uncatcrea/wp-appkit/issues/116), [@mleroi](https://github.com/mleroi))
- **Fix Problem with read-more on singular post. Thanks Willy! :)** ([#106](https://github.com/uncatcrea/wp-appkit/issues/106), [@mleroi](https://github.com/mleroi))
- **Translation : include texts** ([#175](https://github.com/uncatcrea/wp-appkit/issues/175), [@lpointet](https://github.com/lpointet)) 
- **TemplateTags.isTreePage() called with wrong arguments** ([#176](https://github.com/uncatcrea/wp-appkit/issues/176), [@mleroi](https://github.com/mleroi))

### Evolutions:
- **Allow Web service authentication (Add an action hook that fires just before web services dispatch)** ([#145](https://github.com/uncatcrea/wp-appkit/issues/145), [@mleroi](https://github.com/mleroi))
- **Finish testing iOS9 compatibility by making https tests** ([#110](https://github.com/uncatcrea/wp-appkit/issues/110), [@mleroi](https://github.com/mleroi))
- **Add WP Network specific htaccess rules automatically at WP-AppKit installation** ([#167](https://github.com/uncatcrea/wp-appkit/issues/167), [@mleroi](https://github.com/mleroi))
- **Config.xml plugin declarations** ([#172](https://github.com/uncatcrea/wp-appkit/issues/172), [@lpointet](https://github.com/lpointet)) 
- **Activate whitelist plugin by default for iOS builds with Phonegap CLI** ([#113](https://github.com/uncatcrea/wp-appkit/issues/113), [@mleroi](https://github.com/mleroi))
- **Allow all HTML tags in post content by default** ([#140](https://github.com/uncatcrea/wp-appkit/issues/140), [@mleroi](https://github.com/mleroi))
- **Allow to git checkout directly the root of wp-appkit repository** ([#179](https://github.com/uncatcrea/wp-appkit/issues/179), [@mleroi](https://github.com/mleroi))

### Backward compatibility note
- [#139](https://github.com/uncatcrea/wp-appkit/issues/139) **ThemeApp.getGlobalItems() renamed ThemeApp.getItems()**
- [#155](https://github.com/uncatcrea/wp-appkit/issues/155) **Screen transitions renamed: left > next-screen, right > previous-screen, replace > default**
- [#164](https://github.com/uncatcrea/wp-appkit/issues/164) **Error and info events: event.data renamed event.core_data**
- [#168](https://github.com/uncatcrea/wp-appkit/issues/168) **Removed ThemeApp.setAutoBackButton() and ThemeApp.updateBackButtonEvents()**
- [#172](https://github.com/uncatcrea/wp-appkit/issues/172) **Config.xml: ```<gap:plugin>``` replaced by ```<plugin>``` + 'version' attribute replaced by 'spec'**
- [#176](https://github.com/uncatcrea/wp-appkit/issues/176) **TemplateTags.isTreePage( page_id, screen ) replaced by TemplateTags.isTreePage( screen )**
- [#177](https://github.com/uncatcrea/wp-appkit/issues/177) **current_screen.data.item replaced by current_screen.data.post for page screens**

## 0.4.1 (2015-09-30)

### Bugfixes:
- **Fix setAutoScreenTransitions() following hooks implementation evolution** ([#111](https://github.com/uncatcrea/wp-appkit/issues/111), [@mleroi](https://github.com/mleroi))
- **Support for the Whitelist Cordova plugin** ([#109](https://github.com/uncatcrea/wp-appkit/issues/109), [@mleroi](https://github.com/mleroi))
- **Plugin parameters** ([#93](https://github.com/uncatcrea/wp-appkit/issues/93), [@lpointet](https://github.com/lpointet))

## 0.4 (2015-08-03)

### Features:

- **Create a new template tag to retrieve a component's items** ([#104](https://github.com/uncatcrea/wp-appkit/issues/104), [@mleroi](https://github.com/mleroi))
- **Add new filter "redirect" to allow to force redirection to a different screen than the queried one** ([#103](https://github.com/uncatcrea/wp-appkit/issues/103), [@mleroi](https://github.com/mleroi))
- **Allow users to comment securely from apps** ([#102](https://github.com/uncatcrea/wp-appkit/issues/102), [@mleroi](https://github.com/mleroi))
- **User login : allow users to authenticate securely from apps** ([#101](https://github.com/uncatcrea/wp-appkit/issues/101), [@mleroi](https://github.com/mleroi))
- **Extract favorites feature from core** ([#100](https://github.com/uncatcrea/wp-appkit/issues/100), [@mleroi](https://github.com/mleroi))
- **WP CLI command to export WP-AppKit apps** ([#87](https://github.com/uncatcrea/wp-appkit/issues/87), [@mleroi](https://github.com/mleroi))
- **"Live query" web service** ([#86](https://github.com/uncatcrea/wp-appkit/issues/86), [@mleroi](https://github.com/mleroi))
- **Add an easier way (template tag?) to retrieve the template used for the current page** ([#84](https://github.com/uncatcrea/wp-appkit/issues/84), [@mleroi](https://github.com/mleroi))
- **Remove unused code following the Zip export history simplification in 0.3** ([#71](https://github.com/uncatcrea/wp-appkit/issues/71), [@mleroi](https://github.com/mleroi))
- **"Post list" component : don't force to choose a taxonomy term** ([#50](https://github.com/uncatcrea/wp-appkit/issues/50), [@mleroi](https://github.com/mleroi))

### Bugfixes:
- **Collections items not removed from local storage when the collection is empty in webservice** ([#91](https://github.com/uncatcrea/wp-appkit/issues/91), [@mleroi](https://github.com/mleroi))
- **Themes' readme files not supported if filename upper case** ([#85](https://github.com/uncatcrea/wp-appkit/issues/85), [@mleroi](https://github.com/mleroi))
- **Plugins field doesn't allow "source" parameter** ([#82](https://github.com/uncatcrea/wp-appkit/issues/82), [@lpointet](https://github.com/lpointet))
- **Setup appearance and navigation checkbox wrongly checked** ([#81](https://github.com/uncatcrea/wp-appkit/issues/81), [@lpointet](https://github.com/lpointet))
- **wpak_unavailable_media.png appears unexpectedly in a single post** ([#27](https://github.com/uncatcrea/wp-appkit/issues/27), [@mleroi](https://github.com/mleroi))
- **PhoneGap plugins duplicates in config.xml ** ([#24](https://github.com/uncatcrea/wp-appkit/issues/24), [@mleroi](https://github.com/mleroi))

## 0.3.1 (2015-06-09)

### Bugfixes:
- **Export zip creation with PHP >= 5.2.8** ([#88](https://github.com/uncatcrea/wp-appkit/issues/88), [@lpointet](https://github.com/lpointet))

## 0.3 (2015-05-04)

### Features:

- **Help buttons in metaboxes** ([#55](https://github.com/uncatcrea/wp-appkit/issues/55), [@lpointet](https://github.com/lpointet))
- **Remove "Simulator" entry in WordPress menu** ([#61](https://github.com/uncatcrea/wp-appkit/issues/61), [@lpointet](https://github.com/lpointet))
- **Thoughts about the new publish metabox** ([#63](https://github.com/uncatcrea/wp-appkit/issues/63), [@lpointet](https://github.com/lpointet))
- **PhoneGap metabox fieldsets** ([#64](https://github.com/uncatcrea/wp-appkit/issues/64), [@lpointet](https://github.com/lpointet))
- **Better menu items filtering : make "menu-items" js filter more general** ([#66](https://github.com/uncatcrea/wp-appkit/issues/66), [@mleroi](https://github.com/mleroi))
- **Allow to pass any custom data along with web services from app themes and to filter server answer accordingly : new “web-service-params” js filter** ([#68](https://github.com/uncatcrea/wp-appkit/issues/68), [@mleroi](https://github.com/mleroi))
- **Pass any custom param to app templates : new “template-args” js filter** ([#69](https://github.com/uncatcrea/wp-appkit/issues/69), [@mleroi](https://github.com/mleroi))
- **Allow to customize web services jQuery ajax calls : new “ajax-args” js filter** ([#70](https://github.com/uncatcrea/wp-appkit/issues/70), [@mleroi](https://github.com/mleroi))
- **WordPress 4.2 Compatibility** ([#72](https://github.com/uncatcrea/wp-appkit/issues/72), [@lpointet](https://github.com/lpointet))
- **Feedback message when saving** ([#77](https://github.com/uncatcrea/wp-appkit/issues/77), [@lpointet](https://github.com/lpointet))
- **New Appearance Metabox** ([#78](https://github.com/uncatcrea/wp-appkit/issues/78), [@lpointet](https://github.com/lpointet))
- **New My Project Metabox** ([#79](https://github.com/uncatcrea/wp-appkit/issues/79), [@lpointet](https://github.com/lpointet))

### Bugfixes:
- **Edit panel title capitalization** ([#51](https://github.com/uncatcrea/wp-appkit/issues/51), [@lpointet](https://github.com/lpointet))
- **Edit panel add new button label** ([#52](https://github.com/uncatcrea/wp-appkit/issues/52), [@lpointet](https://github.com/lpointet))
- **Components metabox title** ([#53](https://github.com/uncatcrea/wp-appkit/issues/53), [@lpointet](https://github.com/lpointet))
- **PhoneGap Build metabox title** ([#54](https://github.com/uncatcrea/wp-appkit/issues/54), [@lpointet](https://github.com/lpointet))
- **PhoneGap Build metabox - mandatory fields** ([#56](https://github.com/uncatcrea/wp-appkit/issues/56), [@lpointet](https://github.com/lpointet))
- **Components not immediatly updated in nav when component changes** ([#59](https://github.com/uncatcrea/wp-appkit/issues/59), [@lpointet](https://github.com/lpointet))
- **"Increment JS/CSS resources version** ([#60](https://github.com/uncatcrea/wp-appkit/issues/60), [@lpointet](https://github
- **"Help me" displays above the web service link in the "Synchronization"** ([#62](https://github.com/uncatcrea/wp-appkit/issues/62), [@lpointet](https://github.com/lpointet))
- **Export returns always the same .zip** ([#65](https://github.com/uncatcrea/wp-appkit/issues/65), [@mleroi](https://github.com/mleroi))
- **Fatal Error on Plugin Activation** ([#73](https://github.com/uncatcrea/wp-appkit/issues/73), [@lpointet](https://github.com/lpointet))
- **Windows zip creation** ([#74](https://github.com/uncatcrea/wp-appkit/issues/74), [@lpointet](https://github.com/lpointet))
- **Access rights problem when trying uploading a theme** ([#75](https://github.com/uncatcrea/wp-appkit/issues/75), [@lpointet](https://github.com/lpointet))

### Security
- **More security for web services calls by checking that the corresponding app is valid** ([67](https://github.com/uncatcrea/wp-appkit/issues/67), [@mleroi](https://github.com/mleroi))

## 0.2 (2015-03-08)

### Features:
- **Persistent storage** ([#48](https://github.com/uncatcrea/wp-app-kit/issues/48), [@mleroi](https://github.com/mleroi))
- **New theme directory** [(#39](https://github.com/uncatcrea/wp-app-kit/issues/39), [@mleroi](https://github.com/mleroi))
- **Theme's metadata** ([#38](https://github.com/uncatcrea/wp-app-kit/issues/38), [@mleroi](https://github.com/mleroi))
- **Sample themes migration** ([@mleroi](https://github.com/mleroi))
- **Allow to add custom routes** ([#37](https://github.com/uncatcrea/wp-app-kit/issues/37), [@mleroi](https://github.com/mleroi))
- **theme templates files with add-ons** ([#40](https://github.com/uncatcrea/wp-app-kit/issues/40), [@mleroi](https://github.com/mleroi))
- **Geolocation module** ([#41](https://github.com/uncatcrea/wp-app-kit/issues/41), [@mleroi](https://github.com/mleroi))
- **Static views** ([#42](https://github.com/uncatcrea/wp-app-kit/issues/42), [@mleroi](https://github.com/mleroi))
- **Filter app history management** ([#43](https://github.com/uncatcrea/wp-app-kit/issues/43), [@mleroi](https://github.com/mleroi))
- **Create components with add-ons** ([#44](https://github.com/uncatcrea/wp-app-kit/issues/44), [@mleroi](https://github.com/mleroi))
- **Permalinks activation warning** ([#45](https://github.com/uncatcrea/wp-app-kit/issues/45), [@mleroi](https://github.com/mleroi))

### Bugfixes:
- **Secure PhoneGap meta box**: ([#33](https://github.com/uncatcrea/wp-app-kit/issues/33), [@lpointet](https://github.com/lpointet))
- **Remove default mobile image size** ([#46](https://github.com/uncatcrea/wp-app-kit/issues/46), [@mleroi](https://github.com/mleroi))
- **Woff2 files not accepted in themes** ([#47](https://github.com/uncatcrea/wp-app-kit/issues/47), [@mleroi](https://github.com/mleroi))