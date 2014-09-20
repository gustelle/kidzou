<?php


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

		// add_action('init', array($this, 'register_event_type'), 11);

	}

	// public function register_event_type() {

	// 	$labels = array(
	// 		'name'               => 'Evénements',
	// 		'singular_name'      => 'Evénement',
	// 		'add_new'            => 'Ajouter',
	// 		'add_new_item'       => 'Ajouter un événement',
	// 		'edit_item'          => 'Modifier l\'événement',
	// 		'new_item'           => 'Nouvel événement',
	// 		'all_items'          => 'Tous les événements',
	// 		'view_item'          => 'Voir l\'événement',
	// 		'search_items'       => 'Chercher des événements',
	// 		'not_found'          => 'Aucun événement trouvé',
	// 		'not_found_in_trash' => 'Aucun événement trouvé dans la corbeille',
	// 		'menu_name'          => 'Evénements',
	// 		);

	// 	$args = array(
	// 		'labels'             => $labels,
	// 		'public'             => true,
	// 		'publicly_queryable' => true,
	// 		'show_ui'            => true,
	// 		'show_in_menu'       => true,
	// 		'menu_position' 	 => 5, //sous les articles dans le menu
	// 		'menu_icon' 		 => 'dashicons-calendar', 
	// 		'query_var'          => true,
	// 		'rewrite'            => array( 'slug' => 'event' ),
	// 		'capability_type'    => 'event',
	// 		'capabilities' 		 => array(
	// 							        'edit_post'			 => 'edit_event',
	// 							        'edit_posts' 		 => 'edit_events',
	// 							        'edit_others_posts'  => 'edit_others_events',
	// 							        'publish_posts' 	 => 'publish_events',
	// 							        'read_post' 		 => 'read_event',
	// 							        'read_private_posts' => 'read_private_events',
	// 							        'delete_post' 		 => 'delete_event',
	// 							        'delete_private_posts' 		=> 'delete_private_events',
	// 							        'delete_published_posts' 	=> 'delete_published_events',
	// 							        'delete_others_posts' 		=> 'delete_others_events',
	// 							        'edit_private_posts' 		=> 'edit_private_events',
	// 							        'edit_published_posts' 		=> 'edit_published_events',
	// 							        // 'assign_terms' => 'assign_terms'
	// 							    ),
	// 		'map_meta_cap' 		 => true,
	// 		'has_archive'        => true,
	// 		'hierarchical'       => false, //pas de hierarchie d'events
	// 		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions'),
	// 		'taxonomies' 		=> array('age', 'ville', 'divers','category'), //reuse the taxo declared in kidzou plugin
	// 		// 'register_meta_box_cb' => 'add_metabox'
	// 	);

	// 	register_post_type( 'event', $args );

	// 	flush_rewrite_rules();		
	// }

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