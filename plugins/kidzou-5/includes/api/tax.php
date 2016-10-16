<?php
/*
Controller Name: Taxonomy
Controller Description: Accès aux Taxonomies 
Controller Author: Kidzou 
*/

/**
 * EXtension JSON API, cet End Point permet de travailler les taxonomies 
 *
 * @package Kidzou
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 */
class JSON_API_Taxonomy_Controller {


	/**
	* retrouve le Term pour une taxonomie
	* @see https://codex.wordpress.org/Function_Reference/get_term_by
	**/
	public function getTermBy() {

		global $json_api;

		if (!$json_api->query->field) {
	    	$json_api->error("'field' param is mandatory");
	    }
	    if (!$json_api->query->value) {
	    	$json_api->error("'value' param is mandatory");
	    }
	    if (!$json_api->query->taxonomy) {
	    	$json_api->error("'taxonomy' param is mandatory");
	    }

	    $field 		= $json_api->query->field; 
		$value 		= $json_api->query->value; //
		$taxonomy 	= $json_api->query->taxonomy; 

		$term = get_term_by( $field, $value, $taxonomy ) ;

		return array(
			'term' => $term
		);
	
	}

	/**
	* Enregistrement des terms pour un post donné 
	*
	* @see https://codex.wordpress.org/Function_Reference/get_term_by
	**/
	public function setPostTerms() {

		global $json_api;

		// $key = $json_api->query->key;
		$nonce = $json_api->query->nonce;

		// Kidzou_Utils::log(,true);

		// if (!$json_api->query->nonce && !Kidzou_API::isPublicKey($key)) {
	 //      $json_api->error("You must pass either the nonce or a public API Key for this operation");
	 //    }

	    $nonce_id = $json_api->get_nonce_id('taxonomy', 'setPostTerms');
		if ($json_api->query->nonce && !wp_verify_nonce($nonce, $nonce_id)) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");
		
		if ( !isset($_POST['post_id']) || !intval($_POST['post_id'])==1 ) $json_api->error("l'élement 'post_id' n'est pas reconnu");

		if ( !isset($_POST['terms'])  ) $json_api->error("l'élement 'terms' n'est pas reconnu");

		if ( !isset($_POST['taxonomy'])  ) $json_api->error("l'élement 'taxonomy' n'est pas reconnu");

		$result = wp_set_post_terms( $_POST['post_id'], $_POST['terms'], $_POST['taxonomy'] , true ) ;

		return array(
			'result' => $result
		);
	
	}

	
}	

?>