<?php

add_action('kidzou_loaded', array('Kidzou_Events', 'get_instance'));

// schedule the feedburner_refresh event only once
if( !wp_next_scheduled( 'unpublish_posts' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'unpublish_posts' );
}
 
add_action( 'unpublish_posts', array( Kidzou_Events::get_instance(), 'unpublish_obsolete_posts') );


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

		//tri des posts dans la requete: par featured, puis par date
		// add_filter('posts_orderby', array( $this, 'query_orderby'), 100 );
		
		// tri des posts par meta 
		//d'abord les featured, puis par date
		add_filter( 'posts_results', array( $this, 'sort_query_results'), 100  );
		
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

	/**
	 * Construit une WP_Query contenant des evenements sur une metropole donnée, dans un intervalle donné
	 *
	 * @return array()
	 * @author 
	 **/
	public static function getEventsByInterval( $interval_days = 7, $ppp=-1 )
	{
		$metropole = Kidzou_Geo::get_request_metropole();

		$interval = 'P7D';

		switch ($interval_days) {
			case 7:
				$interval = 'P7D';
				break;

			case 30:
				$interval = 'P30D';
				break;
			
			default:
				$interval = 'P7D';
				break;
		}

		$current= time();
		$start 	= date('Y-m-d 00:00:00', $current);
		$end_time 	= new DateTime($start);

		$end_time 	= $end_time->add( new DateInterval($interval) ); 

		$end 	= $end_time->format('Y-m-d 23:59:59');

		$meta_q = array(
	                   array(
	                         'key' => 'kz_event_start_date',
	                         'value' => $end,
	                         'compare' => '<=',
	                         'type' => 'DATETIME'
	                        )
	                   ,
						array(
	                         'key' => 'kz_event_end_date',
	                         'value' => $start,
	                         'compare' => '>=',
	                         'type' => 'DATETIME'
	                        )
			    	);

		if ($metropole!='')
			$args = array(
				'meta_key' => 'kz_event_start_date' , //kz_event_featured
				'orderby' => 'meta_value',
				'order' => 'ASC' ,
				'posts_per_page' => $ppp, 
				'meta_query' => $meta_q,
			    'tax_query' => array(
			        array(
			              'taxonomy' => 'ville',
			              'field' => 'slug',
			              'terms' => $metropole,
			              )
			    )
			);
		else
			$args = array(
				'meta_key' => 'kz_event_start_date' , //kz_event_featured
				'orderby' => 'meta_value',
				'order' => 'ASC' ,
				'posts_per_page' => $ppp, 
				'meta_query' => $meta_q
			);

		$query = new WP_Query($args );	

		$list = 	$query->get_posts(); 

		//les featured en premier
		uasort($list, array('self', "sort_by_featured") );

		return $list;
	}

	/**
	 * Construit une WP_Query contenant des evenements sur une metropole donnée, dans un intervalle donné
	 *
	 * @return array()
	 * @author 
	 **/
	public static function getObsoletePosts( )
	{

		
		$current= time();
		$now 	= date('Y-m-d 00:00:00', $current);

		$meta_q = array(
						array(
	                         'key' => 'kz_event_end_date',
	                         'value' => $now,
	                         'compare' => '<',
	                         'type' => 'DATETIME'
	                        )
			    	);

		$args = array(
			'posts_per_page' => -1, 
			'post_status' => 'publish',
			'meta_query' => $meta_q,
		);

		$query = new WP_Query($args );	

		$list = 	$query->get_posts(); 

		return $list;
	}

	/**
	 * dépublie les events dont la date est dépassée
	 *
	 */
	public static function unpublish_obsolete_posts() {

		global $wpdb;
		
		$obsoletes = self::getObsoletePosts();

		foreach ($obsoletes as $event) {
						
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $event->ID ) );

			clean_post_cache( $event->ID );
				
			$old_status = $event->post_status;
			$event->post_status = 'draft';
			wp_transition_post_status( 'draft', $old_status, $event );

			write_log('Unpublished : ' . $event->ID. '['. $event->post_name .']' );

		}

	}

	/**
	 * compare 2 events et place les featured en premier, mais maintien l'ordre des dates (orderby kz_event_end_date)
	 * pour rappel, un event featured à un meta "featured"='A' alors qu'un event normal a sa "featured"='B'
	 *
	 * @return void
	 * @author 
	 * @todo retravailler ce tri pour limiter les events qui sont remontés (uniquemnet sur la semaine courante)
	 **/
	public static function sort_by_featured($a, $b)
	{
		$featured_a = get_post_meta($a->ID, 'kz_event_featured', TRUE);
		$featured_b = get_post_meta($b->ID, 'kz_event_featured', TRUE);

		// pas de distinction de featured, c'est la start_date qui prime
		if (strcmp($featured_a, $featured_b)==0) {

			$start_a = get_post_meta($a->ID, 'kz_event_start_date', TRUE);
			$start_b = get_post_meta($b->ID, 'kz_event_start_date', TRUE); //echo strcmp($start_a, $start_b);
			return strcmp($start_a, $start_b);
		}

		return strcmp($featured_a, $featured_b);
	}

	public function sort_query_results($posts) {

		if (!is_admin()) {
			uasort($posts, array('self', "sort_by_featured") );
		}
		
		return $posts;
	}


    

} //fin de classe

?>