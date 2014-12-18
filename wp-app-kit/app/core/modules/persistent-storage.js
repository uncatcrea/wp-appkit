define( function( require ) {

	"use strict";

	var Backbone = require( 'backbone' ),
			Config = require( 'root/config' ),
			_ = require( 'underscore' );

	require( 'localstorage' );

	var StorageModel = Backbone.Model.extend( {
		defaults: {
			id: "",
			group: "",
			key: "",
			value: ""
		}
	} );

	var StorageCollection = Backbone.Collection.extend( {
		localStorage: new Backbone.LocalStorage( "Storage-" + Config.app_slug ),
		model: StorageModel,
		resetAll: function() {
			var length = this.length;
			for ( var i = length - 1; i >= 0; i-- ) {
				this.at( i ).destroy();
			}
			this.reset();
		}
	} );

	var StorageInstance = new StorageCollection();
	StorageInstance.fetch();

	var storage = {};

	storage.set = function( group, key, value ) {
		if ( group !== undefined && key !== undefined && value !== undefined ) {
			var entry = StorageInstance.add( { id: group + ':' + key, group: group, key: key, value: value }, {merge: true} );
			entry.save();
		}
	};

	storage.get = function( group, key, default_value ) {
		var value = null;

		var group_entries = StorageInstance.where( { group: group } );
		if ( !_.isEmpty( group_entries ) ) {
			if ( key !== undefined ) {
				var entry = StorageInstance.get( group + ':' + key );
				if ( entry ) {
					value = entry.get( 'value' );
				}else if ( default_value !== undefined ) {
					value = default_value;
				}
			} else {
				value = { };
				_.each( group_entries, function( entry ) {
					value[entry.get( 'key' )] = entry.get( 'value' );
				} );
			}
		}else if ( key !== undefined && default_value !== undefined ) {
			value = default_value;
		}

		return value;
	};

	storage.clear = function( group, key ) {

		var group_entries = StorageInstance.where( { group: group } );
		if ( !_.isEmpty( group_entries ) ) {
			if ( key !== undefined ) {
				var entry = StorageInstance.get( group + ':' + key );
				if ( entry ) {
					entry.destroy();
					StorageInstance.remove( group + ':' + key );
				}
			} else {
				_.each( group_entries, function( entry ) {
					var entry_id = entry.get( 'group' ) + ':' + entry.get( 'key' );
					var entry_instance = StorageInstance.get( entry_id );
					entry_instance.destroy();
					StorageInstance.remove( entry_id );
				} );
			}
		}

	};

	storage.clearAll = function() {
		StorageInstance.resetAll();
	};

	return storage;
} );