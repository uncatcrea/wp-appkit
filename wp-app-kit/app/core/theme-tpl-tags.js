/**
 * Defines "template tags like" functions that can be called from theme templates
 * and theme functions.js.
 */
define(function(require, exports) {

    "use strict";

    var _ = require('underscore'),
            Config = require('root/config'),
            App = require('core/app'),
			Stats = require('core/stats'), 
            ThemeApp = require('core/theme-app');

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
     * Retrieves previous screen infos :
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
    themeTplTags.getPreviousScreen = function() {
        return App.getPreviousScreenData();
    };

    themeTplTags.getPreviousScreenLink = function() {
        return App.getPreviousScreenLink();
    };

    themeTplTags.getPostLink = function(post_id, global) {
        //TODO Check if the post exists in the posts global

        var screen_data = App.getCurrentScreenData();

        var single_global = '';
        if (global != undefined) {
            single_global = global;
        } else {
            if (screen_data.screen_type == 'comments') {
                var previous_screen_data = App.getPreviousScreenData();
                if (previous_screen_data.screen_type == 'single') {
                    single_global = previous_screen_data.global;
                }
            } else {
                if (screen_data.hasOwnProperty('global') && screen_data.global != '') {
                    single_global = screen_data.global;
                }
            }
        }

        return single_global != '' ? '#single/' + single_global + '/' + post_id : '';
    };

    themeTplTags.getCommentsLink = function(post_id) {
        //TODO Check if the post exists in the posts global
        return '#comments-' + post_id;
    };
	
	themeTplTags.getDefaultRouteLink = function() {
        return App.router.getDefaultRoute();
    };

    themeTplTags.isSingle = function(post_id) {
        var screen_data = App.getCurrentScreenData();
        var is_single = screen_data.screen_type == 'single';
        if (is_single && post_id != undefined) {
            is_single &= parseInt(post_id) == screen_data.item_id;
        }
        return is_single == true;
    };

    themeTplTags.isPostType = function(post_type, post_id) {
        var screen_data = App.getCurrentScreenData();
        var is_post_type = (screen_data.screen_type == 'single');
        if (is_post_type && post_type != undefined) {
            is_post_type &= (screen_data.data.post.post_type == post_type);
            if (is_post_type && post_id != undefined) {
                is_post_type &= (parseInt(post_id) == screen_data.item_id);
            }
        }
        return is_post_type == true;
    };

    themeTplTags.isTaxonomy = function(taxonomy, terms) {
        var is_taxonomy = false;

        var screen_data = App.getCurrentScreenData();

        if (!_.isEmpty(screen_data.data) && !_.isEmpty(screen_data.data.query)) {
            var screen_query = screen_data.data.query;
            is_taxonomy = screen_data.screen_type == 'list' && !_.isEmpty(screen_query.type) && screen_query.type == 'taxonomy';
            if (is_taxonomy && !_.isEmpty(taxonomy)) {
                is_taxonomy &= !_.isEmpty(screen_query.taxonomy) && screen_query.taxonomy == taxonomy;
                if (is_taxonomy && terms != undefined) {
                    if (typeof terms === 'string') {
                        terms = [terms];
                    }
                    is_taxonomy &= !_.isEmpty(_.intersection(terms, screen_query.terms));
                }
            }
        }

        return is_taxonomy;
    };

    themeTplTags.isCategory = function(categories) {
        return themeTplTags.isTaxonomy('category', categories);
    };

    themeTplTags.isTag = function(tags) {
        return themeTplTags.isTaxonomy('tag', tags);
    };

    themeTplTags.isScreen = function(screen_fragment) {
        var screen_data = App.getCurrentScreenData();
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


    /**********************************************
     * Pages
     */

    themeTplTags.isPage = function(page_id) {
        var screen_data = App.getCurrentScreenData();
        var is_page = screen_data.screen_type == 'page';
        if (is_page && page_id != undefined) {
            is_page &= parseInt(page_id) == screen_data.item_id;
        }
        return is_page == true;
    };

    themeTplTags.isTreePage = function(page_id) {
        var screen_data = App.getCurrentScreenData();
        var is_tree_page = screen_data.screen_type == 'page' && screen_data.data.is_tree_page;
        if (is_tree_page && page_id != undefined) {
            is_tree_page &= parseInt(page_id) == screen_data.item_id;
        }
        return is_tree_page == true;
    };

    var getPageTreeData = function(what) {
        var screen_data = App.getCurrentScreenData();

        var tree_data = '';

        var tree_data_raw = screen_data.data.item && screen_data.data.item.tree_data ? screen_data.data.item.tree_data : [0, [], [], 0, []];

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

    themeTplTags.getPageParent = function() {
        var parent = null;

        if (themeTplTags.isTreePage()) {
            var parent_id = getPageTreeData('parent');
            if (parent_id > 0) {
                parent = App.getGlobalItem('pages', parent_id);
            }
        }

        return parent;
    };

    themeTplTags.getPageSiblings = function() {
        var siblings = [];

        if (themeTplTags.isTreePage()) {
            var siblings_ids = getPageTreeData('siblings');
            if (siblings_ids.length) {
                siblings = App.getGlobalItems('pages', siblings_ids);
            }
        }

        return siblings;
    };

    themeTplTags.getNextPage = function() {
        var next_page = null;

        if (themeTplTags.isTreePage()) {
            var screen_data = App.getCurrentScreenData();
            var siblings_ids = getPageTreeData('siblings');
            var page_index = siblings_ids.indexOf(screen_data.item_id);
            if (page_index != -1 && page_index < (siblings_ids.length - 1)) {
                next_page = App.getGlobalItem('pages', siblings_ids[page_index + 1]);
            }
        }

        return next_page;
    };

    themeTplTags.getPreviousPage = function() {
        var previous_page = null;

        if (themeTplTags.isTreePage()) {
            var screen_data = App.getCurrentScreenData();
            var siblings_ids = getPageTreeData('siblings');
            var page_index = siblings_ids.indexOf(screen_data.item_id);
            if (page_index != -1 && page_index > 0) {
                previous_page = App.getGlobalItem('pages', siblings_ids[page_index - 1]);
            }
        }
        return previous_page;
    };

    themeTplTags.getPageChildren = function() {
        var children = [];

        if (themeTplTags.isTreePage()) {
            var children_ids = getPageTreeData('children');
            if (children_ids.length) {
                children = App.getGlobalItems('pages', children_ids);
            }
        }

        return children;
    };

    themeTplTags.getPageDepth = function() {
        var depth = 0;

        if (themeTplTags.isTreePage()) {
            depth = getPageTreeData('depth');
        }

        return depth;
    };

    themeTplTags.getPageBreadcrumb = function() {
        var ariane = [];

        if (themeTplTags.isTreePage()) {
            var ariane_ids = getPageTreeData('ariane');
            if (ariane_ids.length) {
                ariane = App.getGlobalItems('pages', ariane_ids);
            }
        }

        return ariane;
    };

    themeTplTags.getPageLink = function(page_id, component_id) {
        var link = '';

		if ( component_id == undefined ) {
			var screen_data = App.getCurrentScreenData();
			component_id = screen_data.component_id;
		}

		//TODO Check if the exists exists in the pages global
		link = '#page/' + component_id + '/' + page_id;

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

    //Use exports so that theme-tpl-tags and theme-app (which depend on each other, creating
    //a circular dependency for requirejs) can both be required at the same time
    //(in theme functions.js for example) :
    _.extend(exports, themeTplTags);
});