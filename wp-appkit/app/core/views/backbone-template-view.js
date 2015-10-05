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
    	
    	screen_data: null,
    	template_name: 'default',
    	fallback_template_name: '',
    	template: null,
		is_static: false,
    	
    	/**
    	 * Called in router to validate the view's template before showing the screen.
    	 */
    	checkTemplate : function(cb_ok,cb_error){
        	var _this = this;
			
			var template_file = 'text!';
			
			if( this.template_name.match(/^addons\//g) ) {
				template_file += this.template_name;
			} else {
				template_file += 'theme/'+ this.template_name;
			}
					
			template_file += '.html';
			
			var fallback_template_failed = function( error ) {
				Utils.log('Error : view templates "'+ _this.template_name +'.html" and fallback "'+ _this.fallback_template_name +'.html" not found in theme');
				if( cb_error ){
					cb_error();
				}
			};
			
			var default_template_failed = function( error ) {
				if( _this.fallback_template_name != '' ){
					Utils.log('View template "'+ _this.template_name +'.html" not found in theme : load fallback template "'+ _this.fallback_template_name +'"');
					require(['text!theme/'+ _this.fallback_template_name +'.html'],
						function(tpl){
							if( tpl.length ) {
								_this.template = _.template(tpl);
								cb_ok();
							} else {
								//On mobile devices (but not in browsers) the require(['text!template'])
								//is successful even if the template is not there...
								//So we solve the problem by checking if tpl is empty, and
								//if it is we consider that the template was not found :
								fallback_template_failed();
							}
						},
						function( fallback_error){
							fallback_template_failed( fallback_error );
						}
					);
				}else{
					Utils.log('Error : view template "'+ _this.template_name +'.html" not found in theme');		
					if( cb_error ){		
						cb_error();		
					}
				}
			};
			
        	require([template_file],
  					function(tpl){
						if( tpl.length ) {
							_this.template = _.template(tpl);
							cb_ok();
						} else {
							//On mobile devices (but not in browsers) the require(['text!template'])
							//is successful even if the template is not there...
							//So we solve the problem by checking if tpl is empty, and
							//if it is we consider that the template was not found :
							default_template_failed();
						}
  	      		  	},
  	      		  	function( error ){
  	      		  		default_template_failed( error );
  	      		  	}
  			);
        },
        
        /**
         * Called in children extended views, so that we can filter the template to use
         * for rendering.
         */
        setTemplate : function(default_template,fallback_template){
        	
        	var template = default_template != undefined ? default_template : '';
        	
    		template = Hooks.applyFilters('template',template,[App.getQueriedScreen()]);

    		if( template != ''){
    			this.template_name = template;
    			
    			if( fallback_template != undefined && fallback_template != ''){
        			this.fallback_template_name = fallback_template;
        		}
    		}
    		
        }
        
    });

    TemplateView.extend = Backbone.View.extend;
    
    return TemplateView;
});
