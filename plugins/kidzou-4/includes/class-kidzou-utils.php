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

	public static function log( $log, $force = false) {

		$logme = $force ;

		if (!$logme)
			$logme = ( true === WP_DEBUG && self::current_user_is('admin') );

        if ( $logme ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
 
	}

	public static function printStackTrace() {

	    $e = new Exception();
	    $trace = explode("\n", $e->getTraceAsString());
	    // reverse array to make steps line up chronologically
	    $trace = array_reverse($trace);
	    array_shift($trace); // remove {main}
	    array_pop($trace); // remove call to this method
	    $length = count($trace);
	    $result = array();
	    
	    for ($i = 0; $i < $length; $i++)
	    {
	        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	    }
	    
	    self::log( "\t" . implode("\n\t", $result) );

	}

	public static function get_option( $option_name='', $default='' ) {

		global $kidzou_options;

		if (''==$option_name)
			return $default;

		if (isset( $kidzou_options[$option_name] ) )
			return $kidzou_options[$option_name];

		return $default;

	}

	/**
	 * les AJAX sont identifiées dans le domaine de l'admin
	 * il faut les exclure
	 *
	 * @return Bool
	 * @author 
	 **/
	public static function is_really_admin( ) {

		if (defined('DOING_AJAX') && DOING_AJAX)
			return false;

		return is_admin() ;

	}

	/**
	 * true si la requete en cours est une api json
	 *
	 * @return Bool
	 * @author 
	 **/
	public static function is_api()
	{
		return preg_match( '#\/api\/#', self::get_request_path() );
	}

	public static function get_request_path() {

		return $_SERVER['REQUEST_URI'];
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function current_user_is($role = '')
	{
		// Kidzou_Utils::log('Kidzou_Utils [current_user_is] '. $role, true );
		// return true;

		$is_user = false;
		switch ($role) {
			case 'subscriber':
				$is_user = current_user_can('read');
				break;

			case 'contributor':
				$is_user = current_user_can('edit_posts');
				break;

			case 'author':
				$is_user = current_user_can('edit_published_posts');
				break;

			case 'editor':
				$is_user = current_user_can('manage_categories');
				break;

			case 'admin':
				$is_user = current_user_can('manage_options');
				break;

			case 'administrator':
				$is_user = current_user_can('manage_options');
				break;

			default:
				return new WP_Error( 'unknown_role', __( "Role inconnu", "kidzou" ) );
				break;
		}

		// Kidzou_Utils::log('Kidzou_Utils [current_user_is] '. $role . ' = ' . ($is_user ? 'yes' : 'no') , true );

		return $is_user;
	}

	/**
	 * Allow to remove method for an hook when, it's a class method used and class don't have global for instanciation !
	 *
	 * @since customer-analytics
	 * @author BeAPI - Copyright 2012 Amaury Balmer - amaury@beapi.fr
	 * @see https://github.com/herewithme/wp-filters-extras/blob/master/wp-filters-extras.php
	 **/
	function remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
		global $wp_filter;
		
		// Take only filters on right hook name and priority
		if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) )
			return false;
		
		// Loop on filters registered
		foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
			// Test if filter is an array ! (always for class/method)
			if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
				// Test if object is a class and method is equal to param !
				if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && $filter_array['function'][1] == $method_name ) {
					unset($wp_filter[$hook_name][$priority][$unique_id]);
				}
			}
			
		}
		
		return false;
	}

	/**
	 * Allow to remove method for an hook when, it's a class method used and class don't have variable, but you know the class name :)
	 * 
	 * @since customer-analytics
	 * @author BeAPI - Copyright 2012 Amaury Balmer - amaury@beapi.fr
	 * @see https://github.com/herewithme/wp-filters-extras/blob/master/wp-filters-extras.php
	 **/
	function remove_filters_for_anonymous_class( $hook_name = '', $class_name ='', $method_name = '', $priority = 0 ) {
		global $wp_filter;
		
		// Take only filters on right hook name and priority
		if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) )
			return false;
		
		// Loop on filters registered
		foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
			// Test if filter is an array ! (always for class/method)
			if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
				// Test if object is a class, class and method is equal to param !
				if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && get_class($filter_array['function'][0]) == $class_name && $filter_array['function'][1] == $method_name ) {
					unset($wp_filter[$hook_name][$priority][$unique_id]);
				}
			}
			
		}
		
		return false;
	}



} //fin de classe

?>