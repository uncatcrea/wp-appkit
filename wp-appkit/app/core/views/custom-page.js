/**
 * Custom pages are pages created dynamically in javascript in the app theme.
 * They're not WordPress pages.
 */
define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags'),
        Utils               = require('core/app-utils'),
		Hooks               = require('core/lib/hooks');

    return TemplateView.extend({
    	
    	className: "app-screen",
    	
    	custom_page_data : null,
    	
    	initialize : function(args) {
    		
    		this.custom_page = args.custom_page;
    		
    		this.custom_page_data = args.custom_page.get('data');
    		
    		this.setTemplate(args.custom_page.get('template'));
    		
            _.bindAll(this,'render');
        },

        render : function() {
        	if( this.custom_page_data !== null ){
				
				var template_args =	{ 
					data : this.custom_page_data, 
					TemplateTags : ThemeTplTags 
				};
				
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
				template_args = Hooks.applyFilters( 'template-args', template_args, ['custom-page',this.template_name,this] );
				
        		var renderedContent = this.template(template_args);
        		$(this.el).html(renderedContent);
        	}
            return this;
        }
        
    });

});
