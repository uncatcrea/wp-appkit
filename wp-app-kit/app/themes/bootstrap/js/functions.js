define(['jquery','core/theme-app','core/lib/storage','theme/js/bootstrap.min'],function($,App,Storage){
	
	/**
	 * Launch app contents refresh when clicking the refresh button :
	 */
	$('#refresh-button').click(function(e){
		e.preventDefault();
		closeMenu();
		App.refresh(function(){
			$('#feedback').removeClass('error').html('Content updated successfully :)').slideDown();
		});
	});
	
	/**
	 * Animate refresh button when the app starts refreshing
	 */
	App.on('refresh:start',function(){
		$('#refresh-button').addClass('refreshing');
	});
	
	/**
	 * Stop refresh button animation when the app stops refreshing
	 */
	App.on('refresh:end',function(){
		scrollTop();
		Storage.clear('scroll-pos'); 
		$('#refresh-button').removeClass('refreshing');
	});
	
	/**
	 * When an error occurs, display it in the feedback box
	 */
	App.on('error',function(error){
		$('#feedback').addClass('error').html(error.message).slideDown();
	});
	
	/**
	 * Hide the feedback box when clicking anywhere in the body
	 */
	$('body').click(function(e){
		$('#feedback').slideUp();
	});
	
	/**
	 * Automatically shows and hide Back button according to current page
	 */
	App.setAutoBackButton($('#go-back'),function(back_button_showed){
		if(back_button_showed){
			$('#refresh-button').hide();
		}else{
			$('#refresh-button').show();
		}
	}); 
	
	/**
	 * Allow to click anywhere on post list <li> to go to post detail :
	 */
	$('#container').on('click','li.media',function(e){
		e.preventDefault();
		var navigate_to = $('a',this).attr('href');
		App.navigate(navigate_to);
	});
	
	/**
	 * Close menu when we click a link inside it.
	 * The menu can be dynamically refreshed, so we use "on" on parent div (which is always here):
	 */
	$('#navbar-collapse').on('click','a',function(e){
		closeMenu();
	});
	
	/**
	 * "Get more" button in post lists
	 */
	$('#container').on('click','.get-more',function(e){
		e.preventDefault();
		$(this).attr('disabled','disabled').text('Loading...');
		App.getMoreComponentItems(function(){
			//If something is needed once items are retrieved, do it here.
			//Note : if the "get more" link is included in the archive.html template (which is recommended),
			//it will be automatically refreshed.
			$(this).removeAttr('disabled');
		});
	});
	
	/**
	 * Do something before leaving a page.
	 * Here, if we're leaving a post list, we memorize the current scroll position, to 
	 * get back to it when coming back to this list.
	 */
	App.on('page:leave',function(current_page,queried_page,view){
		//current_page.page_type can be 'list','single','page','comments'
		if( current_page.page_type == 'list' ){
			Storage.set('scroll-pos',current_page.fragment,$('body').scrollTop());
		}
	});
	
	/**
	 * Do something when a new page is showed.
	 * Here, if we arrive on a post list, we resore the scroll position
	 */
	App.on('page:showed',function(current_page,view){
		//current_page.page_type can be 'list','single','page','comments'
		if( current_page.page_type == 'list' ){
			var pos = Storage.get('scroll-pos',current_page.fragment);
			if( pos !== null ){
				$('body').scrollTop(pos);
			}else{
				scrollTop();
			}
		}else{
			scrollTop();
		}
	});
	
	/**
	 * Manually close the bootstrap navbar
	 */
	function closeMenu(){
		var navbar_toggle_button = $(".navbar-toggle").eq(0);
		if( !navbar_toggle_button.hasClass('collapsed') ){
			navbar_toggle_button.click(); 
		}
	}
	
	/**
	 * Get back to the top of the page
	 */
	function scrollTop(){
		window.scrollTo(0,0);
	}
	
});