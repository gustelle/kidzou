var kidzouProximite = (function(){

	document.querySelector('#proxi_content').innerHTML = kidzou_proxi.wait_geoloc_message;

	document.addEventListener("geolocation", function(e) {

		if (!e.detail.error) {

			document.querySelector('#proxi_content').innerHTML = kidzou_proxi.wait_load_message;

			//déclencher une requete Ajax
		    jQuery.ajax( {
				type: "POST",
				url: kidzou_proxi.ajaxurl,
				data:
				{
					nonce : kidzou_proxi.nonce,
					action :kidzou_proxi.action,
					coords : e.detail.coords,
					radius : kidzou_proxi.radius,
					module_id : kidzou_proxi.module_id,
					module_class : kidzou_proxi.module_class,
					background_layout : kidzou_proxi.background_layout,
					show_title : kidzou_proxi.show_title,
					show_categories : kidzou_proxi.show_categories
				},
				success: function( data ){
					document.querySelector('#proxi_content').innerHTML = data;
					
					//Rafraichir les votes...
					kidzouModule.refresh();
				}
			} );

		} else {

			if (e.detail.acceptGeolocation) {

				console.info("Le user accepte la geoloc, une erreur technique est survenue");

			} else {

				console.info("Le user n'accepte pas la geoloc, dégrader les résultats");
			}
		}
			
	})

})();