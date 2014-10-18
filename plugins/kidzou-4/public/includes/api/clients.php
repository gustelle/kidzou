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
		// $table_connections = $wpdb->prefix . "connections";
		// //$table_events = $wpdb->prefix . "reallysimpleevents";

		$res = $wpdb->get_results("SELECT c.id, c.name FROM $table_clients c", ARRAY_A);

		return array(
			"clients" => $res
		);		
		
	}

	public function getClientByID() {

		global $json_api;
		global $wpdb;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$table_clients = $wpdb->prefix . Kidzou_Customer::CLIENTS_TABLE;
		$table_clients_users = $wpdb->prefix . Kidzou_Customer::CLIENTS_USERS_TABLE;

		//$table_events = $wpdb->prefix . "reallysimpleevents";
		$table_users = $wpdb->prefix . "users";

		$id 		= $json_api->query->id;

		if ($id!='')
		{				
			// $res = $wpdb->get_row(
			// 	"SELECT c.*,f.slug AS connections_slug FROM $table_clients c LEFT JOIN $table_connections f ON (c.connections_id=f.id) WHERE c.id=$id LIMIT 1", ARRAY_A);

			$allusers = $wpdb->get_results(
				"SELECT u.id,u.user_login FROM $table_clients_users c, $table_users u WHERE c.customer_id=$id AND c.user_id=u.id", ARRAY_A);

			//recup du role du user
			$q_users = array();
			$main_users = array();
			$second_users = array(); 

			foreach ( $allusers as $a_user ) {
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

					if (user_can($user, 'edit_others_posts'))
						array_push($main_users, array("id"=> $user->ID, "user_login"=> $user->user_login));	
					else
						array_push($second_users, array("id"=> $user->ID, "user_login"=> $user->user_login));
				}
			}

			$res["users"] = $main_users;
			$res["secondusers"] = $second_users;
			$res["id"] = $id;

			// $wpdb->show_errors();
			$name = $wpdb->get_results(
				"SELECT c.name FROM $table_clients c WHERE c.id=$id", ARRAY_A);
			// $wpdb->print_error();

			$res["name"] = $name[0]["name"];

			return array(
				"client" => $res
			);
		}

		return array();
	}

	/**
	 * liste des contenus que l'on peut rattacher à un client
	 *
	 * @return void
	 * @author 
	 * @deprecated
	 **/
	public function queryAttachableContents( )
	{
		global $json_api;

		$term 		= $json_api->query->term;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		if ($term=='')
			return array(
				"posts" => array(),
			);

		global $wpdb;

		$sql_query = "
				SELECT 
					SQL_CALC_FOUND_ROWS $wpdb->posts.ID
				FROM 
					$wpdb->posts
				LEFT JOIN  
					$wpdb->postmeta AS mt1 ON ( $wpdb->posts.ID = mt1.post_id ) 
				WHERE 1=1
				AND 
		 			$wpdb->posts.post_type in ('offres','post')
				AND 
					$wpdb->posts.post_title LIKE '%$term%'
				AND 
					$wpdb->posts.post_status <> 'trash'
				AND 
					$wpdb->posts.post_status <> 'auto-draft'
				AND 
				(
					(mt1.meta_key = 'kz_event_customer'
					AND CAST(mt1.meta_value AS UNSIGNED) = 0)
				)
				GROUP BY 
					$wpdb->posts.ID
				";

		$postids = $wpdb->get_col( $sql_query ); 

		if ($postids!=null && count($postids)>0) {

			$posts = $json_api->introspector->get_posts( array(
					'post__in' => $postids,
					'post_type' => array('offres','post'),
					// 'post_status' =>  'any'
				) 
			);

		}
		else
			$posts = array();

		return array(
			"posts" => $posts,
			"sql" => $sql_query
		);
	} 

	/**
	 * liste des contenus que l'on peut rattacher à un client
	 *
	 * @return void
	 * @author 
	 **/
	public function queryAttachablePosts( )
	{
		global $json_api;

		$term 		= $json_api->query->term;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		if ($term=='')
			return array(
				"posts" => array(),
			);

		add_filter( 'posts_where', array($this, 'query_where_title_like'), 10, 2 );

		$posts = $json_api->introspector->get_posts( array(
				'post_title' => $term,
				'posts_per_page' => 10,
				'meta_query' => array(
			        'relation' => 'OR',
			            array( // new and edited posts
			                'key' => Kidzou_Customer::$meta_customer_posts,
			                'compare' => 'NOT EXISTS', // works!
     						'value' => '' // This is ignored, but is necessary...
			            ),

			            array( // get old posts w/out custom field
			                'key' => Kidzou_Customer::$meta_customer_posts,
			               	'value' => 'hackme'
			            ) 
			        ),
			) 
		);

		global $wp_query;

		return array(
			"posts" => $posts,
			"query" => $wp_query->request
		);
	} 

	public function query_where_title_like( $where, &$wp_query )
	{
	    global $wpdb;
	    if ( $post_title = $wp_query->get( 'post_title' ) ) {
	        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $post_title ) ) . '%\'';
	    }
	    return $where;
	}

	//update event (meta 'kz_event_customer' supprimée ou mise à 0)
	public function detachFromClient() {

		global $json_api;
		global $wpdb;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$event_id 		= $json_api->query->event_id;

		delete_post_meta($event_id, 'kz_event_customer');

		//client "0" 
		add_post_meta($event_id, 'kz_event_customer', 0, TRUE);

		return array();
		
	}

	public function attachToClient() {

		global $json_api;
		global $wpdb;

		// $table_events = $wpdb->prefix . "reallysimpleevents";

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id; //id du client concerné
		$events 	= $json_api->query->events; //tableau des events à attacher au client

		if ($id!==null && $id!=="" && intval($id)>0)
		{

			foreach ($events as $event) 
			{	

				$prev = get_post_meta($event, 'kz_event_customer', TRUE);
				// echo 'event '.$event.' > '.$prev.' > '. $id;
				if(strlen($prev)>0) { // If the custom field already has a value
					update_post_meta($event, 'kz_event_customer', $id, $prev);
				} else { // If the custom field doesn't have a value
					add_post_meta($event, 'kz_event_customer', $id, TRUE);
				}
			}
			return array("posts" => $events, "id" => $id);
				
		} else
			$json_api->error("l'identifiant ou le nom du client est incorrect.");
	}

	/**
	* retrouve les contenus publiés d'un client
	**/
	public function getContentsByClientID() {

		global $json_api;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id; 	//ID du client

		$posts = $json_api->introspector->get_posts(array(
				'post_type' => array('post','offres'), 
				'post_status' => 'publish' ,
				'meta_key' => 'kz_event_customer',
				'meta_value' => $id,
			));

		return array(
			"posts" => $posts,
			"count" => 10
		);
	
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

		$mergedusers = array_merge($users, $secondusers);

		//supprimer les valeurs vides des tableaux pour éviter erreurs 
		$allusers = array_filter($mergedusers);

		//il faut faire un DIFF :
		//recolter la liste des users existants sur ce client
		//comparer à la liste des users passés dans le POST
		//supprimer, ajouter selon les cas
		$old_users = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM $table_clients_users WHERE customer_id = $id");

		//boucle primaire
		//si les users passés dans la req étaient déjà présents en base
		//	si il n'avaient pas capacité edit_others_events, -
		//	sinon -
		//si non
		// 	on ajoute le user à la liste des users du client
		//		si il n'a pas la capacité edit_others_events, -
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
				if (in_array($a_user, $users) && !user_can($q_user, 'edit_others_posts')) {

					//pas fonctionnel pour l'instant
					//en effet, cette capability autorise n'importe quel utilisateur principal à editer n'importe quel event
					//il faut donc trouver une autre solution pour distinguer users principaux et secondaires

			    	//$q_user->add_cap( 'edit_others_events');
				}

			} else {
				
				//insert row
				$users_cols = array( 
					"user_id" => $a_user,
					"customer_id" => $id );
				
				// $wpdb->show_errors();
			    $return = $wpdb->insert( $table_clients_users ,
		                     $users_cols,
		                     array( '%d' ) );
			    // $wpdb->print_error();

			    //si le user etait déjà associé à un client (?) on update la ligne
				//en fait les users sont crées avec le client "0" au départ ... donc il faut updater
			    if (!$return)
			    	$return = $wpdb->update( $table_clients_users ,
		                     $users_cols,
		                     array( 'user_id' => $a_user ), 
		                     array( '%d' ),
		                     array( '%d' ) );

			    if (!$return)
			    	$json_api->error("Le user n'a pas ete inséré");

			    //si c'est un utilisateur principal, on lui ajoute la capacité qu'il faut
			    if (in_array($a_user, $users) && !user_can($q_user, 'edit_others_posts')) {

			    	//pas fonctionnel pour l'instant
					//en effet, cette capability autorise n'importe quel utilisateur principal à editer n'importe quel event
					//il faut donc trouver une autre solution pour distinguer users principaux et secondaires

			    	// $q_user->add_cap( 'edit_others_events');
			    }
			}
		}

		//boucle secondaire
		//si la base contenait une liste d'utilisateurs pour le client
		//	si le user a été repassé en requette 
		//		si il n'avait pas la capacité edit_others_events, -
		//	si non
		// 		on supprime le user de la base, car il n'a pas été repassé dans la requete

		if (!is_null($old_users)) {

			//boucle complémentaire:
			foreach ($old_users as $a_user) {
				

				//recup du role du user
					$args = array(
						'search'         => $a_user,
						'search_columns' => array( 'ID' ),
					);
					$user_query = new WP_User_Query( $args );
					$q_user = $user_query->results[0];

				if (in_array($a_user, $allusers)) {

					//si c'est un utilisateur principal, on lui ajoute la capacité qu'il fautas
				    if (in_array($a_user, $users) && !user_can($q_user, 'edit_others_posts')) {

				    	//pas fonctionnel pour l'instant
						//en effet, cette capability autorise n'importe quel utilisateur principal à editer n'importe quel event
						//il faut donc trouver une autre solution pour distinguer users principaux et secondaires
				    	// $q_user->add_cap( 'edit_others_events');
				    }
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
				    if (user_can($q_user, 'edit_others_posts')) {

				    	//pas fonctionnel pour l'instant
						//en effet, cette capability autorise n'importe quel utilisateur principal à editer n'importe quel event
						//il faut donc trouver une autre solution pour distinguer users principaux et secondaires
				    	// $q_user->remove_cap( 'edit_others_events');
				    }

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

		$table_clients = $wpdb->prefix . Kidzou_Customer::CLIENTS_TABLE;
		$table_clients_users = $wpdb->prefix . Kidzou_Customer::CLIENTS_USERS_TABLE;

		if (!current_user_can("edit_users") || !current_user_can("manage_options"))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id;
		$name 		= $json_api->query->name;
		// $connections_id = $json_api->query->connections_id;

		if ($name!==null && $name!=="")
		{
			// if ($connections_id==null) $connections_id=0;

			$table_clients_cols = array( 
							"name" => $name );

			if ($id==null || $id=="" || intval($id)==0) {

				$res = $wpdb->insert( $table_clients , $table_clients_cols ); //nb of rows on success or false on failure

				if (!$res)
					$json_api->error("erreur lors de la creation du client.");
				else
					$id = $wpdb->insert_id;

			} else {

				$nb = $wpdb->update( $table_clients ,
		                     $table_clients_cols ,
							 array( 'ID' => $id )
						   );

				if (!$nb)
					$json_api->error("erreur lors de la mise à jour du client.");
			}

		} else
			$json_api->error("l'identifiant ou le nom du client est incorrect.");

		return array(
			"id" => $id,
			"name" => $name,
			// "connections_id" => $connections_id
		);
	}

	
}	

?>