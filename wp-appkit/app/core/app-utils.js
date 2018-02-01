define( function( require ) {

	"use strict";

	var Config = require( 'root/config' );
    var Hooks = require( 'core/lib/hooks' );

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
        if ( url.substr(-1) !== '/' ) {
            url += '/';
        }
        return url;
    };
	
	utils.removeTrailingSlash = function( url ) {
        return url.replace( /\/$/, "" );
    };

    utils.trimFragment = function( fragment ) {
        fragment = fragment.replace( '#', '' );
        fragment = this.removeTrailingSlash( fragment );
        return fragment;
    };
    
    utils.isInternalUrl = function( url ) {
        var is_internal_url = url.indexOf( 'http' ) !== 0;
            
        is_internal_url = Hooks.applyFilters( 'is_internal_url', is_internal_url, [url] );
        
        return is_internal_url;
    };
    
    /**
     * Extracts route (ie single/posts/123/) from full url path (ie my/app-/path/single/posts/123/)
     */
    utils.extractRouteFromUrlPath = function( url_path ) {
        var route = '';
        
        if ( Config.app_path.length > 0 ) {
            //App installed in sub directory:
			
            if ( url_path.indexOf( '/' ) === 0 ) { 
                //url_path starts with slash: remove it because fragments and 
                //app_path have no starting slash:
                url_path = url_path.replace( /^\/+/, '' );
            } 
            
			//Strip Config.app_path from url:
            if ( url_path.indexOf( Config.app_path ) === 0 ) { 
                route = url_path.replace( Config.app_path, '' );
            } else {
                route = url_path;
            }
            
        } else {
            
            //App installed at domain's root
            if ( url_path.indexOf( '/' ) === 0 ) { 
                //url_path starts with slash: simply remove it
                route = url_path.replace( /^\/+/, '' );
            } else {
                route = url_path;
            }
            
        }
        
        return route;
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