<?php
/*
Controller Name: Clients
Controller Description: Accès aux propriétés des clients
Controller Author: Kidzou 
*/

class JSON_API_Clients_Controller {

	public function getClients() {

		global $json_api;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		
		global $wpdb;
		$table_clients = $wpdb->prefix . "clients";
		$table_connections = $wpdb->prefix . "connections";
		$table_events = $wpdb->prefix . "reallysimpleevents";

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

		//jouer avec les transients
		if ($id!='')
		{				
			$res = $wpdb->get_row(
				"SELECT c.*,f.slug AS connections_slug FROM $table_clients c LEFT JOIN $table_connections f ON (c.connections_id=f.id) WHERE c.id=$id LIMIT 1", ARRAY_A);

			$users = $wpdb->get_results(
				"SELECT u.id,u.user_login FROM $table_clients_users c, $table_users u WHERE c.customer_id=$id AND c.user_id=u.id", ARRAY_A);

			//$events = $wpdb->get_results(
			//	"SELECT e.id, e.title, e.customer_id FROM $table_events WHERE e.customer_id=$id", ARRAY_A);

			$res["users"] = $users;
			//$res["events"] = $events;

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

		$table_clients = $wpdb->prefix . "clients";
		$table_events = $wpdb->prefix . "reallysimpleevents";

		$id 		= $json_api->query->id;
		//filter		= $json_api->query->filter;
		//$value		= $json_api->query->value; //valeur du filtre

		if ($id!='')
		{	
			//$filter_query = "";
			//if ($filter=='all')
			//	$filter_query = "";
			//if ($filter=='upcoming')
			//	$filter_query = " AND (start_date >= now() OR end_date >= now()) ";
			//elseif ($filter=="past") {
			// 	$filter_query = " AND start_date < now() AND end_date < now() ";
			//} 
			//elseif ($filter=="year" and $value!="") {
			// 	$filter_query = " AND ( YEAR(start_date) = $value OR YEAR(end_date) = $value ) ";
			//} 
			//elseif ($filter=="month" and $value!="") {
			// 	$filter_query = " AND ( MONTH(start_date) = $value OR MONTH(end_date) = $value ) ";
			//} 

			$events = $wpdb->get_results(
				"SELECT e.* FROM $table_events e WHERE e.customer=$id ORDER BY e.start_date DESC,e.title ASC", ARRAY_A);

			return array(
				"events" => $events,
				//"filters" => array (
				//		"years" => array("2013", "2014"),
				//		"months" => array("01","02","03","04","05","06","07","08","09","10","11","12")
				//	)
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

	public function saveClient() {

		global $json_api;
		global $wpdb;

		$table_clients = $wpdb->prefix . "clients";
		$table_clients_users = $wpdb->prefix . "clients_users";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;
		$users 		= $json_api->query->users;
		$name 		= $json_api->query->name;
		$connections_id = $json_api->query->connections_id;

		if ($name!==null && $name!=="" && $users!==null && $users!=="")
		{
			if ($connections_id==null) $connections_id=0;

			$table_clients_cols = array( 
							"name" => $name,
							"connections_id" => intval($connections_id) );

			if ($id==null || $id=="" || intval($id)==0) {

				$wpdb->insert( $table_clients , $table_clients_cols );

				$id = $wpdb->insert_id; 

				//dans le cas d'une creation de client, il n'y a pas lieu de faire un update de la table clients_users
				//uniquement un insert
				foreach ($users as $user) {

					$users_cols = array( 
						"user_id" => $user,
						'customer_id' => $id);
					
					//$wpdb->show_errors();
				    $wpdb->insert( $table_clients_users, $users_cols  );
				    //$wpdb->print_error();
				}

			} else {

				$wpdb->update( $table_clients ,
		                     $table_clients_cols ,
							 array( 'ID' => $id )
						   );

				//il faut faire un DIFF :
				//recolter la liste des users existants sur ce client
				//comparer à la liste des users passés dans le POST
				//supprimer, ajouter selon les cas
				$old_users = $wpdb->get_col(
					"SELECT DISTINCT user_id FROM $table_clients_users WHERE customer_id = $id");

				//boucle primaire
				foreach ($users as $a_user) {

					if (!is_null($old_users) && in_array($a_user, $old_users)) {
						//do nothing
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
					}
				}

				if (!is_null($old_users)) {

					//boucle complémentaire:
					foreach ($old_users as $a_user) {
						//print_r($old_users);

						if (in_array($a_user, $users)) {
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

						    $isDeleted = true;
							
						}
					}

				}

			}

		} else
			$json_api->error("l'identifiant ou le nom du client est incorrect.");

		return array(
			"id" => $id,
			"name" => $name,
			"users" => $users,
			"connections_id" => $connections_id
		);
	}

	
}	

?>