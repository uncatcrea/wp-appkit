define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );
	var _ = require( 'underscore' );

	var addons = { };
	
	addons.getJs = function(type){
		var js_files = [];
		var config_addons = Config.addons;
		_.each(config_addons,function(addon){
			_.each(addon.js_files,function(file){
				if( file.type == type ){
					js_files.push('addons/'+ addon.slug +'/'+ file.file);
				}
			});
		});
		return js_files;
	};
	
	return addons;
} );


