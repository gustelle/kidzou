<?php

/**
 * 
 *
 * @return Retourne l'URL transformée en fonction des plugins installés 
 * @author 
 **/
function kz_url($url)
{
  if (function_exists('kz_geo_filter_page') )
    return kz_geo_filter_page($url, null); 

  return $url;
}


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

/**
* retourne le slug courant
* utilisé dans category-header.php pour retrouver le tax meta de la categorie courante
**/
function get_the_slug() {

	global $post;
	if ( is_single() || is_page() ) 
		return $post->post_name;
	else if (is_category()) 
	{
		$cat = get_query_var('cat');
  		$yourcat = get_category ($cat);
		return $yourcat->slug;
	}
	return "";
} 

//troncature d un texte
//utilisé dans article.php
function limit_text($text, $limit) {
      if (str_word_count($text, 0) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
      }
      return $text;
    }

/**
 * undocumented function
 *
 * @return le microdata spécifique de la webpage
 * @see http://schema.org/WebPage
 **/
function kz_webpage_microdata ()
{
	if (is_search())
		echo 'itemscope itemtype="http://schema.org/SearchResultsPage"';
	elseif (is_category() || is_tax() || is_archive()) 
		echo 'itemscope itemtype="http://schema.org/CollectionPage"';
  else
    echo 'itemscope itemtype="http://schema.org/WebPage"';
}


function dump_request( $input ) 
{

    var_dump($input);

    return $input;
}

function kz_truncate_text($string, $limit, $break=".", $pad="...") { 
  // return with no change if string is shorter than $limit 
  if(strlen($string) <= $limit) return $string; 
  
  // is $break present between $limit and the end of the string? 
  if(false !== ($breakpoint = strpos($string, $break, $limit))) { 
    if($breakpoint < strlen($string) - 1) { 
      $string = substr($string, 0, $breakpoint) . $pad; 
    } 
  } 
  return $string; 
}


function kz_get_term_root_parent($term_id, $taxonomy) {
    $parent  = get_term_by( 'id', $term_id, $taxonomy);
    while ($parent->parent != 0){
        $parent  = get_term_by( 'id', $parent->parent, $taxonomy);
    }
    return $parent;
}

/**
 * undocumented function
 *
 * @return true si l'événement est en cours, false si il est terminé ou pas visible
 * @author 
 **/
function  kz_is_post()
{
  global $post;
  $type = get_post_type($post);
  return $type=='post';
}


?>