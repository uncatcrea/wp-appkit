define(function (require) {

      "use strict";

      var $                   = require('jquery'),
		  _                   = require('underscore'),
      	  Backbone            = require('backbone'),
      	  Tpl                 = require('text!theme/menu.html'),
		  ThemeTplTags		  = require('core/theme-tpl-tags'),
      	  MenuItems           = require('core/models/menu-items'),
		  Hooks               = require('core/lib/hooks');
      	  
      return Backbone.View.extend({
    	  
  		initialize : function(options) {
  			
  	        this.template = _.template(Tpl);
  	        
  	        _.bindAll(this,'render');
  			
  			this.menu = new MenuItems.MenuItems();
  			
  	    },

  	    addItem : function(id,type,label,link,options){
  	    	this.menu.add(_.extend({id:id,label:label,type:type,link:link},options));
  	    },
  	    
  	    resetAll : function(){
  	    	this.menu.reset();
  	    },
  	    
  	    render : function( ) {
			
			var template_args = {
				menu_items : this.menu.toJSON(), 
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
			template_args = Hooks.applyFilters( 'template-args', template_args, ['menu','menu',this] );
			
  	    	var renderedContent = this.template(template_args);
			
  	        $(this.el).html(renderedContent);
  	        return this;
  	    }
  	    
  	});
});