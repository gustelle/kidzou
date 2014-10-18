(function ( $ ) {
	"use strict";

	$(function () {

		// Place your administration-specific JavaScript here

		var users = kidzou_jsvars.main_users;
		var secondusers = kidzou_jsvars.second_users;
			
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
			}
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