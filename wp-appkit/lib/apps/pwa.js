jQuery().ready( function () {
	var $ = jQuery;

	$( '.wpak_export_link_pwa' ).click( function ( e ) {
		var pwa_export_type = $(this).siblings( '.wpak_export_type_pwa' ).val();
		if ( pwa_export_type === 'pwa-install' ) {

			e.preventDefault();
			
			var $feedback = $(this).siblings( '.wpak_export_pwa_feedback' );

			var data = {
				action: 'wpak_build_app_sources',
				app_id: wpak_pwa_export.app_id,
				nonce: wpak_pwa_export.nonce,
				export_type: pwa_export_type
			};

			$.post( ajaxurl, data, function ( response ) {
				if ( response.ok === 1 ) {
					$feedback.removeClass('updated,error').addClass( 'updated' ).html( 
						wpak_pwa_export.messages['install_successfull'] 
						+ '<br>' +
						'<a href="'+ response.export_uri +'" target="_blank">'+ wpak_pwa_export.messages['see_pwa'] +'</a>'
					);
				} else {
					$feedback.removeClass('updated,error').addClass( 'error' ).html( response.msg );
				}
			} );
		}

	} );

} );

