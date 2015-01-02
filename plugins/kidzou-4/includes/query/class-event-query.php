<?php

class Event_Query extends WP_Query {
 
  function __construct($args=array()) {

    // Kidzou_Utils::log($args);

    $the_args = array_merge($args, array(
          'meta_key' => 'kz_event_start_date' , 
          'orderby' => array('meta_value' => 'ASC'),
        )
      );

    if (isset($args['is_active']) && $args['is_active']==false)
    {
      $current= time();
      $now  = date('Y-m-d 00:00:00', $current);

      $meta_q = array(
                  array(
                         'key' => 'kz_event_end_date',
                         'value' => $now,
                         'compare' => '<',
                         'type' => 'DATETIME'
                        )
                  );

      $the_args = array_merge(
        $the_args, 
        array(
          'meta_query' => $meta_q,
        )
      );

    }
    
    parent::query($the_args);

  }

 
}

?>