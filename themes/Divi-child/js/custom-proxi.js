var kidzouProximite = (function(){

	document.addEventListener('DOMContentLoaded', function() {


		// var zoom = parseInt(kidzou_proxi.zoom);
		var user_lat = kidzou_proxi.request_coords.latitude;
		var user_lng = kidzou_proxi.request_coords.longitude;
		var doing_ajax = false;

		var getContent = function getContentF( _radius, callback ) {

			if (doing_ajax)
				return;

			var radius = _radius || kidzou_proxi.radius;

			// console.debug('getContentF ' + user_lat);

			jQuery.ajax({

				type: "POST",
				url: kidzou_proxi.ajaxurl,
				data:
				{
					nonce : kidzou_proxi.nonce,
					action :kidzou_proxi.action,
					coords : {latitude : user_lat, longitude: user_lng},
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
					var textnode = document.createTextNode(kidzou_proxi.wait_refreshing);   // Create a text node
					sp1.appendChild(textnode);

					var mapNode = document.querySelector('#proxi_content .et_pb_map');
					document.querySelector('#proxi_content .et_pb_map_container').insertBefore(sp1, mapNode);

				},
				success: function( data ){

					doing_ajax = false;

					//nettoyer le message temporaire créé ci-dessus
					document.querySelector('#proxi_content .et_pb_map_container').removeChild(
						document.querySelector('#proxi_content .message')
					);

					//relacher ce booléen pour que de nouvelles requetes puissent avoir lieu
					if (callback) {
						callback();
					}

					if (data.empty_results) {

						//on n'est pas forcément dans le rayon a proximité du point de geoloc :
						//si aucun résultat, et qu'on était parti sur une requete sans coords, on prend les coords par défaut
						//et s'il n'y a pas de résultat...on est dans ce cas
						document.querySelector('.distance_message').innerHTML = '';

					} else {

						var distance_message = kidzou_proxi.distance_message.replace('{radius}', Math.round(radius));
						document.querySelector('.distance_message').innerHTML = distance_message;
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

							var pins = data.markers;
							for (var i = 0; i < pins.length; i++) {
								var pin = pins[i];
							      addMarker(map, pin.latitude, pin.longitude, pin.title, pin.content);
							}

						} else {

							// console.debug('todo : plus de résultats sans carte');
							document.querySelector('#proxi_content .more_results').innerHTML = kidzou_proxi.more_results;

						}
						
						//Rafraichir les votes...
						kidzouModule.refresh();
					}

				}
			} );

		};


		document.addEventListener("geolocation", function(e) {

			if (!e.detail.error && e.detail.refresh) {

				//ce sera les coordonnées reprises dans les req Ajax à l'avenir
				user_lat = e.detail.coords.latitude;
				user_lng = e.detail.coords.longitude;

				// console.debug('addEventListener ' + user_lat);

				//déclencher une requete Ajax pour afficher les activités autour de la position
				getContent(kidzou_proxi.radius);
			    
			} else {

				if (e.detail.error)
				{
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
				
		})

		var mapContainer = document.querySelector('#proxi_content .et_pb_map');
		var map;
		var mapCenter;

		function initialize( ) {

			console.debug('Init map ' + kidzou_proxi.display_mode);

			if (kidzou_proxi.display_mode == 'with_map')
			{
				console.debug('[center] ' + mapContainer.dataset.center_lat + '/' + mapContainer.dataset.center_lng);
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

			        		//on requete du contenu, ne pas encombrer le serveur
			        		// enableRefresh(false);
			        		// console.debug('requesting content');

			        		//aller chercher du contenu
			        		getContent(
								distance*2 //*2 : bon je sais pas pourquoi, mais soit 
							);

			        	} 
		        	}
		      	});

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
				draggable : true
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

		function loadMoreResults() {
			getContent(
				parseFloat(kidzou_proxi.radius) + 5 //rajouter 5 Km
			);
		}
		
		google.maps.event.addDomListener(window, 'load', initialize);

		if (document.querySelector('.load_more_results')) {
			document.querySelector('.load_more_results').addEventListener("click", loadMoreResults);
		}

	}); //document.addEventListener('DOMContentLoaded', ..



})();