<?php


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

		add_action( 'pre_get_posts', array( $this, 'geo_filter_query'), 100 );
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
	        $merge = array_merge( $the_metropole, $national);

	        // print_r($merge);

	        if (!empty($merge))
	        {
	            //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
	            set_query_var('tax_query', array(
	                array(
	                      'taxonomy' => 'ville',
	                      'field' => 'slug',
	                      'terms' => $merge
	                    )
	                )
	            );

	        }

	        return $query;
	    }

	    return $query;
	}

    public static function get_metropoles()
    {
        // self::initialize();

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
	            $def = get_tax_meta($value->term_id,'kz_national_ville');
	            if ("on" ==  $def) {
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


	    $result = get_transient('kz_get_national_metropoles');

	    if (false===$result)
	    {
	        $national = array();

	        $villes = get_terms( array("ville"), array(
	                    "orderby" => "count",
	                    "parent" => 0, //only root terms,
	                    "fields" => "ids"
	                ) );

	        //on prend le premier de la liste
	        foreach ($villes as $key) {
	            $def = get_tax_meta($key,'kz_national_ville');
	            if ("on" ==  $def) {
	                $the_term = get_term_by('id', $key, 'ville');
	                array_push($national, $the_term->slug);
	            }
	        }   

	        $result = $national;

	        set_transient( 'kz_get_national_metropoles', (array)$result, 60 * 60 * 24 ); //1 jour de cache
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
	 * @return void
	 * @author 
	 **/
	public static function get_default_metropole()
	{

	    $result = get_transient('kz_default_metropole');

	    if (false===$result)
	    {
	        $villes = get_terms( array("ville"), array(
	                    "orderby" => "count",
	                    "parent" => 0, //only root terms,
	                    "fields" => "ids"
	                ) );

	        //on prend le premier de la liste
	        foreach ($villes as $key) {
	            $def = get_tax_meta($key,'kz_default_ville');
	            if ("on" ==  $def) {
	                $the_term = get_term_by('id', $key, 'ville');
	                $result = $the_term->slug;
	                break;
	            }
	        }   

	        set_transient( 'kz_default_metropole', $result, 60 * 60 * 24 ); //1 jour de cache
	    }
	    
	    return $result;
	}


	public static function rewrite_post_link( $permalink, $post ) {

	    // Check if the %kz_metropole% tag is present in the url:
	    if ( false === strpos( $permalink, '%kz_metropole%' ) )
	        return $permalink;
	 
	    $m = urlencode(self::get_request_metropole());
	 
	    // Replace '%kz_metropole%'
	    $permalink = str_replace( '%kz_metropole%', $m , $permalink );
	 
	    return $permalink;
	}

	/**
	 * Reecriture des pages qui utilisent le tempate 'tous les contenus'
	 * car ces pages sont geolocalisées, c'est à dire que "tous" les contenus sont en fait
	 * filtres par la metropole de rattachement du user
	 *
	 */
	public static function rewrite_page_link( $link, $param ) {

		$m = urlencode(self::get_request_metropole());

		global $post;

		$template = get_post_meta( 
					$post->ID, '_wp_page_template', true 
				);

		if ($template=='all-content.php') {

			$pos = strpos( $link, '/'. $post->post_name );
			$new_link = substr_replace($link, "/".$m, $pos, 0);
			return $new_link;
		}

		return $link;
	    
	}


	public static function rewrite_term_link( $url, $term, $taxonomy ) {
	 
		// Check if the %kz_metropole% tag is present in the url:
	    if ( false === strpos( $url, '%kz_metropole%' ) )
	        return $url;
	 
	    $m = urlencode(self::get_request_metropole());
	 
	    // Replace '%kz_metropole%'
	    $url = str_replace( '%kz_metropole%', $m , $url );
	 
	    return $url; 
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

	    $post = get_post($post_id);

	    $type = $post->post_type;

	    $location_name      = get_post_meta($post_id, 'kz_'.$type.'_location_name', TRUE);
	    $location_address   = get_post_meta($post_id, 'kz_'.$type.'_location_address', TRUE);
	    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE);
	    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE);
	    $location_tel   = get_post_meta($post_id, 'kz_'.$type.'_location_phone_number', TRUE);
	    $location_web   = get_post_meta($post_id, 'kz_'.$type.'_location_website', TRUE);

	    return array(
	        'location_name' => $location_name,
	        "location_address" => $location_address,
	        "location_latitude" => $location_latitude,
	        "location_longitude" => $location_longitude,
	        "location_tel" => $location_tel,
	        "location_web" => $location_web
	    );
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
	    global $post;

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
	                if (get_tax_meta($root->term_id,'kz_national_ville')!="on") {
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


} //fin de classe

?>