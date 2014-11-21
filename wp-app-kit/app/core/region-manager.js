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
	    var el = "#app-content-wrapper";
	    
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
		    		layoutView.render();
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
		    		App.navigation.each(function(element, index){
		    			var component = App.components.get(element.get('component_id'));
		    			if( component ){
		    				menuView.addItem(component.get('id'),component.get('type'),component.get('label'),element.get('options'));
		    			}
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
		    		vent.trigger('menu:refresh',App.getCurrentScreenData(),menuView);
		    		Utils.log('Render navigation',{menu_view:menuView,force_reload:force_reload});
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
			    vent.trigger('header:render',App.getCurrentScreenData(),headerView);
	    	}
	    };
	    
	    var closeView = function (view) {
	        if( view ){
	        	if( view.isStatic ){
	        		var static_screens_wrapper = $('#app-static-screens');
	        		if( !static_screens_wrapper.find('[data-viewid='+ view.cid +']').length ){
	        			$(view.el).attr('data-viewid',currentView.cid);
	        			static_screens_wrapper.append(view.el);
	        		}
	        	}else{
		        	if( view.close ){
		        		view.close();
		        	}
	        	}
	        }
	    };
	 
	    var openView = function (view) {
	    	
	    	if( !view.isStatic || !$(view.el).html().length ){
	    		if( view.isStatic != undefined && view.isStatic ){
	    			Utils.log('Open static view',{screen_data:App.getCurrentScreenData(),view:view});
	    		}else{
	    			Utils.log('Open view',{screen_data:App.getCurrentScreenData(),view:view});
	    		}
				view.render();
				
				var $el = $(el);
				
				vent.trigger('screen:before-transition',App.getCurrentScreenData(),currentView);
				
				var custom_rendering = App.getParam('custom-screen-rendering');
				if( custom_rendering ){
					Hooks.doAction(
						'screen-transition',
						[$el,$('div:first-child',$el),$(view.el),App.getCurrentScreenData(),App.getPreviousScreenMemoryData()]
					).done(function(){
						 renderSubRegions();
						 vent.trigger('screen:showed',App.getCurrentScreenData(),currentView);
					}).fail(function(){
						 renderSubRegions();
						 vent.trigger('screen:showed:failed',App.getCurrentScreenData(),currentView);
					});
				}else{
					$el.empty().append(view.el);
					renderSubRegions();
					vent.trigger('screen:showed',App.getCurrentScreenData(),currentView);
				}
				
				if(view.onShow) {
	     	        view.onShow();
	     	   	}
	    	}else{
	    		//TODO : we should apply custom rendering logic here too...
	    		Utils.log('Re-open existing static view',{view:view});
	    		$(el).empty().append(view.el);
	    		renderSubRegions();
				vent.trigger('screen:showed',App.getCurrentScreenData(),currentView);
	    	}
	    	
	    };
	    
	    var showView = function(view,force_no_waiting){
	    	
	    	var no_waiting = force_no_waiting != undefined && force_no_waiting == true;

	    	if( !view.isStatic || !$(view.el).html().length ){

	    		if( !no_waiting ){
	    			region.startWaiting();
	    		}
		    	
		    	if( view.loadViewData ){
			    	view.loadViewData(function(){
			    		showSimple(view);
			    		if( !no_waiting ){
			    			region.stopWaiting();
			    		}
			    	});
		    	}else{
		    		showSimple(view);
		    		if( !no_waiting ){
		    			region.stopWaiting();
		    		}
		    	}
		    	
	    	}else{
	    		if( !no_waiting ){
	    			showSimple(view);
	    		}
	    	}
	    };
	    
	    var showSimple = function(view) {
			
	    	var custom_rendering = App.getParam('custom-screen-rendering');
	    	
	    	if( currentView ){
	    		if( !custom_rendering ){ //Custom rendering must handle views closing by itself (on screen:leave)
	    			closeView(currentView);
	    		}
	    	}
	    	
	    	currentView = view;
		    openView(currentView);
	    };
	    
	    region.show = function(view,force_flush,force_no_waiting) {
	    	vent.trigger('screen:leave',App.getCurrentScreenData(),App.getQueriedScreen(),currentView);
	    	
			App.addQueriedScreenToHistory(force_flush);
			
	    	showView(view,force_no_waiting);
	    };
	    
	    region.getCurrentView = function(){
	    	return currentView;
	    };
	    
	    region.startWaiting = function(){
	    	vent.trigger('waiting:start',App.getCurrentScreenData(),currentView);
	    };
	    
	    region.stopWaiting = function(){
	    	vent.trigger('waiting:stop',App.getCurrentScreenData(),currentView);
	    };
	    
	    return region;
	    
	})(Backbone, $, _);
	
	return RegionManager;
});
