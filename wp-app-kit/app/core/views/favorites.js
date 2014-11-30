define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        ArchiveView         = require('core/views/archive'),
        ThemeTplTags		= require('core/theme-tpl-tags');

    return ArchiveView.extend({

    	initialize : function( args ) {
            // Call parent initialize()
            ArchiveView.prototype.initialize.apply( this, [args] );

    		this.setTemplate('archive-favorites');
        },

    });

});
