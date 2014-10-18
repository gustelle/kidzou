(function ( $ ) {
	"use strict";

	$(function () {

		// Place your administration-specific JavaScript here

		var users = kidzou_jsvars.main_users;
		var secondusers = kidzou_jsvars.second_users;

		// self.queryUsers = function (query) {
	 //        jQuery.get(kidzou_jsvars.api_get_userinfo, { term: query.term, term_field: 'user_login' }, function(data) {
	 //        	var filteredResults = ko.utils.arrayFilter(data.status, function(user) {
	 //        			//console.log("filtering " + isNaN(parseInt(user.customer_id)) );
  //                   	return parseInt(user.customer_id) === 0 || isNaN(parseInt(user.customer_id));//on filtre la liste des users disponibles pour ne renvoyer que les users qui ne sont pas déjà attachés à un client
  //                   });
  //   			query.callback({
  //                   results: filteredResults
  //               });
  //   		});
	 //    };
	 //    self.formatUser = function(user) { return user.user_login; };
	 //    self.initSelectedUsers = function (element, callback) {
		// 	var data = [];
		// 	ko.utils.arrayForEach(self.selectedUsers().split(","), function(item) {
		// 		var pieces = item.split(":");
		// 		data.push({id: pieces[0], user_login: pieces[1]});
		// 	});
	 //        callback(data);
	 //    };
	 //    self.initSelectedSecondUsers = function (element, callback) {
	 //    	var data = [];
		// 	ko.utils.arrayForEach(self.selectedSecondUsers().split(","), function(item) {
		// 		var pieces = item.split(":");
		// 		data.push({id: pieces[0], user_login: pieces[1]});
		// 	});
	 //        callback(data);
	 //    };
	 //    self.selectedUserId = function(e) {
	 //    	return e.id+":"+e.user_login; 
	 //    };
			
		$("#main_users_input").select2({
			placeholder: "Selectionnez un ou plusieurs utilisateurs",
			allowClear: true,
			multiple: true,
			data: users,
			initSelection : function (element, callback) {
			    var data = [];
			    $(element.val().split(",")).each(function () {
			        var pieces = this.split(":");
					data.push({id: pieces[0], text: pieces[1]});
			    });
			    callback(data);
			},
			ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
		        url: kidzou_jsvars.api_get_userinfo,
		        dataType: 'jsonp',
		        data: function (term, page) {
		            return {
		                term: term, // search term
		                term_field: 'user_login',
		            };
		        },
		        results: function (data, page) { // parse the results into the format expected by Select2.
		            // since we are using custom formatting functions we do not need to alter remote JSON data
		            return {results: data};

		        }
		    },
		});

		$("#second_users_input").select2({
			placeholder: "Selectionnez un ou plusieurs utilisateurs",
			allowClear: true,
			multiple: true,
			data: secondusers,
			initSelection : function (element, callback) {
			    var data = [];
			    $(element.val().split(",")).each(function () {
			        var pieces = this.split(":");
					data.push({id: pieces[0], text: pieces[1]});
			    });
			    callback(data);
			}
		});
			

	});

}(jQuery));