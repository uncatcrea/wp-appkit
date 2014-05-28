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
    	initialize : function(args){
    		this.localStorage = new Backbone.LocalStorage("Items-"+args.global);
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