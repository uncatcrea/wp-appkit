define(['jquery','core/theme-app','core/modules/storage','core/theme-tpl-tags','theme/js/jquery.velocity.min'],function($,App,Storage,TplTags){

	/* App Events */

    // Refresh process begins
    App.on('refresh:start',function(){

        // the refresh button begins to spin
        $("#refresh-button").removeClass("refresh-off").addClass("refresh-on");

    });

    // Refresh process ends
    App.on('refresh:end',function(result){

		// Reset scroll position
        scrollTop();
        Storage.clear('scroll-pos'); 
		
		// Stop spinnning for the refresh button
        $("#refresh-button").removeClass("refresh-on").addClass("refresh-off");
		
        // Change active items in the left menu
		$("#menu li").removeClass("menu-active-item");
		$("#menu li:first-child").addClass("menu-active-item");
		
		// Display if the refresh process has worked or not
        // TODO : if an errors occurs we should not reset scroll position
        if ( result.ok ) {
			showMessage("Content updated successfully :)");
		}else{
			showMessage(result.message);
		}

	});

	// Error occurs
    App.on('error',function(error){

        // Display error message
        showMessage(error.message);

    });

	// A new screen is displayed
    App.on('screen:showed',function(current_screen,view){
        //current_screen.screen_type can be 'list','single','page','comments'
        
		// iOS back button support
        if (TplTags.displayBackButton()) {
			
            // Display iOS back button
            $("#back-button").css("display","block");
			$("#menu-button").css("display","none");
		
        } else {
			
            // Display the menu button as iOS back button is not supported
            $("#back-button").css("display","none");
			$("#menu-button").css("display","block");

        }

		// Left menu is open
        if (isMenuOpen) {

			// Close the left menu
            $("#content").css("left","85%");
			closeMenu();

        }

		// A post or a page is displayed
        if (current_screen.screen_type=="single"||current_screen.screen_type=="page") {
			
            // Prepare <img> tags for styling
            cleanImgTag();
            
            // Redirect all hyperlinks clicks
            $("#container").on("click",".single-template a",openInBrowser);
		
        }

		// A post list is displayed
        if( current_screen.screen_type == "list" ){
			
            // Retrieve any memorized scroll position
            // If a position has been memorized, scroll to it
            // If not, scroll to the top of the screen
            var pos = Storage.get("scroll-pos",current_screen.fragment);
			if( pos !== null ){
				$("#content").scrollTop(pos);
            }else{
				scrollTop();
			}
		}else{
			scrollTop();
		}
        
	});

    // About to change the current screen
	App.on('screen:leave',function(current_screen,queried_screen,view){
		//current_screen.screen_type can be 'list','single','page','comments'
		
        // If the current screen is a list
        // Memorize the current scroll position
        if( current_screen.screen_type == "list" ){
			Storage.set("scroll-pos",current_screen.fragment,$("#content").scrollTop());
		}
        
	});
    
    /* PhoneGap Plugins Support */
    
    // Status Bar
     try {
        StatusBar.overlaysWebView(false);
        StatusBar.styleDefault();
        StatusBar.backgroundColorByHexString("#F8F8F8");
    } catch(e) {
        alert("StatusBar plugin not available");
        // https://build.phonegap.com/plugins/715
    }

    // InApp Browser
    
	/* UI Events */
    
	var isMenuOpen = false;

    // Event bindings
	$("#container").on("touchstart","#menu-button",menuButtonTapOn);
	$("#container").on("touchend","#menu-button",menuButtonTapOff);

	$("#container").on("touchstart","#refresh-button",refreshTapOn);
	$("#container").on("touchend","#refresh-button",refreshTapOff);

	$("#container").on("click","#menu li a",menuItemTap);
	$("#container").on("click","#content .content-item a",contentItemTap);

	$("#container").on("touchstart","#back-button",backButtonTapOn);
    $("#container").on("touchend","#back-button",backButtonTapOff);

    /* Functions */

    // Open left menu
	function openMenu() {

		$("#menu").css("display","block");

        $("#content,#header").velocity({
			left:"85%",
			},300, function() {
				isMenuOpen=true;
			});
	}

	// Close left menu
    function closeMenu(action,menuItem) {

		isMenuOpen = false;

        $("#content,#header").velocity({
			left:"0",
		},300, function() {

				$("#menu").css("display","none");

				if (action==1) {
					App.navigate(menuItem.attr("href"));
				}

			});
	}

	// Determine if we open or close the left menu
    function toggleMenu() {

		if (isMenuOpen) {
			closeMenu();
		} else {
			openMenu();
		}
	}

	// Finger presses on the menu button (1/2)
    function menuButtonTapOn() {
        // Effect
		$("#menu-button").removeClass("button-tap-off").addClass("button-tap-on");
	}

	// Finger unpresses the menu button (2/2)
	function menuButtonTapOff() {

		// Effect
        $("#menu-button").removeClass("button-tap-on").addClass("button-tap-off");
		
        // We open or close the left menu according to its current state
        toggleMenu();
		
        return false;

	}

	// Finger presses a menu item
    function menuItemTap() {	

		if (isMenuOpen) {

			// Highlight the new current item
            $("#menu li").removeClass("menu-active-item");
			$(this).closest("li").addClass("menu-active-item");

			// Close the left menu
            closeMenu(1,$(this));
		}

		return false;
	}

	// Finger presses an item in a list
    function contentItemTap() {

		if (!isMenuOpen) {
			
            // Change the current screen
            App.navigate($(this).attr("href"));
		
        } else {
            
            // If the menu is open, close it
			closeMenu();

        }
		return false;
	}

	// Display success/failure message
    function showMessage(msgText) {
		$("#refresh-message").html(msgText);
		$("#refresh-message").removeClass("message-off").addClass("message-on");
		setTimeout(hideMessage,3000);
	}

	// Hide success/failure message
    function hideMessage() {
		$("#refresh-message").removeClass("message-on").addClass("message-off");	
		$("#refresh-message").html("");
	}

	// Finger presses refresh button (1/2)
    function refreshTapOn() {
		$("#refresh-button").removeClass("button-touch-off").addClass("button-touch-on");
	}

	// Finger unpresses refresh button (2/2)
    function refreshTapOff() {
		
        // TODO : give the ability to stop the refresh manually
        
        // Check if app's refreshing
        if (!App.isRefreshing()) {
			$("#refresh-button").removeClass("button-touch-on").addClass("button-touch-off");
			$("#refresh-button").removeClass("refresh-off").addClass("refresh-on");
			
            // Start the refresh process
            App.refresh();
		}
	
    }

	// Stop refresh button animation when refresh ends
    function stopRefresh() {
		$("#refresh-button").removeClass("refresh-on").addClass("refresh-off");	
	}

	// Finger presses iOS back button (1/2)
    function backButtonTapOn() {
		$("#back-button").removeClass("button-tap-off").addClass("button-tap-on");
	}

	// Finger unpresses iOS back button (2/2)
    function backButtonTapOff() {
		$("#back-button").removeClass("button-tap-on").addClass("button-tap-off");
		
        // Go back to the previous screen
        App.navigate(TplTags.getPreviousScreenLink());
	}

    // Scroll to the top of the screen
    function scrollTop(){
		window.scrollTo(0,0);
	}
    
    // Prepare <img> tags for proper styling (responsive)
	function cleanImgTag() {
		$(".single-template img").removeAttr("width height");
		$(".single-template .wp-caption").removeAttr("style");
		$(".single-template .wp-caption a").removeAttr("href");
	}
    
    // Hyperlinks clicks handler
    // Relies on the InApp Browser PhoneGap Plugin
    function openInBrowser(e) {
        window.open(e.target.href,"_blank","location=yes");
        e.preventDefault();
    }

});