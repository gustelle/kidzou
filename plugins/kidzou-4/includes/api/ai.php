<?php
/*
Controller Name: AI
Controller Description: API pour ap.ai 
Controller Author: Kidzou 
*/

/**
 * EXtension JSON API, cet End Point permet de travailler les taxonomies 
 *
 * @package Kidzou
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 */
class JSON_API_AI_Controller {


	/**
	*
	**/
	public function search() {

		global $json_api;

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");

		return array(
			'term' => $term
		);
	
	}

	
}	

?>