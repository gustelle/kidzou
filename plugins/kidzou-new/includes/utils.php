<?php


function get_user($type = 'ID')
{
     global $current_user;
     get_currentuserinfo();

     switch ($type)
     {
          case 'ID':
               return $current_user->ID;
               break;
          case 'displayname':
               return $current_user->display_name;
               break;
          case 'username':
               return $current_user->user_login;
               break;
          case 'firstname':
               return $current_user->user_firstname;
               break;
          case 'lastname':
               return $current_user->user_lastname;
               break;
          case 'level':
               return $current_user->user_level;
               break;
          case 'email':
               return $current_user->user_email;
               break;
          default:
               return $current_user->ID;
     }
}




/**
 * troncature d un texte utilisé dans article.php
 *
 * @return texte  
 * @see 
 * @deprecated
 *
 **/
function limit_text($text, $limit) {
     if (str_word_count($text, 0) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
     }
     return $text;
}


/**
 * pour différencier l'affichage d'un post d'un autre type de contenu (event)
 *
 * @return Booléen
 * @author 
 **/
function  kz_is_post() {

  global $post;
  $type = get_post_type($post);
  return $type=='post';
  
}


?>