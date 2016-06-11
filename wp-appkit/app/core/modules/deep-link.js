define( function( require ) {
    "use strict";

    var ParseURI = require( 'core/lib/parse-uri' ),
        Utils    = require( 'core/app-utils' ),
        Hooks    = require( 'core/lib/hooks' );

    var deepLink = {};
    var query_string = {};
    var cached_route = '';

    /**
     * Add query string parameters retrieved from the launch URL to the template args passed to the view.
     * Called when the 'template-args' filter is firing.
     *
     * @param   Object  template_args   Template args object created by WP-AppKit
     *
     * @return  Object  template_args   Template args object with query string parameters in a "query_string" key, if any
     */
    var addTemplateArgs = function( template_args ) {
        // Add query string parameters to template args, so that template can acces them
        if( Object.keys( query_string ).length > 0 ) {
            Utils.log( 'Adding query string parameters to template args', query_string );
            template_args.query_string = query_string;
        }

        // Remove filter as these parameters need to be added only once, for the first page launched
        Hooks.removeFilter( 'template-args', addTemplateArgs );

        return template_args;
    };

    /**
     * Return the launch route guessed from the launch URL
     *
     * @return  String  cached_route   Launch route filled with the actual Backbone route (#host + path). Ex: #single/posts/7
     */
    deepLink.getLaunchRoute = function() {
        if( wpak_open_url.length ) {
            if( !cached_route.length ) {
                // Parse URL
                var parsed_url = ParseURI.parse( wpak_open_url );

                if( parsed_url.host.length > 0 ) {
                    // parsed_url.host is already containing a trailing slash
                    cached_route = '#' + parsed_url.host + parsed_url.path;

                    Utils.log( 'Launch route updated from deep link URL', { launch_route: cached_route, wpak_open_url: wpak_open_url });

                    // Handle query string, store them locally to add them to template args
                    query_string = parsed_url.queryKey;
                }
            }

            if( query_string.length ) {
                Hooks.addFilter( 'template-args', addTemplateArgs );
            }
        }

        return cached_route;
    };

    /**
     * Reset the launch URL
     */
    deepLink.reset = function() {
        wpak_open_url = '';
        query_string = {};
        cached_route = '';
    };

    return deepLink;
});