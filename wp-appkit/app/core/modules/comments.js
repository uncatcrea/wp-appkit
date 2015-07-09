define( function( require ) {

	"use strict";
	
	var $ = require( 'jquery' );
	var Config = require( 'root/config' );
	var Hooks = require( 'core/lib/hooks' );
	var WsToken = require( 'core/lib/encryption/token' );
	var App = require( 'core/app' );
	
	var comments = {};
	
	var ws_url = WsToken.getWebServiceUrlToken( 'comments-post' ) + '/comments-post/';
	
	var ajaxQuery = function( web_service_params, crud_method, success, error ) {
		
		/**
		* Filter 'web-service-params' : use this to send custom key/value formated  
		* data along with the web service. Those params are passed to the server 
		* (via $_GET) when calling the web service.
		* 
		* Filtered data : web_service_params : JSON object where you can add your custom web service params
		* Filter arguments : 
		* - web_service_name : string : name of the current web service ('synchronization' here).
		*/
		web_service_params = Hooks.applyFilters( 'web-service-params', web_service_params, [ 'comments-post' ] );

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
		ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, [ 'comments-post' ] );

		ajax_args.url = Config.wp_ws_url + ws_url;

		ajax_args.type = crud_method;

		ajax_args.dataType = 'json';
		
		ajax_args.success = success;
		
		ajax_args.error = error;
		
		console.log( 'Sending comment query', ajax_args );
		
		$.ajax( ajax_args );
		
	};
	
	comments.postComment = function( comment, cb_ok, cb_error ) {
		
		console.log( 'Posting comment', comment );
		
		var success = function( data ) {
			cb_ok( data );
		};
		
		var error = function(  jqXHR, textStatus, errorThrown ) {
			cb_error( 'ajax:failed' );
			App.triggerError(
				'synchro:ajax',
				{ type: 'ajax', where: 'authentication::getPublicKey', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } }
			);
		};
		
		ajaxQuery( comment, 'POST', success, error );
	};
	
	return comments;
} );

