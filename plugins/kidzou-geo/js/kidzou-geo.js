var kidzouGeoContent = (function () {

	jQuery(document).ready(function() {

		/////////////// Selection de Metropole ////////////////
		//////////////////////////////////////

		jQuery(".metropole").click(function(){
			setCurrentMetropole(jQuery(this).data('metropole'));
		});

		getLocation(); //Refresh du contenu

	});

	function getMetropole(lat, lng, callback) {

		var pos = lat + "," + lng; 
		jQuery.getJSON(kidzou_geo_jsvars.geo_mapquest_reverse_url + "?key=" + kidzou_geo_jsvars.geo_mapquest_key + "&location=" + pos,{})
			.done(function (data) {

				var metropole = data.results[0].locations[0].adminArea4;
				var covered = false;

				//verifier qu'on est dans une des metropoles couvertes
				for (m in kidzou_geo_jsvars.geo_possible_metropoles) {

					if (kidzou_geo_jsvars.geo_possible_metropoles.hasOwnProperty(m)) {

					    var uneMetro = kidzou_geo_jsvars.geo_possible_metropoles[m].toLowerCase();
					    if (uneMetro === metropole.toLowerCase()) {
					    	covered = true;
					    	break;
					    }
					}
				}

				if (!covered) metropole = kidzou_geo_jsvars.geo_default_metropole.toLowerCase();

				if (callback) callback(metropole);

				return metropole;
				
			})
			.fail(function( jqxhr, textStatus, error ) {
			    
			    metropole = kidzou_geo_jsvars.geo_default_metropole.toLowerCase();

				if (callback) callback(metropole);

			});

	}

	function refreshGeoCookie(metropole) {

		var precedenteMetropole = storageSupport.getCookie(kidzou_geo_jsvars.geo_cookie_name);
					
		if (precedenteMetropole!=metropole ) {

			setCurrentMetropole(metropole);

			//forcer le rafraichissement si la ville diffère de la ville par défaut
			if (metropole.toLowerCase()!=kidzou_geo_jsvars.geo_default_metropole.toLowerCase())
				location.reload(true);	
		}
	}


	//rafraichir le cookie
	function getClosestContent(position) {

		//si le user a cliqué expressément sur une ville pour la sélectionné, pas d'update du contenu
		var isMetropoleSelected = storageSupport.getCookie(kidzou_geo_jsvars.geo_select_cookie_name);

		if (isMetropoleSelected==="undefined" || !isMetropoleSelected) {

			getMetropole(position.lat, position.lng, refreshGeoCookie);
			
		}

		
	}

	//geoloc 
	function maPosition(position) {
		var pos = {lat : position.coords.latitude, lng : position.coords.longitude, alt : position.coords.altitude};
		getClosestContent(pos) ;
	}

	function erreurPosition(error) {
		var pos = {lat : kidzou_geo_jsvars.default_geo_lat, lng: kidzou_geo_jsvars.default_geo_lng, alt : 0}; //ville par defaut
		getClosestContent(pos) ;
	}

	function setCurrentMetropole(metropole) {
		storageSupport.setCookie(kidzou_geo_jsvars.geo_cookie_name, metropole.toLowerCase());
		storageSupport.setCookie(kidzou_geo_jsvars.geo_select_cookie_name, true); //forcer cette ville, elle vient d'être selectionnée par le user
	}


	//geolocalisation du user 
	//nb : 600000 ms = 10 minutes de cache
	/**
	* Déclenche un rafraichissement du contenu
	*/
	function getLocation() {

		if (navigator.geolocation) 
			navigator.geolocation.getCurrentPosition(maPosition, erreurPosition); //, {maximumAge:600000,enableHighAccuracy:true}
		
		else {

			//le user refuse la geoloc, ou le navigateur ne la supporte pas...

			var precedenteMetropole = storageSupport.getCookie(kidzou_geo_jsvars.geo_cookie_name);
			var covered = false;
			
			//il y avait déjà une métropole dans le cookie
			if (precedenteMetropole!=null && precedenteMetropole!='' &&  precedenteMetropole!=="undefined" ) {
				
				//verification de securité que la métropole du cookie est bien couverte
				for (m in kidzou_geo_jsvars.geo_possible_metropoles) {

					if (kidzou_geo_jsvars.geo_possible_metropoles.hasOwnProperty(m)) {

						var uneMetro = kidzou_geo_jsvars.geo_possible_metropoles[m].toLowerCase();
					    if (uneMetro === precedenteMetropole.toLowerCase()) {
					    	covered = true;
					    	break;
					    }
					}

				    
				}

				//si pas couverte, on remet la ville par défaut et on recharge
				if (!covered) {
					refreshGeoCookie(kidzou_geo_jsvars.geo_default_metropole.toLowerCase());
				}

			//aucune métropole dan le cookie
			} else {

				//redirection vers la metropole par défaut
				refreshGeoCookie(kidzou_geo_jsvars.geo_default_metropole.toLowerCase());
					
			}
			
		}
	}

	function getUserLocation(callback) {

		var defaultLoc = {
				latitude  : kidzou_geo_jsvars.default_geo_lat,
				longitude : kidzou_geo_jsvars.default_geo_lng,
				altitude  : 0
			};

		if (navigator.geolocation) {

			navigator.geolocation.getCurrentPosition(

					function(position) { 
						if (callback)
							callback({
								latitude: position.coords.latitude,
								longitude : position.coords.longitude,
								altitude : position.coords.altitude 
							}); 
					}, 
					function(err) { 
						if (callback)
							callback(defaultLoc); 
					}
				); //, 	{maximumAge:600000,enableHighAccuracy:true}

		} else {
			if (callback)
				callback(defaultLoc); 
		}
	}

	function getLatLng (address,callback ) {

		var defaultLoc = {
				latitude  : kidzou_geo_jsvars.default_geo_lat,
				longitude : kidzou_geo_jsvars.default_geo_lng,
				altitude  : 0
			};

		jQuery.getJSON(kidzou_geo_jsvars.geo_mapquest_address_url + "?key=" + kidzou_geo_jsvars.geo_mapquest_key + "&location=" + address,{})
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
			    
				if (callback) callback(defaultLoc);

			});

	}
	

	return {
		setCurrentMetropole : setCurrentMetropole,
		getMetropole : getMetropole,
		getLocation : getLocation,
		getLatLng : getLatLng,
		getUserLocation : getUserLocation
	};


})();