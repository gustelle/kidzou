<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   Kidzou
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 *
 * @wordpress-plugin
 * Plugin Name:       Kidzou V
 * Description:       5e version de la plateforme Kidzou, Grosse simplification des features développées en Kidzou 4
 * Version:           0.4
 * Author:            Guillaume Patin
 * Author URI:        @gustelle
 * Text Domain:       fr_FR
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Shared Functionality
 *----------------------------------------------------------------------------*/

$directories = array(
            'public/',          //loads class-kidzou.php
            // 'public/includes/', 
            'includes/',        //loads the core business classes
            'includes/query/',
            'includes/TGM/',
            'includes/Carbon/',
            'includes/redux/',
            'includes/MailChimp/',
            'includes/gravityforms/',
        );

foreach ($directories as $directory) {
    foreach(glob( plugin_dir_path( __FILE__ ) .$directory . "*.php") as $class) {
        include_once $class;
    }
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

if (!is_admin()) {

    $public_directories = array(
            'public/views/',
    );

    foreach ($public_directories as $public_dir) {
        foreach(glob( plugin_dir_path( __FILE__ ) .$public_dir . "*.php") as $pub_class) {
            include_once $pub_class;
        }
    }

}

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 * @TODO:
 *
 * - replace Plugin_Name with the name of the class defined in
 *   `class-plugin-name.php`
 */
register_activation_hook( __FILE__, array( 'Kidzou', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Kidzou', 'deactivate' ) );

/*
 * Charger le plus tot possible cette classe
 * qui va faire les register des taxonomies utilisées ensuite dans bcp d'autres classes
 * 
 */
add_action( 'plugins_loaded', array( 'Kidzou', 	'get_instance' ), 0 );


/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * chargement des classes d'admin
 * 
 * <ul>
 * <li>Les classes D'admin ne sont pas chargées pour les requetes Ajax (admin-ajax)</li>
 * <li>Les classes d'admin sont chargées Pour les CRON (ex: <code>unpublish_obsolete_posts</code> nécessite Kidzou_Admin pour modifier les meta</li>
 * </ul>
 * 
 */
if ( is_admin() || defined( 'DOING_CRON' ) ) {

	// require_once( plugin_dir_path( __FILE__ ) . 'admin/class-kidzou-admin.php' );
    $admin_directories = array(
            'admin/',
            'admin/includes/',
            'admin/views/',
            // 'admin/includes/simplehtmldom/'
    );

    foreach ($admin_directories as $admin_directory) {
        foreach(glob( plugin_dir_path( __FILE__ ) .$admin_directory . "*.php") as $admin_class) {
            include_once $admin_class;
        }
    }

	add_action( 'plugins_loaded', array( 'Kidzou_Admin', 'get_instance' ) );

}
?>
