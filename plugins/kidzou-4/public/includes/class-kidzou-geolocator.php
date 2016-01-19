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
 * Dédiée au Front-End pour la Geolocalisation , cette classe permet de paeser la requete pour déterminer la Métropole concernée, ainsi que les coordonnées GPS en provenance du cookie
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
	 * déstinée à etre un Booleén, la valeur initiale est une chaine vide pour marquer que la valeur n'est pas initialisée
	 * 
	 */
	protected $is_request_filter = '';

	// protected $object_id = 0 ;


	public function __construct() { 
		// $this->object_id = rand() ;
		// $this->init();
			
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private function init()
	{

		// if (!Kidzou_Utils::is_really_admin())
		// {		
			// Kidzou_Utils::log('Kidzou_Geolocator::init() . '.$this->object_id);
			//la metropole choisie par le user
			// $this->set_request_metropole();

			//doit on filtrer les queries par metropole ?
			// $this->set_request_filter();

			//la partie lat/lng
			// $this->set_request_position();

		// }

	}

	/**
	 * Recuperation de la metropole passée en requete ou en Cookie
	 * 
	 *
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

		//en dépit du cookie, la valeur de la metropole passée en requete prime
		if (preg_match('#\/'.$regexp.'\/?#', $uri, $matches)) {

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

	    // Kidzou_Utils::log('set_request_metropole : '. $this->request_metropole,true);
	    // $path = substr(Kidzou_Utils::get_request_path(), 0, 20) ;
		// Kidzou_Utils::log('Kidzou_Geolocator -> set_request_metropole [' . $path  .'] -> '. $this->request_metropole, true);
	}

	/**
	 * positionnement du booléean qui indique si la requete doit etre filtrée par metropole
	 * i.e. est-ce que les contenus de la requetes sont filtrés ou non par métropole de rattachement des posts
	 *
	 * Cas particulier : si la métropole passée dans la requete HTTP correspond à la métropole à portée nationale, on positionne le booléen à FALS de sorte que les contenus ne sont pas filtrés
	 *
	 * @since proximite 
	 **/
	private function set_request_filter()
	{
		//mise à jour du param de filtrage de requete 
		$bypass_param = Kidzou_Utils::get_option('geo_bypass_param', 'region');
		$is_bypass_param = isset($_GET[$bypass_param]);

		//possibilité de bypasser les filtrages de contenus pour des URL qui matchent certaines Regexp
		$bypass_url = Kidzou_Utils::get_option('geo_bypass_regexp', '\/api\/');
		$is_bypass_url = preg_match( '#'.  $bypass_url .'#', $_SERVER['REQUEST_URI'] );

		//Cas particulier de la métropole à portée nationale 
		$is_national = ($this->request_metropole == Kidzou_GeoHelper::get_national_metropole());

		if ( Kidzou_Utils::is_really_admin() || 
			$is_bypass_url || 
			$is_bypass_param  ||
			$is_national ) {

			$this->is_request_filter = false;

			// Kidzou_Utils::log($_SERVER['REQUEST_URI'] . ' set is_request_filter to false', true);

		} else {

			$filter_active = (bool)Kidzou_Utils::get_option('geo_activate',false);
			
			if (!$filter_active) {

				// Kidzou_Utils::log('		Filtrage desactive dans les options', true);
			
				$this->is_request_filter = false;
			
			} else {

				//si la geoloc est active mais qu'aucune metropole n'est détectée en requete
				//on renvoie la chaine '' pour pouvoir ré-ecrire l'URL en supprimant les %kz_metropole%
				if ($this->get_request_metropole()=='' ) {

					$this->is_request_filter = false;

				} else {
					
					$this->is_request_filter = true;
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
	 *
	 * @return String (slug)
	 **/
	public function get_request_metropole()
	{
		// Kidzou_Utils::log('Kidzou_Geolocator [get_request_metropole] '. $this->request_metropole, true);

		if ($this->request_metropole=='' && !Kidzou_Utils::is_really_admin())
			$this->set_request_metropole();

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
	 * si une metropole est transmise en requete pour filtrage
	 *
	 */
	public function is_request_metro_filter()
	{
		if ($this->is_request_filter=='' && !Kidzou_Utils::is_really_admin())
			$this->set_request_filter();

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
			$post_types = Kidzou_GeoHelper::get_supported_post_types();

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