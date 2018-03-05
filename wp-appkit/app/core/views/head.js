define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
		ThemeTplTags		= require('core/theme-tpl-tags'),
		Addons              = require('core/addons-internal'),
        Tpl                 = require('text!theme/head.html'),
		Hooks               = require('core/lib/hooks');

    return Backbone.View.extend({
    	
    	initialize : function(args) {
			Tpl = Addons.getCss('before') + Tpl + Addons.getCss('after');
    		this.template = _.template(Tpl);
        },

        render : function() {
        	
			var template_args = {
				app_title : Config.app_title,
				theme_path : 'themes/'+ Config.theme, //Keep this for legacy but now we use TemplateTags.getThemePath()
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
			template_args = Hooks.applyFilters( 'template-args', template_args, ['head','head',this] );
			
			var renderedContent = this.template(template_args);
			
			$('head').append(renderedContent); 
            return this;
        }
        
    });

});
