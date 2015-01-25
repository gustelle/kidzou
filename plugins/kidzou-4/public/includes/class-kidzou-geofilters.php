<?php

add_action('kidzou_loaded', array('Kidzou_GeoFilters', 'get_instance'));

/**
 * Kidzou
 *
 * @package   Kidzou_GeoFilters
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Cette classe est dédiée au Front-End pour la Geolocalisation 
 * elle permet de filtrer les contenus de Wordpress en fonction de la geolocalisation ou de la Métropole
 * de rattachement
 *
 * Cette classe ne doit pas être utilisée directement par les développeurs, elle fonctionne de façon completement
 * autonome et transparente 
 *
 * elle s'instancie seule et reste statique
 *
 * @package Kidzou_GeoFilters
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_GeoFilters {

	/**
	 * Instance of this class.
	 *
	 *
	 * @var      object Kidzou_GeoFilters
	 */
	protected static $instance = null;


	/**
	 * Instance of Kidzou_Geolocator.
	 *
	 *
	 * @var      object Kidzou_Geolocator
	 */
	protected static $locator;

	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		if (!Kidzou_Utils::is_really_admin())
		{
			self::$locator = new Kidzou_Geolocator();

			//ce hook est sensible
			//mieux vaut qu'il reste en dehors de toute affaire et qu'il ait son propre if ()
			add_action( 'init', array( $this, 'create_rewrite_rules' ),90 );

			//Le filtrage n'est pas actif pour certaines requetes, typiquement les API
			add_filter( 'post_link', array( $this, 'rewrite_post_link' ) , 10, 2 );
			add_filter( 'page_link', array( $this, 'rewrite_page_link' ) , 10, 2 );
			add_filter( 'term_link', array( $this, 'rewrite_term_link' ), 10, 3 );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );

			add_action( 'pre_get_posts', array( $this, 'geo_filter_query'), 999 );
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
		$locator = self::$locator;
		wp_enqueue_script('kidzou-geo', plugins_url( '../assets/js/kidzou-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

		$villes = Kidzou_GeoHelper::get_metropoles();

		$key = Kidzou_Utils::get_option("geo_mapquest_key",'Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6');
  
		$args = array(
					'geo_activate'				=> (bool)Kidzou_Utils::get_option('geo_activate',false), //par defaut non
					'geo_mapquest_key'			=> $key, 
					'geo_mapquest_reverse_url'	=> "http://open.mapquestapi.com/geocoding/v1/reverse",
					'geo_mapquest_address_url'	=> "http://open.mapquestapi.com/geocoding/v1/address",
					'geo_cookie_name'			=> $locator::COOKIE_METRO,
					'geo_possible_metropoles'	=> $villes ,
					'geo_coords'				=> $locator::COOKIE_COORDS,
				);

	    wp_localize_script(  'kidzou-geo', 'kidzou_geo_jsvars', $args );
		
	}

	/**
	 * Rewrites incluant les metropoles
	 *
	 */
	public function create_rewrite_rules() {

		if ((bool)Kidzou_Utils::get_option('geo_activate',false)) 
		{
			global $wp_rewrite; 

			$regexp = Kidzou_GeoHelper::get_metropole_uri_regexp();
			add_rewrite_tag( Kidzou_GeoHelper::REWRITE_TAG ,$regexp, 'kz_metropole=');

			//see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
		    add_rewrite_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home
		    add_rewrite_rule($regexp.'/offres/page/?([0-9]{1,})/?','index.php?post_type=offres&paged=$matches[2]&kz_metropole=$matches[1]','top');
		    add_rewrite_rule($regexp.'/offres/?','index.php?post_type=offres&kz_metropole=$matches[1]','top');
		   	add_rewrite_rule($regexp.'/(.*)$/?','index.php?pagename=$matches[2]&kz_metropole=$matches[1]','top');
			add_rewrite_rule($regexp.'/(.*)/page/?([0-9]{1,})/?$','index.php?pagename=$matches[2]&paged=$matches[3]&kz_metropole=$matches[1]','top');

			//si la ville n'est pas spécifiée en requete, car le user est arrivé directement sur un post (donc pas préfixé par une ville)
			//et navigue ensuite vers une rubrique ou autre:
			add_rewrite_rule('/?rubrique/(.*)/?','index.php?category_name=$matches[1]','top');

		}
		
	    
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function get_locator()
	{
		return self::$locator;
	}

	/**
	 * Recupérer les args de Geoquery
	 *
	 * @return Array
	 * @author 
	 **/
	private function get_metropole_args(  ) {

		$locator = self::$locator;

		if ( $locator->is_request_metro_filter() )
		{
			$the_metropole = array();
	  		$the_metropole[] = $locator->get_request_metropole();

	        $national = (array)Kidzou_GeoHelper::get_national_metropoles(); 
	       	$merge = array_merge( $the_metropole, $national );

	       	return array(
	                  'taxonomy' => 'ville',
	                  'field' => 'slug',
	                  'terms' => $merge
	                );

		}

		return array();
		
	}

	/**
	 * Les Query en Base sont filtrées en tenant compte de la métropole courante
	 * Celle-ci est soit la metropole passée dans la requete (en provenance du cookie utilisateur), soit la metropole par défaut
	 * les contenus à portée "nationale" sont également remontés
	 *
	 * @version proximite    
	 */
	public function geo_filter_query( $query ) {

		Kidzou_Utils::log('Kidzou_GeoFilters [geo_filter_query]' , true);

		$locator = self::$locator;

		if ( $locator->is_request_metro_filter() )
		{

			$post_type = $query->get('post_type');

			//le post type est il suporté par le filtre ?
			if (is_array($post_type))
			{
				foreach ($post_type as $key => $value) {
					if (in_array($value, Kidzou_GeoHelper::get_supported_post_types() ))
					{
						$supported_query = true;
						break;
					}
				}
			}
			else
				$supported_query = in_array($post_type, Kidzou_GeoHelper::get_supported_post_types() ) ;

			//cas spécial des archives : le post type n'est pas spécifié
			//on ouvre au maximim les post types
			if (is_archive() && $query->is_main_query())
			{
				$query->set('post_type', Kidzou_GeoHelper::get_supported_post_types() );
				$supported_query = true;
			}

		    if( !is_admin() && !is_search() && $supported_query ) {

				//reprise des arguments qui auraient pu être passés précédemment par d'autres requetes
		        //d'ou l'importance d'executer celle-ci en dernier
		        $vars = $query->get('tax_query');

		        $ville_tax_present = false;

		        if (isset($vars['taxonomy']) && $vars['taxonomy']=='ville')
		        	$ville_tax_present = true;

		        else if (is_array($vars)) {
		        	foreach ($vars as $key => $value) {
			        	
		        		if (is_array($value)) {
		        			foreach ($value as $k => $v) {
			        			if ($k == 'taxonomy' && $v=='ville') {
			        				$ville_tax_present = true;
			        				// echo 'found';
			        			}
			        				
			        		}

		        		}
			        		
			        }

		        }

	        	if (!$ville_tax_present)
	        	{
	        		$vars[] = $this->get_metropole_args( );
	        	}
	            //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
	            $query->set('tax_query', $vars);

		        return $query;
		    } 
		}

	    return $query;
	}

	/**
	 * Réecriture des URL des posts s'il le faut
	 */
	public  function rewrite_post_link( $permalink, $post ) {

		$locator = self::$locator;

		if ($locator->is_request_metro_filter() )
		{
			$m = urlencode($locator->get_request_metropole());

		    // Check if the %kz_metropole% tag is present in the url:
		    if ( true === strpos( $permalink, Kidzou_GeoHelper::REWRITE_TAG ) ) {

			    // Replace '%kz_metropole%'
			    $permalink = str_replace( Kidzou_GeoHelper::REWRITE_TAG, $m , $permalink );

		    } 
			    
		}
		 
	    return $permalink;
	}

	/**
	 * Reecriture des pages qui utilisent le tempate 'tous les contenus'
	 * car ces pages sont geolocalisées, c'est à dire que "tous" les contenus sont en fait
	 * filtres par la metropole de rattachement du user
	 *
	 */
	public function rewrite_page_link( $link, $page ) {

		// $locator = self::$locator;

		// if ($locator->is_request_metro_filter())
		// {
		// 	$m = urlencode($locator->get_request_metropole());

		// 	$rewrite = Kidzou_GeoHelper::is_page_rewrite($page);

		// 	$post = get_post($page);

		// 	if ($rewrite) {

		// 		Kidzou_Utils::log('Kidzou_GeoFilters : ré-ecriture pour '. $link, true);

		// 		$pos = strpos( $link, '/'. $post->post_name );
		// 		$new_link = substr_replace($link, "/".$m, $pos, 0);
		// 		return $new_link;
		// 	}
		// }

		return $link;
	    
	}

	/**
	 * Récriture des liens vers les taxonomies
	 *
	 */
	public function rewrite_term_link( $url, $term, $taxonomy ) {

		$locator = self::$locator;

		if ($locator->is_request_metro_filter())
		{

			// Check if the %kz_metropole% tag is present in the url:
		    if ( false === strpos( $url, Kidzou_GeoHelper::REWRITE_TAG ) )
		        return $url;
		 
		    $m = urlencode($locator->get_request_metropole());
		 
		    // Replace '%kz_metropole%'
		    $url = str_replace( Kidzou_GeoHelper::REWRITE_TAG, $m , $url );

		}

		//recuperer la trace complete d'appel pour les cas ou l'URL n'est pas convertie (il reste des %kz_metropole%)
		if (preg_match('/'.Kidzou_GeoHelper::REWRITE_TAG.'/', $url))
		 	Kidzou_Utils::printStackTrace();
	 
	    return $url; 
	}

}
