define(function (require) {

    "use strict";

    var Backbone                 = require('backbone'),
		Config					 = require('root/config'),
    	_                   	 = require('underscore');
    
    require('localstorage');

    var Component = Backbone.Model.extend({
    	defaults : {
    		id : "",
    		label : "",
            type : "",
            data : "",
            global : ""
        }
    });

    var Components = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage("Components-"+ Config.app_slug),
    	model : Component,
    	saveAll : function(){
       	 	this.map(function(component){component.save();});
        },
        resetAll : function(){
        	var length = this.length; 
        	for (var i = length - 1; i >= 0; i--) { 
        		this.at(i).destroy(); 
        	} 
        	this.reset();
        }
    });
    
    return Components;

});