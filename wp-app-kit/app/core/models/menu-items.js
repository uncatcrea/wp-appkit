define(function (require) {

    "use strict";

    var Backbone    = require('backbone');

    var MenuItem = Backbone.Model.extend({
    	defaults : {
    		id : "",
            label : "",
            type : "",
            link : ""
        }
    });

    var MenuItems = Backbone.Collection.extend({
    	model : MenuItem,
    });

    return {MenuItem:MenuItem,MenuItems:MenuItems};
});