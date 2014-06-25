<?php 

/**
 * customisation de la barre d'admin pour les users identifiés
 *
 * @return void
 * @author 
 **/

function kz_admin_bar () {
	add_action( 'admin_bar_menu', 'kz_admin_bar_logo', 30 );
	add_action( 'admin_bar_menu', 'kz_admin_bar_espace_prive', 40 );
}
function kz_admin_bar_logo() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo');
	$wp_admin_bar->remove_menu('site-name');
	$wp_admin_bar->remove_menu('updates');
	$wp_admin_bar->add_menu( array(
        'id' => 'kz-logo', // an unique id (required)
        'parent' => false, // false for a top level menu
        'title' => '<span class="ab-icon"></span>', // title/menu text to display
        'href' => site_url(), // target url of this menu item
        // optional meta array
        'meta' => array(
            'onclick' => '',
            'html' => '',
            'class' => '',
            'target' => '',
            'title' => ''
        )
    ) );
}
function kz_admin_bar_espace_prive() {
	global $wp_admin_bar;
	$wp_admin_bar->add_menu( array( 'id' => 'espace-membre', 'title' => __( 'Espace Membres' ), 'href' => site_url().'/espace-membre/' ) );
}
add_action('add_admin_bar_menus', 'kz_admin_bar');





?>