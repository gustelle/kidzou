var kidzouGeoContent = (function () {

	//merci https://github.com/gwilson/getAccurateCurrentPosition
	if (navigator.geolocation) {
		navigator.geolocation.getAccurateCurrentPosition = function (geolocationSuccess, geolocationError, geoprogress, options) {
		    var lastCheckedPosition,
		        locationEventCount = 0,
		        watchID,
		        timerID;

		    options = options || {};

		    var checkLocation = function (position) {
		        lastCheckedPosition = position;
		        locationEventCount = locationEventCount + 1;
		        // We ignore the first event unless it's the only one received because some devices seem to send a cached
		        // location even when maxaimumAge is set to zero
		        if ((position.coords.accuracy <= options.desiredAccuracy) && (locationEventCount > 1)) {
		            clearTimeout(timerID);
		            navigator.geolocation.clearWatch(watchID);
		            foundPosition(position);
		        } else {
		            geoprogress(position);
		        }
		    };

		    var stopTrying = function () {
		        navigator.geolocation.clearWatch(watchID);
		        foundPosition(lastCheckedPosition);
		    };

		    var onError = function (error) {
		        clearTimeout(timerID);
		        navigator.geolocation.clearWatch(watchID);
		        geolocationError(error);
		    };

		    var foundPosition = function (position) {
		        geolocationSuccess(position);
		    };

		    if (!options.maxWait)            options.maxWait = 10000; // Default 10 seconds
		    if (!options.desiredAccuracy)    options.desiredAccuracy = 20; // Default 20 meters
		    if (!options.timeout)            options.timeout = options.maxWait; // Default to maxWait

		    options.maximumAge = 0; // Force current locations only
		    options.enableHighAccuracy = true; // Force high accuracy (otherwise, why are you using this function?)

		    watchID = navigator.geolocation.watchPosition(checkLocation, onError, options);
		    timerID = setTimeout(stopTrying, options.maxWait); // Set a timeout that will abandon the location loop
		};
	}

	document.addEventListener('DOMContentLoaded', function(event) {

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

	}, false);

	function getMetropole(lat, lng, callback) {

		var pos = lat + "," + lng; 

		jQuery.getJSON(kidzou_geo_jsvars.geo_mapquest_reverse_url + "?key=" + kidzou_geo_jsvars.geo_mapquest_key + "&location=" + pos,{})
			.done(function (data) {

				var metropole = (typeof data.results[0].locations[0]!=="undefined" ? data.results[0].locations[0].adminArea4 : '');
				var covered = false;

				//verifier qu'on est dans une des metropoles couvertes
				for (var m in kidzou_geo_jsvars.geo_possible_metropoles) {

					if (kidzou_geo_jsvars.geo_possible_metropoles.hasOwnProperty(m) && kidzou_geo_jsvars.geo_possible_metropoles[m].slug) {

						// console.info(kidzou_geo_jsvars.geo_possible_metropoles[m]);

					    var uneMetro = kidzou_geo_jsvars.geo_possible_metropoles[m].slug.toLowerCase();
					    if (uneMetro === metropole.toLowerCase()) {
					    	covered = true; 
					    	// console.info(covered);
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

		// comparaison de la position : si identique a précédente - on ne décelanche pas le rafraichissement
		var cook = storageSupport.getCookie(kidzou_geo_jsvars.geo_coords);
		var prec_coords = (typeof cook!='undefined' ? JSON.parse(cook) : {});

		// console.info('Request for user location');

		if (navigator.geolocation) {

			// console.info('Getting position');

			//utiliser watchPosition plutot que getCurrentPosition qui n'est pas fiable
			navigator.geolocation.getAccurateCurrentPosition(
					function(position) { 

						//parfois position est "undefined"...why ? je ne sais pas
						if (typeof position!='undefined') {

							//pour comparer la position précédente avec la nouvelle position
							//on ne compare que latitude et longitude et on arrondit à la 3e décimale
							//Car finalement la précision varie tout le temps et ce n'est pas intéressant de rafrichir 
							//le contenu uniquement si la position a varié faiblement
							var short_position = { latitude : Math.round(position.coords.latitude*1000)/1000, longitude : Math.round(position.coords.longitude*1000)/1000 };

							//l'utilisateur change de position
							//on indique a la page qu'elle peut recharger son contenu "proximite"

							if ( ko.toJSON(short_position) != ko.toJSON(prec_coords) ) {

								// console.info('New position detected ' );

								//stockage des résultats dans un cookie pour transmission en requete 
								storageSupport.setCookie(kidzou_geo_jsvars.geo_coords, ko.toJSON( short_position ) );

								var myEvent = new CustomEvent("geolocation", {
									detail: {error: false, acceptGeolocation : true, refresh : true, coords : short_position},
									bubble : true
								});

								// Trigger it!
								document.dispatchEvent(myEvent);

							} else {
								
								// console.info('Old position still OK');

								var myEvent = new CustomEvent("geolocation", {
									detail: {error: false, acceptGeolocation : true, refresh : false},
									bubble : true
								});

								// Trigger it!
								document.dispatchEvent(myEvent);
							}

							if (callback) {
								callback({
									latitude: position.coords.latitude,
									longitude : position.coords.longitude,
									altitude : position.coords.altitude 
								}); 

							}
						}
							

					}, 
					function(err) { 
						
						var myEvent = new CustomEvent("geolocation", {
							detail: {error: true, acceptGeolocation : true},
							bubble : true
						});

						// Trigger it!
						document.dispatchEvent(myEvent);
					},
					function() {
						var myEvent = new CustomEvent("geolocation_progress", {
							detail: {},
							bubble : true
						});

						// Trigger it!
						document.dispatchEvent(myEvent);
					},
					{desiredAccuracy:100, maxWait:5000}
					//http://stackoverflow.com/questions/3397585/navigator-geolocation-getcurrentposition-sometimes-works-sometimes-doesnt
					//definir un timeout pour eviter de partir dans les choix
					//max age : 0 pour forcer le rafraichissement
					// {timeout:10000, maximumAge:0, enableHighAccuracy:true} 
				); 

			// navigator.geolocation.clearWatch(watchID);

		} else {

			// console.error('Error, navigator does not accept geolocation');
			
			var myEvent = new CustomEvent("geolocation", {
				detail: {error: true, acceptGeolocation : false},
				bubble : true
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