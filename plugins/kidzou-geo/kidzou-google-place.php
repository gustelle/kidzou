<?php

add_action( 'admin_enqueue_scripts', 'kz_enqueue_place_scripts' );

function kz_enqueue_place_scripts() {

	$screen = get_current_screen();

	if ($screen->post_type == 'event' ||  $screen->post_type == 'post' ||  $screen->post_type == 'offres' ||  $screen->post_type == 'concours') {

		if (!wp_style_is( 'kidzou-form', 'enqueued' ) )
			wp_enqueue_style( 'kidzou-form', WP_PLUGIN_URL."/kidzou/css/kidzou-form.css" );

		wp_enqueue_script('kidzou-storage',		WP_PLUGIN_URL.'/kidzou/js/front/kidzou-storage-dev.js', array('jquery'), KIDZOU_VERSION, true);

		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
		//validation des champs du formulaire de saisie des events
		wp_enqueue_script('ko-validation',			WP_PLUGIN_URL.'/kidzou/js/admin/knockout.validation.min.js',array("ko"), '1.0', true);
		wp_enqueue_script('ko-validation-locale',	WP_PLUGIN_URL.'/kidzou/js/admin/ko-validation-locales/fr-FR.js',array("ko-validation"), '1.0', true);
		
		//requis par placecomplete
		wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
		wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
		wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
		//selection des places dans Google Places
		wp_enqueue_style( 'placecomplete', WP_PLUGIN_URL."/kidzou-geo/css/jquery.placecomplete.css" );
		wp_enqueue_script('placecomplete', WP_PLUGIN_URL."/kidzou-geo/js/jquery.placecomplete.js",array('jquery-select2', 'google-maps'), '1.0', true);
		wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

		wp_enqueue_script('kidzou-geo', WP_PLUGIN_URL."/kidzou-geo/js/kidzou-geo.js" ,array('jquery','kidzou-storage'), KIDZOU_GEO_VERSION, true);
		wp_enqueue_script('kidzou-place', WP_PLUGIN_URL."/kidzou-geo/js/kidzou-place.js" ,array('jquery','ko-mapping'), KIDZOU_GEO_VERSION, true);
		wp_enqueue_style( 'kidzou-place', WP_PLUGIN_URL."/kidzou-geo/css/kidzou-edit-place.css" );

		add_kz_geo_scripts();
	}
	
}


add_action( 'add_meta_boxes', 'kz_add_place_metabox' );

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_add_place_metabox()
{
	add_meta_box('kz_place_metabox', 'Lieu', 'kz_place_metabox', 'post', 'normal', 'high');
	add_meta_box('kz_place_metabox', 'Lieu', 'kz_place_metabox', 'offres', 'normal', 'high');
	add_meta_box('kz_place_metabox', 'Lieu', 'kz_place_metabox', 'concours', 'normal', 'high');
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_place_metabox()
{
	global $post; 
	global $wpdb;
	
	$type = $post->post_type;

	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="placemeta_noncename" id="placemeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	// Get the location data if its already been entered
	$location_name 		= get_post_meta($post->ID, 'kz_' .$type .'_location_name', TRUE);
	$location_address 	= get_post_meta($post->ID, 'kz_' .$type .'_location_address', TRUE);
	$location_website 	= get_post_meta($post->ID, 'kz_' .$type .'_location_website', TRUE);
	$location_phone_number 	= get_post_meta($post->ID, 'kz_' .$type .'_location_phone_number', TRUE);
	$location_city 			= get_post_meta($post->ID, 'kz_' .$type .'_location_city', TRUE);
	$location_latitude 		= get_post_meta($post->ID, 'kz_' .$type .'_location_latitude', TRUE);
	$location_longitude 	= get_post_meta($post->ID, 'kz_' .$type .'_location_longitude', TRUE);

	echo '<script>
	jQuery(document).ready(function() {
		kidzouPlaceModule.model.initPlace("'.$location_name.'","'.$location_address.'","'.$location_website.'","'.$location_phone_number.'","'.$location_city.'","'.$location_latitude.'","'.$location_longitude.'");
	});
	</script>';

	?>
	<div class="kz_form" id="place_form">

		<?php
			//non affiché au user, pas utile pour lui
			//utile uniquement pour l'affichage des cartes google maps dans les posts
			echo '<input type="hidden" name="kz_location_latitude" id="kz_location_latitude" data-bind="value: placeData().place().lat" />';
			echo '<input type="hidden" name="kz_location_longitude" id="kz_location_longitude" data-bind="value: placeData().place().lng" />';

		?>

		<h4>Cela se passe o&ugrave; ?</h4>
		<ul>
		<!-- ko ifnot: customPlace() -->
		<li>
			<input type="hidden" name="place" data-bind="placecomplete:{
															placeholderText: 'Ou cela se passe-t-il ?',
															minimumInputLength: 2,
															allowClear:true,
														    requestParams: {
														        types: ['establishment']
														    }}, event: {'placecomplete:selected':completePlace}" style="width:80%" >
			<br/><br/>
			<em>
				<a href="#" data-bind="click: displayCustomPlaceForm">Vous ne trouvez pas votre bonheur dans cette liste?</a><br/>
			</em>
		</li>
		<!-- /ko -->
		<!-- ko if: customPlace() -->
		<li>
			<label for="kz_location_name">Nom du lieu:</label>
			<input type="text" name="kz_location_name" placeholder="Ex: chez Gaspard" data-bind="value: placeData().place().venue" required>

		</li>
		<li>
			<label for="kz_location_address">Adresse:</label>
			<input type="text" name="kz_location_address" placeholder="Ex: 13 Boulevard Louis XIV 59800 Lille" data-bind="value: placeData().place().address" required>
		</li>
		<li>
			<label for="kz_location_city">Quartier / Ville:</label>
			<input type="text" name="kz_location_city" placeholder="Ex: Lille Sud" data-bind="value: placeData().place().city" required>

		</li>
		<li>
			<label for="kz_location_latitude">Latitude:</label>
			<input type="text" name="kz_location_latitude" placeholder="Ex : 50.625935" data-bind="value: placeData().place().lat" >
		</li>
		<li>
			<label for="kz_location_longitude">Longitude:</label>
			<input type="text" name="kz_location_longitude" placeholder="Ex : 3.0462689999999384" data-bind="value: placeData().place().lng" >
		</li>
		<li>
			<label for="kz_location_website">Site web:</label>
			<input type="text" name="kz_location_website" placeholder="Ex: http://www.kidzou.fr" data-bind="value: placeData().place().website" >
		</li>
		<li>
			<label for="kz_location_phone_number">Tel:</label>
			<input type="text" name="kz_location_phone_number" placeholder="Ex : 03 20 30 40 50" data-bind="value: placeData().place().phone_number" >
		</li>

		<li>
			
		</li>
		<li><a href="#" data-bind="click: displayGooglePlaceForm">Revenir a la recherche Google</a></li>	
		<!-- /ko -->

		</ul>

	</div>

	<?php
}

add_action( 'save_post', 'kz_save_place_info' );

/**
 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
 *
 * @return void
 * @author 
 **/
function kz_save_place_info($post_id)
{

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['placemeta_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post_id ) || !current_user_can( 'edit_event', $post_id ))
		return $post_id;

	$type = get_post_type($post_id);

	// OK, we're authenticated: we need to find and save the data
	// We'll put it into an array to make it easier to loop though.
	$events_meta['location_name'] 			= $_POST['kz_location_name'];
	$events_meta['location_address'] 		= $_POST['kz_location_address'];
	$events_meta['location_website'] 		= $_POST['kz_location_website'];
	$events_meta['location_phone_number'] 	= $_POST['kz_location_phone_number'];
	$events_meta['location_city'] 			= $_POST['kz_location_city'];
	$events_meta['location_latitude'] 		= $_POST['kz_location_latitude'];
	$events_meta['location_longitude'] 		= $_POST['kz_location_longitude'];

	$prefix = 'kz_' . $type . '_';

	// Add values of $events_meta as custom fields
	foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
		$pref_key = $prefix.$key;
		// if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		// $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		$prev = get_post_meta($post_id, $pref_key, TRUE);
		if($prev && $prev!='') { // If the custom field already has a value
			update_post_meta($post_id, $pref_key, $value, $prev);
		} else { // If the custom field doesn't have a value
			if ($prev=='') delete_post_meta($post_id, $pref_key);
			add_post_meta($post_id, $pref_key, $value, TRUE);
		}
		if(!$value) delete_post_meta($post_id, $pref_key); // Delete if blank
	}
}


?>