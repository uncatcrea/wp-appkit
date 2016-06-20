define( function( require ) {

	/**
	 * Implements a Javascript Hook logic (filters and actions) inspired by WordPress Hooks
	 */

	"use strict";

	var $ = require( 'jquery' );
	var _ = require( 'underscore' );

	var hooks = {};

	var filters = {};
	var actions = {};

	var hash_string = function( string_to_hash ) {
		var hash = 0, i, chr, len;
		if ( string_to_hash.length == 0 )
			return hash;
		for ( i = 0, len = string_to_hash.length; i < len; i++ ) {
			chr = string_to_hash.charCodeAt( i );
			hash = ( ( hash << 5 ) - hash ) + chr;
			hash |= 0; // Convert to 32bit integer
		}
		return hash;
	};
	
	var get_callback_id = function( callback ) {
		return hash_string( callback.toString() );
	};

	hooks.applyFilters = function( filter, value, params, context ) {
		if ( filters.hasOwnProperty( filter ) ) {

			var filters_array = filters[filter];
			filters_array = _.sortBy( filters_array, function( filter_object ) {
				return filter_object.priority;
			} );

			params.unshift( value );
			for ( var i = 0; i < filters_array.length; i++ ) {
				value = filters_array[i].callback.apply( context, params );
				params.shift();
				params.unshift( value );
			}
		}
		return value;
	};

	hooks.addFilter = function( filter, callback, priority ) {
		if ( priority === undefined ) {
			priority = 10;
		}
		var filter_object = { callback: callback, priority: priority, callback_id: get_callback_id( callback ) };
		if ( !filters.hasOwnProperty( filter ) ) {
			filters[filter] = [ filter_object ];
		} else {
			filters[filter].push( filter_object );
		}
	};

	hooks.removeFilter = function( filter, callback, priority ) {
		if ( filters.hasOwnProperty( filter ) ) {
			if ( callback === undefined ) {
				delete filters[filter];
			} else {
				var filters_array = filters[filter];
				var callback_id = get_callback_id( callback );
				if ( priority === undefined ) {
					filters_array = _.reject(
						filters_array,
						function( filter_object ) {
							return filter_object.callback_id == callback_id;
						}
					);
				} else {
					filters_array = _.reject(
						filters_array,
						function( filter_object ) {
							return filter_object.callback_id == callback_id && filter_object.priority == priority;
						}
					);
				}
				filters[filter] = filters_array;
			}
		}
	};

	hooks.doActions = function( action, params, filtered_params, context) {

		filtered_params = ( filtered_params === undefined || !_.isObject( filtered_params ) ) ? {} : filtered_params;

		var action_deferred = $.Deferred();
		
		if ( actions.hasOwnProperty( action ) ) {

			var actions_array = actions[action];
			actions_array = _.sortBy( actions_array, function( action_object ) {
				return action_object.priority;
			} );

			var deferred_array = [];
			
			for ( var i = 0; i < actions_array.length; i++ ) {

				//Pass a deferred to each action params so that we can do asynchrone actions : 
				var deferred = $.Deferred();
				deferred_array.push( deferred );
				params.push( deferred );
				
				params.push( filtered_params );

				filtered_params = actions_array[i].callback.apply( context, params );
				if ( filtered_params !== undefined && _.isObject( filtered_params ) ) {
					//The action can return a special param called "break_actions" to stop actions execution :
					if ( filtered_params.break_actions && filtered_params.break_actions === true ) {
						//The action asked to be the last one executed. Respect that :
						break;
					}
				}

				params.pop(); //remove filtered_params
				params.pop(); //remove deferred
			}

			//Once all actions' deferred are done, resolve the main action deferred :
			$.when.apply( $, deferred_array ).done( function() {
				action_deferred.resolve( filtered_params );
				//NOTE : filtered_params will be usable here ONLY if actions callback are NOT ASYNCHRONE :
				//TODO : see if there would be a way to set the filtering logic among
				//the $.when.apply().done() chain...
			} );
			//TODO : see if we can handle a .fail here
			
		} else {
			
			action_deferred.resolve( filtered_params );
			
		}
		
		return action_deferred.promise();
	};

	hooks.addAction = function( action, callback, priority ) {
		if ( priority === undefined ) {
			priority = 10;
		}
		var action_object = { callback: callback, priority: priority, callback_id: get_callback_id( callback ) };
		if ( !actions.hasOwnProperty( action ) ) {
			actions[action] = [ action_object ];
		} else {
			actions[action].push( action_object );
		}
	};

	hooks.removeAction = function( action, callback, priority ) {
		if ( actions.hasOwnProperty( action ) ) {
			if ( callback === undefined ) {
				delete actions[action];
			} else {
				var actions_array = actions[action];
				var callback_id = get_callback_id( callback );
				if ( priority === undefined ) {
					actions_array = _.reject(
						actions_array,
						function( action_object ) {
							return action_object.callback_id == callback_id;
						}
					);
				} else {
					actions_array = _.reject(
						actions_array,
						function( action_object ) {
							return action_object.callback_id == callback_id && action_object.priority == priority;
						}
					);
				}
				actions[action] = actions_array;
			}
		}
	};

	return hooks;
} );