require.config({

    baseUrl: 'vendor',

    waitSeconds: 10,
    
    paths: {
        core: '../core',
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
	
	require(['jquery', 'core/app-utils', 'core/app', 'core/router', 'core/region-manager', 'core/phonegap-utils'], 
			function ($, Utils, App, Router, RegionManager, PhoneGap) {
	    
			var launch = function() { 
		  
				RegionManager.buildHead(function(){
					
					RegionManager.buildLayout(function(){

						RegionManager.buildHeader(function(){
							
							App.router = new Router();		 
							  
							require(['theme/js/functions'],
									function(){ 
										App.sync(
											function(){
												RegionManager.buildMenu(function(){ //Menu items are loaded by App.sync
													App.resetDefaultRoute();
								  
													Backbone.history.start();
								  
													//Refresh at app launch : as the theme is now loaded, use theme-app :
													require(['core/theme-app'],function(ThemeApp){
														ThemeApp.refresh();
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
		  
			};
	  
			if( PhoneGap.isLoaded() ){
				document.addEventListener('deviceready', launch, false);
			}else{
				$(document).ready(launch);
			}
	    
	});
	
});