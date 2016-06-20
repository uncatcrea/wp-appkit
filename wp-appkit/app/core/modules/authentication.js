define( function( require ) {

	/**
	 * WP-AppKit Authentication Module
	 * 
	 * Handles user authentication using :
	 * - RSA public key encryption for handshake and all sensible data exchanges,
	 * - HMAC token controls based on user secret key to authenticate web services.
	 */

	"use strict";

	var $ = require( 'jquery' );
	var _ = require( 'underscore' );
	var Backbone = require( 'backbone' );
	var App = require( 'core/app' );
	var Hooks = require( 'core/lib/hooks' );
	var Config = require( 'root/config' );
	var Sha256 = require( 'core/lib/encryption/sha256' );
	var WsToken = require( 'core/lib/encryption/token' );
	var Utils = require( 'core/app-utils' );
	require( 'core/lib/encryption/jsencrypt' );
	require( 'localstorage' );

	var AuthenticationDataModel = Backbone.Model.extend( {
		localStorage: new Backbone.LocalStorage( 'Authentication-' + Config.app_slug  ),
		defaults: {
			user_login: "", //This is what the user used to login: can be login or email
			secret: "",
			public_key: "",
			is_authenticated : false,
			permissions: {},
			info: {}
		}
	} );

	var authenticationData = new AuthenticationDataModel( { id: 'Authentication-' + Config.app_slug } );
	authenticationData.fetch();
	
	var ws_url = WsToken.getWebServiceUrlToken( 'authentication' ) + '/authentication/';

	var authentication = { };

	var generateRandomSecret = function() {
		var base = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-=!@#$%^&*()_+:<>{}[]".split( '' );
		var secret = '';
		for ( var i = 0; i < 50; i++ ) {
			secret += base[Math.floor( Math.random() * base.length )];
		}
		return secret;
	};
	
	var resetSecret = function() {
		var new_secret = generateRandomSecret();
		authenticationData.set( 'secret', new_secret );
		authenticationData.save();
	};

	var generateHMAC = function( data, secret ) {
		if ( secret !== undefined ) {
			data = data + '|' + secret;
		}
		return Sha256( data );
	};
	
	var checkHMAC = function( data, secret, to_check ) {
		return generateHMAC( data, secret ) === to_check;
	};
	
	var getTimestamp = function() {
		return Math.floor( Date.now() / 1000);
	};
	
	var generateControlStringFromData = function( to_control, control_key ) {
		var control_string = '';
		
		if ( to_control.length ) {
			_.each( to_control, function( value ) {
				control_string += value;
			} );
			control_string = generateHMAC( control_string, control_key );
		}
		
		return control_string;
	};
	
	var generateControlString = function( control_data_keys, control_data, control_key ) {
		var to_control = [];
		
		_.each( control_data_keys, function( key ) {
			if ( control_data.hasOwnProperty( key ) ) {
				to_control.push( control_data[key] );
			}
		} );
		
		return generateControlStringFromData( to_control, control_key );
	};
	
	/**
	 * Builds the HMAC secured Web service params object.
	 * 
	 * @param string auth_action
	 * @param string user
	 * @param boolean use_user_control Whether to use the user secret key or generate a random one
	 * @param array data_keys Sets the order of data items for hmac
	 * @param object data Data to send to server
	 * @returns object HMAC secured Web service params object
	 */
	var getAuthWebServicesParams = function( auth_action, user, use_user_control, data_keys, data, add_data_to_ws_params ) {
		
		user = user === undefined ? 'wpak-app' : user;
		
		add_data_to_ws_params = add_data_to_ws_params === undefined || add_data_to_ws_params === true;
		
		var timestamp = getTimestamp();

		var web_service_params = {
			user: user,
			timestamp: timestamp
		};
		
		if ( add_data_to_ws_params ) {
			web_service_params.auth_action = auth_action;
		}
		
		var control_key = '';
		if ( use_user_control === undefined || use_user_control === false ) {
			//Used when the user secret key is not defined yet : generate random temporary secret
			//and send it along with web service params.
			control_key = generateRandomSecret();
			web_service_params.control_key = control_key;
		} else {
			//User secret key is available : we use it for HMAC, but we DON'T send
			//it as a web service param!
			control_key = authenticationData.get( 'secret' );
		}

		var to_control = [auth_action, user, timestamp];
		if ( data_keys !== undefined && data !== undefined ) {
			_.each( data_keys, function( key ) {
				if ( data.hasOwnProperty( key ) ) {
					to_control.push( data[key] );
					if ( add_data_to_ws_params ) {
						web_service_params[key] = data[key];
					}
				}
			} );
		}
		
		web_service_params.control = generateControlStringFromData( to_control, control_key ) ;
		
		return web_service_params;
	};

	var ajaxQuery = function( web_service_params, success, error ) {
		
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
		
		ajax_args.success = success;
		
		ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
			var error_id = 'ajax-failed';
			error_id += ( ':' + Utils.getAjaxErrorType( jqXHR, textStatus, errorThrown ) );
			error( error_id, { jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } );
		};
		
		Utils.log( 
			'Sending authentication query', 
			( web_service_params.hasOwnProperty( 'auth_action' ) ? '"' + web_service_params.auth_action + '"' : '' ),
			( web_service_params.hasOwnProperty( 'user' ) ? 'for user ' + web_service_params.user : '' ) 
		);
		
		$.ajax( ajax_args );
	};

	var getPublicKey = function( user, cb_ok, cb_error ) {

		var web_service_params = getAuthWebServicesParams( 'get_public_key', user, false );

		//Retrieve app's public key from server :
		var success = function( data ) {
			if ( data.hasOwnProperty( 'result' ) && data.result.hasOwnProperty( 'status' ) ) {
				if ( data.result.status == 1 ) {
					if ( data.public_key && data.public_key.length && data.control ) {
						
						if ( checkHMAC( data.public_key + user, web_service_params.control_key, data.control ) ) {
						
							//Set public key to Local Storage :
							authenticationData.set( 'public_key', data.public_key );
							authenticationData.save();
							
							Utils.log( 'Public key retrieved successfully' );

							cb_ok( data.public_key );
						} else {
							cb_error( 'wrong-hmac' );
						}
						
					} else if ( data.hasOwnProperty( 'auth_error' ) ) {
						cb_error( data.auth_error );
					}  else {
						cb_error( 'no-auth-error' );
					}
				} else {
					cb_error( 'web-service-error', data.result.message );
				}
			} else {
				cb_error( 'no-result' );
			}
		};

		var error = function( error_id ) {
			cb_error( error_id );
		};

		ajaxQuery( web_service_params, success, error );
		
	};
	
	var sendAuthData = function( user, pass, cb_ok, cb_error ) {
		
		//Get public key from Local Storage :
		var public_key = authenticationData.get( 'public_key' );
		if ( public_key.length ) {
			
			//Generate local app user secret key (for HMAC checking and potentially symetric encryption):
			var user_secret = generateRandomSecret();
			authenticationData.set( 'secret', user_secret ); //need to set it here to be retrieved in getAuthWebServicesParams();

			var encrypt = new JSEncrypt();
			encrypt.setPublicKey( public_key );
			
			var to_encrypt = {
				user : user,
				pass : pass,
				secret : user_secret
			};
			
			var encrypted = encrypt.encrypt( JSON.stringify( to_encrypt ) );
			
			var web_service_params = getAuthWebServicesParams( 'connect_user', user, true, ['encrypted'], { encrypted: encrypted } );
			
			var success = function( data ) {
				if ( data.hasOwnProperty( 'result' ) && data.result.hasOwnProperty( 'status' ) && data.result.hasOwnProperty( 'message' ) ) {
					if ( data.result.status == 1 ) {
						if ( data.hasOwnProperty( 'authenticated' ) ) {
							if ( data.authenticated === 1 ) {
							
									if ( data.hasOwnProperty( 'permissions' ) && data.hasOwnProperty( 'info' ) ) {
							
										//Check control hmac :
										if ( checkHMAC( 'authenticated' + user, user_secret, data.control ) ) {

											//Memorize current user login and secret : 
											authenticationData.set( 'user_login', user ); //This is what the user used to login: can be login or email
											authenticationData.set( 'secret', user_secret );
											authenticationData.set( 'is_authenticated', true );

											//Memorize returned user permissions
											authenticationData.set( 'permissions', data.permissions );
											
											//Memorize returned user info
											authenticationData.set( 'info', data.info );

											//Save all this to local storage
											authenticationData.save();
											
											Utils.log( 'User "' + user + '" logged in successfully', authentication.getCurrentUser() );

											cb_ok( { user: user, permissions: data.permissions, info: data.info } );

										} else {
											cb_error( 'wrong-hmac' );
										}
										
									} else {
										cb_error( 'no-permissions' );
									}
								
							} else if ( data.hasOwnProperty( 'auth_error' ) ) {
								cb_error( data.auth_error );
							} else {
								cb_error( 'no-auth-error' );
							}
							
						} else {
							cb_error( 'wrong-auth-data' );
						}
					} else {
						cb_error( 'web-service-error', data.result.message );
					}
				}else {
					cb_error( 'wrong-result-data' );
				}
			};

			var error = function( error_id ) {
				cb_error( error_id );
			};
		
			ajaxQuery( web_service_params, success, error );
			
		} else {
			cb_error( 'no-public-key');
		}
		
	};

	/**
	 * Generates authentication control data for any custom webservice action.
	 * Use this to authenticate a webservice call.
	 * It generates a control hash based on control_data/user/timestamp/user_secret_key that allows 
	 * to check on server side that the query comes from the right user and has not been modified.
	 * 
	 * @param {string}         action             Webservice query action name 
	 * @param {array}          control_data_keys  Ordered keys of the data you want to control (order is important for the hash control!)
	 * @param {JSON Object}    control_data       Data you want to control.
	 * @returns {JSON Object}  auth_data          Object containing the authentication data, that can be checked directly 
	 *                                            with WpakRsaPublicPrivateAuth::check_authenticated_action() on server side
	 */
	authentication.getActionAuthData = function( action, control_data_keys, control_data ) {
		var auth_data = null;
		
		var user_authenticated = authenticationData.get( 'is_authenticated' ); 
		if ( user_authenticated ) {
			var user_login = authenticationData.get( 'user_login' ); //This is what the user used to login: can be login or email
			var user_secret = authenticationData.get( 'secret' );
			if ( user_login && user_secret ) {
				auth_data = getAuthWebServicesParams( action, user_login, true, control_data_keys, control_data, false );
			}
		}
		
		return auth_data;
	};

	/** 
	 * Get public info about the current user
	 * 
	 * @param {String} field  (Optional) to get a specific user data field (can be 'login', 'permissions', 'info')
	 * @returns {JSON Object} :
	 *			- login {String}
	 *			- permissions {JSON Object} 
	 *			- info {JSON Object} 
	 */
	authentication.getCurrentUser = function( field ) {
		var user = null;
		
		var user_authenticated = authenticationData.get( 'is_authenticated' );
		if ( user_authenticated ) {
			user = {
				login: authenticationData.get( 'user_login' ), //This is what the user used to login: can be login or email
				permissions: authenticationData.get( 'permissions' ),
				info: authenticationData.get( 'info' )
			};
		}
		
		if ( field !== undefined && user && user.hasOwnProperty( field ) ) {
			user = user[field];
		}
		
		return user;
	};
	
	/**
	 * Check if a user is currently logged in.
	 * Note : this only check if a user is locally logged in into the app :
	 * does not make a remote check to see if his connection is still valid on server side :
	 * use authentication.checkUserAuthenticationFromRemote() for that.
	 * 
	 * @returns {boolean} True if a user is logged in.
	 */
	authentication.userIsLoggedIn = function() {
		return authenticationData.get( 'is_authenticated' );
	};
	
	/**
	 * Checks if the current user has the given capability.
	 * Can be customized using the "current-user-can" filter (and "wpak_auth_user_permissions" filter on server side)
	 * 
	 * @param   {string}   capability    Capability we want to check
	 * @returns {Boolean}  True if the user has the given capability in its permissions
	 */
	authentication.currentUserCan = function ( capability ) {
		var user_can = false;
		
		if ( authenticationData.get( 'is_authenticated' ) ) {
			
			var user_permissions = authenticationData.get( 'permissions' );
			
			if ( user_permissions.hasOwnProperty( 'capabilities' ) ) {
				user_can = ( user_permissions.capabilities.indexOf( capability ) !== -1 );
			}
			
			/**
			 * Use this filter to handle your own capability check.
			 * Useful if you used the "wpak_auth_user_permissions" filter on server side
			 * to customize your user permissions.
			 * 
			 * @param    boolean         user_can      Defines if the user has the capability or not
			 * @param    string          capability    Capability that has to be checked
			 * @param    JSON Object     permissions   Permissions object containing role and capibilities array (and more custom data of yours if you added some)
			 * @param    JSON Object     current_user  Currently connected user
			 */
			user_can = Hooks.applyFilters( 'current-user-can', user_can, [capability, authenticationData.get( 'permissions' ),authentication.getCurrentUser()] );
		
		}
		
		return user_can;
	};
	
	/**
	 * Checks if the current user has the given role.
	 * Can be customized using the "current-user-role" filter (and "wpak_auth_user_permissions" filter on server side)
	 * 
	 * @param   {string}   role   Role we want to check
	 * @returns {Boolean}  True if the user has the given role in its permissions
	 */
	authentication.currentUserRoleIs = function ( role ) {
		var user_role_ok = false;
		if ( authenticationData.get( 'is_authenticated' ) ) {
			
			var user_permissions = authenticationData.get( 'permissions' );
			
			if ( user_permissions.hasOwnProperty( 'roles' ) ) {
				user_role_ok = ( user_permissions.roles.indexOf( role ) !== -1 );
			}
			
			/**
			 * Use this filter to handle your own role check.
			 * Useful if you used the "wpak_auth_user_permissions" filter on server side
			 * to customize your user permissions.
			 * 
			 * @param    boolean         user_role_ok  Defines if the role corresponds to the one given or not
			 * @param    string          role          Role that has to be checked
			 * @param    JSON Object     permissions   Permissions object containing role and capibilities array (and more custom data of yours if you added some)
			 * @param    JSON Object     current_user  Currently connected user
			 */
			user_role_ok = Hooks.applyFilters( 'current-user-role', user_role_ok, [role, authenticationData.get( 'permissions' ), authentication.getCurrentUser()] );
		
		}
		return user_role_ok;
	};
	
	/**
	 * If a user is logged in, does a remote server check to see if his connection 
	 * is still valid by verifying public key and user secret from server.
	 * If we reached the server and it answered connection is not ok, 
	 * automatically calls logUserOut() to trigger logout events.
	 * 
	 * @param {function} cb_auth_ok     Called if the user is connected ok
	 * @param {function} cb_auth_error  Called if the user is not connected
	 */
	authentication.checkUserAuthenticationFromRemote = function( cb_auth_ok, cb_auth_error ) {
		
		var cb_ok = function( data ) {
			
			var user = authentication.getCurrentUser();
			Utils.log( 'User authentication remote check ok : user "'+ user.login +'" connected', user );
			
			if ( cb_auth_ok !== undefined ) {
				cb_auth_ok( data );
			}
		};

		var cb_error = function( error ) {
			
			var message = '';
			switch ( error ) {
				case 'user-connection-expired':
					message += 'user connection expired : user logged out';
					break;
				default:
					message += 'user not connected (' + error + ')';
					break;
			}
			Utils.log( 'User authentication remote check : '+ message);
			
			if ( cb_auth_error !== undefined ) {
				cb_auth_error( error );
			}
		};
		
		var user_authenticated = authenticationData.get( 'is_authenticated' );
		if ( user_authenticated ) {
			
			var public_key = authenticationData.get( 'public_key' );
			
			var hasher = generateRandomSecret();
			var hash = generateHMAC( public_key, hasher );
			
			//We check user connection by sending an authenticated query and checking it on server side.
			//We send a public_key hash so that user public key can be verified on server side.
			var web_service_params = getAuthWebServicesParams( 'check_user_auth', authenticationData.get( 'user_login' ), true, ['hash','hasher'], {hash: hash, hasher:hasher} );
			
			var success = function( data ) {
				if ( data.hasOwnProperty( 'result' ) && data.result.hasOwnProperty( 'status' ) && data.result.hasOwnProperty( 'message' ) ) {
					if ( data.result.status == 1 ) {
						if ( data.hasOwnProperty( 'user_auth_ok' ) ) {
							if ( data.user_auth_ok === 1 ) {

								//The user is connected ok.
								//Update its permissions and info from server's answer:
								authenticationData.set( 'permissions', data.permissions );
								authenticationData.set( 'info', data.info );
								authenticationData.save();
								
								cb_ok( authentication.getCurrentUser() );

							} else if ( data.hasOwnProperty( 'auth_error' ) ) {
								switch( data.auth_error ) {
									case 'user-connection-expired':
										authentication.logUserOut( 2 );
										break;
									case 'wrong-permissions':
										authentication.logUserOut( 4 );
										break;
									default:
										authentication.logUserOut( 3 );
										break;
								}
								cb_error( data.auth_error );
							} else {
								cb_error( 'no-auth-error' );
							}
						} else {
							cb_error( 'wrong-answer-format' );
						}
					} else {
						cb_error( 'web-service-error : '+ data.result.message );
					}
				}else {
					cb_error( 'wrong-result-data' );
				}
			};
			
			var error = function( error_id ) {
				cb_error( error_id );
			};
			
			ajaxQuery( web_service_params, success, error );
			
		} else {
			cb_error( 'no-user-logged-in' );
		}
		
	};
	
	/**
	 * Logs the given user in from given login/password.
	 * Use this to log a user in from your theme.
	 * 
	 * Note : all sensible data (password) is encrypted with RSA public key encryption in the process.
	 * 
	 * @param {string}   login     User login
	 * @param {string}   pass      User password 
	 * @param {function} cb_ok     What to do if login went ok
	 * @param {function} cb_error  What to do if login went wrong
	 */
	authentication.logUserIn = function( login, pass, cb_ok, cb_error ) {
		getPublicKey( 
			login, 
			function( public_key ) {
				sendAuthData( 
					login, 
					pass,
					function( auth_data ) {
						auth_data.type = 'authentication-info'; //So that theme info event subtype is set
						App.triggerInfo( 'auth:user-login', auth_data, cb_ok );
					},
					function( error, message ) {
						var error_data = { type: 'authentication-error', where: 'authentication.logUserIn:sendAuthData' };
						if ( message !== undefined ) {
							error_data.message = message;
						}
						App.triggerError(
							'auth:'+ error,
							error_data,
							cb_error
						);
					}
				);
			}, 
			function( error, message ) {
				var error_data = { type: 'authentication-error', where: 'authentication.logUserIn:getPublicKey' };
				if ( message !== undefined ) {
					error_data.message = message;
				}
				App.triggerError(
					'auth:'+ error,
					error_data,
					cb_error
				);
			}
		);
	};
	
	/**
	 * Log the user out on app side.
	 * Note : this does not call the server to warn it that the user has logged out.
	 * 
	 * @param {int} logout_type :
	 * 1: (default) Normal logout triggered by the user in the app : use this from theme.
	 * 2: logout due to user connection expiration (because the server answered so)
	 * 3: logout due to the server answering that the user is not authenticated at all on server side
	 */
	authentication.logUserOut = function( logout_type ) {
		
		if ( !authenticationData.get( 'is_authenticated' ) ) {
			return;
		}
		
		logout_type = ( logout_type === undefined ) ? 1 : logout_type;
		
		var logout_info_type = '';
		switch( logout_type ) {
			case 1:
				logout_info_type = 'normal';
				break;
			case 2:
				logout_info_type = 'user-connection-expired';
				break;
			case 3:
				logout_info_type = 'user-not-authenticated';
				break;
			case 4:
				logout_info_type = 'user-wrong-permissions';
				break;
			default:
				logout_info_type = 'unknown';
				break;
		}
		
		var logout_info = {
			type: 'authentication-info', //So that theme info event subtype is set
			user: authenticationData.get( 'user_login' ), //This is what the user used to login: can be login or email
			permissions: authenticationData.get( 'permissions' ),
			info: authenticationData.get( 'info' ),
			logout_type: logout_info_type
		};
		
		var user_memory = authenticationData.get( 'user_login' );
		
		authenticationData.set( 'user_login', '' );
		authenticationData.set( 'public_key', '' );
		authenticationData.set( 'secret', '' );
		authenticationData.set( 'is_authenticated', false );
		authenticationData.set( 'permissions', {} );
		authenticationData.set( 'info', {} );
		authenticationData.save();
		
		App.triggerInfo( 'auth:user-logout', logout_info );
		
		Utils.log( 'User "' + user_memory + '" logged out' );
	};
	
	//Display user auth info when the module is loaded, if debug mode activated :
	var log_message = 'User authentication : ';
	if ( authenticationData.get( 'is_authenticated' ) ) {
		log_message += ( 'user "'+ authenticationData.get( 'user_login' ) + '" logged in' );
		Utils.log( log_message, authentication.getCurrentUser() );
	} else {
		log_message += 'no user logged in'
		Utils.log( log_message );
	}

	return authentication;
} );