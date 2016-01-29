
(function($){

	$(document).ready(function() {

		$("#customer_users").selectize({
		    options : [],
		    hideSelected : true,
		    create: false,
		    valueField: 'ID',
		    labelField: 'display_name',
		    searchField: ['display_name','user_email'],
		    delimiter: ',',
		    plugins: ['remove_button'],
		    render: {
		    	item: function(item, escape) { 
		            return '<div><span class="name">' + escape(item.display_name) + '</span><span class="email">' + escape(item.user_email) + '</span></div>';
		        },
		        option: function(item, escape) {
		            return 	'<div><span class="label">' + escape(item.display_name) + '</span><span class="caption">' + escape(item.user_email) + '</span></div>';
		        }
		    },
		    load: function(query, callback) {
		        if (!query.length) return callback();
		        $.ajax({
		            url: client_jsvars.api_get_userinfo ,
		            data: {
		                term: query,
		            },
		            error: function() {
		                callback();
		            },
		            success: function(data) {
		            	callback(data.status.map(function(item) {
						    return {
						        ID: item.data.ID,
						        display_name : item.data.display_name,
						        user_email : item.data.user_email
						    };
						}));
		            }
		        });
		    }
		});
	});


})(jQuery)
				