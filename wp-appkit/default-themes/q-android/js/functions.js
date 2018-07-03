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
    
    if ( Config.app_platform !== 'pwa' ) {
        /**
         * @desc Customizing the status bar to match the theme, relies on // https://github.com/apache/cordova-plugin-statusbar
         */
        try { // Testing if the Cordova plugin is available
            StatusBar.backgroundColorByHexString("#212121");
        } catch(err) {
            console.log("StatusBar plugin not available - you're probably in the browser");
        }
    }
    
	// Global variables
    var isMenuOpen = false; // Stores if the off-canvas menu is currently opened or closed
    var showRipple = false; // Show ripple effect for the element
    var $slideupPanelClones = []; // Array to store slide up panels' stack
    var $currentContainer; // Current slide up panel
    var $currentPanelContent; // Current slide up panel content container
    var effectLayerMemorizedStyle = ''; // ?

    // Selector caching
    var $slideUpPanel = $('#slideup-panel'); // Slide up panel model
    var $appCanvas = $('#app-canvas'); // App canvas element
        
    // Animated spinner
    // @todo: rename spinnner -> $spinner
    var spinner = '<svg class="spinner" width="66px" height="66px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"><circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="20"></circle></svg>';

    
    
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
            
        } else if ( view_template === 'menu' ) {
            template_args.Config = Config;
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
                transition_next_screen($wrapper, $current, $next, current_screen, next_screen, $deferred);
                break;
            case 'previous-screen': // Single to archive
                transition_previous_screen($wrapper, $current, $next, current_screen, next_screen, $deferred);
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
	transition_next_screen = function ( $wrapper, $current, $next, current_screen, next_screen, $deferred ) {

        $currentContainer = getContainer(); // Get instance of the current slide up panel
        var currentContainerId = getIdFor($currentContainer); // Get id of the current slide up panel
        $currentPanelContent = getPanelContentFor(currentContainerId); // Get content container for the current slide up panel

        $currentPanelContent.append($next); // Add the next screen element to the current content container

        if (current_screen.screen_type == "list") {
            Storage.set("scroll-pos",current_screen.fragment,$current.scrollTop()); // Memorize the current scroll position in local storage
        }

        // Display next screen
        $currentContainer.velocity({
            top: '0px',
            opacity: 1
        },{
            display: 'block',
            duration: 300,
            complete: function () {
                $deferred.resolve(); // Transition has ended, we can pursue the normal screen display steps (screen:showed)
            }
        });

	}

    // @desc Single to archive animation
	transition_previous_screen = function ( $wrapper, $current, $next, current_screen, next_screen, $deferred ) {
        
        // Display previous screen
        $currentContainer.velocity({
            top: '100px',
            opacity: 0
        },{
            queue: false,
            duration: 200,
            display: 'none',
			complete: function () {
                
                $wrapper.empty().append( $next ); // reload underlying screen
                
                $currentPanelContent.empty(); // empty the current slide up panel content
                
                // If we're returning to a list, scroll to the memorized scroll position in the list
                if (next_screen.screen_type == "list") {
                    var pos = Storage.get("scroll-pos",next_screen.fragment);
                    if (pos !== null) {
                        $next.scrollTop(pos);
                    } else {
                        $next.scrollTop(0);
                    }
                }

                removeContainer($currentContainer); // Remove current slide up panel from the stack of slide up panels
                
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
		if ( $currentContainer ) {
			removeContainer($currentContainer);
		}
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

        // Clear the previous memorized position in the local storage
        Storage.clear('scroll-pos');
        
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
		$("#refresh-button").hide();
        $(".loading-from-remote-button").show();
	});
	
	// @desc A new post was retrieved from remote server
	App.on('info:load-item-from-remote:stop',function(){
		// Stop refresh icon animation
        $(".loading-from-remote-button").hide();
		$("#refresh-button").show();
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
    App.on( 'screen:showed', function( current_screen, view ) {

        var currentScreenObject = App.getCurrentScreenObject();
        
        /*
         * 1. Off canvas menu
         */
        
        // Close off-canvas menu
        if (isMenuOpen) {
			$("#app-canvas").css("left","85%"); 
			closeMenu();
		}

        /*
         * 2. Post list
         */
        
        if ( current_screen.screen_type == "list" ) {
            
            // Change app bar title (display the component label)
            // Todo: create a generic function
            if ( $('#app-header > h1').html() != current_screen.label ) {
                $('#app-header > h1').html(current_screen.label);
            }
            
            // Scroll position is handled in the preparation of the transition (transition_previous_screen)
        }

		/*
         * 3. Single and page
         */
        
        // Page
        if (current_screen.screen_type=="page") {
            
            // Change nav bar title
            // Todo: create a generic function
            if ( $('#app-header > h1').html() != '' ) {
                $('#app-header > h1').html('');
            }

        }
                        
        // Actions shared by single and page
        if (current_screen.screen_type=="single" || current_screen.screen_type=="page") {

            // Make any necessary modification to post/page content
            prepareContent( currentScreenObject );
            
            // Display videos and make them responsive
            // We defer video loading to keep transitions smooth
            loadAndFormatVideosFor( currentScreenObject );

		}

	});

    // @desc About to leave the current screen
    // @param {object} current_screen - Screen types: list|single|page|comments
    // @param queried_screen
    // @param view
	App.on('screen:leave',function(current_screen,queried_screen,view){

        // Get id for the current screen
        var currentContainerId = getIdFor($currentContainer);

        /*
         * 1. Single or page
         */
        
        if (current_screen.screen_type === 'single') { // @todo handle page correctly
            
            // Remove all iframes to get a smooth closing transition (notably because of YouTube iframes)
            $('#' + currentContainerId + ' ' + 'iframe').remove();
    
        }
        
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

    // Menu button events *
    $("#app-layout").on("touchstart mousedown","#menu-button", menuButtonTapOn);
	$("#app-layout").on("touchend click","#menu-button", menuButtonTapOff);

    // Refresh button events *
    $("#app-layout").on("touchstart mousedown","#refresh-button", refreshTapOn);
	$("#app-layout").on("touchend click","#refresh-button", refreshTapOff);

    // Menu item events *
	$("#app-layout").on( "touchstart mousedown", "#menu-items li a", menuItemTapOn );
    $("#app-layout").on( "touchend click", "#menu-items li a", menuItemTapOff );
	
    // Content item events *
    $("#app-layout").on("touchstart","#content .content-item a", contentItemTapOn);
    $("#app-layout").on("click","#content .content-item a", contentItemTap);

    // Close slideup panel button events *
    $("#app-layout").on("touchstart mousedown","#back-button", closePanelButtonTapOn);
    $("#app-layout").on("touchend click","#back-button", closePanelButtonTapOff);

    // Block clicks on images in posts
    $("#app-layout").on("click touchend","#single-content .content-image-link",function(e){e.preventDefault();});
    
    // Get more button events *
    $('#app-layout').on('touchstart mousedown', '#get-more-button', getMoreButtonTapOn);
    $('#app-layout').on('touchend click', '#get-more-button', getMoreButtonTapOff);
    
    // Ripple effect events
    $('#app-layout').on( 'touchstart', '.has-ripple-feedback', rippleItemTapOn );
    $('#app-layout').on( 'touchend', '.has-ripple-feedback', rippleItemTapOff );
    
    // Redirect all content hyperlinks clicks
	// @todo: put it into prepareContent()
	$("#app-layout").on("click", ".single-content a", openInBrowser);
    
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
	function closeMenu( action, menuItem ) {

		isMenuOpen = false;

        $("#app-canvas").velocity({
			left: "0",
		}, {
            duration: 300,
            complete: function() {

                $("#menu-items").css("display","none");

                // We have tapped a menu item, let's open the corresponding screen
                if (action==1) {
                    App.navigate( menuItem.attr("href") );
                }
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
        showRipple = true; // Show ripple effect
	}

    // @desc Finger releases the menu button
	function menuButtonTapOff(e) {
        e.preventDefault();
        toggleMenu(); // Open or close off-canvas menu
	}

    function menuItemTapOn(e) {
        showRipple = true; // Show ripple effect
    }
    
    function menuItemTapOff(e) {
        
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

    function contentItemTapOn(e) {
        showRipple = true; // Show ripple effect
    }
    
    /*
     * 2. Message bar
     */

    // @desc Show toast message during 3 sec
	function showMessage( msgText, autoHide ) {
        
        // By default toast message hides itself
        var autoHide = typeof autoHide !== 'undefined' ? autoHide : true; // Can't use default value for parameters on Android
        
        $("#app-message-bar").velocity({
            opacity: .9
        },{
            queue: false,
            display: 'block',
            duration: 250,
            begin: function() {                
                $("#app-message-bar").html(msgText);            },
            complete: function () {
            }
        });
        
        // Hide toast message
        if ( autoHide === true ) {
            setTimeout( hideMessage, 3000 );
        }

	}

    // @desc Hide toast message
	function hideMessage() {
        $("#app-message-bar").velocity({
            opacity: 0
        },{
            queue: false,
            display: 'none',
            duration: 250,
            begin: function() {                
            },
            complete: function () {
                $("#app-message-bar").html("");
            }
        });
    }

    /*
     * 3. Refresh button
     */
        
    // @desc Finger taps the refresh button
	function refreshTapOn(e) {
        e.preventDefault();
        showRipple = true; // Show ripple effect
	}

    // @desc Finger releases the refresh button
	function refreshTapOff(e) {
        e.preventDefault();
        if (!App.isRefreshing()) { // Check if the app is not already refreshing content
            App.refresh(); // Refresh content
		}
	}

    // @desc Stop spinning when refresh ends
	function stopRefresh() {
		$("#refresh-button").removeClass("refresh-on").addClass("refresh-off");	
	}

    /*
     * 5. More button
     */

    // @desc Finger taps the get more button
    function getMoreButtonTapOn(e) {
        showRipple = true; // Show ripple effect
    }
    
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


    
    // @desc Finger taps the close button
    function closePanelButtonTapOn(e) {
        e.preventDefault();
        showRipple = true; // Show ripple effect
    }
    
    // @desc Finger releases the close button
    function closePanelButtonTapOff(e) {
        e.preventDefault();
        App.navigate(TemplateTags.getPreviousScreenLink()); // Navigate to the previous screen
    }
    

    
    /*
     * 8. Content
     */
    
    // @desc Prepare content for proper display / Part of the work is done in /php/prepare-content.php
	function prepareContent( currentScreenObject ) {

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
    // Link begins with #, route to an internal screen
    // @param {object} e
    function openInBrowser(e) {

        e.preventDefault();
        
        var $link = $(e.target);

        // Get the href attribute value
        // Using attr() rather than directly .href to get the not modified value of the href attribute
        var href = $link.attr('href');
        
        if ( href.charAt(0) !== '#' ) { // href doesn't begin with #
            
            try { // InAppBrowser Cordova plugin is available
                cordova.InAppBrowser.open( href, '_system', 'location=yes' ); // Launch the default Android browser
            } catch(err) { // InAppBrowser Cordova plugin is NOT available
                window.open( href, '_blank', 'location=yes' ); // Open a new browser window
            }
            
        } else { // href begins with # (ie. it's an internal link)
            
            //Add the 'q-theme-prevent-navigation' class to the link if you don't want the following 
            //auto navigation to occur:
            if ( !$link.hasClass( 'q-theme-prevent-navigation' ) ) {
                App.navigate( href );
            }
			
        }

    }

    // @desc Load videos / launched after transitions to keep them smooth
    // data-src are filled and src emptied in /php/prepare-content.php
    // We use the fitVids library to make videos responsive (https://github.com/davatron5000/FitVids.js)
    function loadAndFormatVideosFor( currentScreenObject ) { // @todo currently bugging with pages

        if ( currentScreenObject.screen_type === 'single' ) {
        
            var currentContainerId = getIdFor($currentContainer);

            $('#' + currentContainerId + ' ' + 'iframe').each(function(index) {
                if ($(this).attr('data-src')) {
                    $(this).attr('src', $(this).attr('data-src'));
                }
            });

            $('#' + currentContainerId + ' ' + '#single-content').fitVids();
        }
        
        if ( currentScreenObject.screen_type === 'page' ) {

            $("iframe").each(function(index) {
                if ($(this).attr('data-src')) {
                    $(this).attr('src', $(this).attr('data-src'));
                }
            });

            $('#single-content').fitVids();

        }

    }

    
    
    /*
     * 9. Ripple effect
     */
    
    // @desc Finger taps an element with ripple effect
    function rippleItemTapOn(e){

        if ( showRipple === true ) {

            // Get the original tapped element
            $currentTarget = $(e.currentTarget);

            // Add the ripple drop element and cache it for later use
            $currentTarget.prepend('<span class="ripple-drop"></span>');
            var $rippleDrop = $currentTarget.find('.ripple-drop');

            // Set the maximum size of the ripple drop according to the tapped element
            var rippleDropSize;

            if ($currentTarget.hasClass('ripple-small')) {
                rippleDropSize = 56;
            } else {
                rippleDropSize = Math.max($currentTarget.outerWidth(), $currentTarget.outerHeight());
            }

            $rippleDrop.css({
                'width' : rippleDropSize,
                'height' : rippleDropSize
            });

            // Set the overflow of the tapped element to get the ripple drop cut effect
            var h = $currentTarget.height();

            if ($currentTarget.css('overflow') != 'hidden') {
                $currentTarget.css('height', h + 'px');
                $currentTarget.css('overflow','hidden');
            }

            // Set the position of the ripple drop on the tapped element
            var tapPos;

            tapPos = getTapPos(e);

            $rippleDrop.css({
                'top' : (tapPos.tapTop - $currentTarget.offset().top) - $rippleDrop.width()/2,
                'left' : (tapPos.tapLeft - $currentTarget.offset().left) - ($rippleDrop.width()/2)
            });
            
            // Animate the ripple drop
            $rippleDrop.velocity({
                scaleX: 2.5,
                scaleY: 2.5,
                opacity: 0
            },{
                duration: 600,
                easing: 'linear',
                complete: function () {
                    $rippleDrop.remove(); // Remove the ripple drop when finished
                }
            });

        }
        
    }

    // @desc A finger releases an element with ripple effect
    function rippleItemTapOff(e){
        showRipple = false; // Reset ripple effect
    }

    // @desc Get global tap position
    function getTapPos(e) {
        
        var tapEvent, tapPos;
        tapEvent = e.originalEvent.touches[0];
        tapPos = {
            tapLeft: tapEvent.pageX,
            tapTop: tapEvent.pageY
        };
        return tapPos;
        
    }
    
    /*
     * 10. Slide up panels stack
     */
    
    // @desc Add and get current slide up panel
    function getContainer() {
        
        var p = $slideupPanelClones.length;
        
        // Clone slide up panel model
        $slideupPanelClones[p] = $slideUpPanel.clone();
        $slideupPanelClones[p].attr('id','slideup-panel-' + p);
        $appCanvas.append( $slideupPanelClones[p] );
        
        return $slideupPanelClones[p];
    
    }
    
    // @desc Get a cloned slide up panel ID
    function getIdFor(o) {
        return $(o).attr('id');
    }
    
    // @desc Get a slide up panel content area
    function getPanelContentFor(id) {
        var $panelContent = $( '#' + id + ' .panel-content' );
        return $panelContent;
    }

    // @desc Kill a cloned slide up panel 
    function removeContainer(o) {

        o.remove();
        
        $slideupPanelClones.pop();
        
        var p = $slideupPanelClones.length;
        
        if ( p > 0 ) {
            $currentContainer = $slideupPanelClones[p-1];
        }
        
    }
    
});
