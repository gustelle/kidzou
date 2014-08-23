<?php


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

?>