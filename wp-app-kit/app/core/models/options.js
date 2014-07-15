define(function (require) {

    "use strict";

    var Backbone                 = require('backbone'),
    	_                   	 = require('underscore');

    require('localstorage');

    var Option = Backbone.Model.extend({
    	defaults : {
    		id : "",
            value : ""
        }
    });

    var Options = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage("Options"),
    	model : Option,
    	saveAll : function( preserve ){
       	 	this.map(function(option){
                option
                    .fetch()
                    .done(function() {
                        // Option already exists
                        if( preserve ) {
                            return;
                        }
                        option.save();
                    })
                    .fail(function() {
                        // Option doesn't already exist
                        option.save();
                    });
            });
        },
        resetAll : function(){
        	var length = this.length;
        	for (var i = length - 1; i >= 0; i--) {
        		this.at(i).destroy();
        	}
        	this.reset();
        }
    });

    return Options;

});