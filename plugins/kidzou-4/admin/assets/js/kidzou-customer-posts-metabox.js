
(function($){

$(document).ready(function() {

	$("#customer_posts").selectize({
	    options : [],
	    create: false,
	    hideSelected : true,
	    valueField: 'id',
	    labelField: 'title',
	    searchField: 'title',
	    delimiter: ',',
	    plugins: {
	    	'remove_button':{},
	    	// \'link\':{\'iconClass\':\'fa fa-external-link\'}
	    },
	    render: {
	    	item: function(item, escape) {
	            return '<div><span class="name">' + escape(item.title) + '</span></div>';
	        },
	        option: function(item, escape) {
	            return 	'<div><span class="title"><span class="name">' + escape(item.title) +
	            		 '</span></span></div>';
	        }
	    },
	    load: function(query, callback) {
	        if (!query.length) return callback();
	        $.ajax({
	            url: client_jsvars.api_queryAttachablePosts ,
	            data: {
	                term: query,
	            },
	            error: function() {
	                callback();
	            },
	            success: function(data) { 
	            	// console.debug(data);
	                callback(data.posts);
	            }
	        });
	    }
	});
});

})(jQuery)
				