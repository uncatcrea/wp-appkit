define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );

	var utils = { };

	utils.log = function() {
		if ( console && Config.debug_mode == 'on' ) {
			console.log.apply( console, arguments );
		}
	};

	utils.addParamToUrl = function( uri, key, value ) {
		var new_url = '';
		var re = new RegExp( "([?&])" + key + "=.*?(&|$)", "i" );
		var separator = uri.indexOf( '?' ) !== -1 ? "&" : "?";
		if ( uri.match( re ) ) {
			new_url = uri.replace( re, '$1' + key + "=" + value + '$2' );
		} else {
			new_url = uri + separator + key + "=" + value;
		}
		return new_url;
	};

	return utils;
} );