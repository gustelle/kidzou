<?php
/*
Plugin Name: Kidzou
Plugin URI: http://www.kidzou.fr
Description: Commentaires featured, Image et Featured sur les Taxonomies, Vote sur les posts, Liaison Post / Plugin Connections, Upload media par author
Version: 2014.06.23
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

define('KIDZOU_VERSION','2014.06.23');

require_once (plugin_dir_path( __FILE__ ) . '/kidzou-utils.php'); 
require_once (plugin_dir_path( __FILE__ ) . '/kidzou-enqueue.php'); //styles et css
require_once (plugin_dir_path( __FILE__ ) . '/kidzou-admin.php');

require_once( plugin_dir_path( __FILE__ ) . '/modules/Tax-Meta-Class/Tax-meta-class/Tax-meta-class.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/featured/kidzou-featured.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/ads/kidzou-ads.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/annuaire/kidzou-to-connections.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/json/kidzou-to-json-api.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/megadropdown/kidzou-megadropdown.php');

require_once (plugin_dir_path( __FILE__ ) . '/modules/post-types/kidzou-offres.php');

require_once (plugin_dir_path( __FILE__ ) . '/modules/taxonomies/kidzou-taxo.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/taxonomies/kidzou-category-tax.php');
require_once (plugin_dir_path( __FILE__ ) . '/modules/taxonomies/kidzou-ville-tax.php');


//inversion des commentaires
//trouvé sur http://premium.wpmudev.org/blog/daily-tip-how-to-reverse-wordpress-comment-order-to-show-the-latest-on-top/
if (!function_exists('iweb_reverse_comments')) {
    function iweb_reverse_comments($comments) {
        return array_reverse($comments);
    }   
}
add_filter ('comments_array', 'iweb_reverse_comments');


//On plugin activation 
//schedule the notifications to notify admins when an event is requested for pulblish
// register_activation_hook( __FILE__, 'kz_notifications_activation' );

//http://wordpress.stackexchange.com/questions/25894/how-can-i-organize-the-uploads-folder-by-slug-or-id-or-filetype-or-author
// add_filter('wp_handle_upload_prefilter', 'kz_handle_upload_prefilter');
// add_filter('wp_handle_upload', 'kz_handle_upload');

function kz_handle_upload_prefilter( $file )
{
    add_filter('upload_dir', 'kz_custom_upload_dir');
    return $file;
}

function kz_handle_upload( $fileinfo )
{
    remove_filter('upload_dir', 'kz_custom_upload_dir');
    return $fileinfo;
}

function kz_custom_upload_dir($path)
{   
    /*
     * Determines if uploading from inside a post/page/cpt - if not, default Upload folder is used
     */
    $use_default_dir = ( isset($_REQUEST['post_id'] ) && $_REQUEST['post_id'] == 0 ) ? true : false; 
    if( !empty( $path['error'] ) || $use_default_dir )
        return $path; //error or uploading not from a post/page/cpt 

    /*
     * Save uploads in ID based folders 
     *
     */

    /*
     $customdir = '/' . $_REQUEST['post_id'];
    */


    /*
     * Save uploads in SLUG based folders 
     *
    

     $the_post = get_post($_REQUEST['post_id']);
     $customdir = '/' . $the_post->post_name;
      */


    /*
     * Save uploads in AUTHOR based folders 
     *
     * ATTENTION, CAUTION REQUIRED: 
     * This one may have security implications as you will be exposing the user names in the media paths
     * Here, the *display_name* is being used, but normally it is the same as *user_login*
     *
     * The right thing to do would be making the first/last name mandatories
     * And use:
     * $customdir = '/' . $the_author->first_name . $the_author->last_name;
     *
     */

	$the_post = get_post($_REQUEST['post_id']);
	$the_author = get_user_by('id', $the_post->post_author);
	$customdir = '/' . $the_author->data->user_login; //alternative : display_name

    /*
     * Save uploads in FILETYPE based folders 
     * when using this method, you may want to change the check for $use_default_dir
     *
     */

    /*
     $extension = substr( strrchr( $_POST['name'], '.' ), 1 );
     switch( $extension )
     {
        case 'jpg':
        case 'png':
        case 'gif':
            $customdir = '/images';
            break;

        case 'mp4':
        case 'm4v':
            $customdir = '/videos';
            break;

        case 'txt':
        case 'doc':
        case 'pdf':
            $customdir = '/documents';
            break;

        default:
            $customdir = '/others';
            break;
     }
    */

    $path['path']    = str_replace($path['subdir'], '', $path['path']); //remove default subdir (year/month)
    $path['url']     = str_replace($path['subdir'], '', $path['url']);      
    $path['subdir']  = $customdir;
    $path['path']   .= $customdir; 
    $path['url']    .= $customdir;  

    return $path;
}

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

/**
 * partager un post sur facebook
 *
 * @return HTML snippet
 * @author
 **/
function kz_fb_share ()
{
    $out = "<a rel='nofollow' href='#' onclick='return fbs_click()' target='_blank' class='fb_share_link'>Partager sur Facebook</a>";
    return $out;
}





?>