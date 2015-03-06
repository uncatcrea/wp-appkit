define(function (require) {

    "use strict";

    var Backbone                 = require('backbone'),
        Config                   = require( 'root/config' );

    require( 'localstorage' );

    var Favorite = Backbone.Model.extend({
    	defaults : {
    		id : "",
            add_date : ""
        }
    });

    var Favorites = Backbone.Collection.extend({
    	model : Favorite,
    	localStorage: null,
    	initialize : function(args){
    		this.localStorage = new Backbone.LocalStorage( "Favorites-" + Config.app_slug );
            this.bind( 'remove', this.onRemove );
            this.bind( 'add', this.onAdd );
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
        },
        onAdd : function( model ) {
            model.set( 'add_date', Math.round( Date.now() / 1000 ) );
            this.sort();
        },
        onRemove : function( model ) {
            model.destroy();
        },
        comparator : function( favorite ) {
            return -favorite.get( 'add_date' );
        }
    });

    return Favorites;

});