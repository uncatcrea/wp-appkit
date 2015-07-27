define( function( require ) {

	"use strict";
	
	var $ = require( 'jquery' );
	var Config = require( 'root/config' );
	var Hooks = require( 'core/lib/hooks' );
	var WsToken = require( 'core/lib/encryption/token' );
	var App = require( 'core/app' );
	var Auth = require( 'core/modules/authentication' );
	var RegionManager = require( 'core/region-manager' );
	
	var comments = {};
	
	var ws_url = WsToken.getWebServiceUrlToken( 'comments-post' ) + '/comments-post/';
	
	var ajaxQuery = function( comment, crud_method, success, error ) {
		
		var action = 'comment-'+ crud_method;
		
		var web_service_params = {};
		web_service_params.action = action;
		web_service_params.comment = comment;
		
		var control_data_keys = ['content', 'post']; 
		//TODO : we could add a filter on this to add more comment data to control field.
		//(and same must be applied on server side)
	
		web_service_params.auth = Auth.getActionAuthData( action, control_data_keys, comment );
		
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
	
	var sendComment = function( comment, cb_ok, cb_error ) {
		
		if ( comment.hasOwnProperty( 'content' ) ) {
			//Base64 encode comment content to avoid special chars breaking web service :
			comment['content'] = btoa( unescape( encodeURIComponent( comment['content'] ) ) );
		} else {
			cb_error( 'no-content' );
			return;
		}
		
		if ( !comment.hasOwnProperty( 'post' ) || !comment.post ) {
			cb_error( 'no-comment-post' );
			return;
		}
		
		var success = function( data ) {
			//Ajax call went ok : see if comment submission was accepted on server side :
			if ( data.hasOwnProperty( 'result' ) && data.result.hasOwnProperty( 'status' ) && data.result.hasOwnProperty( 'message' ) ) {
				if ( data.result.status == 1 ) {
					if ( data.hasOwnProperty( 'comment_ok' ) ) {
						if ( data.comment_ok === 1 ) {
							
							//Update comments list if current screen is comments screen :
							var current_view = RegionManager.getCurrentView();
							if ( current_view.hasOwnProperty( 'update_comments' ) ) {
								current_view.update_comments( data.comments );
							}
							
							cb_ok();
						} else if ( data.hasOwnProperty( 'comment_error' ) ) {
							cb_error( data.comment_error );
						} else {
							cb_error( 'no-comment-error' );
						}
					} else {
						cb_error( 'wrong-answer-format' );
					}
				} else {
					cb_error( 'web-service-error : '+ data.result.message );
				}
			}else {
				cb_error( 'wrong-ws-data' );
			}
		};
		
		var error = function(  jqXHR, textStatus, errorThrown ) {
			cb_error( 'ajax-failed' );
		};
		
		ajaxQuery( comment, 'POST', success, error );
	};
	
	comments.postComment = function( comment, cb_ok, cb_error ) {
		
		console.log( 'Posting comment', comment );
		
		sendComment(
			comment,
			function( comment_data ) {
				App.triggerInfo( 'comment:posted', comment_data, cb_ok );
			},
			function( error ) {
				App.triggerError(
					'comment:'+ error,
					{ type: 'comment-error', where: 'comments.postComment:sendComment' },
					cb_error
				);
			}
		);
		
	};
	
	return comments;
} );

