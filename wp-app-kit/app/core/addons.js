define( function( require ) {
	
	/**
	 * Addon module that can be used from outside by WP AppKit addons.
	 */

	"use strict";

	var AppAddons = require( 'core/addons-internal' );

	var addons = { };
	
	addons.isActive = function(addon_slug){
		return AppAddons.isActive(addon_slug);
	};
	
	addons.getAppStaticData = function(addon_slug, field){
		return AppAddons.getAppStaticData(addon_slug, field);
	};
	
	/**
	 * Retrieves addons data passed from WP through the web service.
	 * @param string addon_slug
	 * @param string field Optional
	 * @returns JSON Object
	 */
	addons.getAppDynamicData = function(addon_slug, field){
		return AppAddons.getAppDynamicData(addon_slug, field);
	};
	
	return addons;
} );