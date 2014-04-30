define(function (require) {

      "use strict";

      var Config              = require('root/config');
      
	  var phonegap = {};
	  
	  phonegap.isLoaded = function(){
		  return window.cordova != undefined;
	  };
	  
	  phonegap.hideSplashScreen = function(){
		  if( phonegap.isLoaded() && navigator.splashscreen !== undefined ){
			  navigator.splashscreen.hide();
		  }
	  };
	  
	  return phonegap;
});