define(function (require) {

      "use strict";

      var storage = {};
      
      var data = {};
      
      storage.set = function(group,key,value){
    	  if( !data.hasOwnProperty(group) ){
    		  data[group] = {};
    	  }
    	  data[group][key] = value;
      };
      
      storage.get = function(group,key){
    	  var value = null;
    	  if( data.hasOwnProperty(group)){
    		  if( key != undefined ){
    			  if( data[group].hasOwnProperty(key) ){
    				  value = data[group][key];  
    			  }
    		  }else{
    			  value = data[group];  
    		  }
    	  }
    	  return value;
      };
      
      storage.clear = function(group){
    	  if( data.hasOwnProperty(group) ){
    		  delete data[group];
    	  }
      };
      
      storage.clearAll = function(){
    	  data = {};
      };
      
      return storage;
});