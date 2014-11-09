define(function (require) {

    "use strict";

    var Backbone                 = require('backbone'),
        Config                   = require( 'root/config' );

    require( 'localstorage' );

    var Favorite = Backbone.Model.extend({
    	defaults : {
    		id : ""
        }
    });

    var Favorites = Backbone.Collection.extend({
    	model : Favorite,
    	localStorage: null,
    	initialize : function(args){
    		this.localStorage = new Backbone.LocalStorage( "Favorites-" + Config.app_slug );
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

    return Favorites;

});