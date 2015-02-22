<?php

class Vote_Query extends WP_Query {
 
  function __construct($base_args=array()) {

	if (isset($base_args['is_voted']) && $base_args['is_voted']==false)
    {
      //voir http://wordpress.stackexchange.com/questions/80303/query-all-posts-where-a-meta-key-does-not-exist
		$vote_args = array(
			'meta_query' => array(
			    array(
			     'key' => Kidzou_Vote::$meta_vote_count,
			     'compare' => 'NOT EXISTS', // works!
			    )
			)
		);

		 $the_args = array_merge(
	        $base_args, 
	        $vote_args
	      );
    } else {

    	$the_args = array_merge($base_args, array(
				'meta_key' => Kidzou_Vote::$meta_vote_count,
				'orderby' => array('meta_value_num'=>'DESC'),
			)
		);
    }

    parent::query($the_args);

  }
 
}

?>