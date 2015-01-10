var kidzouProximite = (function(){

	document.addEventListener('DOMContentLoaded', function() {

		var zoom = 14;

		var getContent = function getContentF( lat, lng ) {

			jQuery.ajax({

				type: "POST",
				url: kidzou_proxi.ajaxurl,
				data:
				{
					nonce : kidzou_proxi.nonce,
					action :kidzou_proxi.action,
					coords : {latitude : lat, longitude: lng},
					radius : kidzou_proxi.radius,
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

					// document.querySelector('#proxi_content .message').innerHTML = kidzou_proxi.wait_refreshing;

				},
				success: function( data ){

					if (data.empty_results) {

						document.querySelector('#proxi_content .message').innerHTML = data.content; 

					} else {

						document.querySelector('#proxi_content .message').innerHTML = '';
						document.querySelector('#proxi_content .more_results').innerHTML = kidzou_proxi.more_results;
						document.querySelector('#proxi_content .results').innerHTML = data.content;
						// document.querySelector('#proxi_content .results').classList.remove('overlay');

						if (kidzou_proxi.display_mode == 'with_map')
						{
							displayMap( document.querySelector('#proxi_content .et_pb_map'), zoom ); //false : la map existe déjà
						}
						
						//Rafraichir les votes...
						kidzouModule.refresh();
					}

				}
			} );

		};


		var displayMap = function displayMapF( _el, _zoom ) {

			var $this_map_container = document.querySelector('#proxi_content .et_pb_map_container');

			if ($this_map_container!=null)
			{

				var el = _el;
				var theZoom = _zoom || zoom;

				var map = new google.maps.Map( el, {
						zoom: theZoom,//fixe
						center: new google.maps.LatLng( parseFloat( el.dataset.center_lat ) , parseFloat( el.dataset.center_lng )),
						mapTypeId: google.maps.MapTypeId.ROADMAP
					});

				google.maps.event.addListener(map, 'dragend', function() { 
						var center  = map.getCenter();
						console.debug('center changed ' + center.lat() + ' / ' + center.lng());
						getContent(
							center.lat(), 
							center.lng()
						);
					});

				google.maps.event.addListener(map, 'zoom_changed', function() {
				    zoom = map.getZoom();
				    console.debug('zoom changed to val ' + zoom);
				});

				var bounds = new google.maps.LatLngBounds();

				[].forEach.call( $this_map_container.querySelectorAll('#proxi_content .et_pb_map_pin'), function(el) {
				  	 
					position = new google.maps.LatLng( parseFloat( el.dataset.lat) , parseFloat( el.dataset.lng ) );

					bounds.extend( position );
					// console.debug(map);

					var marker = new google.maps.Marker({
						position: position,
						map: map,
						title: el.dataset.title,
						icon: { url: et_custom.images_uri + '/marker.png', size: new google.maps.Size( 46, 43 ), anchor: new google.maps.Point( 16, 43 ) },
						shape: { coord: [1, 1, 46, 43], type: 'rect' }
					});

					var infowindow = new google.maps.InfoWindow({
						content: el.innerHTML
					});

					google.maps.event.addListener(marker, 'click', function() {
						infowindow.open( map, marker );
					});
						
				});

				setTimeout(function(){
					if (typeof map.getBounds()!=="undefined") {
						if ( !map.getBounds().contains( bounds.getNorthEast() ) || !map.getBounds().contains( bounds.getSouthWest() ) ) {
							map.fitBounds( bounds );
						}
					}
				}, 200 );

			} 
		}; //executée au démarrage

		document.addEventListener("geolocation", function(e) {

			if (!e.detail.error && e.detail.refresh) {

				//déclencher une requete Ajax pour afficher les activités autour de la position
				getContent(
					e.detail.coords.latitude,
					e.detail.coords.longitude
				);
			    
			} else {

				if (e.detail.error)
				{
					if (e.detail.acceptGeolocation) {

						console.info("Le user accepte la geoloc, une erreur technique est survenue");
						var message = document.createTextNode(kidzou_proxi.geoloc_error_msg);
						document.querySelector('#proxi_content .message').insertBefore(message);

					} else {

						console.info("Le user n'accepte pas la geoloc, dégrader les résultats");
						var message = document.createTextNode(kidzou_proxi.geoloc_pleaseaccept_msg);
						document.querySelector('#proxi_content .message').insertBefore(message);

					}

				} else if (!e.detail.refresh) {

					//affichage initial, sans rafraichissement ajax
					//juste afficher la carte pré-chargée en HTML
					if (kidzou_proxi.display_mode == 'with_map')
					{	
						displayMap( document.querySelector('#proxi_content .et_pb_map'), zoom  ); //true : creation initiale de la map
					}
				}
				
			}
				
		})

		// var map = new MapBuilder( document.querySelector('#proxi_content .et_pb_map') );
		var mapContainer = document.querySelector('#proxi_content .et_pb_map');

		//initialement
		displayMap( mapContainer, zoom);


	}); //document.addEventListener('DOMContentLoaded', ..

})();