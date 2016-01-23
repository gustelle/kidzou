<?php

/** 
 * Charger cette classe relativement tard, pour être certain que les taxonomies soient disponibles (registered)
 *
 */
// add_action('kidzou_loaded', array('Kidzou_GeoFilters', 'get_instance'), 999);
add_action('plugins_loaded', array('Kidzou_GeoFilters', 'get_instance'), 100);


/**
 * Permet de filtrer les contenus en fonction de la geolocalisation du user ou de la Métropole de rattachement du post (chaque post est attaché à une metropole via la taxonomie 'ville')
 *
 * * La Geolocalisation du user est effectuée par JS 
 * * le rattachement d'un post à une métropole est fait dans l'admin 
 *
 * Cette classe ne doit pas être utilisée directement par les développeurs, elle fonctionne de façon completement
 * autonome et transparente 
 *
 * elle s'instancie seule et reste statique
 *
 * @link public/js/kidzou-geo.js 
 * @see Kidzou_Admin::save_post_metropole()
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

		//ce hook est sensible
		//mieux vaut qu'il reste en dehors de toute affaire et qu'il ait son propre if ()
		add_action( 'init', array( $this, 'create_rewrite_rules' ),90 );

		if (!Kidzou_Utils::is_really_admin())
		{
			self::$locator = new Kidzou_Geolocator();

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
	 * Injecte les Javascripts nécessaires à la Geolocalisation du user
	 *
	 * @return void
	 * @author 
	 **/
	public function enqueue_geo_scripts()
	{
		$locator = self::$locator;
		wp_enqueue_script('kidzou-geo', plugins_url( '../assets/js/kidzou-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

		$villes = Kidzou_Metropole::get_metropoles();

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
	 * Re-ecriture des requetes HTTP pour tenir compte de la metropole 
	 * 
	 * Les contenus rendus par WP sont filtrés en fonction de la metropole contenue dans la requete
	 *
	 * Par example /lille est ré-ecrit en index.php?kz_metropole=lille
	 *
	 * Par example /lille/agenda est ré-ecrit en index.php?pagename=agenda&kz_metropole=lille
	 *
	 */
	public function create_rewrite_rules() {

		if ((bool)Kidzou_Utils::get_option('geo_activate',false)) 
		{
			global $wp_rewrite; 

			$regexp = Kidzou_Metropole::get_metropole_uri_regexp();
			add_rewrite_tag( Kidzou_Metropole::REWRITE_TAG ,$regexp, 'kz_metropole=');

			//see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
		    add_rewrite_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home
		   	add_rewrite_rule($regexp.'/(.*)$/?','index.php?pagename=$matches[2]&kz_metropole=$matches[1]','top');
			add_rewrite_rule($regexp.'/(.*)/page/?([0-9]{1,})/?$','index.php?pagename=$matches[2]&paged=$matches[3]&kz_metropole=$matches[1]','top');

			//si la ville n'est pas spécifiée en requete, car le user est arrivé directement sur un post (donc pas préfixé par une ville)
			//et navigue ensuite vers une rubrique ou autre:
			add_rewrite_rule('/?rubrique/(.*)/?','index.php?category_name=$matches[1]','top');

			flush_rewrite_rules();
		}
		
	    
	}

	/**
	 * Retourne l'instance unique de cette classe
	 *
	 * @return self::$locator
	 **/
	public static function get_locator()
	{
		return self::$locator;
	}

	/**
	 * Fonction interne utilisée par la WP_Query, cette fonction récupère la metropole indiquée en requete HTTP 
	 * et retourne les args qui vont permettre de filtrer les contenus WP
	 *
	 * A noter que la metropole indiquée en requete HTTP est complétée par les métropoles "NATIONALES" 
	 *
	 * @see Kidzou_Metropole::get_national_metropole() 
	 * @see https://codex.wordpress.org/Class_Reference/WP_Query
	 * @return Array
	 * @internal 
	 **/
	private function get_metropole_args(  ) {

		$locator = self::$locator;

		if ( $locator->is_request_metro_filter() )
		{
			$the_metropole = array();
	  		$the_metropole[] = $locator->get_request_metropole();

	  		if ($locator->get_request_metropole()!=Kidzou_Metropole::get_national_metropole())
	       		array_push($the_metropole, Kidzou_Metropole::get_national_metropole());

	       	return array(
	                  'taxonomy' => 'ville',
	                  'field' => 'slug',
	                  'terms' => $the_metropole
	                );
		}

		return array();
		
	}

	/**
	 * Les WP_Query utilisées sont filtrées en tenant compte de la métropole passée en requete
	 * Celle-ci est :
	 * * soit la metropole passée dans la requete (en provenance du cookie utilisateur), `
	 * * soit la metropole par défaut
	 *
	 * les contenus à portée "nationale" sont également remontés par la WP_Query
	 * 
	 * @see https://codex.wordpress.org/Class_Reference/WP_Query Documentation de WP_Query
	 * @since 0215-fix31 : filtrage des recherches par métropole
	 */
	public function geo_filter_query( $query ) {

		$locator = self::$locator;

		// Kidzou_Utils::log(
		// 	array(	'REQUEST_URI'=>$_SERVER['REQUEST_URI'],
		// 			'request_metropole'=>$locator->get_request_metropole(),
		// 			'is_request_metro_filter' => $locator->is_request_metro_filter()), true);

		if ( $locator->is_request_metro_filter() )
		{
			$post_type = $query->get('post_type');

			//le post type est il suporté par le filtre ?
			if (is_array($post_type))
			{
				foreach ($post_type as $key => $value) {
					if (in_array($value, Kidzou_Metropole::get_supported_post_types() ))
					{
						$supported_query = true;
						break;
					}
				}
			}
			else
				$supported_query = in_array($post_type, Kidzou_Metropole::get_supported_post_types() ) ;

			//cas spécial des archives : le post type n'est pas spécifié
			//on ouvre au maximim les post types
			if (is_archive() && $query->is_main_query())
			{
				$query->set('post_type', Kidzou_Metropole::get_supported_post_types() );
				$supported_query = true;
			}

			//cas du search, le post type n'est pas spécifié mais on filtre par métropole qd même
			if ($query->is_search)
			{
				// Kidzou_Utils::log('Search queries DO support pre_get_posts');
				$supported_query = true;
			}

		    if( !is_admin() && $supported_query ) { //&& !is_search()

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
	 * Cette fonction utilise le hook post_link pour injecter la métropole "courante" du user (celle passée en requete ou celle détectée par geoloc) dans le permalink d'un post
	 * 
	 * NB : Ce hook tient compte du REWRITE_TAG %kz_metropole% indiqué dans les options WP 
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/post_link 	Documentation du Hook post_link
	 * @see https://www.kidzou.fr/wp-admin/options-permalink.php 	Réglages des permaliens dans l'admin Wordpress
	 */
	public  function rewrite_post_link( $permalink, $post ) {

		$locator = self::$locator;

		if ($locator->is_request_metro_filter() )
		{
			$m = urlencode($locator->get_request_metropole());

		    // Check if the %kz_metropole% tag is present in the url:
		    if ( true === strpos( $permalink, Kidzou_Metropole::REWRITE_TAG ) ) {

			    // Replace '%kz_metropole%'
			    $permalink = str_replace( Kidzou_Metropole::REWRITE_TAG, $m , $permalink );

		    } 
			    
		}
		 
	    return $permalink;
	}

	/**
	 * Injection de la métropole "courante" du user (celle passée en requete ou celle détectée par geoloc) dans le permalink d'une page
	 * Cette technique permet d'améliorer le SEO puisque une même page peut être référencée plusieurs fois selon la métropole
	 * Par exemple : /lille/ma-page ou /valenciennes/ma-page
	 *
	 * @see https://developer.wordpress.org/reference/hooks/page_link/	Documentation du Hook page_link
	 * @see Kidzou_Metropole::is_page_rewrite() 	Booleen qui détermine si le permalien de la page doit être ré-ecrit pour y injecter la metropole
	 */
	public function rewrite_page_link( $link, $page ) {

		$locator = self::$locator;

		if ($locator->is_request_metro_filter())
		{
			$m = urlencode($locator->get_request_metropole());

			$rewrite = Kidzou_Metropole::is_page_rewrite($page);

			$post = get_post($page);

			if ($rewrite) {

				$pos = strpos( $link, '/'. $post->post_name );
				$new_link = substr_replace($link, "/".$m, $pos, 0);
				return $new_link;
			}
		}

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
		    if ( false === strpos( $url, Kidzou_Metropole::REWRITE_TAG ) )
		        return $url;
		 
		    $m = urlencode($locator->get_request_metropole());
		 
		    // Replace '%kz_metropole%'
		    $url = str_replace( Kidzou_Metropole::REWRITE_TAG, $m , $url );

		}

		//supprimer le TAG si pas de metropole en requete
		if (preg_match('/'.Kidzou_Metropole::REWRITE_TAG.'/', $url))
			$url = str_replace( Kidzou_Metropole::REWRITE_TAG, '' , $url );

		//recuperer la trace complete d'appel pour les cas ou l'URL n'est pas convertie (il reste des %kz_metropole%)
		// if (preg_match('/'.Kidzou_GeoHelper::REWRITE_TAG.'/', $url))
		//  	Kidzou_Utils::printStackTrace();
	 
	    return $url; 
	}

}
