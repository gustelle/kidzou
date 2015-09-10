<?php

/*
Controller Name: Content
Controller Description: Permet de requeter les contenus du site par des filtres spécifiques Kidzou
Controller Author: Kidzou
*/


/**
 *
 * @todo : sécuriser l'accès aux API: 
 *			demander une clé par app (nonce valable pendant une durée indéfinie, jusqu'à révocation)
 *			vérifier ce nonce avant accès au contenu (revoir isPublicKey)
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
						'terms'			=> $terms,
						'permalink'		=> get_the_permalink(),
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
			'taxonomies' => $terms,
			'featured'	=> Kidzou_Featured::getFeaturedPosts()
		);
	}

	/**
	 * Contextual Related Posts
	 *
	 * @param post_id : le post pour lequel il faut rechercher des similarités
	 * @todo coupler ca à la geolocalisation pour ne retenir que les related qui sont pas trop éloignés
	 *
	 */
	public function get_related_posts(){

		global $json_api;

		$key = $json_api->query->key;
		$id = $json_api->query->post_id;

		if ( !Kidzou_API::isPublicKey($key) ) 
			$json_api->error("Cle invalide ");
		
		if ( !is_int($id) )  
			$json_api->error("post_id non reconnu");

		$results = array(); 

		if (function_exists('get_crp_posts_id'))
			$results = get_crp_posts_id();

		return array(
				'related'=> $results
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

	public static function validateDate($date, $format = 'Y-m-d H:i:s') {
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	}
}


?>