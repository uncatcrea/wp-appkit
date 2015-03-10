# WP-AppKit
Create mobile apps and connect them to WordPress.
> Please keep in mind that WP-AppKit is currently in beta. Test it, break it! But be careful if you use it for professional purposes.

> All versions under 1.0 are beta versions

## News
* 03/08/2015: Version 0.2 is here!
* To know about changes in this version, read the [changelog](https://github.com/uncatcrea/wp-app-kit/blob/master/CHANGELOG.md)

### Migrating from 0.1 to 0.2
* Backup your theme (located in the /wp-content/plugins/wp-app-kit/app/themes)
* Deactivate version 0.1 in WordPress
* Uninstall version 0.1 (simply delete or delete the wp-app-kit plugin folder)
* Don't worry, apps' configuration is preserved
* Install version 0.2 (drop the wp-appkit folder in your plugins folder and acticate wp-appkit in WordPress)
* It creates a new folder (themes-wp-appkit) in wp-content which is the new home for your app themes
* Note that sample themes are not delivered any more with the plugin itself
* You may find them in their own repository: https://github.com/uncatcrea/wp-appkit-themes
* Finally you have to save at least one time any apps done with the 0.1 to migrate them

## What Is WP-AppKit?
It's a WordPress plugin which provides:
* An admin panel to configure your app
* JSON web services to feed your app with WordPress content
* A Javascript engine to create app's themes
* Sample themes to trigger your natural inclination to unbridled creativity

WP-AppKit uses the [Cordova](http://cordova.apache.org/) technology for the app. It means that the app is developed with HTML, CSS and JavaScript but still can be distribued in app stores.

## The WordPress Admin Panel
WP-AppKit adds a menu to the WordPress admin.
* Here you can choose the targeted platform
* Pick a theme
* Pick the app's components (eg. Post List)
* Build the app's navigation
* Use Chrome to simulate your app in the browser
* Export the app's sources ready to be compiled with [PhoneGap Build](https://build.phonegap.com/)

![WP-AppKit WordPress Panel Screenshot](https://cloud.githubusercontent.com/assets/6179747/6526510/ef87b228-c412-11e4-8c90-2753b6d1f4ef.png)

![WP-AppKit WordPress Panel Screenshot](https://cloud.githubusercontent.com/assets/6179747/6472500/4d27fd6a-c1f3-11e4-90fb-df233d82a98b.png)

## App Themes
> Starting with version 0.2, themes are located in /wp-content/themes-wp-appkit

WP-AppKit allows to create themes for your apps. As we use the Cordova technology, app themes are build with HTML, CSS and JavaScript. WP-AppKit provides a JavaScript engine able to interact with the WP-AppKit web services. It also mimics WordPress themes with files such as single, archive... You will also be able to use template tags.

However an app's theme *is not* a WordPress theme.

WP-AppKit themes use JavaScript (along with HTML and CSS) instead of PHP. Template Tags for example use [UnderscoreJS](http://underscorejs.org/).

Developing app themes are at the heart of the WP-AppKit project. If you're ready to dive into the mysteries of app themes, head to the doc: http://uncategorized-creations.com/wp-appkit/doc/.

![Single.html edited into Brackets](https://cloud.githubusercontent.com/assets/6179747/6472801/32accb3a-c1f5-11e4-8ff8-f7286b082a7c.png)

## Who's Behind This Project?
This project is done the [Uncategorized Creations](http://uncategorized-creations.com/) team. UncatCrea is a group of web professionals working with WordPress and Cordova/PhoneGap. facing the challenges to build content based mobile apps connected to WordPress, we've decided to create WP-AppKit.

### Meet the team
* Benjamin Lupu: Product/Project Management
* Mathieu Le Roi: Lead Developer
* Lionel Pointet: Developer