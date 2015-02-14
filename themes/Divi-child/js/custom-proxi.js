var kidzouProximite = (function(){

	//la position courante du cnetre de la carte, 
	//peut être différente de la position du user, si le user drag la carte
	var currentPosition ; 

	//le user a-t-il bougé la carté ?
	//si oui , on ne le recentre pas lorsqu'on on recoit ses coords
	var isMapDragged = false;

	function setCurrentPosition(position) {
		currentPosition = position;
	}

	function getCurrentPosition() {
		return currentPosition;
	}

	function setMapDragged(bool) {
		// console.info('setMapDragged ? ' + bool);
		isMapDragged = bool;
	}

	function getMapDragged() {
		// console.info('getMapDragged ? ' + isMapDragged);
		return isMapDragged;
	}

	
	var mapContainer = document.querySelector('#proxi_content .et_pb_map');
	
	/**
	 * l'objet map de Google Maps
	 */
	var map;

	/**
	 * L'objet coordonnées LatLng du centre de la map
	 */
	var mapCenter;

	/**
	 * Marqueur pour éviter les doubles requetes
	 */
	var doing_ajax = false;

	var getContent = function getContentF( _radius ) {

		if (doing_ajax)
			return;

		var position = getCurrentPosition() || kidzou_proxi.request_coords;
		var radius = _radius || kidzou_proxi.radius;

		jQuery.ajax({

			type: "POST",
			url: kidzou_proxi.ajaxurl,
			data:
			{
				nonce : kidzou_proxi.nonce,
				action :kidzou_proxi.action,
				coords : position,
				radius : radius,
				module_id : kidzou_proxi.module_id,
				module_class : kidzou_proxi.module_class,
				background_layout : kidzou_proxi.background_layout,
				show_title : kidzou_proxi.show_title,
				show_categories : kidzou_proxi.show_categories,
				display_mode : kidzou_proxi.display_mode,
				fullwidth : kidzou_proxi.fullwidth,
				show_distance : kidzou_proxi.show_distance
			},
			beforeSend : function() {

				doing_ajax = true;

				//nettoyer le HTML
				document.querySelector('#proxi_content .more_results').innerHTML = '';

				//Créer un message temporaire qui sera nettoyé plus tard
				var sp1 = document.createElement("span");
				sp1.classList.add('message');
				sp1.innerHTML = kidzou_proxi.wait_refreshing;

				var mapNode = document.querySelector('#proxi_content .et_pb_map');
				document.querySelector('#proxi_content .et_pb_map_container').insertBefore(sp1, mapNode);

			},
			success: function( data ){

				doing_ajax = false;

				//nettoyer le message temporaire créé ci-dessus
				document.querySelector('#proxi_content .et_pb_map_container').removeChild(
					document.querySelector('#proxi_content .message')
				);

				// addRefreshMessage();

				if (data.empty_results) {

					//on n'est pas forcément dans le rayon a proximité du point de geoloc :
					//si aucun résultat, et qu'on était parti sur une requete sans coords, on prend les coords par défaut
					//et s'il n'y a pas de résultat...on est dans ce cas
					// document.querySelector('.distance_message').innerHTML = '';

				} else {

					//bouton pour proposer de rafraichir la geoloc si les résultats ne sont pas pertinents 
					// document.querySelector('.distance_message').innerHTML = kidzou_proxi.refresh_message;

					// var distance_message = kidzou_proxi.distance_message.replace('{radius}', Math.round(radius));
					// document.querySelector('.distance_message').innerHTML = distance_message;
					document.querySelector('#proxi_content .et_pb_portfolio_results').innerHTML = data.portfolio;
					document.querySelector('#proxi_content .more_results').innerHTML = kidzou_proxi.more_results; 

					//nettoyer le eventListener initial pour attacher un autre avec un rayon mis à jour
					document.querySelector('.load_more_results').removeEventListener("click", loadMoreResults);

					//nouveau listener dynamique
					document.querySelector('.load_more_results').addEventListener("click", function() {
						getContent(parseFloat(radius) + 5);
					});

					if (kidzou_proxi.display_mode == 'with_map')
					{	
						console.info(map);
						//panTo new Position if user has not dragged the map
						if (!getMapDragged()) {
							console.info('Centrage sur la nouvelle position détectée');
							var latLng = new google.maps.LatLng(position.latitude, position.longitude); //Makes a latlng
							map.panTo(latLng);
						}

						var pins = data.markers;
						for (var i = 0; i < pins.length; i++) {
							var pin = pins[i];
						      addMarker(map, pin.latitude, pin.longitude, pin.title, pin.content);
						}

					} else {

						// console.info('todo : plus de résultats sans carte');
						document.querySelector('#proxi_content .more_results').innerHTML = kidzou_proxi.more_results;

					}
					
					//Rafraichir les votes...
					kidzouModule.refresh();
				}

			}
		} );

	};

	//geolocation_progress est déclenché par kidzou-geo.js
	//il indique que getCurrentPosition est en cours
	document.addEventListener("geolocation_progress", function(e) {
		document.querySelector('.distance_message').innerHTML = kidzou_proxi.wait_geoloc_progress;			
	}, false);


	//les résultats de getCurrentPosition sont arrivés
	document.addEventListener("geolocation", function(e) {

		// document.querySelector('.distance_message').innerHTML = '';
		addRefreshMessage();

		if (!e.detail.error && e.detail.refresh) {

			//stockage pour reutilisation ultérieure
			setCurrentPosition(e.detail.coords);

			//déclencher une requete Ajax pour afficher les activités autour de la position
			getContent(kidzou_proxi.radius);
		    
		} else {

			// console.info(e);
			//debug
			//déclencher une requete Ajax pour afficher les activités autour de la position
			// getContent(kidzou_proxi.radius);

			if (e.detail.error)
			{
				// console.info('Error ' );
				if (e.detail.acceptGeolocation) {

					// console.info("Le user accepte la geoloc, une erreur technique est survenue");
					var node = document.querySelector('#proxi_content');
					var div1 = document.createElement("DIV");
					div1.innerHTML = kidzou_proxi.geoloc_error_msg;
					node.insertBefore(div1, node.childNodes[0]);

				} else {

					// console.info("Le user n'accepte pas la geoloc, dégrader les résultats");
					var node = document.querySelector('#proxi_content');
					var div1 = document.createElement("DIV");
					div1.innerHTML = kidzou_proxi.geoloc_pleaseaccept_msg;
					node.insertBefore(div1, node.childNodes[0]);

				}

			}  
			
			
		}
			
	}, false);

	function addRefreshMessage() {
		// console.info('addRefreshMessage');
		document.querySelector('.distance_message').innerHTML = kidzou_proxi.refresh_message;
		document.querySelector('.distance_message a').addEventListener('click', function(e){

			//recharger une geoloc complete
			//cette geoloc redéclenchera une mise à jour du contenu
			//grace au EventListener "geolocation"
			kidzouGeoContent.getUserLocation(function() {
				console.debug('User location refreshed');
			});
		}, false);
	}

	function removeLoadingMessage() {
		// document.querySelector('#map_loader').innerHTML = '';
		// document.querySelector('#map_loader').classList.remove('map_over');
	}

	function loadMoreResults() {
		getContent(
			parseFloat(kidzou_proxi.radius) + 5 //rajouter 5 Km
		);
	}

	if (document.querySelector('.load_more_results')) {
		document.querySelector('.load_more_results').addEventListener("click", loadMoreResults);
	}

	function initialize( ) {

		// console.debug('initialize');

		if (kidzou_proxi.display_mode == 'with_map')
		{
			// console.debug('initialize map');
			removeLoadingMessage();
			setCurrentPosition({latitude : mapContainer.dataset.center_lat, longitude : mapContainer.dataset.center_lng});

			//des soucis de tps en temps au niveau des tiles
			//les tiles sont au milieu de l'ocean, peut être retarder légérement
			//l'affichage ??
			setTimeout(function() {

				console.info('map initialize')

				map = new google.maps.Map( mapContainer, {
					zoom: parseInt(kidzou_proxi.zoom),
					center: new google.maps.LatLng( parseFloat( mapContainer.dataset.center_lat ) , parseFloat( mapContainer.dataset.center_lng )),
					mapTypeId: google.maps.MapTypeId.ROADMAP
				});

				var pins = kidzou_proxi.markers;
				for (var i = 0; i < pins.length; i++) {
					var pin = pins[i];
					addMarker(map, pin.latitude, pin.longitude, pin.title, pin.content);
				};

				mapCenter = map.getCenter();

				//calculer la distance entre le centre de la carte et le Marker max requeté par kidzou
				//autrement dit, le radius de kidzou
				google.maps.event.addListener(map, 'bounds_changed', function() {
		        	
		        	//le radius courant de la carte
		        	var distance = getDistanceToCenter();

		        	//prefetcher les prochains resultats
		        	//si la distance au radius kidzou est dépassée
		        	if ( parseFloat( distance ) < parseFloat(kidzou_proxi.max_distance) ) {

		        		if ( (parseFloat( distance ) > parseFloat( kidzou_proxi.radius ) )  ) {	

		        			mapCenter = map.getCenter();

		        			setCurrentPosition({ latitude : mapCenter.lat(), longitude : mapCenter.lng() });

		        			//le user a bougé la carte, on ne le recentrera pas
		        			setMapDragged(true);

			        		//aller chercher du contenu
			        		getContent(
								distance*2 //*2 : bon je sais pas pourquoi, mais soit
							);

			        	} 
		        	}
		      	});

				//http://stackoverflow.com/questions/832692/how-can-i-check-whether-google-maps-is-fully-loaded
				google.maps.event.addListenerOnce(map, 'tilesloaded', function(){
					console.info('tilesloaded');
					google.maps.event.trigger(map, 'resize');
				    //this part runs when the mapobject is created and rendered
				    google.maps.event.addListenerOnce(map, 'tilesloaded', function(){
				        //this part runs when the mapobject shown for the first time
				        // console.info('tilesloaded');
				        
						addRefreshMessage();
				    });
				});

			},200);

		}			

	}

	function addMarker(map, lat, lng, title, content ) {
		
		var position = new google.maps.LatLng( parseFloat( lat ) , parseFloat( lng ) );

		var marker = new google.maps.Marker({
			position: position,
			map: map,
			title: title,
			icon: { url: et_custom.images_uri + '/marker.png', size: new google.maps.Size( 46, 43 ), anchor: new google.maps.Point( 16, 43 ) },
			shape: { coord: [1, 1, 46, 43], type: 'rect' },
			draggable : false,
			animation: google.maps.Animation.DROP
		});

		var infowindow = new google.maps.InfoWindow({
			content: content
		});

		google.maps.event.addListener(marker, 'click', function() {
			infowindow.open( map, marker );
		});
	}

	/**
	 * La distance entre le centre initial et les bords de la carte
	 * i.e. faut-il aller chercher de nouveaux résultats ou est-on encore dans le radius kidzou
	 * @see http://stackoverflow.com/questions/3525670/radius-of-viewable-region-in-google-maps-v3
	 */
	function getDistanceToCenter( ) {

		var bounds = map.getBounds();

		// var center  = map.getCenter();
		var ne = bounds.getNorthEast();

		// r = radius of the earth in statute miles
		var r = 3963.0;  

		// Convert lat or lng from decimal degrees into radians (divide by 57.2958)
		var lat1 = mapCenter.lat() / 57.2958; 
		var lon1 = mapCenter.lng() / 57.2958;
		var lat2 = ne.lat() / 57.2958;
		var lon2 = ne.lng() / 57.2958;

		// distance = circle radius from center to Northeast corner of bounds
		var dis = r * Math.acos(Math.sin(lat1) * Math.sin(lat2) + 
		  Math.cos(lat1) * Math.cos(lat2) * Math.cos(lon2 - lon1));

		return dis;
	}

	if (kidzou_proxi.display_mode == 'with_map')
		google.maps.event.addDomListener(window, 'load', initialize);


})();