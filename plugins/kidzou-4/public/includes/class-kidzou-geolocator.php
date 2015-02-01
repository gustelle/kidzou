<?php

/**
 * Kidzou
 *
 * @package   Kidzou_Geolocator
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Cette classe est dédiée au Front-End pour la Geolocalisation 
 * elle permet de traiter la requete à traiter pour déterminer la Métropole de rattachement de la requete
 * ainsi que les coordonnées GPS envoyées par le navigateur
 *
 * @package Kidzou_Geolocator
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Geolocator {

	const COOKIE_METRO = 'kz_metropole';

	const COOKIE_COORDS = 'kz_coords';

	protected $request_metropole = '';

	protected $request_coords = array();	

	protected $is_request_geolocalized = false;


	/**
	 * par defaut toutes les requetes sont filtrables par geoloc 
	 * (i.e. toutes les requetes qui vont chercher du contenu en base sont filtrées par geoloc)
	 * 
	 */
	protected $is_request_filter = true;


	public function __construct() { 

		$this->init();
			
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function init()
	{

		if (!Kidzou_Utils::is_really_admin())
		{
			//la metropole choisie par le user
			$this->set_request_metropole();

			//doit on filtrer les queries par metropole ?
			$this->set_request_filter();

			//la partie lat/lng
			$this->set_request_position();
		}

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function set_request_metropole()
	{
		//d'abord on prend la ville dans l'URI
		$uri = $_SERVER['REQUEST_URI'];

		$regexp = Kidzou_GeoHelper::get_metropole_uri_regexp();

		$cook_m = '';

		//la metropole en provenance du cookie
		if ( isset($_COOKIE[self::COOKIE_METRO]) )
			$cook_m = strtolower($_COOKIE[self::COOKIE_METRO]);

		// Kidzou_Utils::log('[set_request_metropole] _COOKIE : ' . $cook_m);

		//en dépit du cookie, la valeur de la metropole passée en requete prime
		if (preg_match('#\/'.$regexp.'(/)?#', $uri, $matches)) {

			// Kidzou_Utils::log('[get_request_metropole] Regexp identifiée ');
			
			$ret = rtrim($matches[0], '/'); //suppression du slash à la fin
			$metropole = ltrim($ret, '/'); //suppression du slash au début

			// Kidzou_Utils::log('[set_request_metropole] Regexp : '. $metropole);

			//avant de renvoyer la valeur, il faut repositionner le cookie s'il n'était pas en cohérence
			//la valeur de metropole passée en requete devient la metropole du cookie
			if ($cook_m!=$metropole && $metropole!='') {

				setcookie(self::COOKIE_METRO, $metropole);

				$this->request_metropole = $metropole;

				//positionner cette variable pour ne pas aller plus loin
				$cook_m = $this->request_metropole;

			}	

		}

		//si l'URI ne contient pas la ville, on prend celle du cookie, sinon celle en parametre de requete
		if ($cook_m=='' && isset($_GET[self::COOKIE_METRO]))  {
			$cook_m = strtolower($_GET[self::COOKIE_METRO]);
			// Kidzou_Utils::log('[get_request_metropole] kz_metropole : '. $cook_m);
		} 

		//si rien ne match, on prend la ville par défaut
		if ($cook_m=='')  {
			$cook_m = Kidzou_GeoHelper::get_default_metropole();
			// Kidzou_Utils::log('[get_request_metropole] ville par défaut : '. $cook_m);
		} 

	    $isCovered = false;

	    if ($cook_m!='') 
	    	$isCovered = Kidzou_GeoHelper::is_metropole($cook_m);

	    if ($isCovered) 
	    	$this->request_metropole = $cook_m;
	    else
	    	$this->request_metropole = ''; //on désactive meme la geoloc en laissant la metropole à ''

	    $path = substr(Kidzou_Utils::get_request_path(), 0, 20) ;
		Kidzou_Utils::log('Kidzou_Geolocator -> set_request_metropole [' . $path  .'] -> '. $this->request_metropole, true);
	}

	/**
	 * positionnement du booléean qui indique si la requete doit etre filtrée par metropole
	 * i.e. est-ce que les contenus de la requetes sont filtrés ou non par métropole de rattachement des posts
	 *
	 * @return void
	 * @since proximite 
	 **/
	private function set_request_filter()
	{
		//mise à jour du param de filtrage de requete 
		if ( Kidzou_Utils::is_really_admin() || Kidzou_Utils::is_api() ) {

			Kidzou_Utils::log( '		Filtrage desactive pour admin / api ', true);

			$this->is_request_filter = false;

		} else {

			$filter_active = (bool)Kidzou_Utils::get_option('geo_activate',false);
			
			if (!$filter_active) {

				Kidzou_Utils::log('		Filtrage desactive dans les options', true);
			
				$this->is_request_filter = false;
			
			} else {

				//si la geoloc est active mais qu'aucune metropole n'est détectée en requete
				//on renvoie la chaine '' pour pouvoir ré-ecrire l'URL en supprimant les %kz_metropole%
				if ($this->get_request_metropole()=='' ) {

					Kidzou_Utils::log( '		Filtrage desactive / pas de metropole', true);
					
					$this->is_request_filter = false;
				}

			}
		}
			
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
	 * la metropole de rattachement de la requete
	 * si aucune metropole ne sort de la requete, et si aucun cookie n'est détecté, la chaine $no_filter est retournée
	 *
	 * @return String (slug)
	 * @author 
	 **/
	public function get_request_metropole()
	{
		Kidzou_Utils::log('Kidzou_Geolocator [get_request_metropole] '. $this->request_metropole, true);

		return $this->request_metropole;
	}

	/**
	 * les coordonnées lat/lng de la requete, en fonction du cookie transmis
	 *
	 * @return Array
	 * @author 
	 **/
	public function get_request_coords()
	{
		$coords = $this->request_coords;

		Kidzou_Utils::log('Kidzou_Geolocator [get_request_coords] '. $coords['latitude'].'/'. $coords['longitude'], true);

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
		return $this->is_request_geolocalized;
	}

	/**
	 * si une metropole est transmise en requete pour filtrage
	 *
	 */
	public function is_request_metro_filter()
	{
		return $this->is_request_filter;
	}




	/**
	 * intégration avec le plugin Contextual Relatif Posts
	 *
	 */ 
	public function get_related_posts() {

		if (!function_exists('get_crp_posts_id'))
			return;

		add_filter('crp_posts_join', array($this, 'crp_filter_metropole')) ;

		return get_crp_posts_id();

	}

	/**
	 * Filtrage des Contextual Related Posts par Metropole   
	 *
	 * @see Contextual Related Posts
	 * @return void
	 * @author 
	 **/
	public function crp_filter_metropole()
	{
		$join = ''; 

		$metropole = Kidzou_GeoHelper::get_post_metropole(); //object

		if ($metropole!=null) {
			$join .= "
			INNER JOIN wp_term_taxonomy AS tt ON (tt.term_id=".$metropole->term_id." AND tt.taxonomy='ville')
			INNER JOIN wp_term_relationships AS tr ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tr.object_id=ID) ";
		}

		return $join;
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
	 * NB : la précision sur lat/lng est limitée à 3 décimales 
	 * et le rayon de recherche  est un int
	 *
	 * @param radius (int)
	 * @param search_lat (float) 
	 * @param search_lng (float) 
	 * @author 
	 **/
	public function getPostsNearToMeInRadius($search_lat = 51.499882, $search_lng = -0.126178, $radius=5)
	{

		//s'assurer que les données sont au bon format, i.e. xx.xx 
		//et non pas au format xx,xx ( peut arriver pour une raison de locale ??)
		$search_lat = number_format($search_lat, 3, '.', '');
		$search_lng = number_format($search_lng, 3, '.', '');

		Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] Avant conversion ' . $search_lat.'/' . $search_lng . ' (' . $radius. ')');

		setlocale(LC_NUMERIC, 'en_US');
		$search_lat = floatval($search_lat);
		$search_lng = floatval($search_lng);
		$radius 	= intval($radius);

		Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] Apres conversion ' . $search_lat.'/' . $search_lng . ' (' . $radius. ')');

		// $post_type = 'post';
		$tablename = "geodatastore";
		$orderby = "ASC";
		$post_types_list = implode('\',\'', Kidzou_GeoHelper::get_supported_post_types());

		global $wpdb;// Dont forget to include wordpress DB class
			
		// Calculate square radius search
		$lat1 = (float) $search_lat - ( (int) $radius / 69 );
		$lat2 = (float) $search_lat + ( (int) $radius / 69 );
		$lng1 = (float) $search_lng - (int) $radius / abs( cos( deg2rad( (float) $search_lat ) ) * 69 );
		$lng2 = (float) $search_lng + (int) $radius / abs( cos( deg2rad( (float) $search_lat ) ) * 69 );

		Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] Apres operation (lat1) ' . $lat1);

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

		//bug de conversion, je n'arrive pas à formatter correctement le float
		//je force une reconversion en string pour assurer que lat/lng sont bient
		//formattés avec des "points" et non des "virgules"
		$search_lat = number_format($search_lat, 3, '.', '');
		$search_lng = number_format($search_lng, 3, '.', '');

		Kidzou_Utils::log('Kidzou_Geolocator [getPostsNearToMeInRadius] Avant Requete ' . $search_lat.'/' . $search_lng . ' (' . $radius. ')');
		
		// Create sql for circle radius check
		$sqlcircleradius = "
		SELECT
			`t`.`post_id`,
			3956 * 2 * ASIN(
				SQRT(
					POWER(
						SIN(
							( ". $search_lat." - `t`.`lat` ) * pi() / 180 / 2
						), 2
					) + COS(
						". $search_lat." * pi() / 180
					) * COS(
						`t`.`lat` * pi() / 180
					) * POWER(
						SIN(
							( ". $search_lng." - `t`.`lng` ) * pi() / 180 / 2
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

		// Kidzou_Utils::log($wpdb->last_query);

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


} //fin de classe

?>