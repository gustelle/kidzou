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

		// Kidzou_Utils::log('Vote Up for ' . $id);

		if (!$json_api->query->nonce) {
	      $json_api->error("You must include a 'nonce' value to vote.");
	    }

		$nonce_id = $json_api->get_nonce_id('vote', 'up');

	    if (!wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

	  	// Kidzou_Utils::log('Vote Up for ' . $id);
		$result = Kidzou_Vote::plusOne($id, $user_hash);
		Kidzou_Utils::log($result);

		return array(
			// "user" 			=> $user_id,
			"post_id" 		=> $id,
			"user_hash"		=> $result['user_hash']
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

	    $result = Kidzou_Vote::minusOne($id, $user_hash);

	  	
		return array(
			// "message" 		=> $message,
			// "user" 			=> $user_id,
			"post_id" 		=> $id,
			"user_hash"		=> $result['user_hash']
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
			
			$res = Kidzou_Vote::getPostVotes($id);
			
			$status = array(
		      "id" 		=> $res['id'],
		      "votes"	=> $res['votes'],
		      "voted"	=> (intval($res['votes'])>0),
		      "date"	=> time()
		    );
			
		}
		elseif ($in!='') 
		{

			// $list_array = json_decode($in, true);
			// Kidzou_Utils::log(array('in'=>$in), true);
			if (count($in)>0)
			{
				$status = Kidzou_Vote::getPostsListVotes($in);
			}

		}

		return $status;

	}

	public function voted_by_user() {

		global $json_api;
		$id = $json_api->query->post_id;

		if (!$json_api->query->post_id) {
	      $json_api->error("You must include a 'post_id'");
	    }

	    // $user_id = (is_user_logged_in() ? intval(get_user('ID') : 0);
	    // Kidzou_Utils::log('voted_by_user for '. $id);
	    // Kidzou_Utils::log('voted_by_user ? '. Kidzou_Vote::hasAlreadyVoted($id));
	    $voted = Kidzou_Vote::hasAlreadyVoted($id); 

		return array('voted' => $voted);

	}

	//statut des votes pour un user
	public function get_votes_user() {

		global $json_api;

		$user_hash 	= $json_api->query->user_hash;

		$voted_posts = Kidzou_Vote::getUserVotes($user_hash);

		return $voted_posts;
	}

}





?>