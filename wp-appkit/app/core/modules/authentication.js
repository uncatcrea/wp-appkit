define( function( require ) {

	"use strict";

	var $ = require( 'jquery' );
	var Backbone = require( 'backbone' );
	var App = require( 'core/app' );
	var Hooks = require( 'core/lib/hooks' );
	var Config = require( 'root/config' );
	var Sha256 = require( 'core/lib/encryption/sha256' );
	var RsaEncrypt = require( 'core/lib/encryption/jsencrypt' );
	var WsToken = require( 'core/lib/encryption/token' );

	require( 'localstorage' );

	var AuthenticationDataModel = Backbone.Model.extend( {
		localStorage: new Backbone.LocalStorage( 'Authentication' ),
		defaults: {
			user_login: "",
			secret: "",
			public_key: "",
		}
	} );

	var authenticationData = new AuthenticationDataModel();
	authenticationData.fetch();

	var authentication = { };

	var generateRandomSecret = function() {
		var base = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-=!@#$%^&*()_+:<>{}[]".split( '' );
		var secret = '';
		for ( var i = 0; i < 50; i++ ) {
			secret += base[Math.floor( Math.random() * base.length )];
		}
		return secret;
	};

	var generateHMAC = function( data, secret ) {
		if ( secret !== undefined ) {
			data = data + '|' + secret;
		}
		return Sha256( data );
	};
	
	var getTimestamp = function() {
		return Math.floor( Date.now() / 1000);
	};
	
	var getAuthWebServicesParams = function( auth_action, user ) {
		
		user = user === undefined ? 'wpak-app' : user;
		
		var control_key = generateRandomSecret();
		var timestamp = getTimestamp();

		var web_service_params = {
			auth_action: auth_action,
			user: user,
			control_key: control_key,
			timestamp: timestamp,
			control: generateHMAC( auth_action + user + timestamp, control_key )
		};
		
		return web_service_params;
	};

	var getPublicKey = function( user, cb_ok, cb_error ) {

		var token = WsToken.getWebServiceUrlToken( 'authentication' );
		var ws_url = token + '/authentication/';

		var web_service_params = getAuthWebServicesParams( 'get_public_key', user );
				
		/**
		* Filter 'web-service-params' : use this to send custom key/value formated  
		* data along with the web service. Those params are passed to the server 
		* (via $_GET) when calling the web service.
		* 
		* Filtered data : web_service_params : JSON object where you can add your custom web service params
		* Filter arguments : 
		* - web_service_name : string : name of the current web service ('synchronization' here).
		*/
		web_service_params = Hooks.applyFilters( 'web-service-params', web_service_params, [ 'authentication' ] );

		//Build the ajax query :
		var ajax_args = {
			timeout: 40000,
			data: web_service_params
		};

		/**
		 * Filter 'ajax-args' : allows to customize the web service jQuery ajax call.
		 * Any jQuery.ajax() arg can be passed here except for : 'url', 'type', 'dataType', 
		 * 'success' and 'error' that are reserved by app core.
		 * 
		 * Filtered data : ajax_args : JSON object containing jQuery.ajax() arguments.
		 * Filter arguments : 
		 * - web_service_name : string : name of the current web service ('synchronization' here).
		 */
		ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, [ 'authentication' ] );

		ajax_args.url = Config.wp_ws_url + ws_url;

		ajax_args.type = 'GET';

		ajax_args.dataType = 'json';

		ajax_args.success = function( data ) {
			if ( data.hasOwnProperty( 'result' ) && data.result.hasOwnProperty( 'status' ) ) {
				if ( data.result.status == 1 ) {
					console.log( 'Authentication data', data );
				}
			}
		};

		ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
			App.triggerError(
				'synchro:ajax',
				{ type: 'ajax', where: 'authentication::getPublicKey', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
				cb_error
			);
		};

		$.ajax( ajax_args );

	};

	authentication.getCurrentSecret = function() {
		var current_secret = authenticationData.get( 'secret' );
		return current_secret;
	};

	authentication.resetSecret = function() {
		var new_secret = generateRandomSecret();
		authenticationData.set( 'secret', new_secret );
		authenticationData.save();
	};

	authentication.init = function() {
		getPublicKey();
	};

	return authentication;
} );