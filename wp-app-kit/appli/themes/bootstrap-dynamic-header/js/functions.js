define(['jquery','core/theme-app','theme/js/bootstrap.min'],function($,App){
	
	function closeMenu(){
		var navbar_toggle_button = $(".navbar-toggle").eq(0);
		if( !navbar_toggle_button.hasClass('collapsed') ){
			navbar_toggle_button.click(); 
		}
	}
	
	function scrollTop(){
		window.scrollTo(0,0);
	}
	
	App.setAutoContextClass(true); //Adds class on <body> according to the current page
	
	App.on('refresh:start',function(){
		$('#refresh-button').addClass('refreshing');
	});
	
	App.on('refresh:end',function(){
		scrollTop();
		$('#refresh-button').removeClass('refreshing');
	});
	
	App.on('error',function(error){
		$('#feedback').addClass('error').html(error.message).slideDown();
	});
	
	$('body').click(function(e){
		$('#feedback').slideUp();
	});
	
	//Allow to click anywhere on li to go to post detail :
	$('#container').on('click','li.media',function(e){
		e.preventDefault();
		var navigate_to = $('a',this).attr('href');
		App.navigate(navigate_to);
	});
	
	App.on('page:showed',function(current_page,view){
		scrollTop();
		//current_page.page_type can be 'list','single','page','comments'
	});
	
	function updateHeaderEvents(){
		
		$('#refresh-button').unbind().bind('click', function(e){
			e.preventDefault();
			closeMenu();
			App.refresh(function(){
				$('#feedback').removeClass('error').html('Content updated successfully :)').slideDown();
			});
		});
	
		if( App.isRefreshing() ){
			$('#refresh-button').addClass('refreshing');
		}
	}

	App.on('header:render',function(current_page,headerView){
		updateHeaderEvents();
	});
	
});