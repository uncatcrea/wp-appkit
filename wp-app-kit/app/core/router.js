define(function (require) {
 
    "use strict";
 
    var Backbone       = require('backbone'),
    	Utils          = require('core/app-utils'),
        RegionManager  = require("core/region-manager");
    
    var default_route = '';
    
    return Backbone.Router.extend({
 
        routes: {
            "": "default_route",
            "single/:global/:id" : "single",
            "page/:component_id/:page_id" : "page",
            "comments-:post_id" : "comments",
            "component-:id" : "component",
            "custom-page" : "custom_page"
        },
 
        setDefaultRoute : function(_default_route){
    		default_route = _default_route;
    	},
    	
        default_route: function(){
        	this.navigate(default_route, {trigger: true});
        },
        
        component: function (component_id) {
        	var _this = this;
        	require(["core/app"],function(App){
        		var component = App.getComponentData(component_id);
        		if( component ){
        			switch( component.type ){
        				case 'posts-list':
        					App.setQueriedPage({page_type:'list',component_id:component_id,item_id:0,global:component.global,data:component.data});
        					require(["core/views/archive"],function(ArchiveView){
        						var view = new ArchiveView(component.view_data);
        						view.checkTemplate(function(){
    								RegionManager.show(view);
    							});
	        				});
        					break;
        				case 'page':
        					//Directly redirect to "page" route :
        					_this.navigate('page/'+ component_id +'/'+ component.data.root_id, {trigger: true});
        					break;
        				case 'hooks-list':
        				case 'hooks-no-global':
        					App.setQueriedPage({page_type:'custom-component',component_id:component_id,item_id:0,global:component.global,data:component.data});
        					require(["core/views/custom-component"],function(CustomComponentView){
        						var view = new CustomComponentView({component:component});
        						view.checkTemplate(function(){
    								RegionManager.show(view);
    							});
        					});
        					break;
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
        	require(["core/app"],function(App){
	        	var global = App.globals[item_global];
	        	if( global ){
		        	var item = global.get(item_id);
		        	if( item ){
		        		var item_data = item_global == 'posts' ? {post:item.toJSON()} : {item:item.toJSON()};
		        		App.setQueriedPage({page_type:'single',component_id:'',item_id:parseInt(item_id),global:item_global,data:item_data});
		        		require(["core/views/single"],function(SingleView){
		        			var view = new SingleView({item:item,global:item_global});
    						view.checkTemplate(function(){
								RegionManager.show(view);
							});
		        		});
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
			        		
			        		App.setQueriedPage({page_type:'page',component_id:component_id,item_id:parseInt(page_id),global:item_global,data:item_data});
			        		require(["core/views/page"],function(PageView){
			        			var view = new PageView({item:item,global:item_global});
	    						view.checkTemplate(function(){
									RegionManager.show(view);
								});
			        		});
		        		}else{
			        		Utils.log('Error : router : page route : component with id "'+ component_id +'" not found');
		        			App.router.default_route();
		        		}
		        	}else{
		        		Utils.log('Error : router : page route : item with id "'+ page_id +'" not found in global "'+ item_global +'".');
	        			App.router.default_route();
	        		}
	        	}else{
	        		Utils.log('Error : router : page route : global "'+ item_global +'" not found.');
	    			App.router.default_route();
	    		}
        	});
        },
        
        comments: function (post_id) {
        	require(["core/app","core/views/comments"],function(App,CommentsView){
        		App.setQueriedPage({page_type:'comments',component_id:'',item_id:parseInt(post_id)});
        		RegionManager.startWaiting();
	        	App.getPostComments(
	        		post_id,
	        		function(comments,post){
	        			RegionManager.stopWaiting();
	        			//Check if we are still on the right post :
	        			var current_page = App.getCurrentPageData();
	        			if( current_page.page_type == 'single' && current_page.item_id == post_id ){
		        			var view = new CommentsView({comments:comments,post:post});
    						view.checkTemplate(function(){
								RegionManager.show(view);
							});
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
        	require(["core/app","core/views/custom-page"],function(App,CustomPageView){
        		var current_custom_page = App.getCurrentCustomPage();
        		if( current_custom_page !== null ){
        			App.setQueriedPage({page_type:'custom-page',component_id:'',item_id:0,data:current_custom_page});
	        		var view = new CustomPageView({custom_page:current_custom_page});
	        		view.checkTemplate(function(){
						RegionManager.show(view);
					});
        		}
        	});
        }
        
    });
 
});