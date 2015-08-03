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
	
	utils.getAjaxErrorType = function( jqXHR, textStatus, errorThrown ) {
		var error_type = 'unknown-error';
		
		textStatus = ( textStatus !== null ) ? textStatus : 'unknown';
		
		switch( jqXHR.status ) {
			case 404:
				if ( textStatus == 'error' ) {
					error_type = 'url-not-found';
				} else {
					error_type = '404:' + textStatus; 
				}
				break;
			case 200:
				if ( textStatus == 'parsererror' ) {
					error_type = 'parse-error-in-json-answer';
				} else {
					error_type = '200:' + textStatus;
				}
				break;
			default:
				error_type = jqXHR.status + ':' + textStatus;
				break;
		}
		
		return error_type;
	};

	return utils;
} );