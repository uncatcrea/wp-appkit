var WpakNavigation = (function ($){

		var wpak = {};
		var subject = new Subject();

		var get_parent_form_id = function(element){
			return $(element).closest('div.navigation-item-form').attr('id');
		};

		wpak.ajax_add_or_edit_navigation_row = function(data,callback){

			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'add_or_update',
				data: data,
				post_id: wpak_navigation.post_id,
				nonce: wpak_navigation.nonce
			};

			$.ajax({
			  type: "POST",
			  url: ajaxurl,
			  data: data,
			  success: function(answer) {
				  callback(answer);
				  subject.notify( answer );
			  },
			  error: function(jqXHR, textStatus, errorThrown){
				  callback({'ok':0,'type':'error','message':'Error submitting data'}); //TODO translate js messages
			  },
			  dataType: 'json'
			});

		};

		wpak.ajax_delete_navigation_row = function(post_id,navigation_item_id,callback){

			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'delete',
				data:  {'navigation_item_id': navigation_item_id, 'post_id': post_id},
				post_id: wpak_navigation.post_id,
				nonce: wpak_navigation.nonce
			};

			$.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: data,
				  success: function(answer) {
					  callback(answer);
				  	  subject.notify( answer );
				  },
				  error: function(jqXHR, textStatus, errorThrown){
					  callback({'ok':0,'type':'error','message':'Error deleting navigation item'}); //TODO translate js messages
				  },
				  dataType: 'json'
			});
		};

		wpak.ajax_move_navigation_row = function(post_id,positions,callback){

			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'move',
				data:  {'positions': positions, 'post_id': post_id},
				post_id: wpak_navigation.post_id,
				nonce: wpak_navigation.nonce
			};

			$.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: data,
				  success: function(answer) {
					  callback(answer);
				  },
				  error: function(jqXHR, textStatus, errorThrown){
					  callback({'ok':0,'type':'error','message':'Error moving navigation item'}); //TODO translate js messages
				  },
				  dataType: 'json'
			});
		};

		wpak.ajax_edit_navigation_item_option = function(navigation_item_id,option,value,callback){

			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'edit_option',
				data:  {navigation_item_id: navigation_item_id, option: option, value: value},
				post_id: wpak_navigation.post_id,
				nonce: wpak_navigation.nonce
			};

			$.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: data,
				  success: function(answer) {
					  callback(answer);
				  },
				  error: function(jqXHR, textStatus, errorThrown){
					  callback({'ok':0,'type':'error','message':'Error setting navigation item option "'+ option +'"'}); //TODO translate js messages
				  },
				  dataType: 'json'
			});
		};

		//Called hereunder AND from a "components observer" to refresh available components for navigation
		//when adding/editing components :
		wpak.refresh_available_components = function(post_id){

			var data = {
				action: 'wpak_update_available_components',
				post_id: wpak_navigation.post_id,
				nonce: wpak_navigation.nonce
			};

			$.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: data,
				  success: function(answer) {
					  if( answer.ok ==  1 ){
						  $('#components-available-for-navigation').html(answer.html);
					  }
				  },
				  error: function(jqXHR, textStatus, errorThrown){
					  callback({'ok':0,'type':'error','message':'Error refreshing available components'}); //TODO translate js messages
				  },
				  dataType: 'json'
			});
		};

		wpak.refresh_component = function( component ) {
			if( undefined === typeof component.id || !$( '.navigation-item-component-' + component.id ).length ) {
				return;
			}

			$( '.navigation-item-component-' + component.id + ' .label' ).text( component.label );
			$( '.navigation-item-component-' + component.id + ' .slug' ).text( component.slug );
		};

		wpak.addObserver = function( newObserver ) {
			subject.observe( newObserver );
		};

		wpak.removeObserver = function( deleteObserver ) {
			subject.unobserve( deleteObserver );
		};

		return wpak;

})(jQuery);

jQuery().ready(function(){
	var $ = jQuery;

	function serializeObject(a){
	    var o = {};
	    $.each(a, function() {
	        if (o[this.name] !== undefined) {
	            if (!o[this.name].push) {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(this.value || '');
	        } else {
	            o[this.name] = this.value || '';
	        }
	    });
	    return o;
	};

	function display_feedback(type,message){
		$( '.navigation-item-form .spinner' ).removeClass( 'is-active' );
		$('#navigation-feedback').removeClass().addClass(type).html(message).show();
	};

	function hide_feedback(){
		$('#navigation-feedback').hide();
	};

	$('#navigation-wrapper').on('click','a.navigation-form-submit',function(e){
		e.preventDefault();
		$( '.navigation-item-form .spinner' ).addClass( 'is-active' );
		$('#navigation-feedback').hide();
		var navigation_item_id = $(this).data('id');
		var edit = parseInt(navigation_item_id) != 0;
		var form_tr = edit ? $(this).parents('tr').eq(0) : null;
		var data = $('div#navigation-item-form-'+ navigation_item_id).find("select, textarea, input").serializeArray();
		if( !wpak_navigation.display_modif_alerts || ( !edit && confirm(wpak_navigation.messages.confirm_add) || edit && confirm(wpak_navigation.messages.confirm_edit) ) ){
			WpakNavigation.ajax_add_or_edit_navigation_row(serializeObject(data),function(answer){
				if( answer.ok == 1 ){
					var table = $('#navigation-items-table tbody');
					if( !edit ){
						$('tr.no-component-yet',table).remove();
						table.append(answer.html);
						$('#new-item-form').slideUp();
					}else{
						form_tr.prev('tr').replaceWith(answer.html);
						form_tr.remove();
					}
					$('table#navigation-items-table tbody').sortable('refresh');
					WpakNavigation.refresh_available_components();
				}
				display_feedback(answer.type,answer.message);
			});
		}
		else {
			$( '.navigation-item-form .spinner' ).removeClass( 'is-active' );
		}
	});

	$('#navigation-wrapper').on('click','a.delete_navigation_item',function(e){
		e.preventDefault();
		$('#navigation-feedback').hide();
		var navigation_item_id = $(this).data('id');
		var post_id = $(this).data('post-id');
		if( !wpak_navigation.display_modif_alerts || confirm(wpak_navigation.messages.confirm_delete) ){
			WpakNavigation.ajax_delete_navigation_row(post_id,navigation_item_id,function(answer){
				if( answer.ok == 1 ){
					$('#navigation-items-table tr#navigation-item-row-'+navigation_item_id).remove();
					WpakNavigation.refresh_available_components();
				}
				display_feedback(answer.type,answer.message);
			});
		}
	});

	$('table#navigation-items-table tbody').sortable({
		axis: "y",
		stop: function( event, ui ) {
			var positions = { };
			var table = $( 'table#navigation-items-table' );
			var post_id = table.data( 'post-id' );
			$( 'tbody tr', table ).each( function( index ) {
				$( '#position-' + $( this ).data( 'id' ) ).attr( 'value', index + 1 );
				positions[$( this ).data( 'id' )] = index + 1;
			} );
			WpakNavigation.ajax_move_navigation_row( post_id, positions, function( answer ) {
				display_feedback( answer.type, answer.message );
			} );
		},
		helper: function( e, ui ) { //So that row's <td> don't collapse when moving items
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			} );
			return ui;
		}
	});

	$('#add-new-item').click(function(e){
		e.preventDefault();
		$('#new-item-form').slideToggle();
	});

	$('#navigation-wrapper').on('click','#cancel-new-item',function(e){
		e.preventDefault();
		$('#new-item-form').slideUp();
	});

	/* Icon slug deactivated for now
	
	$('#navigation-wrapper').on('click','.change-icon-slug',function(e){
		e.preventDefault();
		var nav_item_id = $(this).data('id');
		$('#nav-item-value-'+ nav_item_id).hide();
		$('#nav-item-input-'+ nav_item_id).show();
		var value = $('#span-'+ nav_item_id).html();
		$('#icon-'+ nav_item_id).val(value !== 'none' ? value : '').focus();
	});

	$('#navigation-wrapper').on('click','.change-icon-slug-ok',function(e){
		e.preventDefault();
		var nav_item_id = $(this).data('id');
		var post_id = $(this).data('post-id');
		WpakNavigation.ajax_edit_navigation_item_option(nav_item_id,'icon_slug',$('#icon-'+ nav_item_id).val(),function(answer){
			if( answer.ok == 1 ){
				$('#nav-item-input-'+ nav_item_id).hide();
				var new_value = answer.data;
				$('#span-'+ nav_item_id).html(new_value !== '' ? new_value : 'none'); //TODO : 'none' should be translated here!
				$('#nav-item-value-'+ nav_item_id).show();
			}
			display_feedback(answer.type,answer.message);
		});
	});

	$('#navigation-wrapper').on('click','.change-icon-slug-cancel',function(e){
		e.preventDefault();
		var nav_item_id = $(this).data('id');
		$('#nav-item-input-'+ nav_item_id).hide();
		$('#nav-item-value-'+ nav_item_id).show();
	});

	$('.menu-item-icon-input').bind('keypress', function(e){
		if ( e.keyCode == 13 ) {
			var nav_item_id = $(this).data('id');
			$('#change-icon-slug-ok-'+nav_item_id).click();
			e.preventDefault();
		}
	});
	
	*/

	var navigation_observer = {
		update: function( data ) {
			WpakNavigation.refresh_available_components();
			WpakNavigation.refresh_component( data.component );
		}
	}
	WpakComponents.addObserver( navigation_observer );

});