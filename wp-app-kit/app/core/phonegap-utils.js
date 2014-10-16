define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );

	var phonegap = { };

	phonegap.isLoaded = function() {
		return window.cordova != undefined;
	};

	phonegap.hideSplashScreen = function() {
		if ( phonegap.isLoaded() && navigator.splashscreen !== undefined ) {
			navigator.splashscreen.hide();
		}
	};

	phonegap.setNetworkEvents = function( on_online, on_offline ) {
		if ( phonegap.isLoaded() && navigator.connection !== undefined ) {
			document.addEventListener( 'online', on_online, false );
			document.addEventListener( 'offline', on_offline, false );
		}
	};

	phonegap.getNetworkState = function( full_info ) {
		var network_state = 'unknown';
		
		full_info = (full_info !== undefined) && (full_info === true);
		
		if ( phonegap.isLoaded() && navigator.connection !== undefined ) {
			var networkState = navigator.connection.type;

			if( full_info ){
				var states = { };
				states[Connection.UNKNOWN] = 'Unknown connection';
				states[Connection.ETHERNET] = 'Ethernet connection';
				states[Connection.WIFI] = 'WiFi connection';
				states[Connection.CELL_2G] = 'Cell 2G connection';
				states[Connection.CELL_3G] = 'Cell 3G connection';
				states[Connection.CELL_4G] = 'Cell 4G connection';
				states[Connection.CELL] = 'Cell generic connection';
				states[Connection.NONE] = 'No network connection';
				network_state = states[networkState];
			}else{
				network_state = networkState != Connection.NONE ? 'online' : 'offline';
			}
			
		}else if( navigator !== undefined && navigator.onLine !== undefined ){
			//If PhoneGap is not available, try with the standard HTML5 tools :
			network_state = navigator.onLine ? 'online' : 'offline';
		}
		
		return network_state;
	};

	return phonegap;
} );