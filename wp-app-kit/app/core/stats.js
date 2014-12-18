define( function( require ) {

	"use strict";

	var Backbone = require( 'backbone' ),
			Config = require( 'root/config' ),
			_ = require( 'underscore' );

	require( 'localstorage' );

	var StatsModel = Backbone.Model.extend( {
		localStorage: new Backbone.LocalStorage( "Stats-" + Config.app_slug ),
		defaults: {
			id: "",
			current_version: '',
			last_version: '',
			count_open: 0,
			last_open_time: 0,
			current_open_time: 0,
			last_sync: 0
		}
	} );

	var StatsInstance = new StatsModel();
	StatsInstance.fetch();

	var convert_time_to_date = function( time ) {
		var date = new Date( time );

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

	function version_compare( v1, v2, options ) {
		var lexicographical = options && options.lexicographical,
				zeroExtend = options && options.zeroExtend,
				v1parts = v1.split( '.' ),
				v2parts = v2.split( '.' );

		function isValidPart( x ) {
			return ( lexicographical ? /^\d+[A-Za-z]*$/ : /^\d+$/ ).test( x );
		}

		if ( !v1parts.every( isValidPart ) || !v2parts.every( isValidPart ) ) {
			return NaN;
		}

		if ( zeroExtend ) {
			while ( v1parts.length < v2parts.length )
				v1parts.push( "0" );
			while ( v2parts.length < v1parts.length )
				v2parts.push( "0" );
		}

		if ( !lexicographical ) {
			v1parts = v1parts.map( Number );
			v2parts = v2parts.map( Number );
		}

		for ( var i = 0; i < v1parts.length; ++i ) {
			if ( v2parts.length == i ) {
				return 1;
			}

			if ( v1parts[i] == v2parts[i] ) {
				continue;
			}
			else if ( v1parts[i] > v2parts[i] ) {
				return 1;
			}
			else {
				return -1;
			}
		}

		if ( v1parts.length != v2parts.length ) {
			return -1;
		}

		return 0;
	}

	var Stats = {
		getStats: function(stat) {
			var stats = {};

			stats.count_open = Stats.getCountOpen();
			stats.last_open_date = Stats.getLastOpenDate();
			stats.version_diff = Stats.getVersionDiff();
			stats.version = stats.version_diff.current_version;
			stats.last_sync = Stats.getContentLastUpdated();

			if( stat !== undefined && stats.hasOwnProperty(stat) ){
				stats = stats[stat];
			}

			return stats;
		},
		incrementCountOpen: function() {
			StatsInstance.set( 'count_open', StatsInstance.get( 'count_open' ) + 1 );
			StatsInstance.save();
		},
		getCountOpen: function() {
			return StatsInstance.get( 'count_open' );
		},
		incrementLastOpenTime: function() {
			StatsInstance.set( 'last_open_time', StatsInstance.get( 'current_open_time' ) );
			StatsInstance.set( 'current_open_time', new Date().getTime() );
			StatsInstance.save();
		},
		getLastOpenDate: function() {
			return convert_time_to_date( StatsInstance.get( 'last_open_time' ) );
		},
		updateVersion: function() {
			var current_version = StatsInstance.get( 'current_version' );
			StatsInstance.set( 'last_version', current_version != '' ? current_version : Config.version );
			StatsInstance.set( 'current_version', Config.version );
			StatsInstance.save();
		},
		getVersionDiff: function() {
			var current_version = StatsInstance.get( 'current_version' );
			var last_version = StatsInstance.get( 'last_version' );
			var diff = version_compare( current_version, last_version, { lexicographical: false, zeroExtend: true } );
			return { current_version: current_version, last_version: last_version, diff: diff };
		},
		getContentLastUpdated: function() {
			return StatsInstance.get( 'last_sync' );
		},
		incrementContentLastUpdate: function() {
			StatsInstance.set( 'last_sync', Date.now() );
			StatsInstance.save();
		}
	};

	return Stats;
} );