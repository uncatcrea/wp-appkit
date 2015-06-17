define( function( require ) {

	"use strict";

	var Hooks = require( 'core/lib/hooks' );
	var Config = require( 'root/config' );
	var Sha256 = require( 'core/lib/encryption/sha256' );

	var token = { };

	token.getWebServiceUrlToken = function( web_service ) {
		var url_token = '';
		var key = '';

		if ( Config.hasOwnProperty( 'auth_key' ) ) {
			key = Config.auth_key;
			var app_slug = Config.app_slug;
			var date = new Date();
			var month = date.getUTCMonth() + 1;
			var day = date.getUTCDate();
			var year = date.getUTCFullYear();
			if ( month < 10 ) {
				month = '0' + month;
			}
			if ( day < 10 ) {
				day = '0' + day;
			}
			var date_str = year + '-' + month + '-' + day;
			var hash = Sha256( key + app_slug + date_str );
			url_token = window.btoa( hash );
		}

		url_token = Hooks.applyFilters( 'get-token', url_token, [ key, web_service ] );

		if ( url_token.length ) {
			url_token = '/' + url_token;
		}

		return url_token;
	};
	
	return token;
} );
