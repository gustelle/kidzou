<?php

add_action('kidzou_loaded', array('Kidzou_Events', 'get_instance'));

/**
 * Kidzou
 *
 * @package   Kidzou_Events
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
 * @package Kidzou_Events
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Events {

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
	 * ON considere un post de type evenement si les dates ne sont pas nulles
	 *
	 */ 
    public static function isTypeEvent($event_id=0) {

    	if ($event_id==0)
		{
			global $post;
			$event_id = $post->ID;
		}

		$dates = (array)self::getEventDates($event_id);

		return ($dates['start_date']!=='') || ($dates['end_date']!=='') ;

    }

    /**
	 * undocumented function
	 *
	 * @return true si l'événement est en cours, false si il est terminé ou pas visible
	 * @author 
	 **/
	public static function isEventActive($post_id)
	{

		if (self::isTypeEvent($post_id))
		{
			$meta = self::getEventDates($post_id);
			
			$end_time = new DateTime($meta["end_date"]);

			$current= time();

			if ($end_time->getTimestamp() < $current)
				return false;

			return true;

		}
		return false;
	}

    public static function getEventDates($event_id=0) {

    	if ($event_id==0)
		{
			global $post;
			$event_id = $post->ID;
		}

		$start_date 		= get_post_meta($event_id, 'kz_event_start_date', TRUE);
		$end_date   		= get_post_meta($event_id, 'kz_event_end_date', TRUE);

		return array(
				"start_date" => $start_date,
				"end_date" => $end_date,
			);

    }

    public static function isFeatured($post_id = 0)
	{

		if ($post_id==0)
		{
			global $post;
			$post_id = $post->ID;
		}

		$featured_index		= get_post_meta($post_id, 'kz_event_featured', TRUE);
		$featured 			= ($featured_index == 'A');

		return $featured;
	}
    

} //fin de classe

?>