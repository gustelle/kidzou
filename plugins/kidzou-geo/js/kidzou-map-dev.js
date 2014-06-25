var kidzouMap = (function (){

	var poiWidth = 200;
	var domElt ;
	var poiColl = [];
	var mapCenter = {latitude  : kidzou_geo_jsvars.default_geo_lat, longitude : kidzou_geo_jsvars.default_geo_lng };
	var iconURL = kidzou_geo_jsvars.geo_icon_url;
	var zoom = 15;
	// var poiOnClickRedirect = false;

	function setMapElt (elt) {
		domElt = elt;
	}

	function setPOICollection (coll) {
		poiColl = coll;
	}

	function setMapCenter (coord) {
		mapCenter = coord;
	}

	function setZoom (_zoom) {
		zoom = _zoom;
	}

	function setPoiWidth (width) {
		poiWidth = width;
	}

	// function setPoiOnClickRedirect (_mode) {
	// 	poiOnClickRedirect = _mode;
	// }


	function drawMap ( ) {
		
		/*An example of using the MQA.EventUtil to hook into the window load event and execute defined function
	   passed in as the last parameter. You could alternatively create a plain function here and have it
	   executed whenever you like (e.g. <body onload="yourfunction">).*/

	   var options = {
	       elt: domElt,           /*ID of element on the page where you want the map added*/
	       latLng: { lat: parseFloat(mapCenter.latitude), lng: parseFloat(mapCenter.longitude) },   /*center of map in latitude/longitude */
	       zoom : zoom,
	       mtype: 'map',                                  /*map type (osm)*/
	       bestFitMargin: 0,                              /*margin offset from the map viewport when applying a bestfit on shapes*/
	       zoomOnDoubleClick: true                        /*zoom in when double-clicking on map*/
	     };

	    window.map = new MQA.TileMap(options);

	   	MQA.withModule('smallzoom','mousewheel', function() {

		     map.addControl(
			     new MQA.SmallZoom(),
			     new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT, new MQA.Size(5,5))
			   );

		     map.enableMouseWheelZoom();

		     if (poiColl && poiColl.length) {

		     	for (var i = 0; i < poiColl.length; i++) {

			     	var apoi = poiColl[i];
			     	var tel = (apoi.location_tel ? "<br/>Tel : " + apoi.location_tel : '');
				    var web = (apoi.location_web ? "<br/><a href='" + apoi.location_web + "'>" + apoi.location_web + "</a>" : '');
				    var title = apoi.location_name;

				    //affichage du titre de l'event plutot que le lieu 
				    if (apoi.post_type =='event')
				    	title = apoi.post_title;

				    var url = (apoi.permalink ? "<br/><strong><em>Plus d&apos;info sur Kidzou :</em><strong><br/><a href='" + apoi.permalink + "'>" + apoi.permalink + "</a>" : '');

				    var poi = new MQA.Poi({lat:parseFloat(apoi.location_latitude), lng:parseFloat(apoi.location_longitude)});

				   	var icon=new MQA.Icon(iconURL,24,32);
				    poi.setIcon(icon);
				    poi.setRolloverContent('<strong>' +  title + '</strong>');

					poi.setInfoContentHTML('<div style="width:' + poiWidth + 'px;font-size:1.3em;"><div><strong>' + title + '</strong><br/><span>' + apoi.location_address +  tel +  web + "</span></div> "+ url + "</div>");

					// //affichage automatique de la fenetre si on est pas sur une carte MultiPOI
					// if (poiColl.length==1)
					// 	poi.toggleInfoWindow();

					map.addShape(poi);

			     }

		     } else
		     	console.debug(poiColl);

	   });
	}

	function redirectEvent(evt){

	   e.innerHTML=evt.eventName;
	   var eDiv=document.getElementById('eventsFired');
	   eDiv.insertBefore(e, eDiv.firstChild);

	 }

	return {
		setMapElt : setMapElt,
		setPOICollection : setPOICollection,
		setMapCenter : setMapCenter,
		setPoiWidth : setPoiWidth,
		setZoom : setZoom,
		drawMap : drawMap
	};
	

}()); //auto-executée