define(function (require) {

    "use strict";

    var Backbone                 = require('backbone');
    require('localstorage');

    var NavigationItem = Backbone.Model.extend({
    	defaults : {
    		id : "",
    		component_id : "",
            options : {}
        }
    });

    var NavigationItems = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage("Navigation"),
    	model : NavigationItem,
    	saveAll : function(){
       	 	this.map(function(item){item.save();});
        },
        resetAll : function(){
        	var length = this.length; 
        	for (var i = length - 1; i >= 0; i--) { 
        		this.at(i).destroy(); 
        	} 
        	this.reset();
        }
    });
    
    return NavigationItems;

});