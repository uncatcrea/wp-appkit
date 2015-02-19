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
                            case 'favorites':
								RegionManager.show(
									'favorites',
									component.view_data,
									{screen_type:'list',component_id:component_id,item_id:0,global:component.global,data:component.data,label:component.label}
								);
								break;
							case 'page':
								//Directly redirect to "page" route :
								_this.navigate('page/'+ component_id +'/'+ component.data.root_id, {trigger: true});
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

        	require(["core/app"],function(App){
	        	var global = App.globals[item_global];
	        	if( global ){
		        	var item = global.get(item_id);
		        	if( item ){
						var item_json = item.toJSON();
		        		var item_data = item_global == 'posts' ? {post:item_json} : {item:item_json};

						if( check_route('single/'+ item_global +'/'+ item_id) ){
							RegionManager.show(
								'single',
								{item:item,global:item_global},
								{screen_type:'single',component_id:'',item_id:parseInt(item_id),global:item_global,data:item_data,label:item_json.title}
							);
						}

		        	}else{
		        		Utils.log('Error : router single route : item with id "'+ item_id +'" not found in global "'+ item_global +'".');
	        			App.router.default_route();
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
			        			item:item.toJSON(),
			        			is_tree_page:component.data.is_tree,
			        			is_tree_root:(page_id == component.data.root_id),
			        			root_id:component.data.root_id,
			        			root_depth:component.data.root_depth
			        		};

							if( check_route('page/'+ component_id +'/'+ page_id) ){
								RegionManager.show(
									'page',
									{item:item,global:item_global},
									{screen_type:'page',component_id:component_id,item_id:parseInt(page_id),global:item_global,data:item_data,label:item_data.item.title}
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

        comments: function (post_id) {
        	require(["core/app"],function(App){
        		//App.setQueriedScreen({screen_type:'comments',component_id:'',item_id:parseInt(post_id)}); //done in RegionManager.show() now
        		RegionManager.startWaiting();
	        	App.getPostComments(
	        		post_id,
	        		function(comments,post){
	        			RegionManager.stopWaiting();
	        			//Check if we are still on the right post :
	        			var current_screen = App.getCurrentScreenData();
	        			if( current_screen.screen_type == 'single' && current_screen.item_id == post_id ){
							RegionManager.show(
								'comments',
								{comments:comments,post:post},
								{screen_type:'comments',component_id:'',item_id:parseInt(post_id)}
							);
	        			}
		        	},
		        	function(error){
		        		Utils.log('router.js error : App.getPostComments failed',error);
		        		RegionManager.stopWaiting();
		        	}
		        );
        	});
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

        favorite: function( action, item_global, id ) {
            require( ["core/app", "core/theme-app"], function( App, ThemeApp ) {
                switch( action ) {
                    case 'add':
                        ThemeApp.addToFavorites( item_global, id );
                        break;
                    case 'remove':
                        ThemeApp.removeFromFavorites( item_global, id );
                        break;
                }
                App.favorites.saveAll();

                var screen = App.getCurrentScreenData();

                if( undefined !== screen.fragment ) {
                    Backbone.history.loadUrl( screen.fragment );
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
					App.showCustomPage(custom_route.template,custom_route.data,fragment);
				}

				if( fragment_not_found.length ){
					_this.navigate(fragment_not_found, {trigger: true});
				}
			});
			
        }

    });

});