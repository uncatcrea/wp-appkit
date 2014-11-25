define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
        Tpl                 = require('text!theme/layout.html'),
		ThemeTplTags		= require('core/theme-tpl-tags');

    var contains_header = false;
    
    return Backbone.View.extend({
    	
    	initialize : function(args) {
    		contains_header = Tpl.match(/<%=\s*header\s*%>/) !== null;
    		this.template = _.template(Tpl);
        },
        
        render : function() {
        	var renderedContent = this.template({ 
        		app_title : Config.app_title, 
        		header : '<div id="app-header"></div>', 
        		menu : '<div id="app-menu"></div>', 
        		content : '<div id="app-content-wrapper"></div>',
				TemplateTags : ThemeTplTags
        	});
            $(this.el).html(renderedContent); 
            return this;
        },
        
        containsHeader : function(){
        	return contains_header;
        }
        
    });

});
