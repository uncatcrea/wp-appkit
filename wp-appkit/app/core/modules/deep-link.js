define( function( require ) {
    "use strict";

    var ParseURI = require( 'core/lib/parse-uri' );

    var deepLink = {};

    deepLink.getLaunchRoute = function( launch_route ) {
        if( wpak_open_url.length ) {
            // Parse URL
            var parsed_url = ParseURI.parse( wpak_open_url );

            if( parsed_url.host.length > 0 ) {
                launch_route = '#' + parsed_url.host + '/' + parsed_url.path;

                // TODO: handle query string (stored into parsed_url.query)
            }
        }

        return launch_route;
    };

    return deepLink;
});