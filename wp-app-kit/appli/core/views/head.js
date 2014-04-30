define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
        Tpl                 = require('text!theme/head.html');

    return Backbone.View.extend({
    	
    	initialize : function(args) {
    		this.template = _.template(Tpl);
        },

        render : function() {
        	var renderedContent = this.template({title:Config.app_title, theme:'themes/'+ Config.theme});
            $('head').prepend(renderedContent); 
            return this;
        }
        
    });

});
