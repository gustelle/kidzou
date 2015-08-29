<?php

/*
Controller Name: Content
Controller Description: Permet de requeter les contenus du site par des filtres spécifiques Kidzou
Controller Author: Kidzou
*/

class JSON_API_Content_Controller {

	/**
	 * tous les lieux référencés et toutes ses metadata
	 *
	 * @param key : la clé d'API publique 
	 *
	 */
	public function get_place() {

		global $json_api;

		$key = $json_api->query->key;
		$id = $json_api->query->post_id;

		if ( !Kidzou_API::isPublicKey($key)) $json_api->error("Cle invalide ");
		
		global $post;
		$post = get_post($id);

		$is_event 		= Kidzou_Events::isTypeEvent($id);
		$is_featured  	= Kidzou_Featured::isFeatured($id);

		//exit les events non actifs
		if ($is_event && !Kidzou_Events::isEventActive($id))
			continue;

		//terms 
		$terms = wp_get_post_terms( $id, Kidzou::get_taxonomies(), array("fields" => "all") );
		
		//avatars
		$comments = get_comments(array('post_id'=> $id, 'status' => 'approve'));
		foreach ($comments as $comment)
			$comment->avatar = Kidzou_Utils::get_comment_avatar($comment);

		//gallery et contenu "propre" sans gallery
		$content = Kidzou_Utils::strip_shortcode_gallery($post->post_content);
			$post->post_content = $content;

		$gallery = get_post_gallery( $id, false );
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
		
		$place = array(
				'post'			=> $post,
				'location'		=> Kidzou_GeoHelper::get_post_location($id),
				'votes'			=> Kidzou_Vote::getVoteCount($id),
				'is_event' 		=> $is_event,
				'event_dates' 	=> ($is_event ? Kidzou_Events::getEventDates($id) : array()),
				'thumbnail'		=> Kidzou_Utils::get_post_thumbnail($id, 'large'),
				'comments' 		=> $comments,
				'gallery'		=> $images,
				'is_featured'	=> $is_featured,
				'terms'			=> $terms 
			);

		// Kidzou_Utils::log('get_place', $place);

		return array(
			'place' => $place
		);
	}



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

				$is_event 		= Kidzou_Events::isTypeEvent(get_the_ID());
				$is_featured  	= Kidzou_Featured::isFeatured(get_the_ID());

				//exit les events non actifs
				if ($is_event && !Kidzou_Events::isEventActive(get_the_ID()))
					continue;

				//terms 
				$terms = wp_get_post_terms( get_the_ID(), Kidzou::get_taxonomies(), array("fields" => "all") );
				
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
						'gallery'		=> $images,
						'is_featured'	=> $is_featured,
						'terms'			=> $terms
					));
				
			}
			wp_reset_postdata();
			
		}

		$taxonomies = Kidzou::get_taxonomies();

		//Transformation de get_terms en array() pour lecture coté client
		//car le tableau renvoyé est un Objet JSON {} et par un array !
		$the_terms = get_terms( $taxonomies, $args);
		$terms = array();
		foreach ($the_terms as $term)  {
			array_push($terms, $term);
		}

		return array(
			'places' => $places,
			'terms' => $terms,
			'taxonomies' => $terms
		);
	}

	// /**
	//  * Toutes les taxonomies et leurs enfants utilisées pour classifier le contenu
	//  *
	//  * @param key : la clé d'API publique 
	//  *
	//  */
	// public function get_terms() {

	// 	global $json_api;

	// 	$key = $json_api->query->key;

	// 	if ( !Kidzou_API::isPublicKey($key)) $json_api->error("Cle invalide ");

	// 	$taxonomies = Kidzou::get_taxonomies();
		
	// 	$terms = get_terms( $taxonomies, $args);

	// 	return array(
	// 		'terms' => $terms,
	// 	);
	// }

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
		
		$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		$data = json_decode($HTTP_RAW_POST_DATA,true);

		$results = array();

		if ( !isset($data['input_values']) ) $json_api->error("Données manquantes");

		if (class_exists('GFForms')) {

			// $entry = $_POST["entry"];
			$form_id = Kidzou_Utils::get_option('gf_form_id');
			$form = GFAPI::get_form($form_id);

			//mapper les données
			$post_data = $data['input_values'];
			$input_values = [];
			$input_values['input_'.Kidzou_Utils::get_option('gf_field_image_base64')] 	= $post_data['photo'];
			$input_values['input_'.Kidzou_Utils::get_option('gf_field_user_id')] = $post_data['username'];
			$input_values['input_'.Kidzou_Utils::get_option('gf_field_post_id')] = $post_data["post_id"];
			$input_values['input_'.Kidzou_Utils::get_option('gf_field_comment')] = $post_data['photo_comment'];
			$input_values['input_'.Kidzou_Utils::get_option('gf_field_title')] 	= $post_data['photo_title'];

			//soumettre le formulaire			
			$results = GFAPI::submit_form( absint($form_id) , $input_values);

		} 

		return array(
			'results' => $results
		);

	}

	// /**
	//  * envoi d'une photo au format base64 via une API et dépot d'une photo pour validation Kidzou
	//  *
	//  * @param key : la clé d'API publique 
	//  *
	//  */
	// public function test_photo() {

	// 	global $json_api;

	// 	if (class_exists('GFForms')) {

	// 		// $entry = $_POST["entry"];
	// 		$form_id = Kidzou_Utils::get_option('gf_form_id');
	// 		$form = GFAPI::get_form($form_id);

	// 		//mapper les données
	// 		// $post_data = $data['input_values'];
	// 		$input_values = [];
	// 		$input_values['input_'.Kidzou_Utils::get_option('gf_field_photo')] 	= '"data:image/jpeg;base64,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';
	// 		$input_values['input_'.Kidzou_Utils::get_option('gf_field_user_id')] = "guillaume";
	// 		$input_values['input_'.Kidzou_Utils::get_option('gf_field_post_id')] = "13936";
	// 		$input_values['input_'.Kidzou_Utils::get_option('gf_field_comment')] = "";
	// 		$input_values['input_'.Kidzou_Utils::get_option('gf_field_title')] 	= null;

	// 		//soumettre le formulaire			
	// 		$results = \GFAPI::submit_form( absint($form_id) , $input_values);

	// 	} 

	// 	return array(
	// 		// 'message' => 'Gravity Forms is not available and is required for this API function',
	// 		'results' => $results
	// 	);

	// }


	/**
	 * Une liste de lieux référencés autour de coordonnées, dans un rayon donné
	 *
	 * 
	 * @param latitude
	 * @param longitude
	 * @param radius : rayon de recherche
	 *
	 */
	public function places() {

		global $json_api;
		$latitude 	= $json_api->query->latitude;
		$longitude 	= $json_api->query->longitude;
		$radius = $json_api->query->radius;

		if ( !is_numeric($latitude) ||  !is_numeric($longitude)) 
			$json_api->error("Coordonnees invalides");

		if ( !is_numeric($radius) || floatval($radius)<0 ) 
			$json_api->error("Rayon de recherche invalide");
		
		$locator = new Kidzou_Geolocator();

		$ids = $locator->getPostsNearToMeInRadius($latitude, $longitude, $radius, array('post'));

		$pins = array();

		if (!empty($ids))
		{	
			global $post;

			foreach ($ids as $key=>$value) 
			{
				$post = get_post($value->post_id);
				setup_postdata($post);

				// $thumbnail = get_thumbnail( 100, 100, '', get_the_title() , get_the_title() , false );
				
				$is_event = Kidzou_Events::isTypeEvent($value->post_id);
				$is_featured = Kidzou_Featured::isFeatured($value->post_id);
								//terms 
				$terms = wp_get_post_terms( get_the_ID(), Kidzou::get_taxonomies(), array("fields" => "all") );

				
				array_push($pins, array(
						// 'latitude' => $value->latitude,
						// 'longitude'=> $value->longitude,
						'title'		=> get_the_title() ,
						'permalink' => get_the_permalink(),
						'thumbnail' => Kidzou_Utils::get_post_thumbnail($value->post_id, 'large'),
						'id'		=> $value->post_id,
						'location'	=> Kidzou_GeoHelper::get_post_location($value->post_id),
						'distance'	=> $value->distance,
						'votes'		=> Kidzou_Vote::getVoteCount($value->post_id),
						'comments_count'	=> wp_count_comments($value->post_id)->approved,
						'is_event' 	=> $is_event,
						'event_dates' => ($is_event ? Kidzou_Events::getEventDates($value->post_id) : array()),
						'is_featured' => $is_featured,
						'terms' => $terms
					));
				
			}

			wp_reset_postdata();
		}


		return array(
			'places' => $pins,
			'radius'	=> $radius
		);
	}



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