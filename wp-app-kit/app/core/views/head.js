define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
		Addons              = require('core/addons'),
        Tpl                 = require('text!theme/head.html');

    return Backbone.View.extend({
    	
    	initialize : function(args) {
    		this.template = _.template(Tpl);
        },

        render : function() {
        	var renderedContent = this.template({app_title:Config.app_title, theme_path:'themes/'+ Config.theme});
			
			renderedContent = Addons.getCss('before-theme') + renderedContent + Addons.getCss('after-theme');
			
            $('head').prepend(renderedContent); 
			
            return this;
        }
        
    });

});
