(function ( $ ) {
	"use strict";

	$(function () {

	   function formatUser(user) { return user.user_login; };
	   function formatUserId(item) {return item.id+":"+item.user_login; };
	
			
		$("#main_users_input").select2({
			placeholder: "Selectionnez un ou plusieurs utilisateurs",
			allowClear: true,
			multiple: true,
			// data: users,
			initSelection : function (element, callback) {
			    var data = [];
			    $(element.val().split(",")).each(function () {
			        var pieces = this.split(":");
					data.push({id: pieces[0], user_login: pieces[1]});
			    });//console.log(data);
			    callback(data);
			},
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		        url: client_jsvars.api_get_userinfo,
		        dataType: 'jsonp',
		        data: function (term, page) {
		            return {
		                term: term, // search term
		                term_field: 'user_login',
		            };
		        },
		        results: function (data, page) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
		            // console.debug(data)
		            return {results: data.status};

		        }
		    },
		    formatResult : formatUser,
		    formatSelection : formatUser,
		    id : formatUserId,
		});

		$("#second_users_input").select2({
			placeholder: "Selectionnez un ou plusieurs utilisateurs",
			allowClear: true,
			multiple: true,
			// data: secondusers,
			initSelection : function (element, callback) {
			    var data = [];
			    $(element.val().split(",")).each(function () {
			        var pieces = this.split(":");
					data.push({id: pieces[0], user_login: pieces[1]});
			    });
			    callback(data);
			},
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		        url: client_jsvars.api_get_userinfo,
		        dataType: 'jsonp',
		        data: function (term, page) {
		            return {
		                term: term, // search term
		                term_field: 'user_login',
		            };
		        },
		        results: function (data, page) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
		            // console.debug(data)
		            return {results: data.status};

		        }
		    },
		    formatResult : formatUser,
		    formatSelection : formatUser,
		    id : formatUserId,
		});


		//liste des posts du client

		function formatPost(post) { return post.title; };
	   function formatPostId(item) {return item.id+":"+item.title; };

		$("#customer_posts").select2({
			placeholder: "Selectionnez un ou plusieurs articles par leur titre",
			allowClear: true,
			multiple: true,
			// data: users,
			initSelection : function (element, callback) {
			    var data = [];
			    $(element.val().split(",")).each(function () {
			        var pieces = this.split(":");
					data.push({id: pieces[0], title: pieces[1]});
			    });//console.log(data);
			    callback(data);
			},
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		        url: client_jsvars.api_queryAttachablePosts,
		        dataType: 'jsonp',
		        data: function (term, page) {
		            return {
		                term: term, // search term
		            };
		        },
		        results: function (data, page) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
		            // console.debug(data)
		            return {results: data.posts};

		        }
		    },
		    formatResult : formatPost,
		    formatSelection : formatPost,
		    id : formatPostId,
		});


		//le client d'un post
		// var clients = [];
		function formatClient(client) { 
			console.debug(client);
			var pieces = client.split(":");
			return pieces[1]; 
		};
		function formatClientId(item) {return item.id+":"+item.text; };
	   
		console.debug('alors ');
		jQuery("#kz_customer").select2({
			placeholder: "Selectionnez un client",
			allowClear : true,
	        data : clients,
	        initSelection : function (element, callback) {
	        	var pieces = element.val().split(":");
	        	var data = {id: pieces[0], text: pieces[1]};
		        callback(data);
		    },
		    formatResult : formatClient,
		    formatSelection : formatClient,
		    id : formatClientId,
		});
				
		if (!client_jsvars.is_user_admin)
			jQuery("#kz_customer").select2("enable", false);	
			

	});

}(jQuery));