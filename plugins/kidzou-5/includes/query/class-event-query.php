<?php

/**
 * Surcharge de WP_Query pour faciliter le requetage des 'Event'. 
 * NB : 'Event' n'est pas un type de post mias déterminé en fonction des meta start_date et end_date d'un post
 *
 * > Les evenements de longue durée sont déclassés pour laisser le place aux autres
 * > Les featured sont positionnés en haut de la liste
 *
 * @see Kidzou_Events::isTypeEvent()
 *
 * @package Kidzou
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Event_Query extends WP_Query {
 
  function __construct($args=array()) {

    $the_args = array_merge($args, array(
          'meta_key' => Kidzou_Events::$meta_start_date , 
          'orderby' => array('meta_value' => 'ASC'),
        ));

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
    
    /**
     * Les events sont retriés pour  que les events de longue durée soit déclassés apres 7j
     */
    add_filter( 'the_posts', array($this, 'reorder'), 1, 2 ); 

    parent::__construct($the_args);

    /**
     * ne pas laisser de trace après que la query soit faite
     */
    remove_filter( 'the_posts', array($this, 'reorder') );


  }
  
  /**
   * Modification de l'ordre d'une liste de WP_Posts pour passer :
   * > en haut de liste les Featured,
   * > en bas de liste les events qui durent > 6j
   * > en bas du bas de la liste, les events qui durent > 13j
   * > en milieu de liste, les autres
   *
   */
  function reorder ($posts, $query=false) { 

    $lowest_prio   = array();
    $low_prio   = array();
    $med_prio   = array();
    $high_prio  = array();

    foreach ($posts as $p) {

      if (Kidzou_Events::isTypeEvent($p->ID)) {

        $duration = Kidzou_Events::getDurationInDays($p->ID);

        if ($duration>13) {
          $lowest_prio[] = $p;
        } else if ($duration>6) {
          $low_prio[] = $p;
        }else {
          $med_prio[] = $p;
        }

      } else {
        $med_prio[] = $p;
      }

      if (Kidzou_Featured::isFeatured($p->ID)) {
        $high_prio[] = $p;
      }

    }

    /**
     * Les sommes de tableau $a+$b écrasent les valeurs lorsque les index sont identiques
     * cette méthode est bourine mais fonctionne bien pour le résultat attendu
     */ 
    $res = array();
    foreach ($high_prio as $p) {
      $res[] = $p;
    }
    foreach ($med_prio as $p) {
      $res[] = $p;
    }
    foreach ($low_prio as $p) {
      $res[] = $p;
    }
    foreach ($lowest_prio as $p) {
      $res[] = $p;
    }

    return $res;
  }
 
}

?>