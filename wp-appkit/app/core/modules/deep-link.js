define( function( require ) {
    "use strict";

    var deepLink = {};

    deepLink.getLaunchRoute = function( launch_route ) {
        if( wpak_open_url.length ) {
            // Parse URL
            alert( 'in getLaunchRoute ' + wpak_open_url );
        }

        return launch_route;
    };

    return deepLink;
});