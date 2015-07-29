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
            
    		this.setTemplate('comments');
           
            _.bindAll(this,'render','update_comments');
            
    		this.comments = args.comments;
    		
    		this.post = args.post;
        },

        render : function() {
			
			var template_args = { 
				comments : this.comments.toJSON(), 
				post : this.post.toJSON(), 
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
			template_args = Hooks.applyFilters( 'template-args', template_args, ['comments',this.template_name,this] );
			
        	var renderedContent = this.template(template_args);
            $(this.el).html(renderedContent); 
            return this;
        },
		
		update_comments : function( comments ) {
			this.comments.reset( comments );
		}
        
    });

});
