define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
		Addons              = require('core/addons-internal'),
        Tpl                 = require('text!theme/layout.html'),
		ThemeTplTags		= require('core/theme-tpl-tags'),
		Hooks               = require('core/lib/hooks');

    var contains_header = false;
    
    return Backbone.View.extend({
    	
    	initialize : function(args) {
			Tpl = Addons.getHtml('layout','before') + Tpl + Addons.getHtml('layout','after');
			contains_header = Tpl.match(/<%=\s*header\s*%>/) !== null;
    		this.template = _.template(Tpl);
        },
        
        render : function() {
			
			var data = { 
        		app_title : Config.app_title, 
        		header : '<div id="app-header"></div>', 
        		menu : '<div id="app-menu"></div>', 
        		content : '<div id="app-content-wrapper"></div>',
				TemplateTags : ThemeTplTags
        	};
			
			var addons_data = Addons.getHtmlData('layout');
			var template_args = _.extend(data, addons_data);
			
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
			template_args = Hooks.applyFilters( 'template-args', template_args, ['layout','layout',this] );
			
        	var rendered_content = this.template(template_args);
			
            $(this.el).html(rendered_content); 
            return this;
        },
        
        containsHeader : function(){
        	return contains_header;
        }
        
    });

});
