define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags');

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
        	var renderedContent = this.template({ 
				post : this.item.toJSON(), 
				TemplateTags : ThemeTplTags
			});
            $(this.el).html(renderedContent); 
            return this;
        }
        
    });

});
