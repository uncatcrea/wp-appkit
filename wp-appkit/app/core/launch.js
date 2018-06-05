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

	//If Progressive Web App, activate service worker to cache app source files:
	if ( Config.app_type === 'pwa' && 'serviceWorker' in navigator ) {
		navigator.serviceWorker
				.register( Config.app_path +'service-worker-cache.js' )
				.then( function () {
					console.log( '[WP-AppKit Service Worker] Registered' );
				} );
        
	}

	var dynamic_paths = {
		theme: '../themes/'+ Config.theme
	};

	require.config({
	    paths: dynamic_paths
	});

	require(['jquery', 'underscore', 'core/addons-internal', 'core/app-utils', 'core/app', 'core/router', 'core/region-manager', 'core/stats', 'core/phonegap/utils','core/lib/hooks'],
			function ($, _, Addons, Utils, App, Router, RegionManager, Stats, PhoneGap, Hooks) {

			var launch = function() {
				
				require(Addons.getJs('init','before'),function(){

					App.setIsLaunching( true );

					// Initialize application before using it
					App.initialize( function() {

						RegionManager.buildHead(function(){

							RegionManager.buildLayout(function(){

								RegionManager.buildHeader(function(){

                                    App.router = new Router();

									require(Addons.getJs('theme','before'),function(){
										require(['theme/js/functions'],function(){
											
                                            /**
                                             * Intercept navigation inside the app to trigger Backbone router navigation
                                             * instead of browser page refresh. 
                                             */
                                            RegionManager.handleNavigationInterception();
                                            
											/**
											 * Templates that are preloaded by default for before perf.
											 * Note: we can't require 'page' template here as it is not required in themes.
											 * But when implementing a theme with a 'page' template, it is recommended to 
											 * preload it with the following 'preloaded-templates'.
											 */
											var preloaded_templates = ['single','archive'];
											
											/**
											 * Define templates that are preloaded so that we don't have any delay
											 * when requiring them dynamically.
											 * For example use this filter to preload the 'page' template if you implement one in your theme.
											 */
											preloaded_templates = Hooks.applyFilters('preloaded-templates',preloaded_templates,[]);
											
											//Build 'text!path/template.html' dependencies from preloaded templates:
											preloaded_templates = _.map( preloaded_templates, function( template ) {
												if( template.indexOf( '/' ) === -1 ) {
													template = 'theme/'+ template;
												}
												return 'text!'+ template +'.html';
											} );
											
											require(preloaded_templates,function(){
											
												require(Addons.getJs('theme','after'),
													function(){
														App.sync(
															function( deferred ){
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

                                                                    if ( deferred ) {
                                                                        deferred.resolve( { ok: true, message: '', data: {} } );
                                                                    }

																	App.triggerInfo('app-launched'); //triggers info:app-ready, info:app-first-launch and info:app-version-changed

																	//Refresh at app launch can be canceled using the 'refresh-at-app-launch' App param,
																	//this is useful if we set a specific launch page and don't want to be redirected
																	//after the refresh.
																	if( App.getParam('refresh-at-app-launch') ){
																		//Refresh at app launch : as the theme is now loaded, use theme-app :
																		require(['core/theme-app'],function(ThemeApp){
																			var last_updated = Stats.getStats( 'last_sync' );
																			var refresh_interval = App.options.get( 'refresh_interval' );
																			if( undefined === last_updated || undefined === refresh_interval || Date.now() > last_updated + ( refresh_interval.get( 'value' ) * 1000 ) ) {
																				Utils.log( 'Refresh interval exceeded, refreshing', { last_updated: new Date( last_updated ), refresh_interval: refresh_interval.get( 'value' ) } );
																				ThemeApp.refresh();
																			}
																		});
																	}

																	PhoneGap.hideSplashScreen();

																	App.setIsLaunching( false );
																});
															},
															function( error, deferred ){
																App.launchRouting();

																var error_message = "Error : App could not synchronize with website";

																if ( error.id === 'synchro:no-component' ) {
																	error_message += " : no component found in web service answer. Please add components to the App on WordPress side.";
																} 

																Utils.log( error_message );

                                                                if ( deferred ) {
                                                                    deferred.reject( { ok: false, message: error_message, data: error } );
                                                                }

																App.triggerInfo('no-content');
                                                                
                                                                PhoneGap.hideSplashScreen();

                                                                App.setIsLaunching( false );
															},
															false //true to force refresh local storage at each app launch.
														);

													}
												);
											},
											function(error){
												Utils.log('Error : could not preload templates', error);
												App.setIsLaunching( false );
											});
										},
										function(error){
											Utils.log('Error : theme/js/functions.js not found', error);
											App.setIsLaunching( false );
										});
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