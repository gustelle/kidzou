var kidzouAdminGeo = (function () {


	//fonction utilitaire pour récuperer lat et lng à partir d'une adresse
	function getLatLng (address,callback ) {

		jQuery.getJSON(kidzou_admin_geo_jsvars.geo_mapquest_address_url + "?key=" + kidzou_admin_geo_jsvars.geo_mapquest_key + "&location=" + address,{})
			.done(function (d) {

				if (callback) {
					callback({
						latitude 	: d.results[0].locations[0].latLng.lat, 
						longitude 	: d.results[0].locations[0].latLng.lng, 
						altitude	:0
					});
				}

			}).
			fail(function( jqxhr, textStatus, error ) {
			    
				if (callback) callback( );

			});

	}
	

	return {
		getLatLng : getLatLng,
	};


})();