define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );

	var utils = { };

	utils.log = function() {
		if ( console && Config.debug_mode == 'on' ) {
			console.log.apply( console, arguments );
		}
	};

	utils.addParamToUrl = function( uri, key, value ) {
		var new_url = '';
		var re = new RegExp( "([?&])" + key + "=.*?(&|$)", "i" );
		var separator = uri.indexOf( '?' ) !== -1 ? "&" : "?";
		if ( uri.match( re ) ) {
			new_url = uri.replace( re, '$1' + key + "=" + value + '$2' );
		} else {
			new_url = uri + separator + key + "=" + value;
		}
		return new_url;
	};
	
    utils.addTrailingSlash = function( url ) {
        if ( url.substr(-1) !== '/') {
            url += '/';
        }
        return url;
    };
    
    utils.isInternalUrl = function( url ) {
        var is_internal_url = url.indexOf( 'http' ) !== 0;
            
        is_internal_url = Hooks.applyFilters( 'is_internal_url', is_internal_url, [url] );
        
        return is_internal_url;
    };
    
    /**
     * Extracts route (ie single/posts/123) from full url path (ie my/app-/path/single/posts/123)
     */
    utils.extractRootFromUrlPath = function( url_path ) {
        var fragment = '';
        
        if ( Config.app_path.length > 0 ) {
            
            if ( url_path.indexOf( '/' ) === 0 ) { 
                //url_path starts with slash: remove it because fragments and 
                //app_path have no starting slash:
                url_path = url_path.replace( /^\/+/, '' );
            } 
            
            if ( url_path.indexOf( Config.app_path ) === 0 ) { 
                fragment = fragment.replace( Config.app_path, '' );
            }
            
        } else {
            
            //App installed at domain's root
            if ( url_path.indexOf( '/' ) === 0 ) { 
                //url_path starts with slash: simply remove it
                fragment = url_path.replace( /^\/+/, '' );
            } else {
                fragment = url_path;
            }
            
        }
        
        return fragment;
    }
    
	utils.getAjaxErrorType = function( jqXHR, textStatus, errorThrown ) {
		var error_type = 'unknown-error';
		
		textStatus = ( textStatus !== null ) ? textStatus : 'unknown';
		
		switch( jqXHR.status ) {
			case 404:
				if ( textStatus == 'error' ) {
					error_type = 'url-not-found';
				} else {
					error_type = '404:' + textStatus; 
				}
				break;
			case 200:
				if ( textStatus == 'parsererror' ) {
					error_type = 'parse-error-in-json-answer';
				} else {
					error_type = '200:' + textStatus;
				}
				break;
			default:
				error_type = jqXHR.status + ':' + textStatus;
				break;
		}
		
		return error_type;
	};

	return utils;
} );