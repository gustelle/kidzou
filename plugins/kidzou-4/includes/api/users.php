<?php
/*
Controller Name: Users
Controller Description: Accès aux propriétés des utilisateurs
Controller Author: Kidzou 
*/

class JSON_API_Users_Controller {


	public function get_userinfo() {

		global $json_api;

		if (!Kidzou_Utils::current_user_is('author'))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;
		$in 		= $json_api->query->in;
		$term 		= $json_api->query->term;
		$term_field	= $json_api->query->term_field;
		$status = array();

		//jouer avec les transients
		if ($id!='')
		{				
			global $wpdb;
			
			$res = $wpdb->get_results(
				"SELECT id,user_login,user_nicename,user_email,display_name FROM $wpdb->users WHERE id=$id", ARRAY_A);

			$status = $res;
			
		}
		elseif ($in!='') 
		{
			global $wpdb;

			$list = "(".$in.")";
			$list_array = explode(",", $in);
			$limit = count($list_array);

			$res = $wpdb->get_results(
				"SELECT id,user_login,user_nicename,user_email,display_name FROM $wpdb->users WHERE id in $list limit $limit", ARRAY_A); //LIMIT $limit
			
			$status['users'] = array(); $i=0;
			foreach ($res as $ares) 
			{
				$status['users'][$i] = $ares;
				$i++;
			}
		}

		//on renvoie egalement le client attaché au user
		//idealement cette info devrait être renvoyée dans les autres requetes
		elseif ($term!='')
		{				
			
			$args = array(
				'search'         => '*'.$term.'*',
				'search_columns' => array( 'user_login', 'user_email', 'nicename' ),
			);
			$user_query = new WP_User_Query( $args );

			// print_r($user_query);

			$status = $user_query->results;
		}

		return array(
			"status" => $status
		);
	}
}	

?>