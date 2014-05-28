define(function (require) {

      "use strict";

      var _                   = require('underscore'),
      	  Backbone            = require('backbone'),
      	  Tpl                 = require('text!theme/menu.html'),
      	  MenuItems           = require('core/models/menu-items');
      	  
      return Backbone.View.extend({
    	  
  		initialize : function(options) {
  			
  	        this.template = _.template(Tpl);
  	        
  	        _.bindAll(this,'render');
  			
  			this.menu = new MenuItems.MenuItems();
  			
  	    },

  	    addItem : function(id,type,label){
  	    	this.menu.add({id:id,label:label,type:type,link: '#component-'+id});
  	    },
  	    
  	    resetAll : function(){
  	    	this.menu.reset();
  	    },
  	    
  	    render : function( ) {
  	    	var renderedContent = this.template({'menu_items':this.menu.toJSON()});
  	        $(this.el).html(renderedContent);
  	        return this;
  	    }
  	    
  	});
});