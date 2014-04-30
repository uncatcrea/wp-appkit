define(['jquery','core/theme-app','core/theme-tpl-tags'],function($,App,TplTags){

	function scrollTop(){
		window.scrollTo(0,0);
	}
	
	App.setAutoContextClass(true); //Adds class on <body> according to the current page
	
	App.on('refresh:start',function(){
		$("#refresh-button").removeClass("refresh-off").addClass("refresh-on");
	});
	
	App.on('refresh:end',function(){
		
		scrollTop();
		
		$("#refresh-button").removeClass("refresh-on").addClass("refresh-off");
		
		$("#menu li").removeClass("menu-active-item");
		$("#menu li:first-child").addClass("menu-active-item");
		
		showMessage("Content updated successfully :)");
		
	});
	
	App.on('error',function(error){
		showMessage(error.message);
	});

	App.on('page:showed',function(current_page,view){
	//current_page.page_type can be 'list','single','page','comments'

		scrollTop();

		if (TplTags.displayBackButton()) {
			$("#back-button").css("display","block");
			$("#menu-button").css("display","none");
		} else {
			$("#back-button").css("display","none");
			$("#menu-button").css("display","block");
		}

		if (isMenuOpen) {

			$("#content").css("left","85%"); // Maintain the open state after the dynamic zone is refreshed
			closeMenu();

		}

		// Clean WP image code to allow proper styling
		if (current_page.page_type=="single") {
			cleanImgTag();
		}

	});

	// Clean WP image code to allow proper styling
	function cleanImgTag() {

		$(".single-template img").removeAttr("width height");
		$(".single-template .wp-caption").removeAttr("style");
		$(".single-template .wp-caption a").removeAttr("href");

	}

	// Touch back button in the header
	function backButtonTapOn() {
		$("#back-button").removeClass("button-touch-off").addClass("button-touch-on");
	}

	// Untouch back button in the header
	function backButtonTapOff() {
		$("#back-button").removeClass("button-touch-on").addClass("button-touch-off");
		App.navigate(TplTags.getPreviousPageLink());
	}

	// Open left drawer
	function openMenu() {

		$("#menu").css("display","block");

		$("#content,#header").animate({
			left:"85%",
			},300, function() {
				isMenuOpen=true;
			});
	}

	// Close left drawer
	function closeMenu(action,menuItem) {

		isMenuOpen = false;

		$("#content,#header").animate({
			left:"0",
		},300, function() {

				$("#menu").css("display","none");

				if (action==1) { // Close after menu item click
					App.navigate(menuItem.attr("href"));
				}
			});
	}

	// Open left drawer if it's closed and vice versa
	function toggleMenu() {

		if (isMenuOpen) {
			closeMenu();
		} else {
			openMenu();
		}
	}

	// Touch the menu button in the header
	function menuButtonTapOn() {
		$("#menu-button").removeClass("button-touch-off").addClass("button-touch-on");
	}

	// Untouch a menu button in the header
	function menuButtonTapOff() {

		$("#menu-button").removeClass("button-touch-on").addClass("button-touch-off");
		toggleMenu();
		return false; /* Stop click event bubling or menu item will be triggered as soon as the drawer is opened */

	}

	// Handle clicks on menu items
	function menuItemClick() {	

		if (isMenuOpen) {

			$("#menu li").removeClass("menu-active-item");
			$(this).closest("li").addClass("menu-active-item");

			closeMenu(1,$(this)); // Close to navigate
		}

		return false;
	}

	// Handle clicks on list items
	function contentItemClick() {

		if (!isMenuOpen) {
			App.navigate($(this).attr("href"));
		} else {
			closeMenu();
		}
		return false;
	}

	// Touch the refresh button (start animation)
	function refreshTapOn() {
		if (!App.isRefreshing()) {
			$("#refresh-button").removeClass("button-touch-off").addClass("button-touch-on");
		}
	}

	// Untouch the refresh button
	function refreshTapOff() {
		if (!App.isRefreshing()) {
			$("#refresh-button").removeClass("button-touch-on").addClass("button-touch-off");
			$("#refresh-button").removeClass("refresh-off").addClass("refresh-on");
			App.refresh();
		}
	}

	// Show/hide update messages
	function showMessage(msgText) {
		$("#refresh-message").html(msgText);
		$("#refresh-message").removeClass("message-off").addClass("message-on");
		setTimeout(hideMessage,3000);
		// TODO : handle msgType for color
	}

	function hideMessage() {
		$("#refresh-message").removeClass("message-on").addClass("message-off");	
	}

	var isMenuOpen = false; // OK but is there a recommended way to do that ?
	//var isRefreshing = false; // Not really used ?

	$("#container").on("touchstart","#menu-button",menuButtonTapOn);
	$("#container").on("touchend","#menu-button",menuButtonTapOff);
	$("#container").on("click","#menu li a",menuItemClick);

	$("#container").on("touchstart","#refresh-button",refreshTapOn);
	$("#container").on("touchend","#refresh-button",refreshTapOff);
	$("#container").on("click","#content .content-item a",contentItemClick);

	$("#container").on("touchstart","#back-button",backButtonTapOn);
    $("#container").on("touchend","#back-button",backButtonTapOff);

});