define( function( require ) {

    /**
     * Handle dynamic data that can be passed through synchronization webservice
     * and are refreshed at each app content refresh.
     */

    "use strict";

    var Backbone = require( 'backbone' );
    var Config = require( 'root/config' );
    var _ = require( 'underscore' );
    var Utils = require('core/app-utils');
    require('localstorage');

    var model_id = "AppDynamicData-"+ Config.app_slug;

    var AppDynamicDataModel = Backbone.Model.extend({
        localStorage: new Backbone.LocalStorage( model_id ),
        defaults : { id: '' }
    });
    
    var app_dynamic_data_instance = new AppDynamicDataModel( { id: model_id } );

    /*****************
    * Public methods
    */
   
    var app_dynamic_data = {};

    app_dynamic_data.initialize = function( callback ){
        
        app_dynamic_data_instance.fetch({
            success: function( app_dynamic_data_object ){
                Utils.log('App dynamic data retrieved from local storage.', app_dynamic_data_object);
                callback( app_dynamic_data_object.toJSON() );
            },
            error: function( error ) {
                //First launch probably
                Utils.log('No app dynamic data found in local storage.', error);
                callback();
            }
        });

    };

    app_dynamic_data.setDynamicDataFromWebService = function( dynamic_data ){
        app_dynamic_data_instance.clear();
        app_dynamic_data_instance.set( { id: model_id } );
        app_dynamic_data_instance.set( dynamic_data );
        app_dynamic_data_instance.save();
    };

    app_dynamic_data.getDynamicData = function( data_key ){
        var dynamic_data = {};
        if ( data_key !== undefined ) {
            dynamic_data = app_dynamic_data_instance.get( data_key );
        } else {
            dynamic_data = app_dynamic_data_instance.toJSON();
            delete( dynamic_data.id );
        }
        return dynamic_data;
    };
    
    return app_dynamic_data;
} );