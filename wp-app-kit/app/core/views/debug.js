define(function (require) {

    "use strict";

    var $                   = require('jquery'),
        _                   = require('underscore'),
        Backbone            = require('backbone'),
        Config              = require('root/config'),
		Addons              = require('core/addons-internal'),
        Tpl                 = require( 'text!root/debug.html' ),
        App                 = require( 'core/app' ),
		Hooks               = require('core/lib/hooks');

    var self = null;

    return Backbone.View.extend({

    	initialize : function(args) {
            self = this;

			//We add addons "debug_pane" html to div#app-debug-content :
			var $Tpl = $('<div/>').html(Tpl);
			
			$('#app-debug-content',$Tpl).prepend(Addons.getHtml('debug_panel','before'))
										.append(Addons.getHtml('debug_panel','after'));
			
			//jQuery parsing escapes underscore's templating tags : 
			//we restore them manually :
			var tpl_html = $Tpl.html();
			tpl_html = tpl_html.replace(/&lt;%/g,'<%');
			tpl_html = tpl_html.replace(/%&gt;/g,'%>');

            self.template = _.template( tpl_html );

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
			
			var data = {};
			
			var addons_data = Addons.getHtmlData('debug_panel');
			data = _.extend(data, addons_data);
            var renderedContent = self.template(data);

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

			Hooks.doActions('debug-panel-render',[self]);

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
            self.displayFeedback('Options successfully reset')
        },
		
		displayFeedback : function( message, type, timeout ) {
			
			if( _.isUndefined( type ) || _.isEmpty( type ) ) {
				type = 'info';
			}
			
			if( _.isUndefined( timeout ) ) {
				timeout = 3000;
			}
			
			// TODO: style this feedback message
            // TODO: add an error feedback type
            var $feedback = $( '#app-debug-feedback' );
            $feedback
                // .removeClass( 'app-debug-error' )
                .html( message )
                .slideDown();

            setTimeout( function() {
                $feedback.slideUp();
            }, timeout );
		}

    });

});
