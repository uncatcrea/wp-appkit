define(function (require) {

    "use strict";

    var Backbone    = require('backbone');

    var Comment = Backbone.Model.extend({
    	defaults : {
    		id: "",
    		author: "",
    		date: 0,
    		content: "",
    		depth: 1
        }
    });

    var Comments = Backbone.Collection.extend({
    	model : Comment,
    });

    return {Comment:Comment,Comments:Comments};

});