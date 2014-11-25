define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags');

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
        	var renderedContent = this.template({ 
				posts : this.posts.toJSON(), 
				list_title: this.title, 
				total:this.total, 
				TemplateTags : ThemeTplTags
			});
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
