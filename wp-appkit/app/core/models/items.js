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
    	initialize : function(args,options){
			var global = args.global ? args.global : options.global; //TODO : Fix this definitely by removing args.global for all new Items.Items!!
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