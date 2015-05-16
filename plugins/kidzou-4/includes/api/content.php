<?php

/*
Controller Name: Content
Controller Description: Permet de requeter les contenus du site par des filtres spécifiques Kidzou
Controller Author: Kidzou
*/

class JSON_API_Content_Controller {


	/**
	 * Une liste de lieux référencés autour de coordonnées, dans un rayon donné
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

		$ids = $locator->getPostsNearToMeInRadius($latitude, $longitude, $radius);

		$pins = array();

		if (!empty($ids))
		{	
			global $post;

			foreach ($ids as $key=>$value) 
			{
				$post = get_post($value->post_id);
				setup_postdata($post);

				// Kidzou_Utils::log($post, true);

				$thumbnail = get_thumbnail( 100, 100, '', get_the_title() , get_the_title() , false );
				
				// $thumb = $thumbnail["thumb"];
				// $img = print_thumbnail( $thumb, $thumbnail["use_timthumb"], $post->post_title, 100, 100, '', false);

				array_push($pins, array(
						'latitude' => $value->latitude,
						'longitude'=> $value->longitude,
						'title'		=> get_the_title() ,
						'permalink' => get_the_permalink(),
						'thumbnail' => $thumbnail['thumb'],
						'id'		=> $value->post_id,
						'location'	=> Kidzou_GeoHelper::get_post_location($value->post_id),
						'distance'	=> $value->distance,
						'votes'		=> Kidzou_Vote::getVoteCount($value->post_id)
					));
				
			}

			wp_reset_postdata();
		}

		//finalement on incrémente
		// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

		return array(
			'places' => $pins	
		);
	}

	/**
	 * La liste des événements programmés 
	 * 
	 *
	 */
	public function events() {

		global $json_api;

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => Kidzou::post_types(),
		);


		$query = new Event_Query($args);
		$posts = $query->get_posts();
		$list = array();

		//attacher les meta
		foreach ($posts as $post) {
			$o = array('post'=>$post);
			$o['post_meta'] = Kidzou_Events::getEventDates($post->ID);
			array_push($list, $o);
		}

		//finalement on incrémente
		// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

		return array(
			'events' => $list
		);
	}

	/**
	 * La liste des contenus triés par recommandation 
	 * 
	 *
	 */
	public function recos() {

		global $json_api;

		$page 	= $json_api->query->page;
		$offset = $json_api->query->offset;

		if (!$json_api->query->page)
			$page = 0;

		if (!$json_api->query->offset)
			$offset = 0;

		if ( !is_numeric($page) || !is_numeric($offset)) 
			$json_api->error("Paramètres incorrects, offset et page doivent etre numériques");

		$args = array(
			'posts_per_page' => 20, 
			'post_type'      => Kidzou::post_types(),
			'page'	=> $page,
			'offset' => $offset
		);

		$query = new Vote_Query($args);

		$posts = $query->get_posts();
		$list = array();

		//attacher les meta
		foreach ($posts as $post) {
			$o = array('post'=>$post);
			$o['post_meta'] = array('reco_count' => Kidzou_Vote::getVoteCount($post->ID));
			array_push($list, $o);
		}

		//finalement on incrémente
		// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

		return array(
			'votes' => $list	
		);
	}

	/**
	 * La galerie de photos d'un post
	 *
	 * @param id 
	 *
	 */
	public function get_content_without_gallery() {

		global $json_api;
		
		$id = $json_api->query->id;

		// $gallery = get_post_gallery($id, false);

		$content = get_post_field('post_content', $id);

       	$content = Kidzou_Utils::strip_shortcode_gallery($content);

       	$content = str_replace( ']]>', ']]&gt;',   apply_filters('the_content', $content)); 

       	//finalement on incrémente
		// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

       	return array(
			'content' => $content	
		);

	}

	/**
	 * La galerie de photos d'un post
	 *
	 */
	public function get_post_gallery() {

		global $json_api;
		
		$id = $json_api->query->id;

		$gallery = get_post_gallery( $id, false )	;

		$ids = explode( ",", $gallery['ids'] );

		$images = [];

		foreach( $ids as $id ) {

			$images[] = array(
				'url' => wp_get_attachment_url( $id ),
				'comments' => get_comments(array('post_id'=>$id, 'status'=>'approve'))
			);

		} 

		//finalement on incrémente
		// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

		return array(
			'gallery' => $images
		);
	}

	/**
	 * Distance  à un lieu identifié par un ID, à partir de latitude et longitude données
	 *
	 */
	public function distance() {

		global $json_api;

		$id = $json_api->query->id;
		$latitude 	= $json_api->query->latitude;
		$longitude 	= $json_api->query->longitude;

		if ( !is_numeric($latitude) ||  !is_numeric($longitude)) 
			$json_api->error("Coordonnees invalides");

		if ( !is_numeric($id) || $id<1 ) 
			$json_api->error("ID de post invalide");
		
		$locator = new Kidzou_Geolocator();

		$distance = $locator->getPostDistanceInKmById($latitude, $longitude, $id);

		//finalement on incrémente
		// Kidzou_API::incrementUsage(Kidzou_Utils::hash_anonymous(), __FUNCTION__ );

		return array(
			'distance' => $distance	
		);
	}

	/**
	 * Retourne l'URL de l'avatar d'un commentaire
	 *
	 * @param comment_id 
	 *
	 */
	public function get_avatar() {

		global $json_api;
		
		$id = $json_api->query->comment_id;

		if ( !is_numeric($id) ) 
			$json_api->error("ID de commentaire invalide");

		$comment = get_comment( $id ); 

		//@see http://wordpress.stackexchange.com/questions/59442/how-do-i-get-the-avatar-url-instead-of-an-html-img-tag-when-using-get-avatar
		if (function_exists('get_avatar_url'))  //since WP 4.2
			$url = get_avatar_url($comment->comment_author_email);
		else {
			preg_match("/src=['\"](.*?)['\"]/i", $get_avatar, $matches);
			$url = $matches[1];
		}

       	return array(
			'avatar' => $url	
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