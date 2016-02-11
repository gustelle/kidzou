<?php
/*
Controller Name: Clients
Controller Description: Accès aux propriétés des clients
Controller Author: Kidzou 
*/

/**
 * EXtension JSON API, cet End Point permet la relation Client (ou customer) / Contenu. 
 *
 * Les contenus rattachés à un même client sont liés entre eux, ce qui permet une navigation et une gestion facile des clients
 * dans l'optique de services freemium
 *
 * @package Kidzou_Admin
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 */
class JSON_API_Clients_Controller {


	public function getCustomerPlace() {

		global $json_api;

		$id 		= $json_api->query->id;

		if (!Kidzou_Utils::current_user_can('can_edit_post'))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		//attention au hack
		//si le user n'est pas au moins auteur, l'API ne peut être utilisée que avec le $id du customer du user courant
		if (!Kidzou_Utils::current_user_can('can_edit_customer')) {

			$current_customers = Kidzou_Customer::getCustomersIDByUserID();
			if (!in_array($id, $current_customers))
				$json_api->error("Vous n'avez pas le droit de consulter les données de ce client.");
		}

		$location = Kidzou_Geoloc::get_post_location($id, Kidzou_Customer::$post_type);

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

		if (!Kidzou_Utils::current_user_can('can_edit_customer'))
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
			// "query" => $wp_query->request
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

		if (!Kidzou_Utils::current_user_can('can_edit_customer'))
			$json_api->error("Vous n'avez pas le droit d'utiliser cette fonction.");

		$id 		= $json_api->query->id; //ID du client
		$limit 		= 10; //Default value
		
		if (!$json_api->query->limit || is_int($json_api->query->limit)) {
	     	$limit = 10;
	    } else {
	    	$limit = intval($json_api->query->limit);	
	    }

		$posts = $json_api->introspector->get_posts(array(
				'posts_per_page'=> $limit,
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

	/**
	* Attache un ensemble de posts a un client
	* 
	* @param $_POST['posts'] Array un tableau contenant des ID de posts
	**/
	public function posts() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('clients', 'posts');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( !Kidzou_Utils::current_user_can('can_edit_customer') ) $json_api->error("Vous n'avez pas les droits suffisants");
		
		if ( !isset($_POST['customer_id']) || intval($_POST['customer_id'])==1 ) $json_api->error("l'élement 'customer_id' n'est pas reconnu");
		if ( !isset($_POST['posts']) || !is_array($_POST['posts']) ) $json_api->error("l'élement 'posts' n'est pas reconnu");

		Kidzou_Customer::attach_posts($_POST['customer_id'], $_POST['posts']);

		return array();
	
	}

	/**
	* Attache un ensemble de users a un client, afin de les rendre contributeurs pour ce client
	* 
	* @param $_POST['users'] Array un tableau contenant des ID de users
	**/
	public function users() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('clients', 'users');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( !Kidzou_Utils::current_user_can('can_edit_customer') ) $json_api->error("Vous n'avez pas les droits suffisants");
		
		if ( !isset($_POST['customer_id']) || intval($_POST['customer_id'])==1 ) $json_api->error("l'élement 'customer_id' n'est pas reconnu");
		if ( !isset($_POST['users']) || !is_array($_POST['users']) ) $json_api->error("l'élement 'users' n'est pas reconnu");

		Kidzou_Customer::set_users($_POST['customer_id'], $_POST['users']);

		return array();
	
	}

	/**
	* Changement du Quota d'accès aux API pour le client
	* 
	* @param $_POST Array un tableau contenant le quota par méthode d'API
	**/
	public function quota() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('clients', 'quota');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( !Kidzou_Utils::current_user_can('can_edit_customer') ) $json_api->error("Vous n'avez pas les droits suffisants");

		if ( !isset($_POST['quota']) || !is_array($_POST['quota']) ) $json_api->error("l'élement 'quota' n'est pas reconnu");
		
		if ( !isset($_POST['customer_id']) || intval($_POST['customer_id'])==1  ) $json_api->error("l'élement 'customer_id' n'est pas reconnu");

		$name 	= reset(array_keys($_POST['quota'])); //premiere clé du tableau
		$quota 	= $_POST['quota'][$name];
		Kidzou_Customer::setQuota($_POST['customer_id'], $name, $quota);

		return array();
	
	}

	/**
	* Enregistre le fait que le client ait le droit ou non de consulter ses analytics
	* 
	* @param $_POST Array un tableau contenant 
	**/
	public function analytics() {

		global $json_api;

		$key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	    }

	    $nonce_id = $json_api->get_nonce_id('clients', 'analytics');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		if ( !Kidzou_Utils::current_user_can('can_edit_customer') ) $json_api->error("Vous n'avez pas les droits suffisants");
		
		if ( !isset($_POST['customer_id']) || intval($_POST['customer_id'])==1  ) $json_api->error("l'élement 'customer_id' n'est pas reconnu");

		if ( !isset($_POST['analytics']) ) $json_api->error("l'élement 'analytics' n'est pas reconnu");

		// Kidzou_Utils::log('_POST[analytics]='.$_POST['analytics'], true);

		Kidzou_Customer::set_analytics($_POST['customer_id'], ($_POST['analytics']=='true' ? true : false) );

		return array();
	
	}
	
}	

?>