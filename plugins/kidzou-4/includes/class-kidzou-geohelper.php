<?php

add_action('plugins_loaded', array('Kidzou_GeoHelper', 'get_instance'), 100);

/**
 * Kidzou_Geo
 *
 * @package   Kidzou_GeoHelper
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Cette classe fournit des facilités d'accès aux meta de geoloc des posts ainsi
 * qu'aux metropoles disponibles dans le système
 *
 * @package Kidzou_GeoHelper
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_GeoHelper {
	

	const REWRITE_TAG = '%kz_metropole%';

	/**
	 *
	 * @var      string
	 */
	const META_COORDS = 'kz_coords';

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
	 * le tableau des post types qui supportent la geolocalisation$
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

	    $return = ($location_latitude!='' && $location_longitude!='');

	    return $return;
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
	                
	            	$def = Kidzou_Utils::get_option('geo_national_metropole');
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


	/**
	 * La liste des metropoles supportées par le système
	 * autrement dit, les metropoles à la racine de la taxonomie "ville"
	 *
	 * @return Array<StdObject>
	 */ 
    public static function get_metropoles()
    {

        $result = get_transient('kz_covered_metropoles_all_fields');

	    if (false===$result)
	    {
	        $villes = get_terms( array("ville"), array(
	            "orderby" => "slug",
	            "parent" => 0, //only root terms,
	            "fields" => "all"
	        ) );

	        $result  = array();

	        if (!is_wp_error($villes)) {

	        	//sortir les villes à couverture nationale
		        //on prend le premier de la liste
		        foreach ($villes as $key=>$value) {
		            $def = Kidzou_Utils::get_option('geo_national_metropole'); 
		            if ( intval($def) ==  intval($value->term_id) ) {

		            } else {
		                $result[$key] = $value;
		                // Kidzou_Utils::log('Kidzou_GeoHelper::get_metropoles() : adding ' . $value->slug);
		            }
		        }   

		        if (!empty($result) && count($result)>0) {
		        	set_transient( 'kz_covered_metropoles_all_fields', (array)$result, 60 * 60 * 24 ); //1 jour de cache
		        	// Kidzou_Utils::log('kz_covered_metropoles_all_fields -> set ' . count($result) . ' result');
		        }
		       		
	        } else {
	        	Kidzou_Utils::log($villes, true);
	        }
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

	    $term = get_term_by('id', Kidzou_Utils::get_option('geo_national_metropole'), 'ville');

	    $result = array();

	    if (!is_wp_error($term) && is_object($term)) {
			array_push($result, $term->slug);
	    }

	    return $result;


	}

	

	/**
	 * la ville (slug) passee en parametre est-elle connue comme metropole dans notre système?
	 *
	 * @return Booléen
	 * @author 
	 **/
	public static function is_metropole($m)
	{

		// Kidzou_Utils::log('is_metropole : '. $m);
	    if ($m==null || $m=="") return false;

	    //la ville du user est-elle couverte par Kidzou
	    $villes  = self::get_metropoles();

	    $isCovered = false;
	    foreach ($villes as $v) {
	    	// error_log(print_r($v), true);
	        if ($v->slug == $m)
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

	    $term = get_term_by('id', Kidzou_Utils::get_option('geo_default_metropole') , 'ville');

	    if (!is_wp_error($term) && is_object($term))
	    	return $term->slug;

	    return '';
	}



	/**
	 * fournit le REGEX des metropoles dans une URI
	 *
	 * @return String du genre (metropole1|metropole2|...)
	 */ 
	public static function get_metropole_uri_regexp() {

		$regexp = get_transient('kz_metropole_uri_regexp'); 

   		if (false===$regexp) {

   			$villes = self::get_metropoles();

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
	        $regexp .= ')'; //'|'.self::$no_filter.
	
			if ($regexp != '()')
				set_transient( 'kz_metropole_uri_regexp' , $regexp, 60 * 60 * 24 ); //1 jour de cache
   		}

   		return $regexp;

	}



} //fin de classe

?>