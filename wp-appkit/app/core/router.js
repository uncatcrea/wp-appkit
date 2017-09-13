define(function (require) {

    "use strict";

    var Backbone       = require('backbone'),
		_              = require('underscore'),
    	Utils          = require('core/app-utils'),
        RegionManager  = require("core/region-manager"),
		Hooks          = require('core/lib/hooks');

    var default_route = '';

	//Hack to avoid render a view after another route has been triggered
	//(when 2 routes are called in a very short delay),
	var route_asked = '';
	var check_route = function(route){
		var route_asked_trimed = route_asked.replace('#','');
		route = route.replace('#','');
		return route_asked_trimed == route;
	};

    return Backbone.Router.extend({

        routes: {
            "": "default_route",
            "single/:global/:id" : "single",
            "page/:component_id/:page_id" : "page",
            "comments-:post_id" : "comments",
            "component-:id" : "component",
            "custom-page" : "custom_page",
			'*notFound': "not_found"
        },

        setDefaultRoute: function(_default_route){
    		default_route = _default_route;
    	},

		getDefaultRoute: function(){
			return default_route;
		},

        default_route: function(){
        	this.navigate(default_route, {trigger: true});
        },

        component: function (component_id) {
        	var _this = this;
			route_asked = 'component-'+ component_id;
        	require(["core/app"],function(App){
        		var component = App.getComponentData(component_id);
        		if( component ) {
					if( check_route('component-'+ component_id) ){
						switch( component.type ){
							case 'posts-list':
								RegionManager.show(
									'posts-list',
									component.view_data,
									{screen_type:'list',component_id:component_id,item_id:0,global:component.global,data:component.data,label:component.label}
								);
                                break;
							case 'page':
								//Directly redirect to "page" route :
								//Note : when displaying a page component, it is better to directly use the 
								//page fragment (/page/:component_id/:page_id) rather than the component
								//fragment, because the following redirection makes the native browser's back button 
								//fail. To be sure to handle that correctly, just use App.getScreenFragment( 'component', ... )
								//that will build the correct fragment for you for any component type.
								_this.navigate( App.getScreenFragment( 'page', { component_id: component_id, item_id: component.data.root_id } ), {trigger: true} );
								break;
							case 'hooks-list':
							case 'hooks-no-global':
								RegionManager.show(
									'hooks',
									{component:component},
									{screen_type:'custom-component',component_id:component_id,item_id:0,global:component.global,data:component.data,label:component.label}
								);
								break;
							default:
								var screen_view_data = {
									view_type: '',
									view_data: {},
									screen_data: {}
								};
								screen_view_data = Hooks.applyFilters('component-custom-type',screen_view_data,[component]);
								if( screen_view_data.view_type !== '' ) {
									RegionManager.show(
										screen_view_data.view_type,
										screen_view_data.view_data,
										screen_view_data.screen_data
									);
								}
								break;
						}
					}
        		}else{
        			App.router.default_route();
        		}

        	});
        },

        /**
         * The post must be in the "posts" global to be accessed via this "single" route.
         */
        single: function (item_global,item_id) {
			route_asked = 'single/'+ item_global +'/'+ item_id;

			var _this = this;
			
			var show_single = function( item ) {
				var item_json = item.toJSON();
				var item_data = item_global == 'posts' ? {post:item_json} : {item:item_json};

				if( check_route('single/'+ item_global +'/'+ item_id) ){
					RegionManager.show(
						'single',
						{item:item,global:item_global},
						{screen_type:'single',component_id:'',item_id:parseInt(item_id),global:item_global,data:item_data,label:item_json.title}
					);
				}
			}

        	require(["core/app"],function(App){
	        	var global = App.globals[item_global];
	        	if( global ){
		        	var item = global.get(item_id);
		        	if( item ){
						
						show_single( item );
						
		        	}else{
						
		        		Utils.log('Router single route : item with id "'+ item_id +'" not found in global "'+ item_global +'".');
	        			
						var load_from_remote =  Hooks.applyFilters('load-unfound-items-from-remote', true, [item_id,item_global]);
						
						if ( load_from_remote ) {
							
							/**
							 * Use 'load-unfound-items-component-id' and 'load-unfound-items-component-type' to customize
							 * which component is used to retrieve the item from remote. 
							 * Default is the first "posts-list" component found.
							 */
							var item_component_id = Hooks.applyFilters('load-unfound-items-component-id', '', [item_id,item_global]);
							var item_component_type = Hooks.applyFilters('load-unfound-items-component-type', 'posts-list', [item_id,item_global]);
							
							App.triggerInfo( 'load-item-from-remote:start', { 
								item_id: item_id, item_global: item_global, item_component_id: item_component_id, item_component_type: item_component_type 
							} );
							
							App.getItemsFromRemote( [item_id], {
								item_component_id: item_component_id,
								item_component_type: item_component_type,
								success: function() {
									var item = global.get(item_id);
									
									App.triggerInfo( 'load-item-from-remote:stop', { 
										item_id: item_id, item_global: item_global, item: item, 
										item_component_id: item_component_id, item_component_type: item_component_type,
										success: !!item,
									} );
									
									if ( item ) {
										
										//Success! display single screen:
										show_single( item );
										
									} else {
										Utils.log('Router single route : unexpected error "'+ item_id +'" not found in global "'+ item_global +'" even after remote call.');
										
										App.triggerError(
											'get-items:remote:item-not-found-in-global',
											{ type:'not-found', where:'router::single', message: 'Requested items not found', data: { 
												item_id: item_id, item_global: item_global, item: item, 
												item_component_id: item_component_id, item_component_type: item_component_type
											} },
											options.error
										);
								
										App.router.default_route();
									}
								},
								error: function() {
									
									App.triggerInfo( 'load-item-from-remote:stop', { 
										item_id: item_id, item_global: item_global, 
										item_component_id: item_component_id, item_component_type: item_component_type,
										success: false,
									} );
									
									App.router.default_route();
								}
							} );
							
						} else {
							App.router.default_route();
						}
	        		}
	        	}else{
	        		Utils.log('Error : router single route : global "'+ item_global +'" not found.');
	    			App.router.default_route();
	    		}
        	});
        },

        page: function (component_id,page_id) {
			route_asked = 'page/'+ component_id +'/'+ page_id;

        	require(["core/app"],function(App){
        		var item_global = 'pages';
	        	var global = App.globals[item_global];
	        	if( global ){
		        	var item = global.get(page_id);
		        	if( item ){
		        		var component = App.getComponentData(component_id);
		        		if( component ){

		        			var item_data = {
			        			post:item.toJSON(),
			        			is_tree_page:component.data.is_tree,
			        			is_tree_root:(page_id == component.data.root_id),
			        			root_id:component.data.root_id,
			        			root_depth:component.data.root_depth
			        		};

							if( check_route('page/'+ component_id +'/'+ page_id) ){
								RegionManager.show(
									'page',
									{item:item,global:item_global},
									{screen_type:'page',component_id:component_id,item_id:parseInt(page_id),global:item_global,data:item_data,label:item_data.post.title}
								);
							}

		        		}else{
			        		Utils.log('Error : router : page route : component with id "'+ component_id +'" not found');
		        			App.router.default_route();
		        		}
		        	}else{
		        		Utils.log('Error : router : page route : item with id "'+ page_id +'" not found in global "'+ item_global +'".');
	        			App.router.default_route();
	        		}
	        	}else{
	        		Utils.log('Error : router : screen route : global "'+ item_global +'" not found.');
	    			App.router.default_route();
	    		}
        	});
        },

        comments: function ( post_id ) {
			route_asked = 'comments-' + post_id;

			require( ["core/app"], function ( App ) {

				function showCommentsScreen( comments, post, item_global ) {
					if ( check_route( 'comments-' + post.id ) ) {
						RegionManager.show(
							'comments',
							{ comments: comments, post: post },
							{ screen_type: 'comments', component_id: '', item_id: parseInt( post.id ), data: { item_global: item_global } }
						);
					}
				}

				/**
				 * If ThemeApp.displayPostComments() was used to display the comments screen (recommended),
				 * post comments should already be in app's memory.
				 * If not, we fetch it now.
				 */
				var post_comments_memory = App.comments.get( post_id );
				if ( post_comments_memory ) {
					
					showCommentsScreen(
						post_comments_memory.get( 'post_comments' ),
						post_comments_memory.get( 'post' ),
						post_comments_memory.get( 'item_global' )
					);
					
				} else {
					
					App.getPostComments(
						post_id,
						function ( comments, post, item_global ) {
							showCommentsScreen( comments, post, item_global );
						},
						function ( error ) {
							Utils.log( 'router.js error : App.getPostComments failed', error );
						}
					);
					
				}
				
			} );
		},

        custom_page: function(){
			route_asked = 'custom-page';
        	require(["core/app"],function(App){
        		var current_custom_page = App.getCurrentCustomPage();
        		if( current_custom_page !== null ){
					if( check_route('custom-page') ){
						RegionManager.show(
							'custom-page',
							{custom_page:current_custom_page},
							{screen_type:'custom-page',component_id:'',item_id:current_custom_page.get('id'),data:{custom_page:current_custom_page}}
						);
					}
        		}
        	});
        },

		not_found: function(fragment){
			var _this = this;
			require(["core/app"],function(App){
				var fragment_not_found = Hooks.applyFilters('fragment-not-found', '#', [fragment]);

				var custom_route = App.getCustomRoute(fragment);
				if( !_.isEmpty(custom_route) ){
					fragment_not_found = '';
					App.showCustomPage(custom_route.template,custom_route.data,fragment,true);
				}

				if( fragment_not_found.length ){
					_this.navigate(fragment_not_found, {trigger: true});
				}
			});

        },
		
		/**
		 * Execute router's method corresponding to the given route without 
		 * changing current url or fragment.
		 * Used for custom routes.
		 */
		execute_route_silently: function ( route ) {

			var fragment = Backbone.history.getFragment( route );
			var route_handler = _.find( Backbone.history.handlers, function ( handler ) {
				return handler.route.test( fragment );
			} );

			if ( route_handler !== undefined ) {
				this.execute( route_handler.callback, [ fragment ], '' );
			} else {
				Utils.log( 'Router.js error: execute_route_silently: route not found.' );
			}

		}


    });

});