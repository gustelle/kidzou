<?php

/**
 * renvoie l'adresse IP de l'utilisateur
 * pour securiser les vote des users 
 *
 * @return IP Address (String)
 * @author http://www.media-camp.fr/blog/developpement/recuperer-adresse-ip-visiteur-php
 **/
function get_ip()
{
     if ( isset ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    elseif ( isset ( $_SERVER['HTTP_CLIENT_IP'] ) )
    {
        $ip  = $_SERVER['HTTP_CLIENT_IP'];
    }
    else
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * hash pour identifier un user anonyme entre 2 votes
 *
 * @return a hash string to identify "uniquely" an anonymous user
 * @author Kidzou
 **/
function hash_anonymous()
{
  $ip = get_ip(); 
  $ua = $_SERVER['HTTP_USER_AGENT'];

  return md5( $ip . $ua );
}

?>