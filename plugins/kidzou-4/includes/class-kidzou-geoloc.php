<?php

add_action('plugins_loaded', array('Kidzou_Geoloc', 'get_instance'), 100);


/**
 * Cette classe accède aux meta de geoloc des posts (lat/lng, adresse, ville...) et fournit des services de geoloc de contenu par lat/lng
 *
 * @package Kidzou
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Geoloc {
	

	protected $request_coords = array();	

	protected $is_request_geolocalized = false;


	/**
	 *
	 * @var      string
	 */
	const META_COORDS = 'kz_coords';

	const COOKIE_COORDS = 'kz_coords';

	//utilisé en externe
	public static $meta_latitude = 'kz_post_location_latitude';
	public static $meta_longitude = 'kz_post_location_longitude';

	/**
	 * Instance of this class.
	 *
	 *
	 * @var      object Kidzou_Geo
	 */
	protected static $instance = null;

	
	/**
	 * le tableau des post types qui supportent la geolocalisation
	 * ce tableay est complété à l'init par les post types additionnels ajoutés par l'admin
	 */
	protected static $supported_post_types = array('post', 'page'); //'offres'


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		// if (!Kidzou_Utils::is_really_admin())
		// {
		// 	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );
		// }

		self::init();	
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
	private static function init()
	{
		//extension des post types supportés
		self::add_supported_post_types();
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private static function add_supported_post_types()
	{
		$more_types = Kidzou_Utils::get_option('geo_supported_post_types', array());

		foreach ($more_types as $key => $value) {
			array_push(self::$supported_post_types, $value);
		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function get_supported_post_types()
	{
		return self::$supported_post_types;
	}


	/**
	 * les infos d'emplacement géographique d'un post
	 *
	 * @return Tableau contenant les meta de Geoloc d'un post
	 * @author 
	 **/
	public static function get_post_location($post_id=0)
	{

	    if ($post_id==0)
	    {
	        global $post; 
	        $post_id = $post->ID; 
	    }

	    //necessité de récupérer le post type
	    //car les customers ont une adresse stockée sur la meta kz_customer_xxx
	    //c'est du legacy...
	    $post = get_post($post_id); 
	   	$type = $post->post_type;

	    $location_name      = get_post_meta($post_id, 'kz_'.$type.'_location_name', TRUE);
	    $location_address   = get_post_meta($post_id, 'kz_'.$type.'_location_address', TRUE);
	    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE); //'kz_'.$type.'_location_latitude'
	    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE); //'kz_'.$type.'_location_longitude'
	    $location_tel   = get_post_meta($post_id, 'kz_'.$type.'_location_phone_number', TRUE);
	    $location_web   = get_post_meta($post_id, 'kz_'.$type.'_location_website', TRUE);
	    $location_city   = get_post_meta($post_id, 'kz_'.$type.'_location_city', TRUE);

	    return array(
	        'location_name' => $location_name,
	        "location_address" => $location_address,
	        "location_latitude" => $location_latitude,
	        "location_longitude" => $location_longitude,
	        "location_phone_number" => $location_tel,
	        "location_website" => $location_web,
	        "location_city" => $location_city
	    );
	}

	/**
	 * Enregistrement de la meta 'place'. Cette méthode est indépendant de la metabox pour pouvoir être attaquée depuis des API
	 *
	 * @param $post_id int le post sur lequel on vient attacher la meta  
	 * @param $arr string les données de localisation (location_name, location_address, location_website, location_phone_number, location_city, location_latitude, location_longitude)
	 **/
	public static function set_location($post_id, $location_name, $location_address, $location_website, $location_phone_number, $location_city, $location_latitude, $location_longitude )
	{	
		if ($location_name=='' || $location_address=='' || $location_city=='')
			return new WP_Error('save_place', 'Certaines donnees sont manquantes');

		$type = get_post_type($post_id);

		$prefix = 'kz_' . $type . '_';

		$meta['location_name'] 			= $location_name;
		$meta['location_address'] 		= $location_address;
		$meta['location_website'] 		= $location_website;
		$meta['location_phone_number'] 	= $location_phone_number;
		$meta['location_city'] 			= $location_city;
		$meta['location_latitude'] 		= $location_latitude;
		$meta['location_longitude'] 	= $location_longitude;

		// Kidzou_Utils::log($meta, true);

		Kidzou_Utils::save_meta($post_id, $meta, $prefix);
		
	}


	/**
	 * le post est-il associé à un lieu ?
	 *
	 * @return Tableau contenant les meta de Geoloc d'un post
	 * @author 
	 **/
	public static function has_post_location($post_id=0)
	{

	    if ($post_id==0)
	    {
	        global $post;
	        $post_id = $post->ID;
	    }

	    $post = get_post($post_id);

	    $type = $post->post_type;

	    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE);
	    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE);

	    $return = ($location_latitude!='' && $location_longitude!='');

	    return $return;
	}	

	/**
	 * les coordonnées sont déterminées par le navigateur et renvoyées par cookie dans la requete
	 *
	 * @return void
	 * @since proximite
	 * @author 
	 **/
	private function set_request_position()
	{

		if ( isset($_COOKIE[self::COOKIE_COORDS]) ) {

			$cookie_val = json_decode(
					stripslashes($_COOKIE[self::COOKIE_COORDS]), 
					true
				);

			$this->request_coords = $cookie_val;
			$this->is_request_geolocalized = true;

		} else {

			$this->request_coords = array(
					'latitude' => Kidzou_Utils::get_option('geo_default_lat'),
					'longitude' => Kidzou_Utils::get_option('geo_default_lng')
				);
		}	

	}


	/**
	 * les coordonnées lat/lng de la requete, en fonction du cookie transmis
	 *
	 * @return Array
	 * @author 
	 **/
	public function get_request_coords()
	{	
		if (empty($this->request_coords) && !Kidzou_Utils::is_really_admin())
			$this->set_request_position();

		return $this->request_coords;
	}

	/**
	 * les coordonnées lat/lng de la requete sont-elles celles par défaut ?
	 * autrement dit le user est-il "vraiment geolocalisé " ?
	 *
	 * @return Bool
	 * @author 
	 **/
	public function is_request_geolocalized()
	{
		if (empty($this->request_coords) && !Kidzou_Utils::is_really_admin())
			$this->set_request_position();

		return $this->is_request_geolocalized;
	}

	/**
	 * Get all post id's ordered by distance from given point
	 *
	 * @param string $post_type The post type of posts you are searching
	 * @param float $search_lat The latitude of where you are searching
	 * @param float $search_lng The Longitude of where you are searching
	 * @param string $orderby What order do you want the ID's returned as? ordered by distance ASC or DESC?
	 * @return array $wpdb->get_col() array of ID's in ASC or DESC order as distance from point
	 **/
	private function getPostIDsByRange($search_lat = 51.499882, $search_lng = -0.126178, $post_type = 'post')
	{
		if (class_exists( 'sc_GeoDataStore' ))
		{
			return sc_GeoDataStore::getPostIDsByRange($post_type, $search_lat , $search_lng, "ASC");
		}

		return new WP_Error( 'missing-plugin', 'Le Plugin Geo Data Store est manquant, vous ne pouvez pas utiliser cette fonction');
	}

	/**
	 * Get all post id's of those that are in range
	 *
	 * @param string $post_type The post type of posts you are searching
	 * @param int $radius The search radius in MILES
	 * @param float $search_lat The latitude of where you are searching
	 * @param float $search_lng The Longitude of where you are searching
	 * @param string $orderby What order do you want the ID's returned as? ordered by distance ASC or DESC?
	 * @return array $wpdb->get_col() array of ID's of posts in radius. You can use this array in 'post__in' in WP_Query
	*/
	private function getPostIDsOfInRange($search_lat = 51.499882, $search_lng = -0.126178, $radius=5, $post_type = 'post')
	{
		if (class_exists( 'sc_GeoDataStore' ))
		{
			return sc_GeoDataStore::getPostIDsOfInRange($post_type, $radius * 0.621371192 , $search_lat , $search_lng , "ASC");
		}

		return new WP_Error( 'missing-plugin', 'Le Plugin Geo Data Store est manquant, vous ne pouvez pas utiliser cette fonction');
	}

	/**
	 * Ré-eacriture de Geo Data Store pour prévoir le fait que le plugin ne puisse pas être installé
	 * et convertir les miles en KM
	 * + remontée de la distance au post 
	 * + remontée des lat/lng pour exploitation dans une carte
	 *
	 * NB : la précision sur lat/lng est limitée à 6 décimales 
	 * et le rayon de recherche  est un int
	 *
	 * @param radius (int)
	 * @param search_lat (float) 
	 * @param search_lng (float) 
	 * @param post_types (array)
	 * @author 
	 **/
	public function getPostsNearToMeInRadius($search_lat = 51.499882, $search_lng = -0.126178, $radius=5, $post_types = array() )
	{

		//@see http://stackoverflow.com/questions/20686211/how-should-i-use-setlocale-setting-lc-numeric-only-works-sometimes
		setlocale(LC_NUMERIC, 'C');

		//s'assurer que les données arrivent au bon format, i.e. xx.xx 
		//et non pas au format xx,xx ( ce qui arrive ne prod ??)
		if (is_string($search_lat))
			$search_lat = str_replace(",",".",$search_lat);
		if (is_string($search_lng))
			$search_lng = str_replace(",",".",$search_lng);

		// Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] number_format(number) ' . $search_lat.'/' . $search_lng . ' (' . (int)$radius. ')', true);

		$search_lat = floatval($search_lat);
		$search_lng = floatval($search_lng);

		$tablename = "geodatastore";
		$orderby = "ASC";

		//par defaut
		if (count($post_types)==0)
			$post_types = Kidzou_Geoloc::get_supported_post_types();

		$post_types_list = implode('\',\'', $post_types);

		global $wpdb;// Dont forget to include wordpress DB class
			
		// Calculate square radius search
		$lat1 = (float) $search_lat - ( (int) $radius / 69 );
		$lat2 = (float) $search_lat + ( (int) $radius / 69 );
		$lng1 = (float) $search_lng - (int) $radius / abs( cos( deg2rad( (float) $search_lat ) ) * 69 );
		$lng2 = (float) $search_lng + (int) $radius / abs( cos( deg2rad( (float) $search_lat ) ) * 69 );


		$sqlsquareradius = "
		SELECT
			`" . $wpdb->prefix . $tablename . "`.`post_id`,
			`" . $wpdb->prefix . $tablename . "`.`lat`,
			`" . $wpdb->prefix . $tablename . "`.`lng`
		FROM
			`" . $wpdb->prefix . $tablename . "`
		WHERE
			`" . $wpdb->prefix . $tablename . "`.`post_type` IN ('{$post_types_list}')
		AND
			`" . $wpdb->prefix . $tablename . "`.`lat` BETWEEN '{$lat1}' AND '{$lat2}'
		AND
			`" . $wpdb->prefix . $tablename . "`.`lng` BETWEEN '{$lng1}' AND '{$lng2}'
		"; // End $sqlsquareradius


		// Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] Avant Requete ' . (float)$search_lat.'/' . (float)$search_lng . ' (' . (int)$radius. ')', true);
		
		// Create sql for circle radius check
		$sqlcircleradius = "
		SELECT
			`t`.`post_id`,
			3956 * 2 * ASIN(
				SQRT(
					POWER(
						SIN(
							( ". (float) $search_lat." - `t`.`lat` ) * pi() / 180 / 2
						), 2
					) + COS(
						". $search_lat." * pi() / 180
					) * COS(
						`t`.`lat` * pi() / 180
					) * POWER(
						SIN(
							( ". (float) $search_lng." - `t`.`lng` ) * pi() / 180 / 2
						), 2
					)
				)
			) / 0.621371192 AS `distance`,
			`t`.`lat` AS `latitude`,
			`t`.`lng` AS `longitude`
		FROM
			({$sqlsquareradius}) AS `t`
		HAVING
			`distance` <= ".(int) $radius."
		ORDER BY `distance` {$orderby}
		"; // End $sqlcircleradius

		$results = $wpdb->get_results($sqlcircleradius);

		// Kidzou_Utils::log($wpdb->last_query, true);

		$nonfeatured = array();

	    $featured =  Kidzou_Featured::getFeaturedPosts( );
	    
	    $featured_in_list = array();
	    $nonfeatured = array();

	    //les featured qui sont dans la liste
	    foreach ($results as $rk => $rv) {

	    	$is_featured = false;

	    	//safety check
	    	//les events obsolete sont sortis
	    	if (Kidzou_Events::isTypeEvent($rv->post_id) && !Kidzou_Events::isEventActive($rv->post_id))
	    		continue;

	    	foreach ($featured as $fk => $fv) {
	    		
	    		if ( intval($fv->ID) == intval($rv->post_id) ) {
	    			array_push($featured_in_list, $rv);
	    			$is_featured = true;
	    		}
	    	}

	    	if (!$is_featured) {
	    		array_push($nonfeatured, $rv);
	    	}
	    }
	    
	    $posts = array_merge( $featured_in_list, $nonfeatured );

	    return $posts;
	}

	/**
	 * Ré-eacriture de Geo Data Store pour prévoir le fait que le plugin ne puisse pas être installé
	 * et convertir les miles en KM
	 * + remontée de la distance au post 
	 * + remontée des lat/lng pour exploitation dans une carte
	 *
	 * NB : la précision sur lat/lng est limitée à 6 décimales 
	 * et le rayon de recherche  est un int
	 *
	 * @param radius (int)
	 * @param search_lat (float) 
	 * @param search_lng (float) 
	 * @author 
	 **/
	public function getPostDistanceInKmById($search_lat = 51.499882, $search_lng = -0.126178, $id=0)
	{

		//@see http://stackoverflow.com/questions/20686211/how-should-i-use-setlocale-setting-lc-numeric-only-works-sometimes
		setlocale(LC_NUMERIC, 'C');

		//s'assurer que les données arrivent au bon format, i.e. xx.xx 
		//et non pas au format xx,xx ( ce qui arrive ne prod ??)
		if (is_string($search_lat))
			$search_lat = str_replace(",",".",$search_lat);
		if (is_string($search_lng))
			$search_lng = str_replace(",",".",$search_lng);

		$search_lat = floatval($search_lat);
		$search_lng = floatval($search_lng);

		$tablename = "geodatastore";

		global $wpdb;// Dont forget to include wordpress DB class
			
		
		// Create sql for circle radius check
		$sqlcircleradius = "
		SELECT
			3956 * 2 * ASIN(
				SQRT(
					POWER(
						SIN(
							( ". (float) $search_lat." - `t`.`lat` ) * pi() / 180 / 2
						), 2
					) + COS(
						". $search_lat." * pi() / 180
					) * COS(
						`t`.`lat` * pi() / 180
					) * POWER(
						SIN(
							( ". (float) $search_lng." - `t`.`lng` ) * pi() / 180 / 2
						), 2
					)
				)
			) / 0.621371192 AS `distance`
		FROM
			`" . $wpdb->prefix . $tablename . "` AS `t`
		WHERE 
			`t`.`post_id`=" . $id ; // End $sqlcircleradius

		$result = $wpdb->get_var($sqlcircleradius);

	    return $result;
	}


} //fin de classe

?>