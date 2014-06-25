<?php
/*
Controller Name: Clients
Controller Description: Accès aux propriétés des clients
Controller Author: Kidzou 
*/

class JSON_API_Clients_Controller {

	public function queryClients( )
	{
		global $json_api;
		global $wpdb;

		$term 		= $json_api->query->term;

		$table_clients = $wpdb->prefix . "clients";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$sqlres = $wpdb->get_results( 
					"SELECT c.id, c.name FROM $table_clients AS c WHERE c.name like '%$term%' ORDER BY name ASC",
					ARRAY_A
				);

		return array(
			"clients" => $sqlres
		);
	} 

	public function getClients() {

		global $json_api;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		
		global $wpdb;
		$table_clients = $wpdb->prefix . "clients";
		$table_connections = $wpdb->prefix . "connections";
		//$table_events = $wpdb->prefix . "reallysimpleevents";

		$res = $wpdb->get_results("SELECT c.id, c.name, c.connections_id, f.slug as connections_slug FROM $table_clients c LEFT JOIN $table_connections f on (c.connections_id=f.id)", ARRAY_A);

		return array(
			"clients" => $res
		);		
		
	}

	public function getClientByID() {

		global $json_api;
		global $wpdb;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$table_clients = $wpdb->prefix . "clients";
		$table_clients_users = $wpdb->prefix . "clients_users";
		$table_connections = $wpdb->prefix . "connections";
		//$table_events = $wpdb->prefix . "reallysimpleevents";
		$table_users = $wpdb->prefix . "users";

		$id 		= $json_api->query->id;

		if ($id!='')
		{				
			$res = $wpdb->get_row(
				"SELECT c.*,f.slug AS connections_slug FROM $table_clients c LEFT JOIN $table_connections f ON (c.connections_id=f.id) WHERE c.id=$id LIMIT 1", ARRAY_A);

			$allusers = $wpdb->get_results(
				"SELECT u.id,u.user_login FROM $table_clients_users c, $table_users u WHERE c.customer_id=$id AND c.user_id=u.id", ARRAY_A);

			//recup du role du user
			$q_users = array();
			$main_users = array();
			$second_users = array();

			foreach ( $allusers as $a_user ) {
				//echo "a_user['id']:".$a_user["id"];
				array_push($q_users, $a_user["id"]);
			}

			if (count($q_users)>0) {
				//s'il y a des users
				//print_r($q_users);
				$args = array( 'include' => $q_users );
				//print_r($args);
				$user_query = new WP_User_Query( $args );

				foreach ( $user_query->results as $user ) {
					//print_r($user);

					if (user_can($user, 'edit_others_events'))
						array_push($main_users, array("id"=> $user->ID, "user_login"=> $user->user_login));	
					else
						array_push($second_users, array("id"=> $user->ID, "user_login"=> $user->user_login));
				}
			}

			$res["users"] = $main_users;
			$res["secondusers"] = $second_users;

			return array(
				"client" => $res
			);
		}

		return array();
	}


	public function getEventsByClientID() {

		global $json_api;
		global $wpdb;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		//$table_clients = $wpdb->prefix . "clients";
		$table_events = $wpdb->prefix . "reallysimpleevents";
		//$table_users  = $wpdb->prefix . "users";

		$id 		= $json_api->query->id;
		$page 		= $json_api->query->page;
		$filters	= $json_api->query->filters;
		$index		= $json_api->query->index; 

		if ($id!='')
		{	
			//paging
			if ($page=='') $page=0;
			
			$page = intval($page);
			$min = $page*10;
			$max = ($page+1)*10;
			$where = "";

			if ($filters!==null) {

				foreach ($filters as $key => $value) {
					if (strtoupper($key)=="YEAR" && intval($value)>0)
						$where .= " AND YEAR(start_date) = ".$value;
					elseif (strtoupper($key)=="MONTH" && intval($value)>0) 
						$where .= " AND MONTH(start_date) = ".$value;
				}

				//Ex : les filtres font qu'il n'y avait pas forcément 10 resultats sur la page
				//au départ, mais peut-etre uniquement 2 ou 3...donc il faut repartir de là
				$min = $min - 10 + intval($index);
				$max = $max - 10 + intval($index);
			}

			//ramener le demandeur et le client
			//$wpdb->show_errors();
			$events = $wpdb->get_results(
				"SELECT e.* FROM $table_events e WHERE e.customer=$id AND e.status<>'draft' $where ORDER BY e.start_date DESC,e.title ASC LIMIT $min, $max", ARRAY_A);
			//$wpdb->print_error();
			//qui a saisi cet evenement ?
			$i=0;
			foreach ($events as $key => $value) {
				$modified_by = get_userdata($events[$i]['modified_by']);
				if (!$modified_by) //si le user n'existe pas...
					$modified_by = array("data"=> array("id" => 0, "user_login" => ""));
				$events[$i]['modified_by'] = $modified_by;
				$i++;
			}

			//combien y a-t-il d'evenements disponibles (pour savoir si on peut paginer)
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_events e WHERE e.customer=$id AND e.status<>'draft' $where" );
			
			return array(
				"events" => $events,
				"count" => $count
			);
		}

		$json_api->error("L'identifiant du client est incorrect.");
	}

	public function deleteClient() {

		global $json_api;
		global $wpdb;

		$table_clients = $wpdb->prefix . "clients";
		$table_clients_users = $wpdb->prefix . "clients_users";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;

		if ($id!==null && $id!=="") {

			$cust_cols = array( "id" => $id);

		    $wpdb->delete( $table_clients ,
		                     $cust_cols,
		                     array( '%d') );

		    $cust_cols = array( "customer_id" => $id);

		    $wpdb->delete( $table_clients_users ,
		                     $cust_cols,
		                     array( '%d') );

		    return array(
				"id" => $id,
			);

		}

		$json_api->error("L'identifiant fourni n'est pas correct, la suppression n'a pas eu lieu");

	}

	public function saveUsers() {

		global $json_api;
		global $wpdb;

		//$table_clients = $wpdb->prefix . "clients";
		$table_clients_users = $wpdb->prefix . "clients_users";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;
		$users 		= $json_api->query->users;
		$secondusers= $json_api->query->secondusers;

		$allusers = array_merge($users, $secondusers);

		//il faut faire un DIFF :
		//recolter la liste des users existants sur ce client
		//comparer à la liste des users passés dans le POST
		//supprimer, ajouter selon les cas
		$old_users = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM $table_clients_users WHERE customer_id = $id");

		//boucle primaire
		//si les users passés dans la req étaient déjà présents en base
		//	si il n'avaient pas capacité edit_others_events, on leur ajoute
		//	sinon -
		//si non
		// 	on ajoute le user à la liste des users du client
		//		si il n'a pas la capacité edit_others_events, on lui ajoute la capacité edit_others_events
		foreach ($allusers as $a_user) {

			//recup du role du user
			$args = array(
				'search'         => $a_user,
				'search_columns' => array( 'ID' ),
			);
			$user_query = new WP_User_Query( $args );
			$q_user = $user_query->results[0];

			if (!is_null($old_users) && in_array($a_user, $old_users)  ) {
				
				//si c'est un utilisateur principal, on lui ajoute la capacité qu'il faut
				if (in_array($a_user, $users) && !user_can($q_user, 'edit_others_events'))
			    	$q_user->add_cap( 'edit_others_events');

			} else {
				
				//insert row
				$users_cols = array( 
					"user_id" => $a_user,
					"customer_id" => $id );
				
				//$wpdb->show_errors();
			    $wpdb->insert( $table_clients_users ,
		                     $users_cols,
		                     array( '%d' ) );
			    //$wpdb->print_error();

			    //si c'est un utilisateur principal, on lui ajoute la capacité qu'il faut
			    if (in_array($a_user, $users) && !user_can($q_user, 'edit_others_events'))
			    	$q_user->add_cap( 'edit_others_events');
			}
		}

		//si la base contenait une liste d'utilisateurs pour le client
		//	si le user a été repassé en requette 
		//		si il n'avait pas la capacité edit_others_events, on lui ajoute
		//	si non
		// 		on supprime le user de la base, car il n'a pas été repassé dans la requete

		if (!is_null($old_users)) {

			//boucle complémentaire:
			foreach ($old_users as $a_user) {
				//print_r($old_users);

				//recup du role du user
					$args = array(
						'search'         => $a_user,
						'search_columns' => array( 'ID' ),
					);
					$user_query = new WP_User_Query( $args );
					$q_user = $user_query->results[0];

				if (in_array($a_user, $allusers)) {

					//si c'est un utilisateur principal, on lui ajoute la capacité qu'il fautas
				    if (in_array($a_user, $users) && !user_can($q_user, 'edit_others_events'))
				    	$q_user->add_cap( 'edit_others_events');
					//do nothing
				
				} else {
					//echo "delete user : " . $a_user;
					//delete row
					$users_cols = array( 
						"customer_id" => $id,
						"user_id" => $a_user );
					
				    $wpdb->delete( $table_clients_users ,
				                     $users_cols,
				                     array( '%d', '%d') );

				    //on supprime la capacité du user
				    if (user_can($q_user, 'edit_others_events'))
				    	$q_user->remove_cap( 'edit_others_events');

				    $isDeleted = true;
					
				}
			}

		}

		return array(
			"id" => $id,
			"users" => $users,
			"secondusers" => $secondusers
		);

	}

	public function saveClient() {

		global $json_api;
		global $wpdb;

		$table_clients = $wpdb->prefix . "clients";
		$table_clients_users = $wpdb->prefix . "clients_users";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;
		$name 		= $json_api->query->name;
		$connections_id = $json_api->query->connections_id;

		if ($name!==null && $name!=="")
		{
			if ($connections_id==null) $connections_id=0;

			$table_clients_cols = array( 
							"name" => $name,
							"connections_id" => intval($connections_id) );

			if ($id==null || $id=="" || intval($id)==0) {

				$wpdb->insert( $table_clients , $table_clients_cols );

			} else {

				$wpdb->update( $table_clients ,
		                     $table_clients_cols ,
							 array( 'ID' => $id )
						   );
			}

		} else
			$json_api->error("l'identifiant ou le nom du client est incorrect.");

		return array(
			"id" => $id,
			"name" => $name,
			"connections_id" => $connections_id
		);
	}

	
}	

?>