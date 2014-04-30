define(function (require) {

      "use strict";
      
      var $ = require('jquery');

      var transitions = {};
      
      var config = {
    	delay:2000	  
      };

      transitions.replace = function($wrapper,$current,$next,$deferred){
    	  $current.remove();
    	  $wrapper.empty().append($next);
    	  $deferred.resolve();
      };
	
      transitions.slideLeft = function($wrapper,$current,$next,$deferred){
		$next.css({
		      position: 'absolute',
		      top: 0,
		      left: '100%',
		      height: '100%'
		});
		
		$wrapper.append($next);
	    
		$wrapper.animate(
	      {left:'-100%'},
	      config.delay,
	      'swing',
	      function () {
	        // remove the page that has been transitioned out
	    	$current.remove();
	    
	        // remove the CSS transition
	    	$wrapper.attr('style', '');
	    
	        // remove the position absoluteness
	    	$next.css({
	          top: '',
	          left: '',
	          position: ''
	        });
	    	
	    	$deferred.resolve();
	    });
	}
	
      transitions.slideRight = function($wrapper,$current,$next,$deferred){
		$next.css({
		      position: 'absolute',
		      top: 0,
		      left: '-100%',
		      height: '100%'
		});
		
		$wrapper.prepend($next);
	    
		$wrapper.animate(
	      {left:'100%'},
	      config.delay,
	      'swing',
	      function () {
	        // remove the page that has been transitioned out
	    	$current.remove();
	    
	        // remove the CSS transition
	    	$wrapper.attr('style', '');
	    
	        // remove the position absoluteness
	    	$next.css({
	          top: '',
	          left: '',
	          position: ''
	        });
	    	
	    	$deferred.resolve();
	    });
	};
	
    return transitions;
});