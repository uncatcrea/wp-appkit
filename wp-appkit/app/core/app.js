define(function (require,exports) {

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
		  WsToken             = require('core/lib/encryption/token'),
          DeepLink			  = require( 'core/modules/deep-link' );

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
	  app.showCustomPage = function(template,data,fragment,silent){
		  var args = {template: template, data: data};
		  if( fragment !== undefined ){
			  args.id = fragment;
		  }
		  current_custom_page = new CustomPage(args);
		  if ( silent === true ) {
			  app.router.execute_route_silently( app.getScreenFragment( 'custom-page' ) );
			  app.router.navigate( fragment, { trigger: false } );
		  } else {
			  app.router.navigate( app.getScreenFragment( 'custom-page' ), { trigger: true } );
		  }
	  };

	  app.addCustomRoute = function( fragment, template, data ) {
		  fragment = Utils.removeTrailingSlash( fragment );
		  custom_routes[fragment] = { template: template, data: data };
	  };

	  app.removeCustomRoute = function( fragment ) {
		  fragment = Utils.removeTrailingSlash( fragment );
		  if( custom_routes.hasOwnProperty(fragment) ) {
			  delete custom_routes[fragment];
		  }
	  };

	  app.getCustomRoute = function( fragment ) {
		  var route = {};
		  fragment = Utils.removeTrailingSlash( fragment );
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
	  // Beware when merging these because options are stored permanently while params are runtime-dependant (refreshed at each app launch)
	  // Deep Link module especially is updating a param

	  var params = {
		  'refresh-at-app-launch' : true,
		  'go-to-default-route-after-refresh' : true,
		  'custom-screen-rendering' : false,
          'use-html5-pushstate': false //Automatically set to true only for PWA
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
	  app.router = null; //Set in launch.js for now, to avoid circular dependency

      /**
       * Add a screen to history manually from its route, without triggering 
       * the route, nor the corresponding screen rendering. 
       */
      app.silentlyAddRouteToAppHistory = function( route ) {
        var route_info = app.router.getRouteTypeAndParameters( route );
        if ( route_info ) {
            var route_rendering_data = app.router.getRouteData( route_info.type, route_info.parameters );
            app.setQueriedScreen( route_rendering_data.screen_data );
            app.addQueriedScreenToHistory();
            Utils.log( 'Silently added route to history: '+ route );
        }
      };
      
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
			default_route = app.getScreenFragment( 'component', { component_id: first_nav_component_id } );
		}else{
			//No navigation item : set default route to first component found:
			if( app.components.length ){
				var first_component = app.components.first();
				default_route = app.getScreenFragment( 'component', { component_id: first_component.id } );
			}else{
				Utils.log('No menu item, no component found. Could not set default route.');
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

      /**
       * Flag to know if the app is currently launching.
       * (is_launching state is set and updated in launch.js).
       */
      var is_launching = false;

      app.setIsLaunching = function( launching ) {
        is_launching = launching;
      };

      app.isLaunching = function() {
        return is_launching;
      };

      /**
       * Initialize and start app router, taking deeplink and url fragment/pathname into account.
       */
	  app.launchRouting = function() {

		var default_route = app.resetDefaultRoute(true);
		var launch_route = default_route;
		var deep_link_route = DeepLink.getLaunchRoute();
        var url_fragment = window.location.hash; //Retrieve asked route from url's fragment
        var url_pathname = window.location.pathname; //Retrieve asked route from url's relative path
        
		/**
         * Set default route to the one passed via deeplink or via url fragment or via url slug.
         * + Disable refresh at app launch because:
         * 1. it will cause going to default route right after, so our launch route won't be useful
         * 2. if we're here, it's because we're in launching process, after having retrieved some data
         * 3. if we disable 'go-to-default-route-after-refresh', we'd have to enable it again after the next refresh
         */
        var asked_route = '';
        var is_deeplink = false;
		if( deep_link_route.length > 0 ) {
            
            is_deeplink = true;

            asked_route = deep_link_route;

            app.setParam( 'refresh-at-app-launch', false );

		} else if ( url_fragment.length > 0 ) {
            
            asked_route = url_fragment;
            
            app.setParam( 'refresh-at-app-launch', false );
        
        } else if ( app.getParam( 'use-html5-pushstate' ) && url_pathname.length > 0 && url_pathname.indexOf( Config.app_path ) === 0 ) {
            
            asked_route = url_pathname.replace( Config.app_path, '' );
            
            if ( asked_route.length > 0 ) {
                asked_route = Utils.addTrailingSlash( asked_route );
                if ( asked_route !== default_route ) {
                    app.setParam( 'refresh-at-app-launch', false );
                }
            }
        }
        
        //A route was asked by url, that is different from launch route.
        //Set launch_route to this asked route and manually add the default screen
        //to history, so that we can go back to it with back button.
        if ( asked_route.length > 0 && asked_route !== launch_route ) {
           
            var add_default_route_before_asked_route = Hooks.applyFilters( 'add-default-route-before-asked-route', true, [ asked_route, default_route, launch_route ] );
           
            if ( add_default_route_before_asked_route ) {
                app.silentlyAddRouteToAppHistory( default_route );
            }
            
            launch_route = asked_route;
        }

		/**
		 * Use the 'launch-route' filter to display a specific screen at app launch
		 * If the returned launch_route = '' the initial
		 * navigation to launch route is canceled. Then you should navigate manually
		 * to a choosen page in the "info:app-ready" event for example.
		 */
		launch_route = Hooks.applyFilters('launch-route',launch_route,[Stats.getStats()]);
        
		/**
		 * 'pre-start-router' action: use this if you need to do some treatment
		 * before Backbone routing starts.
		 */
		Hooks.doActions( 'pre-start-router', [ launch_route, Stats.getStats() ] ).done( function() {
            
			// Reset DeepLink launch route after hooks are fired to let scripts retrieve this route if needed
			DeepLink.reset();

            var history_start_args = {};
            
            if( app.getParam( 'use-html5-pushstate' ) ) {
                history_start_args.pushState = true;
                history_start_args.root = Config.app_path;
            }

            if ( is_deeplink ) {
                //We don't want to trigger '/' route when router starts and we have a deeplink to open: 
                //it leads to 2 routes triggered very closely causing history/view rendering issue.
                history_start_args.silent = true;
            }

			if( launch_route.length > 0 && default_route.length > 0 ){

                //Start router
                //(history.start triggers the current url fragment or pathname if any)
				Backbone.history.start( history_start_args );

				//Navigate to the launch_route :
				if ( launch_route === default_route ) {
					app.router.default_route();
				} else if ( is_deeplink ) {
                    //Deeplinks start silently, we need to manually navigate to launch_route:
					app.router.navigate(launch_route, {trigger: true});
				}

			}else{
                history_start_args.silent = true;
				Backbone.history.start( history_start_args );
				//Hack : Trigger a non existent route so that no view is loaded :
				app.router.navigate('#wpak-none', {trigger: true});
			}

		} );

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
	  };
	  
	/**
	 * Builds screen link according to given link type.
	 * @param link_type    int          Can be 'single', 'page', 'comments', 'component' or 'custom-page'
	 * @param data         JSON Object  Depends on link_type:
	 * - for single: {global, item_id}
	 * - for page: {component_id, item_id}
	 * - for comments: {item_id}
	 * - for component: {component_id}
	 * - for custom-page: none
	 */
	app.getScreenFragment = function ( link_type, data ) {
		
		var screen_link = '';
        
        var prefix = app.getParam('use-html5-pushstate') ? '' : '#';
        var suffix = app.getParam('use-html5-pushstate') ? '/' : '';
		
		switch( link_type ) {
			case 'single':
				screen_link = prefix + 'single/' + data.global + '/' + data.item_id + suffix;
				break;
			case 'page':
				screen_link = prefix + 'page/' + data.component_id + '/' + data.item_id + suffix;
				break;
			case 'comments':
				screen_link = prefix + 'comments-' + data.item_id + suffix;
				break;
			case 'component':
				var component = app.getComponentData( data.component_id );
				if ( component ) {
					//If page component, return directly page's screen fragment to avoid
					//redirection in router, which leads to back button not working.
					screen_link = component.type !== 'page' ? prefix + 'component-'+ component.id + suffix : prefix + 'page/'+ component.id +'/'+ component.data.root_id + suffix;
				}
				break;
			case 'custom-page':
				screen_link = prefix + 'custom-page' + suffix;
				break;
		}
		
		return screen_link;
	};

	  //--------------------------------------------------------------------------
	  //History : allows to handle back button.

	  var history_stack = [];
	  var queried_screen_data = {};
	  var previous_screen_memory = {};
	  var last_history_action = '';

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
	   * Called in region-manager.js to set the queried screen according to the current route.
	   * This queried screen is then pushed to history in app.addQueriedScreenToHistory().
	   */
	  app.setQueriedScreen = function( screen_data ){
		  
		  screen_data = _.extend( screen_data, {
		  	fragment: Backbone.history.fragment
	  	  });
		  
		  /**
		   * 'queried-screen' filter
		   * Allows to customize queried screen data before inserting it into history.
		   * Useful for example to customize screen label.
		   */
		  screen_data = Hooks.applyFilters( 'queried-screen', screen_data, [] );
		  
		  queried_screen_data = formatScreenData( screen_data );
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
		  
		  //If we "pop" history current_screen is going to be removed from history_stack:
		  //memorize it so that we can know where we came from (for screen transitions for example):
		  previous_screen_memory = current_screen;

		  if( current_screen.screen_type != queried_screen_data.screen_type || current_screen.component_id != queried_screen_data.component_id
			  || current_screen.item_id != queried_screen_data.item_id || current_screen.fragment != queried_screen_data.current_fragment ){

			  if( force_flush_history ){
				  history_stack = [];
			  }

			  var history_action = '';

			  if( queried_screen_data.fragment == current_screen.fragment ) { 
				  //Redisplaying same screen: do nothing
				  history_action = 'none';
			  }else if( queried_screen_data.screen_type == 'list' ){
				  history_action = 'empty-then-push';
			  }else if( queried_screen_data.screen_type == 'single' ){
				  history_action = 'push';
				  if( current_screen.screen_type == 'comments' ){
					  if( previous_screen.screen_type == 'single' && previous_screen.item_id == queried_screen_data.item_id ){
						  history_action = 'pop';
					  }else{
						  history_action = 'empty-then-push';
					  }
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
				  }else if( current_screen.screen_type == 'comments' ){
					  if( previous_screen.screen_type == 'page' && previous_screen.item_id == queried_screen_data.item_id ){
						  history_action = 'pop';
					  }else{
						  history_action = 'empty-then-push';
					  }
				  }else{
					  history_action = 'empty-then-push';
				  }
			  }else if( queried_screen_data.screen_type == 'comments' ){
				  if( ( current_screen.screen_type == 'single' || current_screen.screen_type == 'page' ) && current_screen.item_id == queried_screen_data.item_id ){
					  history_action = 'push';
				  } else {
					  //Trying to reach a comment screen directly without displaying its parent post or page.
					  //Try to add the parent post or page manually, if it exists in the app:
					  var parent_item_global = 'posts';
					  var parent_item = app.getGlobalItem( 'posts', queried_screen_data.item_id );
					  if ( !parent_item ) {
						  parent_item = app.getGlobalItem( 'pages', queried_screen_data.item_id );
						  if ( parent_item ) {
							  parent_item_global = 'pages';
						  }
					  }
					  
					  if ( parent_item ) {
						  
						//Note: this is quite hacky as this is normally done from router and 
						//we don't have all data about the parent screen here (especially for the page case)...
						var parent_item_data = {
							screen_type: parent_item_global === 'posts' ? 'single' : 'page',component_id:'',item_id:queried_screen_data.item_id,
							global:parent_item_global,data:{post:parent_item},label:parent_item.title,
							fragment: parent_item_global === 'posts' ? app.getScreenFragment( 'single', { global: 'posts', item_id: queried_screen_data.item_id } ) : '' 
							//we can't know page's fragment as we don't know page's component...
						};
						
						//Push comments' parent screen to history:
						history_push( formatScreenData( parent_item_data ) );
						
						//Then push the comments screen itself:
						history_action = 'push';
					  }
				  }
			  }else if( queried_screen_data.screen_type == 'custom-page' ){
				  history_action = 'empty-then-push';
			  }else if( queried_screen_data.screen_type == 'custom-component' ){
				  history_action = 'empty-then-push';
			  }else{
				  history_action = 'empty';
			  }
			}

			history_action = Hooks.applyFilters( 'make-history', history_action, [ history_stack, queried_screen_data, current_screen, previous_screen ] );

			last_history_action = history_action;

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
	   * Returns app's current history stack
	   * @returns {Array} App's history stack (array of screen objects)
	   */
	  app.getHistory = function() {
		  //Clone the history_stack array so that it can't be modified from outside:
		  return history_stack.slice(0); 
	  };
	  
	  /**
	   * Returns last action applied to history stack
	   * @returns {String} history action: push, pop, empty or empty-then-push
	   */
	  app.getLastHistoryAction = function() {
		  return last_history_action;
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
	  };

	  //--------------------------------------------------------------------------
	  //App items data :
	  app.components = new Components;
	  app.navigation = new Navigation;
	  app.options    = new Options;
	  app.comments   = new Comments.CommentsMemory;

	  //For globals, separate keys from values because localstorage on
	  //collections of collections won't work :-(
	  var globals_keys = new Globals;
	  app.globals = {};

	app.addGlobalType = function( type ) {
		if( undefined === globals_keys.get( type ) ) {
			Utils.log( 'app.addGlobalType info: adding a type to globals', { type: type } );
			globals_keys.add( { id: type } );
            app.globals[type] = new Items.Items( [], { global: type } );
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

	app.componentExists = function( component_id ) {
		return app.components.get( component_id ) !== undefined;
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

	  app.sync = function( cb_ok, cb_error, force_reload ){

		  var force = force_reload != undefined && force_reload;
          
		  app.components.fetch({'success': function(components, response, options){
			  Hooks.doActions( 'components-fetched', [components, response, options] ).done( function() {
	    		 if( components.length == 0 || force ){
	    			 syncWebService( cb_ok, cb_error );
	    		 }else{
	    			 Utils.log('Components retrieved from local storage.',{components:components});
	    			 app.navigation.fetch({'success': function(navigation, response_nav, options_nav){
	    	    		 if( navigation.length == 0 ){
	    	    			 syncWebService( cb_ok, cb_error );
	    	    		 }else{
	    	    			 Utils.log('Menu items retrieved from local storage.',{navigation:navigation});
	    	    			 globals_keys.fetch({'success': function(global_keys, response_global_keys, options_global_keys){
	    	    	    		 if( global_keys.length == 0 ){
	    	    	    			 syncWebService( cb_ok, cb_error );
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
	    	    	    				 var items = new Items.Items( [], {global:global_id} );
	    	    	    				 fetches.push(fetch(items,global_id));
	    	    	    			 });

	    	    	    			 $.when.apply($, fetches).done(function () {
	    	    	    				 if( app.globals.length == 0 ){
		    	    	    				 syncWebService( cb_ok, cb_error );
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

      /**
       * Calls synchronization webservice.
       * Note: cb_ok and cb_error callbacks are given a deferred that they must
       * resolve so that the refresh:end event is triggered.
       */
	  var syncWebService = function( cb_ok, cb_error ){
			
            //If we're asking a sync with server at app launch (meaning there's no or wrong data in the app
            //at launch), we don't need to re-trigger app refresh after launch.
            if ( app.isLaunching() ) {
                app.setParam( 'refresh-at-app-launch', false );
            }

            //Set refresh events:
            
            //Trigger 'refresh:start' event.
            //For legacy, this is not an "info" event for now.
            vent.trigger( 'refresh:start' );

            var sync_deferred = $.Deferred();

            var sync_cb_ok = function() {
                cb_ok( sync_deferred );
            };

            var sync_cb_error = function( error_data ) {
                cb_error( error_data, sync_deferred );
            };

            //Trigger 'refresh:end' when sync ends
            sync_deferred.always( function( data ){
                vent.trigger( 'refresh:end', data );
            } );
          
            //Setup synchronization webservice call:
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
								var items = new Items.Items( [], {global:global_id} );
								items.resetAll();
							});

							app.globals = {};

							//Then reload new items from web service :
							globals_keys.resetAll();
							_.each( data.globals, function( global, key, list ) {
								var items = new Items.Items( [], { global: key } );
								items.resetAll();
								_.each( global, function( item, id ) {
									items.add( _.extend( { id: id }, item ) );
								} );
								items.saveAll();
								app.globals[key] = items;
								globals_keys.add( { id: key } );
							} );
							globals_keys.saveAll();

							//Empty comments memory
							app.comments.reset();

							Stats.incrementContentLastUpdate();

							Addons.setDynamicDataFromWebService( data.addons );

							if ( data.components.length === 0 ) {

								app.triggerError(
									'synchro:no-component',
									{ type: 'web-service', where: 'app::syncWebService', message: 'No component found for this App. Please add components to the App on WordPress side.', data: data },
									sync_cb_error
								);

							} else {

								Utils.log( 'Components, menu items and globals retrieved from online.', { components: app.components, navigation: app.navigation, globals: app.globals } );
								sync_cb_ok();

							}

						} else {
							app.triggerError(
								'synchro:wrong-answer',
								{ type: 'web-service', where: 'app::syncWebService', message: 'Wrong "synchronization" web service answer', data: data },
								sync_cb_error
							);
						}

					} else if ( data.result.status == 0 ) {
						app.triggerError(
							'synchro:ws-return-error',
							{ type: 'web-service', where: 'app::syncWebService', message: 'Web service "synchronization" returned an error : [' + data.result.message + ']', data: data },
							sync_cb_error
						);
					} else {
						app.triggerError(
							'synchro:wrong-status',
							{ type: 'web-service', where: 'app::syncWebService', message: 'Wrong web service answer status', data: data },
							sync_cb_error
						);
					}

				} else {
					app.triggerError(
						'synchro:wrong-format',
						{ type: 'web-service', where: 'app::syncWebService', message: 'Wrong web service answer format', data: data },
						sync_cb_error
					);
				}

			};

			ajax_args.error = function( jqXHR, textStatus, errorThrown ) {
				app.triggerError(
					'synchro:ajax',
					{ type: 'ajax', where: 'app::syncWebService', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
					sync_cb_error
				);
			};

			$.ajax( ajax_args );
	  };

	var fetchPostComments = function ( post_id, cb_ok, cb_error ) {

		var token = WsToken.getWebServiceUrlToken( 'comments-post' );
		var ws_url = token + '/comments-post/' + post_id;

		var comments = new Comments.Comments;

		/**
		 * Use this 'comments-globals' filter if you defined custom global
		 * types (other than posts and pages) that have comments associated to.
		 */
		var comments_globals = Hooks.applyFilters( 'comments-globals', [ 'posts', 'pages' ], [ post_id ] );

		var post = null;
		var item_global = '';

		comments_globals.every( function ( global ) {
			post = app.globals[global].get( post_id );
			if ( post != undefined ) {
				item_global = global;
			}
			return post === undefined; //To make the loop break as soon as post != undefined
		} );

		if ( post != undefined && post != null ) {

			/**
			 * Filter 'web-service-params' : use this to send custom key/value formated
			 * data along with the web service. Those params are passed to the server
			 * (via $_GET) when calling the web service.
			 *
			 * Filtered data : web_service_params : JSON object where you can add your custom web service params
			 * Filter arguments :
			 * - web_service_name : string : name of the current web service ('get-post-comments' here).
			 */
			var web_service_params = Hooks.applyFilters( 'web-service-params', { }, [ 'get-post-comments' ] );

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
			ajax_args = Hooks.applyFilters( 'ajax-args', ajax_args, [ 'get-post-comments' ] );

			ajax_args.url = Config.wp_ws_url + ws_url;

			ajax_args.type = 'GET';

			ajax_args.dataType = 'json';

			ajax_args.success = function ( data ) {
				_.each( data.items, function ( value, key, list ) {
					comments.add( value );
				} );
				
				post.set( 'nb_comments', comments.length );
				post.save();
						
				cb_ok( comments, post, item_global );
			};

			ajax_args.error = function ( jqXHR, textStatus, errorThrown ) {
				app.triggerError(
					'comments:ajax',
					{ type: 'ajax', where: 'app::fetchPostComments', message: textStatus + ': ' + errorThrown, data: { url: Config.wp_ws_url + ws_url, jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown } },
					cb_error
				);
			};

			$.ajax( ajax_args );

		} else {
			app.triggerError(
				'comments:post-not-found',
				{ type: 'not-found', where: 'app::fetchPostComments', message: 'Post ' + post_id + ' not found.' },
				cb_error
			);
		}
	};

	app.getPostComments = function ( post_id, cb_ok, cb_error, force_refresh ) {

		force_refresh = force_refresh === true;

		var post_comments_memory = app.comments.get( post_id );
		if ( post_comments_memory && !force_refresh ) {
			
			var post_comments = post_comments_memory.get( 'post_comments' );
			var post = post_comments_memory.get( 'post' );
			var item_global = post_comments_memory.get( 'item_global' );

			Utils.log( 'Comments retrieved from cache.', { comments: post_comments, post: post, item_global: item_global });

			cb_ok( post_comments, post, item_global );

		} else {
			
			fetchPostComments(
				post_id,
				function( post_comments, post, item_global ) {
					Utils.log( 'Comments retrieved from online.', { comments: post_comments, post: post, item_global: item_global } );

					//Memorize retrieved comments:
					app.comments.addPostComments( post_id, post, item_global, post_comments );

					cb_ok( post_comments, post, item_global );
				},
				function( error ) {
					cb_error( error );
				}
			);
		}

	};

      app.getPostGlobal = function( id, global_default ) {
      	var global = app.getCurrentScreenGlobal( global_default );

      	return Hooks.applyFilters( 'post-global', global, [id, global_default] );
      };

      app.getMoreOfComponent = function( component_id, cb_ok, cb_error, use_standard_pagination ) {
		  
			use_standard_pagination = ( use_standard_pagination !== undefined ) && use_standard_pagination === true;
			
			var current_screen = app.getCurrentScreenData();
			var current_component_id = '';
			if ( current_screen.component_id && app.componentExists( current_screen.component_id ) ) {
				current_component_id = current_screen.component_id;
			}
			
			/**
			 * Use this 'use-standard-pagination' filter to set standard pagination type for the component.
			 * 
			 * WP-AppKit supports 2 kind of pagination: 
			 * 
			 * - "Infinite Scroll pagination": we retrieve posts before the last post in the list (by passing its id in "before_id" param).
			 *   It avoids post duplication when getting page>1 and a new post was created in the meantime.
			 *   This is the default behaviour for the "Get More Posts" button in WP-AppKit's post lists.
			 * 
			 * - "Standard pagination": corresponds to the standard use of "paged" param in WP_Query.
			 *   Return true as a result of this 'use-standard-pagination' filter to activate it.
			 * 
			 * Those 2 pagination types are exclusive: you can't use both at the same time.
			 * If standard pagination is set, infinite scroll pagination is ignored.
			 * 
			 * @param use_standard_pagination   {boolean}     Set this to true to activate standard pagination (default false)
			 * @param current_component_id      {string}      String identifier for the component the "get more" is called on
			 * @param current_screen            {JSON Object} Current screen on which "get more" is called
			 */
			use_standard_pagination = Hooks.applyFilters( 'use-standard-pagination', use_standard_pagination, [ current_component_id, current_screen ] );
		  
			var component = app.components.get( component_id );
			if ( component ) {

				var component_data = component.get( 'data' );

				if ( component_data.hasOwnProperty( 'ids' ) ) {

					var token = WsToken.getWebServiceUrlToken( 'component' );
					var ws_url = token + '/component/' + component_id;

					var last_item_id = _.last( component_data.ids );
					
					var web_service_params = {};
					
					if ( use_standard_pagination ) {
						var current_pagination_page = component_data.query.hasOwnProperty( 'pagination_page' ) && component_data.query.pagination_page > 0 ? 
													  parseInt( component_data.query.pagination_page ) : 1;
						web_service_params.pagination_page = current_pagination_page + 1;
					} else {
						web_service_params.before_item = last_item_id;
					}

					/**
					* Filter 'web-service-params' : use this to send custom key/value formated
					* data along with the web service. Those params are passed to the server
					* (via $_GET) when calling the web service.
					*
					* Filtered data : web_service_params : JSON object where you can add your custom web service params
					* Filter arguments :
					* - web_service_name : string : name of the current web service ('get-more-of-component' here).
					*/
					var web_service_params = Hooks.applyFilters( 'web-service-params', web_service_params, ['get-more-of-component'] );

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
									
									component_data.query.pagination_page = answer.component.data.query.pagination_page;

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

									app.triggerInfo( 'component:get-more', { new_items: new_items, is_last: is_last, nb_left: nb_left, new_ids: new_ids, global: global, component: component }, cb_ok );
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
	 * - "replace" : delete current component data, empty the corresponding global, and replace with new
	 * - "replace-keep-global-items" (default) : for list components : replace component items ids and merge global items
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

			var existing_component_data = existing_component.get( 'data' );
			var new_component_data = new_component.data;
			
			if ( new_component_data.hasOwnProperty( 'ids' ) ) { //List component

				if( new_component.global ) {

					var global = new_component.global;
					if ( !app.globals.hasOwnProperty( global ) ) {
						var items = new Items.Items( [], { global: global } );
						app.globals[global] = items;
					}

					var new_ids = [ ];
					if ( type === "replace" || type === "replace-keep-global-items" ) {
						new_ids = new_component_data.ids;
					} else {
						new_ids = _.difference( new_component_data.ids, existing_component_data.ids );
						new_component_data.ids = _.union( existing_component_data.ids, new_component_data.ids ); //merge ids
					}

					existing_component.set( 'data', new_component_data );

					var current_items = app.globals[global];
					if ( type === "replace" ) {
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

		//interpretation_type defaults to 'replace-keep-global-items' :
		var interpretation_type = options.hasOwnProperty('type') ? options.type : 'replace-keep-global-items';

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

					//See if components data were returned ("get-component" action): 
					//if so, update the corresponding component(s) :
					var new_components = {};
					if ( answer.components ) {
						new_components = answer.components;
					} else if( answer.component ) {
						new_components[answer.component.slug] = answer.component;
					}

					if( !_.isEmpty( new_components ) ) {

						//"get-component" case (as opposed to "get-items").

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

						//"get-items" case (as opposed to "get-component").
						//> No component returned, but some global items :
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
	
	  app.getPageComponentByPageId = function( page_id, default_to_first_component ) {
			var page_component = _.find( this.getComponents(), function( component ){
				return component.type === 'page' && component.global === 'pages' && component.data.root_id === page_id;
			} );
			
			if ( !page_component && default_to_first_component === true ) {
				page_component = this.findFirstComponentOfType( 'page' );
			}
			
			return page_component;
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
						  //No component.data, which should not happen, unless something went wrong on server side
						  //when building the component data. This can happen for example when a custom component 
						  //is added to an app without providing the correct hook name or not setting correct data 
						  //in hook.
    		  			  app.triggerError(
  			    			  'getcomponentdata:hooks:no-data',
  			    			  {type:'wrong-data',where:'app::getComponentData',message: 'Custom component ['+ component_id +'] has no data attribute: please check that the component\'s hook is set correctly.',data:{component:component}}
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
	  };

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
		 * Retrieve items (posts/pages etc) from remote server and merge them into existing app's items.
		 * 
		 * @param Array items array of ids of pages/posts to retrieve. 
		 * @param JSON Object options:
		 *  - component_id:   Int (optional) Slug of the component we want to retrieve items for.
		 *                    If not provided, the first component of "component_type" found
		 *                    will be used.
		 *  - component_type: String (optional) Type of component ("posts-list", "pages") we want to
		 *                    retrieve items for. Only useful if component_id is not provided.
		 *                    If not provided, defaults to "posts-list".
		 *  - persistent:     Boolean (optional) Whether to persist retrieved items to local storage.
		 *                    Defaults to true.
		 *  - success:        Callback (optional) Called if items are retrieved successfully
		 *  - error:          Callback (optional) Called if an error occured while retrieving items from server.
		 *                    App error events are also triggered in that case.
		 */
		app.getItemsFromRemote = function ( items_ids, options ) {

			options = options || {};
			
			Utils.log('Retrieving items from remote server.', items_ids);
			
			//Posts/pages/items can only be retrieved by component, as their content is formatted
			//according to the component type they belong to.
			var component = null;
			if ( options.component_id ) {
				if ( this.components.get( options.component_id ) ) {
					component = this.components.get( options.component_id );
				} else {
					this.triggerError(
						'get-items:remote:wrong-component-given',
						{ type:'wrong-data', where:'app::getItemsFromRemote', message: 'Provided component not found ['+ options.component_id +']', data: { options: options, items_ids: items_ids } },
						options.error
					);
					return;
				}
			}
			
			if ( !component ) {
				var component_type = options.component_type ? options.component_type : 'posts-list';
				component = this.findFirstComponentOfType( component_type );
			}

			if ( component ) {

				var _this = this;
				
				var persistent = !options.persistent || options.persistent === true;

				//Call liveQuery to retrieve the given items from server and store them in local storage:
				this.liveQuery(
					{
						wpak_component_slug: component.id,
						wpak_query_action: 'get-items',
						wpak_items_ids: items_ids
					},
					function( answer, results ){

						var items_found = _.find( results, function( result ) {
							return result.data.new_items.length > 0;
						} );

						if ( items_found ) {
							Utils.log('Items retrieved successfully from remote server.', items_ids, results);
							if ( options.success ) {
								options.success( answer, component, results );
							}
						} else {
							//Requested posts where not found. Trigger error
							if ( options.error ) {
								_this.triggerError(
									'get-items:remote:no-item-found',
									{ type:'not-found', where:'app::getItemsFromRemote', message: 'Requested items not found', data: { options: options, items_ids: items_ids } },
									options.error
								);
							}
						}

					},
					function( error ){
						//liveQuery error: error event has been triggered in liveQuery,
						//simply call the error callback here:
						if ( options.error ) {
							options.error( error );
						}
					},
					{
						type: 'update',
						persistent: persistent
					}
				);

			} else {
				app.triggerError(
					'get-items:remote:wrong-component',
					{ type:'wrong-data', where:'app::getItemsFromRemote', message: 'Could not find a valid component', data: { options: options, items_ids: items_ids } },
					options.error
				);
			}
		};
		
		app.findFirstComponentOfType = function( component_type ) {
			return _.findWhere( this.getComponents(), { type: component_type } )
		};
		
		app.loadRouteItemFromRemote = function( item_id, item_global, component_type, options ){
			var load_from_remote =  Hooks.applyFilters('load-unfound-items-from-remote', true, [item_id,item_global]);
			if ( load_from_remote ) {

				/**
				 * Use 'load-unfound-items-component-id' and 'load-unfound-items-component-type' to customize
				 * which component is used to retrieve the item from remote. 
				 * Default is the first "posts-list" component found.
				 */
				var item_component_id = Hooks.applyFilters('load-unfound-items-component-id', '', [item_id,item_global]);
				var item_component_type = Hooks.applyFilters('load-unfound-items-component-type', component_type, [item_id,item_global]);

				this.triggerInfo( 'load-item-from-remote:start', { 
					item_id: item_id, item_global: item_global, item_component_id: item_component_id, item_component_type: item_component_type 
				} );
				
				var global = this.globals[item_global];
				
				var _this = this;

				this.getItemsFromRemote( [item_id], {
					component_id: item_component_id,
					component_type: item_component_type,
					success: function( answer, component, results ) {
						var item = global.get(item_id);

						_this.triggerInfo( 'load-item-from-remote:stop', { 
							item_id: item_id, item_global: item_global, item: item, 
							item_component_id: item_component_id, item_component_type: item_component_type,
							success: !!item
						} );

						if ( item ) {

							//Success!
							if ( options.success ) {
								options.success( item, component );
							}

						} else {
							Utils.log('loadRouteItemFromRemote : unexpected error "'+ item_id +'" not found in global "'+ item_global +'" even after remote call.');

							_this.triggerError(
								'get-items:remote:item-not-found-in-global',
								{ type:'not-found', where:'app::loadRouteItemFromRemote', message: 'Requested items not found', data: { 
									item_id: item_id, item_global: item_global, item: item, 
									item_component_id: item_component_id, item_component_type: item_component_type
								} }
							);
					
							if ( options.error ) {
								options.error();
							}
						}
					},
					error: function() {

						_this.triggerInfo( 'load-item-from-remote:stop', { 
							item_id: item_id, item_global: item_global, 
							item_component_id: item_component_id, item_component_type: item_component_type,
							success: false
						} );

						if ( options.error ) {
							options.error();
						}
					}
					
				} );

			} else {
				if ( options.error ) {
					options.error();
				}
			}
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
       *  - register "resume" event
       *  - set options
	   *  - initialize addons
       */
      app.initialize = function ( callback ) {

        //Activate pretty slugs via html5 pushstate for PWA:
        //(this can be deactivated in theme by setting the param
        //'use-html5-pushstate' to false)
        if( Config.app_platform === 'pwa' && Config.app_type !== 'preview' ) {
            app.setParam( 'use-html5-pushstate', true );
            Utils.log( 'HTML5 pushstate mode activated.' );
        }

      	document.addEventListener( 'resume', app.onResume, false );

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

      /**
       * Fires when the application was in background and is called to be in foreground again.
       * Handles:
       *  - deep links
       */
      app.onResume = function() {
      	// If there is a defined launch URL, use it
      	var route = DeepLink.getLaunchRoute();

      	route = Hooks.applyFilters( 'resume-route', route, [Stats.getStats()] );

      	if( route.length ) {
      		app.router.navigate( route, { trigger: true } );
      	}
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
