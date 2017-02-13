jQuery().ready( function () {
	var $ = jQuery;

	$( '#wpak_export_link_pwa' ).click( function ( e ) {
		var pwa_export_type = $( '#wpak_export_type_pwa' ).val();
		if ( pwa_export_type === 'pwa-install' ) {

			e.preventDefault();
			
			var $feedback = $( '#wpak_export_pwa_feedback' );

			var data = {
				action: 'wpak_build_app_sources',
				app_id: wpak_pwa_export.app_id,
				nonce: wpak_pwa_export.nonce,
				export_type: pwa_export_type
			};

			$.post( ajaxurl, data, function ( response ) {
				console.log('RESPONSE',response);
				if ( response.ok === 1 ) {
					$feedback.removeClass().addClass( 'updated' ).html( 
						wpak_pwa_export.messages['install_successfull'] 
						+ '<br>' +
						'<a href="'+ response.export_uri +'">See the Progressive Web App</a>'
					);
				} else {
					$feedback.removeClass().addClass( 'error' ).html( response.msg );
				}
			} );
		}

	} );

} );

