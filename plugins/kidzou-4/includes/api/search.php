<?php

/*
Controller Name: Search
Controller Description: fournit des services de recherche à la sauce kidzou
Controller Author: Kidzou
*/

class JSON_API_Search_Controller {

	public function suggest() {

		global $json_api;
		$term 	= $json_api->query->term;
	  	
		// $result = Kidzou_Vote::plusOne($id, $user_hash);

		return array(
			"taxonomies" => array("category" => array('test')),
			"posts" => array("posts" => array("post")),
		);

	}

}





?>