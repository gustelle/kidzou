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
	private function __construct() { }


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

}

?>