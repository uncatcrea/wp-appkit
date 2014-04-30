define(function (require) {

      "use strict";

      var Config = require('root/config');
      
	  var utils = {};
	
	  utils.log = function(){
		  if(console && Config.debug_mode == 'on'){
			  console.log.apply(console, arguments);
		  }
	  };
	  
	  return utils;
});