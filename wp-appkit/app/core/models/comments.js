define( function ( require ) {

	"use strict";

	var Backbone = require( 'backbone' );

	var Comment = Backbone.Model.extend( {
		defaults: {
			id: "",
			author: "",
			date: 0,
			content: "",
			depth: 1
		}
	} );

	var Comments = Backbone.Collection.extend( {
		model: Comment
	} );

	var PostComments = Backbone.Model.extend( {
		defaults: {
			post: null, //instance of Item model
			item_global: '',
			post_comments: null //instance of Comments collection
		}
	} );
	
	var PostCommentsMemory = Backbone.Collection.extend( {
		model: PostComments,
		addPostComments: function( post_id, post, item_global, comments ) {
			this.add( { id: post_id, post: post, item_global: item_global, post_comments: comments.clone() }, { merge: true } );
		}
	} );

	return { Comment: Comment, Comments: Comments, CommentsMemory: PostCommentsMemory };

} );