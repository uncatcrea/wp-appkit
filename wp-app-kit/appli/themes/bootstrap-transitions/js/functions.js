define(['jquery','core/theme-app','core/lib/storage.js','theme/js/transitions','theme/js/bootstrap.min'],function($,App,Storage,Transitions){
	
	//Example of how to overide the web services token computation :
	App.filter('get-token',function(token,auth_key,web_service){
		//Here you can make your own token computation, provided you've done the same  
		//on Wordpress side using the "wpak_generate_token" hook !
		return token; //For this example, we do nothing, just return the default token.
	});
	
	App.setAutoPageTransitions(Transitions.replace,Transitions.slideLeft,Transitions.slideRight);
	
	/*
	To handle page transitions manually :
	 
	App.setParam('custom-page-rendering', true);
	
	App.action('page-transition',function($deferred,$wrapper,$current,$next,current_page,previous_page){
		
		var direction = App.getTransitionDirection(current_page,previous_page);
		
		switch(direction){
			case 'left':
				Transitions.slideLeft($wrapper,$current,$next,$deferred);
				break;
			case 'right':
				Transitions.slideRight($wrapper,$current,$next,$deferred);
				break;
			case 'replace':
				Transitions.replace($wrapper,$current,$next,$deferred);
				break;
			default:
				Transitions.replace($wrapper,$current,$next,$deferred);
				break;
		};
		
	});
	*/
	
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
			App.showInfoPage(info.message); //Set your own custom message here
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
		App.getMoreComponentItems(function(){
			//If something is needed once items are retrieved, do it here.
			//Note : if the "get more" link is included in the archive.html template (which is recommended),
			//it will be automatically refreshed.
		});
	});
	
	App.on('page:leave',function(current_page,view){
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
	
	App.on('waiting:start',function(){
		$('#waiting').show();
	});
	
	App.on('waiting:stop',function(){
		$('#waiting').hide();
	});
	
	/*
	//Example of how to display your own, customized info page :
	$('#container').on('click','#custom-info',function(e){
		e.preventDefault();
		App.showInfoPage("This is a custom info message!","Custom info"); 
	});
	*/
	
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