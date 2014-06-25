var kidzouContest = (function () {

	jQuery(document).ready(function() {

		console.log("kidzouContest");

		//onclick : participate
		jQuery("#contest").submit(function(e){
			
			jQuery('button', this).addClass("sending");
			e.preventDefault();
			
			participate( document.getElementById("contest"), function( status ) {

				if (status==='ok') {
					jQuery('#contestMessage').html("Votre participation a bien &eacute;t&eacute; enregistr&eacute;e !").removeClass("error").addClass("success");
					jQuery('#contest button').html("Mettre &agrave; jour votre participation").removeClass("sending");
				} else {
					jQuery('#contestMessage').html("Une erreur est survenue, merci de retenter !").removeClass("success").addClass("error");
					jQuery('#contest button').html("Une erreur est survenue, merci de retenter !").removeClass("sending");
				}

			});

			return false;

		});

	});

	function participate( domForm, callback ) {

		var serializedForm = jQuery(domForm).serializeArray();

		jQuery.getJSON(kidzou_contest_jsvars.api_get_nonce,{controller: 'contest',	method: 'participate'})
					.done(function (data) {

						if (data!==null) {
			
				           jQuery.getJSON( kidzou_contest_jsvars.api_contest_participate  , { nonce : data.nonce, fields : serializedForm  })
				           		.done(function(result) {

				           			callback("ok");

								}).fail(function(jqXHR, textStatus, errorThrown) {
									console.error( errorThrown );
									console.error( textStatus );
								    console.error( jqXHR.responseText );

								    callback("ko");

								});
							}

			        });

	}


	// return {
	// 	participate : participate
	// };


})();