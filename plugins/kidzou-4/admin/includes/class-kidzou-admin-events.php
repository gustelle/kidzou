<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Admin_Events', 'get_instance' ) );

/**
 * Kidzou
 *
 * @package   Kidzou_Admin
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * 
 * @todo Décharger la classe Admin dans cette class pour y voir clair dans le code
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Admin_Events {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  d'evenement
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_event = array('post'); // typiquement pas les "customer"

	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  place
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_place = array('post', 'customer'); 



	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		
		//sauvegarde des meta à l'enregistrement
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		add_action( 'add_meta_boxes', array( $this, 'event_metaboxes' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		$screen = get_current_screen(); 

		//on a besoin de font awesome dans le paneau d'admin
		// wp_enqueue_style( 'fontello', "//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css", null, '3.0.2' );

		if ( in_array($screen->id , $this->screen_with_meta_event)  ) {

		
			wp_enqueue_style( 'kidzou-place', plugins_url( 'assets/css/kidzou-edit-place.css', dirname(__FILE__) ) );
			wp_enqueue_style( 'placecomplete', plugins_url( 'assets/css/jquery.placecomplete.css', dirname(__FILE__) ) );
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

			//datepicker pour les events
			wp_enqueue_style( 'jquery-ui-custom', plugins_url( 'assets/css/jquery-ui-1.10.3.custom.min.css', dirname(__FILE__) ) );	

			wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
			
			//validation des champs du formulaire de saisie des events
			wp_enqueue_script('ko-validation',			plugins_url( 'assets/js/knockout.validation.min.js', dirname(__FILE__) ),array("ko"), '1.0', true);
			wp_enqueue_script('ko-validation-locale',	plugins_url( 'assets/js/ko-validation-locales/fr-FR.js', dirname(__FILE__) ),array("ko-validation"), '1.0', true);
			
			//requis par placecomplete
			wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
			wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );

			//selection des places dans Google Places
			wp_enqueue_script('placecomplete', plugins_url( 'assets/js/jquery.placecomplete.js', dirname(__FILE__) ),array('jquery-select2', 'google-maps'), '1.0', true);
			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', dirname(__FILE__) ) ,array('jquery'), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-place', plugins_url( 'assets/js/kidzou-place.js', dirname(__FILE__) ) ,array('jquery','ko-mapping'), Kidzou::VERSION, true);
			
			//gestion des events
			wp_enqueue_script('kidzou-event', plugins_url( 'assets/js/kidzou-event.js', dirname(__FILE__) ) ,array('jquery','ko-mapping', 'moment'), Kidzou::VERSION, true);
			wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
			wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-datepicker-fr', plugins_url( 'assets/js/jquery.ui.datepicker-fr.js', dirname(__FILE__) ), array('jquery-ui-datepicker'),'1.0', true);

			// wp_enqueue_script( 'kidzou-admin-script', plugins_url( 'assets/js/admin.js', dirname(__FILE__) ), array( 'jquery' ), Kidzou::VERSION );
			wp_localize_script('kidzou-event', 'events_jsvars', array(
				'api_getClients'				=> site_url()."/api/clients/getClients/",
				'api_getCustomerPlace'			=> site_url()."/api/clients/getCustomerPlace/",
				'api_addMediaFromURL'			=> site_url()."/api/import/addMediaFromURL/"
				)
			);

		} 


	}


	

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function event_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta_event) ) { 
			// add_meta_box('kz_facebook_metabox', 'Importer un &eacute;v&eacute;nement Facebook', array($this, 'facebook_event_metabox'), $screen->id, 'normal', 'high');
			add_meta_box('kz_event_metabox', 'Evenement', array($this, 'event_metabox'), $screen->id, 'normal', 'high');
		} 

		if ( in_array($screen->id , $this->screen_with_meta_place) ) {
			add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');
		}

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function event_metabox()
	{
		global $post; 

		////////////////////////////////

		$checkbox = false;

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'event_metabox', 'event_metabox_nonce' );
		
		$start_date		= get_post_meta($post->ID, Kidzou_Events::$meta_start_date, TRUE);
		$end_date 		= get_post_meta($post->ID, Kidzou_Events::$meta_end_date, TRUE);
		$recurrence		= get_post_meta($post->ID, Kidzou_Events::$meta_recurring, FALSE);
		$past_dates		= get_post_meta($post->ID, Kidzou_Events::$meta_past_dates, FALSE);

		$facebook_appId = Kidzou_Utils::get_option('fb_app_id','');
		$facebook_appSecret = Kidzou_Utils::get_option('fb_app_secret','');

		if ($facebook_appId!='' && $facebook_appSecret!='') {

			echo "
				<script>
				  window.fbAsyncInit = function() {
				    FB.init({
				      appId      : 'your-app-id',
				      xfbml      : true,
				      version    : 'v2.4'
				    });
				  };

				  (function(d, s, id){
				     var js, fjs = d.getElementsByTagName(s)[0];
				     if (d.getElementById(id)) {return;}
				     js = d.createElement(s); js.id = id;
				     js.src = '//connect.facebook.net/en_US/sdk.js';
				     fjs.parentNode.insertBefore(js, fjs);
				   }(document, 'script', 'facebook-jssdk'));
				</script>
			";
		}

		echo '<script>
		jQuery(document).ready(function() {
			kidzouEventsModule.model.initDates("'.$start_date.'","'.$end_date.'", '.json_encode($recurrence).');
		});
		</script>

		<div class="kz_form" id="event_form">';

			if ($facebook_appId!='' && $facebook_appSecret!='' && Kidzou_Utils::current_user_is('administrator')) {

				$token_url =	"https://graph.facebook.com/oauth/access_token?" .
								"client_id=" . $facebook_appId .
								"&client_secret=" . $facebook_appSecret .
								"&grant_type=client_credentials";

				$resp = file_get_contents($token_url);
				$pattern = '/access_token=(.+)/';
				preg_match($pattern, $resp, $matches);

				echo '
					<h4>Importer un &eacute;v&eacute;nement Facebook</h4>
					<ul>
						<li>
							<label for="facebook_url">URL de l&apos;&eacute;v&eacute;nement Facebook:</label>
					    	<input type="text" placeholder="Ex : https://www.facebook.com/events/1028586230505678/"   data-bind="value: eventData().facebookUrl" /> 
							<input type="hidden" name="access_token"  value="'.$matches[1].'" />
							<span data-bind="html: eventData().facebookImportMessage"></span>
						</li>
					</ul>
				';
			}

			//si le user n'est pas un "pro", on permet des fonctions d'administration supplémentaires
			if (Kidzou_Utils::current_user_is('administrator')) {

				echo '<h4>Fonctions client</h4>
						<ul>';
				$checkbox = get_post_meta($post->ID, 'kz_event_featured', TRUE);
				echo '	<li>
							<label for="kz_event_featured">Mise en avant:</label>
							<input type="checkbox" name="kz_event_featured"'. ( $checkbox == 'A' ? 'checked="checked"' : '' ).'/>  
						</li>
						</ul>';
					
			} 

			echo '<h4>Dates de l&apos;&eacute;v&eacute;nement</h4>

			<ul>
				<li>
					<label for="start_date">Date de d&eacute;but:</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().start_date, datepickerOptions: { dateFormat: \'dd MM yy\' }"  /> <!-- required -->
			    	<input type="hidden" name="kz_event_start_date"  data-bind="value: eventData().formattedStartDate" />
			    	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
				</li>
				<li>
					<label for="end_date">Date de fin</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().end_date, datepickerOptions: { dateFormat: \'dd MM yy\' }" />
					<input type="hidden" name="kz_event_end_date"  data-bind="value: eventData().formattedEndDate" />
					<em data-bind="if: eventData().eventDuration()!==\'\'">(<span data-bind="text: eventData().eventDuration"></span>)</em>
					<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
				</li>';

				if (Kidzou_Utils::current_user_is('author')) {
					echo
					'<li>
						<label for="kz_event_is_reccuring">Cet &eacute;v&eacute;nement est r&eacute;current </label>
						<input type="checkbox" name="kz_event_is_reccuring" data-bind="enable: eventData().isReccurenceEnabled, checked: eventData().recurrenceModel().isReccuring" />
					</li>';
				}

			echo '</ul>

			<!-- ko if: eventData().recurrenceModel().isReccuring -->
			<h4>R&eacute;p&eacute;tition de L&apos;&eacute;v&eacute;nement</h4>
			<ul>	
		    	<li data-bind="visible: $root.eventData().recurrenceModel().showSelectRepeat">
		    		<label for="kz_event_reccurence_mod">R&eacute;ccurence:</label>
					<select name="kz_event_reccurence_mod" data-bind="options: $root.eventData().recurrenceModel().repeatOptions,
																		optionsText: \'label\',
												                       	value: $root.eventData().recurrenceModel().selectedRepeat" ></select>
					<input type="hidden" name="kz_event_reccurence_model" data-bind="value: eventData().recurrenceModel().selectedRepeat().value" />

		    	</li>
		    	<li>
		    		<label for="kz_event_reccurence_repeat_select">R&eacute;p&eacute;ter tous les :</label>
					<select name="kz_event_reccurence_repeat_select" data-bind="options: $root.eventData().recurrenceModel().selectedRepeat().repeatEvery,
																		value: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEvery" ></select>

		    	</li>
		    	
		    	<li>
		    		<label for="kz_event_reccurence_repeat_choices">R&eacute;p&eacute;ter le :</label>
		    		<!-- ko if: $root.eventData().recurrenceModel().selectedRepeat().multipleChoice -->
			    		<span data-bind="foreach:  $root.eventData().recurrenceModel().selectedRepeat().repeatEach">
			    			<input type="checkbox" name="kz_event_reccurence_repeat_choices"  data-bind="checked: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems, checkedValue: $data" /><span data-bind="text: $data.label" style="padding-right:6px;"></span>
			    			<input type="hidden" name="kz_event_reccurence_repeat_weekly_items" data-bind="value: $root.eventData().recurrenceModel().repeatItemsValue()" />
			    		</span>
			    	<!-- /ko -->
		    		<!-- ko ifnot: $root.eventData().recurrenceModel().selectedRepeat().multipleChoice -->
			    		<span data-bind="foreach:  $root.eventData().recurrenceModel().selectedRepeat().repeatEach">
			    			<input type="radio" name="kz_event_reccurence_repeat_choices" data-bind="checked: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems, checkedValue: $data" /><span data-bind="text: $data.label" style="padding-right:6px;"></span>
			    			<input type="hidden" name="kz_event_reccurence_repeat_monthly_items" data-bind="value: $root.eventData().recurrenceModel().repeatItemsValue()" />
			    		</span>
		    		<!-- /ko -->
		    	</li>

			</ul>
			<ul>	
				<li>
					<label for="kz_event_reccurence_end_type">L&apos;&eacute;v&eacute;nement prend fin :</label>
		    		<input type="radio" name="kz_event_reccurence_end_type" value="never" data-bind="checked: eventData().recurrenceModel().endType" /> never
		    	</li>
		    	<li>
		    		<label> </label>
		    		<input type="radio" name="kz_event_reccurence_end_type" value="date" data-bind="checked: eventData().recurrenceModel().endType" /> Le
		    		<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().recurrenceModel().reccurenceEndDate, datepickerOptions: { dateFormat: \'dd MM yy\' }"  /> 
			    	<input type="hidden" name="kz_event_reccurence_end_date" data-bind="value: eventData().recurrenceModel().formattedReccurenceEndDate" />
		    	</li>
			   	<li>
			   		<label> </label>
			    	<input type="radio" name="kz_event_reccurence_end_type" value="occurences" data-bind="checked: eventData().recurrenceModel().endType" /> Apr&egrave;s <input type="text" name="kz_event_reccurence_end_after_occurences" data-bind="value: eventData().recurrenceModel().occurencesNumber" /> occurences
			    </li>
			</ul>
			<ul>	
		    	<li><b>R&eacute;sum&eacute; : <span data-bind="text: eventData().recurrenceModel().recurrenceSummary()" /></b></li>
			</ul>
			<!-- /ko -->';

			if (!empty($past_dates) && count($past_dates[0])>0)
			{
				echo '<ul><h4>Ev&eacute;nements pass&eacute;s :</h4>';
				foreach ($past_dates[0] as  $value) {
					// Kidzou_Utils::log($value);
					$past_start_date=date_create($value['start_date']);
					$past_end_date=date_create($value['end_date']);
					echo '<li>Du '.date_format($past_start_date,"d/m/Y").' au '.date_format($past_end_date,"d/m/Y").'</li>';
				}
				echo '</ul>';

			}

		echo 
		'</div>';

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function place_metabox()
	{
		global $post;
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="placemeta_noncename" id="placemeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		$location = Kidzou_GeoHelper::get_post_location($post->ID); 

		// Get the location data if its already been entered
		$location_name 		= $location['location_name'];
		$location_address 	= $location['location_address'];
		$location_website 	= $location['location_web'];
		$location_phone_number 	= $location['location_tel'];
		$location_city 			= $location['location_city'];
		$location_latitude 		= $location['location_latitude'];
		$location_longitude 	= $location['location_longitude'];

		//si aucune méta de lieu n'est pré-existante, on prend celle du client associé au post
		if ($location_name=='') {

			$id = 0;

			if (!Kidzou_Utils::current_user_is('administrator') ) {

				$res = Kidzou_Customer::getCustomersIDByUserID();//print_r($res);

				//on prend le premier s'il n'y en a qu'un
				if (is_array($res) && count($res)==1) {
					$vals =array_values($res);
					$id = $vals[0];
				}
					
			} else {
				
				$id = Kidzou_Customer::getCustomerIDByPostID();
				if (is_wp_error($id))
					$id=0;
			}

			$location = Kidzou_GeoHelper::get_post_location($id);

			if (isset($location['location_name']) && $location['location_name']!='') {

				$location_name 		= $location['location_name'];
				$location_address 	= $location['location_address'];
				$location_website 	= $location['location_web'];
				$location_phone_number 	= $location['location_tel'];
				$location_city 			= $location['location_city'];
				$location_latitude 		= $location['location_latitude'];
				$location_longitude 	= $location['location_longitude'];
			}

		}

		echo '<script>
		jQuery(document).ready(function() {
			kidzouPlaceModule.model.initPlace("'.$location_name.'","'.$location_address.'","'.$location_website.'","'.$location_phone_number.'","'.$location_city.'","'.$location_latitude.'","'.$location_longitude.'");
		});
		</script>

		<div class="kz_form" id="place_form">

			<input type="hidden" name="kz_location_latitude" id="kz_location_latitude" data-bind="value: placeData().place().lat" />
			<input type="hidden" name="kz_location_longitude" id="kz_location_longitude" data-bind="value: placeData().place().lng" />

			<h4>Cela se passe o&ugrave; ?</h4>
			<ul>
			<!-- ko ifnot: customPlace() -->
			<li>
				<input type="hidden" name="place" data-bind="placecomplete:{
																placeholderText: \'Ou cela se passe-t-il ?\',
																minimumInputLength: 2,
																allowClear:true,
															    requestParams: {
															        types: [\'establishment\']
															    }}, event: {\'placecomplete:selected\':completePlace}" style="width:80%" >
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
			<li><button data-bind="click: displayGooglePlaceForm" class="button button-primary button-large">Changer de lieu</button></li>	
			<!-- /ko -->

			</ul>

		</div>';
	}


	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 **/
	public function save_metaboxes($post_id) {

		$this->unarchive_event($post_id);

		//
		$this->save_event_meta($post_id);
		$this->save_place_meta($post_id);

	}

	/**
	 * <p>
	 * Un événement obsolète est automatiquement dépublié ou archivé par le système.
	 * Lorsqu'un événement est marqué par la meta "archive" par le cron, le système sait que le traitement est déjà passé sur cet evenement et ne cherche pas à repasser dessus 
	 * <br/>
	 * Lorsqu'un user reactualise un événement (changement de dates), il faut supprimer cette meta pour que le traitement puisse repasser dessus et le ré-archiver ou dépublier selon préférence de l'utilisateur
	 * </p>
	 *
	 * @internal
	 *
	 **/
	private function unarchive_event($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		delete_post_meta($post_id, Kidzou_Events::$meta_archive); 

		// Kidzou_Utils::log('Evenement '.$post_id. ' désarchivé');
	}

	/**
	 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
	 *
	 * @return void
	 * @author 
	 **/
	private function save_event_meta($post_id)
	{

		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slug = 'post';

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		// Check if our nonce is set.
		if ( ! isset( $_POST['event_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['event_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'event_metabox' ) )
			return $post_id;


		//formatter les dates avant de les sauvegarder 
		//input : 23 Février 2014
		//output : 2014-02-23 00:00:01 (start_date) ou 2014-02-23 23:59:59 (end_date)
		$events_meta['start_date'] 			= (isset($_POST['kz_event_start_date']) ? $_POST['kz_event_start_date'] : '');
		$events_meta['end_date'] 				= (isset($_POST['kz_event_end_date']) ? $_POST['kz_event_end_date'] : '');

		//les options de récurrence
		if (isset($_POST['kz_event_is_reccuring']) && $_POST['kz_event_is_reccuring']=='on')
		{
			$events_meta['recurrence'] = array(
					"model" => $_POST['kz_event_reccurence_model'],
					"repeatEach" => $_POST['kz_event_reccurence_repeat_select'],
					"repeatItems" => (isset($_POST['kz_event_reccurence_repeat_monthly_items']) ? $_POST['kz_event_reccurence_repeat_monthly_items'] : json_decode($_POST['kz_event_reccurence_repeat_weekly_items'])), 
					"endType" 	=> $_POST['kz_event_reccurence_end_type'],
					"endValue"	=> ($_POST['kz_event_reccurence_end_type']=='date' ? $_POST['kz_event_reccurence_end_date'] : $_POST['kz_event_reccurence_end_after_occurences'])
				);
		}
		
		//cette metadonnée n'est pas mise à jour dans tous les cas
		//uniquement si le user est admi
		// echo ''
		if ( Kidzou_Utils::current_user_is('administrator') ) 
			$events_meta['featured'] 			= (isset($_POST['kz_event_featured']) && $_POST['kz_event_featured']=='on' ? "A" : "B");
		else {
			if (get_post_meta($post_id, 'kz_event_featured', TRUE)!='') {
				$events_meta['featured'] 			= get_post_meta($post_id, 'kz_event_featured', TRUE);
			} else {
				$events_meta['featured'] = "B";//($events_meta['start_date']!='' ? "B" : "Z");
			}
				
		}


		Kidzou_Admin::save_meta($post_id, $events_meta, "kz_event_");

	}

	/**
	 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
	 *
	 * @return void
	 * @author 
	 **/
	private function save_place_meta($post_id)
	{	
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slugs = array('post', 'customer');

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || !in_array($_POST['post_type'] , $slugs) ) {
	        return;
	    }

		if ( ! isset( $_POST['placemeta_noncename'] ) )
			return $post_id;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['placemeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		// Is the user allowed to edit the post or page?
		if ( !Kidzou_Utils::current_user_is('contributor') )
			return $post_id;

		$type = get_post_type($post_id);

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.
		$events_meta['location_name'] 			= (isset($_POST['kz_location_name']) ? $_POST['kz_location_name'] : '');
		$events_meta['location_address'] 		= (isset($_POST['kz_location_address']) ? $_POST['kz_location_address'] : '');
		$events_meta['location_website'] 		= (isset($_POST['kz_location_website']) ? $_POST['kz_location_website'] : '');
		$events_meta['location_phone_number'] 	= (isset($_POST['kz_location_phone_number']) ? $_POST['kz_location_phone_number'] : '');
		$events_meta['location_city'] 			= (isset($_POST['kz_location_city']) ? $_POST['kz_location_city'] : '');
		$events_meta['location_latitude'] 		= (isset($_POST['kz_location_latitude']) ? $_POST['kz_location_latitude'] : '');
		$events_meta['location_longitude'] 		= (isset($_POST['kz_location_longitude']) ? $_POST['kz_location_longitude'] : '');

		$prefix = 'kz_' . $type . '_';

		Kidzou_Admin::save_meta($post_id, $events_meta, $prefix);
		
	}

}
