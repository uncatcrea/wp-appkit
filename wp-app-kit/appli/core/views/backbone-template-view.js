define(function (require) {

    "use strict";

    //Mother view for all theme views with hookable template (archive, single, comments, 
    //custom-page and custom-component).
    //Implements specific pattern to replace the classical Backbone.View.extend, so that we can
    //extend our TemplateView without having to call the parent view's initialize() method.
    
    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Hooks               = require('core/lib/hooks'),
        Utils               = require('core/app-utils'),
        App                 = require('core/app');

    var TemplateView = function(args) {
    	//This is the equivalent to the initialize() method
    	
    	_.bindAll(this,'checkTemplate','setTemplate');

        Backbone.View.apply(this, [args]);
    };

    _.extend(TemplateView.prototype, Backbone.View.prototype, {
    	
    	page_data: null,
    	template_name: 'default',
    	template: null,
    	
    	/**
    	 * Called in router to validate the view's template before showing the page.
    	 */
    	checkTemplate : function(cb_ok,cb_error){
        	var _this = this;
        	require(['text!theme/'+ this.template_name +'.html'],
  					function(tpl){
  						_this.template = _.template(tpl);
  						cb_ok();
  	      		  	},
  	      		  	function(error){
  	      		  		Utils.log('Error : view template "'+ _this.template_name +'.html" not found in theme');
  	      		  		if( cb_error ){
  	      		  			cb_error();
  	      		  		}
  	      		  	}
  			);
        },
        
        /**
         * Called in children extended views, so that we can filter the template to use
         * for rendering.
         */
        setTemplate : function(default_template){
        	
        	var template = default_template != undefined ? default_template : '';
        	
    		template = Hooks.applyFilter('template',template,[App.getQueriedPage()]);

    		if( template != ''){
    			this.template_name = template;
    		}
        }
        
    });

    TemplateView.extend = Backbone.View.extend;
    
    return TemplateView;
});
