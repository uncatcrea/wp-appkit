define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
		ThemeTplTags		= require('core/theme-tpl-tags'),
        Tpl                 = require('text!theme/head.html');

    return Backbone.View.extend({
    	
    	initialize : function(args) {
    		this.template = _.template(Tpl);
        },

        render : function() {
        	var renderedContent = this.template({
				app_title : Config.app_title,
				theme_path : 'themes/'+ Config.theme, //Keep this for legacy but now we use TemplateTags.getThemePath()
				TemplateTags : ThemeTplTags
			});
            $('head').prepend(renderedContent); 
            return this;
        }
        
    });

});
