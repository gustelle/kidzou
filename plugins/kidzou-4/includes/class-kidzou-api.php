<?php

add_action( 'plugins_loaded', array( 'Kidzou_API', 'get_instance' ), 100);



/**
 * Gestion des accès aux API : droit, incrémentation du quota, ...
 *
 * @package Kidzou
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

	/**
	 *
	 * la tableau des noms des API, correspondant aux noms de méthodes  dand JSON_API_Content_Controller
	 */
	public static function getAPINames() {

		require_once(plugin_dir_path( __FILE__ ) ."/api/content.php");

		return get_class_methods('JSON_API_Content_Controller');
	}

	public static function getCurrentUsage($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);
		$usage = 0;

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return 0;

		$usages = (array)self::getUsages($key, $api_name);
		
		$dStart = new DateTime( );
		$date = $dStart->format('Y-m-d') ;

		if (isset($usages[$date]))
			$usage = intval($usages[$date]);

		return $usage;

	}

	/** 
	 *
	 * Le tableau des stats d'utilisation des API
	 */ 
	public static function getUsages($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);
		$usage = 0;

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return 0;

		$usage_array = get_post_meta($customer->ID, Kidzou_Customer::$meta_api_usage,true);

		if (!isset( $usage_array[$api_name] ) || !is_array($usage_array[$api_name]))
			$usages = array();
		else
			$usages = (array)$usage_array[$api_name];
	
		return $usages;


	}
	
	/**
	 *
	 * Fournit le quota d'un client identifié par sa <em>key</em> pour une API donnée
	 *
	 * @param key 
	 * @param api_name
	 *
	 */
	public static function getQuota($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return 0;

		$quota_array = get_post_meta($customer->ID, Kidzou_Customer::$meta_api_quota,true);

		//initialisation pour éviter les warnings php
		$quota = -1;

		if(isset($quota_array[$api_name])) 
			$quota = intval($quota_array[$api_name]); 

		//et decrementer son utilisation
		if (!$quota || $quota=='' || intval($quota)<0)
			$quota = 0;

		Kidzou_Utils::log(array(
				'getQuota' => array(
						'key' => $key,
						'api_name' => $api_name,
						'customer' => $customer->ID,
						'quota_array' => $quota_array,
						'quota'=> $quota

					)
			), true);

		return $quota;

	}

	public static function isQuotaOK($key='', $api_name='') {

		$quota = self::getQuota($key, $api_name);
		$usage = self::getCurrentUsage($key, $api_name);

		Kidzou_Utils::log(
			array('isQuotaOK' => array(
					'key' => $key,
					'api_name'=> $api_name,
					'quota'=> $quota, 
					'usage'=>$usage
				)
			), true);

		return ($quota-$usage)>0;
	}

	public static function incrementUsage($key='', $api_name='') {

		$customer = self::getCustomerByKey($key);

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return new WP_Error( 'invalid_data', __( "Clé ou API invalide", "kidzou" ) );

		$meta = array();
		
		$usages = (array)self::getUsages($key, $api_name);
		$usage = 0;

		$dStart = new DateTime( );
		$date = $dStart->format('Y-m-d') ;

		if (isset($usages[$date]))
			$usage = intval($usages[$date]);

		$usage++;
		$usages[$date] = $usage;

		$entries = Kidzou_Utils::get_option('api_usage_history', 1);

		if ( count($usages)>intval($entries) ) {
			array_shift($usages);
		}
		
		$meta[Kidzou_Customer::$meta_api_usage] = array( $api_name => $usages );

		Kidzou_Utils::save_meta($customer->ID, $meta);

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
	 * les cles publiques permettent l'utilisation d'API de facon publique (non authentifié)
	 * Une clé publique n'appartient pas à un client mais à tout le monde
	 *
	 * @param key : une cle publique (String)
	 */
	public static function isPublicKey($key) {

		$public_keys = Kidzou_Utils::get_option('api_public_key');

		foreach ($public_keys as $a_key) {
		    if ($a_key==$key) return true;
		}

		// Kidzou_Utils::log('Public Key : '.$public_key, true);

		return false;

	}


	// /**
	//  * fonction utilitaire
	//  */
	// public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {

	// 	if ($post_id==0)
	// 		return;

	// 	// Add values of $events_meta as custom fields
	// 	foreach ($arr as $key => $value) { // Cycle through the $events_meta array!

	// 		$pref_key = $prefix.$key; 
	// 		$prev = get_post_meta($post_id, $pref_key, TRUE);

	// 		if ($prev!='') { // If the custom field already has a value
	// 			update_post_meta($post_id, $pref_key, $value);
	// 		} else { // If the custom field doesn't have a value
	// 			if ($prev=='') delete_post_meta($post_id, $pref_key);
	// 			add_post_meta($post_id, $pref_key, $value, TRUE);
	// 		}
	// 		if(!$value) delete_post_meta($post_id, $pref_key); // Delete if blank
	// 	}

	// }



} //fin de classe

?>