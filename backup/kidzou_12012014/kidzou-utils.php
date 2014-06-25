<?php


function catch_first_image($html) {
 
  $first_img = '';
  ob_start();
  ob_end_clean();
  $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', stripslashes($html), $matches);
  $first_img = $matches[1][0];

  if (is_int($first_img)) {
        $first_img = wp_get_attachment_image_src($first_img);
        $first_img = $first_img[0];
    }

  return $first_img;
}

/**
 * retourne la source du thumb d'une fiche
 *
 * @return void
 * @author 
 * @see http://codex.wordpress.org/Function_Reference/get_children
 **/
function catch_post_thumb ($post_id)
{

  //si un thumbnail existe
  if(has_post_thumbnail($post_id)) 
  {
    $tid = get_post_thumbnail_id( $post_id);
    $thumb = wp_get_attachment_image_src($tid, 'thumbnail');
    return $thumb[0];
  }

  //sinon
  $args = array(
    'numberposts' => 1,
    'order' => 'ASC',
    'post_mime_type' => 'image',
    'post_parent' => $post_id,
    'post_status' => null,
    'post_type' => 'attachment',
  );

  $attachments = get_children( $args );

  if ( $attachments ) {
    $thumb = '';
    foreach ( $attachments as $attachment ) {
      //$image_attributes = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' )  ? wp_get_attachment_image_src( $attachment->ID, 'thumbnail' ) : wp_get_attachment_image_src( $attachment->ID, 'full' );
      $thumb = wp_get_attachment_thumb_url( $attachment->ID ) ;
    }
    //echo 'get_children : '. $thumb;
    return $thumb;
  }

  return '';

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
 * undocumented function
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

/**
 * utilse pour nettoyer les adresses des events, afin de transmettre les adresses à Google Map
 *
 * @return une chaine nettoyée des retours chariots et des tabulations
 * @see 
 **/
function kz_sanitize_text($text) 
{
    $tabs = array("\t", "\n", "\r");
    $t = str_replace($tabs, " ", $text); 
    return trim ($t);
}



?>