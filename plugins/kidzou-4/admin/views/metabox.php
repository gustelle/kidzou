<?php


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_event_metabox()
{
	global $post; 
	global $wpdb;
	
	$checkbox = false;

	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	$start_date		= get_post_meta($post->ID, 'kz_event_start_date', TRUE);
	$end_date 		= get_post_meta($post->ID, 'kz_event_end_date', TRUE);

	echo '<script>
	jQuery(document).ready(function() {
		kidzouEventsModule.model.initDates("'.$start_date.'","'.$end_date.'");
	});
	</script>';

	?>
	<div class="kz_form" id="event_form">

		<?php

		//si le user n'est pas un "pro", on permet des fonctions d'administration supplémentaires
		if (!current_user_can('pro')) {

			echo '<h4>Fonctions client</h4>
					<ul>';
			$checkbox = get_post_meta($post->ID, 'kz_event_featured', TRUE);
			echo '	<li>
						<label for="kz_event_featured">Mise en avant:</label>
						<input type="checkbox" name="kz_event_featured"'. ( $checkbox == 'A' ? 'checked="checked"' : '' ).'/>  
					</li>
					</ul>';
				
		} ?>

		<h4>Dates de l'&eacute;v&eacute;nement</h4>

		<ul>
			<li>
				<label for="start_date">Date de d&eacute;but:</label>
		    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().start_date, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
		    	<input type="hidden" name="kz_event_start_date"  data-bind="value: eventData().formattedStartDate" />
		    	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
			</li>
			<li>
				<label for="end_date">Date de fin</label>
		    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().end_date, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
				<input type="hidden" name="kz_event_end_date"  data-bind="value: eventData().formattedEndDate" />
				<em data-bind="if: eventData().eventDuration()!==''">(<span data-bind="text: eventData().eventDuration"></span>)</em>
				<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
			</li>
		</ul>

	</div>

	<?php
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

?>