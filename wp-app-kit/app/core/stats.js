define(function (require) {

    "use strict";

    var Backbone                 = require('backbone'),
		Config					 = require('root/config'),
    	_                   	 = require('underscore');
    
    require('localstorage');

    var StatsModel = Backbone.Model.extend({
		localStorage: new Backbone.LocalStorage("Stats-"+ Config.app_slug),
    	defaults : {
    		id : "",
    		count_open : 0,
            last_open_time : 0,
			current_open_time : 0
        }
    });
	
	var StatsInstance = new StatsModel();
	StatsInstance.fetch();
	
	var convert_time_to_date = function(time){
		var date = new Date(time);

		var year = date.getFullYear();
		var month = date.getMonth() + 1;
		month = month < 10 ? '0' + month : month;
		var day = date.getDate();
		day = day < 10 ? '0' + day : day;
		var hours = date.getHours();
		hours = hours < 10 ? '0' + hours : hours;
		var minutes = date.getMinutes();
		minutes = minutes < 10 ? '0' + minutes : minutes;
		var seconds = date.getSeconds();
		seconds = seconds < 10 ? '0' + seconds : seconds;

		return year + "-" + month + "-" + day + " " + hours + ":" + minutes + ":" + seconds;
	}
			
	var Stats = {
		increment_count_open: function(){
			StatsInstance.set('count_open',StatsInstance.get('count_open')+1);
			StatsInstance.save();
		},
		get_count_open: function(){
			return StatsInstance.get('count_open');
		},
		increment_last_open_time: function(){
			StatsInstance.set('last_open_time',StatsInstance.get('current_open_time'));
			StatsInstance.set('current_open_time',new Date().getTime());
			StatsInstance.save();
			
		},
		get_last_open_date: function(){
			return convert_time_to_date(StatsInstance.get('last_open_time'));
		}
	};

    return Stats;
});