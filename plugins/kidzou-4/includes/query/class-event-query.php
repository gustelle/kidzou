<?php

/**
 * Surcharge de WP_Query pour faciliter le requetage des 'Event'. NB : 'Event' n'est pas un type de post mias déterminé en fonction des meta start_date et end_date d'un post
 *
 * @see Kidzou_Events::isTypeEvent()
 * @package Kidzou
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Event_Query extends WP_Query {
 
  function __construct($args=array()) {


    $the_args = array_merge($args, array(
          'meta_key' => Kidzou_Events::$meta_start_date , 
          'orderby' => array('meta_value' => 'ASC'),
        )
      );

    $meta_args = array();

    if (isset($args['is_active']) && $args['is_active']==false)
    {
      $current= time();
      $now  = date('Y-m-d 00:00:00', $current);

      $meta_args[] = array(
                      array(
                             'key' => Kidzou_Events::$meta_end_date,
                             'value' => $now,
                             'compare' => '<',
                             'type' => 'DATETIME'
                            )
                    );

    } 

    //requete sur les archives
    if (isset($args['is_archive'])) {

      $meta_args['relation'] = 'AND';

      if ($args['is_archive']==true) {
        $meta_args[] = array(
            'key' => Kidzou_Events::$meta_archive,
            'value' => true,
          ); 
      } else {
        //si is_archive est à false, la meta n'est pas censé exister
        $meta_args[] = array(
           'key' => Kidzou_Events::$meta_archive,
           'compare' => 'NOT EXISTS', // works!
          );
      }

    }

    if (count($meta_args)>0) {
      $the_args = array_merge(
        $the_args, 
          array(
           'meta_query' => $meta_args,
          )
      );
    }
    
    parent::query($the_args);

  }

 
}

?>