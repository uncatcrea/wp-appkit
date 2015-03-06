define(function (require) {

    "use strict";

    var Backbone                 = require('backbone');
    require('localstorage');

    var Global = Backbone.Model.extend({
    	defaults : {
    		id : ""
        }
    });

    var Globals = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage("Globals"),
    	model : Global,
    	saveAll : function(){
       	 	this.map(function(global){global.save();});
        },
        resetAll : function(){
        	var length = this.length; 
        	for (var i = length - 1; i >= 0; i--) { 
        		this.at(i).destroy(); 
        	} 
        	this.reset();
        }
    });
    
    return Globals;

});