var cacheName = ''; //Set dynamically in /lib/apps/build.php::build_service_worker_cache()
var filesToCache = []; //Set dynamically in /lib/apps/build.php::build_service_worker_cache()

filesToCache = filesToCache.map( function( item ) {
	var subdir = location.pathname.replace( '/service-worker-cache.js', '' );
	return subdir + item;
} );

self.addEventListener( 'install', function ( e ) {
	console.log( '[WP-AppKit Service Worker] Install' );
	e.waitUntil(
		caches.open( cacheName ).then( function ( cache ) {
			console.log( '[WP-AppKit Service Worker] Caching app assets' );
			return cache.addAll( filesToCache );
		} )
	);
} );

self.addEventListener( 'activate', function ( e ) {
	console.log( '[WP-AppKit Service Worker] Activate' );
	e.waitUntil(
		caches.keys().then( function ( keyList ) {
			return Promise.all( keyList.map( function ( key ) {
				if ( key !== cacheName ) {
					console.log( '[WP-AppKit Service Worker] Removing old cache', key );
					return caches.delete( key );
				}
			} ) );
		} )
	);
	return self.clients.claim();
} );

self.addEventListener( 'fetch', function ( e ) {
	console.log( '[WP-AppKit Service Worker] Fetch', e.request.url );
	e.respondWith(
		caches.match( e.request ).then( function ( response ) {
			return response || fetch( e.request );
		} )
	);
} );


