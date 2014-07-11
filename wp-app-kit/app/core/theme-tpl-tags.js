/**
  * Defines "template tags like" functions that can be called from theme templates 
 * and theme functions.js. 
 */
define(function (require,exports) {

      "use strict";

      var _                   = require('underscore'),
          Config              = require('root/config'),
          App                 = require('core/app'),
      	  ThemeApp            = require('core/theme-app');
          
      var themeTplTags = {};
      
      /**
       * Retrieves current page infos :
       * @return JSON object containing :
       * - page_type : list, single, comments, page
       * - fragment : unique page url id (what's after # in url)
       * - component_id : component slug id, if displaying a component page (list, page)
       * - item_id : current page id, if displaying single content (post,page)
       * - data : contains more specific data depending on which page type is displayed
       * 	> total : total number of posts for lists
       * 	> query : query vars used to retrieve contents (taxonomy, terms...)
       * 	> ids : id of posts displayed in lists
       * 	> any other specific data depending on currently displayed component
       */
      themeTplTags.getCurrentPage = function(){
    	  return App.getCurrentPageData();
      };
      
      themeTplTags.getPreviousPageLink = function(){
    	  return App.getPreviousPageLink();
	  };
	  
      themeTplTags.getPostLink = function(post_id,global){
    	  //TODO Check if the post exists in the posts global

    	  var page_data = App.getCurrentPageData();
    	  
    	  var single_global = '';
    	  if( global != undefined ){
    		  single_global = global;
    	  }else{
    		  if( page_data.page_type == 'comments' ){
    			  var previous_page_data = App.getPreviousPageData();
    			  if( previous_page_data.page_type == 'single' ){
    				  single_global = previous_page_data.global;
    			  }
    		  }else{
    			  if( page_data.hasOwnProperty('global') && page_data.global != '' ){
        			  single_global = page_data.global;
        		  }
    		  }
    	  }
    	 
    	  return single_global != '' ? '#single/'+ single_global +'/'+ post_id : '';
      };
      
      themeTplTags.getCommentsLink = function(post_id){
    	  //TODO Check if the post exists in the posts global
    	  return '#comments-'+ post_id;
      };
      
      themeTplTags.isSingle = function(post_id){
    	  var page_data = App.getCurrentPageData();
    	  var is_single = page_data.page_type == 'single';
    	  if( is_single && post_id != undefined ){
    		  is_single &= parseInt(post_id) == page_data.item_id;
    	  }
    	  return is_single == true;
      };
      
      themeTplTags.isPostType = function(post_type,post_id){
    	  var page_data = App.getCurrentPageData();
    	  var is_post_type = (page_data.page_type == 'single');
    	  if( is_post_type && post_type != undefined ){
    		  is_post_type &= (page_data.data.post.post_type == post_type);
    		  if( is_post_type && post_id != undefined ){
    			  is_post_type &= (parseInt(post_id) == page_data.item_id);
        	  }
    	  }
    	  return is_post_type == true;
      };
      
      themeTplTags.isTaxonomy = function(taxonomy,terms){
    	  var is_taxonomy = false;
    	  
    	  var page_data = App.getCurrentPageData();
    	  
    	  if( !_.isEmpty(page_data.data) && !_.isEmpty(page_data.data.query) ){
	    	  var page_query = page_data.data.query;
	    	  is_taxonomy = page_data.page_type == 'list' && !_.isEmpty(page_query.type) && page_query.type == 'taxonomy';
	    	  if( is_taxonomy && !_.isEmpty(taxonomy) ){
	    		  is_taxonomy &= !_.isEmpty(page_query.taxonomy) && page_query.taxonomy == taxonomy;
		    	  if( is_taxonomy && terms != undefined ){
		    		  if( typeof terms === 'string' ){
		    			  terms = [terms];
		    		  }
		    		  is_taxonomy &= !_.isEmpty(_.intersection(terms,page_query.terms));
		    	  }
	    	  }
    	  }	  
    	  
    	  return is_taxonomy;
      };
      
      themeTplTags.isCategory = function(categories){
    	  return themeTplTags.isTaxonomy('category',categories);
      };
      
      themeTplTags.isTag = function(tags){
    	  return themeTplTags.isTaxonomy('tag',tags);
      };
      
      themeTplTags.isScreen = function(screen_fragment){
    	  var page_data = App.getCurrentPageData();
    	  return page_data.fragment == screen_fragment;
      };
      
      themeTplTags.displayBackButton = function(){
    	  var display = ThemeApp.getBackButtonDisplay();
    	  return display == 'show';
	  };
	  
	  themeTplTags.displayGetMoreLink = function(){
		  var get_more_link_display = ThemeApp.getGetMoreLinkDisplay();
		  return get_more_link_display.display;
	  };
	  
	  themeTplTags.getMoreLinkNbLeft = function(){
		  var get_more_link_display = ThemeApp.getGetMoreLinkDisplay();
		  return get_more_link_display.nb_left;
	  };
	  
	  themeTplTags.formatDate = function(date_timestamp,format){
		
		  //TODO : this is really really basic, incomplete and not robust date formating... improve this!
		  
		  if( format == undefined ){
    		  format = "d/m/Y";
    	  }
		  var date = new Date(date_timestamp*1000);
		  var month = date.getUTCMonth() + 1;
    	  month = month < 10 ? '0' + month : month;
    	  var day = date.getUTCDate();
    	  var year = date.getUTCFullYear();
    	  
    	  format = format.replace('d',day);
    	  format = format.replace('m',month);
    	  format = format.replace('Y',year);
    	  
    	  return format;
	  };
	  
	  
	  /**********************************************
	   * Pages 
	   */
	  
	  themeTplTags.isPage = function(page_id){
    	  var page_data = App.getCurrentPageData();
    	  var is_page = page_data.page_type == 'page';
    	  if( is_page && page_id != undefined ){
    		  is_page &= parseInt(page_id) == page_data.item_id;
    	  }
    	  return is_page == true;
      };
      
      themeTplTags.isTreePage = function(page_id){
		  var page_data = App.getCurrentPageData();
		  var is_tree_page = page_data.page_type == 'page' && page_data.data.is_tree_page;
    	  if( is_tree_page && page_id != undefined ){
    		  is_tree_page &= parseInt(page_id) == page_data.item_id;
    	  }
    	  return is_tree_page == true;
	  };
      
	  var getPageTreeData = function(what){
		  var page_data = App.getCurrentPageData();
		  
		  var tree_data = '';
		  
		  var tree_data_raw = page_data.data.item && page_data.data.item.tree_data ? page_data.data.item.tree_data : [0,[],[],0,[]];
		  
		  var parent = page_data.data.is_tree_page && !page_data.data.is_tree_root ? tree_data_raw[0] : 0;
		  var siblings = page_data.data.is_tree_page && !page_data.data.is_tree_root ? tree_data_raw[1] : [];
		  var children = page_data.data.is_tree_page ? tree_data_raw[2] : [];
		  
		  var depth = page_data.data.is_tree_page ? tree_data_raw[3]-page_data.data.root_depth : 0;
		  
		  var ariane = [];
		  if( page_data.data.is_tree_page ){
			  var ariane_raw = tree_data_raw[4];
			  var root_index_in_ariane = ariane_raw.indexOf(page_data.data.root_id);
			  if( root_index_in_ariane > -1 ){
				  for(var i=root_index_in_ariane; i<ariane_raw.length; i++){
					  ariane.push(ariane_raw[i]);
				  }
			  }
		  }
		  
		  if( tree_data_raw.length ){
			  if( what != undefined ){
				  switch( what ){
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
			  }else{
				  tree_data = {'parent':parent,'siblings':siblings,'children':children,'depth':depth,'ariane':ariane};
			  }
		  }
		  
		  return tree_data;
	  };
	  
	  themeTplTags.getPageParent = function(){
    	  var parent = null;
    	  
    	  if( themeTplTags.isTreePage() ){
    		  var parent_id = getPageTreeData('parent');
    		  if( parent_id > 0 ){
    			  parent = App.getGlobalItem('pages',parent_id);
    		  }
    	  }
    	  
    	  return parent;
	  };
	  
	  themeTplTags.getPageSiblings = function(){
    	  var siblings = [];
    	  
    	  if( themeTplTags.isTreePage() ){
    		  var siblings_ids = getPageTreeData('siblings');
    		  if( siblings_ids.length ){
    			  siblings = App.getGlobalItems('pages',siblings_ids);
    		  }
    	  }
    	  
    	  return siblings;
	  };
	  
	  themeTplTags.getNextPage = function(){
    	  var next_page = null;
    	  
    	  if( themeTplTags.isTreePage() ){
    		  var page_data = App.getCurrentPageData();
    		  var siblings_ids = getPageTreeData('siblings');
			  var page_index = siblings_ids.indexOf(page_data.item_id);
			  if( page_index != -1 && page_index < (siblings_ids.length-1) ){
				  next_page = App.getGlobalItem('pages',siblings_ids[page_index+1]);
			  }
    	  }
    	  
    	  return next_page;
	  };
	  
	  themeTplTags.getPreviousPage = function(){
    	  var previous_page = null;
    	  
    	  if( themeTplTags.isTreePage() ){
    		  var page_data = App.getCurrentPageData();
			  var siblings_ids = getPageTreeData('siblings');
			  var page_index = siblings_ids.indexOf(page_data.item_id);
			  if( page_index != -1 && page_index > 0 ){
				  previous_page = App.getGlobalItem('pages',siblings_ids[page_index-1]);
			  }
    	  }
    	  return previous_page;
	  };
	  
	  themeTplTags.getPageChildren = function(){
    	  var children = [];
    	  
    	  if( themeTplTags.isTreePage() ){
    		  var children_ids = getPageTreeData('children');
    		  if( children_ids.length ){
    			  children = App.getGlobalItems('pages',children_ids);
    		  }
    	  }
    	  
    	  return children;
	  };
	  
	  themeTplTags.getPageDepth = function(){
    	  var depth = 0;
    	  
    	  if( themeTplTags.isTreePage() ){
    		  depth = getPageTreeData('depth');
    	  }
    	  
    	  return depth;
	  };
	  
	  themeTplTags.getPageAriane = function(){
    	  var ariane = [];
    	  
    	  if( themeTplTags.isTreePage() ){
    		  var ariane_ids = getPageTreeData('ariane');
    		  if( ariane_ids.length ){
    			  ariane = App.getGlobalItems('pages',ariane_ids);
    		  }
    	  }
    	  
    	  return ariane;
	  };
	  
	  themeTplTags.getPageLink = function(page_id,component_id){
		  var link = '';
		  
		  if( themeTplTags.isTreePage() ){
			  if( component_id == undefined ){
				  var page_data = App.getCurrentPageData();
				  var component_id = page_data.component_id; 
			  }
			  link = '#page/'+ component_id +'/'+ page_id;
		  }
		  
		  return link;
	  };
	  
	  //Use exports so that theme-tpl-tags and theme-app (which depend on each other, creating
	  //a circular dependency for requirejs) can both be required at the same time 
	  //(in theme functions.js for example) : 
	  _.extend(exports,themeTplTags); 
});