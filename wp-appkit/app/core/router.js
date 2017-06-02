define(function (require, exports) {

    "use strict";

    var Backbone       = require('backbone'),
		_              = require('underscore'),
    	Utils          = require('core/app-utils'),
        RegionManager  = require("core/region-manager"),
		Hooks          = require('core/lib/hooks'),
        App            = require('core/app');

    var default_route = '';

	//Hack to avoid render a view after another route has been triggered
	//(when 2 routes are called in a very short delay),
	var route_asked = '';
	var check_route = function(route){
		var route_asked_trimed = route_asked.replace('#','');
		route = route.replace('#','');
		return route_asked_trimed == route;
	};

    /**
     * Get route type from Backbone history handler's regexp.
     * Used to find the route type corresponding to a given fragment,
     * when inserting a screen manually in app history (see App.silentlyAddRouteToAppHistory()). 
     * Backbone history regexps corresponding to our routes can be found in Backbone.history.handlers.
     * 
     * Route types can be 'default_route', 'single', 'page', 'comments', 'component', 'custom-page', 'not_found'.
     */
    var get_route_type_from_regexp = function( route_regexp ) {
        var route_type = 'not_found';
        if ( route_regexp === '/^(?:\?([\s\S]*))?$/' ) {
            route_type = 'default_route';
        } else if ( route_regexp.indexOf( '/^single' ) === 0 ) {
            route_type = 'single';
        } else if ( route_regexp.indexOf( '/^page' ) === 0 ) {
            route_type = 'page';
        } else if ( route_regexp.indexOf( '/^comments' ) === 0 ) {
            route_type = 'comments';
        } else if ( route_regexp.indexOf( '/^component' ) === 0 ) {
            route_type = 'component';
        } else if ( route_regexp.indexOf( '/^custom\-page' ) === 0 ) {
            route_type = 'custom-page';
        } else if ( route_regexp === '/^([^?]*?)(?:\?([\s\S]*))?$/' ) {
            route_type = 'not_found'
        }
        return route_type;
    };

    return Backbone.Router.extend({

        routes: {
            "": "default_route",
            
            //Screen routes:
            "single/:global/:id" : "single",
            "page/:component_id/:page_id" : "page",
            "comments-:post_id" : "comments",
            "component-:id" : "component",
            "custom-page" : "custom_page",
            
            //Same screen routes with trailing slashes (used when HTML5 pushstate is activated):
            "single/:global/:id/" : "single",
            "page/:component_id/:page_id/" : "page",
            "comments-:post_id/" : "comments",
            "component-:id/" : "component",
            "custom-page/" : "custom_page",
            
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
        
        component: function ( component_id ) {
        	var _this = this;
			route_asked = 'component-'+ component_id;
            
            var route_data = this.getRouteData( 'component', { component_id: component_id } );
            
            if ( route_data.error.length === 0 ) {
                
                if ( check_route( 'component-'+ component_id ) ) {
                    
                    switch( route_data.screen_data.component_type ) {
                        case 'posts-list':
                            RegionManager.show(
                                route_data.view_type,
                                route_data.view_data,
                                route_data.screen_data
                            );
                            break;
                        case 'page':
                            //Directly redirect to "page" route :
                            //Note : when displaying a page component, it is better to directly use the 
                            //page fragment (/page/:component_id/:page_id) rather than the component
                            //fragment, because the following redirection makes the native browser's back button 
                            //fail. To be sure to handle that correctly, just use App.getScreenFragment( 'component', ... )
                            //that will build the correct fragment for you for any component type.
                            _this.navigate( App.getScreenFragment( 'page', { component_id: component_id, item_id: route_data.screen_data.data.root_id } ), {trigger: true} );
                            break;
                        case 'hooks-list':
                        case 'hooks-no-global':
                            RegionManager.show(
                                route_data.view_type,
                                route_data.view_data,
                                route_data.screen_data
                            );
                            break;
                        default:
                            if( route_data.view_type !== '' ) {
                                RegionManager.show(
                                    route_data.view_type,
                                    route_data.view_data,
                                    route_data.screen_data
                                );
                            }
                            break;
                    }
                }
                
            } else {
                Utils.log( route_data.error );
                App.router.default_route();
            }
            
        },

        /**
         * The post must be in the "posts" global to be accessed via this "single" route.
         */
        single: function ( item_global, item_id ) {
			route_asked = 'single/'+ item_global +'/'+ item_id;
            
            var route_data = this.getRouteData( 'single', { item_global: item_global, item_id: item_id } );
            
            if ( route_data.error.length === 0 ) {
                
                if ( check_route( 'single/'+ item_global +'/'+ item_id ) ) {
                    RegionManager.show(
                        route_data.view_type,
                        route_data.view_data,
                        route_data.screen_data
                    );
                }

            } else {
                Utils.log( route_data.error );
                App.router.default_route();
            }
        },

        page: function (component_id,page_id) {
			route_asked = 'page/'+ component_id +'/'+ page_id;

            var route_data = this.getRouteData( 'page', { component_id: component_id, page_id: page_id } );
            
            if ( route_data.error.length === 0 ) {
                
                if ( check_route( 'page/'+ component_id +'/'+ page_id ) ) {
                    RegionManager.show(
                        route_data.view_type,
                        route_data.view_data,
                        route_data.screen_data
                    );
                }
                
            } else {
                Utils.log( route_data.error );
                App.router.default_route();
            }
        },

        comments: function ( post_id ) {
			route_asked = 'comments-' + post_id;

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
		},

        custom_page: function(){
			route_asked = 'custom-page';
            
            var route_data = this.getRouteData( 'custom-page', {} );
            
            if ( route_data.error.length === 0 ) {
                
                if ( check_route( 'custom-page' ) ) {
                    RegionManager.show(
                        route_data.view_type,
                        route_data.view_data,
                        route_data.screen_data
                    );
                }
                
            } else {
                Utils.log( route_data.error );
                App.router.default_route();
            }
        },

		not_found: function(fragment){
			var _this = this;
            var fragment_not_found = Hooks.applyFilters('fragment-not-found', '#', [fragment]);

            var custom_route = App.getCustomRoute(fragment);
            if( !_.isEmpty(custom_route) ){
                fragment_not_found = '';
                App.showCustomPage(custom_route.template,custom_route.data,fragment);
            }

            if( fragment_not_found.length ){
                _this.navigate(fragment_not_found, {trigger: true});
            }
        },
        
        /**
         * Retrieves the type of the giver route and extracts route parameters.
         * Used to silently add screens to app history without really switching screen.
         * See App.silentlyAddRouteToAppHistory().
         * 
         * @return Returns { type: string, parameters: JSON Object } or null if the route is wrong.
         * Route types can be 'default_route', 'single', 'page', 'comments', 'component', 'custom-page', 'not_found'.
         */
        getRouteTypeAndParameters: function( route ) {
            var route_type_and_parameters = null;
            
            var fragment = Backbone.history.getFragment( route );
            
            var route_handler = _.find( Backbone.history.handlers, function( handler ) {
                return handler.route.test( fragment );
            });
            
            if ( route_handler ) {
                
                route_type_and_parameters = {
                    type: get_route_type_from_regexp( route_handler.route.toString() ),
                    parameters: {}
                };
                
                var route_params = this._extractParameters( route_handler.route, fragment );
                
                switch( route_type_and_parameters.type ) {
                    case 'default_route':
                        route_type_and_parameters.parameters = {};
                        break;
                    case 'single':
                        route_type_and_parameters.parameters = { item_global: route_params[0], item_id: route_params[1] };
                        break;
                    case 'page':
                        route_type_and_parameters.parameters = { component_id: route_params[0], page_id: route_params[1] };
                        break;
                    case 'comments':
                        route_type_and_parameters.parameters = { post_id: route_params[0] };
                        break;
                    case 'component':
                        route_type_and_parameters.parameters = { component_id: route_params[0] };
                        break;
                    case 'custom-page':
                        route_type_and_parameters.parameters = {};
                        break;
                    case 'not_found':
                        route_type_and_parameters.parameters = { fragment: route_params[0] };
                        break;
                }
            }
            
            return route_type_and_parameters;
        },
        
        /**
         * Retrieve screen data corresponding to given route type and parameters.
         * This is the data that is passed to RegionManager.show() to render the view.
         * Also used when we add screen manually with App.silentlyAddRouteToAppHistory().
         */
        getRouteData: function( route_type, route_params ) {
            var route_data = { view_type: '', view_data: {}, screen_data: {}, error: '' };
            
            switch( route_type ) {
                case 'default_route':
                    //No data to return
                    break;
                case 'single':
                    var item_global = route_params.item_global;
                    var item_id = route_params.item_id;
                    var global = App.globals[item_global];
                    if( global ){
                        var item = global.get(item_id);
                        if( item ){
                            var item_json = item.toJSON();
                            var item_data = item_global == 'posts' ? {post:item_json} : {item:item_json};
                            route_data.view_type = 'single';
                            route_data.view_data = {item:item,global:item_global};
                            route_data.screen_data = {screen_type:'single',component_id:'',component_type:'',item_id:parseInt(item_id),global:item_global,data:item_data,label:item_json.title};
                        }else{
                            route_data.error = 'Error : router single route : item with id "'+ item_id +'" not found in global "'+ item_global +'".';
                        }
                    }else{
                        route_data.error = 'Error : router single route : global "'+ item_global +'" not found.';
                    }
                    break;
                case 'page':
                    var component_id = route_params.component_id;
                    var page_id = route_params.page_id;
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

                                route_data.view_type = 'page';
                                route_data.view_data = {item:item,global:item_global};
                                route_data.screen_data = {screen_type:'page',component_id:component_id,component_type:component.type,item_id:parseInt(page_id),global:item_global,data:item_data,label:item_data.post.title};

                            }else{
                                route_data.error = 'Error : router : page route : component with id "'+ component_id +'" not found';
                            }
                        }else{
                            route_data.error = 'Error : router : page route : item with id "'+ page_id +'" not found in global "'+ item_global +'".';
                        }
                    }else{
                        route_data.error = 'Error : router : screen route : global "'+ item_global +'" not found.';
                    }
                    break;
                case 'comments':
                    screen_data = {};
                    //Can't be done because comments data are retrieved from AJAX query to server.
                    break;
                case 'component':
                    var component_id = route_params.component_id;
                    var component = App.getComponentData( route_params.component_id );
                    if( component ) {
                        if ( component.type === 'posts-list' ) {
                            route_data.view_type = 'posts-list';
							route_data.view_data = component.view_data;
                            route_data.screen_data = {screen_type:'list',component_type:component.type,component_id:component_id,item_id:0,global:component.global,data:component.data,label:component.label};
                        } else if ( component.type === 'hooks-list' || component.type === 'hooks-no-global' ) {
                            route_data.view_type = 'hooks';
							route_data.view_data = {component:component};
                            route_data.screen_data = {screen_type:'custom-component',component_type:component.type,component_id:component_id,item_id:0,global:component.global,data:component.data,label:component.label};
                        } else {
                            route_data = Hooks.applyFilters('component-custom-type',route_data,[component]);
                        }
					} else {
                        route_data.error = 'Error : component not found: "'+ component_id +'"';
                    }
                    break;
                case 'custom-page':
                    var current_custom_page = App.getCurrentCustomPage();
                    if( current_custom_page !== null ){
                        route_data.view_type = 'custom-page';
                        route_data.view_data = {custom_page:current_custom_page};
                        route_data.screen_data = {screen_type:'custom-page',component_id:'',component_type:'',item_id:current_custom_page.get('id'),data:{custom_page:current_custom_page}};
                    } else {
                        route_data.error = 'Error : no current custom page found';
                    }
                    break;
                case 'not_found':
                    //No data to return
                    break;
            }
            
            return route_data;
        }

    });

});