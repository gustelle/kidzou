<?php
/*
Controller Name: Users
Controller Description: Accès aux propriétés des utilisateurs
Controller Author: Kidzou 
*/

class JSON_API_Users_Controller {

	public function get_userinfo() {

		global $json_api;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
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
			global $wpdb;
			$table_clients_users = $wpdb->prefix . "clients_users";
			// $wpdb->show_errors();
			$res = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT u.id, u.user_login, u.user_nicename, u.user_email, u.display_name, c.customer_id FROM $wpdb->users u LEFT JOIN $table_clients_users c ON (u.id=c.user_id) WHERE u.$term_field like %s;", '%' . like_escape($term) . '%'),
				ARRAY_A
			);
			// $wpdb->print_error();
			$status = $res;
		}

		return array(
			"status" => $status
		);
	}
}	

?>