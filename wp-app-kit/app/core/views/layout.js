define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
		Addons              = require('core/addons-internal'),
        Tpl                 = require('text!theme/layout.html'),
		ThemeTplTags		= require('core/theme-tpl-tags');

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
			data = _.extend(data, addons_data);
			
        	var rendered_content = this.template(data);
			
            $(this.el).html(rendered_content); 
            return this;
        },
        
        containsHeader : function(){
        	return contains_header;
        }
        
    });

});
