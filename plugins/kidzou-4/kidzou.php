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
 * Plugin Name:       Kidzou 4
 * Plugin URI:        @TODO
 * Description:       4e version de la plateforme de contenu dédié à la famille, a l'occasion du 4e anniversaire de Kidzou
 * Version:           1.0.0
 * Author:            Guillaume Patin
 * Author URI:        @gustelle
 * Text Domain:       fr_FR
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace `class-plugin-name.php` with the name of the plugin's class file
 *
 */

$directories = array(
            'includes/',
            'includes/query/',
            'includes/TGM/',
            'includes/Carbon/',
            'includes/redux/',
            'public/',
            'public/includes/',
            'public/views/',
        );

foreach ($directories as $directory) {
    foreach(glob( plugin_dir_path( __FILE__ ) .$directory . "*.php") as $class) {
        include_once $class;
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
 * @TODO:
 *
 * - replace Plugin_Name with the name of the class defined in
 *   `class-plugin-name.php`
 */
add_action( 'plugins_loaded', array( 'Kidzou', 			'get_instance' ) );


/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * @TODO:
 *
 * - replace `class-plugin-name-admin.php` with the name of the plugin's admin file
 * - replace Plugin_Name_Admin with the name of the class defined in
 *   `class-plugin-name-admin.php`
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	// require_once( plugin_dir_path( __FILE__ ) . 'admin/class-kidzou-admin.php' );
    $admin_directories = array(
            'admin/',
            'admin/includes/',
            'admin/views/'
    );
    foreach ($admin_directories as $admin_directory) {
        foreach(glob( plugin_dir_path( __FILE__ ) .$admin_directory . "*.php") as $class) {
            include_once $class;
        }
    }

	add_action( 'plugins_loaded', array( 'Kidzou_Admin', 'get_instance' ) );

}


