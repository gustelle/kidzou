<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Customer', 'get_instance' ) );


/**
 * Kidzou
 *
 * @package   Kidzou_Customer
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
 * @package Kidzou_Customer
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Customer {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $meta = '';

	public static $meta_customer = 'kz_customer';

	public static $meta_api_key = 'kz_api_key';

	public static $meta_api_quota = 'kz_api_quota';

	public static $meta_api_usage = 'kz_api_usage';

	public static $meta_customer_analytics = 'kz_customer_analytics';

	/**
	 * Le post type d'un customer
	 *
	 */
	public static $post_type = 'customer';

	/**
	 * Les post types supportés pour se raccrocher à un customer
	 *
	 */
	public static $supported_post_types = array('product', 'post', 'offres' );



	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 


		add_action('init', array($this, 'register_customer_type'));

		//pour le F.O
		if (class_exists('GADASH_Frontend'))
		{
			add_action('wp', array($this, 'check_customer_analytics'), 0);
		}

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
	 * Si les analytics sont actifs et que le user a le droit
	 * on ouvre les tuyaux pour afficher les analytics en bas de page
	 *
	 * @return void
	 * @since customer-analytics
	 * @see https://wordpress.org/plugins/google-analytics-dashboard-for-wp/
	 * @author 
	 **/
	public function check_customer_analytics()
	{
		if (is_user_logged_in() && !is_admin() && !Kidzou_Utils::current_user_is('author'))
		{
			$remove_analytics = false;
			$activate = Kidzou_Utils::get_option('customer_analytics_activate', false);
		
			if (!$activate)
			{
				//checker si le user à le droit de voir les analytics
				// Kidzou_Utils::log('Analytics non actifs');
				$remove_analytics = true;
			}
			else
			{	
				global $post;
				// Kidzou_Utils::log($post);
				//vérif que le customer de la page courante est autorisé à visualiser ses analytics
				$customer_id = self::getCustomerIDByPostID( $post->ID );
				$is_authorized = self::isAnalyticsAuthorizedForCustomer($customer_id);

				if (!$is_authorized)
				{
					// Kidzou_Utils::log('Client non autorisé pour les analytics ' . $is_authorized);
					$remove_analytics = true;
				}
				else
				{	
					//le client du post
					$current_user_customers = self::getCustomersIDByUserID();

					//hack : les auteurs voient tjrs les analytics
					// if (Kidzou_Utils::current_user_is('author')) $current_user_customers[] = $customer_id;

					if ( !in_array($customer_id, $current_user_customers) )
					{
						$remove_analytics = true;
					}
					// else 
					// 	Kidzou_Utils::log('Bande de veinards');
				}
				
			}

			if ($remove_analytics)
			{
				Kidzou_Utils::remove_filters_for_anonymous_class('the_content', 'GADASH_Frontend', 'ga_dash_front_content', 10);
				Kidzou_Utils::remove_filters_for_anonymous_class('wp_enqueue_scripts', 'GADASH_Frontend', 'ga_dash_front_enqueue_styles', 10);
			}
		}

	

	}

	

	public function register_customer_type() {

		//definir les custom post types
		//ne pas faire a chaque appel de page 

		$labels = array(
			'name'               => 'Clients &amp; Partenaires',
			'singular_name'      => 'Client ou Partenaire',
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Ajouter un client/partenaire',
			'edit_item'          => 'Modifier le client/partenaire',
			'new_item'           => 'Nouveau client/partenaire',
			'all_items'          => 'Tous les clients/partenaires',
			'view_item'          => 'Voir le client/partenaire',
			'search_items'       => 'Chercher des clients/partenaires',
			'not_found'          => 'Aucun client ou partenaire trouvé',
			'not_found_in_trash' => 'Aucun client our partenaire trouvé dans la corbeille',
			'menu_name'          => 'Clients &amp; Partenaires',
			);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position' 	 => 20, //sous les pages
			'menu_icon' 		 => 'dashicons-businessman',
			'supports' 			=> array( 'title', 'author', 'revisions'),
			);

		register_post_type( self::$post_type, $args );

	}
	

    /**
	 * le customer d'un post, ou 0 si le post n'est pas attaché a un customer
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerIDByPostID($post_id = 0)
	{

		if ($post_id==0)
		{
			global $post; 
			$post_id = $post->ID; 
		}

		//si le post est un customer on jette une erreur
		$post = get_post($post_id);

		if (get_post_type($post)==self::$post_type)
			return new WP_Error( 'not_a_post', __( "L'ID correspond déjà à un Client", "kidzou" ) );

		$customer = get_post_meta($post_id, self::$meta_customer, TRUE);

		if (!$customer || $customer=='')
			$customer = 0;

		// Kidzou_Utils::log('getCustomerIDByPostID '. $customer);

		return intval($customer);
	}

	/**
	 * le nom du client par son ID
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerNameByCustomerID($customer_id = 0)
	{

		$customer = self::getCustomerByID($customer_id);

		if (!is_wp_error($customer))
			return $customer->post_title;

		return $customer;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerByID ($customer_id = 0)
	{
		if ($customer_id==0)
			new WP_Error( 'null_customer', __( "L'ID du client est attendu !", "kidzou" ) );

		$customer = get_post($customer_id);

		if (get_post_type($customer)==self::$post_type)
			return $customer;

		return new WP_Error( 'not_a_customer', __( "L'objet correspondant n'est pas un client !", "kidzou" ) );
	}


	/**
	 * les posts d'un customer (géolocalisé)
	 *
	 * @return array of posts
	 * @author 
	 **/
	public static function getPostsByCustomerID($customer_id = 0, $args= array()) {

		$posts = array();

		if ($customer_id==0) {

			$customer_id = self::getCustomerIDByPostID(); //echo $customer_id;

			if ($customer_id==0)
				return $posts;
		}

		global $post;

		$defaults = array(
			'posts_per_page' => 4,
			'post_type' => self::$supported_post_types,
			'post__not_in' => array( $post->ID ) //exclure le post courant 
		);

		$settings = array(); //todo

		$defaults = array_merge( $defaults, $settings );

		// Parse incomming $args into an array and merge it with $defaults
		$args = wp_parse_args( $args, $defaults );

		// Declare each item in $args as its own variable i.e. $type, $before.
		extract( $args, EXTR_SKIP );

		//Est-ce vraiment une bonne chose de filtrer ici par metropole ?
		// $metropole = Kidzou_Geo::get_request_metropole();

		$rd_args = array(
			'posts_per_page' => $posts_per_page,
			'post_type' => self::$supported_post_types,
			'meta_key' => self::$meta_customer,
			'meta_value' => $customer_id,
			'post__not_in'=> $post__not_in,
	
		);
		 
		$rd_query = new WP_Query( $rd_args );

		$list = 	$rd_query->get_posts(); 

		return $list;

	}

	/**
	 * retourne les clients d'un user
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomersIDByUserID($user_id = 0)
	{

		if ($user_id == 0)
			$user_id = get_current_user_id();

		$customer_ids = get_user_meta($user_id, self::$meta_customer, false); 

		// Kidzou_Utils::log( 'getCustomersIDByUserID -> ' . count($customer_ids) );

		//supprimer les révisions et autrs
		return array_filter($customer_ids, function($item) {
			$instance = Kidzou_Customer::get_instance();
			$this_type = $instance::$post_type;

			return get_post_type($item)==$this_type;
		});
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function isAnalyticsAuthorizedForCustomer($customer_id = 0)
	{
		return get_post_meta($customer_id, self::$meta_customer_analytics , TRUE);
	}



} //fin de classe

?>