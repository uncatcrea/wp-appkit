// build the Subject base class
var Subject = ( function( window, undefined ) {
    function Subject() {
        this._list = [];
    }

    // this method will handle adding observers to the internal list
    Subject.prototype.observe = function( obj ) {
        this._list.push( obj );
    };

    Subject.prototype.unobserve = function( obj ) {
        for( var i = 0, len = this._list.length; i < len; i++ ) {
            if( this._list[ i ] === obj ) {
                this._list.splice( i, 1 );
                return true;
            }
        }
        return false;
    };

    Subject.prototype.notify = function() {
        var args = Array.prototype.slice.call( arguments, 0 );
        for( var i = 0, len = this._list.length; i < len; i++ ) {
            this._list[ i ].update.apply( null, args );
        }
    };

    return Subject;
})( window );

jQuery().ready(function(){
    var $ = jQuery,
        wizard_title = $( '#wpak_app_wizard_title' ),
        wizard_components = $( '#wpak_app_wizard_components' ),
        wizard_navigation = $( '#wpak_app_wizard_navigation' ),
        wizard_save = $( '#wpak_app_wizard_save' ),
        title = $( '#title' ),
        app_title = $( '#wpak_app_title' );

    var wizard_components_observer = {
        update: function() {
            if( $( '.component-row' ).length ) {
                wizard_components
                    .removeClass( 'nok' )
                    .addClass( 'ok' );
            }
            else {
                wizard_components
                    .removeClass( 'ok' )
                    .addClass( 'nok' );
            }
        }
    };

    var wizard_navigation_observer = {
        update: function() {
            if( $( '#navigation-items-table tr > td' ).length && app_title.val().length ) {
                wizard_navigation
                    .removeClass( 'nok' )
                    .addClass( 'ok' );
            }
            else {
                wizard_navigation
                    .removeClass( 'ok' )
                    .addClass( 'nok' );
            }
        }
    };

    var wizard_title_observer = {
        update: function() {
            if( title.val().length ) {
                wizard_title
                    .removeClass( 'nok' )
                    .addClass( 'ok' );
            }
            else {
                wizard_title
                    .removeClass( 'ok' )
                    .addClass( 'nok' );
            }
        }
    }

    WpakComponents.addObserver( wizard_components_observer );
    WpakNavigation.addObserver( wizard_navigation_observer );
    app_title.on( 'keyup', wizard_navigation_observer.update )
    title.on( 'keyup', wizard_title_observer.update );

});