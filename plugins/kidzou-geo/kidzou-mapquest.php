<?php

/**
 * Affichage du lieu de l'evenement sur carte google map
 *
 * @return void
 * @author 
 **/
function kz_map() {

	global $post;

	$meta = (array)kz_get_post_map_meta();

	if ( kz_is_map() ) {

		wp_enqueue_script('kidzou-map',		WP_PLUGIN_URL.'/kidzou-geo/js/kidzou-map-dev.js', array('jquery'), KIDZOU_GEO_VERSION, true);

		echo '<style>#kidzou_map img {max-width:none;}</style><hr/><div id="kidzou_map" style="width:100%; height:300px;">
		</div><!--p>Carte fournie par <a href="http://www.mapquest.com/">MapQuest.com</a></p--><hr/>';

		$coordinates = array();
		$coordinates[0] = $meta;

		echo '	<script>
						jQuery(document).ready(function() {
							
							var kidzou_map_data = '.json_encode($coordinates). ';
							var width = jQuery("#post-content").width(); 
							jQuery("#kidzou_map").css("width", width + "px");
							
							kidzouMap.setMapElt( document.getElementById("kidzou_map") );
							kidzouMap.setZoom( 15 );
							kidzouMap.setPoiWidth( (width/3) );
							kidzouMap.setPOICollection( kidzou_map_data ); ';

							
				if (!kz_is_latLng()) {

						echo '
							kidzouGeoContent.getLatLng("'.$meta["location_address"].'" , function(pos) {
								kidzouMap.setMapCenter(pos);
								kidzouMap.drawMap();
							});';

				} else {

						echo '
							kidzouMap.setMapCenter({
								latitude : '.$meta["location_latitude"].',
								longitude : '.$meta["location_longitude"].',
								altitude : 0
							});
							kidzouMap.drawMap();';

				}

				echo '
					});
				</script>';

	}

}

/**
 * True si une carte peut etre attachee au post (autrement dit les meta du post relatives à la geoloc sont remplies)
 * - soit l'adresse est saisie
 * - soit latitude et longitude sont présentes
 *
 * @return Boolean
 * @author 
 **/
function kz_is_map ($post_id=0)
{

	if ($post_id==0)
    {
        global $post;
        $post_id = $post->ID;
    }

	$meta = (array)kz_get_post_geoloc_meta($post_id);

	return ($meta['location_name'] != '' && $meta['location_address'] != '') || ( $meta['location_latitude'] !='' && $meta['location_longitude']!='' ) ;

}

/**
 * True si une carte peut etre attachee au post (autrement dit les meta du post relatives à la geoloc sont remplies)
 * - soit l'adresse est saisie
 * - soit latitude et longitude sont présentes
 *
 * @return Boolean
 * @author 
 **/
function kz_is_latLng ()
{

	$meta = (array)kz_get_post_geoloc_meta();

	return ( $meta['location_latitude'] !='' && $meta['location_longitude']!='' ) ;

}


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_get_post_map_meta($post_id=0)
{
	if ($post_id==0)
    {
        global $post;
        $post_id = $post->ID;
    }

	$meta = (array)kz_get_post_geoloc_meta($post_id);
	$result = array();

	$post = get_post($post_id);

	$meta['post_title'] = $post->post_title;
	$meta['post_type'] 	= $post->post_type;
	$meta["permalink"] 	= get_permalink() ;

	if (function_exists('kz_is_event') && kz_is_event($post_id))
	{
		$event_meta = get_event_meta($post_id);
		$result = array_merge((array)$meta, (array)$event_meta);
	}
	else
		$result = $meta;

	return $result;

}


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_geo_map_build($coordinates)
{
	if (count($coordinates)>0) {

		wp_enqueue_script('kidzou-map',		WP_PLUGIN_URL.'/kidzou-geo/js/kidzou-map-dev.js', array('jquery'), KIDZOU_GEO_VERSION, true);

		echo '<h1>Autour de vous :</h1>';
		echo '<style>#kidzou_cat_map img {max-width:none;}</style><hr/><div id="kidzou_cat_map" style="width:100%; height:300px;">
				</div><!--p>Carte fournie par <a href="http://www.mapquest.com/">MapQuest.com</a></p--><hr/>';

		echo '	<script>
					jQuery(document).ready(function() { 
						
						var kidzou_map_cat_data = '.json_encode($coordinates). ';
						var width = jQuery("#left_area").width(); 
						jQuery("#kidzou_cat_map").css("width", width + "px");

						kidzouMap.setMapElt( document.getElementById("kidzou_cat_map") );
						kidzouMap.setZoom( 12 );
						kidzouMap.setPOICollection( kidzou_map_cat_data );
						kidzouMap.setPoiWidth( (width/3) );
						kidzouGeoContent.getUserLocation(
							function(pos) {
								kidzouMap.setMapCenter(pos);
								kidzouMap.drawMap();
							}
						);
					});
				</script>';
	}
}

/**
 * construit une carte des lieux autour du user dont il est question dans la catégorie courante
 * autrement dit : pour les posts listés dans la catégories/taxonomie courante, une carte avec leurs lieux
 *
 * @return void
 * @author 
 **/
function kz_geo_map_cat()
{

	// The Query
	global $wp_query;

	$option_active = (bool)get_option("kz_map_post_list");

	$coordinates = array();

	// The Loop
	if ( have_posts() && $option_active ) {

		$i=0;

		while ( have_posts() ) {
			
			$wp_query->the_post();
			
			$meta = kz_get_post_map_meta();

			if ( kz_is_map() ) {
				$coordinates[$i] = $meta;
				$i++;
			}
		}

	}

	kz_geo_map_build($coordinates);

	// Remettre la loop à zero
	rewind_posts();
}

/**
 * construit une carte à partir d'une liste de posts
 *
 * @return void
 * @author 
 **/
function kz_geo_map_list($list)
{
	$option_active = (bool)get_option("kz_map_post_list");

	if ( $option_active && is_array($list) && count($list)>0 ) {

		// global $post;

		$coordinates = array();

		$i=0;

		foreach ($list as $post) {

			// $post = $p;

			// setup_postdata($post);

			$meta = kz_get_post_map_meta($post->ID);

			if ( kz_is_map($post->ID) ) {
				$coordinates[$i] = $meta;
				$i++;
			}

			# code...
		}

		// wp_reset_postdata();

		kz_geo_map_build($coordinates);
	}

}


?>