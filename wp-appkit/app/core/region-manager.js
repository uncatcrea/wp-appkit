define(function (require) {

	"use strict";

	var $                   = require('jquery'),
		_                   = require('underscore'),
		Backbone            = require('backbone'),
		App            		= require('core/app'),
		Hooks               = require('core/lib/hooks'),
		Utils               = require('core/app-utils');

	Backbone.View.prototype.close = function(){

		//We also have to remove Views Models and Collections events by hand + handle closing subviews :
		// > use onClose for this.
		if( this.onClose ){
			this.onClose();
		}

		this.unbind(); // this will unbind all listeners to events from this view. This is probably not necessary because this view will be garbage collected.
		this.remove(); // uses the default Backbone.View.remove() method which removes this.el from the DOM and removes DOM events.
	};

	var RegionManager = (function (Backbone, $, _) {

		var headView = null;

		var layoutView = null;
		var elLayout = "#app-layout";

		var headerView = null;
		var elHeader = "#app-header";

		var currentView = null;
	    var elContent = "#app-content-wrapper";

	    var elMenu = "#app-menu";
	    var menuView= null;

	    var region = {};

	    var vent = _.extend({}, Backbone.Events);
	    region.on = function(event,callback){
	    	vent.on(event,callback);
	    };
	    region.off = function(event,callback){
	    	vent.off(event,callback);
	    };

        /**
        * Intercept internal navigation in the app to trigger Backbone router navigation
        * instead of browser page refresh. Mandatory for pretty slugs with HTML5 pushstate.
        */
        region.handleNavigationInterception = function() {
            if ( App.getParam( 'use-html5-pushstate' ) ) {
                var auto_prevent_page_reload = true;
                
                /**
                 * When using HTML5 pushstate pretty urls, clicking on an internal link in the app
                 * leads to the browser refreshing the page. To avoid that we have to 
                 * intercept clicks on links and do a Backbone router.navigate() instead
                 * of letting the browser handling the navigation. By default we do that
                 * by intercepting all click events on body element.
                 * To handle this in your own way directly in theme, use this 'auto-prevent-page-reload'
                 * filter and return false.
                 */
                auto_prevent_page_reload = Hooks.applyFilters( 'auto-prevent-page-reload', auto_prevent_page_reload, [] );
                
                if ( auto_prevent_page_reload ) {
                    $( 'body' ).on( 'click', 'a', preventPageReloadForInternalLinks );
                }
            }
        };
        
        /**
         * When clicking on internal links, cancel click event and do a manual
         * Backbone router navigation. This is to avoid the browser refreshing
         * the page when clicking internal links and using HTML5 pushstate.
         */
        var preventPageReloadForInternalLinks = function( e ) {
			var $link_el = $( e.currentTarget );
            
			var href = $link_el.attr( 'href' ).trim();

			if ( Utils.isInternalUrl( href ) && href !== '#' ) {
				e.preventDefault();
                var route = Utils.extractRouteFromUrlPath( href );
				App.router.navigate( route, { trigger: true } );
			}

		};

	    region.buildHead = function(cb){
	    	if( headView === null ){
	    		require(['core/views/head'],function(HeadView){
	    			headView = new HeadView();
					headView.render();
					cb();
	    		});
	    	}else{
	    		cb();
	    	}
	    };

	    region.buildLayout = function(cb){
	    	if( layoutView === null ){
	    		require(['core/views/layout'],function(LayoutView){
		    		layoutView = new LayoutView({el:elLayout});
		    		//Layout may already have been pre-rendered before export (for PWAs).
		    		//If not, render it:
		    		if ( !layoutView.isRendered() ) {
		    			layoutView.render();
		    		}
		    		cb();
	    		});
	    	}else{
	    		cb();
	    	}
	    };

	    region.buildHeader = function(cb){
	    	if( layoutView.containsHeader() ){
		    	if( headerView === null ){
		    		require(['core/views/header'],
		    				function(HeaderView){
					    		headerView = new HeaderView({
					    			el:elHeader,
					    			do_if_template_exists:function(view){
						    			if( layoutView.containsHeader() ){
						    				view.render();
						    			}
					    				cb();
						    		},
						    		do_if_no_template:function(){
						    			cb();
						    		}
						    	});
		    				}
		    		);
		    	}else{
		    		cb();
		    	}
	    	}else{
	    		cb();
	    	}
	    };

	    region.buildMenu = function(cb,force_reload){

	    	force_reload = (force_reload!=undefined && force_reload);

	    	if( menuView === null || force_reload ){
	    		require(['core/views/menu'],function(MenuView){
	    			var menu_el = $(elMenu).length ? {el:elMenu} : {};
		    		menuView = new MenuView(menu_el);
	    			menuView.resetAll();

					var menu_items = [];

		    		App.navigation.each(function(element, index){
		    			var component = App.components.get(element.get('component_id'));
		    			if( component ){
							menu_items.push( { 
								id:component.get('id'),
								label:component.get('label'),
								type:component.get('type'),
								link: App.getScreenFragment( 'component', { component_id: component.get('id') } ),
								options:element.get('options')
							} );
		    			}
		   		  	});

					/**
					 * Use this "menu-items" filter to add or remove menu items.
					 * Menu item format :
					 * {
					 *	 id:slug, //unique slug identifier for the menu item
					 *	 label:label, //displayed menu item label
					 *	 type:type, //page, posts-list, favorites etc or custom
					 *	 link:fragment, //screen fragment
					 *	 options:options //Optional : json object to pass additionnal data to the menu item
					 * }
					 */
					menu_items = Hooks.applyFilters('menu-items', menu_items, [App.navigation]);

					_.each(menu_items,function( menu_item ){
						var options = menu_item.options ? menu_item.options : {};
						menuView.addItem( menu_item.id, menu_item.type, menu_item.label, menu_item.link, options );
					});

					showMenu(force_reload);
		    		cb();
	    		});
	    	}else{
	    		cb();
	    	}
	    };

	    var showMenu = function(force_reload){
	    	if( menuView ){
	    		if( $(elMenu).length
	    			&& (!$(elMenu).html().length || (force_reload!=undefined && force_reload) ) ){
		    		menuView.render();

					/**
					 * Use this 'menu:rendered' event to bind JS events on menu's DOM once
					 * it is rendered. Useful for example when using JS lib that don't use
					 * event delegation.
					 */
		    		vent.trigger( 'menu:rendered', App.getCurrentScreenData(), menuView );

					Utils.log('Render menu',{menu_view:menuView,force_reload:force_reload});
	    		}
	    	}else{
	    		if( $(elMenu).html().length ){
	    			$(elMenu).empty();
	    		}
	    	}
	    };

	    region.getMenuView = function(){
	    	return menuView;
	    };

	    var renderSubRegions = function(){
	    	if( headerView && headerView.templateExists() && layoutView.containsHeader() ){
		    	headerView.render();
		    	Utils.log('Render header',{header_view:headerView});
		    	if( headerView.containsMenu() ){
		    		showMenu(true);
		    	}
			    vent.trigger('header:rendered',App.getCurrentScreenData(),headerView);
	    	}
	    };

	    var closeView = function (view) {
	        if( view ){
	        	if( view.is_static ){
					//Static views are memorized in screen_static_views now.
					//Backbone memorizes the DOM element, so we don't need to keep a copy of it!
	        		/*var static_screens_wrapper = $('#app-static-screens');
	        		if( !static_screens_wrapper.find('[data-viewid='+ view.cid +']').length ){
	        			$(view.el).attr('data-viewid',currentView.cid);
	        			static_screens_wrapper.append(view.el);
	        		}*/
	        	}else{
		        	if( view.close ){
		        		view.close();
		        	}
	        	}
	        }
	    };

	    var openView = function (view) {
	    	var first_static_opening = false;

			var custom_rendering = App.getParam('custom-screen-rendering');

			if( !view.is_static || !$(view.el).html().length ){
	    		if( view.is_static != undefined && view.is_static ){
					first_static_opening = true;
	    			Utils.log('Open static view',{screen_data:App.getCurrentScreenData(),view:view});
	    		}else{
	    			Utils.log('Open view',{screen_data:App.getCurrentScreenData(),view:view});
	    		}
				view.render();
			} else {
				Utils.log('Re-open existing static view',{view:view});
			}

			var $elContent = $(elContent);

			if( custom_rendering ){

				/**
				 * 'screen-transition' action: allows to implement your own screen transitions using JS/CSS.
				 *
				 * @param $wrapper:       jQuery Object corresponding to div#app-content-wrapper, which is the element wrapping $current and $next screens.
				 * @param $current:       jQuery Object corresponding to the screen (div.app-screen) that we're leaving, to display $next instead.
				 * @param $next:          jQuery Object corresponding to the new screen (div.app-screen) that we want to display (by replacing $current).
				 * @param current_screen: JSON Object: screen object containing information about the screen we're leaving.
				 * @param next_screen:    JSON Object: screen object containing information (screen type, screen item id, etc) about the new screen we want to display (see getCurrentScreen() for details about screen objects) .
				 * @param $deferred:      jQuery deferred object that must be resolved at the end of the transition animation to tell app core that the new screen has finished rendering.
				 */
				Hooks.doActions(
					'screen-transition',
					[$elContent,$('div:first-child',$elContent),$(view.el),App.getPreviousScreenMemoryData(),App.getCurrentScreenData()]
				).done(function(){
					 renderSubRegions();
					 vent.trigger('screen:showed',App.getCurrentScreenData(),currentView,first_static_opening);
				}).fail(function(){
					//Note : Hooks.doActions doesn't handle a fail case for now,
					//but it may in the future!
					renderSubRegions();
					vent.trigger('screen:showed:failed',App.getCurrentScreenData(),currentView,first_static_opening);
				});

			}else{
				$elContent.empty().append(view.el);
				renderSubRegions();
				vent.trigger('screen:showed',App.getCurrentScreenData(),currentView,first_static_opening);
			}

			if(view.onShow) {
				view.onShow();
			}
	    };

	    var showView = function(view) {

	    	var custom_rendering = App.getParam('custom-screen-rendering');

	    	if( currentView ){
				if( !custom_rendering ){ //Custom rendering must handle views closing by itself (on screen:leave)
					closeView(currentView);
				}
	    	}

	    	currentView = view;
		    openView(currentView);
	    };

		var screen_static_views = { };

		var getScreenId = function( screen ) {
			return screen.fragment + '-' + String( screen.item_id );
		};

		var getScreenStaticView = function( screen ) {
			var screen_id = getScreenId( screen );
			return screen_static_views.hasOwnProperty( screen_id ) ? screen_static_views[screen_id] : null;
		};

		var memorizeScreenStaticView = function( screen, view ) {
			screen_static_views[getScreenId( screen )] = view;
		};

	    var switchScreen = function( view, force_flush ) {
			var queried_screen = App.getQueriedScreen();
			vent.trigger( 'screen:leave', App.getCurrentScreenData(), queried_screen, currentView );

			App.addQueriedScreenToHistory( force_flush );
			if ( view.is_static ) {
				memorizeScreenStaticView( queried_screen, view );
			}

			showView( view );
		};

		var createNewView = function( view_type, view_data, is_static, callback ) {

			var return_view = function( view ){
				view.is_static = is_static;
				callback( view );
			};

			switch ( view_type ) {
				case 'single':
					require( [ "core/views/single" ], function( SingleView ) {
						return_view( new SingleView( view_data ) );
					} );
					break;
				case 'page':
					require( [ "core/views/page" ], function( PageView ) {
						return_view( new PageView( view_data ) );
					} );
					break;
				case 'posts-list':
					require( [ "core/views/archive" ], function( ArchiveView ) {
						return_view( new ArchiveView( view_data ) );
					} );
					break;
				case 'hooks':
					require( [ "core/views/custom-component" ], function( CustomComponentView ) {
						return_view( new CustomComponentView( view_data ) );
					} );
					break;
				case 'comments':
					require( [ "core/views/comments" ], function( CommentsView ) {
						return_view( new CommentsView( view_data ) );
					} );
					break;
				case 'custom-page':
					require( [ "core/views/custom-page" ], function( CustomPageView ) {
						return_view( new CustomPageView( view_data ) );
					} );
					break;
				default:
					// Allow customized not native view type created by an addon
					var customView = Hooks.applyFilters( 'custom-view', "", [view_type] );
					if( customView.length ) {
						require( [ customView ], function( CustomViewObject ) {
							return_view( new CustomViewObject( view_data ) );
						});
					}
					break;
			}

		};

		region.show = function( view_type, view_data, screen_data ) {

			App.setQueriedScreen( screen_data );
			var queried_screen = App.getQueriedScreen();

			/**
			 * Use this 'redirect' filter to redirect to a different screen than the queried one,
			 * just before it is showed.
			 *
			 * To redirect to a different screen from the filter callback (in themes) :
			 * - call App.navigate('your_screen_route');
			 * - return true
			 *
			 * Implementation note : This has to be a filter (not action) so that we
			 * can stop the showing process if true is returned.
			 */
			var redirect = Hooks.applyFilters( 'redirect', false, [queried_screen, App.getCurrentScreenData()] );
			if( redirect ) {
				return;
			}

			/**
			 * Use this 'is-static-screen' filter to decide whether a screen
			 * is static or not. A static screen is never refreshed or re-rendered
			 * by the app core.
			 */
			var is_static = Hooks.applyFilters( 'is-static-screen', false, [ queried_screen ] );

			if ( is_static ) {
				var screen_view = getScreenStaticView( queried_screen );
				if ( screen_view !== null ) {
					screen_view.checkTemplate( function() {
						switchScreen( screen_view );
					} );
				} else {
					createNewView( view_type, view_data, is_static, function( newview ) {
						newview.checkTemplate( function() {
							switchScreen( newview );
						} );
					} );
				}
			} else {
				createNewView( view_type, view_data, is_static, function( newview ) {
					newview.checkTemplate( function() {
						switchScreen( newview );
					} );
				} );
			}
		};

	    region.getCurrentView = function(){
	    	return currentView;
	    };

	    return region;

	})(Backbone, $, _);

	return RegionManager;
});
