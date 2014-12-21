<?php
/*
Controller Name: Clients
Controller Description: Accès aux propriétés des clients
Controller Author: Kidzou 
*/

class JSON_API_Clients_Controller {


	public function getCustomerPlace() {

		global $json_api;

		$id 		= $json_api->query->id;

		if (!Kidzou_Utils::current_user_is('contributor'))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		//attention au hack
		//si le user n'est pas au moins auteur, l'API ne peut être utilisée que avec le $id du customer du user courant
		if (!Kidzou_Utils::current_user_is('author')) {

			$current_customers = Kidzou_Customer::getCustomersIDByUserID();
			if (!in_array($id, $current_customers))
				$json_api->error("Vous n'avez pas le droit de consulter les données de ce client.");
		}

		$location = Kidzou_Geo::get_post_location($id, Kidzou_Customer::$post_type);

		return array('location'=> $location);
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

		if (!Kidzou_Utils::current_user_is('author'))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		if ($term=='')
			return array(
				"posts" => array(),
			);

		add_filter( 'posts_where', array($this, 'query_where_title_like'), 10, 2 );

		$posts = $json_api->introspector->get_posts( array(
				'post_title' => $term,
				'posts_per_page' => 10,
				'post_type' => Kidzou_Customer::$supported_post_types,
				'meta_query' => array(
			        'relation' => 'OR',
			            array( // new and edited posts
			                'key' => Kidzou_Customer::$meta_customer,
			                'compare' => 'NOT EXISTS', // works!
     						'value' => '' // This is ignored, but is necessary...
			            ),

			            array( // get old posts w/out custom field
			                'key' => Kidzou_Customer::$meta_customer,
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

	

	/**
	* retrouve les contenus publiés d'un client
	**/
	public function getContentsByClientID() {

		global $json_api;

		if (!Kidzou_Utils::current_user_is('admin'))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id; 	//ID du client

		$posts = $json_api->introspector->get_posts(array(
				'post_type' => Kidzou_Customer::$supported_post_types, 
				'post_status' => 'publish' ,
				'meta_key' => Kidzou_Customer::$meta_customer,
				'meta_value' => $id,
			));

		return array(
			"posts" => $posts,
			"count" => 10
		);
	
	}

	
}	

?>