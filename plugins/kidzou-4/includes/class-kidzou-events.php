<?php

add_action('kidzou_loaded', array('Kidzou_Events', 'get_instance'));

// schedule the feedburner_refresh event only once
if( !wp_next_scheduled( 'unpublish_posts' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'unpublish_posts' );
}
 
add_action( 'unpublish_posts', array( Kidzou_Events::get_instance(), 'unpublish_obsolete_posts') );

//import RSS d'agenda
if( !wp_next_scheduled( 'feed_import' ) ) {
   wp_schedule_event( time(), 'daily', 'feed_import' );
}
 
add_action( 'feed_import', array( Kidzou_Events::get_instance(), 'feed_import_events') );


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
	const VERSION = '04-nov';


	// private static $initialized = false;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	public static $meta_featured = 'kz_event_featured';


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		//mise en avant de posts
		add_filter( 'posts_results', array( $this, 'order_by_featured'), PHP_INT_MAX, 2  );
		
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
	 * On considere un post de type evenement si les dates ne sont pas nulles
	 * il s'agit d'un hack pour tenir compte d'un legacy ou les event étaient des post types différents des posts normaux
	 *
	 */ 
    public static function isTypeEvent($event_id=0) {

    	if ($event_id==0)
		{
			global $post;
			$event_id = $post->ID;
		}

		$dates = (array)self::getEventDates($event_id);

		return ($dates['start_date']!=='') && ($dates['end_date']!=='') ;

    }

    /**
	 * Un evenement est actif si la date de fin est postérieure à la date courante
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

		$featured_index		= get_post_meta($post_id, self::$meta_featured, TRUE);
		$featured 			= ($featured_index == 'A');

		return $featured;
	}

	/**
	 * la liste des post featured 
	 * il s'agit d'un tableau d'objets WP_Post
	 *
	 * @return void
	 * @author 
	 **/
	public static function getFeaturedPosts(  )
	{
		

		$list = get_posts(array(
					'meta_key'         => self::$meta_featured,
					'meta_value'       => 'A',
					'post_type'        => array('post','offres'),
				));


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

			Kidzou_Utils::log( 'Unpublished : ' . $event->ID. '['. $event->post_name .']' );

		}

	}

	/**
	 * Aspiration de l'agenda sur lille.fr
	 *
	 * 
	 *
	 */
	public static function feed_import_events() {

		Kidzou_Utils::log("feed_import_events " );

		$content = file_get_contents("http://www.lille.fr/cms/agenda?template=events.rss&definitionName=events");
	    $x = new SimpleXmlElement($content);

	    Kidzou_Utils::log("feed_import_events after " );
	     
	    foreach($x->channel->item as $entry) {
	        Kidzou_Utils::log("Import RSS : " . $entry->title );
	    }

	}

	

	/**
	 * les loops secondaires sont tries pour mettre les featured en 1er
	 *
	 */ 
	public function order_by_featured($posts, $query) {

		if (!is_admin()  && !$query->is_main_query() && !is_page() && !is_single()) { //

			remove_filter( current_filter(), __FUNCTION__, PHP_INT_MAX, 2 );

			$queried = get_queried_object();

			if (isset($queried->term_taxonomy_id )) {

				$nonfeatured = array();

			    $featured =  self::getFeaturedPosts( );

			    $filtered = array_filter($featured, function($item) {
			    	$queried = get_queried_object();
			    	$terms = wp_get_post_terms($item->ID, $queried->taxonomy, array('fields' => 'ids'));
			    	return in_array($queried->term_id, $terms);
			    });

			    $filtered_posts = array_map(function($el) {
			    	return get_post($el->ID);
			    }, $filtered);
			    
			    foreach ( $posts as $a_post ) {
			     
					if ( !self::isFeatured($a_post->ID) ) {

						$nonfeatured[] = $a_post;

					}			    		

			    }
		    
		    	$posts = array_merge( $filtered_posts, $nonfeatured );

			}		   

		} 
		
		return $posts;
	}


    

} //fin de classe

?>