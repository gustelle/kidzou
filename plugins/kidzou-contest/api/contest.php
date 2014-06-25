<?php
/*
Controller Name: Jeu Concours
Controller Description: Participation aux jeux concours
Controller Author: Kidzou 
*/

class JSON_API_Contest_Controller {

	public function participate( )
	{
		
		global $json_api;

		// //verifier nonce
		if (!$json_api->query->nonce) {
	      $json_api->error("You must include a 'nonce' value to participate. Use the `get_nonce` Core API method.");
	    }

		$nonce_id = $json_api->get_nonce_id('contest', 'participate');
		if ( !wp_verify_nonce($json_api->query->nonce, $nonce_id) ) {
			$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
		}

		//verifier connécté
		if ( !is_user_logged_in() ) {
			$json_api->error("Vous ne pouvez pas participer à un Jeu concours sans être connecté ;-)");
		}

		//recup current user
		$user_ID = get_current_user_id();
		$fields = $json_api->query->fields;

		$post_id = 0;

		foreach ($fields as $item) {

			$name = $item['name'];
			switch ($name) {

				case 'post_id':
					$post_id = $item['value'];
					break;
				
				default:
					break;
			}

		}

		if ( intval($post_id)==0 ) {
			$json_api->error("Vous devez associer un post a ces données ;-)");
		}

		//recup de la liste des jeux concours auxquel le user a participé
		$contests = get_user_meta( $user_ID, 'kz_contests', TRUE );

		if ($contests=='') {
			//la méta n'existait pas il faut la créer
			$contests = array( 0 => $post_id);
		
		} else {
			//ajout du post à la fin de la liste des jeux concours auxquels le user a déjà participé
			//seulement s'il n'avait pas déjà participé à ce jeu concours

			$key = array_search( $post_id, $contests ); // $key = 2;

			if ( !$key ) 
				array_push( $contests, $post_id );
		}

		//liste des participants aux concours
		$users = get_post_meta( $post_id, 'kz_contest_users', TRUE ); 
		
		if (is_array($users))
		{
			if (!in_array($user_ID, $users))
				array_push($users, $user_ID);
		}
		else 
		{
			$users = array(0 => $user_ID);
		}

		// //sauvegarder user meta
		update_user_meta( $user_ID, 'kz_contests' , $contests ); //liste des concours par user
		update_user_meta( $user_ID, 'kz_contest_'.$post_id , $fields ); //detail du concours
		update_post_meta($post_id, 'kz_contest_users', $users);

		return array(
			"user_id" 	=> $user_ID,
			"post_id"	=> $post_id,
			"fields" 	=> $fields,
			"contests" 	=> $contests
		);
	} 

	/**
	 * retourne la liste des participants à un concours
	 *
	 * @return void
	 * @author 
	 **/
	public function get_participants()
	{
		//verifier droits
		if ( !current_user_can("edit_post") ) {
			$json_api->error("Vous n'avez pas les droits pour voir la liste des participants a ce concours");
		}

		global $json_api;

		$term 		= $json_api->query->term;
		$post_id 	= $json_api->query->post_id;

		$user_query = new WP_User_Query(
			array(
				'meta_key'	  	=>	'kz_contest_'.$post_id,
				'meta_value'	=>	'',
				'meta_compare'	=> 	'EXISTS',
				'search'         => '*'.$term.'*',
				'search_columns' => array( 'user_login', 'user_email' ),
			)
		);

		// Get the results from the query, returning the first user
		$users = $user_query->get_results();

		return array( "results" => $users);
	}
	
}	

?>