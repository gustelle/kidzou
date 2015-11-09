var kidzouProximite = (function(){

	//la position courante du cnetre de la carte, 
	//peut être différente de la position du user, si le user drag la carte
	var currentPosition ; 

	//le user a-t-il bougé la carté ?
	//si oui , on ne le recentre pas lorsqu'on on recoit ses coords
	var isMapDragged = false;

	//les markers sur la carte, nécessaire d'en déclarer un tableau pour pouvoir les supprimer
	// http://stackoverflow.com/questions/12526717/google-maps-v3-remove-all-markers
	var markers= []; 

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

	
	var mapContainer = document.querySelector(kidzou_proxi.map_selector);
	
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
				document.querySelector(kidzou_proxi.more_results_selector).innerHTML = '';

				//Créer un message temporaire qui sera nettoyé plus tard
				var sp1 = document.createElement("span");
				sp1.classList.add('message');
				sp1.innerHTML = kidzou_proxi.wait_refreshing;

				var mapNode = document.querySelector(kidzou_proxi.map_selector);
				document.querySelector(kidzou_proxi.map_container).insertBefore(sp1, mapNode);

			},
			success: function( data ){

				doing_ajax = false;

				//nettoyer le message temporaire créé ci-dessus
				document.querySelector(kidzou_proxi.map_container).removeChild(
					document.querySelector(kidzou_proxi.message_selector)
				);

				if (data.empty_results) {

				} else {

					
					document.querySelector(kidzou_proxi.portfolio_selector).innerHTML = data.portfolio;
					document.querySelector(kidzou_proxi.more_results_selector).innerHTML = kidzou_proxi.more_results; 

					//nettoyer le eventListener initial pour attacher un autre avec un rayon mis à jour
					document.querySelector(kidzou_proxi.more_results_cta_selector).removeEventListener("click", loadMoreResults);

					//nouveau listener dynamique
					document.querySelector(kidzou_proxi.more_results_cta_selector).addEventListener("click", function() {
						getContent(parseFloat(radius) + 5);
					});

					if (kidzou_proxi.display_mode != 'simple')
					{	
						//panTo new Position if user has not dragged the map
						if (!getMapDragged()) {
							console.info('Centrage sur la nouvelle position détectée');
							var latLng = new google.maps.LatLng(position.latitude, position.longitude); //Makes a latlng
							map.panTo(latLng);
						}

						//suppression des anciens markers avant d'enrajouter des nouveaux
						hideMarkers();
						
						var pins = data.markers;
						for (var i = 0; i < pins.length; i++) {
							var pin = pins[i]; 
						    addMarker(map, pin.latitude, pin.longitude, pin.id);
						}
						var markerCluster = new MarkerClusterer(map, markers);
						// console.debug('markerCluster',markerCluster);

					} else {

						// console.info('todo : plus de résultats sans carte');
						document.querySelector(kidzou_proxi.more_results_selector).innerHTML = kidzou_proxi.more_results;

					}
					
					//Rafraichir les votes...
					kidzouModule.refresh();
				}

			}
		} );

	};

	/** 
	 * Récupere le contenu d'un post identifié par son ID au format JSON
	 *
	 */
	var getPostContent = function getPostContentF( _post_id, callback ) {

		jQuery.ajax({
			url: kidzou_proxi.api_get_place + '?key=' + kidzou_proxi.api_public_key + '&post_id=' + _post_id,
			success: function( data ){
				callback(data.place);
			}
		});

	};

	//geolocation_progress est déclenché par kidzou-geo.js
	//il indique que getCurrentPosition est en cours
	document.addEventListener("geolocation_progress", function(e) {
		// console.info('geolocation in progress');
		if (document.querySelector(kidzou_proxi.distance_message_selector))
			document.querySelector(kidzou_proxi.distance_message_selector).innerHTML = kidzou_proxi.wait_geoloc_progress;			
	}, false);


	//les résultats de getCurrentPosition sont arrivés
	document.addEventListener("geolocation", function(e) {

		// console.info('geolocation done');
		// console.info(e.detail);

		addRefreshMessage();

		if (!e.detail.error && e.detail.refresh) {

			//stockage pour reutilisation ultérieure
			setCurrentPosition(e.detail.coords);

			//déclencher une requete Ajax pour afficher les activités autour de la position
			getContent(kidzou_proxi.radius);
		    
		} else {

			if (e.detail.error)
			{
				// console.info('Error ' );
				if (e.detail.acceptGeolocation) {

					// console.info("Le user accepte la geoloc, une erreur technique est survenue");
					var node = document.querySelector(kidzou_proxi.page_container_selector);
					var div1 = document.createElement("DIV");
					div1.innerHTML = kidzou_proxi.geoloc_error_msg;
					node.insertBefore(div1, node.childNodes[0]);

				} else {

					// console.info("Le user n'accepte pas la geoloc, dégrader les résultats");
					var node = document.querySelector(kidzou_proxi.page_container_selector);
					var div1 = document.createElement("DIV");
					div1.innerHTML = kidzou_proxi.geoloc_pleaseaccept_msg;
					node.insertBefore(div1, node.childNodes[0]);

				}

			}  
			
			
		}
			
	}, false);

	function addRefreshMessage() {
		// console.info('addRefreshMessage');
		if (document.querySelector(kidzou_proxi.distance_message_selector)) {
			document.querySelector(kidzou_proxi.distance_message_selector).innerHTML = kidzou_proxi.refresh_message;
			document.querySelector(kidzou_proxi.distance_message_selector +' a').addEventListener('click', function(e){

				//recharger une geoloc complete
				//cette geoloc redéclenchera une mise à jour du contenu
				//grace au EventListener "geolocation"

				kidzouGeoContent.getUserLocation(function(position) {

				});
			}, false);
		}
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

	if (document.querySelector(kidzou_proxi.more_results_selector)) {
		document.querySelector(kidzou_proxi.more_results_selector).addEventListener("click", loadMoreResults);
	}

	function initialize( ) {

		if (kidzou_proxi.display_mode != 'simple')
		{
			var scrollwheel = (kidzou_proxi.scrollwheel && kidzou_proxi.scrollwheel=='off' ? false : true);
			
			// console.debug('initialize map');
			removeLoadingMessage();
			setCurrentPosition({latitude : mapContainer.dataset.center_lat, longitude : mapContainer.dataset.center_lng});

			//des soucis de tps en temps au niveau des tiles
			//les tiles sont au milieu de l'ocean, peut être retarder légérement
			//l'affichage ??
			setTimeout(function() {

				map = new google.maps.Map( mapContainer, {
					zoom: parseInt(kidzou_proxi.zoom),
					center: new google.maps.LatLng( parseFloat( mapContainer.dataset.center_lat ) , parseFloat( mapContainer.dataset.center_lng )),
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					scrollwheel: scrollwheel ,
					disableDefaultUI : false,
					mapTypeControl : false,
					// panControl : false,
					// scaleControl : true,
					streetView : true,
					zoomControl : true
				});

				var pins = kidzou_proxi.markers;
				for (var i = 0; i < pins.length; i++) {
					var pin = pins[i]; 
					addMarker(map, pin.latitude, pin.longitude, pin.id);
				};
				var markerCluster = new MarkerClusterer(map, markers);

				//http://stackoverflow.com/questions/832692/how-can-i-check-whether-google-maps-is-fully-loaded
				google.maps.event.addListenerOnce(map, 'tilesloaded', function(){
					// console.info('tilesloaded');
					addRefreshMessage();

					google.maps.event.trigger(map, 'resize');
				    //this part runs when the mapobject is created and rendered
				    google.maps.event.addListenerOnce(map, 'tilesloaded', function(){
				        //this part runs when the mapobject shown for the first time
				        console.info('tilesloaded fully loaded');
				        
						// addRefreshMessage();
				    });
				});

			},200);

		}			

	}

	function addMarker(map, lat, lng, post_id ) {
		
		var position = new google.maps.LatLng( parseFloat( lat ) , parseFloat( lng ) );

		var marker = new google.maps.Marker({
			position: position,
			map: map,
			draggable : false,
		});

		markers.push(marker);
		var infowindow = new google.maps.InfoWindow({});
		// console.debug('post_id', post_id);

		google.maps.event.addListener(marker, 'click', (function(m,_id) {
		    return function() {
		    	infowindow.setContent('<i class="fa fa-spinner fa-spin"></i>Chargement...');
				infowindow.open( map, m );
		        getPostContent(_id, function(data){
		        	var content = windowContent(data); //console.debug(content);
					infowindow.setContent(content);
				});
		    }
		})(marker, post_id));
	}

	var windowContent = function formatInfoWindow(json_data) {

		var terms_list = json_data.terms.map(function(term){
			return term.name;
		}).join(', ');	

		var str = '<div id="post-{0}" class="infowindow et_pb_portfolio_item kz_portfolio_item post-{0} post type-post status-publish format-gallery has-post-thumbnail hentry">' +
					'<a href="{1}">' +
						'<span class="et_portfolio_image">' +
							'<img src="{2}" alt="{3}">'+
						'</span><!--  et_portfolio_image -->'+
					'</a>'+
					'<h2><a href="{1}">{3}</a></h2>'+
					'<p><a href="{1}">{4}</a></p>' + 
					'<p><a href="{1}"><i class="fa fa-heart vote"></i><span class="vote">{5}</span><i class="fa fa-comments-o"></i><span>{6}</span></a></p>'+
				'</div>';
		return str.format(json_data.post.ID, json_data.permalink, json_data.thumbnail,json_data.post.post_title, terms_list ,json_data.votes, json_data.comments.length);
	};

	/**
	 *
	 * Suppression des markers de la carte
	 */
	function hideMarkers() {
        /* Remove All Markers */
        while(markers.length){
            markers.pop().setMap(null);
        }

        // console.log("Remove All Markers");
    }

    //s'il y a une carte, c'est par ici que tout commence
	if (kidzou_proxi.display_mode != 'simple')
		google.maps.event.addDomListener(window, 'load', initialize);

})();

if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number]
        : match
      ;
    });
  };
}