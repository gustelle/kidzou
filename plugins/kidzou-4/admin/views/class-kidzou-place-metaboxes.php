<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Place_Metaboxes', 'get_instance' ) );

/**
 * Gestion des metadonnées de localisation d'un post, rattachement à un lieu 
 *
 * @todo Décharger la classe Admin dans cette class pour y voir clair dans le code
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Place_Metaboxes {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;


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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

		add_action( 'add_meta_boxes', array( $this, 'place_metaboxes' ) );
	}

	/**
	 * Recherche de la metropole la plus proche du lieu de rattachement
	 *
	 * @return void
	 * @author 
	 **/
	public function enqueue_geo_scripts()
	{

		$screen = get_current_screen(); 
		// $events = Kidzou_Events_Metaboxes::get_instance();
		// $customer = Kidzou_Customer_Metaboxes::get_instance();

		if (in_array($screen->id , $this->screen_with_meta_place)) {

			wp_enqueue_script('kidzou-admin-geo', plugins_url( '../assets/js/kidzou-admin-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

			$villes = Kidzou_Metropole::get_metropoles();

			$key = Kidzou_Utils::get_option("geo_mapquest_key",'Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6');
	  
			$args = array(
						// 'geo_activate'				=> (bool)Kidzou_Utils::get_option('geo_activate',false), //par defaut non
						'geo_mapquest_key'			=> $key, 
						'geo_mapquest_reverse_url'	=> "http://open.mapquestapi.com/geocoding/v1/reverse",
						'geo_mapquest_address_url'	=> "http://open.mapquestapi.com/geocoding/v1/address",
						// 'geo_cookie_name'			=> $locator::COOKIE_METRO,
						'geo_possible_metropoles'	=> $villes ,
						// 'geo_coords'				=> $locator::COOKIE_COORDS,
					);

		    wp_localize_script(  'kidzou-admin-geo', 'kidzou_admin_geo_jsvars', $args );
		}
		
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

		if ( in_array($screen->id , $this->screen_with_meta_place)  ) {

		
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );


			wp_enqueue_script('ko',	 		"https://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"https://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
			
			//validation des champs du formulaire de saisie des events
			wp_enqueue_script('ko-validation',			plugins_url( 'assets/js/knockout.validation.min.js', dirname(__FILE__) ),array("ko"), '1.0', true);
			wp_enqueue_script('ko-validation-locale',	plugins_url( 'assets/js/ko-validation-locales/fr-FR.js', dirname(__FILE__) ),array("ko-validation"), '1.0', true);
			
			wp_enqueue_script('selectize', 	"https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.1/js/standalone/selectize.js",array(), '0.12.1', true);
			wp_enqueue_style( 'selectize', 	"https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.1/css/selectize.default.min.css" );
			wp_enqueue_script('selectize-placecomplete', plugins_url( 'assets/js/selectize-placecomplete.js', dirname(__FILE__) ),array('placecomplete'), '0.12.1', true);


			//selection des places dans Google Places
			wp_enqueue_script('placecomplete', plugins_url( 'assets/js/jquery.placecomplete.js', dirname(__FILE__) ),array('google-maps'), '1.0', true);

			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', dirname(__FILE__) ) ,array('jquery'), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-place', plugins_url( 'assets/js/kidzou-place.js', dirname(__FILE__) ) ,array('jquery','ko-mapping', 'kidzou-admin-geo'), Kidzou::VERSION, true);

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

	public function place_metaboxes() {

		$screen = get_current_screen(); 

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
	public function place_metabox()
	{
		$screen = get_current_screen(); 

		global $post;
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="placemeta_noncename" id="placemeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		$location = Kidzou_Geoloc::get_post_location($post->ID); 

		// Get the location data if its already been entered
		$location_name 		= $location['location_name'];
		$location_address 	= $location['location_address'];
		$location_website 	= $location['location_web'];
		$location_phone_number 	= $location['location_tel'];
		$location_city 			= $location['location_city'];
		$location_latitude 		= $location['location_latitude'];
		$location_longitude 	= $location['location_longitude'];

		//si aucune méta de lieu n'est pré-existante, on prend celle du client associé au post
		//A condition qu'il ne s'agisse pas d'un ecran "customer"
		if ($location_name=='' && $screen->id!='customer') {

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

			$location = Kidzou_Geoloc::get_post_location($id);

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

		echo '
		<script>
		jQuery(document).ready(function() {

			kidzouPlaceModule.model.initPlace("'.$location_name.'","'.$location_address.'","'.$location_website.'","'.$location_phone_number.'","'.$location_city.'","'.$location_latitude.'","'.$location_longitude.'");
			
			//selection GooglePlace/PlaceComplete depuis selectize
			jQuery("select[name=\'place\']").selectize({
			  mode: "single",
			  openOnFocus: false,
			  delimiter: null,
			  plugins: {
			    \'placecomplete\': {
			      selectDetails: function(placeResult) { 
		
			      	var city = placeResult.display_text;
					//tentative de retrouver la ville de manière plus précise
					//voir https://developers.google.com/maps/documentation/geocoding/?hl=FR#Types
					placeResult.address_components.forEach(function(entry) {
					    if (entry.types[0]==\'locality\') {
					    	city = entry.long_name;
					    }
					});
			      	//Alimenter les champs Kidzou
			        kidzouPlaceModule.model.completePlace({
			        		name 	: placeResult.name, 
							address : placeResult.formatted_address, 
							website : placeResult.website, 
							phone 	: placeResult.formatted_phone_number, 
							city 	: city,
							latitude 	: placeResult.geometry.location.lat(), //latitude
							longitude 	: placeResult.geometry.location.lng(), //longitude
							opening_hours : (placeResult.opening_hours ? placeResult.opening_hours.periods : [])
			        	});
			        // la valeur que prend le <select>
			        return placeResult.name + ", " + placeResult.formatted_address;
			      }
			    }
			  }
			});

		});
		</script>

		<div class="kz_form hide" id="place_form">

			<input type="hidden" name="kz_location_latitude" id="kz_location_latitude" data-bind="value: placeData().place().lat" />
			<input type="hidden" name="kz_location_longitude" id="kz_location_longitude" data-bind="value: placeData().place().lng" />

			<h4>Cela se passe o&ugrave; ?</h4>
			<ul>
			<!-- ko if: isProposal() -->
			<li>
				<h5>Autre adresse possible : </h5>
				<div data-bind="foreach: placeProposals">
					<div class="address_proposal">
						<span data-bind="text: $data.place.name"></span><br/>
						<span data-bind="text: $data.place.address"></span><br/>
						<span data-bind="text: $data.place.city"></span><br/>
						<em><a href="#" data-bind="click: $parent.useAddress">Utiliser cette adresse</a></em>
					</div>
				</div>
			</li>
			<!-- /ko -->
			<!-- selectize ne fonctionne que si l\'element est dans le DOM , il faut donc utiliser un bind "visible" et non "if" -->
			<li data-bind="visible: !customPlace()">
				<select name="place" style="width:80%"></select>
				<br/><br/>
				<em>
					<a href="#" data-bind="click: displayCustomPlaceForm">Vous ne trouvez pas votre bonheur dans cette liste?</a><br/>
				</em>
			</li>
			<!-- ko if: customPlace() -->
			<li class="fade-in">
				<label for="kz_location_name">Nom du lieu:</label>
				<input type="text" name="kz_location_name" placeholder="Ex: chez Gaspard" data-bind="value: placeData().place().venue" required>

			</li>
			<li class="fade-in">
				<label for="kz_location_address">Adresse:</label>
				<input type="text" name="kz_location_address" placeholder="Ex: 13 Boulevard Louis XIV 59800 Lille" data-bind="value: placeData().place().address" required>
			</li>
			<li class="fade-in">
				<label for="kz_location_city">Quartier / Ville:</label>
				<input type="text" name="kz_location_city" placeholder="Ex: Lille Sud" data-bind="value: placeData().place().city" required>

			</li>
			<li class="fade-in">
				<label for="kz_location_latitude">Latitude:</label>
				<input type="text" name="kz_location_latitude" placeholder="Ex : 50.625935" data-bind="value: placeData().place().lat" >
			</li>
			<li class="fade-in">
				<label for="kz_location_longitude">Longitude:</label>
				<input type="text" name="kz_location_longitude" placeholder="Ex : 3.0462689999999384" data-bind="value: placeData().place().lng" >
			</li>
			<li class="fade-in">
				<label for="kz_location_website">Site web:</label>
				<input type="text" name="kz_location_website" placeholder="Ex: http://www.kidzou.fr" data-bind="value: placeData().place().website" >
			</li>
			<li class="fade-in">
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

		$this->save_place_meta($post_id);

	}


	/**
	 * Enregistrement de la metabox 'place'
	 *
	 * @uses self::save_place()
	 * @param $post_id int le post sur lequel on vient attacher la meta  
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

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.
		$location_name			= (isset($_POST['kz_location_name']) ? $_POST['kz_location_name'] : '');
		$location_address 		= (isset($_POST['kz_location_address']) ? $_POST['kz_location_address'] : '');
		$location_website 		= (isset($_POST['kz_location_website']) ? $_POST['kz_location_website'] : '');
		$location_phone_number 	= (isset($_POST['kz_location_phone_number']) ? $_POST['kz_location_phone_number'] : '');
		$location_city			= (isset($_POST['kz_location_city']) ? $_POST['kz_location_city'] : '');
		$location_latitude 		= (isset($_POST['kz_location_latitude']) ? $_POST['kz_location_latitude'] : '');
		$location_longitude		= (isset($_POST['kz_location_longitude']) ? $_POST['kz_location_longitude'] : '');

		Kidzou_Geoloc::set_location($post_id, $location_name, $location_address, $location_website, $location_phone_number, $location_city, $location_latitude, $location_longitude);
		
	}



}
