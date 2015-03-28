define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags'),
        Hooks               = require('core/lib/hooks'),
        Utils               = require('core/app-utils'),
		Hooks               = require('core/lib/hooks');
    
    return TemplateView.extend({
    	
    	className: "app-screen",
    	
    	component: null,
    	
    	initialize : function(args) {
    		this.component = args.component;
    		var template = this.component.data.hasOwnProperty('template') ? this.component.data.template : '';
    		this.setTemplate(template);
    		_.bindAll(this,'render');
        },
        
        render : function() {
        	if( this.template ){
	        	var template_args = _.extend(this.component.view_data,{
					component: this.component, 
					TemplateTags : ThemeTplTags 
				});
				
				/**
				 * Use this 'template-args' filter to pass custom data to your
				 * templates.
				 * 
				 * @param template_args : JSON object : the default template data to filter
				 * Params passed to the filter : 
				 * - view type : String
				 * - template name : String
				 * - view object : Backbone view object
				 */
				template_args = Hooks.applyFilters( 'template-args', template_args, ['custom-component',this.template_name,this] );
				
	    		var renderedContent = this.template(template_args);
	    		$(this.el).html(renderedContent);
	        }
            return this;
        }
        
    });

});
