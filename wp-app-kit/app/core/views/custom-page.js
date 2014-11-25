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
        Utils               = require('core/app-utils');

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
        		var renderedContent = this.template({ 
					data : this.custom_page_data, 
					TemplateTags : ThemeTplTags 
				});
        		$(this.el).html(renderedContent);
        	}
            return this;
        }
        
    });

});
