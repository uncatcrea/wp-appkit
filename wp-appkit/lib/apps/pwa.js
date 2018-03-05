jQuery().ready( function () {
	var $ = jQuery;

	$( '.wpak_export_link_pwa.pwa-install' ).click( function ( e ) {
		e.preventDefault();

		var $this = $(this);
		var $feedback = $this.siblings( '.wpak_export_pwa_feedback' );
		var $spinner = $this.siblings( '.spinner' );

		$spinner.addClass( 'is-active' );

		var data = {
			action: 'wpak_build_app_sources',
			app_id: wpak_pwa_export.app_id,
			nonce: wpak_pwa_export.nonce,
			export_type: 'pwa-install'
		};

		$.post( ajaxurl, data, function ( response ) {
			$spinner.removeClass( 'is-active' );
			if ( response.ok === 1 ) {
				$feedback.removeClass('updated,error').addClass( 'updated' ).html(
					wpak_pwa_export.messages['install_successfull']
					+ '<br>' +
					'<a href="'+ response.export_uri +'" target="_blank">'+ wpak_pwa_export.messages['see_pwa'] +'</a>'
				);
			} else {
				$feedback.removeClass('updated error').addClass( 'error' ).html( response.msg );
			}

		}).fail(function() {
			$spinner.removeClass( 'is-active' );
		    $feedback.removeClass('updated error').addClass( 'error' ).html( wpak_pwa_export.messages['install_server_error'] );
		});

	} );

	var $icons_container = $( '.wpak-pwa-icons' );

	$('#wpak_app_theme_choice').change(function(){
		var data = {
			action: 'wpak_get_pwa_icons',
			app_id: wpak_pwa_export.app_id,
			theme: $(this).val(),
			nonce: wpak_pwa_export.icons_nonce
		};

		$.get( ajaxurl, data, function( response ) {
			var html = '';
			if( typeof response.icons !== "undefined" && response.icons.length ) {
				html = $( '<div>' ).addClass( 'wpak-pwa-icons-text' ).html( wpak_pwa_export.messages.pwa_icons_detected.replace( '%s', response.icons[0].dir ) );
				$.each( response.icons, function( i, icon ) {
					var $img = $( '<img />' )
						.addClass( 'wpak-pwa-icon' )
						.attr( 'width', icon.width )
						.attr( 'height', icon.height )
						.attr( 'src', icon.url );
					html = html.add( $( '<span></span>' )
						.append( $img )
						.append( '<br/>' + icon.width + 'x' + icon.height )
					);
				});
			}
			else {
				html = $( '<div>' ).addClass( 'wpak-pwa-no-icons' ).html( wpak_pwa_export.messages.pwa_no_icons );
			}
			$icons_container.html( html );

		}).fail(function() {
			var html = $( '<div>' ).addClass( 'wpak-pwa-no-icons' ).html( wpak_pwa_export.messages['install_server_error'] );
			$icons_container.html( html );
		});

	}).change();

	$( '.color-field' ).wpColorPicker();

} );

