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