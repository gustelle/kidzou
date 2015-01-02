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
					show_categories : kidzou_proxi.show_categories,
					display_mode : kidzou_proxi.display_mode,
				},
				success: function( data ){

					document.querySelector('#proxi_content').innerHTML = data;
					var $this_map_container = document.querySelector('#proxi_content .et_pb_map_container');

					if (kidzou_proxi.display_mode == 'with_map' && $this_map_container!=null)
					{
						var $this_map = document.querySelector('#proxi_content .et_pb_map');

						var map = new google.maps.Map( $this_map, {
							zoom: parseInt( $this_map.dataset.zoom ),
							center: new google.maps.LatLng( parseFloat( $this_map.dataset.center_lat ) , parseFloat( $this_map.dataset.center_lng )),
							mapTypeId: google.maps.MapTypeId.ROADMAP
						});

						var bounds =  new google.maps.LatLngBounds() ;

						[].forEach.call( $this_map_container.querySelectorAll('#proxi_content .et_pb_map_pin'), function(el) {
						  	 
							position = new google.maps.LatLng( parseFloat( el.dataset.lat) , parseFloat( el.dataset.lng ) );

							bounds.extend( position );

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