<?php

add_action('kidzou_loaded', array('Kidzou_Events', 'get_instance'));

// schedule the feedburner_refresh event only once
if( !wp_next_scheduled( 'unpublish_posts' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'unpublish_posts' );
}
 
add_action( 'unpublish_posts', array( Kidzou_Events::get_instance(), 'unpublish_obsolete_posts') );


//https://github.com/briannesbitt/Carbon
// require 'Carbon/Carbon.php';
use Carbon\Carbon;


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
	 * les événements qui sont recurrents sont marqués de cette meta
	 *
	 */
	public static $meta_recurring = 'kz_event_recurrence';

	/**
	 * les types de posts qui supportent les meta event
	 *
	 */
	public static $supported_post_types = array('post','offres');


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
					'post_type'        => self::$supported_post_types,
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
	public static function unpublish_obsolete_posts() 
	{

		global $wpdb;
		
		$obsoletes = self::getObsoletePosts();

		foreach ($obsoletes as $event) {

			$event_dates 	= self::getEventDates($event->ID);
			$start_date 	= $event_dates['start_date'];
			$end_date 		= $event_dates['end_date'];

			$start_time = new DateTime($start_date);
			$end_time = new DateTime($end_date);

			//gestion de la recurrence:
			$recurrence		= get_post_meta($event->ID, Kidzou_Events::$meta_recurring, FALSE);
			$occurences 	= intval($data['endValue']);

			$repeatable = false;

			if (is_array($recurrence[0]))
			{
				//plus facile à menipuler
				$data 		= $recurrence[0];
				$endType 	= $data['endType'];

				if ($endType=='never') {

					$repeatable = true;

				} else if ($endType=='date') {

					$now = new DateTime(date('Y-m-d 00:00:00', time()));
					if ($end_time > $now)
						$repeatable = true;

				} else {

					if ( $endType=='occurences' && ($occurences > 0))
						$repeatable = true;
				}			
			}

			if ($repeatable)
			{
				$data 			= $recurrence[0]; //

				if($data['model'] == 'weekly')
				{
					//semaine 0
					//imaginons que l'evenement doivent etre répété certains jours 
					//de la semaine ou se passe l'événement (ex: l'evenement est en début/fin le mercredi 03/12, il doit se répéter le vendredi 05/12)
					//Dans ce cas il ne faut pas encore ajouter les semaines (repeatEach)

					//modele de répétition hebdo : les valeurs de répétition sont les jours
					//1: lundi -> 7: dimanche
					$days = (array)$data['repeatItems'];
					
					//Recupérer le jour de start_date
					//1: lundi...7:dimanche
					$start_day = $start_time->format('N'); 

					//dans la semaaine de la start_date, y a-t-il un jour ou l'événement se répété ?
					if (intval($start_day)<7) 
					{
						foreach ($days as $day) {

							if (intval($day)>intval($start_day)) {

								//positionner le jour de répétition
								$diff = intval($day) - intval($start_day);
								$start_time->add(new DateInterval( "P".$diff."D" ));
								$end_time->add(new DateInterval( "P".$diff."D" ));

								break;
							}
						}
					}
					
					//sinon, on voit s'il y a des répétitions à faire les semaines suivantes
					//toutes les x semaines
					else
					{
						$jumpWeeks =  (int)$data['repeatEach'];
						$start_time->add(new DateInterval( "P".$jumpWeeks."W" ));
						$end_time->add(new DateInterval( "P".$jumpWeeks."W" ));

						//attention :
						//on est le dimanche de la semaine 1, l'événement se répéte le mardi de la semaine 3
						//on ajout 2 semaines, mais on retire 7-2
						//autre exemple : on est le le mardi, l'événement se répété le mardi suivant: il ne faut rien retirer cette fois
						$first_day_of_repeat = $days[0];
						$diff = intval($start_day)-intval($first_day_of_repeat);
						if ($diff>0)
						{
							$start_time->sub(new DateInterval( "P".$diff."D" ));
							$end_time->sub(new DateInterval( "P".$diff."D" ));
						}

					}

				}
				else
				{

					//dans ce modele, repeatItems est une string
					$days = $data['repeatItems'];

					//modele de répétition mensuelle
					$jumpMonths =  (int)$data['repeatEach'];

					if ($days=='day_of_month') {

						//le 3 du mois
						$start_time->add(new DateInterval( "P".$jumpMonths."M" ));
						$end_time->add(new DateInterval( "P".$jumpMonths."M" ));

					} else if ($days=='day_of_week') {

						//Ex : le 2e jeudi du mois

						//le numéro de la semaine 
						$carbon = Carbon::instance($start_time);
						$week_number = $carbon->weekOfMonth;

						//Recupérer le jour de start_date
						//1: lundi...7:dimanche
						$start_day = $start_time->format('N'); 

						//RAF : positionner ce jour dans les mois suivant
						//next month
						$start_time->add(new DateInterval( "P".$jumpMonths."M" ));
						$end_time->add(new DateInterval( "P".$jumpMonths."M" ));
						
						$dt = Carbon::parse($start_date);
						$next_day = $dt->next(Carbon::WEDNESDAY); 

						// echo $next_day; 
						
					}

				}

				//sauvegarder les meta
				if ($endType=='occurences')
					$occurences++;

				Kidzou_Utils::log( 'Recurrence : Modification des dates sur ' . $event->ID. ' ['. $event->post_name .']' );
			} 
			else
			{
				//plus besoin de ces posts s'ils ne sont pas recurrents
				
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $event->ID ) );

				clean_post_cache( $event->ID );
					
				$old_status = $event->post_status;
				$event->post_status = 'draft';
				wp_transition_post_status( 'draft', $old_status, $event );

				Kidzou_Utils::log( 'Unpublished : ' . $event->ID. '['. $event->post_name .']' );
			}

		}
	}

	// /**
	//  * Aspiration de l'agenda sur lille.fr
	//  *
	//  * 
	//  *
	//  */
	// public static function getFeed() {

	// 	Kidzou_Utils::log("feed_import_events " );

	// 	$content = file_get_contents("http://www.lille.fr/cms/agenda?template=events.rss&definitionName=events");
	//     $x = new SimpleXmlElement($content);

	//     Kidzou_Utils::log("feed_import_events after " );
	     
	//     foreach($x->channel->item as $entry) {
	//         Kidzou_Utils::log("Import RSS : " . $entry->title );
	//     }

	// }

	

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