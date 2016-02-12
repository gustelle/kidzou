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
	 * @todo : transformer en const
	 */ 
	public static $meta_api_key = 'kz_api_key';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_api_quota = 'kz_api_quota';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_api_usage = 'kz_api_usage';

	

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

	public static function getCurrentUsageByKey($key='', $api_name='') {
		
		$usage = 0;

		$usages = (array)self::getUsagesByKey($key, $api_name);
		
		$dStart = new DateTime( );
		$date = $dStart->format('Y-m-d') ;

		if (isset($usages[$date]))
			$usage = intval($usages[$date]);

		return $usage;
	}

	public static function getCurrentUsageByPostID($customer_id=0, $api_name='') {
		
		$usage = 0;

		$usages = (array)self::getUsagesByPostID($customer_id, $api_name);
		
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
	private static function getUsagesByKey($key='',  $api_name='') {

		$customer = self::getPostByKey($key);

		if ( is_wp_error($customer) )
			return new WP_Error('getUsagesByKey', '');
	
		return self::getUsagesByPostID($customer->ID,  $api_name);
	}


	/** 
	 *
	 * Le tableau des stats d'utilisation des API
	 */ 
	private static function getUsagesByPostID($customer_id=0, $api_name='') {

		$usage = 0;

		if ($customer_id==0 || !in_array($api_name, self::getAPINames() ))
			return new WP_Error('getUsagesByPostID', '');

		$usage_array = get_post_meta($customer_id, self::$meta_api_usage,true);

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
	public static function getQuotaByKey($key='', $api_name='', $post_type='post') {

		$customer = self::getPostByKey($key, $post_type);

		if (is_wp_error($customer) || !in_array($api_name, self::getAPINames() ))
			return new WP_Error('getQuotaByKey','');

		return self::getQuotaByPostID($customer->ID, $api_name, $post_type);
	}

	/**
	 *
	 * Fournit le quota d'un client identifié par sa <em>key</em> pour une API donnée
	 *
	 * @param key 
	 * @param api_name
	 *
	 */
	public static function getQuotaByPostID($post_id=0, $api_name='', $post_type='post') {

		if ($post_id==0 || !in_array($api_name, self::getAPINames() ))
			return new WP_Error('getQuotaByPostID','');

		$quota_array = get_post_meta($post_id, self::$meta_api_quota,true);

		//initialisation pour éviter les warnings php
		$quota = -1;

		if(isset($quota_array[$api_name])) 
			$quota = intval($quota_array[$api_name]); 

		//et decrementer son utilisation
		if (!$quota || $quota=='' || intval($quota)<0)
			$quota = 0;

		Kidzou_Utils::log(array(
				'getQuotaByPostID' => array(
						'api_name' => $api_name,
						'customer' => $post_id,
						'quota_array' => $quota_array,
						'quota'=> $quota

					)
			), true);

		return $quota;
	}

	/**
	 * Enregistrement du quota pour l'API donnée
	 *
	 * @param $quota int quota d'appel quotidien
	 * @api_name $api_name string nom de méthode de l'API customer concernée par le quota
	 *
	 **/
	public static function setAPIQuota($post_id=0, $api_name='', $quota=0)
	{	
		if ($post_id==0)
			return new WP_Error('setAPIQuota_1', 'un ID de customer est requis');

		if ($api_name=='')
			return new WP_Error('setAPIQuota_2', 'Aucune Methode d\'API pour le quota');

		if ($quota==0)
			return new WP_Error('setAPIQuota_3', 'Aucun quota indiqué');

		$meta = array();

		$meta[self::$meta_api_key]	= self::getAPIKey($post_id);
		$meta[self::$meta_api_quota] = array($api_name => $quota);

		Kidzou_Utils::save_meta($post_id, $meta);	
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

		$customer = self::getPostByKey($key);

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
		
		$meta[self::$meta_api_usage] = array( $api_name => $usages );

		Kidzou_Utils::save_meta($customer->ID, $meta);

	}

	/**
	 * Retrouve un objet WP_Post par sa Clé d'API
	 *
	 */
	public static function getPostByKey($key='', $post_type='post') {

		if (!$key || $key=='') 
			return new WP_Error( 'getPostByKey_1', __( "clé invalide", "kidzou" ) );
	    	
		//qui est donc notre client ?
		$args = array(
			'posts_per_page' => 1,
			'post_type'	=> $post_type,
			'meta_key' => self::$meta_api_key,
			'meta_value' => $key
		);

		$the_query = new WP_Query( $args );
		Kidzou_Utils::log($the_query->request,true);
		wp_reset_query();

		$results = $the_query->get_posts();

		if (count($results)==0)
			return new WP_Error( 'getPostByKey_2', __( "Cette clé ne correspond à aucun client", "kidzou" ) );

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

		return false;

	}

	/**
	 * retourne la clé d'API d'un post
	 *
	 * @return void
	 * @author 
	 **/
	public static function getAPIKey($post_id = 0)
	{

		if ($post_id==0) 
			return new WP_Error('getAPIKeyByPostID', 'post_id est requis');

		$key = get_post_meta($post_id, self::$meta_api_key, TRUE);
		if ($key == '') {
			$key = md5(uniqid());
		}

		return $key;
	}

	/**
	 * enregistre la clé d'API d'un post
	 *
	 * @return void
	 * @author 
	 **/
	public static function setAPIKey($post_id = 0, $key='')
	{

		if ($post_id==0)
			return new WP_Error('setAPIKey', 'un ID est requis');

		$meta = array();

		$meta[self::$meta_api_key] 	= $key;

		Kidzou_Utils::save_meta($post_id, $meta);
	}


} //fin de classe

?>