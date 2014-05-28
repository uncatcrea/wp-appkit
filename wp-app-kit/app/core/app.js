define(function (require) {

      "use strict";

      var $                   = require('jquery'),
      	  _                   = require('underscore'),
          Backbone            = require('backbone'),
          Components          = require('core/models/components'),
          Globals             = require('core/models/globals'),
          Navigation          = require('core/models/navigation'),
          Items               = require('core/models/items'),
          Comments            = require('core/models/comments'),
          CustomPage          = require('core/models/custom-page'),
          Config              = require('root/config'),
          Utils               = require('core/app-utils'),
          Hooks               = require('core/lib/hooks'),
          Sha256              = require('core/lib/sha256');
      
	  var app = {};
	  
	  //--------------------------------------------------------------------------
	  //Event aggregator
	  var vent = _.extend({}, Backbone.Events);
	  app.on = function(event,callback){
		  vent.on(event,callback);
	  };
	  
	  //--------------------------------------------------------------------------
	  //Error handling
	  
	  app.triggerError = function(error_id,error_data,error_callback){
		  vent.trigger('error:'+ error_id,error_data);
		  Utils.log('app.js error ('+ error_id +') : '+ error_data.message, error_data);
		  if( error_callback != undefined ){
	  		error_callback(error_data);
	  	  }
	  };
	  
	  //--------------------------------------------------------------------------
	  //Custom pages handling
	  
	  var current_custom_page = null;
	  
	  app.getCurrentCustomPage = function(){
		  return current_custom_page;
	  };
	  
	  /**
	   * Displays a custom page using the given template.
	   * @param data see models/custom-page.js for data fields
	   */
	  app.showCustomPage = function(template,data){
		  current_custom_page = new CustomPage({template: template, data: data});
		  app.router.navigate('custom-page',{trigger: true});
	  };
	  
	  //--------------------------------------------------------------------------
	  //App params :
	  //Params that can be changed by themes
	  var params = {
		  'custom-page-rendering' : false
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
	  
	  //Router must be set before calling this resetDefaultRoute :
	  app.resetDefaultRoute = function(){
		  var first_nav_component_id = app.navigation.first().get('component_id');
		  app.router.setDefaultRoute('#component-'+ first_nav_component_id);
	  };
	  
	  //--------------------------------------------------------------------------
	  //History :
	  var history_stack = [];
	  var queried_page_data = {};
	  var previous_page_memory = {};
	  
	  var history_push = function(page_data){
		  history_stack.push(page_data);
	  };
	  
	  var formatPageData = function(page_data){
		  return {	
			  page_type:page_data.page_type,
			  component_id:page_data.component_id,
			  item_id:page_data.item_id,
			  fragment:page_data.fragment,
			  data:page_data.hasOwnProperty('data') ? page_data.data : {},
			  global:page_data.hasOwnProperty('global') ? page_data.global : '',
		  };
	  };
	  
	  app.setQueriedPage = function(page_data){
		  queried_page_data = formatPageData(_.extend(page_data,{fragment: Backbone.history.fragment}));
	  };
	  
	  app.getQueriedPage = function(page_data){
		  return queried_page_data;
	  };
	  
	  app.addQueriedPageToHistory = function(force_flush){
		  
		  var force_flush_history = force_flush != undefined && force_flush == true;
		  
		  var current_page = app.getCurrentPageData();
		  var previous_page = app.getPreviousPageData();
		  
		  previous_page_memory = current_page;
		  
		  if( current_page.page_type != queried_page_data.page_type || current_page.component_id != queried_page_data.component_id 
			  || current_page.item_id != queried_page_data.item_id || current_page.fragment != queried_page_data.current_fragment ){
			  
			  if( force_flush_history ){
				  history_stack = [];
			  }
			  
			  if( queried_page_data.page_type == 'list' ){
				  history_stack = [];
				  history_push(queried_page_data);
			  }else if( queried_page_data.page_type == 'single' ){
				  if( current_page.page_type == 'list' ){
					  history_push(queried_page_data);
				  }else if( current_page.page_type == 'custom-component' ){
					  history_push(queried_page_data);
				  }else if( current_page.page_type == 'comments' ){
					  if( previous_page.page_type == 'single' && previous_page.item_id == queried_page_data.item_id ){
						  history_stack.pop();
					  }else{
						  history_stack = [];
						  history_push(queried_page_data);
					  }
				  }else{
					  history_stack = [];
					  history_push(queried_page_data);
				  }
			  }else if( queried_page_data.page_type == 'page' ){
				  history_stack = [];
				  history_push(queried_page_data);
			  }else if( queried_page_data.page_type == 'comments' ){
				  //if( current_page.page_type == 'single' && current_page.item_id == item_id ){
					  history_push(queried_page_data);
				  //}
			  }else if( queried_page_data.page_type == 'custom-page' ){
				  history_stack = [];
				  history_push(queried_page_data);
			  }else if( queried_page_data.page_type == 'custom-component' ){
				  history_stack = [];
				  history_push(queried_page_data);
			  }else{
				  history_stack = [];
			  }
			  
		  }
		  
	  };
	  
	  /**
	   * Returns infos about the currently displayed page.
	   * @returns {page_type:string, component_id:string, item_id:integer, fragment:string}
	   * Core page_types are "list", "single", "page" "comments". 
	   */
	  app.getCurrentPageData = function(){
		  var current_page = {};
		  if( history_stack.length ){
			  current_page = history_stack[history_stack.length-1];
		  }
		  return current_page;
	  };
	  
	  /**
	   * Returns infos about the page displayed previously.
	   * @returns {page_type:string, component_id:string, item_id:integer, fragment:string} or {} if no previous page 
	   */
	  app.getPreviousPageData = function(){
		  var previous_page = {};
		  if( history_stack.length > 1 ){
			  previous_page = history_stack[history_stack.length-2];
		  }
		  return previous_page;
	  };
	  
	  app.getPreviousPageMemoryData = function(){
		  return previous_page_memory;
	  };
	  
	  app.getPreviousPageLink = function(){
		  var previous_page_link = '';
		  var previous_page = app.getPreviousPageData();
		  if( !_.isEmpty(previous_page) ){
			  previous_page_link = '#'+ previous_page.fragment;
		  }
		  return previous_page_link;
	  };
	  
	  //--------------------------------------------------------------------------
	  //App items data :
	  app.components = new Components;
	  app.navigation = new Navigation;
	  
	  //For globals, separate keys from values because localstorage on 
	  //collections of collections won't work :-(
	  var globals_keys = new Globals; 
	  app.globals = {};
	  
	  var getToken = function(web_service){
		  var token = '';
		  var key = '';
		  
		  if( Config.hasOwnProperty('auth_key') ){
			  key = Config.auth_key;
			  var app_slug = Config.app_slug;
	    	  var date = new Date();
	    	  var month = date.getUTCMonth() + 1;
	    	  var day = date.getUTCDate();
	    	  var year = date.getUTCFullYear();
	    	  if( month < 10 ){
	    		  month = '0'+ month;
	    	  }
	    	  if( day < 10 ){
	    		  day = '0'+ day;
	    	  }
	    	  var date_str = year +'-'+ month +'-'+ day;
	    	  var hash = Sha256(key + app_slug + date_str);
	    	  token = window.btoa(hash);
		  }
		  
		  token = Hooks.applyFilter('get-token',token,[key,web_service]);
		  
		  if( token.length ){
			  token = '/'+ token;
		  }
		  
    	  return token;
	  };
	  
	  app.sync = function(cb_ok,cb_error,force_reload){
		  
		  var force = force_reload != undefined && force_reload;
		  
		  app.components.fetch({'success': function(components, response, options){
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
	    	    	    			 app.globals = {};
	    	    	    			 
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
		  }});
		  
      };
      
	  var syncWebService = function(cb_ok,cb_error,force_reload){
		  var token = getToken('synchronization');
    	  var ws_url = token +'/synchronization/';
    	  
		  $.ajax({
				url : Config.wp_ws_url + ws_url, 
				timeout : 40000,
				dataType : 'json',
				success : function(data) {
				  	  if( data.hasOwnProperty('result') && data.result.hasOwnProperty('status') ){
				  		  if( data.result.status == 1 ){
				  			  if( data.hasOwnProperty('components') 
				  				  && data.hasOwnProperty('navigation')
				  				  && data.hasOwnProperty('globals')
				  				  ){
				  				  
					  			  app.components.resetAll();
								  _.each(data.components,function(value, key, list){
									  app.components.add({id:key,label:value.label,type:value.type,data:value.data,global:value.global});
								  });
								  app.components.saveAll();
								  
								  app.navigation.resetAll();
								  _.each(data.navigation,function(value, key, list){
									  app.navigation.add({id:key,component_id:key,data:{}});
								  });
								  app.navigation.saveAll();
								  
								  globals_keys.resetAll();
								  _.each(data.globals,function(global, key, list){
									  var items = new Items.Items({global:key});
									  items.resetAll();
									  _.each(global,function(item, id){
										  items.add(_.extend({id:id},item));
									  });
									  items.saveAll();
									  app.globals[key] = items;
									  globals_keys.add({id:key});
								  });
								  globals_keys.saveAll();
								  
								  Utils.log('Components, navigation and globals retrieved from online.',{components:app.components,navigation:app.navigation,globals:app.globals});

								  cb_ok();
				  			  }else{
				  				  app.triggerError(
				  						'synchro:wrong-answer',
							  			{type:'ws-data',where:'app::syncWebService',message: 'Wrong "synchronization" web service answer',data: data},
				    		  		    cb_error
							  	  );
				  			  }
				  			  
				  		  }else if( data.result.status == 0 ){
				  			  app.triggerError(
				  					'synchro:ws-return-error',
						  			{type:'ws-data',where:'app::syncWebService',message: 'Web service "synchronization" returned an error : ['+ data.result.message +']', data:data},
			    		  		    cb_error
						  	  );
				  		  }else{
				  			  app.triggerError(
				  					'synchro:wrong-status',
						  			{type:'ws-data',where:'app::syncWebService',message: 'Wrong web service answer status',data: data},
			    		  		    cb_error
						  	  );
				  		  }
				  	  }else{
				  		  app.triggerError(
				  				'synchro:wrong-format',
					  			{type:'ws-data',where:'app::syncWebService',message: 'Wrong web service answer format',data: data},
		    		  		    cb_error
					  	  );
				  	  }
					  
				},
			  	error : function(jqXHR, textStatus, errorThrown){
			  		app.triggerError(
			  			'synchro:ajax',
			  			{type:'ajax',where:'app::syncWebService',message: textStatus + ': '+ errorThrown, data:{url: Config.wp_ws_url + ws_url, jqXHR:jqXHR, textStatus:textStatus, errorThrown:errorThrown}},
    		  		    cb_error
			  		);
			  	}
		  });
	  };
	  
	  app.getPostComments = function(post_id,cb_ok,cb_error){
    	  var token = getToken('comments-post');
    	  var ws_url = token +'/comments-post/'+ post_id;
    	  
    	  var comments = new Comments.Comments;
    	  
    	  var post = app.globals['posts'].get(post_id);
    	  
    	  if( post != undefined ){
	    	  $.ajax({
	    		  type: 'GET',
	    		  url: Config.wp_ws_url + ws_url,
	    		  success: function(data) {
		    		  	_.each(data.items,function(value, key, list){
		    		  		comments.add(value);
		    	  		});
		    		  	cb_ok(comments,post);
		    	  },
		    	  error: function(jqXHR, textStatus, errorThrown){
		    		  app.triggerError(
		    			  'comments:ajax',
		    			  {type:'ajax',where:'app::getPostComments',message: textStatus + ': '+ errorThrown,data:{url: Config.wp_ws_url + ws_url, jqXHR:jqXHR, textStatus:textStatus, errorThrown:errorThrown}},
	    		  		  cb_error
	        		  );
		    	  }
	    	  });
    	  }else{
    		  app.triggerError(
    			  'comments:post-not-found',
    			  {type:'not-found',where:'app::getPostComments',message:'Post '+ post_id +' not found.'},
		  		  cb_error
    		  );
    	  }
      };
      
      app.getMoreOfComponent = function(component_id,cb_ok,cb_error){
    	  var component = app.components.get(component_id);
    	  if( component ){
    		  
    		  var component_data = component.get('data');
    		  
    		  if( component_data.hasOwnProperty('ids') ){
    		  
		    	  var token = getToken('component');
		    	  var ws_url = token +'/component/'+ component_id;
		    	  
		    	  var last_item_id = _.last(component_data.ids);
		    	  ws_url += '?before_item='+ last_item_id;
		    	  
		    	  $.ajax({
		    		  type: 'GET',
		    		  url: Config.wp_ws_url + ws_url,
		    		  success: function(answer) {
			    		  if( answer.result && answer.result.status == 1 ){
			    			  if( answer.component.slug == component_id ){
			    				  var global = answer.component.global;
			    				  if( app.globals.hasOwnProperty(global) ){
			    					  
			    					  var new_ids = _.difference(answer.component.data.ids,component_data.ids);
			    					  
			    					  component_data.ids = _.union(component_data.ids,answer.component.data.ids); //merge ids
			    					  component.set('data',component_data);
			    					  
				    				  var current_items = app.globals[global];
									  _.each(answer.globals[global],function(item, id){
										  current_items.add(_.extend({id:id},item)); //auto merges if "id" already in items
									  });
									  
			    					  var new_items = [];
									  _.each(new_ids,function(item_id){
										  new_items.push(current_items.get(item_id));
				          	  		  });
									  
									  var nb_left = component_data.total - component_data.ids.length;
									  var is_last = !_.isEmpty(answer.component.data.query.is_last_page) ? true : nb_left <= 0;  
									  
									  Utils.log('More content retrieved for component',{component_id:component_id,new_ids:new_ids,new_items:new_items,component:component});
									  
									  cb_ok(new_items,is_last,{nb_left:nb_left,new_ids:new_ids,global:global,component:component});
									  
			    				  }else{
				    				  app.triggerError(
				    					  'getmore:global-not-found',
				    					  {type:'not-found',where:'app::getMoreOfComponent',message:'Global not found : '+ global},
								  		  cb_error
						    		  );
				    			  }
			    			  }else{
							  	  app.triggerError(
							  		  'getmore:wrong-component-id',
							  		  {type:'not-found',where:'app::getMoreOfComponent',message:'Wrong component id : '+ component_id},
							  		  cb_error
					    		  );
			    			  }
			    		  }else{
						  	  app.triggerError(
						  		  'getmore:ws-return-error',
						  		  {type:'web-service',where:'app::getMoreOfComponent',message:'Web service "component" returned an error : ['+ answer.result.message +']'},
						  		  cb_error
				    		  );
			    		  }
			    	  },
			    	  error: function(jqXHR, textStatus, errorThrown){
			    		  app.triggerError(
			    			  'getmore:ajax',
			    			  {type:'ajax',where:'app::getMoreOfComponent',message: textStatus + ': '+ errorThrown,data:{url: Config.wp_ws_url + ws_url, jqXHR:jqXHR, textStatus:textStatus, errorThrown:errorThrown}},
			    			  cb_error
			    		  );
			    	  }
		    	  });
    		  }
    	  }
      };
	  
      app.alertNoContent = function(){
    	  vent.trigger('info:no-content');
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
	    			  break;
	    		  case 'page':
	    			  var data = component.get('data');
	    			  var component_global = component.get('global');
	    			  var global = app.globals[component_global];
	    			  if( global ){
	    				  var page = global.get(data.id);
	    				  if( page ){
	    					  component_data = {
	    							  type: component_type,
	        						  view_data: {item:page,global:component_global},
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
    		  }
    	  };
    	  
    	  if( component_data != null ){
    		  component_data = _.extend({id:component.get('id'),label:component.get('label'),global:component.get('global')},component_data);
    	  }

    	  return component_data;
      };
      
	  return app;
	  
});
