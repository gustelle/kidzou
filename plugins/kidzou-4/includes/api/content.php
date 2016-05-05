<?php

/*
Controller Name: Content
Controller Description: Permet de requeter les contenus du site par des filtres spécifiques Kidzou
Controller Author: Kidzou
*/


/**
 *
 * permet la lecture / écriture de contenus par API
 *
 * @package Kidzou
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 * @todo : sécuriser l'accès aux API: 
 *			demander une clé par app (nonce valable pendant une durée indéfinie, jusqu'à révocation)
 *			vérifier ce nonce avant accès au contenu (revoir isPublicKey)
 */
class JSON_API_Content_Controller {

	/**
	 * Spécifique Kidzou pour créer un post en tenant compte de l'adresse, du client, et plus généralement de toutes les meta associées
	 * Remarque : on pourrait utiliser la methode standard create_post de JSON API mais il faudrait créer séparément les méta
	 *
	 * Il faut avoir les droits admin pour utiliser cette méthode et passer :
	 * * soit une clé d'API 
	 * * soit un nonce
	 * 
	 *
	 */
	public function create_post() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('content', 'create_post');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");
		
		if ( !Kidzou_Utils::current_user_can('can_edit_post') ) $json_api->error("Vous n'avez pas les droits suffisants");

		if ( !isset($_POST['data']) ) $json_api->error("l'élement 'data' est attendu en parametre POST");

		//attention aux différentes permissions pour créer un contenu
		$is_author 		= Kidzou_Utils::current_user_can('can_edit_customer') ;
		$feedback 		= array();

		$data 			= $_POST['data'];

		$titre 			= $data['titre'];
		$description 	= $data['description'];
		$adresse 		= $data['adresse'];
		$infos 			= $data['infos'];
		$name 			= $titre; 

		//contact info
		$tel 	=	$data['contact']['tel'];
		$web 	=	$data['contact']['web'];

		//dates d'évenement
		$start_date = $data['dates']['start_date'];
		$end_date	= (isset($data['dates']['end_date']) ? $data['dates']['end_date'] : '');

		$adresseRedresseeCorrecte = false;

		if ( isset($data['adresse']) ) {

			$location = $data['adresse'];

			$street 	= $location['street'];
			$city 		= $location['city'];
			$postalCode = $location['zip'];
			$lat = $location['lat'];
			$lng = $location['lng'];

			//on va surcharger le nom du customer
			$name = $location['name'];

			$location_address = ($street.$postalCode.$city=='' ? '' : $street.', '.$postalCode.' '.$city);

			//généralement quand l'adresse n'est pas bien redréssée le pays est 'US' 
			//de toute facon sur Kidzou on utilise des adresses en 'FR' ou 'BE'
			$adresseRedresseeCorrecte = ($location['country']=='FR' || $location['country']=='BE');
		}
		
		//récupérer le template de contenu à ajouter
		$template_append 	= Kidzou_Utils::get_option('import_content_append');

		//l'auteur est soit recupéré en param à condition que le user courant soit suffisamment capé
		//sinon, on récupère le user courant
		//sinon, il s'agit d'un cas d'import par API externe, on prend le user en option kidzou
		$author_id = -1;
		if ( $is_author && isset($_POST['author_id']) ) {
			$author_id = intval($_POST['author_id']);
		} else {
			//si il n'y pas de user (ex: import depuis un plugin chrome) cette fonction renvoie "0"
			$author_id = get_current_user_id();
		}

		//recuperer le user "KidzouTeam" si aucun author n'est détecté
		if (!$author_id>0) {
			$author_id 	= Kidzou_Utils::get_option('import_author_id');
			Kidzou_Utils::log('Import externe, user d\'import : '. $author_id, true);
		}

		//créer le post 
		$post_id = wp_insert_post(
			array(
				'post_author'		=>	$author_id,
				'post_title'		=>	wp_strip_all_tags($titre),
				'post_content'		=>  $description.'<br/>'.$infos.'<br/>'.$template_append,
				'post_status'		=>	'draft',
				'post_type'			=>	'post',
			)
		);

		//associer le lieu
		if ($adresseRedresseeCorrecte) {

			$ret = Kidzou_Geoloc::set_location(
				$post_id, 
				$name, 
				$location_address, 
				$web, 
				$tel, 
				$city, 
				$lat, 
				$lng );

			if (is_wp_error( $ret )) {
				$feedback[] = $ret->get_error_message();
			}
		}

		//positionner les dates
		if ($start_date!==null && $start_date!=='' ) { //end_date peut être nulle on s'en fout
			//check du format 
			if ($end_date==null || $end_date=='')
				$end_date = $start_date;

			$date_s = DateTime::createFromFormat('Y-m-d H:i:s', $start_date);
			$date_e = DateTime::createFromFormat('Y-m-d H:i:s', $end_date);

			if (!$date_s || !$date_e) {
			    $json_api->error("Format de date invalide");
			} else {
				Kidzou_Events::setEventDates($post_id, $start_date, $end_date); //on ne gere pas encore les récurrences
			}
		}

		$customer_id = -1;

		//on créé le customer si le user est suffisamment capé
		if ($is_author) {

			//créer le customer si le user à les bons droits
			// if (Kidzou_Utils::current_user_is('author')) {
			$customer_id = wp_insert_post(
				array(
					'post_author'		=>	$author_id,
					'post_title'		=>	wp_strip_all_tags($name),
					'post_status'		=>	'publish', //pas de pb pour le rendre public, non exposé au public
					'post_type'			=>	'customer'
				)
			);
			//associer le post au customer
			$ids = array();
			$ids[] = $post_id;
			Kidzou_Customer::setPosts($customer_id, $ids);

			//associer l'adresse au customer, 
			//elle sera transitive sur le post par association du client au post
			if ($adresseRedresseeCorrecte) {

				$ret = Kidzou_Geoloc::set_location(
					$customer_id, 
					$name, 
					$location_address, 
					$web, 
					$tel, 
					$city, 
					$lat, 
					$lng );

				if (is_wp_error( $ret )) {
					$feedback[] = $ret->get_error_message();
				}
			}
		}

		return array(
			'post_id'	=> $post_id,
			'customer_id'=> $customer_id,
			'post_edit_url'=> admin_url( 'post.php?post='.$post_id.'&action=edit' ),
			'customer_edit_url'=> admin_url( 'post.php?post='.$customer_id.'&action=edit' ),
			'errors'	=> $feedback
		);
	}


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

		if ( !is_int(intval($id)) )  
			$json_api->error("post_id non reconnu");
		
		global $post;
		$post = get_post($id);
		
		$is_event 		= Kidzou_Events::isTypeEvent($id);
		$is_featured  	= Kidzou_Featured::isFeatured($id);

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
				'location'		=> Kidzou_Geoloc::get_post_location($id),
				'votes'			=> Kidzou_Vote::getVoteCount($id),
				'is_event' 		=> $is_event,
				'event_dates' 	=> ($is_event ? Kidzou_Events::getEventDates($id) : array()),
				'thumbnail'		=> Kidzou_Utils::get_post_thumbnail($id, 'large'),
				'comments' 		=> $comments,
				'gallery'		=> $images,
				'is_featured'	=> $is_featured,
				'terms'			=> $terms ,
				'permalink'		=> get_the_permalink(),
			);

		// Kidzou_Utils::log('get_place', $place);

		return array(
			'place' => $place
		);
	}

	/**
	 * enreigistrement de la metadonnée "place" pour un contenu
	 */
	public function place() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('content', 'place');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");
		if ( !Kidzou_Utils::current_user_can('can_edit_post') ) $json_api->error("Vous n'avez pas les droits suffisants");

		if ( !isset($_POST['location']) ) $json_api->error("l'élement 'location' est attendu en parametre POST");

		if ( !isset($_POST['post_id']) || intval($_POST['post_id'])==1 ) $json_api->error("l'élement 'post_id' n'est pas reconnu");

		$tel  = '';
		$web = '';

		if (isset($_POST['contact'])) {
			$contact 		= $_POST['contact'];
			$tel 	=	$contact['tel'];
			$web 	=	$contact['web'];
		}
		
		$post_id 	=	intval($_POST['post_id']);

		$location = $_POST['location'];

		$address 	= $location['address'];
		$city 		= $location['city'];
		$lat = $location['lat'];
		$lng = $location['lng'];

		//on va surcharger le nom du customer
		$name = $location['name'];

		//généralement quand l'adresse n'est pas bien redréssée le pays est 'US' 
		//de toute facon sur Kidzou on utilise des adresses en 'FR'
		$adresseRedresseeCorrecte = ($location['country']=='FR');
		$res = '';

		//associer l'adresse au customer, 
		//elle sera transitive sur le post par association du client au post
		if ($adresseRedresseeCorrecte) {

			$res = Kidzou_Geoloc::set_location(
				$post_id, 
				$name, 
				$address, 
				$web, 
				$tel, 
				$city, 
				$lat, 
				$lng );
		}

		return array('result'=>$res);
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
						'location'		=> Kidzou_Geoloc::get_post_location(get_the_ID()),
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
		$limit = $json_api->query->limit;

		if ( !Kidzou_API::isPublicKey($key) ) 
			$json_api->error("Cle invalide ");
		
		if ( !is_int(intval($id)) )  
			$json_api->error("post_id non reconnu");

		if ( !is_int(intval($limit)) )  
			$limit=3;

		$locator = Kidzou_Metropole::get_instance();
		$results = $locator->get_related_posts();


		// if (function_exists('get_crp_posts_id'))
		// 	$results = get_crp_posts_id(array( 'postid' => $id, 'limit' => $limit ));

		//filtrer les résultats
		$filtered = array();
		global $post;
        foreach ($results as $id) {
            $post = get_post($id);
            setup_postdata($post);
            $is_event               = Kidzou_Events::isTypeEvent(get_the_ID());
            
            //exit les events non actifs
            if ($is_event && !Kidzou_Events::isEventActive(get_the_ID()))
                    continue;
            
            $filtered[] = $id;
        }


		return array(
				'related'=> $filtered
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
		
		$locator = Kidzou_Geoloc::get_instance();

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
						'content'	=> get_the_content(),
						'permalink' => get_the_permalink(),
						'thumbnail' => Kidzou_Utils::get_post_thumbnail($value->post_id, 'large'),
						'id'		=> $value->post_id,
						'location'	=> Kidzou_Geoloc::get_post_location($value->post_id),
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
			$location = Kidzou_Geoloc::get_post_location($post->ID);
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

	/**
	* Enregistre le fait que le contenu soit featured
	* 
	* @param $_POST Array 
	**/
	public function featured() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('content', 'featured');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( !Kidzou_Utils::current_user_can('can_edit_featured') ) $json_api->error("Vous n'avez pas les droits suffisants");
		
		if ( !isset($_POST['post_id']) || intval($_POST['post_id'])==1  ) $json_api->error("l'élement 'customer_id' n'est pas reconnu");

		if ( !isset($_POST['featured']) ) $json_api->error("l'élement 'featured' n'est pas reconnu");

		Kidzou_Featured::setFeatured($_POST['post_id'], ($_POST['featured']=='true' ? true : false));

		return array();
	}

	/**
	* Enregistre les dates d'événement d'un post
	* 
	* @param $_POST Array 
	**/
	public function eventData() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('content', 'eventData');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( !Kidzou_Utils::current_user_can('can_edit_post') ) $json_api->error("Vous n'avez pas les droits suffisants");
		
		if ( !isset($_POST['post_id']) || intval($_POST['post_id'])==1  ) $json_api->error("l'élement 'customer_id' n'est pas reconnu");

		if ( !isset($_POST['start_date']) ) $json_api->error("l'élement 'start_date' n'est pas reconnu");

		$post_id 	= $_POST['post_id'];
		$start_date = $_POST['start_date'];
		$end_date	= (isset($_POST['end_date']) ? $_POST['end_date'] : '');

		//check des formats de date
		if ($start_date!==null && $start_date!=='' ) { //end_date peut être nulle on s'en fout
			
			if ($end_date==null || $end_date=='')
				$end_date = $start_date;

			$date_s = DateTime::createFromFormat('Y-m-d H:i:s', $start_date);
			$date_e = DateTime::createFromFormat('Y-m-d H:i:s', $end_date);
			
			if (!$date_s || !$date_e) {
			    $json_api->error("Format de date invalide");
			} 
		}

		//les options de récurrence
		$recurrence = array();
		if (Kidzou_Utils::current_user_can('can_set_event_recurrence') && isset($_POST['recurrence']) && $_POST['recurrence']=='true')
		{
			
			$recurrence = array( 	"model" 		=> $_POST['model'],
									"repeatEach" 	=> $_POST['repeatEach'],
									"repeatItems" 	=> $_POST['repeatItems'], 
									"endType" 		=> $_POST['endType'],
									"endValue"		=> $_POST['endValue'] );
		}  
		
		$return = Kidzou_Events::setEventDates($post_id, $start_date, $end_date, $recurrence);

		return array('result'=>$return);
	}

	public static function validateDate($date, $format = 'Y-m-d H:i:s') {
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	}
}


?>