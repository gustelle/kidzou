var kidzouGeoContent = (function () {

	jQuery(document).ready(function() {

		/////////////// Selection de Metropole dans la topnav ////////////////
		//////////////////////////////////////
		jQuery(".metropole").click(function(){
			setCurrentMetropole(jQuery(this).data('metropole'));
		});

		//fonction initiale au chargement de la page
		if (kidzou_geo_jsvars.geo_activate) {
			getUserLocation(function(pos){
				getClosestContent(pos);
			}); 
		} 

	});

	function getMetropole(lat, lng, callback) {

		var pos = lat + "," + lng; 

		jQuery.getJSON(kidzou_geo_jsvars.geo_mapquest_reverse_url + "?key=" + kidzou_geo_jsvars.geo_mapquest_key + "&location=" + pos,{})
			.done(function (data) {

				var metropole = (typeof data.results[0].locations[0]!=="undefined" ? data.results[0].locations[0].adminArea4 : '');
				var covered = false;

				//verifier qu'on est dans une des metropoles couvertes
				for (var m in kidzou_geo_jsvars.geo_possible_metropoles) {

					if (kidzou_geo_jsvars.geo_possible_metropoles.hasOwnProperty(m)) {

						// console.debug(kidzou_geo_jsvars.geo_possible_metropoles[m]);

					    var uneMetro = kidzou_geo_jsvars.geo_possible_metropoles[m].slug.toLowerCase();
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

	//et voilà, ici on stocke l'info de la métropole du user dans un cookie
	//pour éviter de recalculer sa position à chaque fois
	function refreshGeoCookie(metropole) {

		var precedenteMetropole = storageSupport.getCookie(kidzou_geo_jsvars.geo_cookie_name);
					
		if (precedenteMetropole!=metropole ) {

			setCurrentMetropole(metropole.toLowerCase()); //on force encore une fois le toLowerCase() pour assurer le regexp coté serveur

			//forcer le rafraichissement si la ville diffère de la ville par défaut
			// if (metropole.toLowerCase()!=kidzou_geo_jsvars.geo_default_metropole.toLowerCase())
			// 	location.reload(true);	
		}
	}


	//rafraichir le cookie
	function getClosestContent(position) {

		//si le user a cliqué expressément sur une ville pour la sélectionné, pas d'update du contenu
		var isMetropole = storageSupport.getCookie(kidzou_geo_jsvars.geo_cookie_name);

		if (isMetropole==="undefined" || !isMetropole) {

			//le contenu sera rafraichit (callback: "refreshGeoCookie") avec la metropole
			//obtenue par geoloc du navigateur

			position = position || {};

			//si la mposition n'est pas fournie, on prend la ville par défaut
			if ( position.latitude && position.longitude ) {

				getMetropole(position.latitude, position.longitude, refreshGeoCookie);
			}
			
		}  

		//si effectivement la metropole est pré-selectionnée, elle a été passée dans la requete, et le contenu
		//a été distribué en en tenant compte
		
	}


	function setCurrentMetropole(metropole) {
		storageSupport.setCookie(kidzou_geo_jsvars.geo_cookie_name, metropole.toLowerCase());
		// storageSupport.setCookie(kidzou_geo_jsvars.geo_select_cookie_name, true); //forcer cette ville, elle vient d'être selectionnée par le user
	}


	function getUserLocation(callback) {

		if (navigator.geolocation) {

			navigator.geolocation.getCurrentPosition(

					function(position) { 

						var myEvent = new CustomEvent("geolocation", {
							detail: {error: false, acceptGeolocation : true, coords : position.coords}
						});

						// Trigger it!
						document.dispatchEvent(myEvent);

						if (callback)
							callback({
								latitude: position.coords.latitude,
								longitude : position.coords.longitude,
								altitude : position.coords.altitude 
							}); 

					}, 
					function(err) { 
						
						var myEvent = new CustomEvent("geolocation", {
							detail: {error: true, acceptGeolocation : true}
						});

						// Trigger it!
						document.dispatchEvent(myEvent);
					}
				); 

		} else {
			
			var myEvent = new CustomEvent("geolocation", {
				detail: {error: true, acceptGeolocation : false}
			});

			// Trigger it!
			document.dispatchEvent(myEvent);
		}
	}

	//fonction utilitaire pour récuperer lat et lng à partir d'une adresse
	function getLatLng (address,callback ) {

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
			    
				if (callback) callback( );

			});

	}
	

	return {
		setCurrentMetropole : setCurrentMetropole,
		getMetropole : getMetropole,
		getLatLng : getLatLng,
		getUserLocation : getUserLocation
	};


})();