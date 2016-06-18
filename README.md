# WP-AppKit
WP-AppKit is a WordPress plugin to build mobile applications connected to WordPress. Applications are created with HTML, CSS and JavaScript (thanks to the Cordova/PhoneGap technology). Developers, you can extend and customize applications using our API.

## Latest News

### 06/20/2016: Version 0.6
* **WP-AppKit now provides pre-installed starter themes.** You don't need to download and install them anymore :-)
* **New starter themes.** _Wpak themes_ are deprecated. Discover the new shiny _Q themes_ for [iOS](https://github.com/uncatcrea/q-ios) and [Android](https://github.com/uncatcrea/q-android).
* Along with the new starter themes, we've added a **theme management panel** to the plugin's UI.
* **Deep links**. WP-AppKit now provides an easy way to support deeplinks for your app.
* As usual, we've fixed bugs and included a bunch of small evolutions (including a lot of user requests).

**Please note this version breaks backward compatibilities on minor features. Be sure to check the [changelog](https://github.com/uncatcrea/wp-app-kit/blob/master/CHANGELOG.md).**

> Please keep in mind that WP-AppKit is currently in beta. Test it, break it! But be careful if you use it for professional purposes.

> All versions under 1.0 are beta versions

## Getting Started
Creating apps with WP-AppKit means creating JavaScript based app themes. (More on that [here](https://github.com/uncatcrea/wp-appkit/blob/master/README.md#app-themes)).

By default, we provide 2 simple and elegant starter themes ([Q for iOS](https://www.youtube.com/watch?v=jkjtkH6wDys) and [Q for Android](https://www.youtube.com/watch?v=fSQVx8-rqCY)). These themes are pre-installed (since version 0.6).

## Developer Friendly
Our plugin is fully documented and we are committed to support developers. Discover the plugin and themes API on [our website](http://uncategorized-creations.com/wp-appkit/doc/). We also publish regularly [tutorials](http://uncategorized-creations.com/tag/tutorials/) to help you build great apps.

## What Is WP-AppKit?
It's a WordPress plugin which provides:
* An admin panel to configure your app
* JSON web services to feed your app with WordPress content
* A JavaScript engine to create app's themes
* Starter themes to trigger your natural inclination to unbridled creativity

WP-AppKit uses the [Cordova](http://cordova.apache.org/) technology for the app. It means that the app is developed with HTML, CSS and JavaScript but still can be distribued in app stores.

More on that [here](http://uncategorized-creations.com/wp-appkit/).

## The WordPress Admin Panel
WP-AppKit adds a menu to the WordPress admin.
* Here you can choose the targeted platform
* Pick a theme
* Pick the app's components (eg. Post List)
* Build the app's navigation
* Use Chrome to simulate your app in the browser
* Set your secure key to support WordPress authentication in your app
* Set the custom URL scheme for deeplinks
* Export the app's sources ready to be compiled with [PhoneGap Build](https://build.phonegap.com/)

![WP-AppKit WordPress Panel Screenshot](https://cloud.githubusercontent.com/assets/6179747/7479033/24fbf862-f35f-11e4-8c58-ceb823540c73.png)

![WP-AppKit WordPress Panel Screenshot](https://cloud.githubusercontent.com/assets/6179747/6472500/4d27fd6a-c1f3-11e4-90fb-df233d82a98b.png)

## App Themes
WP-AppKit allows to create themes for your apps. As we use the Cordova technology, app themes are build with HTML, CSS and JavaScript. WP-AppKit provides a JavaScript engine able to interact with the WP-AppKit web services. It also mimics WordPress themes with files such as single, archive... You will also be able to use template tags.

However an app's theme *is not* a WordPress theme.

WP-AppKit themes use JavaScript (along with HTML and CSS) instead of PHP. Template Tags for example use [UnderscoreJS](http://underscorejs.org/).

Developing app themes are at the heart of the WP-AppKit project. If you're ready to dive into the mysteries of app themes, head to the doc: [http://uncategorized-creations.com/wp-appkit/doc/](http://uncategorized-creations.com/wp-appkit/doc/).

![Single.html edited into Brackets](https://cloud.githubusercontent.com/assets/6179747/6472801/32accb3a-c1f5-11e4-8ff8-f7286b082a7c.png)

## Who's Behind This Project?
This project is done the [Uncategorized Creations](http://uncategorized-creations.com/) team. UncatCrea is a group of web professionals working with WordPress and Cordova/PhoneGap. facing the challenges to build content based mobile apps connected to WordPress, we've decided to create WP-AppKit.

### Meet the team
* Mathieu Le Roi: _Quiet and mystic_ developer
* Benjamin Lupu: Product/Project Management and _junior theme developer_
* Lionel Pointet: _Body rhythm_ developer