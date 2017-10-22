/*
 * All JavaScript required for the theme has to be placed in this file
 * Use RequireJS define to import external JavaScript libraries
 * To be imported, a JavaScript has to be a module (AMD)
 * http://www.sitepoint.com/understanding-requirejs-for-effective-javascript-module-loading/
 * If this is not the case: place the path to the library at the end of the define array
 * Paths are relative to the app subfolder of the wp-app-kit plugin folder
 * You don't need to specify the .js extensions
    
 * (AMD) jQuery          available as    $
 * (AMD) Theme App Core  available as    App
 * (AMD) Local Storage   available as    Storage
 * (AMD) Template Tags   available as    TemplateTags
 * (AMD) App Config      available as    Config
 * (AMD) Moment 2.10.6   available as    Moment (http://momentjs.com/)
 * (AMD) Velocity 1.2.3  available as    Velocity (but used with jQuery) (http://julian.com/research/velocity/)
 *       FitVids (https://github.com/davatron5000/FitVids.js)
 */
define([
    'jquery',
    'core/theme-app',
    'core/modules/storage',
    'core/theme-tpl-tags',
    'root/config',
    'theme/js/moment.min',
    'theme/js/velocity.min',
    'theme/js/jquery.fitvids'
    ], function($,App,Storage,TemplateTags,Config,Moment,Velocity) {

    
    
    /*
     * App's parameters
     */
    
    App.setParam( 'go-to-default-route-after-refresh', false ); // Don't automatically show default screen after a refresh
    App.setParam( 'custom-screen-rendering', true ); // Don't use default transitions and displays for screens

    
    
    /*
     * Init
     */

    /**
     * @desc Customizing the iOS status bar to match the theme, relies on // https://build.phonegap.com/plugins/715
     */
    try { // Testing if the Cordova plugin is available
        StatusBar.overlaysWebView(false);
        StatusBar.styleDefault();
        StatusBar.backgroundColorByHexString("#F8F8F8");
    } catch(e) {
        console.log("StatusBar plugin not available - you're probably in the browser");
    }

	// Global variables
    var isMenuOpen = false; // Stores if the off-canvas menu is currently opened or closed

    // Animated iOS spinner
    var spinner = $('<div class="spinner"><div class="bar1"></div><div class="bar2"></div><div class="bar3"></div><div class="bar4"></div><div class="bar5"></div><div class="bar6"></div><div class="bar7"></div><div class="bar8"></div><div class="bar9"></div><div class="bar10"></div><div class="bar11"></div><div class="bar12"></div></div>');

    
    
    /*
     * Filters
     */
    
    // @desc Add template args
    App.filter( 'template-args', function( template_args, view_type, view_template ) {
        
        // Template parameters for single, page, archive and comments
        if (view_type == 'single' || view_type == 'page' || view_type == 'archive') {
            
            
            // Get Twitter like date format to single, archive and comments templates
            // Relies on MomentJS available as Moment()
            template_args.getCustomDate = function(postDate) {

                var gmtOffSetSec = Config.gmt_offset * 3600; // Get GMT offset as defined in config.js
                
                var momentNow = Moment(); // Get current date and time
                
                var momentPostDate = Moment(new Date((postDate-gmtOffSetSec)*1000)); // Get the post date

                // Get the duration between current date and the post date
                var diffDays = momentNow.diff(momentPostDate, 'days');
                
                var customPostDate;

                if (diffDays == 0) { // Duration is less than a day (eg. 8 hours ago)
                    customPostDate = momentPostDate.fromNow();
                } else { // Duration is more than a day (eg. March 3rd 2014)
                    // @todo: find a way to let dev localize date formats
                    customPostDate = momentPostDate.format('MMMM Do YYYY');
                }

                return customPostDate;
            }
            
        }
        
        // Return parameters and functions
        return template_args;
        
    } );
	
	//@desc Memorize the last history action so that we can decide what to do when
	//doing "single to single" transitions:
	var last_history_action = '';

	// @desc Catch if we're going to a single and coming from a single (it is the case when clicking on a post in the last posts widget at the bottom of a post)
    // Update properly the history stack
    App.filter( 'make-history', function( history_action, history_stack, queried_screen, current_screen, previous_screen ) {

        if( queried_screen.screen_type === 'single' && current_screen.screen_type === 'single' ) {
            if ( ( queried_screen.item_id !== previous_screen.item_id ) ) { // Going to a single to another single that is not the one we came from
                history_action = 'push';
            } else { // Going back to the previous single
                history_action = 'pop';
            }
        }
        
		last_history_action = history_action;
		
        // Return the proper history action
        return history_action;

    });
	
	// @desc Handle "single to single" transition:
	App.filter( 'transition-direction', function( transition, current_screen, next_screen ){
		
		if( current_screen.screen_type === 'single' && next_screen.screen_type === 'single' ) {
			if ( last_history_action === 'push' ) {
				transition = 'next-screen';
			} else {
				transition = 'previous-screen';
			}
			
		}
		
		return transition;
	});

    // @desc Handle transitions for deeplinks:
    App.filter( 'transition-direction', function( transition, current_screen, next_screen ){

        //Display single in a slide up panel when opening from deeplinks:
        if( next_screen.screen_type === 'single' && _.isEmpty( current_screen ) ) {
                transition = 'next-screen';
        }

        return transition;
    });
    

    /*
     * Actions
     */
    
    // @desc Detect transition types (aka directions) and launch corresponding animations
    App.action( 'screen-transition', function( $wrapper, $current, $next, current_screen, next_screen, $deferred ) {

        // Get the direction keyword from current screen and  previous screen
        var direction = App.getTransitionDirection( current_screen, next_screen );
        
        switch ( direction ) {
            case 'next-screen': // Archive to single
                transition_slide_next_screen( $wrapper, $current, $next, current_screen, next_screen, $deferred );
                break;
            case 'previous-screen': // Single to archive
                transition_slide_previous_screen( $wrapper, $current, $next, current_screen, next_screen, $deferred );
                break;
            case 'default': // Default direction
                transition_default( $wrapper, $current, $next, current_screen, next_screen, $deferred );
                break;
            default: // Unknown direction
                transition_default( $wrapper, $current, $next, current_screen, next_screen, $deferred );
                break;
        }

    });

    
    
    /*
     * Transition animations
     */
    
    // @desc Archive to single animation
	transition_slide_next_screen = function ( $wrapper, $current, $next, current_screen, next_screen, $deferred ) {

        $wrapper.append($next); // Add the next screen to the DOM / Mandatory first action (notably to get scrollTop() working)

        // When transitioning from a list, memorize the scroll position in local storage to be able to find it when we return to the list
        if (current_screen.screen_type == "list") {
            Storage.set( "scroll-pos", current_screen.fragment, $current.scrollTop() ); // Memorize the current scroll position in local storage
        }

        // 1. Prepare next screen (the destination screen is not visible. We are before the animation)
        
        // Hide the single screen on the right
        $next.css({
			left: '100%'
		});

        // 2. Animate to display next screen
        
		// Slide screens wrapper from right to left
        $wrapper.velocity({
            left: '-100%'
        },{
            duration: 300,
            easing: 'ease-out',
            complete: function () {
                
                // remove the screen that has been transitioned out
				$current.remove();

				// remove CSS added specically for the transition
				$wrapper.attr( 'style', '' );

				$next.css({
				    left: '',
				});
                
				$deferred.resolve(); // Transition has ended, we can pursue the normal screen display steps (screen:showed)
            }
        });
	}

    // @desc Single to archive animation
	transition_slide_previous_screen = function ( $wrapper, $current, $next, current_screen, next_screen, $deferred ) {

        $wrapper.prepend($next); // Add the next screen to the DOM / Mandatory first action (notably to get scrollTop() working)
        
        // 1. Prepare next screen (the destination screen is not visible. We are before the animation)

        // Hide the archive screen on the left
		$next.css( {
			left: '-100%'
		} );

        // If a scroll position has been memorized in local storage, retrieve it and scroll to it to let user find its former position when he/she left
        if (next_screen.screen_type == "list") {
            var pos = Storage.get( "scroll-pos", next_screen.fragment );
            if (pos !== null) {
                $next.scrollTop(pos);
            } else {
                $next.scrollTop(0);
            }
        }

        // 2. Animate to display next screen

		// Slide screens wrapper from left to right
		$wrapper.velocity({
            left: '100%'
        },{
            duration: 300,
            easing: 'ease-out',
			complete: function () {

                // remove the screen that has been transitioned out
                $current.remove();

				// remove CSS added specically for the transition
                $wrapper.attr( 'style', '' );

                $next.css( {
                    left: '',
                } );
                
                $deferred.resolve(); // Transition has ended, we can pursue the normal screen display steps (screen:showed)
            }
        });
	}

    // @desc Default animation
    // Also used when the direction is unknown
	transition_default = function ( $wrapper, $current, $next, current_screen, next_screen, $deferred ) {
		
		// Simply replace current screen with the new one
        $current.remove();
		$wrapper.empty().append( $next );
		$deferred.resolve();
        
	};

    
    
	/**
     * App Events
     */

    // @desc Refresh process begins
	App.on('refresh:start',function(){

		// Start refresh icon animation
        $("#refresh-button").removeClass("refresh-off").addClass("refresh-on");
        
	});
     
    // @desc Refresh process ends
    // @param result
	App.on('refresh:end',function(result){

        // Navigate to the default screen
        App.navigateToDefaultRoute();

        Storage.clear('scroll-pos');    // Clear the previous memorized position in the local storage
        
		// The refresh icon stops to spin
		$("#refresh-button").removeClass("refresh-on").addClass("refresh-off");
		
		// Select the current screen item in off-canvas menu
        $("#menu-items li").removeClass("menu-active-item");
		$("#menu-items li:first-child").addClass("menu-active-item");
		
		/**
         * Display if the refresh process is a success or not
         * @todo if an error occurs we should not reset scroll position
         * @todo messages should be centralized to ease translations
         */
		if ( result.ok ) {
			showMessage("Content updated successfully");
		}else{
			showMessage(result.message);
		}

    });

	// @desc The app starts retrieving a new post from remote server
	App.on('info:load-item-from-remote:start',function(){
		// Start refresh icon animation
		$("#refresh-button").removeClass("refresh-off").addClass("refresh-on");
	});
	
	// @desc A new post was retrieved from remote server
	App.on('info:load-item-from-remote:stop',function(){
		// Stop refresh icon animation
        $("#refresh-button").removeClass("refresh-on").addClass("refresh-off");
	});

    // @desc An error occurs
    // @param error
	App.on('error',function(error){
        
        // Show message under the nav bar
        showMessage(error.message);
        
	});

    // @desc A screen has been displayed
    // @param {object} current_screen - Screen types: list|single|page|comments
    // @param view
    App.on('screen:showed',function(current_screen,view){

        /*
         * 1. Back button
         */
        
        // Show/Hide back button depending on the displayed screen
        if (TemplateTags.displayBackButton()) {
            $("#back-button").css("display","block");
			$("#menu-button").css("display","none");
        } else {
            $("#back-button").css("display","none");
			$("#menu-button").css("display","block");
        }

        /*
         * 2. Off canvas menu
         */
        
        // Close off-canvas menu
        if (isMenuOpen) {
			$("#app-canvas").css("left","85%"); 
			closeMenu();
		}

        /*
         * 3. Post list
         */
        
        if(current_screen.screen_type == "list") {

            // Change nav bar title (display the component label)
            // Todo: create a generic function
            if ( $('#app-header > h1').html() != current_screen.label ) {
                $('#app-header > h1').html(current_screen.label);
            }
            
            // Scroll position is handled in the preparation of the transition (transition_slide_previous_screen)
        }

		/*
         * 4. Single and page
         */
        

        // Get and store data necessary for the sharing
        // @TODO: separate sharing code from title code
        
        if (current_screen.screen_type=="single") {
            
            // Change nav bar title
            // Todo: create a generic function
            if ( $('#app-header > h1').html() != 'Article' ) {
                $('#app-header > h1').html('Article');
            }

        }

        if (current_screen.screen_type=="page") {
            
            // Change nav bar title
            // Todo: create a generic function
            if ( $('#app-header > h1').html() != '' ) {
                $('#app-header > h1').html('');
            }

        }
                        
        if (current_screen.screen_type=="single" || current_screen.screen_type=="page") {

            // Redirect all content hyperlinks clicks
            // @todo: put it into prepareContent()
            $("#app-layout").on("click", ".single-content a", openInBrowser);
            
            // Make any necessary modification to post/page content
            prepareContent();
            
            // Display videos and make them responsive
            // We defer video loading to keep transitions smooth
            loadAndFormatVideos();

		}

	});

    // @desc About to leave the current screen
    // @param {object} current_screen - Screen types: list|single|page|comments
    // @param queried_screen
    // @param view
	App.on('screen:leave',function(current_screen,queried_screen,view){

    });

    // @desc Catch when the device goes online
    // relies on https://github.com/apache/cordova-plugin-network-information
    // Possible values:
    // * Unknown connection
    // * Ethernet connection
    // * WiFi connection
    // * Cell 2G connection
    // * Cell 3G connection
    // * Cell 4G connection
    // * Cell generic connection
    // * No network connection
    App.on('network:online', function(event){
        
        // Get the current network state
        var ns = TemplateTags.getNetworkState(true);
        
        // Display the current network state
        showMessage(ns);
    });

    // @desc Catch when the device goes offline
    // @desc Catch when the device goes online
    // relies on https://github.com/apache/cordova-plugin-network-information
    // Possible values:
    // * Unknown connection
    // * Ethernet connection
    // * WiFi connection
    // * Cell 2G connection
    // * Cell 3G connection
    // * Cell 4G connection
    // * Cell generic connection
    // * No network connection
    App.on( 'network:offline', function(event){

        // Get the current network state
        var ns = TemplateTags.getNetworkState(true);

        // Display the current network state
        showMessage(ns);
    });

    
      
    /*
     * Event bindings
     * All events are bound to #app-layout using event delegation as it is a permanent DOM element
     * They became available as soon as the target element is available in the DOM
     * Single and page content click on hyperlinks bindings are done in screen:showed
     * .app-screen scroll event binding is done in screen:showed because event delegation is not possible for this kind of event
     */

    // Menu Button events
    $("#app-layout").on("touchstart","#menu-button",menuButtonTapOn);
	$("#app-layout").on("touchend","#menu-button",menuButtonTapOff);

    // Refresh Button events
    $("#app-layout").on("touchstart","#refresh-button",refreshTapOn);
	$("#app-layout").on("touchend","#refresh-button",refreshTapOff);

    // Menu Item events
	$("#app-layout").on("click","#menu-items li a",menuItemTap);
	$("#app-layout").on("click","#content .content-item a",contentItemTap);

	// Back button events
    $("#app-layout").on("touchstart","#back-button",backButtonTapOn);
    $("#app-layout").on("touchend","#back-button",backButtonTapOff);

    // Block clicks on images in posts
    $("#app-layout").on("click touchend","#single-content .content-image-link",function(e){e.preventDefault();});
    
    // Get more button events
    $( '#app-layout' ).on( 'touchend', '#get-more-button', getMoreButtonTapOff);
    
    /*
     * @desc Display default image if an error occured when loading an image element (eg. offline)
     * 1. Binding onerror event doesn't seem to work properly in functions.js
     * 2. Binding is done directly on image elements
     * 3. You can't use UnderscoreJS tags directly in WordPress content. So we have to attach an event to window.
     * 4. Content image onerror handlers are set in prepare-content.php
     * 5. Thumbnail event handlers are done in the templates archive.html and single.html
     */
    window.displayDefaultImage = function(o) {
        $(o).attr('src',TemplateTags.getThemeAssetUrl('img/img-icon.svg'));
    }

    
    
    /*
     * Functions
     */

    /*
     * 1. Off canvas menu
     */

    // @desc Open off-canvas menu
    function openMenu() {

		$("#menu-items").css("display","block");
        
        $("#app-canvas").velocity({
			left:"85%",
        }, {
            duration: 300,
            complete: function() {
				setTimeout(function(){
                    isMenuOpen=true;
                },150);
			}
        });    
    }

    // @desc Close off-canvas menu
    // @param action (1 means that we close the off-canvas menu after clicking on a menu item)
    // @param menuItem
	function closeMenu(action,menuItem) {

		isMenuOpen = false;

        $("#app-canvas").velocity({
			left:"0",
		},300, function() {

            $("#menu-items").css("display","none");

            // We have tapped a menu item, let's open the corresponding screen
            if (action==1) {
                App.navigate(menuItem.attr("href"));
            }

        });
	}

    // @desc Open or close off-canvas menu (based on isMenuOpen variable)
	function toggleMenu() {  
		if (isMenuOpen) {
			closeMenu();
		} else {
 			openMenu();
		}
	}

    // @desc Finger presses the menu button
	function menuButtonTapOn(e) {
        e.preventDefault();
        $("#menu-button").removeClass("button-tap-off").addClass("button-tap-on"); // Switch icon state (on)
	}

    // @desc Finger releases the menu button
	function menuButtonTapOff(e) {
        e.preventDefault();
		$("#menu-button").removeClass("button-tap-on").addClass("button-tap-off"); // Switch icon state (off)
		toggleMenu(); // Open or close off-canvas menu
	}

    // @desc Finger taps one of the off-canvas menu item
	function menuItemTap(e) {	

        e.preventDefault();
        
		if (isMenuOpen) {

			// Select tapped item
            $("#menu-items li").removeClass("menu-active-item"); // Unselect all menu items
			$(this).closest("li").addClass("menu-active-item");

            // Close menu and navigate to the item's corresponding screen
            // @todo use navigate here rather than in close menu
			closeMenu(1,$(this));
            
		}

	}

    // @desc Finger taps one of the post item in a post list
	function contentItemTap(e) {

        e.preventDefault();
        
		if (!isMenuOpen) {
			App.navigate($(this).attr("href")); // Display post
		} else {
			closeMenu(); // Tapping a post item when the off-canvas menu is opened closes it
		}
	}

    /*
     * 2. Message bar
     */

    // @desc Show a message in the message bar during 3 sec
	function showMessage(msgText) {
		$("#app-message-bar").html(msgText);
		$("#app-message-bar").removeClass("message-off").addClass("message-on");
		setTimeout(hideMessage,3000);
	}

    // @desc Hide the message bar
	function hideMessage() {
		$("#app-message-bar").removeClass("message-on").addClass("message-off");	
		$("#app-message-bar").html("");
	}

    /*
     * 3. Refresh button
     */
        
    // @desc Finger taps the refresh button
	function refreshTapOn(e) {
        e.preventDefault();
        $("#refresh-button").removeClass("button-touch-off").addClass("button-touch-on");
	}

    // @desc Finger releases the refresh button
	function refreshTapOff(e) {
        e.preventDefault();
        if (!App.isRefreshing()) { // Check if the app is not already refreshing content
			$("#refresh-button").removeClass("button-touch-on").addClass("button-touch-off");
			$("#refresh-button").removeClass("refresh-off").addClass("refresh-on");
			App.refresh(); // Refresh content
		}
	}

    // @desc Stop spinning when refresh ends
	function stopRefresh() {
		$("#refresh-button").removeClass("refresh-on").addClass("refresh-off");	
	}

    /*
     * 4. Back button
     */

    // @desc Finger taps the back button
    function backButtonTapOn(e) {
        e.preventDefault();
		$("#back-button").removeClass("button-tap-off").addClass("button-tap-on");
	}

    // @desc Finger releases the back button
    function backButtonTapOff(e) {
		e.preventDefault();
        $("#back-button").removeClass("button-tap-on").addClass("button-tap-off");
        App.navigate(TemplateTags.getPreviousScreenLink()); // Navigate to the previous screen using the history stack
	}

    /*
     * 5. More button
     */

    // @desc Finger releases the get more button
    function getMoreButtonTapOff(e) {

        e.preventDefault();
        
        // Disable the Get more button and show spinner
        $('#get-more-button').attr('disabled','disabled');
        $("#get-more-button").append(spinner);

        // Get the next posts
        App.getMoreComponentItems(
            function() {

                // On success, hide spinner and activate the Get more button
                $("#get-more-button .spinner").remove();
                $('#get-more-button').removeAttr('disabled');

            }, function(error, get_more_link_data) {

                // On error, hide spinner and activate the Get more button
                // @todo: fire a specific message
                $("#get-more-button .spinner").remove();
                $('#get-more-button').removeAttr('disabled');
                
            }
        );
    }


    
    /*
     * 6. Content
     */
    
    // @desc Prepare content for proper display / Part of the work is done in /php/prepare-content.php
	function prepareContent() {
        
        // Modify embedded tweets code for proper display
        // Note: it is not possible to style embedded tweet in apps as Twitter doesn't identify the referer
        $(".single-template blockquote.twitter-tweet p").css( "display", "inline-block" );

        // Set content for unavailable content notification
        // Note: unavaible content is notified with [hide_from_apps notify="yes"] shortcode
        $(".wpak-content-not-available").html('Content unavailable');	
    }
    
    // @desc Hyperlinks click handler
    // Relies on the InAppBrowser Cordova Core Plugin / https://build.phonegap.com/plugins/233
    // Target _blank calls an in app browser (iOS behavior)
    // Target _system calls the default browser (Android behavior)
    // @param {object} e
    function openInBrowser(e) {
        
        try {
            cordova.InAppBrowser.open(e.target.href, '_blank', 'location=yes');    
        } catch(e) {
            window.open(e.target.href, '_blank', 'location=yes');
        }

        e.preventDefault();
    }

    // @desc Load videos / launched after transitions to keep them smooth
    // data-src are filled and src emptied in /php/prepare-content.php
    // We use the fitVids library to make videos responsive (https://github.com/davatron5000/FitVids.js)
    function loadAndFormatVideos() {

        $("iframe").each(function(index) {
            if ($(this).attr('data-src')) {
                $(this).attr('src', $(this).attr('data-src'));
            }
        });
        
        $('#single-content').fitVids();

    }
    
});