<?php

add_action('kidzou_loaded', array('Kidzou_Geo', 'get_instance'));
/* seulement à l'activation du plugin */
add_action( 'kidzou_activate', array('Kidzou_Geo', 'set_permalink_rules'));

/**
 * Kidzou
 *
 * @package   Kidzou_Geo
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Kidzou_Geo
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Geo {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '2014.08.24';


	// private static $initialized = false;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		// Activate plugin when new blog is added

		add_action( 'init', array( $this, 'create_rewrite_rules' ),90 );

		add_filter( 'post_link', array( $this, 'rewrite_post_link' ) , 10, 2 );
		add_filter( 'page_link', array( $this, 'rewrite_page_link' ) , 10, 2 );
		add_filter( 'term_link', array( $this, 'rewrite_term_link' ), 10, 3 );
		// add_filter( 'divers_rewrite_rules', array( $this, 'divers_rewrite_rules' ), 10, 3 );

		add_action( 'pre_get_posts', array( $this, 'geo_filter_query'), 999 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );
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

		wp_enqueue_script('kidzou-geo', plugins_url( '../../assets/js/kidzou-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), self::VERSION, true);

		$villes = self::get_metropoles();

		global $kidzou_options;
  
		$args = array(
					'geo_mapquest_key'			=> $kidzou_options["geo_mapquest_key"], //Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6
					'geo_mapquest_reverse_url'	=> "http://www.mapquestapi.com/geocoding/v1/reverse",
					'geo_mapquest_address_url'	=> "http://www.mapquestapi.com/geocoding/v1/address",
					'geo_default_lat'			=> $kidzou_options["geo_default_lat"],//50.637234, //lille //externaliser dans $kidzou_options
					'geo_default_lng' 			=> $kidzou_options["geo_default_lng"],//3.06339, //lille //externaliser dans $kidzou_options
					'geo_default_metropole'		=> self::get_default_metropole(),
					'geo_cookie_name'			=> "kz_metropole",
					'geo_select_cookie_name'	=> "kz_metropole_selected",
					'geo_possible_metropoles'	=> $villes ,
	                //'geo_icon_url'              => WP_PLUGIN_URL.'/kidzou-geo/images/location_icon2.png' //todo
				);

	    wp_localize_script(  'kidzou-geo', 'kidzou_geo_jsvars', $args );

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function set_permalink_rules () {

		global $wp_rewrite;

		$wp_rewrite->set_category_base('%kz_metropole%/rubrique/');
		$wp_rewrite->set_tag_base('%kz_metropole%/tag/');

		self::create_rewrite_rules();
	}


    /**
	 * Les Query en Base sont filtrées en tenant compte de la métropole courante
	 * Celle-ci est soit la metropole passée dans la requete (en provenance du cookie utilisateur), soit la metropole par défaut
	 * les contenus à portée "nationale" sont également remontés
	 *
	 * @since    1.0.0
	 */
	public static function geo_filter_query( $query ) {

		//les pages n'ont pas de taxo "ville", il faut les exclure du filtre
	    if( !is_admin() && !is_search() ) {


	        $the_metropole = array(self::get_request_metropole());
	        $national = (array)self::get_national_metropoles(); 
	       	$merge = array_merge( $the_metropole, $national );

	        //reprise des arguments qui auraient pu être passés précédemment par d'autres requetes
	        //d'ou l'importance d'executer celle-ci en dernier
	        $vars = get_query_var('tax_query'); 

	        $ville_tax_present = false;

	        if (isset($vars['taxonomy']) && $vars['taxonomy']=='ville')
	        	$ville_tax_present = true;

	        else if (is_array($vars)) {
	        	foreach ($vars as $key => $value) {
		        	
	        		// print_r(array_keys($value));
	        		if (is_array($value)) {
	        			foreach ($value as $key => $value) {
		        			if ($key == 'taxonomy' && $value=='ville') {
		        				$ville_tax_present = true;
		        				// echo 'found';
		        			}
		        				
		        		}

	        		}
		        		
		        }

	        }
	        
	        if (!$ville_tax_present)
	        	$vars[] = array(
	                      'taxonomy' => 'ville',
	                      'field' => 'slug',
	                      'terms' => $merge
	                    );

	        if (!empty($vars))
	        {
	            //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
	            set_query_var('tax_query', $vars);

	        }

	        return $query;
	    }

	    return $query;
	}

    public static function get_metropoles()
    {
        // self::initialize();

        global $kidzou_options;

        $result = get_transient('kz_covered_metropoles_all_fields');

	    if (false===$result)
	    {
	        $villes = get_terms( array("ville"), array(
	            "orderby" => "slug",
	            "parent" => 0, //only root terms,
	            "fields" => "all"
	        ) );

	        $result  = array();

	        //sortir les villes à couverture nationale
	        //on prend le premier de la liste
	        foreach ($villes as $key=>$value) {
	            $def = $kidzou_options['geo_national_metropole']; //get_tax_meta($value->term_id,'kz_national_ville');
	            if ( intval($def) ==  intval($value->term_id) ) {

	            } else {
	                $result[$key] = $value;
	            }
	        }   

	        set_transient( 'kz_covered_metropoles_all_fields', (array)$result, 60 * 60 * 24 ); //1 jour de cache
	    }

	    return $result;
    }

    /**
	 * retourne un tableau de villes à portée nationale
	 * les villes à portée nationale ont pour vocation de porter des articles à portée nationale 
	 * Les villes à portée nationale doivent être à la racine 
	 *
	 * @return array
	 * @author 
	 **/
	public static function get_national_metropoles()
	{

	    global $kidzou_options;

	    $term = get_term_by('id', $kidzou_options['geo_national_metropole'], 'ville');

	    $result = array();

	    if (!is_wp_error($term) && is_object($term)) {
			array_push($result, $term->slug);
	    }

	    return $result;


	}

	/**
	 * la metropole de rattachement de la requete
	 *
	 * @return String (slug)
	 * @author 
	 **/
	public static function get_request_metropole()
	{
		if (isset($_GET['kz_metropole']))
		{
			$cook_m = strtolower($_GET['kz_metropole']);

		    $isCovered = self::is_metropole($cook_m);

		    if (!$isCovered)
		        return self::get_default_metropole();
		    else
		        return $cook_m;
		}
	    
	    return self::get_default_metropole();
	}

	/**
	 * la ville (slug) passee en parametre est-elle connue comme metropole dans notre système?
	 *
	 * @return Booléen
	 * @author 
	 **/
	public static function is_metropole($m)
	{

	    if ($m==null || $m=="")
	        return false;

	    //la ville du user est-elle couverte par Kidzou
	    $villes  = self::get_metropoles();
	    $isCovered = false;
	    foreach ($villes as $key => $value) {
	        if (is_string($value) && $m==strtolower($value))
	            $isCovered = true;
	    }

	    return $isCovered;
	}

	/**
	 * 
	 *
	 * @return le slug de la ville par defaut, selectionnée dans les options
	 * @author 
	 **/
	public static function get_default_metropole()
	{

	    global $kidzou_options;

	    $term = get_term_by('id', $kidzou_options['geo_default_metropole'], 'ville');

	    if (!is_wp_error($term) && is_object($term))
	    	return $term->slug;

	    return '';
	}


	public static function rewrite_post_link( $permalink, $post ) {

		//pas dans l'admin !
		if (!is_admin()) {

			$m = urlencode(self::get_request_metropole());

		    // Check if the %kz_metropole% tag is present in the url:
		    if ( true === strpos( $permalink, '%kz_metropole%' ) ) {

			    // Replace '%kz_metropole%'
			    $permalink = str_replace( '%kz_metropole%', $m , $permalink );

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
	public static function rewrite_page_link( $link, $page ) {

		//pas dans l'admin !
		if (!is_admin()) {

			$m = urlencode(self::get_request_metropole());

			$rewrite = self::is_page_rewrite($page);

			$post = get_post($page);

			if ($rewrite) {

				$pos = strpos( $link, '/'. $post->post_name );
				$new_link = substr_replace($link, "/".$m, $pos, 0);
				return $new_link;
			}

		}

		return $link;
	    
	}


	public static function rewrite_term_link( $url, $term, $taxonomy ) {

		if (!is_admin()) {

			// echo ' ? ' . $url;

			// Check if the %kz_metropole% tag is present in the url:
		    if ( false === strpos( $url, '%kz_metropole%' ) )
		        return $url;
		 
		    $m = urlencode(self::get_request_metropole());
		 
		    // Replace '%kz_metropole%'
		    $url = str_replace( '%kz_metropole%', $m , $url );

		}
	 
	    return $url; 
	}

	// public static function divers_rewrite_rules( $url, $term, $taxonomy ) {

	// 	echo $url;
	 
	//     return $url; 
	// }

	/**
	 * les infos d'emplacement géographique d'un post
	 *
	 * @return Tableau contenant les meta de Geoloc d'un post
	 * @author 
	 **/
	public static function get_post_location($post_id=0, $type='')
	{

	    if ($post_id==0)
	    {
	        global $post; 
	        $post_id = $post->ID; 
	    }

	    $post = get_post($post_id); 

	    if ($type == '')
	    	$type = $post->post_type;

	    $location_name      = get_post_meta($post_id, 'kz_'.$type.'_location_name', TRUE);
	    $location_address   = get_post_meta($post_id, 'kz_'.$type.'_location_address', TRUE);
	    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE);
	    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE);
	    $location_tel   = get_post_meta($post_id, 'kz_'.$type.'_location_phone_number', TRUE);
	    $location_web   = get_post_meta($post_id, 'kz_'.$type.'_location_website', TRUE);
	    $location_city   = get_post_meta($post_id, 'kz_'.$type.'_location_city', TRUE);

	    return array(
	        'location_name' => $location_name,
	        "location_address" => $location_address,
	        "location_latitude" => $location_latitude,
	        "location_longitude" => $location_longitude,
	        "location_tel" => $location_tel,
	        "location_web" => $location_web,
	        "location_city" => $location_city
	    );
	}

	/**
	 * l'URL de la page doit-elle etre préfixée de la metropole du user ?
	 *
	 * @return void
	 * @author 
	 **/
	public static function is_page_rewrite ($post_id=0)
	{
		if ($post_id==0)
	    {
	        global $post;
	        $post_id = $post->ID;
	    }

	    return get_post_meta($post_id, 'kz_rewrite_page', TRUE);
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

	    return $location_latitude<>'' && $location_longitude<>'';
	}

	/**
	 * Rewrites incluant les metropoles
	 *
	 */
	public static function create_rewrite_rules() {
		
	    $villes = self::get_metropoles();

	    if (!empty($villes)) {

	    	$regexp = '(';
	        $i=0;
	        $count = count($villes);
	        foreach ($villes as $item) {
	            $regexp .= $item->slug;
	            $i++;
	            if ($regexp!=='' && $count>$i) {
	                $regexp .= '|';
	            }
	        }
	        $regexp .= ')';

			add_rewrite_tag('%kz_metropole%',$regexp, 'kz_metropole=');

			//see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
		    add_rewrite_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home
		    add_rewrite_rule($regexp.'/offres/page/?([0-9]{1,})/?','index.php?post_type=offres&paged=$matches[2]&kz_metropole=$matches[1]','top');
		    add_rewrite_rule($regexp.'/offres/?','index.php?post_type=offres&kz_metropole=$matches[1]','top');
		   	add_rewrite_rule($regexp.'/(.*)$/?','index.php?pagename=$matches[2]&kz_metropole=$matches[1]','top');
		   	add_rewrite_rule($regexp.'/(.*)/page/?([0-9]{1,})/?$','index.php?pagename=$matches[2]&paged=$matches[3]&kz_metropole=$matches[1]','top');

	    }
	    
	}

	/**
	 * la metropole du post courant
	 * si rattaché à plusieurs metropoles (national, lille...) on prend la metropole qui dispose du meta kz_national_ville
	 * si aucune metropole ne dispose de cette meta, on prend la premiere de la liste
	 *
	 * @return Object
	 * @author 
	 **/
	public static function get_post_metropole( )
	{
	    global $post; global $kidzou_options;

	    $result = get_transient('kz_post_metropole_'. $post->ID ); 

	    if (false===$result)
	    {

	        $terms = wp_get_post_terms( $post->ID, 'ville');

	        $roots = array();

	        foreach ($terms as $key => $value){
	            //get top level parent
	            $ancestors = get_ancestors( $value->term_id, 'ville' );
	            
	            if (count($ancestors)==0) {
	                //le terme est déjà à la racine
	                array_push($roots, $value);
	            
	            } else {

	                foreach ($ancestors as $ancestor){
	                    $ville = get_term_by('id', (int)$ancestor, 'ville');
	                    if ($ville->parent == 0) {
	                        array_push($roots, $ville);
	                    }
	                }
	            }
	            
	        }

	        //si le post est rattaché à plus d'une metropole
	        if (count($roots)>1) {
	            $i=0;
	            $save_me = $roots[$i];
	            foreach ($roots as $root) {
	                //if (get_tax_meta($root->term_id,'kz_national_ville')!="on") {
	            	$def = $kidzou_options['geo_national_metropole'];
	            	if ( intval($def)!=intval($root->term_id) ) {
	                    unset($roots[$i]);
	                } else {
	                    $save_me = $roots[$i];
	                }
	                $i++;
	            }
	            $roots[0] = $save_me;
	        }

	        $result = $roots[0];

	        set_transient( 'kz_post_metropole_'. $post->ID , $result, 60 * 60 * 24 ); //1 jour de cache
	        
	    }
	    
	    return $result;
	}

	public static function get_related_posts() {

		add_filter('crp_posts_join', array(self::get_instance(), 'crp_filter_metropole')) ;

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

		$metropole = self::get_post_metropole(); //object

		if ($metropole!=null) {
			$join .= "
			INNER JOIN wp_term_taxonomy AS tt ON (tt.term_id=".$metropole->term_id." AND tt.taxonomy='ville')
			INNER JOIN wp_term_relationships AS tr ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tr.object_id=ID) ";
		}

		return $join;
	}


} //fin de classe

?>