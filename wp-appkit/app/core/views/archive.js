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
            
    		this.setTemplate('archive');
           
            _.bindAll(this,'render','addPosts');
            
    		this.posts = args.posts;
    		
    		this.title = args.title;
    		this.total = args.total;
        },

        render : function() {
			var template_args  = { 
				posts : this.posts.toJSON(), 
				list_title: this.title, 
				total:this.total, 
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
			template_args = Hooks.applyFilters( 'template-args', template_args, ['archive',this.template_name,this] );
			
        	var renderedContent = this.template(template_args);
            $(this.el).html(renderedContent); 
            return this;
        },
        
        addPosts : function(posts){
        	var _this = this;
        	_.each(posts,function(post){
        		_this.posts.add(post);
	  		});
        }
        
    });

});
