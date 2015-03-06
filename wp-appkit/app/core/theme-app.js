/**
 * Defines functions that can be called from theme functions.js.
 * (Those functions can't be directly called form theme templates).
 */
define( function( require, exports ) {

	"use strict";

	var _ = require( 'underscore' ),
			Backbone = require( 'backbone' ),
			RegionManager = require( 'core/region-manager' ),
			Utils = require( 'core/app-utils' ),
			Config = require( 'root/config' ),
			Messages = require( 'core/messages' ),
			App = require( 'core/app' ),
			Hooks = require( 'core/lib/hooks' ),
			TemplateTags = require( 'core/theme-tpl-tags' ),
			PhoneGap = require( 'core/phonegap/utils' );

	var themeApp = { };

	/************************************************
	 * Events management
	 */

	/**
	 * Theme event aggregator
	 */
	var vent = _.extend( { }, Backbone.Events );

	/**
	 * Allows themes (and addons) to trigger events
	 * @param string event Event id
	 * @param JSON object data
	 */
	themeApp.trigger = function( event, data ) {
		vent.trigger( event, data );
	};
	
	/**
	 * Aggregate App and RegionManager events
	 */
	themeApp.on = function( event, callback ) {
		if ( _.contains( [ 'screen:leave',
							'screen:showed',
							'screen:before-transition',
							'menu:refresh',
							'header:render',
							'waiting:start',
							'waiting:stop'
						],
						event ) ) {
			//Proxy RegionManager events :
			RegionManager.on( event, callback );
		} else {
			vent.on( event, callback );
		}
	};

	/**
	 * Proxy App events to theme events
	 *
	 * @param {string} event App event id
	 * @param {object} data App event data
	 * @returns {event} Triggers theme event based on App core event
	 */
	App.on( 'all', function( event, data ) {

		var theme_event_data = format_theme_event_data( event, data );

		if ( theme_event_data.type == 'error'
				|| theme_event_data.type == 'info'
				|| theme_event_data.type == 'network'
				) {
			//2 ways of binding to error and info events :
			vent.trigger( event, theme_event_data ); //Ex: bind directly to 'info:no-content'
			vent.trigger( theme_event_data.type, theme_event_data ); //Ex: bind to general 'info', then filter with if( info.event == 'no-content' )
		}

	} );

	/**
	 * Formats App core events feedbacks in a themes friendly way.
	 *
	 * @param {string} event App event id (example "error:synchro:ajax")
	 * @param {object} data
	 * @returns {object} {
	 *		event: string : theme event id (example : "synchro:ajax"),
	 *		type: string : 'error' | 'info',
	 *		message: string : error or info message
	 *		data: object : original core event data : {
	 *			type: string : 'ajax' | 'ws-data' | 'not-found' | 'wrong-data',
	 *			where: string : core function where the event occured
	 *			message: string : message associated to the event
	 *			data: object : data associated to the core event
	 *		}
	 * }
	 */
	var format_theme_event_data = function( event, data ) {

		var theme_event_data = { event: event, type: '', message: '', data: data };

		if ( event.indexOf( 'error:' ) === 0 ) {

			theme_event_data.type = 'error';
			theme_event_data.event = event.replace( 'error:', '' );

			if ( data.type == 'ajax' ) {
				theme_event_data.message = Messages.get('error_remote_connexion_failed');
			}
			else {
				theme_event_data.message = Messages.get('error_occured_undefined');
			}

		} else if ( event.indexOf( 'info:' ) === 0 ) {

			theme_event_data.type = 'info';
			theme_event_data.event = event.replace( 'info:', '' );

			if ( event == 'info:no-content' ) {
				theme_event_data.message = Messages.get('info_no_content');
			}

		} else if ( event.indexOf( 'network:' ) === 0 ) {

			theme_event_data.type = 'network';
			theme_event_data.event = event.replace( 'network:', '' );

			if( event == 'network:online' ) {
				theme_event_data.message = Messages.get('info_network_online');
			}else if( event == 'network:offline' ) {
				theme_event_data.message = Messages.get('info_network_offline');
			}

		}

		return theme_event_data;
	};

	/************************************************
	 * Themes actions results
	 */

	/**
	 * Formats data that is used in themes as the result of an event or
	 * treatment.
	 *
	 * @param {boolean} ok
	 * @param {string} message
	 * @param {object} data
	 * @returns object {
	 *		ok: boolean,
	 *		message: string,
	 *		data: object
	 * }
	 */
	var format_result_data = function( ok, message, data ) {

		ok = ok === true || ok === 1 || ok === '1';

		message = !_.isUndefined(message) && _.isString(message) ? message: '';

		data = !_.isUndefined(data) ? data : {};

		return { ok: ok, message: message, data: data };
	};


	/************************************************
	 * Filters, actions and Params management
	 */
	themeApp.filter = function( filter, callback, priority ) {
		Hooks.addFilter( filter, callback, priority );
	}

	themeApp.action = function( action, callback, priority ) {
		Hooks.addAction( action, callback, priority );
	}

	themeApp.setParam = function( param, value ) {
		App.setParam( param, value );
	};


	/************************************************
	 * App contents refresh
	 */

	var refreshing = 0;

	/**
	 * Launches app content refresh
	 *
	 * @param {callback} cb_ok Treatment to apply on success
	 * @param {callback} cb_error Treatment to apply on error
	 * @returns {event|callback} : when refresh is finished :
	 * - "refresh:end" event is triggered with a "result" object param
	 * - callback cb_ok is called if success, with a "result" object param
	 * - callback cb_error is called if error, with a "result" object param
	 *
	 * "result" object : {
	 *		ok: boolean : true if refresh is successful,
	 *		message: string : empty if success, error message if refresh fails,
	 *		data: object : empty if success, error object if refresh fails :
	 *			  Use this result.data if you need specific info about the error.
	 *			  See format_theme_event_data() for error object details.
	 * }
	 */
	themeApp.refresh = function( cb_ok, cb_error ) {

		refreshing++;
		vent.trigger( 'refresh:start' );

		App.sync(
			function() {
				RegionManager.buildMenu(
					function() {
						App.resetDefaultRoute();

						/**
						 * Use the 'go-to-default-route-after-refresh' to control whether
						 * the default route should be automatically triggered after refresh.
						 */
						var go_to_default_route = App.getParam('go-to-default-route-after-refresh');

						if( go_to_default_route ){
							App.router.default_route();
						}

						Backbone.history.stop();
						Backbone.history.start({silent:false});

						refreshing--;
						vent.trigger( 'refresh:end', format_result_data(true) );

						if ( cb_ok ) {
							cb_ok();
						}
					},
					true
				);
			},
			function( error ) {
				refreshing--;

				var formated_error = format_theme_event_data( error.event, error );

				if ( cb_error ) {
					cb_error( formated_error );
				}

				var result = format_result_data(false,formated_error.message,formated_error);

				vent.trigger( 'refresh:end', result );
			},
			true
		);
	};

	themeApp.isRefreshing = function() {
		return refreshing > 0;
	};

	/************************************************
	 * App navigation
	 */

	themeApp.navigate = function( navigate_to_fragment ) {
		App.router.navigate( navigate_to_fragment, { trigger: true } );
	};

	themeApp.navigateToDefaultRoute = function() {
		App.router.default_route();
	};

	/************************************************
	 * Back button
	 */

	/**
	 * Automatically shows and hide Back button according to current screen (list, single, page, comments, etc...)
	 * Use only if back button is not refreshed at each screen load! (otherwhise $go_back_btn will not be set correctly).
	 * @param $go_back_btn Back button jQuery DOM element
	 */
	themeApp.setAutoBackButton = function( $go_back_btn, do_before_auto_action ) {
		RegionManager.on( 'screen:showed', function( current_screen, view ) {
			var display = themeApp.getBackButtonDisplay();
			if ( display == 'show' ) {
				if ( do_before_auto_action != undefined ) {
					do_before_auto_action( true );
				}
				$go_back_btn.show();
				themeApp.updateBackButtonEvents( $go_back_btn );
			} else if ( display == 'hide' ) {
				if ( do_before_auto_action != undefined ) {
					do_before_auto_action( false );
				}
				themeApp.updateBackButtonEvents( $go_back_btn );
				$go_back_btn.hide();
			}
		} );
	};

	/**
	 * To know if the back button can be displayed on the current screen,
	 * according to app history. Use this to configure back button
	 * manually if you don't use themeApp.setAutoBackButton().
	 */
	themeApp.getBackButtonDisplay = function() {
		var display = '';

		var previous_screen = App.getPreviousScreenData();

		if ( !_.isEmpty( previous_screen ) ) {
			display = 'show';
		} else {
			display = 'hide';
		}

		return display;
	};

	/**
	 * Sets back buton click event. Use this to configure back button
	 * manually if you don't use themeApp.setAutoBackButton().
	 * @param $go_back_btn Back button jQuery DOM element
	 */
	themeApp.updateBackButtonEvents = function( $go_back_btn ) {
		if ( $go_back_btn.length ) {
			var display = themeApp.getBackButtonDisplay();
			if ( display == 'show' ) {
				$go_back_btn.unbind( 'click' ).click( function( e ) {
					e.preventDefault();
					var prev_screen_link = App.getPreviousScreenLink();
					themeApp.navigate( prev_screen_link );
				} );
			} else if ( display == 'hide' ) {
				$go_back_btn.unbind( 'click' );
			}
		}
	};

	/************************************************
	 * "Get more" link
	 */

	themeApp.getGetMoreLinkDisplay = function() {
		var get_more_link_data = { display: false, nb_left: 0 };

		var current_screen = App.getCurrentScreenData();
		if ( current_screen.screen_type == 'list' ) {
			var component = App.components.get( current_screen.component_id );
			if ( component ) {
				var component_data = component.get( 'data' );
				if ( component_data.hasOwnProperty( 'ids' ) ) {
					var nb_left = component_data.total - component_data.ids.length;
					get_more_link_data.nb_left = nb_left;
					get_more_link_data.display = nb_left > 0;
				}
			}
		}

		return get_more_link_data;
	};

	themeApp.getMoreComponentItems = function( do_after ) {
		var current_screen = App.getCurrentScreenData();
		if ( current_screen.screen_type == 'list' ) {
			App.getMoreOfComponent(
					current_screen.component_id,
					function( new_items, is_last, data ) {
						var current_archive_view = RegionManager.getCurrentView();
						current_archive_view.addPosts( new_items );
						current_archive_view.render();
						do_after( is_last, new_items, data.nb_left );
					}
			);
		}
	};

	/************************************************
	 * DOM element auto class
	 */

	/**
	 * Sets class to the given DOM element according to the given current screen.
	 * If element is not provided, defaults to <body>.
	 */
	var setContextClass = function( current_screen, element_id ) {
		if ( !_.isEmpty( current_screen ) ) {
			var $element = element_id == undefined ? $( 'body' ) : $( '#' + element_id );
			$element.removeClass( function( index, css ) {
				return ( css.match( /\app-\S+/g ) || [ ] ).join( ' ' );
			} );
			$element.addClass( 'app-' + current_screen.screen_type );
			$element.addClass( 'app-' + current_screen.fragment );
		}
	};

	/**
	 * Adds class on given DOM element according to the current screen.
	 * If element is not provided, defaults to <body>.
	 * @param activate Set to true to activate
	 */
	themeApp.setAutoContextClass = function( activate, element_id ) {
		if ( activate ) {
			RegionManager.on( 'screen:showed', function( current_screen ) {
				setContextClass( current_screen, element_id );
			} );
			setContextClass( App.getCurrentScreenData(), element_id );
		}
		//TODO : handle deactivation!
	};


	/************************************************
	 * Screen transitions
	 */

	themeApp.getTransitionDirection = function( current_screen, previous_screen ) {
		var transition = 'replace';

		if ( current_screen.screen_type == 'list' || current_screen.screen_type == 'custom-component' ) {
			if ( previous_screen.screen_type == 'single' ) {
				transition = 'right';
			} else {
				transition = 'replace';
			}
		} else if ( current_screen.screen_type == 'single' ) {
			if ( previous_screen.screen_type == 'list' || previous_screen.screen_type == 'custom-component' ) {
				transition = 'left';
			} else if ( previous_screen.screen_type == 'comments' ) {
				transition = 'right';
			} else {
				transition = 'replace';
			}
		} else if ( current_screen.screen_type == 'comments' ) {
			transition = 'left';
		} else {
			transition = 'replace';
		}

		return transition;
	};

	themeApp.setAutoScreenTransitions = function( transition_replace, transition_left, transition_right ) {

		themeApp.setParam( 'custom-screen-rendering', true );

		themeApp.action( 'screen-transition', function( $deferred, $wrapper, $current, $next, current_screen, previous_screen ) {

			var direction = themeApp.getTransitionDirection( current_screen, previous_screen );

			switch ( direction ) {
				case 'left':
					transition_left( $wrapper, $current, $next, $deferred );
					break;
				case 'right':
					transition_right( $wrapper, $current, $next, $deferred );
					break;
				case 'replace':
					transition_replace( $wrapper, $current, $next, $deferred );
					break;
				default:
					transition_replace( $wrapper, $current, $next, $deferred );
					break;
			}
			;

		} );

	};

	/************************************************
	 * App network management
	 */

	/**
	 * Retrieve network state : "online", "offline" or "unknown"
	 * If full_info is passed and set to true, detailed connexion info is
	 * returned (Wifi, 3G etc...).
	 *
	 * @param boolean full_info Set to true to get detailed connexion info
	 * @returns string "online", "offline" or "unknown"
	 */
	themeApp.getNetworkState = function(full_info) {
		return PhoneGap.getNetworkState(full_info);
	};

	/************************************************
	 * App custom pages and custom routes management
	 */

	themeApp.showCustomPage = function( template, data, id ) {
		if ( template === undefined ) {
			template = 'custom';
		}
		if ( data === undefined ) {
			data = {};
		}
		if ( id === undefined ) {
			id = 'auto-custom-page';
		}
		App.showCustomPage( template, data, id );
	};
	
	themeApp.addCustomRoute = function( fragment, template, data ) {
		fragment = fragment.replace('#','');
		if ( template === undefined ) {
			template = 'custom';
		}
		if ( data === undefined ) {
			data = {};
		}
		App.addCustomRoute( fragment, template, data );
	};
	
	themeApp.removeCustomRoute = function( fragment ) {
		fragment = fragment.replace('#','');
		App.removeCustomRoute( fragment );
	};

	/**
	 * Add a post to the favorites list.
	 * Refresh the current view in order to reflect this addition (the link/button should be updated).
	 *
	 * @param 	int 	 	id 				The post id.
	 * @param 	callable 	callback 		The callback to call after favorite has been added.
	 * @param 	string 		default_global 	The default value to use as global key for this post id.
	 */
	themeApp.addToFavorites = function( id, callback, default_global ) {
		var item_global = App.getPostGlobal( id, default_global );
		var item = App.getGlobalItem( item_global, id );
		var saved = false;

		if( null !== item ) {
			App.favorites.add( _.extend( { global: item_global }, item ) );
			App.favorites.saveAll();
			saved = true;
		}

		if( undefined !== callback ) {
			callback( saved, id );
		}
	};

	/**
	 * Remove a post from the favorites list.
	 * Refresh the current view in order to reflect this removal (the link/button should be updated).
	 *
	 * @param 	int 	id 				The post id.
	 */
	themeApp.removeFromFavorites = function( id, callback ) {
		var item = App.getGlobalItem( App.getPostGlobal( id ), id );
		var saved = false;

		if( null !== item ) {
			App.favorites.remove( item );
			App.favorites.saveAll();
			saved = true;
		}

		if( undefined !== callback ) {
			callback( saved, id );
		}
	};

    /**
     * Reset the list of favorites.
     */
    themeApp.resetFavorites = function( callback ) {
    	App.favorites.resetAll();

    	if( undefined !== callback ) {
    		callback();
    	}
    };

	/**************************************************
	 * Retrieve internal app data that can be useful in themes
	 */

	themeApp.getGlobalItems = function( global_key, items_ids, result_type ) {
		var items = null;
		
		if( result_type === undefined ) {
			result_type = 'slice';
		}
		
		switch( result_type ) {
			case 'slice' :
				items = App.getGlobalItemsSlice( global_key, items_ids );
				break;
			case 'array' :
				items = App.getGlobalItems( global_key, items_ids );
				break;
		}
		
		return items;
	};
	
	//Use exports so that theme-tpl-tags and theme-app (which depend on each other, creating
	//a circular dependency for requirejs) can both be required at the same time
	//(in theme functions.js for example) :
	_.extend( exports, themeApp );
} );