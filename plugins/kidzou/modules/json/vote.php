<?php

/*
Controller Name: Vote
Controller Description: Permet de voter ou de retirer son vote sur un article (Je recommande / Je ne recommande plus). Permet également de récupérer le nombre de vote pour une liste d'articles ou pour un article. Enfin, permet de récupérer les votes d'un user
Controller Author: Kidzou
*/

class JSON_API_Vote_Controller {

	public function up() {

		global $json_api;
		$id 		= $json_api->query->post_id;
		$nonce 		= $json_api->query->nonce;
		$user_hash 	= $json_api->query->user_hash;

		if (!$json_api->query->nonce) {
	      $json_api->error("You must include a 'nonce' value to vote.");
	    }

		$nonce_id = $json_api->get_nonce_id('vote', 'up');

	    if (!wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

	  	$user_id 	= get_user("ID");
	  	$loggedIn 	= is_user_logged_in();
	  	
		// Get votes count for the current post
		$meta_count = get_post_meta($id, "kz_reco_count", true);
		$message 	= '';

		//on ne recalcule pas systématiquement le hash du user, 
		//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
		if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
			$user_hash=hash_anonymous();

		// Use has already voted ?
		if(!hasAlreadyVoted($id, $loggedIn, $user_id, $user_hash))
		{
			update_post_meta($id, "kz_reco_count", ++$meta_count);

			//update les user meta pour indiquer les posts qu'il recommande
			//cela améliore les perfs à terme par rapport à updater les meta du posts avec la liste des users
			//car la liste des posts recommandés est chargée au chargement de la page si le user n'a pas de cookie
			//afin qu il retrouve ses petits...par ex si son cookie est expiré ou si il utilise un autre device

			if ($loggedIn)
			{
				$meta_posts = get_user_meta(intval($user_id), "kz_reco_post_id");
				
				//print_r($wpdb->queries);
				
				$voted_posts = $meta_posts[0]; //print_r($voted_posts);
				//$index_posts = count($voted_posts);

				if(!is_array($voted_posts))
					$voted_posts = array();

				array_push($voted_posts, $id) ;

				// $voted_posts[$index_posts] = $id;

				update_user_meta( $user_id, "kz_reco_post_id", $voted_posts);
			}
			else
			{

				if ( !update_post_meta (intval($id), 'kz_anonymous_user', $user_hash ) ) add_post_meta( intval($id), 'kz_anonymous_user', $user_hash );
			}

		}
		else
		{
			$message = "You have already voted for $id";
		}

		return array(
			"message" 		=> $message,
			"user" 			=> $user_id,
			"post_id" 		=> $id,
			"user_hash"		=> $user_hash
		);
	}

	/**
	 * retrait d'un vote ('Je ne recommande plus')
	 *
	 * @return void
	 * @author 
	 **/
	function down ()
	{

		global $json_api;
		$id 		= $json_api->query->post_id;
		$nonce 		= $json_api->query->nonce;
		$user_hash 	= $json_api->query->user_hash;

		if (!$json_api->query->nonce) {
	      $json_api->error("You must include a 'nonce' value to vote.");
	    }

		$nonce_id = $json_api->get_nonce_id('vote', 'down');

	    if (!wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

	  	$user_id = get_user("ID");
	  	$loggedIn = is_user_logged_in();

		// Get votes count for the current post
		$meta_count = get_post_meta($id, "kz_reco_count", true);
		$message 	= '';

		//on ne recalcule pas systématiquement le hash du user, 
		//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
		if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
			$user_hash=hash_anonymous();

		// Use has already voted ?
		if(hasAlreadyVoted($id, $loggedIn, $user_id, $user_hash))
		{
			update_post_meta($id, "kz_reco_count", --$meta_count);

			//update les user meta pour indiquer les posts qu'il recommande
			//cela améliore les perfs à terme par rapport à updater les meta du posts avec la liste des users
			//car la liste des posts recommandés est chargée au chargement de la page si le user n'a pas de cookie
			//afin qu il retrouve ses petits...par ex si son cookie est expiré ou si il utilise un autre device

			if ($loggedIn)
			{
				$meta_posts = get_user_meta(intval($user_id), "kz_reco_post_id");
				
				//print_r($wpdb->queries);
				
				$voted_posts = $meta_posts[0];
				//$index_posts = count($voted_posts);

				if(!is_array($voted_posts))
					$voted_posts = array();

				foreach ($voted_posts as $i => $value) {
				    //retrait du vote sur le user
				    if ( intval($value)==intval($id) )
						unset($voted_posts[$i]);
				}

				update_user_meta( $user_id, "kz_reco_post_id", $voted_posts);
			}
			else
				delete_post_meta(intval($id), 'kz_anonymous_user', $user_hash );

			//kz_clear_cache();

		}
		else
		{
			$message = "You have not voted for $id";
		}

		return array(
			"message" 		=> $message,
			"user" 			=> $user_id,
			"post_id" 		=> $id,
			"user_hash"		=> $user_hash
		);

	}

	//statut des votes pour un post ou un ensemble de posts
	public function get_votes_status() {

		global $json_api;
		$id = $json_api->query->post_id;
		$in = $json_api->query->posts_in;

		// echo 'in:'.$in;

		$status = array();

		//jouer avec les transients
		if ($id!='')
		{			
				
			global $wpdb;
			
			$res = $wpdb->get_results(
				"SELECT post_id as id,meta_value as votes FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_reco_count' AND key1.post_id = $id", ARRAY_A);

			$status = array(
		      "id" 		=> $res[0]['id'],
		      "votes"	=> $res[0]['votes'],
		      "voted"	=> "false",
		      "date"	=> time()
		    );
			
		}
		elseif ($in!='') 
		{

			$list_array = json_decode($in, true);

			if (count($list_array)>0)
			{
				global $wpdb;
				$list  = '('.implode(",", $list_array).')'; //echo $list;

				//attention à cette requete
				//ajout de DISTINCT et suppression de la limite car certains couples ID|META_VALUE peuvent être multiples !?
				$res = $wpdb->get_results(
					"SELECT DISTINCT post_id as id,meta_value as votes FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_reco_count' AND key1.post_id in $list ", ARRAY_A); //LIMIT $limit
				
				$status['status'] = array(); $i=0;
				foreach ($res as &$ares) 
				{
					$status['status'][$i] = &$ares;
					$i++;
				}
			}

		}

		return $status;

	}

	//statut des votes pour un user
	public function get_votes_user() {

		global $json_api;

		// $in 		= $json_api->query->posts_in;
		$user_hash 	= $json_api->query->user_hash;

		// $list_array = json_decode($in, true);

		$loggedIn   = is_user_logged_in();
		$voted_posts= array();

		if ($loggedIn)
		//recup des posts recommandes
		//par le user courant, si celui-ci n'est pas anonyme
		{
			//les posts que le user courant recommande sont dans les meta users
			//et non dans les meta post !
			$user_id = intval(get_user('ID'));

			global $wpdb;
			$res = $wpdb->get_results(
								"SELECT meta_value as serialized FROM $wpdb->usermeta WHERE user_id=$user_id AND meta_key='kz_reco_post_id'",
								ARRAY_A
							);
			$unserialized = maybe_unserialize($res[0]['serialized']);//print_r($unserialized);
			$voted = array();
			if ($unserialized!=null)
			{ 
				foreach ($unserialized as &$ares) 
					array_push($voted, array ('id' => intval($ares))) ;
			}

			$voted_posts['voted'] = $voted;
			$voted_posts['user'] = $user_id;
		}
		else 
		//le user est anonyme, on travaille avec son IP+UA pour l'identifier
		{
				
			//verification des données en base
			//le PK pour vérifier les données étant md5(IP+UA)
			global $wpdb;

			//on ne recalcule pas systématiquement le hash du user, 
			//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
			if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
				$user_hash=hash_anonymous();

			//$hash = hash_anonymous();
			// AND key1.post_id in $list LIMIT $limit
			$res = $wpdb->get_results(
								"SELECT DISTINCT post_id as id FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_anonymous_user' AND key1.meta_value='$user_hash' ", 
								ARRAY_A
							);

			$voted_posts['voted'] 		= $res;
			$voted_posts['user_hash'] 	= $user_hash;
		}

		return $voted_posts;
	}

}

/**
 * - si le user est loguué, on utilise son ID
 * - si le user est anonymous, on utilise son hash
 *
 * @return TRUE si le user a déjà voté le post
 * @author Kidzou
 **/
function hasAlreadyVoted($post_id, $loggedIn, $user_id, $user_hash)
{

	if ($loggedIn)
	{
		//check DB
		$meta_posts = get_user_meta($user_id, "kz_reco_post_id");
		$voted_posts = $meta_posts[0];

		if(!is_array($voted_posts))
			$voted_posts = array();

		if(in_array($post_id, $voted_posts))
			return true;

	}
	else
		return hasAnonymousAlreadyVoted ($post_id, $user_hash);

	return false;

}


/**
 * checke if un user anonyme a deja vote pour le poste concerné
 *
 * @return TRUE si le user anonyme a deja voté
 * @author Kidzou
 **/
function hasAnonymousAlreadyVoted($post_id, $user_hash)
{
	global $wpdb;
	// $hash = hash_anonymous();
	$res = $wpdb->get_var(
		"SELECT count(*) FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_anonymous_user' AND key1.meta_value='$user_hash' AND key1.post_id=$post_id LIMIT 1"
	);

	if (intval($res)>0)
		return true;

	return false;
}

/**
 * fonction très très laide mais qui permet de rafraichir le cache du nombre de vote
 * cette fonction supprime simplement tous les fichier présents à la racine de $cache_path (voir WP Super Cache)
 * car je ne sais pas comment retrouver le cache associé à un vote !
 *
 * @return void
 * @author 
 **/
// function kz_clear_cache()
// {
// 	global $cache_path;
//     $filenames = array();
//     $iterator = new DirectoryIterator($cache_path);
//     foreach ($iterator as $fileinfo) {
//         if ($fileinfo->isFile()) {
//             //$filenames[$fileinfo->getMTime()] = $fileinfo->getFilename();
//             // $message .= "Suppression de $fileinfo";
//             $bool = unlink($cache_path."/".$fileinfo->getFilename());
//             // $message .= " > $bool | ";
//         }
//     }
// }

?>