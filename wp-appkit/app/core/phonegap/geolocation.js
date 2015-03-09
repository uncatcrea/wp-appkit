define( function( require ) {

	"use strict";

	var _     = require('underscore'),
		Utils = require('core/app-utils');

	var geolocation = { };

	var current_position = {
		lat: '48.858554',
		lng: '2.294492',
		altitude: '0',
		accuracy: '0',
		altitudeAccuracy: '0',
		heading: '0',
		speed: '0',
		timestamp: 'none'
	};

	var set_position = function( position_raw ) {
		if( position_raw.hasOwnProperty('coords') ) {
			current_position.lat = position_raw.coords.latitude;
			current_position.lng = position_raw.coords.longitude;
			current_position.altitude = position_raw.coords.altitude;
			current_position.accuracy = position_raw.coords.accuracy;
			current_position.altitudeAccuracy = position_raw.coords.altitudeAccuracy;
			current_position.heading = position_raw.coords.heading;
			current_position.speed = position_raw.coords.speed;
			current_position.timestamp = position_raw.hasOwnProperty('timestamp') && parseInt(position_raw.timestamp) > 0 ? position_raw.timestamp : 0;
		}
	};
	
	geolocation.updateCurrentPosition = function( cb_ok, cb_error ) {
		if ( navigator.geolocation ) {
			navigator.geolocation.getCurrentPosition( 
				function( position ) {
					set_position( position );
					Utils.log('Geolocation : position retrieved', position);
					cb_ok( current_position );
				}, function() {
					Utils.log('Geolocation : navigator.geolocation.getCurrentPosition() failed');
					cb_error( 'navigator-no-position' );
				} 
			);
		} else {
			// Browser doesn't support Geolocation 
			// or the PhoneGap geolocation plugin is not installed
			Utils.log('Geolocation : no support for geolocation');
			cb_error( 'no-support-for-geolocation' );
		}
	}

	geolocation.getCurrentPosition = function( cb_ok, cb_error ) {
		if( current_position.timestamp !== 'none' ){
			cb_ok( current_position );
		}else {
			//No location has been retrieved yet, get it!
			geolocation.updateCurrentPosition( cb_ok, cb_error );
		}
	};

	return geolocation;
} );