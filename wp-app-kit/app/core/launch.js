require.config({

    baseUrl: 'vendor',

    waitSeconds: 10,

    paths: {
        core: '../core',
		lang: '../lang',
        root: '..'
    },

    shim: {
        'backbone': {
            deps: ['underscore', 'jquery'],
            exports: 'Backbone'
        },
        'underscore': {
            exports: '_'
        }
    }
});

require(['root/config'],function(Config){

	require.config({
	    paths: {
	    	theme: '../themes/'+ Config.theme
	    }
	});

	require(['jquery', 'core/app-utils', 'core/app', 'core/router', 'core/region-manager', 'core/stats', 'core/phonegap-utils'],
			function ($, Utils, App, Router, RegionManager, Stats, PhoneGap) {

			var launch = function() {
				// Initialize application before using it
				App.initialize( function() {

					RegionManager.buildHead(function(){

						RegionManager.buildLayout(function(){

							RegionManager.buildHeader(function(){

								App.router = new Router();

								require(['theme/js/functions'],
										function(){
											App.sync(
												function(){
													RegionManager.buildMenu(function(){ //Menu items are loaded by App.sync
														
														Stats.increment_count_open();
														Utils.log( 'App opening  count : ', Stats.get_count_open() );
														
														Stats.increment_last_open_time();
														Utils.log( 'Last app opening  was on ', Stats.get_last_open_date() );
														
														App.resetDefaultRoute();
														
														Backbone.history.start();
														
														//Refresh at app launch : as the theme is now loaded, use theme-app :
														require(['core/theme-app'],function(ThemeApp){
															last_updated = App.options.get( 'last_updated' );
															refresh_interval = App.options.get( 'refresh_interval' );
															if( undefined === last_updated || undefined === refresh_interval || Date.now() > last_updated.get( 'value' ) + ( refresh_interval.get( 'value' ) * 1000 ) ) {
																Utils.log( 'Refresh interval exceeded, refreshing', { last_updated: last_updated, refresh_interval: refresh_interval } );
																ThemeApp.refresh();
															}
														});

														PhoneGap.hideSplashScreen();
													});
												},
												function(){
													Backbone.history.start();
													Utils.log("launch.js error : App could not synchronize with website.");

													PhoneGap.hideSplashScreen();

													App.alertNoContent();
												},
												false //true to force refresh local storage at each app launch.
											);

										},
										function(error){
											Utils.log('Error : theme/js/functions.js not found', error);
										}
								);

							});

						});

					});

				});

			};

			if( PhoneGap.isLoaded() ){
				document.addEventListener('deviceready', launch, false);
			}else{
				$(document).ready(launch);
			}

	});

});