define(function (require) {

      "use strict";

      var $                   = require('jquery'),
      	  _                   = require('underscore'),
          Backbone            = require('backbone'),
          Components          = require('core/models/components'),
          Globals             = require('core/models/globals'),
          Navigation          = require('core/models/navigation'),
          Options             = require('core/models/options'),
          Items               = require('core/models/items'),
          Comments            = require('core/models/comments'),
          CustomPage          = require('core/models/custom-page'),
          Config              = require('root/config'),
          Utils               = require('core/app-utils'),
          Hooks               = require('core/lib/hooks'),
		  Stats               = require('core/stats'),
		  Addons              = require('core/addons-internal'),
		  WsToken             = require('core/lib/encryption/token');

	  var app = {};

	  //--------------------------------------------------------------------------
	  //Event aggregator
	  var vent = _.extend({}, Backbone.Events);
	  app.on = function(event,callback){
		  vent.on(event,callback);
	  };

	//--------------------------------------------------------------------------
	//Public event handling : errors and infos

	app.triggerError = function( error_id, error_data, error_callback ) {
		vent.trigger( 'error:' + error_id, error_data );
		Utils.log( 'App error event [' + error_id + ']' + (error_data.hasOwnProperty('message') ? ' ' + error_data.message : ''), error_data );
		if ( error_callback != undefined ) {
			error_data = _.extend( { event: 'error:' + error_id, id: error_id }, error_data );
			error_callback( error_data );
		}
	};

	/**
	 * Triggers an info event. Use this to trigger your own App events from modules and addons.
	 * 
	 * @param {String} info event name
	 * @param {JSON Object} data Data that is passed to event callback
	 */
	app.triggerInfo = function( info, info_data, info_callback ) {

		switch ( info ) {
			
			case 'no-content':
				vent.trigger( 'info:no-content' );
				break;
				
			case 'app-launched':
				var stats = Stats.getStats();
				vent.trigger( 'info:app-ready', { stats: stats } );
				if ( stats.count_open == 1 ) {
					vent.trigger( 'info:app-first-launch', { stats: stats } );
				}
				if ( stats.version_diff.diff != 0 ) {
					vent.trigger( 'info:app-version-changed', { stats: stats } );
				}
				break;
				
			default:
				vent.trigger( 'info:' + info, info_data );
				if ( info_callback != undefined ) {
					info_data = _.extend( { event: 'info:' + info, id: info }, info_data );
					info_callback( info_data );
				}
				break;
				
		}
		
		
	};
	
	  //--------------------------------------------------------------------------
	  //Custom pages and routes handling

	  var current_custom_page = null;
	  var custom_routes = {};

	  app.getCurrentCustomPage = function(){
		  return current_custom_page;
	  };

	  /**
	   * Displays a custom page using the given template.
	   * @param data see models/custom-page.js for data fields
	   */
	  app.showCustomPage = function(template,data,id){
		  var args = {template: template, data: data};
		  if( id !== undefined ){
			  args.id = id;
		  }
		  current_custom_page = new CustomPage(args);
		  app.router.navigate('#custom-page',{trigger: true});
	  };

	  app.addCustomRoute = function( fragment, template, data ) {
		  custom_routes[fragment] = { template: template, data: data };
	  };

	  app.removeCustomRoute = function( fragment ) {
		  if( custom_routes.hasOwnProperty(fragment) ) {
			  delete custom_routes[fragment];
		  }
	  };

	  app.getCustomRoute = function( fragment ) {
		  var route = {};
		  if( custom_routes.hasOwnProperty(fragment) ) {
			  route = custom_routes[fragment];
		  }
		  return route;
	  };

	  //--------------------------------------------------------------------------
	  //App params :
	  //Params that can be changed by themes dynamically : themes can freely change
	  //their value during a same app execution.
	  //TODO : we should link (but not merge because they don't have the same usage)
	  //those params to App options...

	  var params = {
		  'refresh-at-app-launch' : true,
		  'go-to-default-route-after-refresh' : true,
		  'custom-screen-rendering' : false,
	  };

	  app.getParam = function(param){
		  var value = null;
		  if( params.hasOwnProperty(param) ){
    		  value = params[param];
    	  }
    	  return value;
	  };

	  app.setParam = function(param,value){
		  if( params.hasOwnProperty(param) ){
			  params[param] = value;
		  }
	  };

	  //--------------------------------------------------------------------------
	  //App Backbone router :
	  app.router = null;

	  /**
	   * Sets the route corresponding to the app homepage : fragment empty or = "#".
	   * Use the 'default-route' filter to change the default route from functions.js.
	   *
	   * app.router must be set before calling this resetDefaultRoute
	   */
	  app.resetDefaultRoute = function(is_app_launch){

		var default_route = '';

		var is_app_launch = is_app_launch !== undefined && is_app_launch === true;
		if( app.navigation.length > 0 ){
			var first_nav_component_id = app.navigation.first().get('component_id');
			default_route = '#component-'+ first_nav_component_id;
		}else{
			//No navigation item : set default route to first component found:
			if( app.components.length ){
				var first_component = app.components.first();
				default_route = '#component-'+ first_component.id;
			}else{
				Utils.log('No navigation, no component found. Could not set default route.');
			}
		}

		/**
		 * Hook : filter 'default-route' : use this to define your own default route
		 */
		default_route = Hooks.applyFilters('default-route',default_route,[Stats.getStats(),is_app_launch]);

		if( default_route != '' ){
			app.router.setDefaultRoute(default_route);
		}

		return default_route;
	  };

	  app.launchRouting = function() {

		var default_route = app.resetDefaultRoute(true);

		var launch_route = default_route;

		/**
		 * Use the 'launch-route' filter to display a specific screen at app launch
		 * If the returned launch_route = '' the initial
		 * navigation to launch route is canceled. Then you should navigate manually
		 * to a choosen page in the "info:app-ready" event for example.
		 */
		launch_route = Hooks.applyFilters('launch-route',launch_route,[Stats.getStats()]);

		Hooks.doActions('pre-start-router',[launch_route,Stats.getStats()]);

		if( launch_route.length > 0 ){
			Backbone.history.start();
			//Navigate to the launch_route :
			app.router.navigate(launch_route, {trigger: true});
		}else{
			Backbone.history.start({silent:true});
			//Hack : Trigger a non existent route so that no view is loaded :
			app.router.navigate('#wpak-none', {trigger: true});
		}

		/*
		    //Keep this commented for now in case the problem comes back.
		    //Normally it was solved by the "Router callback checking" hack in routers.js.

			//Start "silently" so that we don't navigate to a url already set in browser
			//(that causes some odd crashes opening views that don't correpond to current screen,
			//when triggering 2 routes in a very short delay) :

			Backbone.history.start({silent: true});

			if( launch_route.length > 0 ){
				//Navigate to the launch_route :
				app.router.navigate(launch_route, {trigger: true});
			}else{
				//Hack : Trigger a non existent route so that no view is loaded :
				app.router.navigate('#wpak-none', {trigger: true});
			}

			//If the launch_route is the same as the current browser url, it won't fire.
			//So we have to restart the router, removing the silent option to really
			//navigate to the launch_route (at last!).
			Backbone.history.stop();
			Backbone.history.start({silent: false});
		*/
	  }

	  //--------------------------------------------------------------------------
	  //History : allows to handle back button.

	  var history_stack = [];
	  var queried_screen_data = {};
	  var previous_screen_memory = {};

	  var history_push = function(screen_data){
		  history_stack.push(screen_data);
	  };

	  var formatScreenData = function(screen_data){
		  return {
			  screen_type:screen_data.screen_type,
			  component_id:screen_data.component_id,
			  item_id:screen_data.item_id,
			  fragment:screen_data.fragment,
			  data:screen_data.hasOwnProperty('data') ? screen_data.data : {},
			  global:screen_data.hasOwnProperty('global') ? screen_data.global : '',
			  label:screen_data.hasOwnProperty('label') ? screen_data.label : ''
		  };
	  };

	  /**
	   * Called in router.js to set the queried screen according to the current route.
	   * This queried screen is then pushed to history in app.addQueriedScreenToHistory().
	   */
	  app.setQueriedScreen = function(screen_data){
		  queried_screen_data = formatScreenData(_.extend(screen_data,{fragment: Backbone.history.fragment}));
	  };

	  app.getQueriedScreen = function(){
		  return queried_screen_data;
	  };

	  /**
	   * Pushes the queried screen to the history stack according to the screen type and where we're from.
	   */
	  app.addQueriedScreenToHistory = function(force_flush){

		  var force_flush_history = force_flush != undefined && force_flush == true;

		  var current_screen = app.getCurrentScreenData();
		  var previous_screen = app.getPreviousScreenData();

		  previous_screen_memory = current_screen;

		  if( current_screen.screen_type != queried_screen_data.screen_type || current_screen.component_id != queried_screen_data.component_id
			  || current_screen.item_id != queried_screen_data.item_id || current_screen.fragment != queried_screen_data.current_fragment ){

			  if( force_flush_history ){
				  history_stack = [];
			  }

			  var history_action = '';

			  if( queried_screen_data.screen_type == 'list' ){
				  history_action = 'empty-then-push';
			  }else if( queried_screen_data.screen_type == 'single' ){
				  if( current_screen.screen_type == 'list' ){
					  history_action = 'push';
				  }else if( current_screen.screen_type == 'custom-component' ){
					  history_action = 'push';
				  }else if( current_screen.screen_type == 'comments' ){
					  if( previous_screen.screen_type == 'single' && previous_screen.item_id == queried_screen_data.item_id ){
						  history_action = 'pop';
					  }else{
						  history_action = 'empty-then-push';
					  }
				  }else{
					  history_action = 'empty-then-push';
				  }
			  }else if( queried_screen_data.screen_type == 'page' ){
				  if( current_screen.screen_type == 'page'
					  && current_screen.component_id == queried_screen_data.component_id
					  && !queried_screen_data.data.is_tree_root
					  ){
					  if( previous_screen.screen_type == 'page'
						  && previous_screen.component_id == queried_screen_data.component_id
						  && previous_screen.item_id == queried_screen_data.item_id
						  ){
						  history_action = 'pop';
					  }else{
						  history_action = 'push';
					  }

				  }else{
					  history_action = 'empty-then-push';
				  }
			  }else if( queried_screen_data.screen_type == 'comments' ){
				  //if( current_screen.screen_type == 'single' && current_screen.item_id == item_id ){
					  history_action = 'push';
				  //}
			  }else if( queried_screen_data.screen_type == 'custom-page' ){
				  history_action = 'empty-then-push';
			  }else if( queried_screen_data.screen_type == 'custom-component' ){
				  history_action = 'empty-then-push';
			  }else{
				  history_action = 'empty';
			  }
			}

			history_action = Hooks.applyFilters( 'make-history', history_action, [ history_stack, queried_screen_data, current_screen, previous_screen ] );

			switch ( history_action ) {
				case 'empty-then-push':
					history_stack = [];
					history_push( queried_screen_data );
					break;
				case 'empty':
					history_stack = [];
					break;
				case 'push':
					history_push( queried_screen_data );
					break;
				case 'pop':
					history_stack.pop();
					break;
			}

	  };

	  /**
	   * Returns infos about the currently displayed screen.
	   * @returns {screen_type:string, component_id:string, item_id:integer, fragment:string}
	   * Core screen_types are "list", "single", "page" "comments".
	   */
	  app.getCurrentScreenData = function(){
		  var current_screen = {};
		  if( history_stack.length ){
			  current_screen = history_stack[history_stack.length-1];
		  }
		  return current_screen;
	  };

	  /**
	   * Returns infos about the screen displayed previously.
	   * @returns {screen_type:string, component_id:string, item_id:integer, fragment:string} or {} if no previous screen
	   */
	  app.getPreviousScreenData = function(){
		  var previous_screen = {};
		  if( history_stack.length > 1 ){
			  previous_screen = history_stack[history_stack.length-2];
		  }
		  return previous_screen;
	  };

	  app.getPreviousScreenMemoryData = function(){
		  return previous_screen_memory;
	  };

	  app.getPreviousScreenLink = function(){
		  var previous_screen_link = '';
		  var previous_screen = app.getPreviousScreenData();
		  if( !_.isEmpty(previous_screen) ){
			  previous_screen_link = '#'+ previous_screen.fragment;
		  }
		  return previous_screen_link;
	  };

	  app.getCurrentScreenGlobal = function( global ) {
        var screen_data = app.getCurrentScreenData();

        var current_screen_global = '';
        if (global != undefined) {
            current_screen_global = global;
        } else {
            if (screen_data.screen_type == 'comments') {
                var previous_screen_data = app.getPreviousScreenData();
                if (previous_screen_data.screen_type == 'single') {
                    current_screen_global = previous_screen_data.global;
                }
            } else {
                if (screen_data.hasOwnProperty('global') && screen_data.global != '') {
                    current_screen_global = screen_data.global;
                }
            }
        }

		current_screen_global = Hooks.applyFilters( 'current-screen-global', current_screen_global, [screen_data, global] );

        return current_screen_global;
	  }

	  //--------------------------------------------------------------------------
	  //App items data :
	  app.components = new Components;
	  app.navigation = new Navigation;
	  app.options    = new Options;

	  //For globals, separate keys from values because localstorage on
	  //collections of collections won't work :-(
	  var globals_keys = new Globals;
	  app.globals = {};

	app.addGlobalType = function( type ) {
		if( undefined === globals_keys.get( type ) ) {
			Utils.log( 'app.addGlobalType info: adding a type to globals', { type: type } );
			globals_keys.add( { id: type } );
            app.globals[type] = new Items.Items( { global: type } );
		}
	};

	app.addGlobalItem = function( type, item ) {
		if( undefined === item.id ) {
			Utils.log( 'app.addGlobalItem error: undefined item.id', { item: item } );
			return;
		}

		if( undefined === globals_keys.get( type ) ) {
			Utils.log( 'app.addGlobalItem info: ' + type + ' not known, adding it', { type: type, item: item } );
			app.addGlobalType( type );
		}

		if( null === app.getGlobalItem( type, item.id ) ) {
			Utils.log( 'app.addGlobalItem info: adding an item to globals', { type: type, item: item } );
            app.globals[type].add( item );
        }
	};

	app.getComponents = function( filter ) {
		var components = [];

		if ( _.isObject( filter ) ) {
			if ( filter.type ) {
				components = app.components.where( { type: filter.type } );
			}
		} else {
			components = app.components.toJSON();
		}

		return components;
	};

	app.getNavigationComponents = function( filter ) {
		var navigation_components = [];

		app.navigation.each( function( element ) {
			var component = app.components.get( element.get( 'component_id' ) );
			if ( component ) {
				if ( filter.type ) {
					if ( component.get( 'type' ) == filter.type ) {
						navigation_components.push( component );
					}
				} else {
					navigation_components.push( component );
				}
			}
		} );

		return navigation_components;
	};

	  //--------------------------------------------------------------------------
	  //App synchronization :

	  app.sync = function(cb_ok,cb_error,force_reload){

		  var force = force_reload != undefined && force_reload;

		  app.components.fetch({'success': function(components, response, options){
			  Hooks.doActions( 'components-fetched', [components, response, options] ).done( function() {
	    		 if( components.length == 0 || force ){
	    			 syncWebService(cb_ok,cb_error);
	    		 }else{
	    			 Utils.log('Components retrieved from local storage.',{components:components});
	    			 app.navigation.fetch({'success': function(navigation, response_nav, options_nav){
	    	    		 if( navigation.length == 0 ){
	    	    			 syncWebService(cb_ok,cb_error);
	    	    		 }else{
	    	    			 Utils.log('Navigation retrieved from local storage.',{navigation:navigation});
	    	    			 globals_keys.fetch({'success': function(global_keys, response_global_keys, options_global_keys){
	    	    	    		 if( global_keys.length == 0 ){
	    	    	    			 syncWebService(cb_ok,cb_error);
	    	    	    		 }else{
	    	    	    			 var fetch = function(_items,_key){
	    	    	    				 return _items.fetch({'success': function(fetched_items, response_items, options_items){
    	    	    	    				app.globals[_key] = fetched_items;
    	    	    	    				//Backbone's fetch returns jQuery ajax deferred object > works with $.when
    	    	    					 }});
	    	    	    			 };

	    	    	    			 var fetches = [];
	    	    	    			 global_keys.each(function(value, key, list){
	    	    	    				 var global_id = value.get('id');
	    	    	    				 var items = new Items.Items({global:global_id});
	    	    	    				 fetches.push(fetch(items,global_id));
	    	    	    			 });

	    	    	    			 $.when.apply($, fetches).done(function () {
	    	    	    				 if( app.globals.length == 0 ){
		    	    	    				 syncWebService(cb_ok,cb_error);
		    	    	    			 }else{
		    	    	    				 Utils.log('Global items retrieved from local storage.',{globals:app.globals});
		    	    	    				 cb_ok();
		    	    	    			 }
	    	    	    		     });

	    	    	    		 }
	    	    			 }});
	    	    		 }
	    			 }});
	    		 }
	      	  });
		  }});

      };

	  var syncWebService = function(cb_ok,cb_error,force_reload){
			var token = WsToken.getWebServiceUrlToken( 'synchronization' );
			var ws_url = token + '/synchronization/';

			/**
			* Filter 'web-service-params' : use this to send custom key/value formated
			* data along with the web service. Those params are passed to the server
			* (via $_GET) when calling the web service.
			*
			* Filtered data : web_service_params : JSON object where you can add your custom web service params
			* Filter arguments :
			* - web_service_name : string : name of the current web service ('synchronization' here).
			*/
			var web_service_params = Hooks.applyFilters('web-service-params',{},['synchronization']);

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
			ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, ['synchronization'] );

			ajax_args.url = Config.wp_ws_url + ws_url;

			ajax_args.type = 'GET';

			ajax_args.dataType = 'json';

			ajax_args.success = function( data ) {
				if ( data.hasOwnProperty( 'result' ) && data.result.hasOwnProperty( 'status' ) ) {
					if ( data.result.status == 1 ) {
						if ( data.hasOwnProperty( 'components' )
								&& data.hasOwnProperty( 'navigation' )
								&& data.hasOwnProperty( 'globals' )
								) {

							app.components.resetAll();
							_.each( data.components, function( value, key, list ) {
								app.components.add( { id: key, label: value.label, type: value.type, data: value.data, global: value.global } );
							} );
							app.components.saveAll();

							app.navigation.resetAll();
							_.each( data.navigation, function( options, key, list ) {
								app.navigation.add( { id: key, component_id: key, options: options } );
							} );
							app.navigation.saveAll();

							//Delete all existing items from local storage :
							globals_keys.each(function(value, key, list){
								var global_id = value.get('id');
								var items = new Items.Items({global:global_id});
								items.resetAll();
							});

							app.globals = {};

							//Then reload new items from web service :
							globals_keys.resetAll();
							_.each( data.globals, function( global, key, list ) {
								var items = new Items.Items( { global: key } );
								items.resetAll();
								_.each( global, function( item, id ) {
									items.add( _.extend( { id: id }, item ) );
								} );
								items.saveAll();
								app.globals[key] = items;
								globals_keys.add( { id: key } );
							} );
							globals_keys.saveAll();

							Stats.incrementContentLastUpdate();

							Addons.setDynamicDataFromWebService( data.addons );

							Utils.log( 'Components, navigation and globals retrieved from online.', { components: app.components, navigation: app.navigation, globals: app.globals } );

							cb_ok();
						} else {
							app.triggerError(
								'synchro:wrong-answer',
								{ type: 'ws-data', where: 'app::syncWebService', message: 'Wrong "synchronization" web service answer', data: data },
								cb_error
							);
						}

					} else if ( data.result.status == 0 ) {
						app.triggerError(
							'synchro:ws-return-error',
							{ type: 'ws-data', where: 'app::syncWebService', message: 'Web service "synchronization" returned an error : [' + data.result.message + ']', data: data },
							cb_error
						);
					} else {
						app.triggerError(
							'synchro:wrong-status',
							{ type: 'ws-data', where: 'app::syncWebService', message: 'Wrong web service answer status', data: data },
							cb_error
						);
					}

				} else {
					app.triggerError(
						'synchro:wrong-format',
						{ type: 'ws-data', where: 'app::syncWebService', message: 'Wrong web service answer format', data: data },
						cb_error
					);
				}

			};

			ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
				app.triggerError(
					'synchro:ajax',
					{ type: 'ajax', where: 'app::syncWebService', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
					cb_error
				);
			};

			$.ajax( ajax_args );
	  };

	  app.getPostComments = function(post_id,cb_ok,cb_error){
    	  var token = WsToken.getWebServiceUrlToken('comments-post');
    	  var ws_url = token +'/comments-post/'+ post_id;

    	  var comments = new Comments.Comments;

    	  var post = app.globals['posts'].get(post_id);

    	  if( post != undefined ){

			/**
			* Filter 'web-service-params' : use this to send custom key/value formated
			* data along with the web service. Those params are passed to the server
			* (via $_GET) when calling the web service.
			*
			* Filtered data : web_service_params : JSON object where you can add your custom web service params
			 * Filter arguments :
			 * - web_service_name : string : name of the current web service ('get-post-comments' here).
			*/
			var web_service_params = Hooks.applyFilters('web-service-params',{},['get-post-comments']);

			//Build the ajax query :
			var ajax_args = {
				data: web_service_params
			};

			/**
			 * Filter 'ajax-args' : allows to customize the web service jQuery ajax call.
			 * Any jQuery.ajax() arg can be passed here except for : 'url', 'type', 'dataType',
			 * 'success' and 'error' that are reserved by app core.
			 *
			 * Filtered data : ajax_args : JSON object containing jQuery.ajax() arguments.
			 * Filter arguments :
			 * - web_service_name : string : name of the current web service ('get-post-comments' here).
			 */
			ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, ['get-post-comments'] );

			ajax_args.url = Config.wp_ws_url + ws_url;

			ajax_args.type = 'GET';

			ajax_args.dataType = 'json';

			ajax_args.success = function( data ) {
				_.each( data.items, function( value, key, list ) {
					comments.add( value );
				} );
				cb_ok( comments, post );
			};

			ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
				app.triggerError(
					'comments:ajax',
					{ type: 'ajax', where: 'app::getPostComments', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
					cb_error
				);
			};

	    	$.ajax( ajax_args );

    	  }else{
    		  app.triggerError(
    			  'comments:post-not-found',
    			  {type:'not-found',where:'app::getPostComments',message:'Post '+ post_id +' not found.'},
		  		  cb_error
    		  );
    	  }
      };

      app.getPostGlobal = function( id, global_default ) {
      	var global = app.getCurrentScreenGlobal( global_default );

      	return Hooks.applyFilters( 'post-global', global, [id, global_default] );
      }

      app.getMoreOfComponent = function(component_id,cb_ok,cb_error){
			var component = app.components.get( component_id );
			if ( component ) {

				var component_data = component.get( 'data' );

				if ( component_data.hasOwnProperty( 'ids' ) ) {

					var token = WsToken.getWebServiceUrlToken( 'component' );
					var ws_url = token + '/component/' + component_id;

					var last_item_id = _.last( component_data.ids );
					ws_url += '?before_item=' + last_item_id;

					/**
					* Filter 'web-service-params' : use this to send custom key/value formated
					* data along with the web service. Those params are passed to the server
					* (via $_GET) when calling the web service.
					*
					* Filtered data : web_service_params : JSON object where you can add your custom web service params
					* Filter arguments :
					* - web_service_name : string : name of the current web service ('get-more-of-component' here).
					*/
					var web_service_params = Hooks.applyFilters('web-service-params',{},['get-more-of-component']);

					//Build the ajax query :
					var ajax_args = {
						data: web_service_params
					};

					/**
					 * Filter 'ajax-args' : allows to customize the web service jQuery ajax call.
					 * Any jQuery.ajax() arg can be passed here except for : 'url', 'type', 'dataType',
					 * 'success' and 'error' that are reserved by app core.
					 *
					 * Filtered data : ajax_args : JSON object containing jQuery.ajax() arguments.
					 * Filter arguments :
					 * - web_service_name : string : name of the current web service ('get-more-of-component' here).
					 */
					ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, [ 'get-more-of-component' ] );

					ajax_args.url = Config.wp_ws_url + ws_url;

					ajax_args.type = 'GET';

					ajax_args.dataType = 'json';

					ajax_args.success = function( answer ) {
						if ( answer.result && answer.result.status == 1 ) {
							if ( answer.component.slug == component_id ) {
								var global = answer.component.global;
								if ( app.globals.hasOwnProperty( global ) ) {

									var new_ids = _.difference( answer.component.data.ids, component_data.ids );

									component_data.ids = _.union( component_data.ids, answer.component.data.ids ); //merge ids
									component.set( 'data', component_data );

									var current_items = app.globals[global];
									_.each( answer.globals[global], function( item, id ) {
										current_items.add( _.extend( { id: id }, item ) ); //auto merges if "id" already in items
									} );

									var new_items = [ ];
									_.each( new_ids, function( item_id ) {
										new_items.push( current_items.get( item_id ) );
									} );

									var nb_left = component_data.total - component_data.ids.length;
									var is_last = !_.isEmpty( answer.component.data.query.is_last_page ) ? true : nb_left <= 0;

									Utils.log( 'More content retrieved for component', { component_id: component_id, new_ids: new_ids, new_items: new_items, component: component } );

									cb_ok( new_items, is_last, { nb_left: nb_left, new_ids: new_ids, global: global, component: component } );

								} else {
									app.triggerError(
										'getmore:global-not-found',
										{ type: 'not-found', where: 'app::getMoreOfComponent', message: 'Global not found : ' + global },
										cb_error
									);
								}
							} else {
								app.triggerError(
									'getmore:wrong-component-id',
									{ type: 'not-found', where: 'app::getMoreOfComponent', message: 'Wrong component id : ' + component_id },
									cb_error
								);
							}
						} else {
							app.triggerError(
								'getmore:ws-return-error',
								{ type: 'web-service', where: 'app::getMoreOfComponent', message: 'Web service "component" returned an error : [' + answer.result.message + ']' },
								cb_error
							);
						}
					};

					ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
						app.triggerError(
							'getmore:ajax',
							{ type: 'ajax', where: 'app::getMoreOfComponent', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
							cb_error
						);
					};

					$.ajax( ajax_args );
				}
			}
      };

	/**
	 * Update items for the given global
	 *
	 * @param {string} global The global we want to update items for
	 * @param {JSON Object} items Global items WITH ITEM ID AS KEY
	 * @param {string} type 'update' to merge items, or 'replace' to delete then replace by new items
	 * @param {boolean} persistent Set to true to save global items to Local Storage
	 * @returns {JSON object} feedback data
	 */
	var update_global_items = function( global, items, type, persistent ) {

		type = ( type !== undefined ) ? type : 'update';
		persistent = ( persistent !== undefined ) && persistent === true;

		var result = { ok: true, message: '', type: '', data: {} };

		if ( type !== 'update' && type !== 'replace' ) {
			result.ok = false;
			result.type = 'bad-format';
			result.message = 'Wrong type : '+ type;
			return result;
		}

		//Create the global if does not exist :
		if ( !app.globals.hasOwnProperty( global ) ) {
			app.globals[global] = new Items.Items( [], { global: global } );
		}

		var current_items = app.globals[global];

		var original_ids = [ ];
		_.each( current_items, function( item, id ) {
			original_ids.push( id );
		} );

		if ( type == "replace" ) {
			current_items.resetAll();
		}

		var items_ids = [ ];
		_.each( items, function( item, id ) {
			items_ids.push( id );
			current_items.add( _.extend( { id: id }, item ), { merge: true } ); //merge if "id" already in items
		} );

		var new_ids = [ ];
		if ( type == "replace" ) {
			new_ids = items_ids;
		} else {
			new_ids = _.difference( items_ids, original_ids );
		}

		var new_items = [ ];
		_.each( new_ids, function( item_id ) {
			new_items.push( current_items.get( item_id ) );
		} );

		if ( persistent ) {
			current_items.saveAll();
		}

		result.data = { new_ids: new_ids, new_items: new_items, global: global, items: current_items };

		return result;
	};

	 /**
	 * Update a component
	 *
	 * @param JSON object new_component Component containing new data
	 * @param array new_globals Array of new items referenced by the new component
	 * @param string type Type of update. Can be :
	 * - "update" : merge new with existing component data,
	 * - "replace" : delete current component data and replace with new
	 * - "replace-keep-global-items" (default) : for list components : replace component ids and merge global items
	 * @param boolean persistent (default false). If true, new data is stored in local storage.
	 * @returns {JSON object} feedback data
	 */
	var update_component = function( new_component, new_globals, type, persistent ) {

		type = ( type !== undefined ) ? type : 'replace-keep-global-items';
		persistent = ( persistent !== undefined ) && persistent === true;

		var result = { ok:true, message:'', type: '', data: {} };

		if ( !new_component.data || !new_component.slug ) {
			//Passed object is not a well formated component
			result.ok = false;
			result.type = 'bad-format';
			result.message = 'Wrong component format';
			return result;
		}

		if ( type !== 'update' && type !== 'replace' && type !== 'replace-keep-global-items' ) {
			result.ok = false;
			result.type = 'bad-format';
			result.message = 'Wrong type : '+ type;
			return result;
		}

		var existing_component = app.components.get( new_component.slug );
    	if( existing_component ) {

			var existing_component_data = existing_component.data;
			var new_component_data = new_component.data;

			if ( new_component_data.hasOwnProperty( 'ids' ) ) { //List component

				if( new_component.global ) {

					var global = new_component.global;
					if ( !app.globals.hasOwnProperty( global ) ) {
						var items = new Items.Items( [], { global: global } );
						app.globals[global] = items;
					}

					var new_ids = [ ];
					if ( type == "replace" || type == "replace-keep-global-items" ) {
						new_ids = new_component_data.ids;
					} else {
						new_ids = _.difference( new_component_data.ids, existing_component_data.ids );
						new_component_data.ids = _.union( existing_component_data.ids, new_component_data.ids ); //merge ids
					}

					existing_component.set( 'data', new_component_data );

					var current_items = app.globals[global];
					if ( type == "replace" ) {
						current_items.resetAll();
					}

					_.each( new_globals[global], function( item, id ) {
						current_items.add( _.extend( { id: id }, item ), { merge: true } ); //merge if "id" already in items
					} );

					var new_items = [ ];
					_.each( new_ids, function( item_id ) {
						new_items.push( current_items.get( item_id ) );
					} );

					if ( persistent ) {
						existing_component.save();
						current_items.saveAll();
					}

					result.data = { new_ids: new_ids, new_items: new_items, component: existing_component };

				} else {
					//A list component must have a global
					result.ok = false;
					result.type = 'bad-format';
					result.message = 'List component must have a global';
				}

			} else { //Non list component

				if ( type == "update" ) {
					new_component_data = _.extend( existing_component_data, new_component_data );
				} else { //replace or replace-keep-global-items
					//nothing : new_component_data is ready to be set
				}

				existing_component.set( 'data', new_component_data );

				if ( persistent ) {
					existing_component.save();
				}

				if( new_component.global ) { //Page component for example

					var global = new_component.global;
					if ( !app.globals.hasOwnProperty( global ) ) {
						var items = new Items.Items( [], { global: global } );
						app.globals[global] = items;
					}

					var current_items = app.globals[global];
					if ( type == "replace" ) {
						current_items.resetAll();
					}

					_.each( new_globals[global], function( item, id ) {
						current_items.add( _.extend( { id: id }, item ), { merge: true } ); //merge if "id" already in items
					} );

					if ( persistent ) {
						current_items.save();
					}

				}

				result.data = { component: existing_component };

			}

		} else {
			result.ok = false;
			result.type = 'not-found';
			result.message = 'Component not found : ' + new_component.slug;
		}

		return result;
	};

	/**
	 * Deletes items for the given global.
	 *
	 * @param {string} global
	 * @param {boolean} persistent If true, will be stored in local storage
	 */
	app.resetGlobalItems = function( global, persistent) {
		persistent = ( persistent !== undefined ) && persistent === true;
		update_global_items( global, {}, 'replace', persistent );
	};

	/**
	 * Live query web service
	 *
	 * @param JSON Object web_service_params Any params that you want to send to the server.
	 *        The following params are automatically recognised and interpreted on server side :
	 *        - wpak_component_slug : { string | Array of string } components to make query on
	 *        - wpak_query_action : { string } 'get-component' to retrieve the full component, or 'get-items' to retrieve choosen component items
	 *        - wpak_items_ids : { int | array of int } If wpak_query_action = 'get-items' : component items ids to retrieve
	 * @param callback cb_ok
	 * @param callback cb_error
	 * @param options JSON Object : allowed settings :
	 * - auto_interpret_result Boolean (default true). If false, web service answer must be interpreted in the cb_ok callback.
	 * - type String : can be one of :
	 *       -- "update" : merge new with existing component data,
	 *       -- "replace" : delete current component data and replace with new
	 *       -- "replace-keep-global-items" (default) : for list components : replace component ids and merge global items
	 * - persistent Boolean (default false). If true, new data is stored in local storage.
	 */
	app.liveQuery = function( web_service_params, cb_ok, cb_error, options ) {

		//auto_interpret_result defaults to true :
		var auto_interpret_result = !options.hasOwnProperty('auto_interpret_result') || options.auto_interpret_result === true;

		//interpretation_type defaults to 'update' :
		var interpretation_type = options.hasOwnProperty('type') ? options.type : 'update';

		//persistent defaults to false :
		var persistent = options.hasOwnProperty('persistent') && options.persistent === true;
		
		var token = WsToken.getWebServiceUrlToken( 'live-query' );
		var ws_url = token + '/live-query';

		/**
		* Filter 'web-service-params' : use this to send custom key/value formatted
		* data along with the web service. Those params are passed to the server
		* (via $_GET) when calling the web service.
		*
		* Filtered data : web_service_params : JSON object where you can add your custom web service params
		* Filter arguments :
		* - web_service_name : string : name of the current web service ('live-query' here).
		*/
		web_service_params = Hooks.applyFilters( 'web-service-params', web_service_params, [ 'live-query' ] );

		//Build the ajax query :
		var ajax_args = {
			data: web_service_params
		};

		/**
		 * Filter 'ajax-args' : allows to customize the web service jQuery ajax call.
		 * Any jQuery.ajax() arg can be passed here except for : 'url', 'type', 'dataType',
		 * 'success' and 'error' that are reserved by app core.
		 *
		 * Filtered data : ajax_args : JSON object containing jQuery.ajax() arguments.
		 * Filter arguments :
		 * - web_service_name : string : name of the current web service ('get-more-of-component' here).
		 */
		ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, [ 'live-query' ] );

		ajax_args.url = Config.wp_ws_url + ws_url;

		ajax_args.type = 'GET';

		ajax_args.dataType = 'json';

		ajax_args.success = function( answer ) {

			if ( answer.result && answer.result.status == 1 ) {

				//If we asked to auto interpret and the ws answer is correctly
				//formated, we do the correct treatment according to answer fields :
				if ( auto_interpret_result ) {

					//See if components data were retured : if so,
					//update the corresponding component(s) :
					var new_components = {};
					if ( answer.components ) {
						new_components = answer.components;
					} else if( answer.component ) {
						new_components[answer.component.slug] = answer.component;
					}

					if( !_.isEmpty( new_components ) ) {

						var error_message = '';
						var update_results = {};

						_.each( new_components, function( component ) {

							var result = update_component( component, answer.globals, interpretation_type, persistent );
							update_results[component.slug] = result;

							if ( result.ok ) {
								Utils.log( 'Live query update component "'+ component.slug +'" OK.', result );
							} else {
								Utils.log( 'Error : Live query : update_component "' + component.slug + '"', result, component );
								error_message += ( result.message + ' ' );
							}

						} );

						if ( error_message === '' ) {
							if ( cb_ok ) {
								cb_ok( answer, update_results );
							}
						} else {
							app.triggerError(
								'live-query:update-component-error',
								{ type: 'mixed', where: 'app::liveQuery', message: error_message, data: { results: update_results } },
								cb_error
							);
						}

					} else if ( answer.globals && !_.isEmpty( answer.globals ) ) {

						//No component returned, but some global items :
						//update current global items with new items sent :

						var error_message = '';
						var update_results = {};

						_.each( answer.globals, function( items, global ) {

							var result = update_global_items( global, items, interpretation_type, persistent );
							update_results[global] = result;

							if ( result.ok ) {
								Utils.log( 'Live query update global "'+ global +'" OK.', result );
							} else {
								Utils.log( 'Error : Live query : update_global_items "' + global + '"', result, items );
								error_message += ( result.message + ' ' );
							}

						} );

						if ( error_message === '' ) {
							if ( cb_ok ) {
								cb_ok( answer, update_results );
							}
						} else {
							app.triggerError(
								'live-query:update-global-items-error',
								{ type: 'mixed', where: 'app::liveQuery', message: error_message, data: { results: update_results } },
								cb_error
							);
						}

					} else {
						app.triggerError(
							'live-query:no-auto-interpret-action-found',
							{ type: 'not-found', where: 'app::liveQuery', message: 'Live Query web service : could not auto interpret answer', data: {answer : answer} },
							cb_error
						);
					}

				} else {

					Utils.log( 'Live query ok (no auto interpret). Web Service answer : "', answer, ajax_args );

					//The 'live-query' web service answer must be interpreted
					//manually in cb_ok() :
					if ( cb_ok ) {
						cb_ok( answer );
					}

				}

			} else {
				app.triggerError(
					'live-query:ws-return-error',
					{ type: 'web-service', where: 'app::liveQuery', message: 'Web service "liveQuery" returned an error : [' + answer.result.message + ']' },
					cb_error
				);
			}
		};

		ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
			app.triggerError(
				'live-query:ajax',
				{ type: 'ajax', where: 'app::liveQuery', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
				cb_error
			);
		};

		$.ajax( ajax_args );
	};

      app.getComponentData = function(component_id){
    	  var component_data = null;

    	  var component = app.components.get(component_id);

    	  if( component ){
    		  var component_type = component.get('type');
    		  switch(component_type){
	    		  case 'posts-list':
	    			  var data = component.get('data');
	    			  var items = new Items.ItemsSlice();
    				  var global = app.globals[component.get('global')];
    				  _.each(data.ids,function(post_id, index){
    					  items.add(global.get(post_id));
    				  });

    				  component_data = {
    						  type: component_type,
    						  view_data: {posts:items,title: component.get('label'), total: data.total},
    						  data: data
    				  };
	    			  break
    			  case 'page':
	    			  var data = component.get('data');
	    			  var component_global = component.get('global');
	    			  var global = app.globals[component_global];
	    			  if( global ){
	    				  var page = global.get(data.root_id);
	    				  if( page ){
	    					  //Page component are directly redirected to "page" route in router.js.
	    					  //> Don't need "view_data" here.
	    					  component_data = {
	    							  type: component_type,
	        						  view_data: {},
	        						  data: data
	        				  };
	    				  }
	    			  }
	    			  break;
	    		  case 'hooks':
	    			  if( component.get('data') ){
		    			  var data = component.get('data');
		    			  if( component.get('global') ){
			    			  var global = app.globals[component.get('global')];
			    			  if( global ){
			    				  if( data.hasOwnProperty('ids') ){
			    					  var items = new Items.ItemsSlice();
			    					  _.each(data.ids,function(post_id, index){
			        					  items.add(global.get(post_id));
			        				  });

			    					  var view_data = {items:items.toJSON(),title: component.get('label')};
			    					  if( data.hasOwnProperty('total') ){
			    						  view_data = _.extend(view_data,{total:data.total});
			    					  }

			    					  component_data = {
		    							  type: 'hooks-list',
		        						  view_data: view_data,
		        						  data: data
			        				  };

			    				  }else{
			    					  //We have a global, but no ids : it's just as if we had no global :
			    					  component_data = {
		    							  type: 'hooks-no-global',
		        						  view_data: data,
		        						  data: data
			        				  };
			    				  }
			    			  }else{
									//We have a global, but no ids : it's just as if we had no global :
									component_data = {
										type: 'hooks-no-global',
										view_data: data,
										data: data
									};
							  }
		    			  }else{
		    				  Utils.log('app.js warning : custom component has a global but no ids : the global will be ignored.');
		    				  component_data = {
    							  type: 'hooks-no-global',
        						  view_data: data,
        						  data: data
	        				  };
		    			  }
    		  		  }else{
    		  			  app.triggerError(
  			    			  'getcomponentdata:hooks:no-data',
  			    			  {type:'wrong-data',where:'app::getComponentData',message: 'Custom component has no data attribute',data:{component:component}},
  			    			  cb_error
  			    		  );
    		  		  }
	    			  break;
				  default:
						component_data = {
							type: '',
							view_data: {},
							data: {}
						};
						component_data = Hooks.applyFilters('component-data',component_data,[component]);
						if( component_data.type == '' ) {
							component_data = null;
						}
						break;
    		  }
    	  };

    	  if( component_data != null ){
    		  component_data = _.extend({id:component.get('id'),label:component.get('label'),global:component.get('global')},component_data);
    	  }

    	  return component_data;
      };

      app.getGlobalItems = function(global_key,items_ids,raw_items){
    	  var items = []; //Must be an array (and not JSON object) to keep items order.

		  raw_items = raw_items === undefined ? false : ( raw_items === true );

    	  if( _.has(app.globals,global_key) ){
			  var global = app.globals[global_key];
			  if( items_ids !== undefined && items_ids.length ) {
				_.each(items_ids,function(item_id, index){
					var item = global.get(item_id);
					if( item ) {
						items.push( raw_items ? item : item.toJSON() );
					}
				});
			  } else {
				  global.each( function(item, key){
					  items.push( raw_items ? item : item.toJSON() );
				  });
			  }
    	  }

    	  return items;
      };

	  app.getGlobalItemsSlice = function( global_key, items_ids ) {
			var items = new Items.ItemsSlice();

			if ( _.has( app.globals, global_key ) ) {
				var global = app.globals[global_key];
				if ( items_ids !== undefined && items_ids.length ) {
					_.each( items_ids, function( post_id ) {
						items.add( global.get( post_id ) );
					} );
				} else {
					global.each( function(item, key){
					  items.add( item );
				  });
				}
			}

			return items;
	  }

      app.getGlobalItem = function(global_key,item_id){
    	  var item = null;

    	  if( _.has(app.globals,global_key) ){
			  var global = app.globals[global_key];
			  var item_raw = global.get(item_id);
			  if( item_raw ){
				  item = item_raw.toJSON();
			  }
    	  }

    	  return item;
      };

	  /**
       * App options:
       */

	  // Retrieve all existing options
	  var fetchOptions = function( callback ){
      	app.options.fetch( {
      		'success': function( appOptions, response, options ) {
				Utils.log( 'Options retrieved from local storage.', { options: appOptions } );
				app.saveOptions( callback );
	      	},
	      	'error': function( appOptions, response, options ) {
	      		app.saveOptions( callback );
	      	}
      	});
	  };

      app.saveOptions = function( callback ) {
      	// Retrieve options from Config and store them locally
      	_.each( Config.options, function( value, key, list ) {
      		// Don't override an existing option
      		if( undefined === app.options.get( key ) ) {
      			Utils.log( 'Option not existing: adding to collection', { key: key, value: value } );
      			app.options.add( { id: key, value: value } );
      		}
      	});
	  	app.options.saveAll();

	  	// If a callback was passed, call it
	  	if( undefined !== callback ) {
	  		callback();
	  	}
      };

	  /**
       * App init:
       *  - set options
	   *  - initialize addons
       */
      app.initialize = function ( callback ) {

		fetchOptions(function(){

			Addons.initialize( function(){

				if( Config.debug_mode == 'on' ) {
					require( ['core/views/debug', 'jquery.velocity'], function( DebugView ) {
						var debugView = new DebugView();
						debugView.render();
					});
				}

				// If a callback was passed, call it
				if( undefined !== callback ) {
					callback();
				}
			});

		});

      };

	//--------------------------------------------------------------------------
	//Network : handle network state if the Network phonegap plugin is available

	app.onOnline = function(){
		vent.trigger('network:online');
		Utils.log('Network event : online');
	};

	app.onOffline = function(){
		vent.trigger('network:offline');
		Utils.log('Network event : offline');
	};

	return app;

});
