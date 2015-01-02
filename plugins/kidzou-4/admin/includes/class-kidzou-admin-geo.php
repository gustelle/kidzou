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
	 *
	 * @var      string
	 */
	protected static $meta_coords = 'kz_coords';


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

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
	 * Décleanchée a la demande, cette fonction synchronise les meta lat/lng de Kidzou avec le Geo Data Store
	 *
	 * @since proximite
	 * @see  sc_GeoDataStore
	 * @author 
	 **/
	public function sync_geo_data()
	{

		global $wpdb;

		$result = $wpdb->get_results ( "
		    SELECT ID
		    FROM  $wpdb->posts
		        WHERE $wpdb->posts.post_status = 'publish'
		        AND $wpdb->posts.post_type in ('post', 'offres')
		" );

		foreach ( $result as $row )
		{
			$id = $row->ID;
		   if ( Kidzou_Geo::has_post_location($id) && Kidzou_Events::isEventActive() )
		   {	
		   		$post = get_post($id); 
	   			$type = $post->post_type;
		   		$location = Kidzou_Geo::get_post_location($id);
		   		$meta_key = 'kz_'.$type.'_location_latitude';

		   		$mid = $wpdb->get_var( 
		   			"SELECT meta_id FROM $wpdb->postmeta WHERE post_id = $id AND meta_key = '$meta_key'"
		   		);
		   		// Kidzou_Utils::log($wpdb->last_query);
		   		sc_GeoDataStore::after_post_meta( 
		   			$mid, //hack : nécessaire de mettre un meta_id pour les opé de delete/update, donc on met celui de la lat
		   			$id, 
		   			self::$meta_coords, 
		   			$location['location_latitude'].','.$location['location_longitude'] 
		   		);

		   		Kidzou_Utils::log('sync_geo_data - Synchronized Post['.$id.']['.$mid.'] / ' . $location['location_latitude'].','.$location['location_longitude'] );
 
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

		$keys[] = self::$meta_coords;
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

    	if ( isset(self::$coords['latitude']) && isset(self::$coords['longitude']) ) {

    		//checker que les valeurs lat/lng sont non nulles
    		if (Kidzou_Geo::has_post_location($post_id))
    		{
    			Kidzou_Utils::log('Storing in Geo Data Store : ' . self::$coords['latitude'].','. self::$coords['longitude']);
	    		sc_GeoDataStore::after_post_meta( 
					self::$coords['meta_id'], 
					$post_id, 
					self::$meta_coords, 
					self::$coords['latitude'].','. self::$coords['longitude']
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

		$wp_rewrite->set_category_base( Kidzou_Geo::$rewrite_tag . '/rubrique/');
		$wp_rewrite->set_tag_base( Kidzou_Geo::$rewrite_tag . '/tag/');

		Kidzou_Geo::create_rewrite_rules();
		
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
