<?php

add_action('kidzou_loaded', array('Kidzou_Events', 'get_instance'));

// schedule the feedburner_refresh event only once
if( !wp_next_scheduled( 'kidzou_events_scheduler' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'kidzou_events_scheduler' );
}
 
add_action( 'kidzou_events_scheduler', array( Kidzou_Events::get_instance(), 'unpublish_obsolete_posts') );


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
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;


	/**
	 * les meta qui définissent la recurrence d'un événement
	 *
	 */
	public static $meta_recurring = 'kz_event_recurrence';

	/**
	 * les événements qui sont recurrents et passés sont marqués de cette meta
	 *
	 */
	public static $meta_past_dates = 'kz_event_past_dates';

	/**
	 * les événements qui sont recurrents sont marqués de cette meta
	 *
	 */
	public static $meta_start_date = 'kz_event_start_date';

	/**
	 * les événements qui sont recurrents sont marqués de cette meta
	 *
	 */
	public static $meta_end_date = 'kz_event_end_date';


	/**
	 * les types de posts qui supportent les meta event
	 *
	 */
	// public static $supported_post_types = array('post','offres');


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

	/**
	 * traitement par Job des events dont la date est dépassée
	 *
	 */
	public static function unpublish_obsolete_posts() 
	{

		global $wpdb;

		Kidzou_Utils::log('------ unpublish_obsolete_posts -------', true);

		$args = $args = array(
	      'posts_per_page' => -1, 
	      'post_status' => 'publish',
	      'is_active'	=> false
	    );
		
		// $obsoletes = self::getObsoletePosts();
		$query = new Event_Query($args);
		$obsoletes = $query->get_posts(); 

		// Kidzou_Utils::log(count($obsoletes) . ' evenements concernes ', true);

		foreach ($obsoletes as $event) {

			Kidzou_Utils::log('Event ['. $event->post_name . '] ', true );

			////////////////////////////////

			//le jour de la semaine n'est pas bon (ex: 2e mercredi -> 3e mardi)
			//la date de fin ne marche pas (ex: début le 10/12, tous les 2 mois / fin le 12/12 )

			$start_date		= get_post_meta($event->ID, self::$meta_start_date, TRUE);
			$end_date 		= get_post_meta($event->ID, self::$meta_end_date, TRUE);
			$recurrence		= get_post_meta($event->ID, self::$meta_recurring, FALSE);
			$past_dates		= get_post_meta($event->ID, self::$meta_past_dates, FALSE);

			$start_time = new DateTime($start_date);
			$end_time = new DateTime($end_date);

			//gestion de la recurrence:
			$occurences 	= 0;
			$repeatable = false;

			//pour les récurrences : les dates mises à jour
			$new_start_date = $start_date;
			$new_end_date = $end_date;

			if (is_array($recurrence[0]))
			{
				//plus facile à menipuler
				$data 		= $recurrence[0];
				$endType 	= $data['endType'];
				$occurences = intval($data['endValue']);

				////////////////////////////////
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
					// $nextOccurenceInCurrentWeek = false;
					$nextDay = 0;

					// Kidzou_Utils::log('     start_day=' . $start_day, true);

					//calculer la prochaine date d'occurrence
					foreach ($days as $day) {

						if (intval($day)>intval($start_day)) {

							//une prochaine occurrence dans la sameine
							// Kidzou_Utils::log('     next occurence found in current week ' . $day, true);
							// $nextOccurenceInCurrentWeek = true;
							$nextDay = $day;
							break;

						}
					}

					//dans la semaaine de la start_date, y a-t-il un jour ou l'événement se répété ?
					if ($nextDay>0) 
					{

						//positionner le jour de répétition
						$diff = intval($nextDay) - intval($start_day);
						$start_time->add(new DateInterval( "P".$diff."D" ));
						$end_time->add(new DateInterval( "P".$diff."D" ));

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

						// Kidzou_Utils::log('     first_day_of_repeat=' . $first_day_of_repeat, true);

						$diff = intval($start_day)-intval($first_day_of_repeat);
						if ($diff>0)
						{
							$start_time->sub(new DateInterval( "P".$diff."D" ));
							$end_time->sub(new DateInterval( "P".$diff."D" ));
						}

					}

					//on met à jour les dates
					$carbon = Carbon::instance($start_time);
					$new_start_date = $carbon->toDateTimeString() ; 

					$carbon = Carbon::instance($end_time);
					$new_end_date = $carbon->toDateTimeString() ; 

				}
				else
				{

					//dans ce modele, repeatItems est une string
					$days = $data['repeatItems'];

					//modele de répétition mensuelle
					$jumpMonths =  (int)$data['repeatEach'];

					if ($days=='day_of_month') {

						//ex : le 3 du mois

						$startCarbon = Carbon::parse($start_date);
						$endCarbon = Carbon::parse($end_date);

						$new_start_date = $startCarbon->addMonths(intval($jumpMonths))->toDateTimeString();
						$new_end_date = $endCarbon->addMonths(intval($jumpMonths))->toDateTimeString();


					} else if ($days=='day_of_week') {

						//Ex : le 2e jeudi du mois

						//le numéro de la semaine 
						$startCarbon = Carbon::parse($start_date);
						$endCarbon = Carbon::parse($end_date);

						$diffInDays = $startCarbon->diffInDays( $endCarbon, false );

						$week_number = intval($startCarbon->weekOfMonth)-1; //car on se recalera déjà sur le 1er par next() 

						$start_day = $startCarbon->dayOfWeek; 
						$end_day = $endCarbon->dayOfWeek;
						
						$new_start_date = $startCarbon->startOfMonth()->addMonths(intval($jumpMonths))->next($start_day)->addWeeks(intval($week_number))->toDateTimeString();
						$new_end_date = $endCarbon->startOfMonth()->addMonths(intval($jumpMonths))->next($end_day)->addWeeks(intval($week_number))->toDateTimeString();
						
					}

				}

				////////////////////////////////

				if ($endType=='never') {

					$repeatable = true;

				} else if ($endType=='date') {

					$untill = Carbon::parse($data['endValue']);
					$nextStart = Carbon::parse($new_start_date); 

					if ( $nextStart->diffInDays( $untill, false ) >= 0 ) { //pas en valeur absolue !
						// Kidzou_Utils::log('endType = days, diff = '. $untill->diffInDays( $nextStart ), true);
						$repeatable = true;
					}
						

				} else {
	 				
	 				//on est forcément sur les occurences
	 				//endtype = occurences
					if ( $occurences>0 ) {
						$repeatable = true;
						$occurences--; //on décrémente les occurences, pas l'inverse...sinon ca ne se termine jamais
					}
						
				}	
					
			}

			if ($repeatable)
			{
				$events_meta['start_date'] 	= $new_start_date;
				$events_meta['end_date'] 	= $new_end_date;

				$events_meta['recurrence'] = array(
						"model" => $data['model'],
						"repeatEach" => (int)$data['repeatEach'],
						"repeatItems" => $data['repeatItems'], 
						"endType" 	=> $data['endType'],
						"endValue"	=> ($data['endType'] == 'date' ? $data['endValue'] : $occurences) 
					);

				$old_dates = array(
						'start_date' => $start_date,
						'end_date'	=> $end_date
					);

				if (!isset($past_dates[0]))
					$past_dates[0] = array();

				array_push($past_dates[0], $old_dates);

				$events_meta['past_dates'] = $past_dates[0];

				Kidzou_Admin::save_meta($event->ID, $events_meta, "kz_event_");	

				Kidzou_Utils::log( 'Event changed dates ['. $event->post_name .'] : new start_date = ' . $events_meta['start_date'] , true);

				// Kidzou_Utils::log($events_meta);
			}
			else
			{
				//plus besoin de ces posts s'ils ne sont pas recurrents

				$remove_cats 	= Kidzou_Utils::get_option('obsolete_events_remove_cats', array());	
				$add_cats 		= Kidzou_Utils::get_option('obsolete_events_add_cats', array());
				
				//inclure des catégories supplémentaires ou en supprimer
				if (($add_cats!=null && count($add_cats)>0) || 
					($remove_cats!=null && count($remove_cats)>0)) {

					//recuperer les id pre-affectés et faire un diff
					$term_list = wp_get_post_terms($event->ID, 'category', array("fields" => "ids"));
					
					$remove_ids = array_map( 'intval', $remove_cats );
					$remove_ids = array_unique( $remove_ids );

					$list_remove = implode(',', $remove_ids);

					$add_ids = array_map( 'intval', $add_cats );
					$add_ids = array_unique( $add_ids );

					$list_add = implode(',', $add_ids);

					$all_terms_ids = array_merge($term_list, $add_ids) ;
					$all_terms_ids = array_unique( $all_terms_ids );

					foreach ($remove_ids as $key => $value) {
						$index = array_search($value, $all_terms_ids, false);
						if ($index) {
							unset($all_terms_ids[$index]);
						}
							
					}

					$term_taxonomy_ids = wp_set_post_terms( $event->ID , $all_terms_ids, 'category', false ); //replace all cats by new list

					$list = implode(',', $all_terms_ids);

					if ( is_wp_error( $term_taxonomy_ids ) ) {
						Kidzou_Utils::log( 'Erreur dans affectation de cagtegories ['. $event->post_name .'] = ' .$list , true);
					} else {
						Kidzou_Utils::log( 'Nouvelles categories affectees pour  ['. $event->post_name .'] = '.$list , true);
					}

					//suppression des autres taxonomies
					$remove_other_taxos = Kidzou_Utils::get_option('obsolete_events_remove_taxonomies', array());
					
					foreach ($remove_other_taxos as $key => $value) {

						$res = wp_set_post_terms( $event->ID, array(), $value, false ); 

						if ( is_wp_error( $res ) ) {
							Kidzou_Utils::log( 'Erreur dans la suppression de la taxonomie '. $value, true);
						} else {
							Kidzou_Utils::log( 'Suppression de la taxonomie ' . $value, true);
						}
					}

				}	

				$unpublish 		= (bool)Kidzou_Utils::get_option('obsolete_events_unpublish', false);

				if ($unpublish)
				{
					$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $event->ID ) );

					clean_post_cache( $event->ID );
						
					$old_status = $event->post_status;
					$event->post_status = 'draft';
					wp_transition_post_status( 'draft', $old_status, $event );

					Kidzou_Utils::log( 'Unpublished ['. $event->post_name .']' , true);
				}

				//dans tous les cas on supprime l'event de la table Geo Data Store
				if (class_exists( 'sc_GeoDataStore' ))
				{	
					//simulation de la suppression de meta pou supprimer la ligne Geo Data Store
		   			$type = $event->post_type;
			   		$meta_key = 'kz_'.$type.'_location_latitude';
			   		$id = $event->ID;

			   		$mid = $wpdb->get_var( 
			   			"SELECT meta_id FROM $wpdb->postmeta WHERE post_id = $id AND meta_key = '$meta_key'"
			   		);

					sc_GeoDataStore::delete_postmeta($mid);

					Kidzou_Utils::log( 'Remove Entry from Geo Data Store ['. $event->post_name .']' , true);
				}
					
			}

		}

		Kidzou_Utils::log('------ / unpublish_obsolete_posts -------', true);
	}

    

} //fin de classe

?>