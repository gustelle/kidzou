<?php

class Geo_Query extends WP_Query {
 
  function __construct($args=array()) {


    $the_args = array_merge($args, array(
          'meta_key' => Kidzou_Geoloc::$meta_latitude , 
          // 'orderby' => array('meta_value' => 'ASC'),
        )
      );

    $meta_args = array();
    
    parent::__construct($the_args);

  }

 
}

?>