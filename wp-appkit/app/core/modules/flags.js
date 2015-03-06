define( function( require ) {

	"use strict";

	var Backbone = require( 'backbone' ),
		Config = require( 'root/config' ),
		_ = require( 'underscore' );

	require( 'localstorage' );

	var FlagModel = Backbone.Model.extend( {
		defaults: {
			id: "",
			data: {}
		}
	} );
	
	var FlagsCollection = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage("Flags-"+ Config.app_slug),
    	model : FlagModel,
        resetAll : function(){
        	var length = this.length; 
        	for (var i = length - 1; i >= 0; i--) { 
        		this.at(i).destroy(); 
        	} 
        	this.reset();
        }
    });

	var FlagsInstance = new FlagsCollection();
	FlagsInstance.fetch();

	var Flags = {
		raise: function(flag_slug, data) {
			if( _.isEmpty(data) ){
				data = {};
			}
			var flag = FlagsInstance.add({id: flag_slug, data: data});
			flag.save();
		},
		lower: function(flag_slug) {
			var flag = FlagsInstance.get(flag_slug);
			if( flag ){
				flag.destroy();
				FlagsInstance.remove(flag_slug);
			}
		},
		setData: function(flag_slug, data) {
			var flag = FlagsInstance.get(flag_slug);
			if( !_.isEmpty(flag) ){
				flag.set({data: data});
				flag = FlagsInstance.set(flag, {add: false, remove: false});
				flag.save();
			}
		},
		get: function(flag_slug) {
			var flag = FlagsInstance.get(flag_slug);
			return flag ? flag.clone() : null;
		},
		getData: function(flag_slug) {
			var flag = FlagsInstance.get(flag_slug);
			return flag ? flag.get('data') : null;
		},
		isUp: function(flag_slug) {
			var flag = FlagsInstance.get(flag_slug);
			return !_.isEmpty(flag);
		},
		isDown: function(flag_slug) {
			var flag = FlagsInstance.get(flag_slug);
			return _.isEmpty(flag);
		},
		getAllFlags: function() {
			return FlagsInstance.clone();
		}
	};

	return Flags;
} );