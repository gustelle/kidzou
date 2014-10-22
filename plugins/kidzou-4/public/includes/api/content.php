<?php

/*
Controller Name: Content
Controller Description: Les contenus du site
Controller Author: Kidzou
*/

class JSON_API_Content_Controller {

	public function excerpts() {

		global $json_api;
		$key 	= $json_api->query->key;
		$date_from = $json_api->query->date_from;

		if (!$key) 
	    	$json_api->error("Votre clé n'est pas valide");

	  	$now   = new DateTime();

		if (!$date_from) {
			//prendre la date du jour par défaut
			$date_from = $now->format( 'Y-m-d H:i:s' );
		}
		
		//parser la date
		if (!self::validateDate($date_from, 'Y-m-d'))
			$json_api->error("Vous etes certain que la date est correcte (format YYYY-MM-DD) ?");

		//si la date est posterieure a la date du jour, on jette

		//si la date est trop lointaine, on jetter le user
		global $kidou_options;
		$max_days = $kidzou_options['excerpts_max_days']; 

		$dStart = new DateTime($date_from);
		$dNow = new DateTime();
	   	$dDiff = $dStart->diff($dNow);
	   	$diff = $dDiff->days;

		if (!self::validateDate($date_from, 'Y-m-d'))
			$json_api->error("Vous etes certain que la date est correcte (format YYYY-MM-DD) ?");

		global $wpdb;

		//qui est donc notre client ?
		$args = array(
			'posts_per_page' => 1,
			'post_type'	=> 'customer',
			'meta_key' => Kidzou_Customer::$meta_api_key,
			'meta_value' => $key
		);

		$the_query = new WP_Query( $args );

		$results = $the_query->get_posts();

		$customer = $results[0];

		//calculer le quota
		$quota = get_post_meta($customer->ID, Kidzou_Customer::$meta_api_quota,true);
		$usage = get_post_meta($customer->ID, Kidzou_Customer::$meta_api_usage,true);

		//et decrementer son utilisation
		if (!$usage || $usage=='' || intval($usage)<0)
			$usage = 0;

		if ( ($quota-$usage)<=0 )
			$json_api->error("Vous avez utilise votre quota pour cette API :-/");

		$usage++;

		$meta = array();
		$meta[Kidzou_Customer::$meta_api_usage] = $usage;

		self::save_meta($customer->ID, $meta);

		//requeter les extraits	

		return array(
			'customer' => $the_query->get_posts(),
			'date_from' => $date_from,
			'remaining_queries' => ($quota-$usage),
			// 'date' => $diff
		);

	}

	public static function validateDate($date, $format = 'Y-m-d H:i:s')
	{
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	}

	public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {

		if ($post_id==0)
			return;

		// Add values of $events_meta as custom fields
		foreach ($arr as $key => $value) { // Cycle through the $events_meta array!
			$pref_key = $prefix.$key; 
			// if( $post->post_type == 'revision' ) return; // Don't store custom data twice
			// $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
			$prev = get_post_meta($post_id, $pref_key, TRUE);
			// if ($pref_key=='kz_event_customer') echo $prev;
			if ($prev!='') { // If the custom field already has a value
				update_post_meta($post_id, $pref_key, $value);
			} else { // If the custom field doesn't have a value
				if ($prev=='') delete_post_meta($post_id, $pref_key);
				add_post_meta($post_id, $pref_key, $value, TRUE);
			}
			if(!$value) delete_post_meta($post_id, $pref_key); // Delete if blank
		}

	}

}




?>