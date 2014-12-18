define(function (require) {

    "use strict";

    var $                   = require('jquery'),
		_                   = require('underscore'),
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
			var template_args = {};
			
			if( this.global == 'posts' || this.global == 'pages' ){
				template_args.post = this.item.toJSON();
			}else{
				template_args.item = this.item.toJSON();
			}	
			
			template_args.TemplateTags = ThemeTplTags;
			
        	var renderedContent = this.template(template_args);
			
            $(this.el).html(renderedContent); 
            return this;
        }
        
    });

});
