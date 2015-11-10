define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );

	var phonegap = { };

	phonegap.isLoaded = function() {
		return window.cordova != undefined;
	};

	phonegap.hideSplashScreen = function() {
		if ( phonegap.isLoaded() && navigator.splashscreen !== undefined ) {
			var app_platform = phonegap.getDeviceInfo().platform;
			if ( app_platform === 'ios' ) {
				navigator.splashscreen.hide();
				if ( StatusBar !== undefined ) { //Status bar plugin activated
					StatusBar.show(); // Status bar is initially hidden, we need to show it when splashscreen disappears
				}
			} else {
				navigator.splashscreen.hide(); 
			}
		}
	};

	phonegap.setNetworkEvents = function( on_online, on_offline ) {
		if ( phonegap.isLoaded() && navigator.connection !== undefined ) {
			document.addEventListener( 'online', on_online, false );
			document.addEventListener( 'offline', on_offline, false );
		}
	};
	
	/**
	 * Retrieves information about the current device, using the 
	 * 'cordova-plugin-device' cordova plugin if available.
	 * 
	 * See https://github.com/apache/cordova-plugin-device/blob/c6e23d8a61793c263443794d66d40723b4d04377/doc/index.md
	 * 
	 * Note concerning the 'platform' attribute returned: 
	 * - We return a "lowercased" version of the platform name 
	 *   returned by cordova.
	 * - If 'cordova-plugin-device' is not there, the 
	 *   'platform' info is retrieved from Config.app_platform (app
	 *   platform defined in WordPress BO for the app).
	 * 
	 * @returns {JSON Object} Device information
	 */
	phonegap.getDeviceInfo = function() {
		var device_info = {
			platform: '',
			cordova: '',
			model: '',
			uuid: '',
			version: ''
		};
		
		if ( phonegap.isLoaded() && device !== undefined ) { //Cordova "Device" plugin installed
			device_info.platform = device.platform.toLowerCase();
			device_info.cordova = device.cordova;
			device_info.model = device.model;
			device_info.uuid = device.uuid;
			device_info.version = device.version;
		} else {
			device_info.platform = Config.app_platform;
		}
		
		return device_info;
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