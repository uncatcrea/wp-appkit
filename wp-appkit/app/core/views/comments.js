define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags');

    return TemplateView.extend({
    	
    	className: "app-screen",
    	
    	initialize : function(args) {
            
    		this.setTemplate('comments');
           
            _.bindAll(this,'render');
            
    		this.comments = args.comments;
    		
    		this.post = args.post;
        },

        render : function() {
        	var renderedContent = this.template({ 
				comments : this.comments.toJSON(), 
				post : this.post.toJSON(), 
				TemplateTags : ThemeTplTags
			});
            $(this.el).html(renderedContent); 
            return this;
        }
        
    });

});
