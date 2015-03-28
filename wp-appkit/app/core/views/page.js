define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags'),
		Hooks               = require('core/lib/hooks');

    return TemplateView.extend({
    	
    	className: "app-screen",
    	
    	initialize : function(args) {
            
    		this.setTemplate('page','single');
           
            _.bindAll(this,'render');
            
    		this.item = args.item;
    		this.global = args.hasOwnProperty('global') ? args.global : 'pages';
    		
    		this.item.on('change', this.render);
        },

        render : function() {
			
			var template_args = { 
				post : this.item.toJSON(), 
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
			template_args = Hooks.applyFilters( 'template-args', template_args, ['page',this.template_name,this] );
			
        	var renderedContent = this.template(template_args);
            $(this.el).html(renderedContent); 
            return this;
        }
        
    });

});
