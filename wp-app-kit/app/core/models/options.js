define(function (require) {

    "use strict";

    var Backbone                 = require('backbone'),
    	_                   	 = require('underscore'),
        Config                   = require( 'root/config' );

    require('localstorage');

    var Option = Backbone.Model.extend({
    	defaults : {
    		id : "",
            value : ""
        }
    });

    var Options = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage( "Options-" + Config.app_slug ),
    	model : Option,
    	saveAll : function(){
       	 	this.map(function(option){option.save();});
        },
        resetAll : function(){
        	var length = this.length;
        	for (var i = length - 1; i >= 0; i--) {
        		this.at(i).destroy();
        	}
        	this.reset();
        }
    });

    return Options;

});