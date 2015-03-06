require.config({

    baseUrl: 'vendor',

    waitSeconds: 10,

    paths: {
        core: '../core',
		lang: '../lang',
		addons: '../addons',
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

	var dynamic_paths = {
		theme: '../themes/'+ Config.theme,
	};

	require.config({
	    paths: dynamic_paths
	});

	require(['jquery', 'core/addons-internal', 'core/app-utils', 'core/app', 'core/router', 'core/region-manager', 'core/stats', 'core/phonegap/utils'],
			function ($, Addons, Utils, App, Router, RegionManager, Stats, PhoneGap) {

			var launch = function() {
				// Initialize application before using it
				App.initialize( function() {

					RegionManager.buildHead(function(){

						RegionManager.buildLayout(function(){

							RegionManager.buildHeader(function(){

								App.router = new Router();

								require(Addons.getJs('theme','before'),function(){
									require(['theme/js/functions'],function(){
										require(Addons.getJs('theme','after'),
											function(){
												App.sync(
													function(){
														RegionManager.buildMenu(function(){ //Menu items are loaded by App.sync

															Stats.updateVersion();
															Stats.incrementCountOpen();
															Stats.incrementLastOpenTime();

															if( Config.debug_mode == 'on' ){
																Utils.log( 'App version : ', Stats.getVersionDiff() );
																Utils.log( 'App opening  count : ', Stats.getCountOpen() );
																Utils.log( 'Last app opening  was on ', Stats.getLastOpenDate() );
															}

															App.launchRouting();

															App.sendInfo('app-launched'); //triggers info:app-ready, info:app-first-launch and info:app-version-changed

															//Refresh at app launch can be canceled using the 'refresh-at-app-launch' App param,
															//this is useful if we set a specific launch page and don't want to be redirected
															//after the refresh.
															if( App.getParam('refresh-at-app-launch') ){
																//Refresh at app launch : as the theme is now loaded, use theme-app :
																require(['core/theme-app'],function(ThemeApp){
																	last_updated = Stats.getStats( 'last_sync' );
																	refresh_interval = App.options.get( 'refresh_interval' );
																	if( undefined === last_updated || undefined === refresh_interval || Date.now() > last_updated + ( refresh_interval.get( 'value' ) * 1000 ) ) {
																		Utils.log( 'Refresh interval exceeded, refreshing', { last_updated: new Date( last_updated ), refresh_interval: refresh_interval.get( 'value' ) } );
																		ThemeApp.refresh();
																	}
																});
															}

															PhoneGap.hideSplashScreen();
														});
													},
													function(){
														Backbone.history.start();
														Utils.log("launch.js error : App could not synchronize with website.");

														PhoneGap.hideSplashScreen();

														App.sendInfo('no-content');
													},
													false //true to force refresh local storage at each app launch.
												);

											}
										);
									},
									function(error){
										Utils.log('Error : theme/js/functions.js not found', error);
									});
								});
							});

						});

					});

				});

			};

			if( PhoneGap.isLoaded() ){
				PhoneGap.setNetworkEvents(App.onOnline,App.onOffline);
				document.addEventListener('deviceready', launch, false);
			}else{
				window.ononline = App.onOnline;
				window.onoffline = App.onOffline;
				$(document).ready(launch);
			}

	});

},function(){ //Config.js not found

	//Can't use Utils.log here : log messages by hand :
	var message = 'WP AppKit error : config.js not found.';
	console && console.log(message);
	document.write(message);

	//Check if we are simulating in browser :
	var query = window.location.search.substring(1);
	if( query.length && query.indexOf('wpak_app_id') != -1 ){
		message = 'Please check that : ';
		message += '<br/> - you are connected to your WordPress back office,';
		message += '<br/> - WordPress permalinks are activated';
		console && console.log(message);
		document.write('<br>'+ message);
	}

});