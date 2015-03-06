define( function( require ) {

	/**
	 * Internal Addons module, used by the app core only.
	 * To retrieve addons data from the addons themselves, use 
	 * the "addon.js" module.
	 */
	
	"use strict";

	var Backbone = require( 'backbone' );
	var Config = require( 'root/config' );
	var _ = require( 'underscore' );
	var Utils = require('core/app-utils');
	require('localstorage');

	/***************************************
	 * Retrieve addons from config.js
	 */
	var config_addons = {};
	_.each(Config.addons, function(addon){
		config_addons[addon.slug] = addon;
	});
	
	/***************************************
	 * Url logic to handle addons CSS files
	 */
	var css_query_args = 'bust='+ new Date().getTime();
		
	//Only for use inside WP plugin : we have to pass the app id to retrieve our addon css :
	var app_id = window.location.search.substring(1);
	if( app_id.length ){
		css_query_args = app_id +'&'+ css_query_args;
	}
	
	/***************************************
	 * Handle addons HTML files
	 */
	var html_files = [];
	
	var set_html_files = function(callback){
		//HTML files loading must be asynchrone (because of require('text!...') )
		//> Here, we load addons HTML files at app initialization.
		
		var html_files_require = [];
		
		_.each(config_addons,function(addon){
			_.each(addon.html_files,function(file){
				html_files_require.push('text!addons/'+ addon.slug +'/'+ file.file +'?'+ css_query_args);
				html_files.push({
					file: file.file, 
					type: file.type, 
					position: file.position,
					data: file.data
				});
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
	
	/***************************************
	* Addons dynamic data Model and Collection, stored in Local Storage :
	*/
    var AddonDynamicData = Backbone.Model.extend({
    	defaults : {
    		id : "",
            data : "",
        }
    });

    var AddonsDynamicData = Backbone.Collection.extend({
    	localStorage: new Backbone.LocalStorage("Addons-"+ Config.app_slug),
    	model : AddonDynamicData,
    	saveAll : function(){
       	 	this.map(function(addon){addon.save();});
        },
        resetAll : function(){
        	var length = this.length; 
        	for (var i = length - 1; i >= 0; i--) { 
        		this.at(i).destroy(); 
        	} 
        	this.reset();
        }
    });
	
	var addons_dynamic_data_instance = new AddonsDynamicData();
	
	var fetch_dynamic_data = function(cb_ok){
		
		addons_dynamic_data_instance.fetch({
			success: function(addons_dynamic_data){
				
				//Note : addons_dynamic_data.length == 0 is not considered as 
				//an error here, because some addons can have no dynamic data.
				
				//Remove addons that are not in config.js : 
				//Can happen if we add an addon on Wordpress side
				//after the app compilation, or if we deactivate an addon.
				
				var to_remove = [];
				
				addons_dynamic_data.each(function(addon){
					if( !config_addons.hasOwnProperty(addon.get('id')) ){
						to_remove.push(addon.get('id'));
					}
				});
				
				if( to_remove.length > 0 ){
					_.each(to_remove, function(addon_slug){
						var addon = addons_dynamic_data_instance.get(addon_slug);
						if( addon ){
							addon.destroy();
							addons_dynamic_data_instance.remove(addon_slug);
						}
					});
				}
				
				cb_ok(addons_dynamic_data_instance.toJSON());
				
			}
		});
	};
	
	/***************************************
	* Public methods that are used in the app core :
	*/
	var addons = { };
	
	addons.initialize = function(callback){
		
		fetch_dynamic_data(
			function(addons_data){
				Utils.log('Addons dynamic data retrieved from local storage.',addons_data);
				
				set_html_files(callback);
			}
		);
		
	};
	
	addons.setDynamicDataFromWebService = function(addons_data){
		addons_dynamic_data_instance.resetAll();
		_.each(addons_data,function(dynamic_data, addon_slug){
			//Don't add addons that are not in config.js : 
			if( config_addons.hasOwnProperty(addon_slug) ){
				addons_dynamic_data_instance.add({id:addon_slug,data:dynamic_data});
			}
		});
		addons_dynamic_data_instance.saveAll();
	};
	
	addons.isActive = function(addon_slug){
		var is_active = false;
		_.each(config_addons,function(addon){
			if( !is_active && addon.slug == addon_slug ){
				is_active = true;
			}
		});
		return is_active;
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
	
	addons.getHtmlData = function(type){
		var html_data = {};
		
		_.each(html_files,function(file){
			if( file.type == type ){
				html_data = _.extend(html_data, file.data);
			}
		});
		
		return html_data;
	};
	
	addons.getAppStaticData = function(addon_slug, field){
		var addon_app_data = {};
		_.each(config_addons,function(addon){
			if( addon.slug == addon_slug && !_.isEmpty(addon.app_data) ){
				if( field !== undefined && addon.app_data.hasOwnProperty(field) ){
					addon_app_data = addon.app_data[field];
				}else{
					addon_app_data = addon.app_data;
				}
			}
		});
		return addon_app_data;
	};
	
	addons.getAppDynamicData = function(addon_slug, field){
		var addon_app_data = {};
		
		var addon_dynamic_data_object = addons_dynamic_data_instance.get(addon_slug);
		
		if( addon_dynamic_data_object ){
			var addon_dynamic_data = addon_dynamic_data_object.get('data');
			if( !_.isEmpty(addon_dynamic_data) ){
				if( field !== undefined && addon_dynamic_data.hasOwnProperty(field) ){
					addon_app_data = addon_dynamic_data[field];
				}else{
					addon_app_data = addon_dynamic_data;
				}
			}
		}
		
		return addon_app_data;
	};
	
	return addons;
} );





