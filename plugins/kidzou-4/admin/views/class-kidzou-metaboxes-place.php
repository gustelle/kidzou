<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Place', 'get_instance' ) , 14);

/**
 * Gestion des metadonnées de localisation d'un post, rattachement à un lieu 
 *
 * @todo Décharger la classe Admin dans cette class pour y voir clair dans le code
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Metaboxes_Place {

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

		if (in_array($screen->id , $this->screen_with_meta_place)) {

			// wp_enqueue_script('kidzou-storage', 	plugins_url( '../assets/js/kidzou-storage.js', dirname(__FILE__) ) ,array('jquery'), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-admin-geo', 	plugins_url( '../assets/js/kidzou-admin-geo.js', __FILE__ ) ,array('jquery'), Kidzou::VERSION, true);

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

			$args = array();
			global $post;
			$location = Kidzou_Geoloc::get_post_location($post->ID); 

			// Kidzou_Utils::log($location,true);

			$args['location_name'] 		= $location['location_name'];
			$args['location_address'] 	= $location['location_address'];
			$args['location_website'] 	= $location['location_website'];
			$args['location_phone_number'] 	= $location['location_phone_number'];
			$args['location_city'] 		= $location['location_city'];
			$args['location_latitude'] 	= $location['location_latitude'];
			$args['location_longitude'] = $location['location_longitude'];

			$args['api_save_place'] 	= site_url()."/api/content/place/";
			$args['api_set_post_terms'] = site_url()."/api/taxonomy/setPostTerms/";
			$args['api_base'] 			= site_url();

			//recuperation de l'adresse du client associé pour la proposer
			//A condition qu'il ne s'agisse pas d'un ecran "customer" et que le user n'ait pas le droit de selectionner un client
			//sinon, cette proposition d'adresse client viendra de la metabox customer
			if ($screen->id!='customer' && !Kidzou_Utils::current_user_is('administrator')) {

				$id = 0;
				$res = Kidzou_Customer::getCustomersIDByUserID();//print_r($res);
	
				//on prend le premier 
				if ( is_array($res) ) { //&& count($res)==1
					$vals =array_values($res);
					$id = reset($vals);//[0];

					$customer_location = Kidzou_Geoloc::get_post_location($id);

					if (isset($customer_location['location_name']) && $customer_location['location_name']!='') {

						$args['customer_location_name'] 	= $customer_location['location_name'];
						$args['customer_location_address'] 	= $customer_location['location_address'];
						$args['customer_location_website'] 		= $customer_location['location_website'];
						$args['customer_location_phone_number'] = $customer_location['location_phone_number'];
						$args['customer_location_city'] 	= $customer_location['location_city'];
						$args['customer_location_latitude'] = $customer_location['location_latitude'];
						$args['customer_location_longitude'] = $customer_location['location_longitude'];
						// Kidzou_Utils::log(array('args'=>$args),true);
					}
				}		
				
			}
		
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );
			
			wp_enqueue_script('react',			"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js",	array(), '0.14.7', true);
			wp_enqueue_script('react-dom',		"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js",	array('react'), '0.14.7', true);
			wp_enqueue_script('google-maps', 	"https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);
			
			wp_enqueue_script('react-geosuggest', 		plugins_url( 'assets/js/lib/react-geosuggest.min.js', dirname(__FILE__) ), array('react','google-maps'), '1.0', true);
			wp_enqueue_script('react-inline-edit', 		plugins_url( 'assets/js/lib/react-inline-edit.js', dirname(__FILE__) ), array('react'), '1.0', true);
			wp_enqueue_style( 'react-geosuggest', 		plugins_url( 'assets/css/lib/geosuggest.css', dirname(__FILE__) )  );

			wp_enqueue_script('kidzou-react', 			plugins_url( 'assets/js/kidzou-react.js', dirname(__FILE__) ) ,array('react-dom'), Kidzou::VERSION, true);			
			wp_enqueue_script('kidzou-place-metabox', 	plugins_url( 'assets/js/kidzou-place-metabox.js', dirname(__FILE__) ) ,array('react-geosuggest','kidzou-react', 'react-inline-edit'), Kidzou::VERSION, true);
			wp_localize_script('kidzou-place-metabox', 'place_jsvars', $args);

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
		echo '<input type="hidden" name="placemeta_noncename" id="placemeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		echo '<div class="react-content"></div>';
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
