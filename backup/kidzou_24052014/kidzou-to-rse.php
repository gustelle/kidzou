<?php



// /**
//  * Tous les evenements qui se tiennent dans les 7 jours
//  *
//  * @param pageIndex for paging amongst the results
//  * @return $wpdb result set $events - Result set of the query
//  */
// function get_teaser_events_7days( $limit = 3 ) {

// 	return get_teaser_events_xdays(7,3);

// }

// /**
//  * undocumented function
//  *
//  * @return void
//  * @author 
//  **/
// function get_teaser_events_xdays( $days=7, $limit = 3 )
// {
// 	$isLimit = ($limit>0 ? true :false);

// 	global $wpdb;

// 	$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;
// 	//$daymax 	= $days-1; //on tient compte de aujourd'hui

// 	$eventQuery  = "SELECT * FROM $table_name WHERE start_date <= ADDDATE( CURDATE( ) , $days ) AND ( start_date >= now() OR end_date >= now() )  AND status='approved' ORDER BY featured DESC, start_date ASC ";
// 	$eventQuery .= $isLimit ? " LIMIT 0,$limit" : "";

// 	$events = $wpdb->get_results( $eventQuery );

// 	return $events;

// }

// /**
//  * Le tableau des evenements dans les 7 prochains jours, rangés par date
//  *
//  * @return void
//  * @author
//   *@deprecated 
//  **/
// function get_upcoming_events_7days( )
// {
// 	return get_upcoming_events_xdays(7);
// }

// /**
//  * Le tableau des evenements dans les 7 prochains jours, rangés par date
//  *
//  * @return void
//  * @author
//  * @deprecated
//  **/
// function get_upcoming_events_xdays( $days=7 )
// {
// 	//use transients
// 	// Get any existing copy of our transient data
// 	if ( false === ( $events = get_transient( 'kz_upcoming_events_'.$days.'days' ) ) ) {

// 		global $wpdb;

// 		$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;
// 		$events = array();
		
// 		$i = 0;
// 		while ($i<$days) {

// 	    	$eventQuery  = "SELECT *, ADDDATE( CURDATE( ) , $i ) as the_date FROM $table_name WHERE start_date <= ADDDATE( CURDATE( ) , $i ) AND ( start_date >= now() OR end_date >= now() ) AND status='approved' ORDER BY featured DESC, start_date ASC ";
// 			$res = $wpdb->get_results( $eventQuery );

// 			$the_date = $res[0]->the_date;

// 			if ($the_date!='')
// 				$events[$the_date] = $res;

// 			$i++;
// 		}

//   		set_transient( 'kz_upcoming_events_'.$days.'days', $events, 60 * 60 * 1 ); //expiration toutes les heures
// 	}

// 	return $events;
// }

// /**
//  * Le tableau des evenements à venir pour un client
//  *
//  * @return void
//  * @author
//  * @deprecated
//  **/
// function get_upcoming_events_by_customer( $id )
// {

// 	global $wpdb;

// 	$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;
// 	$events = array();

// 	if ($id!="" && is_int($id)){

// 		//$wpdb->show_errors();
// 		$eventQuery  = "SELECT * FROM $table_name WHERE customer=$id AND (start_date >= now() OR end_date >= now()) AND status='approved' ORDER BY featured DESC, start_date ASC ";
// 		$res = $wpdb->get_results( $eventQuery );
// 		//$wpdb->print_error();

// 		return $res;
// 	}

// 	return null;
	
// }


// function get_upcoming_events_7days_nogroup( )
// {
// 	return get_upcoming_events_xdays_nogroup(7);
// }

// function get_upcoming_events_xdays_nogroup( $days=7 )
// {
// 	global $wpdb;

// 	$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;
// 	$eventQuery  = "SELECT * FROM $table_name WHERE start_date <= ADDDATE( CURDATE( ) , $days ) AND ( start_date >= now() OR end_date >= now() ) AND status='approved' ORDER BY featured DESC, start_date ASC ";
// 	$res = $wpdb->get_results( $eventQuery );

// 	return $res;
// }


// /**
//  * get the events associated with a post (limit = 3 events per query)
//  *
//  * @return void
//  * @author
//  **/
// function get_upcoming_events_by_post($post_id)
// {

// 	//Retrouver la fiche du post
// 	// if ( false === ( $results = get_transient( 'kz_upcoming_events_by_post_'.$post_id ) ) ) {

// 		$connections_id  = get_post_meta( $post_id, 'kz_connections', true );

// 		$results = array();

// 		if ($connections_id!='')
// 		{
// 			global $wpdb;
// 			$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;
// 			$eventQuery  = "SELECT * FROM $table_name as r WHERE ( ( r.start_date >= NOW() AND r.start_date <= ADDDATE( CURDATE( ) , 14 ) ) OR ( r.end_date >= NOW() AND r.end_date <= ADDDATE( CURDATE( ) , 14 ) ) ) and r.connections_id='$connections_id' AND r.status='approved' ORDER BY r.featured DESC,r.start_date ASC LIMIT 0,3";
// 			$results = $wpdb->get_results( $eventQuery );
// 		}

// 		// set_transient( 'kz_upcoming_events_by_post_'.$post_id, $results, 60 * 60 * 8 ); //expiration toutes les 8 heures
// 	// }

// 	return $results;
// }

// /**
//  * retourne un Booléen qui indique si l'evenement se déroule aujourd'hui
//  *
//  * @return void
//  * @author
//  **/
// function is_event_today($event)
// {
// 	$now = time();

// 	if (strtotime( $event->start_date ) > $now)
// 		return false;
// 	else if (strtotime( $event->end_date ) < $now)
// 		return false;
// 	return true;
// }


// add_action( 'admin_init', 'kz_rse_admin_print_scripts' );
// function kz_rse_admin_print_scripts() {
//     wp_enqueue_style( 'thickbox' ); // Stylesheet used by Thickbox
//     wp_enqueue_script( 'thickbox' );
//     wp_enqueue_script( 'media-upload' );
// }

// /**
//  * pour des facilités de saisie à l'import, le texte du lien est figé...
//  * à l'import on a juste besoin d'entrer l'URL
//  *
//  * @return un lien formatté (title)[href]
//  * @author
//  **/
// function kz_rse_formatlink ($href)
// {
// 	if ($href==null || $href=='')
// 		return '';

// 	 return "(Plus d'info)[".$href."]";
// }

// /**
//  * 
//  *
//  * @return  
//  * @author http://yoast.com/smarter-upload-handling-wp-plugins/
//  **/

// function event_pre_upload($file){
// 	// echo 'event_pre_upload';
//     add_filter('upload_dir', 'event_upload_dir');
//     return $file;
// }

// /**
//  * 
//  *
//  * @return  
//  * @author http://yoast.com/smarter-upload-handling-wp-plugins/
//  **/

// function event_post_upload($fileinfo){
// 	// echo 'event_post_upload';
//     remove_filter('upload_dir', 'event_upload_dir');
//     return $fileinfo;
// }

// /**
//  * organisation des fichiers dans /events par /year/month/user_id 
//  *
//  * @return  
//  * @author http://yoast.com/smarter-upload-handling-wp-plugins/
//  **/
// function event_upload_dir($upload) {
// 	global $current_user;
// 	$upload['subdir']	= '/events' . $upload['subdir'] . "/" . $current_user->ID;
// 	$upload['path']		= $upload['basedir'] . $upload['subdir'];
// 	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
// 	return $upload;
// }


?>