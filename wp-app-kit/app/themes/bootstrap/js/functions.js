define(['jquery','core/theme-app','core/lib/storage.js','theme/js/bootstrap.min'],function($,App,Storage){
	
	//Example of how to overide the web services token computation :
	App.filter('get-token',function(token,auth_key,web_service){
		//Here you can make your own token computation, provided you've done the same  
		//on Wordpress side using the "wpak_generate_token" hook !
		return token; //For this example, we do nothing, just return the default token.
	});
	
	//Example of how to chose a template for a specific custom component :
	App.filter('template',function(template,current_page){
		if( current_page.fragment == 'component-total-custom' ){
			template = 'my-custom-component';
		}else if( current_page.global == 'custom-global-total-custom' ){
			template = 'my-single';
		}
		return template;
	});
	
	//Launch app contents refresh when clicking the refresh button :
	$('#refresh-button').click(function(e){
		e.preventDefault();
		closeMenu();
		App.refresh(function(){
			$('#feedback').removeClass('error').html('Content updated successfully :)').slideDown();
		});
	});
	
	App.on('refresh:start',function(){
		$('#refresh-button').addClass('refreshing');
	});
	
	App.on('refresh:end',function(){
		scrollTop();
		Storage.clear('scroll-pos');
		$('#refresh-button').removeClass('refreshing');
	});
	
	App.on('error',function(error){
		$('#feedback').addClass('error').html(error.message).slideDown();
	});
	
	App.on('info',function(info){
		if( info.event == 'no-content' ){
			
		}
	});
	
	//Automatically shows and hide Back button according to current page
	App.setAutoBackButton($('#go-back'),function(back_button_showed){
		if(back_button_showed){
			$('#refresh-button').hide();
		}else{
			$('#refresh-button').show();
		}
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
	
	//The menu can be dynamically refreshed, so we use "on" on parent div (which is always here):
	$('#navbar-collapse').on('click','a',function(e){
		//Close menu when we click a link inside it
		closeMenu();
	});
	
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
	
	App.on('page:leave',function(current_page,queried_page,view){
		//current_page.page_type can be 'list','single','page','comments'
		if( current_page.page_type == 'list' ){
			Storage.set('scroll-pos',current_page.fragment,$('body').scrollTop());
		}
	});
	
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
	
	//Example of how to display your own customized page :
	$('#container').on('click','#custom-page',function(e){
		e.preventDefault();
		//Render custom page using any custom template that you created in your theme (here, a template called "info.html") : 
		App.showCustomPage('info',{
			title:"Custom page example",
			content:"This is a custom page created dynamically in functions.js :-)",
			any_data_i_want:"Display anything you want! A key > value list for example :",
			my_list:{"First element":"Item one", "Second one":"Item two"}
		}); 
	});
	
	function closeMenu(){
		var navbar_toggle_button = $(".navbar-toggle").eq(0);
		if( !navbar_toggle_button.hasClass('collapsed') ){
			navbar_toggle_button.click(); 
		}
	}
	
	function scrollTop(){
		window.scrollTo(0,0);
	}
	
});