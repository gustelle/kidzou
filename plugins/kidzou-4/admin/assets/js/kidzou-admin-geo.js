var kidzouAdminGeo = (function () {

	function getMetropole(lat, lng, callback) {

		var pos = lat + "," + lng; 

		jQuery.getJSON(kidzou_admin_geo_jsvars.geo_mapquest_reverse_url + "?key=" + kidzou_admin_geo_jsvars.geo_mapquest_key + "&location=" + pos,{})
			.done(function (data) {

				var metropole = (typeof data.results[0].locations[0]!=="undefined" ? data.results[0].locations[0].adminArea4 : '');
				var covered = false;

				//verifier qu'on est dans une des metropoles couvertes
				for (var m in kidzou_admin_geo_jsvars.geo_possible_metropoles) {

					if (kidzou_admin_geo_jsvars.geo_possible_metropoles.hasOwnProperty(m)) {

						// console.debug(kidzou_geo_jsvars.geo_possible_metropoles[m]);

					    var uneMetro = kidzou_admin_geo_jsvars.geo_possible_metropoles[m].slug.toLowerCase();
					    if (uneMetro === metropole.toLowerCase()) {
					    	covered = true; 
					    	// console.debug(covered);
					    	break;
					    }
					}
				}

				//si la metreopole n'est pas couverte on ne filtre pas

				//toujours renvoyer la metropole en minuscule pour analyse regexp coté serveur
				if (callback && covered) callback(metropole.toLowerCase());

				return metropole;
				
			})
			.fail(function( jqxhr, textStatus, error ) {

				//silence, pas de filtrage sur la metropole si erreur

			});

	}


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
		// setCurrentMetropole : setCurrentMetropole,
		getMetropole : getMetropole,
		getLatLng : getLatLng,
		// getUserLocation : getUserLocation
	};


})();