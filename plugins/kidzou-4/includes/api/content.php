<?php

/*
Controller Name: Content
Controller Description: Permet de requeter les contenus du site par des filtres spécifiques Kidzou
Controller Author: Kidzou
*/

class JSON_API_Content_Controller {

	// /**
	//  * tous les lieux référencés
	//  * 
	//  * @deprecated
	//  *
	//  * @param latitude
	//  * @param longitude
	//  * @param radius : rayon de recherche
	//  *
	//  */
	// public function get_all_places() {

	// 	global $json_api;

	// 	$args = array(
	// 			'post_type' => 'post',
 //        		'posts_per_page' => -1,
	// 		);
		
	// 	global $post;
	// 	$query = new Geo_Query($args); 
	// 	$posts = $query->get_posts(); //Kidzou_Utils::log($posts,true);
	// 	$pins = array();

	// 	if (!empty($posts))
	// 	{	

	// 		foreach ($posts as $post) 
	// 		{
	// 			// $post = get_post($value->post_id);
	// 			setup_postdata($post);

	// 			// Kidzou_Utils::log($p,true);

	// 			// $thumbnail = get_thumbnail( 100, 100, '', get_the_title() , get_the_title() , false );
				
	// 			$is_event = Kidzou_Events::isTypeEvent(get_the_ID());

	// 			//exit les events non actifs
	// 			if ($is_event && !Kidzou_Events::isEventActive(get_the_ID()))
	// 				continue;
				
	// 			array_push($pins, array(
	// 					'title'		=> get_the_title() ,
	// 					'permalink' => get_the_permalink(),
	// 					'thumbnail' => Kidzou_Utils::get_post_thumbnail(get_the_ID(), 'large'),
	// 					'id'		=> get_the_ID(),
	// 					'location'	=> Kidzou_GeoHelper::get_post_location(get_the_ID()),
	// 					'votes'		=> Kidzou_Vote::getVoteCount(get_the_ID()),
	// 					'comments_count'	=> wp_count_comments(get_the_ID())->approved,
	// 					'is_event' 	=> $is_event,
	// 					'event_dates' => ($is_event ? Kidzou_Events::getEventDates(get_the_ID()) : array())
	// 				));
				
	// 		}
	// 		wp_reset_postdata();
			
	// 	}


	// 	return array(
	// 		'places' => $pins,
	// 	);
	// }

	/**
	 * tous les lieux référencés et toutes ses metadata
	 *
	 * @param key : la clé d'API publique 
	 *
	 */
	public function get_all_content() {

		global $json_api;

		$key = $json_api->query->key;

		if ( !Kidzou_API::isPublicKey($key)) $json_api->error("Cle invalide ");

		$args = array(
				'post_type' => 'post',
				'post_status' => 'publish',
        		'posts_per_page' => -1,
			);
		
		global $post;
		$query = new WP_Query($args); 
		$posts = $query->get_posts(); 
		$places = array();//

		if (!empty($posts))
		{	

			foreach ($posts as $post) 
			{
				// $post = get_post($value->post_id);
				setup_postdata($post);

				$is_event = Kidzou_Events::isTypeEvent(get_the_ID());

				//exit les events non actifs
				if ($is_event && !Kidzou_Events::isEventActive(get_the_ID()))
					continue;
				
				//avatars
				$comments = get_comments(array('post_id'=> get_the_ID(), 'status' => 'approve'));
				foreach ($comments as $comment)
					$comment->avatar = Kidzou_Utils::get_comment_avatar($comment);

				//gallery et contenu "propre" sans gallery
				$content = Kidzou_Utils::strip_shortcode_gallery($post->post_content);
       			$post->post_content = $content;

       			$gallery = get_post_gallery( get_the_ID(), false );
				$images = [];
				if ($gallery && count($gallery)>0)
				{
					$ids = explode( ",", $gallery['ids'] );
					foreach( $ids as $the_id ) {
						$images[] = array(
							'base_url' => wp_upload_dir()['baseurl'],
							'attachment'	=> get_post($the_id),
							'attachment_metadata' => wp_get_attachment_metadata( $the_id ),
						);

					} 
				}
				
				array_push($places, array(
						'post'			=> $post,
						'location'		=> Kidzou_GeoHelper::get_post_location(get_the_ID()),
						'votes'			=> Kidzou_Vote::getVoteCount(get_the_ID()),
						'is_event' 		=> $is_event,
						'event_dates' 	=> ($is_event ? Kidzou_Events::getEventDates(get_the_ID()) : array()),
						'thumbnail'		=> Kidzou_Utils::get_post_thumbnail(get_the_ID(), 'large'),
						'comments' 		=> $comments,
						'gallery'		=> $images
					));
				
			}
			wp_reset_postdata();
			
		}

		return array(
			'places' => $places,
		);
	}

	/**
	 * envoi d'une photo au format base64 via une API et dépot d'une photo pour validation Kidzou
	 *
	 * @param key : la clé d'API publique 
	 *
	 */
	public function add_photo() {

		global $json_api;

		$key = $json_api->query->key;

		if ( !Kidzou_API::isPublicKey($key)) $json_api->error("Cle invalide ");

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( empty($_POST["photo"]) 		|| 
			 empty($_POST["username"]) 		|| 
			 empty($_POST["post_id"]) 		||
			 empty($_POST["photo_comment"]) ||
			 empty($_POST["photo_title"])  ) $json_api->error("Données à soumettre non détectées");

		if (class_exists('GFForms')) {

			// $entry = $_POST["entry"];
			$form_id = Kidzou_Utils::get_option('gf_form_id');
			$form = GFAPI::get_form($form_id);

			//mapper les données
			$input_values = [];
			$input_values[Kidzou_Utils::get_option('gf_field_photo')] 	= $_POST['photo'];
			$input_values[Kidzou_Utils::get_option('gf_field_user_id')] = $_POST['username'];
			$input_values[Kidzou_Utils::get_option('gf_field_post_id')] = $_POST["post_id"];
			$input_values[Kidzou_Utils::get_option('gf_field_comment')] = $_POST['photo_comment'];
			$input_values[Kidzou_Utils::get_option('gf_field_title')] 	= $_POST['photo_title'];

			//soumettre le formulaire			
			$results = GFAPI::submit_form($form_id, $input_values);

			Kidzou_Utils::log(array('add_photo/submit_form' => $results), true);

			return array(
				'result' => $results
			);
		} 

		return array(
			'message' => 'Gravity Forms is not available and is required for this API function',
			'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD']
		);

	}


	// /**
	//  * Une liste de lieux référencés autour de coordonnées, dans un rayon donné
	//  *
	//  * 
	//  * @param latitude
	//  * @param longitude
	//  * @param radius : rayon de recherche
	//  *
	//  */
	// public function places() {

	// 	global $json_api;
	// 	$latitude 	= $json_api->query->latitude;
	// 	$longitude 	= $json_api->query->longitude;
	// 	$radius = $json_api->query->radius;

	// 	if ( !is_numeric($latitude) ||  !is_numeric($longitude)) 
	// 		$json_api->error("Coordonnees invalides");

	// 	if ( !is_numeric($radius) || floatval($radius)<0 ) 
	// 		$json_api->error("Rayon de recherche invalide");
		
	// 	$locator = new Kidzou_Geolocator();

	// 	$ids = $locator->getPostsNearToMeInRadius($latitude, $longitude, $radius, array('post'));

	// 	$pins = array();

	// 	if (!empty($ids))
	// 	{	
	// 		global $post;

	// 		foreach ($ids as $key=>$value) 
	// 		{
	// 			$post = get_post($value->post_id);
	// 			setup_postdata($post);

	// 			// $thumbnail = get_thumbnail( 100, 100, '', get_the_title() , get_the_title() , false );
				
	// 			$is_event = Kidzou_Events::isTypeEvent($value->post_id);
				
	// 			array_push($pins, array(
	// 					// 'latitude' => $value->latitude,
	// 					// 'longitude'=> $value->longitude,
	// 					'title'		=> get_the_title() ,
	// 					'permalink' => get_the_permalink(),
	// 					'thumbnail' => Kidzou_Utils::get_post_thumbnail($value->post_id, 'large'),
	// 					'id'		=> $value->post_id,
	// 					'location'	=> Kidzou_GeoHelper::get_post_location($value->post_id),
	// 					'distance'	=> $value->distance,
	// 					'votes'		=> Kidzou_Vote::getVoteCount($value->post_id),
	// 					'comments_count'	=> wp_count_comments($value->post_id)->approved,
	// 					'is_event' 	=> $is_event,
	// 					'event_dates' => ($is_event ? Kidzou_Events::getEventDates($value->post_id) : array())
	// 				));
				
	// 		}

	// 		wp_reset_postdata();
	// 	}


	// 	return array(
	// 		'places' => $pins,
	// 		'radius'	=> $radius
	// 	);
	// }


	// /**
	//  * La liste des événements programmés 
	//  * @deprecated
	//  *
	//  */
	// public function events() {

	// 	global $json_api;

	// 	$args = array(
	//       'posts_per_page' => -1, 
	//       'post_status' => 'publish',
	//       'is_archive'	=> false,
	//     );


	// 	$query = new Event_Query($args);
	// 	$posts = $query->get_posts();
	// 	$list = array();

	// 	//temp pour débug
	// 	// $sql = $query;

	// 	//attacher les meta
	// 	foreach ($posts as $post) {
	// 		$o = array('post'=>$post);
	// 		$o['post_meta'] = array(
	// 			"dates" => Kidzou_Events::getEventDates($post->ID),
	// 			"votes" => Kidzou_Vote::getVoteCount($post->ID)
	// 		);
	// 		// $thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'medium' );
	// 		// $o['thumbnail'] = $thumb['0'];
	// 		$o['thumbnail'] = Kidzou_Utils::get_post_thumbnail($post->ID, 'medium');
	// 		array_push($list, $o);
	// 	}

	// 	return array(
	// 		'events' => $list,
	// 		// 'sql' => $sql
	// 	);
	// }

	// /**
	//  * La liste des contenus triés par recommandation 
	//  * @deprecated
	//  *
	//  */
	// public function recos() {

	// 	global $json_api;

	// 	$page 	= $json_api->query->page;
	// 	$offset = $json_api->query->offset;

	// 	if (!$json_api->query->page)
	// 		$page = 0;

	// 	if (!$json_api->query->offset)
	// 		$offset = 0;

	// 	if ( !is_numeric($page) || !is_numeric($offset)) 
	// 		$json_api->error("Paramètres incorrects, offset et page doivent etre numériques");

	// 	$args = array(
	// 		'posts_per_page' => 20, 
	// 		'post_type'      => Kidzou::post_types(),
	// 		'page'	=> $page,
	// 		'offset' => $offset
	// 	);

	// 	$query = new Vote_Query($args);

	// 	$posts = $query->get_posts();
	// 	$list = array();

	// 	//attacher les meta
	// 	foreach ($posts as $post) {
	// 		$o = array('post'=>$post);
	// 		$o['post_meta'] = array('reco_count' => Kidzou_Vote::getVoteCount($post->ID));
	// 		array_push($list, $o);
	// 	}

	// 	//finalement on incrémente
	// 	// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

	// 	return array(
	// 		'votes' => $list	
	// 	);
	// }

	// /**
	//  * La galerie de photos d'un post
	//  * @deprecated
	//  *
	//  * @param id 
	//  *
	//  */
	// public function get_content_without_gallery() {

	// 	global $json_api;
		
	// 	$id = $json_api->query->id;

	// 	// $gallery = get_post_gallery($id, false);

	// 	$content = get_post_field('post_content', $id);

 //       	$content = Kidzou_Utils::strip_shortcode_gallery($content);

 //       	$content = str_replace( ']]>', ']]&gt;',   apply_filters('the_content', $content)); 

 //       	//finalement on incrémente
	// 	// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

 //       	return array(
	// 		'content' => $content	
	// 	);

	// }

	// /**
	//  * La galerie de photos d'un post
	//  * @deprecated
	//  *
	//  */
	// public function get_post_gallery() {

	// 	global $json_api;
		
	// 	$id = $json_api->query->id;

	// 	$gallery = get_post_gallery( $id, false );

	// 	$images = [];

	// 	if ($gallery && count($gallery)>0)
	// 	{
	// 		$ids = explode( ",", $gallery['ids'] );

	// 		foreach( $ids as $the_id ) {

	// 			$images[] = array(
	// 				'image_base' => wp_upload_dir()['baseurl'],
	// 				'post' => get_post( $the_id ),
	// 				'meta' => wp_get_attachment_metadata( $the_id ),
	// 				// 'comments' => get_comments(array('post_id'=>$id, 'status'=>'approve'))
	// 			);

	// 		} 
	// 	}

	// 	return array(
	// 		'gallery' => $images
	// 	);
	// }

	// /**
	//  * Distance  à un lieu identifié par un ID, à partir de latitude et longitude données
	//  *
	//  */
	// public function distance() {

	// 	global $json_api;

	// 	$id = $json_api->query->id;
	// 	$latitude 	= $json_api->query->latitude;
	// 	$longitude 	= $json_api->query->longitude;

	// 	if ( !is_numeric($latitude) ||  !is_numeric($longitude)) 
	// 		$json_api->error("Coordonnees invalides");

	// 	if ( !is_numeric($id) || $id<1 ) 
	// 		$json_api->error("ID de post invalide");
		
	// 	$locator = new Kidzou_Geolocator();

	// 	$distance = $locator->getPostDistanceInKmById($latitude, $longitude, $id);

	// 	//finalement on incrémente
	// 	// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

	// 	return array(
	// 		'distance' => $distance	
	// 	);
	// }

	// /**
	//  * Retourne l'URL de l'avatar d'un commentaire
	//  *
	//  * @param comment_id 
	//  *
	//  */
	// public function get_avatar() {

	// 	global $json_api;
		
	// 	$id = $json_api->query->comment_id;

	// 	if ( !is_numeric($id) ) 
	// 		$json_api->error("ID de commentaire invalide");

	// 	$comment = get_comment( $id ); 

	// 	$url = Kidzou_Utils::get_comment_avatar($comment);

 //       	return array(
	// 		'avatar' => $url	
	// 	);

	// }

	/**
	 * fournit la liste des extraits de tous les contenus produits depuis une date donnée 
	 *
	 * @todo : API 'my_content'
	 */
	public function excerpts() {

		global $json_api;
		$key 	= $json_api->query->key;
		$date_from = $json_api->query->date_from;

	  	$now   = new DateTime();

		//parser la date
		if (!self::validateDate($date_from, 'Y-m-d'))
			$json_api->error("Vous etes certain que la date est correcte (format YYYY-MM-DD) ?");

		
		//si la date est trop lointaine, on jetter le user
		$max_days = Kidzou_Utils::get_option('excerpts_max_days', 1);

		$dStart = new DateTime($date_from);
		$dNow = new DateTime();
	   	$dDiff = $dStart->diff($dNow);
	   	$diff = $dDiff->days;

	   	// Kidzou_Utils::log('API/excerpts : ' . $diff);

		if (intval($diff) > intval($max_days))
			$json_api->error("Vous ne pouvez pas remonter aussi loin dans le temps...");

		if ( !Kidzou_API::isQuotaOK($key, __FUNCTION__ ) )
			$json_api->error("Vous avez utilise votre quota pour cette API :-/");

		// on repart de la veille car la requete after part de 23:59:59
		$dStart->sub(new DateInterval('P1D'));
		$date = $dStart->format('Y-m-d') ;
		$tokens = explode("-", $date);

		$args = array(
			'date_query' => array(
				'after' => array(
					'year'  => $tokens[0],
					'month' => $tokens[1],
					'day'   => $tokens[2],
				),
			),
			'posts_per_page' => -1,
			'post_type' => 'post'
		);
		$query = new WP_Query( $args );

		$excertps = $query->get_posts();

		$results = array();

		global $post;
		foreach ($excertps as $post) {

			setup_postdata($post);
			$dates = Kidzou_Events::getEventDates($post->ID);
			$location = Kidzou_GeoHelper::get_post_location($post->ID);
			$author = get_the_author();
			$publish_date = get_the_date('Y-m-d');
			$excerpt = get_the_excerpt();
			$permalink = get_permalink();
			
			$results[] = array(
					"id" => $post->ID,
					"post_title" => $post->post_title,
					"author" 	=> $author,
					"publish_date" => $publish_date,
					"excerpt" => $excerpt,
					"permalink" => $permalink,
					"event_dates" => $dates,
					"location" => $location,
				);

		}

		wp_reset_postdata();
		wp_reset_query();

		//finalement on incrémente
		Kidzou_API::incrementUsage($key, __FUNCTION__ );

		return array(
			'posts' => $results	,
		);

	}

	public static function validateDate($date, $format = 'Y-m-d H:i:s')
	{
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	}



}




?>