define( [ 'jquery', 'core/theme-app', 'theme/js/bootstrap.min' ], function( $, App ) {

	//Require the addon js separately or it avoids to load the theme correctly
	//when the addon is not activated :
	require( ['addons/wp-appkit-note/wpak-note'], function( WpakNote ) {

		/**
		 * Override the default WPAK Note Addon rendering with a Bootstrap modal display.
		 */

		var $modal = $('#theme-modal');
		var $modal_header = $('#theme-modal-header');
		var $modal_body = $('#theme-modal-body');
		var $modal_footer = $('#theme-modal-footer');
		var $modal_close = $('#theme-modal-close');

		var hide_modal = function(){
			$modal_close.removeClass('wpak-note-close');
			$modal.modal('hide');
		};

		WpakNote.setActions({
			display_first_box: function(){
				$('h4',$modal_header).html('We need your feedback!');
				$modal_body.html($('#wpak-note-first-box-question').html());
				$modal_footer.html(
					'<button id="first-box-yes" type="button" class="btn btn-primary btn-sm">'+ $('#wpak-note-first-box-yes').html() +'</button>'+
					'<button id="first-box-no" type="button" class="btn btn-default btn-sm" >'+ $('#wpak-note-first-box-no').html() +'</button>'+
					'<button id="first-box-later" type="button" class="btn btn-default btn-sm" >'+ $('#wpak-note-first-box-later').html() +'</button>'+
					'<button id="first-box-dont-ask-again" type="button" class="btn btn-default btn-sm" >'+ $('#wpak-note-first-box-dont-ask-again').html() +'</button>'	
				);
				$modal_close.addClass('wpak-note-close'); //To know that we close the modal in the Wpak Note context
				$modal.modal();
			},
			display_satisfied_box: function(){
				$modal_body.html($('#wpak-note-satisfied-box-question').html());
				$modal_footer.html(
					'<button id="satisfied-box-yes" type="button" class="btn btn-primary">'+ $('#wpak-note-satisfied-box-yes').html() +'</button>'+
					'<button id="satisfied-box-no" type="button" class="btn btn-default" >'+ $('#wpak-note-satisfied-box-no').html() +'</button>'
				);
			},
			display_not_satisfied_box: function(){
				$modal_body.html($('#wpak-note-not-satisfied-box-question').html());
				$modal_footer.html(
					'<button id="not-satisfied-box-yes" type="button" class="btn btn-primary">'+ $('#wpak-note-not-satisfied-box-yes').html() +'</button>'+
					'<button id="not-satisfied-box-no" type="button" class="btn btn-default" >'+ $('#wpak-note-not-satisfied-box-no').html() +'</button>'
				);
			},
			answer_to_first_box_later: function(){
				hide_modal();
			},
			answer_to_first_box_dont_ask_again: function(){
				hide_modal();
			},
			ok_to_vote: function(){
				hide_modal();
			},
			not_ok_to_vote: function(){
				hide_modal();
			},
			ok_to_email: function(){
				hide_modal();
			},
			not_ok_to_email: function(){
				hide_modal();
			},
			closed_box: function(){
				hide_modal();
			}
		});

		App.on( 'info:app-ready', function( ) {

			WpakNote.launchIfNeeded();

		} );

		$modal.on('click','button#first-box-yes',function(e){
			e.preventDefault();
			WpakNote.answerToFirstBox('yes');
		});

		$modal.on('click','button#first-box-no',function(e){
			e.preventDefault();
			WpakNote.answerToFirstBox('no');
		});

		$modal.on('click','button#first-box-later',function(e){
			e.preventDefault();
			WpakNote.answerToFirstBox('later');
		});

		$modal.on('click','button#first-box-dont-ask-again',function(e){
			e.preventDefault();
			WpakNote.answerToFirstBox('dont_ask_again');
		});

		$modal.on('click','button#satisfied-box-yes',function(e){
			e.preventDefault();
			WpakNote.answerToSatisfiedBox('yes');
		});

		$modal.on('click','button#satisfied-box-no',function(e){
			e.preventDefault();
			WpakNote.answerToSatisfiedBox('no');
		});

		$modal.on('click','button#not-satisfied-box-yes',function(e){
			e.preventDefault();
			WpakNote.answerToNotSatisfiedBox('yes');
		});

		$modal.on('click','button#not-satisfied-box-no',function(e){
			e.preventDefault();
			WpakNote.answerToNotSatisfiedBox('no');
		});
		
		$modal.on('click','button.wpak-note-close',function(e){
			e.preventDefault();
			WpakNote.closeBox();
		});
	});
	
});
