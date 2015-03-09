/**
 * Handles App messages.
 * 
 * For now, only the messages used in themes are handled here.
 * Messages are defined in lang/theme-messages.js.
 * 
 * TODO : 
 * - see if we handle other messages types : "log" messages and "triggered events" messages
 * - here we could handle translations and/or give priority to messages re-defined in themes.
 */
define( function ( require ) {

	"use strict";
	
	var ThemeMessages = require( 'lang/theme-messages' );
	
	return {
		get : function(message_id){
			var message = '';
			if( ThemeMessages.hasOwnProperty(message_id) ){
				message = ThemeMessages[message_id];
			}
			return message;
		}
	};

});
