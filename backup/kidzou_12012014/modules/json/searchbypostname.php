<?php

class JSON_API_SearchByPostName_Controller {

	public function posts() {
		
		global $json_api;
		$q = $json_api->query->term;

		$results = array();
		
		if ($q!='')
		{
			global $wpdb;
			$sqlres = $wpdb->get_results( 
				"
				SELECT ID, post_name
				FROM $wpdb->posts
				WHERE post_type = 'post'
					AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'private')
					AND post_name like '%$q%'
					ORDER BY post_date DESC LIMIT 0, 5
				",
				ARRAY_A
			);

			foreach ( $sqlres as $r ) 
			{
				array_push($results, array('id' => $r['ID'], 'post_name' => $r['post_name'] ));
			}
		}

		$return = array(
	      "posts" 		=> $results
	    );

	    return cacheable_json($return);
	}

}

?>