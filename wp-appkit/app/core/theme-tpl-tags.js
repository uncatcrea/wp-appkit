/**
 * Defines "template tags like" functions that can be called from theme templates
 * and theme functions.js.
 */
define(function(require, exports) {

    "use strict";

    var _             = require('underscore'),
        Config        = require('root/config'),
        App           = require('core/app'),
        RegionManager = require( 'core/region-manager' ),
		Stats         = require('core/stats'),
		Addons        = require('core/addons-internal'),
        ThemeApp      = require('core/theme-app'),
        Hooks         = require('core/lib/hooks'),
		Utils         = require('core/app-utils');

    var themeTplTags = {};

    /**
     * Retrieves current screen infos :
     * @return JSON object containing :
     * - screen_type : list, single, comments, page
     * - fragment : unique screen url id (what's after # in url)
     * - component_id : component slug id, if displaying a component screen (list, page)
     * - item_id : current item id, if displaying single content (post,page)
	 * - label : current item label (title of component, title of post)
     * - data : contains more specific data depending on which screen type is displayed
     * 	> total : total number of posts for lists
     * 	> query : query vars used to retrieve contents (taxonomy, terms...)
     * 	> ids : id of posts displayed in lists
     * 	> any other specific data depending on currently displayed component
     */
    themeTplTags.getCurrentScreen = function() {
        return App.getCurrentScreenData();
    };
	
	/**
	 * Retrieves useful data corresponding to the object that is currently displayed.
	 * Alias of ThemeApp.getCurrentScreenObject()
	 */
	themeTplTags.getCurrentScreenObject = function() {
		return ThemeApp.getCurrentScreenObject();
	};

    /**
     * Retrieves previous screen infos :
     * @return JSON object containing :
     * - screen_type : list, single, comments, page
     * - fragment : unique screen url id (what's after # in url)
     * - component_id : component slug id, if displaying a component screen (list, page)
     * - item_id : current item id, if displaying single content (post,page)
     * - label : current item label (title of component, title of post)
     * - data : contains more specific data depending on which screen type is displayed
     *  > total : total number of posts for lists
     *  > query : query vars used to retrieve contents (taxonomy, terms...)
     *  > ids : id of posts displayed in lists
     *  > any other specific data depending on currently displayed component
     */
    themeTplTags.getPreviousScreen = function() {
        return App.getPreviousScreenData();
    };

	/**
     * Check if the given or current screen is the default one
     *
     * @param   Object  screen_data     An object describing the screen we want to test, if not given, the current screen will be tested
     *
     * @return  bool            Whether the screen is the default one or not
     */
    themeTplTags.isDefaultScreen = function( screen_data ) {
        var fragment = "undefined" != typeof screen_data && screen_data.hasOwnProperty( 'fragment' ) ? screen_data.fragment : Backbone.history.fragment;
        var prefix = App.getParam('use-html5-pushstate') ? '' : '#';
        fragment = prefix + fragment;
        return fragment.length === 0 || App.router.getDefaultRoute() == fragment;
    };

	/**
	 * Retrieves the name of the template used for the current screen.
	 * @returns {String} Template name
	 */
	themeTplTags.getCurrentTemplate = function() {
        var current_template = '';
		var current_view = RegionManager.getCurrentView();
		if ( current_view ) {
			current_template = current_view.template_name;
		}
		return current_template;
    };

    themeTplTags.getPreviousScreenLink = function() {
        return App.getPreviousScreenLink();
    };

    themeTplTags.getPostLink = function(post_id, global) {
        //TODO Check if the post exists in the posts global

        var single_global = App.getCurrentScreenGlobal( global );

        return single_global !== '' ? App.getScreenFragment( 'single', { global: single_global, item_id: post_id } ) : '';
    };

	/**
	 * Retrieves url fragment for a comments screen.
	 * NOTE : It is better to use ThemeApp.displayPostComments() than to use this
	 * function, as ThemeApp.displayPostComments() allows to handle success and error callback.
	 * Using TplTags.getCommentsLink() directly, you won't be able to handle errors on
	 * comments screens display.
	 * 
	 * @param {int} post_id Post id to retrieve comments for
	 * @returns {String} Comment's screen fragment
	 */
    themeTplTags.getCommentsLink = function(post_id) {
        //TODO Check if the post exists in the posts global
        return App.getScreenFragment( 'comments', { item_id: post_id } );
    };

	themeTplTags.getDefaultRouteLink = function() {
        return App.router.getDefaultRoute();
    };

	/**
	 * Checks if displayin a single
	 * @param int post_id : Optional : The post ID to check
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isSingle = function(post_id, screen, global) {
        var screen_data = screen !== undefined && !_.isEmpty(screen) ? screen : App.getCurrentScreenData();
		global = global !== undefined ? global : 'posts';
        var is_single = screen_data.screen_type == 'single' && screen_data.global == global;
        if (is_single && post_id != undefined && post_id != '' && post_id != 0 ) {
            is_single = parseInt(post_id) == screen_data.item_id;
        }
        return is_single == true;
    };

	/**
	 * Checks if displaying a single for the given post type
	 * @param string post_type
	 * @param int post_id : Optional : The post ID to check
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isPostType = function(post_type, post_id, screen) {
        var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
        var is_post_type = (screen_data.screen_type == 'single');
        if (is_post_type && post_type != undefined) {
            is_post_type = (screen_data.data.post.post_type == post_type);
            if (is_post_type && post_id != undefined && post_id != '' && post_id != 0 ) {
                is_post_type = is_post_type && (parseInt(post_id) == screen_data.item_id);
            }
        }
        return is_post_type == true;
    };

	/**
	 * Check if displaying a taxonomy terms archive
	 * @param string taxonomy : WordPress taxonomy slug to check
	 * @param string|array terms : Optional : Taxonomy terms slug(s) to check
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isTaxonomy = function(taxonomy, terms, screen) {
        var is_taxonomy = false;

        var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();

        if (!_.isEmpty(screen_data.data) && !_.isEmpty(screen_data.data.query)) {
            var screen_query = screen_data.data.query;
            is_taxonomy = screen_data.screen_type == 'list' && !_.isEmpty(screen_query.type) && screen_query.type == 'taxonomy';
            if (is_taxonomy && !_.isEmpty(taxonomy)) {
                is_taxonomy = !_.isEmpty(screen_query.taxonomy) && screen_query.taxonomy == taxonomy;
                if (is_taxonomy && !_.isEmpty(terms)) {
                    if (typeof terms === 'string') {
                        terms = [terms];
                    }
                    is_taxonomy = is_taxonomy && !_.isEmpty(_.intersection(terms, screen_query.terms));
                }
            }
        }

        return is_taxonomy;
    };

	/**
	 * Check if displaying a Category archive
	 * @param string|array categories : Optional : Categories slug(s) to check
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isCategory = function(categories, screen) {
        return themeTplTags.isTaxonomy('category', categories, screen);
    };

	/**
	 * Check if displaying a Tag archive
	 * @param string|array tags : Optional : Tags slug(s) to check
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isTag = function(tags, screen) {
        return themeTplTags.isTaxonomy('tag', tags, screen);
    };

	/**
	 * Check if displaying the given screen_fragment screen
	 * @param string screen_fragment The string fragment to check
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isScreen = function(screen_fragment, screen) {
        var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
        return screen_data.fragment == screen_fragment;
    };

    themeTplTags.displayBackButton = function() {
        var display = ThemeApp.getBackButtonDisplay();
        return display == 'show';
    };

    themeTplTags.displayGetMoreLink = function() {
        var get_more_link_display = ThemeApp.getGetMoreLinkDisplay();
        return get_more_link_display.display;
    };

    themeTplTags.getMoreLinkNbLeft = function() {
        var get_more_link_display = ThemeApp.getGetMoreLinkDisplay();
        return get_more_link_display.nb_left;
    };

	themeTplTags.getComponent = function(component_id) {
        return App.getComponentData(component_id);
    };
	
	themeTplTags.componentExists = function(component_id) {
        return App.componentExists(component_id);
    };
	
	themeTplTags.getComponentLink = function( component_id ) {
		var component_link = App.getScreenFragment( 'component', { component_id: component_id } );
        return component_link;
    };
	
	/**
	 * Check if current screen's component is the given component
	 * 
	 * @param   {string}  component_id   Id of the component (usually a string slug)
	 * @returns {boolean} true if current screen correspond to given component id
	 */
	themeTplTags.isComponent = function( component_id ) {
        return component_id.length > 0 && ThemeApp.getCurrentComponentId() === component_id;
    };

	themeTplTags.getComponentItems = function( component_id, return_format ) {
		return_format = return_format === undefined ? 'view_items' : return_format;
		var items = [];
		var component = themeTplTags.getComponent( component_id );
		if ( component ) {
			if ( return_format === 'view_items' || return_format === 'json' ) {
				
				if ( component.view_data.hasOwnProperty( 'posts' ) ) {
					items = component.view_data.posts;
				} else if ( component.view_data.hasOwnProperty( 'items' ) ) {
					items = component.view_data.items;
				}
				
				if ( return_format === 'json' ) {
					items = items.map( function( model ){ return model.toJSON(); } );
				}
			}
		}
        return items;
    };

    themeTplTags.formatDate = function(date_timestamp, format) {

        //TODO : this is really really basic, incomplete and not robust date formating... improve this!

        if (format == undefined) {
            format = "d/m/Y";
        }
        var date = new Date(date_timestamp * 1000);
        var month = date.getUTCMonth() + 1;
        month = month < 10 ? '0' + month : month;
        var day = date.getUTCDate();
        var year = date.getUTCFullYear();

        format = format.replace('d', day);
        format = format.replace('m', month);
        format = format.replace('Y', year);

        return format;
    };

	/**
	 * Retrieves the path to the current theme
	 * @returns string Path to the current theme
	 */
	themeTplTags.getThemePath = function() {
		return 'themes/'+ Config.theme;
	};

	/**
	 * This allows to add theme path and cache busting (and GET params that may be needed
	 * for app simulation in browser) to the given asset file url.
	 *
	 * For example, to include a CSS in the head.html template you can use one
	 * of the following according to your needs :
	 * - <%= TemplateTags.getThemePath() %>/css/my-styles.css
	 * - <%= TemplateTags.getThemeAssetUrl('css/my-styles.css') %> : adds cache busting if in debug mode
	 * - <%= TemplateTags.getThemeAssetUrl('css/my-styles.css', true) %> : to force cache busting in any case
	 *
	 * @param {string} theme_asset_url Asset file url RELATIVE to the theme directory
	 * @param Optional {type} bust True to add a cache busting param to the url. Defaults to Config.debug_mode == 'on'.
	 * @returns {String} modified theme asset url with cache busting
	 */
	themeTplTags.getThemeAssetUrl = function( theme_asset_url, bust ) {

		if( bust === undefined ) {
			bust = Config.debug_mode === 'on' && Config.app_type !== 'pwa';
		}else{
			bust = bust === true;
		}

		//For app simulation in browser :
		var query = window.location.search.substring(1);
		if( query.length ){
			//query = wpak_app_id=[app_slug]
			var query_key_value = query.split('=');
			var query_key = query_key_value[0];
			var query_value = query_key_value[1];
			theme_asset_url = Utils.addParamToUrl(theme_asset_url, query_key, query_value);
		}

		if( bust ) {
			var time = new Date().getTime();
			theme_asset_url = Utils.addParamToUrl(theme_asset_url, 'bust', time);
		}

		theme_asset_url = themeTplTags.getThemePath() +'/'+ theme_asset_url;

		return theme_asset_url;
	};

	/**
	 * Retrieves menu items, in the same format as in the menu.html template
	 * @returns {Array of JSON objects} Menu items
	 */
	themeTplTags.getMenuItems = function() {
		var menu_items = [];

		var menu_view = RegionManager.getMenuView();
		if ( menu_view ) {
			menu_items = menu_view.menu.toJSON();
		}

		return menu_items;
	};
	
	themeTplTags.isComponentInMenu = function( component_id ) {
		var component_in_menu = false;
		var menu_view = RegionManager.getMenuView();
		if ( menu_view ) {
			component_in_menu = menu_view.menu.get( component_id ) !== undefined;
		}
		return component_in_menu;
	};

	/**********************************************
	 * Addons
	 */

	/**
	 * Checks if an addon is active
	 * @param string addon_slug
	 * @returns Boolean True if the addon is active
	 */
	themeTplTags.addonIsActive = function( addon_slug ) {
		return Addons.isActive( addon_slug );
	};

    /**********************************************
     * Pages
     */

	/**
	 * Check if displaying a page
	 * @param int page_id
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isPage = function(page_id, screen) {
        var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
        var is_page = screen_data.screen_type == 'page';
        if (is_page && page_id != undefined) {
            is_page = parseInt(page_id) == screen_data.item_id;
        }
        return is_page == true;
    };

	/**
	 * Check if displaying a page that has a subtree
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns boolean
	 */
    themeTplTags.isTreePage = function(screen) {
        var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
        var is_tree_page = screen_data.screen_type == 'page' && screen_data.data.is_tree_page;
        return is_tree_page == true;
    };

	/**
	 * Get page tree data
	 * @param string : what you want to retrieve about the page (parent, siblings, children, depth, ariane)
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns mixed array|page object|int
	 */
    var getPageTreeData = function(what, screen) {
        var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();

        var tree_data = '';

        var tree_data_raw = screen_data.data.post && screen_data.data.post.tree_data ? screen_data.data.post.tree_data : [0, [], [], 0, []];

        var parent = screen_data.data.is_tree_page && !screen_data.data.is_tree_root ? tree_data_raw[0] : 0;
        var siblings = screen_data.data.is_tree_page && !screen_data.data.is_tree_root ? tree_data_raw[1] : [];
        var children = screen_data.data.is_tree_page ? tree_data_raw[2] : [];

        var depth = screen_data.data.is_tree_page ? tree_data_raw[3] - screen_data.data.root_depth : 0;

        var ariane = [];
        if (screen_data.data.is_tree_page) {
            var ariane_raw = tree_data_raw[4];
            var root_index_in_ariane = ariane_raw.indexOf(screen_data.data.root_id);
            if (root_index_in_ariane > -1) {
                for (var i = root_index_in_ariane; i < ariane_raw.length; i++) {
                    ariane.push(ariane_raw[i]);
                }
            }
        }

        if (tree_data_raw.length) {
            if (what != undefined) {
                switch (what) {
                    case 'parent':
                        tree_data = parent;
                        break;
                    case 'siblings':
                        tree_data = siblings;
                        break;
                    case 'children':
                        tree_data = children;
                        break;
                    case 'depth':
                        tree_data = depth;
                        break;
                    case 'ariane':
                        tree_data = ariane;
                        break;
                }
            } else {
                tree_data = {'parent': parent, 'siblings': siblings, 'children': children, 'depth': depth, 'ariane': ariane};
            }
        }

        return tree_data;
    };

	/**
	 * Get page parent
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns {unresolved}
	 */
    themeTplTags.getPageParent = function(screen) {
        var parent = null;

        if (themeTplTags.isTreePage(screen)) {
            var parent_id = getPageTreeData('parent',screen);
            if (parent_id > 0) {
                parent = App.getGlobalItem('pages', parent_id);
            }
        }

        return parent;
    };

	/**
	 * Get page siblings
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns {Array}
	 */
    themeTplTags.getPageSiblings = function(screen) {
        var siblings = [];

        if (themeTplTags.isTreePage(screen)) {
            var siblings_ids = getPageTreeData('siblings', screen);
            if (siblings_ids.length) {
                siblings = App.getGlobalItems('pages', siblings_ids);
            }
        }

        return siblings;
    };

	/**
	 * Get next page
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns page object
	 */
    themeTplTags.getNextPage = function(screen) {
        var next_page = null;

        if (themeTplTags.isTreePage(screen)) {
            var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
            var siblings_ids = getPageTreeData('siblings',screen);
            var page_index = siblings_ids.indexOf(screen_data.item_id);
            if (page_index != -1 && page_index < (siblings_ids.length - 1)) {
                next_page = App.getGlobalItem('pages', siblings_ids[page_index + 1]);
            }
        }

        return next_page;
    };

	/**
	 * Get previous page
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns page object
	 */
    themeTplTags.getPreviousPage = function(screen) {
        var previous_page = null;

        if (themeTplTags.isTreePage(screen)) {
            var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
            var siblings_ids = getPageTreeData('siblings', screen);
            var page_index = siblings_ids.indexOf(screen_data.item_id);
            if (page_index != -1 && page_index > 0) {
                previous_page = App.getGlobalItem('pages', siblings_ids[page_index - 1]);
            }
        }
        return previous_page;
    };

	/**
	 * Get page children
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns {Array}
	 */
    themeTplTags.getPageChildren = function(screen) {
        var children = [];

        if (themeTplTags.isTreePage(screen)) {
            var children_ids = getPageTreeData('children', screen);
            if (children_ids.length) {
                children = App.getGlobalItems('pages', children_ids);
            }
        }

        return children;
    };

	/**
	 * Get page depth
	 * object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns int
	 */
    themeTplTags.getPageDepth = function(screen) {
        var depth = 0;

        if (themeTplTags.isTreePage(screen)) {
            depth = getPageTreeData('depth',screen);
        }

        return depth;
    };

	/**
	 * Get page breadcrumb
	 * @param object screen : Optional : use only if you want data from a different screen than the current one
	 * @returns array
	 */
    themeTplTags.getPageBreadcrumb = function(screen) {
        var ariane = [];

        if (themeTplTags.isTreePage(screen)) {
            var ariane_ids = getPageTreeData('ariane',screen);
            if (ariane_ids.length) {
                ariane = App.getGlobalItems('pages', ariane_ids);
            }
        }

        return ariane;
    };

	/**
	 * Get the link to a given page
	 * @param int page_id
	 * @param string component_id Optional : will try to guess from the current screen
	 * @param object screen Optional : the screen to check if no component_id specified
	 * @returns string
	 */
	themeTplTags.getPageLink = function(page_id, component_id, screen) {
        var link = '';

		if ( _.isEmpty(component_id) ) {
			var screen_data = screen !== undefined ? screen : App.getCurrentScreenData();
			component_id = screen_data.component_id;
		}

		//TODO Check if the exists exists in the pages global
		link = App.getScreenFragment( 'page', { component_id: component_id, item_id: page_id } );

        return link;
    };

	/************************************************
	 * App network management
	 */

	/**
	 * Retrieve network state : "online", "offline" or "unknown"
	 * If full_info is passed and set to true, detailed connexion info is
	 * returned (Wifi, 3G etc...).
	 * This is an alias for ThemeApp.getNetworkState(full_info) because it
	 * can be useful in themes too.
	 *
	 * @param boolean full_info Set to true to get detailed connexion info
	 * @returns string "online", "offline" or "unknown"
	 */
	themeTplTags.getNetworkState = function(full_info) {
		return ThemeApp.getNetworkState(full_info);
	};

	/************************************************
	 * App stats management
	 */

	/**
	 * Retrieves app stats. "stat" can be empty to retrieve all stats, or
	 * "count_open", "last_open_date", "version", "version_diff".
	 *
	 * @param string stat (optionnal) : Name of stat to retrieve
	 * @returns JSON object|string : Returns JSON object if "stat" is empty,
	 * or the specific stat value correponding to the "stat" arg.
	 */
	themeTplTags.getAppStats = function(stat) {
		return Stats.getStats(stat);
	};

    /**
     * Return a list of "data-xxx" attributes to include into a link for a specific post.
     *
     * @param   int     post_id         The post id.
     * @return  string                  The completed "data-xxx" attributes.
     */
    themeTplTags.getPostDataAttributes = function( post_id ) {
        var attributes = [
            'data-id="' + post_id + '"',
            'data-global="' + App.getPostGlobal( post_id ) + '"'
        ];

        attributes = Hooks.applyFilters( 'post-data-attributes', attributes, [post_id] );

        return attributes.join( ' ' );
    };

    /**
     * Return true or false whether the given type of component is inluded into the app or not.
     *
     * @param   string  type    The type of component.
     *
     * @return  bool            True if the type of component is included, false otherwise.
     */
    themeTplTags.isComponentTypeLoaded = function( type ) {
        var components = App.components.where( { type: type } );

        return components.length > 0;
    };

    //Use exports so that theme-tpl-tags and theme-app (which depend on each other, creating
    //a circular dependency for requirejs) can both be required at the same time
    //(in theme functions.js for example) :
    _.extend(exports, themeTplTags);
});