<?php
/*
Plugin Name: Kidzou
Plugin URI: http://www.kidzou.fr
Description: Refonte de l'architecture JS, background pour anniversaire kidzou (3 ans), dequeue des scripts fancybox et et-ptemplates-frontend
Version: 2013.11.21
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

define(KIDZOU_VERSION,'2014.01.12-fix3');

require_once (plugin_dir_path( __FILE__ ) . '/kidzou-utils.php'); 
require_once (plugin_dir_path( __FILE__ ) . '/kidzou-enqueue.php'); //styles et css
require_once (plugin_dir_path( __FILE__ ) . '/kidzou-admin.php');

require_once (plugin_dir_path( __FILE__ ) . '/modules/ui/admin/kidzou-admin.php'); //customisation de la barre d'admin qd le user est loggué

require_once (plugin_dir_path( __FILE__ ) . '/modules/featured/kidzou-featured.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/ads/kidzou-taxmeta.php'); //gestion d'images sur les catégories
require_once (plugin_dir_path( __FILE__ ) . '/modules/ads/kidzou-ads.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/annuaire/kidzou-to-connections.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/events/kidzou-to-rse.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/json/kidzou-to-json-api.php');

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

function kidzou_excerpt_length( $length ) {
	return 200;
}
add_filter( 'excerpt_length', 'kidzou_excerpt_length', 999 );

//ajout d'un thumbnail dans les feeds
function rss_post_thumbnail($content) {
	global $post;
	if(has_post_thumbnail($post->ID)) {
	$content = '<p>' . get_the_post_thumbnail($post->ID) .
	'</p>' . get_the_content();
	}
	return $content;
}
//add_filter('the_excerpt_rss', 'rss_post_thumbnail');
//add_filter('the_content_feed', 'rss_post_thumbnail');


?>