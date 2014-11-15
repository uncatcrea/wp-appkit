define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );
	var _ = require( 'underscore' );

	var config_addons = Config.addons;
	
	var html_files = [];
		
	var css_query_args = 'bust='+ new Date().getTime();
		
	//Only for use inside WP plugin : we have to pass the app id to retrieve our addon css :
	var app_id = window.location.search.substring(1);
	if( app_id.length ){
		css_query_args = app_id +'&'+ css_query_args;
	}

	var addons = { };
	
	addons.initialize = function(callback){
		//HTML files loading must be asynchrone (because of require('text!...') )
		//> Here, we load addons HTML files at app initialization.
		
		var html_files_require = [];
		
		_.each(config_addons,function(addon){
			_.each(addon.html_files,function(file){
				html_files_require.push('text!addons/'+ addon.slug +'/'+ file.file +'?'+ css_query_args);
				html_files.push({file:file.file, type: file.type, position: file.position});
			});
		});
		
		require(html_files_require,function(){
			
			_.each(arguments, function(file_content,index){
				html_files[index].content = file_content;
			});
			
			if( callback !== undefined ){
				callback();
			}
		});
	};
	
	addons.getJs = function(type, position){
		var js_files = [];
		
		_.each(config_addons,function(addon){
			_.each(addon.js_files,function(file){
				if( file.type == type && file.position == position ){
					js_files.push('addons/'+ addon.slug +'/'+ file.file);
				}
			});
		});
		
		return js_files;
	};
	
	addons.getCss = function(position){
		var css_files = '';
		
		_.each(config_addons,function(addon){
			_.each(addon.css_files,function(file){
				if( file.position == position ){
					css_files = css_files + '<link rel="stylesheet" href="addons/'+ addon.slug +'/'+ file.file +'?'+ css_query_args +'">';
				}
			});
		});
		
		return css_files;
	};
	
	addons.getHtml = function(type,position){
		var layout_html = '';
		
		_.each(html_files,function(file){
			if( file.type == type && file.position == position ){
				layout_html = layout_html + file.content;
			}
		});
		
		return layout_html;
	};
	
	return addons;
} );


