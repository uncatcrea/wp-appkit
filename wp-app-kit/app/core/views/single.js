define(function (require) {

    "use strict";

    var $                   = require('jquery'),
		_                   = require('underscore'),
		Config              = require('root/config'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags');

    return TemplateView.extend({
    	
    	className: "app-screen",
    	
    	initialize : function(args) {
            
    		this.setTemplate('single');
           
            _.bindAll(this,'render');
            
    		this.item = args.item;
    		this.global = args.hasOwnProperty('global') ? args.global : 'posts';
    		
    		this.item.on('change', this.render);
        },

        render : function() {
        	var renderedContent = this.template({ 
				post : this.item.toJSON(), 
				TemplateTags : ThemeTplTags, 
				theme_path : 'themes/'+ Config.theme 
			});
            $(this.el).html(renderedContent); 
            return this;
        }
        
    });

});
