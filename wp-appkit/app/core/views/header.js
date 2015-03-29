define(function (require) {

      "use strict";

      var _                   = require('underscore'),
		  $                   = require('jquery'),
      	  Backbone            = require('backbone'),
      	  Config              = require('root/config'),
      	  ThemeTplTags	 	  = require('core/theme-tpl-tags'),
      	  Utils               = require('core/app-utils'),
		  Hooks               = require('core/lib/hooks');
      
      var tpl = null;
      var contains_menu = false;
      	  
      return Backbone.View.extend({
  		
  		initialize : function(args) {
  			var _this = this;
  			require(['text!theme/header.html'],
  					function(_tpl){
  						tpl = _tpl;
  						contains_menu = tpl.match(/<%=\s*menu\s*%>/) !== null;
  						_this.template = _.template(tpl);
  						args.do_if_template_exists(_this);
  	      		  	},
  	      		  	function(error){
  	      		  		Utils.log('Info : no theme/header.html found in theme'); 
  	      		  		args.do_if_no_template();
  	      		  	}
  			);
  	    },

  	    render : function(){
  	    	if( tpl !== null ){
				var template_args = {
					title : Config.app_title, 
					menu : '<div id="app-menu"></div>', 
					TemplateTags : ThemeTplTags
				};
				
				/**
				 * Use this 'template-args' filter to pass custom data to your
				 * templates.
				 * 
				 * @param template_args : JSON object : the default template data to filter
				 * Params passed to the filter : 
				 * - view type : String
				 * - template name : String
				 * - view object : Backbone view object
				 */
				template_args = Hooks.applyFilters( 'template-args', template_args, ['header','header',this] );
				
	  	    	var renderedContent = this.template(template_args);
	  	        $(this.el).html(renderedContent);
  	    	}
  	        return this;
  	    },
  	    
  	    templateExists : function(){
  	    	return tpl !== null;
  	    },
  	    
  	    containsMenu : function(){
  	    	return contains_menu;
  	    }
  	    
  	});
});