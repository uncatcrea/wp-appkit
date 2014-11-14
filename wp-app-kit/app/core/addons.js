define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );
	var _ = require( 'underscore' );

	var css_query_args = 'bust='+ new Date().getTime();
		
	//Only for use inside WP plugin : we have to pass the app id to retrieve our addon css :
	var app_id = window.location.search.substring(1);
	if( app_id.length ){
		css_query_args = app_id +'&'+ css_query_args;
	}

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
	
	addons.getCss = function(type){
		var css_files = '';
		var config_addons = Config.addons;
		
		_.each(config_addons,function(addon){
			_.each(addon.css_files,function(file){
				if( file.type == type ){
					css_files = css_files + '<link rel="stylesheet" href="addons/'+ addon.slug +'/'+ file.file +'?'+ css_query_args +'">';
				}
			});
		});
		
		return css_files;
	};
	
	return addons;
} );


