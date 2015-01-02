<?php

class Vote_Query extends WP_Query {
 
  function __construct($base_args=array()) {

    $the_args = array_merge($base_args, array(
			'meta_key' => Kidzou_Vote::$meta_vote_count,
			'orderby' => array('meta_value_num'=>'DESC'),
		)
	);

	if (isset($base_args['is_voted']) && $base_args['is_voted']==false)
    {
      //voir http://wordpress.stackexchange.com/questions/80303/query-all-posts-where-a-meta-key-does-not-exist
		$vote_args = array(
			'meta_query' => array(
			   'relation' => 'OR',
			    array(
			     'key' => Kidzou_Vote::$meta_vote_count,
			     'compare' => 'NOT EXISTS', // works!
			     'value' => '' // This is ignored, but is necessary...
			    ),
			    array(
			     'key' => Kidzou_Vote::$meta_vote_count,
			     'value' => 1
			    )
			)
		);

		 $the_args = array_merge(
	        $the_args, 
	        $vote_args
	      );
    }

    parent::query($the_args);

  }
 
}

?>