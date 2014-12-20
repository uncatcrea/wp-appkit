define( [ 'jquery', 'core/theme-app', 'core/theme-tpl-tags', 'core/modules/storage', 'theme/js/bootstrap.min', 'theme/js/wp-appkit-note-addon' ], function( $, App, TemplateTags, Storage ) {

	/**
	 * Launch app contents refresh when clicking the refresh button :
	 */
	$( '#refresh-button' ).click( function( e ) {
		e.preventDefault();
		closeMenu();
		App.refresh();
	} );

	/**
	 * Animate refresh button when the app starts refreshing
	 */
	App.on( 'refresh:start', function() {
		$( '#refresh-button' ).addClass( 'refreshing' );
	} );

	/**
	 * When the app stops refreshing :
	 * - scroll to top
	 * - stop refresh button animation
	 * - display success or error message
	 *
	 * Callback param : result : object {
	 *		ok: boolean : true if refresh is successful,
	 *		message: string : empty if success, error message if refresh fails,
	 *		data: object : empty if success, error object if refresh fails :
	 *					   use result.data to get more info about the error
	 *					   if needed.
	 * }
	 */
	App.on( 'refresh:end', function( result ) {
		scrollTop();
		Storage.clear( 'scroll-pos' );
		$( '#refresh-button' ).removeClass( 'refreshing' );
		if ( result.ok ) {
			$( '#feedback' ).removeClass( 'error' ).html( 'Content updated successfully :)' ).slideDown();
		} else {
			$( '#feedback' ).addClass( 'error' ).html( result.message ).slideDown();
		}
	} );

	/**
	 * When an error occurs, display it in the feedback box
	 */
	App.on( 'error', function( error ) {
		$( '#feedback' ).addClass( 'error' ).html( error.message ).slideDown();
	} );

	/**
	 * Hide the feedback box when clicking anywhere in the body
	 */
	$( 'body' ).click( function( e ) {
		$( '#feedback' ).slideUp();
	} );

	/**
	 * Automatically shows and hide Back button according to current screen
	 */
	App.setAutoBackButton( $( '#go-back' ), function( back_button_showed ) {
		if ( back_button_showed ) {
			$( '#refresh-button' ).hide();
		} else {
			$( '#refresh-button' ).show();
		}
	} );

	/**
	 * Allow to click anywhere on post list <li> to go to post detail :
	 */
	$( '#container' ).on( 'click', 'li.media', function( e ) {
		e.preventDefault();
		var navigate_to = $( 'a', this ).attr( 'href' );
		App.navigate( navigate_to );
	} );

	/**
	 * Close menu when we click a link inside it.
	 * The menu can be dynamically refreshed, so we use "on" on parent div (which is always here):
	 */
	$( '#navbar-collapse' ).on( 'click', 'a', function( e ) {
		closeMenu();
	} );

	/**
	 * Open all links inside single content with the inAppBrowser
	 */
	$( "#container" ).on( "click", "#single a, .page-content", function( e ) {
		e.preventDefault();
		openWithInAppBrowser( e.target.href );
	} );

	/**
	 * "Get more" button in post lists
	 */
	$( '#container' ).on( 'click', '.get-more', function( e ) {
		e.preventDefault();
		$( this ).attr( 'disabled', 'disabled' ).text( 'Loading...' );
		App.getMoreComponentItems( function() {
			//If something is needed once items are retrieved, do it here.
			//Note : if the "get more" link is included in the archive.html template (which is recommended),
			//it will be automatically refreshed.
			$( this ).removeAttr( 'disabled' );
		} );
	} );

	/**
	 * Do something before leaving a screen.
	 * Here, if we're leaving a post list, we memorize the current scroll position, to
	 * get back to it when coming back to this list.
	 */
	App.on( 'screen:leave', function( current_screen, queried_screen, view ) {
		//current_screen.screen_type can be 'list','single','page','comments'
		if ( current_screen.screen_type == 'list' ) {
			Storage.set( 'scroll-pos', current_screen.fragment, $( 'body' ).scrollTop() );
		}
	} );

	/**
	 * Do something when a new screen is showed.
	 * Here, if we arrive on a post list, we resore the scroll position
	 */
	App.on( 'screen:showed', function( current_screen, view ) {
		//current_screen.screen_type can be 'list','single','page','comments'
		if ( current_screen.screen_type == 'list' ) {
			var pos = Storage.get( 'scroll-pos', current_screen.fragment );
			if ( pos !== null ) {
				$( 'body' ).scrollTop( pos );
			} else {
				scrollTop();
			}
		} else {
			scrollTop();
		}
	} );

	/**
	 * Toggle the display for both 'add' and 'remove' favorites links.
	 * Called after a post has been added or removed to favorites list, so that the user can have a visual feedback.
	 *
	 * @param 	bool 	saved 		True or false whether the favorites list update has been made or not.
	 * @param 	int 	post_id 	ID of the post that has been added or removed from the favorites list.
	 */
	function toggleFavoriteLinks( saved, post_id ) {
		if ( saved ) {
			if ( TemplateTags.isFavorite( post_id ) ) {
				$( '.post-' + post_id + ' .favorite.add' ).addClass( 'hidden' );
				$( '.post-' + post_id + ' .favorite.remove' ).removeClass( 'hidden' );
			}
			else {
				$( '.post-' + post_id + ' .favorite.remove' ).addClass( 'hidden' );
				$( '.post-' + post_id + ' .favorite.add' ).removeClass( 'hidden' );
			}
		}
	}

	/**
	 * Add/Remove from favorites buttons
	 */
	$( '#container' ).on( 'click', '.favorite', function( e ) {
		e.preventDefault();
		var $link = $( this );
		var id = $link.data( 'id' );
		var global = $link.data( 'global' );

		if ( TemplateTags.isFavorite( id ) ) {
			App.removeFromFavorites( id, toggleFavoriteLinks );
		}
		else {
			App.addToFavorites( id, toggleFavoriteLinks, global );
		}
	} );

	/**
	 * Reset favorites button
	 */
	$( '#container' ).on( 'click', '.favorite-reset', function( e ) {
		e.preventDefault();
		App.resetFavorites( function() {
			// @TODO: Refresh the archive view, but how?
		} );
	} );

	/**
	 * Example of how to react to network state changes :
	 */
	/*
	 App.on( 'network:online', function(event) {
	 $( '#feedback' ).removeClass( 'error' ).html( "Internet connexion ok :)" ).slideDown();
	 } );
	 
	 App.on( 'network:offline', function(event) {
	 $( '#feedback' ).addClass( 'error' ).html( "Internet connexion lost :(" ).slideDown();
	 } );
	 */

	/**
	 * Manually close the bootstrap navbar
	 */
	function closeMenu() {
		var navbar_toggle_button = $( ".navbar-toggle" ).eq( 0 );
		if ( !navbar_toggle_button.hasClass( 'collapsed' ) ) {
			navbar_toggle_button.click();
		}
	}

	/**
	 * Get back to the top of the screen
	 */
	function scrollTop() {
		window.scrollTo( 0, 0 );
	}

	/**
	 * Opens the given url using the inAppBrowser
	 */
	function openWithInAppBrowser( url ) {
		window.open( url, "_blank", "location=yes" );
	}

} );