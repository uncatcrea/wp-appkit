define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        TemplateView        = require('core/views/backbone-template-view'),
        ThemeTplTags		= require('core/theme-tpl-tags'),
        Hooks               = require('core/lib/hooks'),
        Utils               = require('core/app-utils');

    
    return TemplateView.extend({
    	
    	className: "app-screen",
    	
    	component: null,
    	
    	initialize : function(args) {
    		this.component = args.component;
    		var template = this.component.data.hasOwnProperty('template') ? this.component.data.template : '';
    		this.setTemplate(template);
    		_.bindAll(this,'render');
        },
        
        render : function() {
        	if( this.template ){
	        	var view_data = _.extend(this.component.view_data,{
					component: this.component, 
					TemplateTags : ThemeTplTags 
				});
	    		var renderedContent = this.template(view_data);
	    		$(this.el).html(renderedContent);
	        }
            return this;
        }
        
    });

});
