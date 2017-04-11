define(function (require) {

    "use strict";

    var Backbone                 = require('backbone');

    var Item = Backbone.Model.extend({
    	defaults : {
    		id : ""
        }
    });

    var Items = Backbone.Collection.extend({
    	model : Item,
    	localStorage: null,
    	initialize : function(models,options){
			options = options || {};
			var global = options.global ? options.global : 'no-global';
    		this.localStorage = new Backbone.LocalStorage("Items-"+global);
    	},
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
    
    var ItemsSlice = Backbone.Collection.extend({
    	model : Item,
    });
    
    return {Item:Item,Items:Items,ItemsSlice:ItemsSlice};

});