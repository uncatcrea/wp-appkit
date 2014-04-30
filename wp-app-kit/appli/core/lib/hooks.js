define(function (require) {

      "use strict";
      
      var $ = require('jquery');

      var hooks = {};
      
      var filters = {};
      var actions = {};
	  
      hooks.applyFilter = function(filter,value,params,context){
    	  if( filters.hasOwnProperty(filter) ){
    		  params.unshift(value);
    		  value = filters[filter].apply(context,params);
    	  }
    	  return value;
	  };
	  
	  hooks.addFilter = function(filter,callback){
		  filters[filter] = callback;
	  };
	  
	  hooks.removeFilter = function(filter,callback){
		  if( filters.hasOwnProperty(filter) ){
			  delete filters[filter];
		  }
	  };
	  
	  hooks.doAction = function(action,params,context){
		  var deferred = $.Deferred();
    	  if( actions.hasOwnProperty(action) ){
    		  params.unshift(deferred);
    		  actions[action].apply(context,params);
    	  }
    	  return deferred.promise();
	  };
	  
	  hooks.addAction = function(action,callback){
		  actions[action] = callback;
	  };
	  
	  hooks.removeAction = function(action,callback){
		  if( actions.hasOwnProperty(action) ){
			  delete actions[action];
		  }
	  };
      
      return hooks;
});