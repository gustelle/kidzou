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


	private static $initialized = false;


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

	}

    private static function initialize()
    {
        if (self::$initialized)
            return;

        self::$initialized = true;
    }

    public static function get_metropoles()
    {
        self::initialize();

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


} //fin de classe

?>