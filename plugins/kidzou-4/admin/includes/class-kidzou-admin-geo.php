<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Admin_Geo', 'get_instance' ) );

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
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Admin_Geo {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 *
	 * @var      array
	 */
	protected static $coords = array();


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );

		//nettoyage des transients de geoloc lorsque la taxo "ville bouge"
		//merci https://www.dougv.com/2014/06/25/hooking-wordpress-taxonomy-changes-with-the-plugins-api/
		add_action('create_ville', 	array( $this, 'rebuild_geo_rules') );
		add_action('edit_ville', 	array( $this, 'rebuild_geo_rules') );
		add_action('delete_ville', 	array( $this, 'rebuild_geo_rules') );

		//Plugin Geo Data Store
		if (class_exists('sc_GeoDataStore')) {

			//hook sur le plugin pour intégration spécifique
			remove_action( 'added_post_meta', array( 'sc_GeoDataStore', 'after_post_meta' ), 10 );
			remove_action( 'updated_post_meta', array( 'sc_GeoDataStore', 'after_post_meta' ), 10);
            remove_action( 'updated_postmeta', array( 'sc_GeoDataStore', 'after_post_meta' ), 10);

            add_action( 'added_post_meta', array( $this, 'after_post_meta' ), 10, 4 );
			add_action( 'updated_post_meta', array( $this, 'after_post_meta' ), 10, 4);
            add_action( 'updated_postmeta', array( $this, 'after_post_meta' ), 10, 4);

			//intégration 'standard' du plugin
			add_filter( 'sc_geodatastore_meta_keys', array( $this, 'store_geo_data') );

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

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function enqueue_geo_scripts()
	{
		// $locator = self::$locator;
		wp_enqueue_script('kidzou-admin-geo', plugins_url( '../assets/js/kidzou-admin-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

		$villes = Kidzou_GeoHelper::get_metropoles();

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

	/**
	 * Décleanchée a la demande, cette fonction synchronise les meta lat/lng de Kidzou avec le Geo Data Store
	 *
	 * @since proximite
	 * @see  sc_GeoDataStore
	 * @author 
	 **/
	public function sync_geo_data()
	{

		Kidzou_Utils::log('Kidzou_Admin_Geo [sync_geo_data]', true);
		global $wpdb;

		$post_types_list = implode('\',\'', Kidzou_GeoHelper::get_supported_post_types() );

		//ajouter des quotes autour des valeurs

		$result = $wpdb->get_results ( "
		    SELECT ID
		    FROM  $wpdb->posts
		        WHERE $wpdb->posts.post_status = 'publish'
		        AND $wpdb->posts.post_type in ('$post_types_list')
		" );

		foreach ( $result as $row )
		{
			$id = $row->ID;
		   if ( Kidzou_GeoHelper::has_post_location($id) && Kidzou_Events::isEventActive() )
		   {	
		   		$post = get_post($id); 
	   			$type = $post->post_type;
		   		$location = Kidzou_GeoHelper::get_post_location($id);
		   		$meta_key = 'kz_'.$type.'_location_latitude';

		   		$mid = $wpdb->get_var( 
		   			"SELECT meta_id FROM $wpdb->postmeta WHERE post_id = $id AND meta_key = '$meta_key'"
		   		);
		   		
		   		//@see http://stackoverflow.com/questions/20686211/how-should-i-use-setlocale-setting-lc-numeric-only-works-sometimes
				setlocale(LC_NUMERIC, 'C');

				$lat = $location['location_latitude'];
				$lng = $location['location_longitude'];

				//s'assurer que les données arrivent au bon format, i.e. xx.xx 
				//et non pas au format xx,xx ( ce qui arrive ne prod ??)
				if (is_string($lat))
					$lat = str_replace(",",".",$lat);
				if (is_string($lng))
					$lng = str_replace(",",".",$lng);

				Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] number_format(number) ' . $lat.'/' . $lng, true);

				$lat = floatval($lat);
				$lng = floatval($lng);

		   		sc_GeoDataStore::after_post_meta( 
		   			$mid, //hack : nécessaire de mettre un meta_id pour les opé de delete/update, donc on met celui de la lat
		   			$id, 
		   			Kidzou_GeoHelper::META_COORDS, 
		   			$lat.','.$lng
		   		);
		   		
		   		Kidzou_Utils::log('sync_geo_data - Synchronized Post['.$id.']['.$mid.'] / ' . $lat.','.$lng );
 
		   }
		}
	}

	/**
	 * le plugin Geo Data Store permet de stocker les coordonnées lat/lng dans une table dédiée
	 * (les coordonnées sont stockés en tant que meta par Kidzou, ce qui ne rend pas optimale les requetes de distance, type : "trouve moi les lieux les plus proches")
	 *
	 * @since proximite
	 * @see  sc_GeoDataStore
	 * @author 
	 **/
	public function store_geo_data($keys)
	{
		global $post;

		$keys[] = Kidzou_GeoHelper::META_COORDS;
    	return $keys;
	}

	/**
	 * Hooked de la classe sc_GeoDataStore
	 * car cette classe est censée recevoir les coordonnées au format lat,lng
	 *
	 * @since proximite
	 * @see sc_GeoDataStore
	*/
	public static function after_post_meta( $meta_id, $post_id, $meta_key, $meta_value )
    {

    	if (is_array($meta_value))
    		$meta_value = implode(',', $meta_value);

    	Kidzou_Utils::log('after_post_meta ' . $meta_id.', '.$post_id.', '. $meta_key. ', '. $meta_value );

    	$post = get_post($post_id); 

	   	$type = $post->post_type;

	   	$lat_meta = 'kz_'.$type.'_location_latitude';
	   	$lng_meta = 'kz_'.$type.'_location_longitude';

    	switch ($meta_key) {
    		case $lat_meta:
    			self::$coords['latitude'] = $meta_value;
    			self::$coords['meta_id'] = $meta_id;
    			break;
  			case $lng_meta:
    			self::$coords['longitude'] = $meta_value;
    			break;
    		default:
    			break;
    	}

    	//quand tout est pret
    	//on synchronise les meta de geoloc avec geo data store
    	if (isset(self::$coords['latitude']) && isset(self::$coords['longitude']))
    	{
    		//le type est supporté ?
	    	//sinon on ne synchronise pas...
	    	$should_sync = in_array($type, Kidzou_GeoHelper::get_supported_post_types());

	    	//on continue les checks
	    	//vérification que l'événement est actif s'il s'agit d'un event
	    	if ($should_sync) {
	    		
	    		$should_sync = ( Kidzou_Events::isTypeEvent($post_id) ? Kidzou_Events::isEventActive($post_id) : true );

	    		//le post est-il public ?
		    	if ($should_sync) {
		    		
		    		$should_sync = (get_post_status ( $post_id ) == 'publish');

		    		//le post est-il géolocalisé
			    	if ($should_sync) {
			    		
			    		$should_sync = Kidzou_GeoHelper::has_post_location($post_id);
			    		
			    		if ($should_sync) {
			    			//plus rien à checker
			    		} else {
			    			Kidzou_Utils::log("Pas de synchro avec le geo datastore - Post non géolocalisé");
			    		}

			    	} else {
			    		Kidzou_Utils::log("Pas de synchro avec le geo datastore - Post non publié");
			    	}
		    	
		    	} else {
		    		Kidzou_Utils::log("Pas de synchro avec le geo datastore - Event non actif");
		    	}

	    	} else {
	    		Kidzou_Utils::log("Pas de synchro avec le geo datastore - Post Type non supporté");
	    	}


	    	if ( $should_sync ) {

	    		//@see http://stackoverflow.com/questions/20686211/how-should-i-use-setlocale-setting-lc-numeric-only-works-sometimes
				setlocale(LC_NUMERIC, 'C');

				$lat = self::$coords['latitude'];
				$lng = self::$coords['longitude'];

				//s'assurer que les données arrivent au bon format, i.e. xx.xx 
				//et non pas au format xx,xx ( ce qui arrive ne prod ??)
				if (is_string($lat))
					$lat = str_replace(",",".",$lat);
				if (is_string($lng))
					$lng = str_replace(",",".",$lng);

				$lat = floatval($lat);
				$lng = floatval($lng);

	    		//checker que les valeurs lat/lng sont non nulles
				Kidzou_Utils::log('Storing in Geo Data Store : ' . self::$coords['latitude'].','. self::$coords['longitude']);
	    		sc_GeoDataStore::after_post_meta( 
					self::$coords['meta_id'], 
					$post_id, 
					Kidzou_GeoHelper::META_COORDS, 
					$lat.','. $lng
				);
	    	} 
    	}

	}


	/**
	 * déclenchée à l'actication de la geoloc
	 * Mise à jour de la structure des permaliens Category et Tag
	 *
	 * Mise à jour du .htaccess avec les règles de geoloc
	 *
	 * @return void
	 * @author 
	 **/
	public static function set_permalink_rules () {
		
		global $wp_rewrite;

		$wp_rewrite->set_category_base( Kidzou_GeoHelper::REWRITE_TAG . '/rubrique/');
		$wp_rewrite->set_tag_base( Kidzou_GeoHelper::REWRITE_TAG . '/tag/');

		flush_rewrite_rules();
		
	}

	/**
	 * déclenchée à la desactivation de la geoloc
	 * Mise à jour de la structure des permaliens Category et Tag
	 *
	 * Mise à jour du .htaccess avec les règles de geoloc
	 *
	 * @return void
	 * @author 
	 **/
	public static function unset_permalink_rules () {
		
		global $wp_rewrite;

		$wp_rewrite->set_category_base('rubrique/');
		$wp_rewrite->set_tag_base('tag/');

		flush_rewrite_rules();
		
	}

	/**
	 * permet de reconstruire les regles de ré-ecriture de permaliens et de nettoyer les caches des metropoles
	 *
	 * @return void
	 * @author 
	 **/
	public static function rebuild_geo_rules()
	{

		//nettoyager les transients
		delete_transient('kz_covered_metropoles_all_fields');
		delete_transient('kz_metropole_uri_regexp');

		//si la geoloc est active uniquement
		if ((bool)Kidzou_Utils::get_option('geo_activate',false)) 
		{
			self::set_permalink_rules();
		}
		else
		{
			self::unset_permalink_rules();
		}

        flush_rewrite_rules();
		Kidzou_Utils::log('Rewrite rules rafraichies et transients de geoloc nettoyes');

	}

}
