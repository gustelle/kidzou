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
		                term_field: 'user_lastname',
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
	

		//PLUS TARD
		// $("#second_users_input").select2({
		// 	placeholder: "Selectionnez un ou plusieurs utilisateurs",
		// 	allowClear: true,
		// 	multiple: true,
		// 	// data: secondusers,
		// 	initSelection : function (element, callback) {
		// 	    var data = [];
		// 	    $(element.val().split(",")).each(function () {
		// 	        var pieces = this.split(":");
		// 			data.push({id: pieces[0], user_login: pieces[1]});
		// 	    });
		// 	    callback(data);
		// 	},
		// 	ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		//         url: client_jsvars.api_get_userinfo,
		//         dataType: 'jsonp',
		//         data: function (term, page) {
		//             return {
		//                 term: term, // search term
		//                 term_field: 'user_login',
		//             };
		//         },
		//         results: function (data, page) { // parse the results into the format expected by Select2.
		//             // since we are using custom formatting functions we do not need to alter remote JSON data
		//             // console.debug(data)
		//             return {results: data.status};

		//         }
		//     },
		//     formatResult : formatUser,
		//     formatSelection : formatUser,
		//     id : formatUserId,
		// });


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

	   if ($("#kz_customer").length) {
	   		$("#kz_customer").select2({
				placeholder: "Selectionnez un client",
				allowClear : true,
		        data : clients,
		        initSelection : function (element, callback) {
		        	var pieces = element.val().split(":");
		        	var data = {id: pieces[0], text: pieces[1]};
			        callback(data);
			    }
			});

			//mise à jour de Google place quand on choisit un client
			//le lieu est alimenté par le lieu par défaut du client
			$("#kz_customer").on("select2-selecting", function(e) { 
				console.debug ("selecting val="+ e.val+" choice="+ JSON.stringify(e.choice)); 

				//l'id du client est socké dans e.val
				jQuery.get(client_jsvars.api_getCustomerPlace, { 
		   				id 	: e.val
	   				}).done(function(data) {
	   					// console.log(data);
	   					if (data.status==="ok" && data.location.location_name!='') {
	   						kidzouPlaceModule.model.initPlace(
	   							data.location.location_name, 
	   							data.location.location_address, 
	   							data.location.location_web, 
	   							data.location.location_tel, 
	   							data.location.location_latitude,
	   							data.location.location_longitude,
	   							data.location.location_city);
	   					} 
	    			});

			});
					
			if (!client_jsvars.is_user_admin) {
				console.debug('user not admin, disabling customer field');
				$("#kz_customer").select2("enable", false);	
			}
	   }
			

	});

}(jQuery));