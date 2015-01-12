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

					document.querySelector('#proxi_content .message').innerHTML = kidzou_proxi.wait_refreshing;

				},
				success: function( data ){

					// console.debug(data);

					if (data.empty_results) {

						document.querySelector('#proxi_content .message').innerHTML = data.portfolio; 

					} else {

						document.querySelector('#proxi_content .message').innerHTML = '';
						document.querySelector('#proxi_content .et_pb_portfolio_results').innerHTML = data.portfolio;

						if (kidzou_proxi.display_mode == 'with_map')
						{
							var LatLng = new google.maps.LatLng(lat, lng);
							map.panTo(LatLng);

							var pins = data.markers;
							for (var i = 0; i < pins.length; i++) {
								var pin = pins[i];
							      addMarker(map, pin.latitude, pin.longitude, pin.title, pin.content);
							}

						} else {

							console.debug('todo : plus de résultats sans carte');
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

				} 
				
				
			}
				
		})

		var mapContainer = document.querySelector('#proxi_content .et_pb_map');
		var map;

		function initialize( ) {


			if (kidzou_proxi.display_mode == 'with_map')
			{

				map = new google.maps.Map( mapContainer, {
						zoom: zoom,
						center: new google.maps.LatLng( parseFloat( mapContainer.dataset.center_lat ) , parseFloat( mapContainer.dataset.center_lng )),
						mapTypeId: google.maps.MapTypeId.ROADMAP
					});

				var pins = kidzou_proxi.markers;
				for (var i = 0; i < pins.length; i++) {
					var pin = pins[i];
					addMarker(map, pin.latitude, pin.longitude, pin.title, pin.content);
				};

				google.maps.event.addListener(map, 'dragend', function() { 
					var center  = map.getCenter();
					getContent(
						center.lat(), 
						center.lng()
					);
				});

				google.maps.event.addListener(map, 'zomm_changed', function() { 
					zoom = map.getZoom();
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
		
		google.maps.event.addDomListener(window, 'load', initialize);

	}); //document.addEventListener('DOMContentLoaded', ..



})();