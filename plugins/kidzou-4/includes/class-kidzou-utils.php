<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Utils', 'get_instance' ) );


/**
 * Kidzou
 *
 * @package   Kidzou_Utils
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Kidzou_Utils
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Utils {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	// const VERSION = '04-nov';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	

	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function log( $log ) {

        if ( true === WP_DEBUG && current_user_can('manage_options') ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
 
	}

	public static function get_option( $option_name='', $default='' ) {

		global $kidzou_options;

		if (''==$option_name)
			return $default;

		if (isset( $kidzou_options[$option_name] ) )
			return $kidzou_options[$option_name];

		return $default;

	}

	public static function get_request_path() {

		return $_SERVER['REQUEST_URI'];
	}



} //fin de classe

?>