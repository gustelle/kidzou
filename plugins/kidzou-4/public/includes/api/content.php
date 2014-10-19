<?php

/*
Controller Name: Content
Controller Description: Les contenus du site
Controller Author: Kidzou
*/

class JSON_API_Content_Controller {

	public function get() {

		global $json_api;
		$key 	= $json_api->query->key;

		global $wpdb;

		$args = array(
			'posts_per_page' => 1,
			'post_type'	=> 'customer',
			'meta_key' => Kidzou_Customer::$meta_api_key,
			'meta_value' => $key
		);

		$the_query = new WP_Query( $args );

		if (!$key) {
	      $json_api->error("Votre clé n'est pas valide");
	    }
	  	

		return array(
			'customer' => $the_query->get_posts()
		);

	}

}





?>