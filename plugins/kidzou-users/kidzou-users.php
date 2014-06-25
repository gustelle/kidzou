<?php

/*
Plugin Name: Kidzou Users
Plugin URI: http://www.kidzou.fr
Description: Profils utilisateurs enrichis pour Kidzou - requiert Kidzou Geo
Version: 2014.06.23
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

function kz_hide_personal_options(){
    echo "<script type='text/javascript'>jQuery(document).ready(function($) { $('form#your-profile > h3:first').hide(); $('form#your-profile > table:first').hide(); $('form#your-profile').show(); });</script>" ;
}
add_action('admin_head','kz_hide_personal_options');

define('KIDZOU_USERS_VERSION', '2014.06.23');

register_activation_hook( __FILE__, 'kidzou_users_install' );
register_deactivation_hook( __FILE__, 'kidzou_users_uninstall' );

function kidzou_users_install() {

    add_role(
        'pro',
        __( 'Professionnel' ), 
        array(
            'read'          => true,
            'upload_files'  => true,
            'edit_event'    => true,
            'edit_events'   => true,
            'read_event'    => true,
            'read_private_events' =>true,
            'delete_event'  => true,
            'delete_private_events' => true,
            'edit_private_events' => true,
            'manage_categories' => false,
            // 'assign_terms' => true
            // 'edit_posts'  => true

        )
    );

    
}

function kidzou_users_uninstall() {

    remove_role( 'pro' );

}

/**
 * filtre la liste des evenements dans l'écran d'admin pour que les 'pro', contrib et auteurs
 * ne voient que LEURS contenus, et pas ceux saisis par les autres dans l'admin
 *
 * @return void
 * @see http://shinephp.com/hide-draft-and-pending-posts-from-other-authors/ 
 **/
add_filter('parse_query', 'only_own_events_parse_query' );
function only_own_events_parse_query( $wp_query ) {
    if ( is_admin() && strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-admin/edit.php' ) !== false && 
        ( current_user_can('pro')  || current_user_can('author') || current_user_can('contributor') ) ) {
        global $current_user;
        $wp_query->set( 'author', $current_user->id );
    }
}


//@see http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
add_action( 'edit_user_profile', 'kz_edit_user_info' );
// add_action( 'edit_user_profile', 'kz_edit_card_section' );

/**
 * Adds an additional settings section on the edit user/profile page in the admin.  This section allows admins to 
 * select a metropole from a checkbox of terms from the profession taxonomy.  This is just one example of 
 * many ways this can be handled.
 *
 * @param object $user The user object currently being edited.
 */
function kz_edit_user_info( $user ) {

    $tax = get_taxonomy( 'ville' );

    /* Make sure the user is admin. */
    if ( !current_user_can( 'edit_user' ) )
        return;

    /* Get the terms of the 'profession' taxonomy. */
    $values = kz_covered_metropoles_all_fields();

    //valeur déjà enregistrée pour l'event ?
    $metros = wp_get_object_terms($user->ID, 'ville', array("fields" => "all"));
    $metro = $metros[0]; //le premier (normalement contient 1 seul resultat)

    $radio = empty($metro) ? '' : $metro->term_id;

    wp_nonce_field( 'kz_save_user_nonce', 'kz_user_info_nonce' );

    echo '<h3>Infos Kidzou</h3>';
    echo '<table class="form-table">';

    if ( user_can( $user->ID, 'edit_posts' ) || user_can( $user->ID, 'edit_events' ) ) {

        echo '<tr><th><label for="kz_user_metropole">M&eacute;tropole sur laquelle le user pourra publier</label></th><td>';
        foreach ($values as $value) {
            $id = $value->term_id;
        ?>  
                <input type="radio" name="kz_user_metropole" id="kz_user_metropole_<?php echo $value->slug; ?>" value="<?php echo $id; ?>" <?php echo ($radio == $id)? 'checked="checked"':''; ?>/> <?php echo $value->name; ?><br />
            
        <?php   
        }
    }
    
    $card = get_user_meta( $user->ID, 'kz_has_family_card', TRUE );
    $val = '1';
    if (!$card || $card!=='1') $val = '0';

    echo '</td/></tr>';


    echo '<tr><th><label for="kz_has_family_card">L&apos;utilisateur a la carte famille</label></th><td>';
    echo '<input type="checkbox" name="kz_has_family_card" value="1" '.($val !== "0" ? 'checked="checked"':'').'/> <br />';
    echo '</td></tr>';

    //infos de participation aux jeux concours
    $contests = get_user_meta( $user->ID, 'kz_contests', TRUE );
    echo '<tr><th><label for="kz_contests">Participation aux Jeux Concours</label></th><td>';
    if (is_array($contests) && count($contests)>0)
    {
        foreach ($contests as $contest) {
            $post = get_post($contest); 
            echo '<a href="'.get_permalink( $contest ).'">'.$post->post_title.'</a> , ';
        }
    }
    echo '</td></tr>';

    //les concours gagnés par le user
    $won = get_user_meta( $user->ID, 'kz_contests_winners', TRUE );
    echo '<tr><th><label for="kz_contests_winners">Concours gagn&eacute;s</label></th><td>';
    if (is_array($won) && count($won)>0)
    {
        foreach ($won as $awon) {
            $post = get_post($awon); 
            echo '<a href="'.get_permalink( $awon ).'">'.$post->post_title.'</a> , ';
        }
    }
    echo '</td></tr>';

    echo '</table>';
}


/**
 * déclenchée sur la sauvegarde du user profile dans l'admin
 *
 * @return void
 * @author 
 **/
function kz_save_user_info($user_id) {

    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if( !isset( $_POST['kz_user_info_nonce'] ) || !wp_verify_nonce( $_POST['kz_user_info_nonce'], 'kz_save_user_nonce' ) ) return;
    if ( !current_user_can( 'edit_user', $user_id )) return;

    //meta metropole
    $metropole = $_POST['kz_user_metropole'];
    $result = wp_set_object_terms( $user_id, array( intval($metropole) ), 'ville' );

    // meta de la carte famille
    $card = $_POST['kz_has_family_card']; 

    if (!isset($card)) $card = "0";
    else if ($card!=="1") $card="1";

    update_user_meta( $user_id, 'kz_has_family_card', $card );
    
}
add_action( 'edit_user_profile_update', 'kz_save_user_info');


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_get_user_metropoles ($user_id)
{
    if (!$user_id)
        $user_id = get_current_user_id();

    $meta = wp_get_object_terms( $user_id, 'ville', array('fields' => 'all') );

    return (array)$meta;
}

add_action( 'admin_menu' , 'remove_metaboxes' );
function remove_metaboxes() {

    if (current_user_can( 'pro' ) ) {
        remove_menu_page('upload.php'); //ne pas afficher le menu "media"

        //les taxos standards ne sont de toute façon pas editables par les "pro" 
        remove_meta_box( 'categorydiv' , 'event' , 'side' );
        remove_meta_box( 'agediv' , 'event' , 'side' ); 
        remove_meta_box( 'diversdiv' , 'event' , 'side' );  
        remove_meta_box( 'crp_metabox' , 'event' , 'advanced' );  // ??marche pas
    }

    //suppression de la meta native "Ville" afin de simplifier la saisie de l'evenement 
    //pour les users non admin, la ville est la ville de rattachement du user
    if (!current_user_can('manage_options')) {

        remove_meta_box( 'postcustom' , 'post' , 'normal' ); //removes custom fields 
        remove_meta_box( 'postcustom' , 'event' , 'normal' );
        remove_meta_box( 'postcustom' , 'offres' , 'normal' ); 
        remove_meta_box( 'postcustom' , 'concours' , 'normal' ); 

        remove_meta_box( 'villediv' , 'post' , 'side' ); //removes ville tax
        remove_meta_box( 'villediv' , 'event' , 'side' );
        remove_meta_box( 'villediv' , 'offres' , 'side' ); 
        remove_meta_box( 'villediv' , 'concours' , 'side' );  

    }

}

add_action( 'admin_bar_menu', 'remove_media_node', 999 );
function remove_media_node( $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'new-media' ); //ne pas afficher le medu "media" dans la top bar
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_set_post_user_metropole($post_id)
{
    if (!$post_id) return;

    //la metropole est la metropole de rattachement du user
    $metropoles = (array)kz_get_user_metropoles();
    $ametro = $metropoles[0];
    $metro_id = $ametro->term_id;

    $result = wp_set_post_terms( $post_id, array( intval($metro_id) ), 'ville' );
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_user_has_family_card()
{

    if (current_user_can('manage_options'))
        return true;

    $current_user = wp_get_current_user();

    $umeta = get_user_meta($current_user->ID, 'kz_has_family_card', TRUE);

    return ($umeta!='' && intval($umeta)==1);
}


?>