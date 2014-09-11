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

require_once( plugin_dir_path( __FILE__ ) . 'includes/Tax-meta-class/Tax-meta-class.php');
require_once( plugin_dir_path( __FILE__ ) . 'includes/utils.php' );

require_once( plugin_dir_path( __FILE__ ) . 'public/class-kidzou.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-kidzou-geo.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-kidzou-events.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-kidzou-customer.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-kidzou-offres.php' );

require_once( plugin_dir_path( __FILE__ ) . 'public/includes/vote.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/category-header.php' );

require_once( plugin_dir_path( __FILE__ ) . 'admin/views/metabox.php');
require_once( plugin_dir_path( __FILE__ ) . 'includes/PageTemplater/class-page-templater.php');

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
add_action( 'plugins_loaded', array( 'Kidzou_Events', 	'get_instance' ) );
add_action( 'plugins_loaded', array( 'Kidzou_Geo', 		'get_instance' ) );
add_action( 'plugins_loaded', array( 'Kidzou_Customer', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'Kidzou_Offres', 'get_instance' ) );

//ajouter les templates specifiques à Kidzou
add_action( 'plugins_loaded', array( 'PageTemplater', 	'get_instance' ) );

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

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-kidzou-admin.php' );
	add_action( 'plugins_loaded', array( 'Kidzou_Admin', 'get_instance' ) );

}


