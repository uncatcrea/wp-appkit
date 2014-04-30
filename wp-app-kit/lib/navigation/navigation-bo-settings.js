var WpakNavigation = (function ($){
		
		var wpak = {};
		
		var get_parent_form_id = function(element){
			return $(element).closest('div.navigation-item-form').attr('id');
		};
		
		wpak.ajax_add_or_edit_navigation_row = function(data,callback){
			//TODO : add nonce (via wp_localize_script...)
			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'add_or_update',
				data: data
			};
			
			jQuery.ajax({
			  type: "POST",
			  url: ajaxurl,
			  data: data,
			  success: function(answer) {
				  callback(answer);
			  },
			  error: function(jqXHR, textStatus, errorThrown){
				  callback({'ok':0,'message':'Error submitting data'}); //TODO translate js messages
			  },
			  dataType: 'json'
			});
			
		};
		
		wpak.ajax_delete_navigation_row = function(post_id,navigation_item_id,callback){
			//TODO : add nonce (via wp_localize_script...)
			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'delete',
				data:  {'navigation_item_id': navigation_item_id, 'post_id': post_id}
			};
			
			jQuery.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: data,
				  success: function(answer) {
					  callback(answer);
				  },
				  error: function(jqXHR, textStatus, errorThrown){
					  callback({'ok':0,'type':'error','message':'Error deleting navigation item'}); //TODO translate js messages
				  },
				  dataType: 'json'
			});
		};
		
		wpak.ajax_move_navigation_row = function(post_id,positions,callback){
			//TODO : add nonce (via wp_localize_script...)
			var data = {
				action: 'wpak_edit_navigation',
				wpak_action: 'move',
				data:  {'positions': positions, 'post_id': post_id}
			};
			
			jQuery.ajax({
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
		$('#navigation-feedback').removeClass().addClass(type).html(message).show();
	}; 
	
	function hide_feedback(){
		$('#navigation-feedback').hide();
	}; 
	
	$('#navigation-wrapper').on('click','a.navigation-form-submit',function(e){
		e.preventDefault();
		$('#navigation-feedback').hide();
		var navigation_item_id = $(this).data('id');
		var edit = parseInt(navigation_item_id) != 0;
		var form_tr = edit ? $(this).parents('tr').eq(0) : null;
		var data = $('div#navigation-item-form-'+ navigation_item_id).find("select, textarea, input").serializeArray();
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
			}
			display_feedback(answer.type,answer.message);
		});
	});
	
	$('#navigation-wrapper').on('click','a.delete_navigation_item',function(e){
		e.preventDefault();
		$('#navigation-feedback').hide();
		var navigation_item_id = $(this).data('id');
		var post_id = $(this).data('post-id');
		WpakNavigation.ajax_delete_navigation_row(post_id,navigation_item_id,function(answer){
			if( answer.ok == 1 ){
				$('#navigation-items-table tr#navigation-item-row-'+navigation_item_id).remove();
			}
			display_feedback(answer.type,answer.message);
		});
	});
	
	$('table#navigation-items-table tbody').sortable({
		  axis: "y",
		  stop: function( event, ui ) {
			  var positions = {};
			  var table = $('table#navigation-items-table');
			  var post_id = table.data('post-id');
			  $('tbody tr',table).each(function(index){
				  $('#position-'+ $(this).data('id')).attr('value',index+1);
				  positions[$(this).data('id')] = index+1;
			  });
			  WpakNavigation.ajax_move_navigation_row(post_id,positions,function(answer){
				  display_feedback(answer.type,answer.message);
			  });
		  }
	});
	
	$('#add-new-item').click(function(e){
		e.preventDefault();
		$('#new-item-form').slideToggle();
	});
	
	$('#cancel-new-item').click(function(e){
		e.preventDefault();
		$('#new-item-form').slideUp();
	});
	
});