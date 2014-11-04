<?php

add_action( 'kidzou_loaded', array( 'Kidzou_API', 'get_instance' ) );


/**
 * Kidzou
 *
 * @package   Kidzou_API
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
 * @package Kidzou_API
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_API {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '04-nov';

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

	public static function getAPINames() {

		require_once(plugin_dir_path( __FILE__ ) ."/api/content.php");

		return get_class_methods('JSON_API_Content_Controller');
	}

	public static function getCurrentUsage($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);
		$usage = 0;

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return 0;

		$usage_array = get_post_meta($customer->ID, Kidzou_Customer::$meta_api_usage,true);

		if(isset($usage_array[$api_name])) 
			$usage = intval($usage_array[$api_name]); 

		if (intval($usage)<0)
			$usage = 0;

		return $usage;


	}
	
	public static function getQuota($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return 0;

		$quota_array = get_post_meta($customer->ID, Kidzou_Customer::$meta_api_quota,true);

		if(isset($quota_array[$api_name])) 
			$quota = intval($quota_array[$api_name]); 

		//et decrementer son utilisation
		if (!$quota || $quota=='' || intval($quota)<0)
			$quota = 0;

		return $quota;

	}

	public static function isQuotaOK($key='', $api_name='') {

		$quota = self::getQuota($key, $api_name);
		$usage = self::getCurrentUsage($key, $api_name);

		return ($quota-$usage)>0;
	}

	public static function incrementUsage($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return new WP_Error( 'unvalid_data', __( "Clé ou API invalide", "kidzou" ) );

		$meta = array();
		
		$usage = self::getCurrentUsage($key, $api_name);
		$usage++;
		
		$meta[Kidzou_Customer::$meta_api_usage] = array( $api_name => $usage );

		self::save_meta($customer->ID, $meta);

	}

	public static function getCustomerByKey($key) {

		if (!$key) 
			return new WP_Error( 'unvalid_key', __( "Votre clé n'est pas valide", "kidzou" ) );
	    	
		//qui est donc notre client ?
		$args = array(
			'posts_per_page' => 1,
			'post_type'	=> 'customer',
			'meta_key' => Kidzou_Customer::$meta_api_key,
			'meta_value' => $key
		);

		$the_query = new WP_Query( $args );

		wp_reset_query();

		$results = $the_query->get_posts();

		if (count($results)==0)
			return new WP_Error( 'unvalid_key', __( "Votre clé n'est pas valide", "kidzou" ) );

		$customer = $results[0];

		return $customer;
	}

	/**
	 * fonction utilitaire
	 */
	public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {

		if ($post_id==0)
			return;

		// Add values of $events_meta as custom fields
		foreach ($arr as $key => $value) { // Cycle through the $events_meta array!

			$pref_key = $prefix.$key; 
			$prev = get_post_meta($post_id, $pref_key, TRUE);

			if ($prev!='') { // If the custom field already has a value
				update_post_meta($post_id, $pref_key, $value);
			} else { // If the custom field doesn't have a value
				if ($prev=='') delete_post_meta($post_id, $pref_key);
				add_post_meta($post_id, $pref_key, $value, TRUE);
			}
			if(!$value) delete_post_meta($post_id, $pref_key); // Delete if blank
		}

	}



} //fin de classe

?>