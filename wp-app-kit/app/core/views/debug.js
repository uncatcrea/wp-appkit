define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
        Tpl                 = require( 'text!root/debug.html' ),
        App                 = require( 'core/app' );

    var self = null;

    return Backbone.View.extend({

    	initialize : function(args) {
            self = this;

            self.template = _.template( Tpl );

            // Register events here as backbone events don't seem to work...
            $( "#app-debug-button" )
                .on( "touchstart", self.buttonTapOn )
                .on( "touchend", self.buttonTapOff );
        },

        render : function() {
            self.showButton();
        },

        renderPanel : function() {
            var $el = $( "#app-debug-panel" );
            var renderedContent = self.template();

            $el.html( renderedContent );

            // Close button (in the header)
            $( "#app-debug-header .app-panel-close" )
                .on( "touchstart", self.closePanelTapOn )
                .on( "touchend", self.closePanelTapOff );

            // All buttons
            $( "#app-debug-content .app-button" )
                .on( "touchstart", self.panelButtonTapOn )
                .on( "touchend", self.panelButtonTapOff );

            // Reset options
            $( "#reset-options" )
                .on( "touchend", self.resetOptions );

            return $el;
        },

        openPanel : function() {
            var $el = self.renderPanel();

            // Panel slides right
            $el.velocity( {
                left: 0
            }, {
                duration: 500,
                display: "block"
            });

            // After 250 ms debug button fades out
            setTimeout( function() {
                $( "#app-debug-button" ).velocity( {
                    opacity: 0
                }, {
                    duration: 200,
                    queue: false,
                    display: "none"
                });
            }, 250 );
        },

        closePanel : function() {
            var $el = $( "#app-debug-panel" );

            $el.velocity( {
                left: -5000
            }, {
                duration: 500,
                display: "none"
            });

            // Show debug button
            self.showButton();

        },

        showButton : function() {
            var $el = $( "#app-debug-button" );

            $el.velocity( {
                left: [ 10, -60 ],
                opacity: .9
            }, {
                duration: 500,
                easing: [ 70, 8 ],
                display: "block"
            });
        },

        buttonTapOn : function() {
            var $el = $( this );
            var newPosTop = $el.offset().top + 1;
            var newPosLeft = $el.offset().left + 1;
            $el.css( { top: newPosTop, left: newPosLeft } );
        },

        buttonTapOff : function() {
            var $el = $( this );
            var newPosTop = $el.offset().top - 1;
            var newPosLeft = $el.offset().left - 1;
            $el.css( { top: newPosTop, left: newPosLeft } );

            self.openPanel();
        },

        closePanelTapOn : function() {
            $( this )
                .removeClass( "app-panel-close-off" )
                .addClass( "app-panel-close-on" );
        },

        closePanelTapOff : function() {
            $( this )
                .removeClass( "app-panel-close-on" )
                .addClass( "app-panel-close-off" );

            self.closePanel();
        },

        panelButtonTapOn : function() {
            $( this )
                .removeClass( "app-button-off" )
                .addClass( "app-button-on" );
        },

        panelButtonTapOff : function() {
            $( this )
                .removeClass( "app-button-on" )
                .addClass( "app-button-off" );
        },

        resetOptions : function() {
            // Delete all existing options
            App.options.resetAll();

            // Take back the default values from Config
            App.saveOptions( self.confirmReset );
        },

        confirmReset : function() {
            // TODO: add a "refreshing" state somewhere
            // $( '#reset-options' ).removeClass( 'refreshing' );

            // Re-render the panel in order to take into account the new options values
            var $panel = self.renderPanel();

            // Display a confirmation message
            // TODO: style this feedback message
            // TODO: add an error feedback type
            var $feedback = $( '#app-debug-feedback' );
            $feedback
                // .removeClass( 'app-debug-error' )
                .html( 'Options successfully reset' )
                .slideDown();

            setTimeout( function() {
                $feedback.slideUp();
            }, 3 * 1000 );
        }

    });

});
