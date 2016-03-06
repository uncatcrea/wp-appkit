# WP-AppKit
Create mobile apps and connect them to WordPress.

## Latest News

### 03/10/2016: Version 0.5
* Better Theme and Plugin API (19)
* Minor enhancements to admin panel (6)
* Better support for icons & splashscreens (3)
* Bug fixes (8)
* Evolutions (7)

Please note this version breaks backward compatibilities on minor features.

**Be sure to check the [changelog](https://github.com/uncatcrea/wp-app-kit/blob/master/CHANGELOG.md).**

> Please keep in mind that WP-AppKit is currently in beta. Test it, break it! But be careful if you use it for professional purposes.

> All versions under 1.0 are beta versions

## Getting Started
Creating apps with WP-AppKit means creating JavaScript based app themes. (More on that [here](https://github.com/uncatcrea/wp-appkit/blob/master/README.md#app-themes)).

To get you started we propose 2 free starter themes (for iOS and Android):
* [Wpak Off-Canvas](https://github.com/uncatcrea/wpak-off-canvas-themes)
* [Wpak Tabs](https://github.com/uncatcrea/wpak-tabs-themes)

**Make sure to download one of these themes after you installed the plugin and preview the app in the browser.**

![Wpkak Off-Canvas screenshot](https://cloud.githubusercontent.com/assets/6179747/8889585/5846e28e-32e0-11e5-9afa-0b9440fd6a62.png)

## Developer Friendly
Our plugin is fully documented and we are committed to support developers. Discover the plugin and themes API on [our website](http://uncategorized-creations.com/wp-appkit/doc/). We also publish regularly [tutorials](http://uncategorized-creations.com/tag/tutorials/) to help you build great apps.

## What Is WP-AppKit?
It's a WordPress plugin which provides:
* An admin panel to configure your app
* JSON web services to feed your app with WordPress content
* A JavaScript engine to create app's themes
* Sample themes to trigger your natural inclination to unbridled creativity

WP-AppKit uses the [Cordova](http://cordova.apache.org/) technology for the app. It means that the app is developed with HTML, CSS and JavaScript but still can be distribued in app stores.

More on that [here](http://uncategorized-creations.com/wp-appkit/).

## The WordPress Admin Panel
WP-AppKit adds a menu to the WordPress admin.
* Here you can choose the targeted platform
* Pick a theme
* Pick the app's components (eg. Post List)
* Build the app's navigation
* Use Chrome to simulate your app in the browser
* Export the app's sources ready to be compiled with [PhoneGap Build](https://build.phonegap.com/)

![WP-AppKit WordPress Panel Screenshot](https://cloud.githubusercontent.com/assets/6179747/7479033/24fbf862-f35f-11e4-8c58-ceb823540c73.png)

![WP-AppKit WordPress Panel Screenshot](https://cloud.githubusercontent.com/assets/6179747/6472500/4d27fd6a-c1f3-11e4-90fb-df233d82a98b.png)

## App Themes
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