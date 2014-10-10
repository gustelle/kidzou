<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Customer', 'get_instance' ) );

/* seulement à l'activation du plugin */
add_action( 'kidzou_activate', array('Kidzou_Customer', 'create_client_tables'));
add_action( 'kidzou_deactivate', array('Kidzou_Customer', 'drop_client_tables'));

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
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '2014.08.24';

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

	const CLIENTS_TABLE = "clients";
	const CLIENTS_USERS_TABLE = "clients_users";

	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		add_action('init', array($this, 'register_customer_type'));

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
			// 'query_var'          => true,
			// 'has_archive'        => true,
			// 'rewrite' 			=> array('slug' => 'offres'),
			// 'hierarchical'       => false, //pas de hierarchie de clients
			'supports' 			=> array( 'title', 'author', 'revisions'),
			// 'taxonomies' 		=> array('age', 'ville', 'divers', 'category'), //reuse the taxo declared in kidzou plugin
			);

		register_post_type( 'customer', $args );

	}

	/**
	 * Creates the db schema
	 *
	 * @global type $wpdb
	 * @global string $kz_db_version
	 *
	 * @return void
	 */
	public static function create_client_tables() {

		global $wpdb;
		// global $kz_clients_db_version;
		$table_clients = $wpdb->prefix . self::CLIENTS_TABLE;
		$table_clients_users = $wpdb->prefix . self::CLIENTS_USERS_TABLE;

		$sql = "CREATE TABLE $table_clients (
	        id mediumint(9) NOT NULL AUTO_INCREMENT,
	        name varchar(255) NOT NULL,
	        UNIQUE KEY id (id)
	       )CHARSET=utf8;";

		$sql .= "CREATE TABLE $table_clients_users (
	        customer_id mediumint(9) NOT NULL DEFAULT 0,
	        user_id bigint(20) NOT NULL DEFAULT 0
	       )CHARSET=utf8;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// $wpdb->show_errors();
		$ret = dbDelta( $sql );
		// $wpdb->print_error();
		// update_site_option( 'kz_clients_db_version' , $kz_clients_db_version );
	}

	public static function drop_client_tables() {

		global $wpdb;
		// global $kz_clients_db_version;
		$table_clients = $wpdb->prefix . self::CLIENTS_TABLE;
		$table_clients_users = $wpdb->prefix . self::CLIENTS_USERS_TABLE;

		$sql = "DROP TABLE $table_clients, $table_clients_users;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// $wpdb->show_errors();
		$ret = dbDelta( $sql );
		// $wpdb->print_error();
		// update_site_option( 'kz_clients_db_version' , $kz_clients_db_version );
	}

    /**
	 * le customer d'un post, ou 0 si le post n'a pas de customer
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

		$customer = get_post_meta($post_id, 'kz_event_customer', TRUE);

		return intval($customer)>0 ? intval($customer) : 0;
	}

	/**
	 * le nom du client par son ID
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerNameByCustomerID($customer_id = 0)
	{

		if ($customer_id==0)
			return '';
			
		if (intval($customer_id)>0) {

			global $wpdb;
			$table_clients 		 = $wpdb->prefix . self::CLIENTS_TABLE;
			$customer 	= $wpdb->get_results("SELECT c.id, c.name FROM $table_clients AS c WHERE c.id=$customer_id", ARRAY_A);

			return (isset($customer[0]) && isset($customer[0]['name']) ? $customer[0]["name"] : '');
		}
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
			// global $post;

			$customer_id = self::getCustomerIDByPostID(); //echo $customer_id;

			if ($customer_id==0)
				return $posts;
		}

		global $post;

		$defaults = array(
			'posts_per_page' => 4,
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
			'meta_key' => 'kz_event_customer',
			'meta_value' => $customer_id,
			'post__not_in'=> $post__not_in,
			// 'tax_query' => array(
			//         array(
			//               'taxonomy' => 'ville',
			//               'field' => 'slug',
			//               'terms' => $metropole,
			//               )
			//     )
		);
		 
		$rd_query = new WP_Query( $rd_args );

		$list = 	$rd_query->get_posts(); 

		//Reutiliser le tri disponible dans Kidzou_Events
		uasort($list, array( Kidzou_Events::get_instance(), "sort_by_featured" ) );

		return $list;

	}

	/**
	 * retourne le client d'un auteur de contenu
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerIDByAuthorID($user_id = 0)
	{
		global $wpdb;

		$table_clients_users = $wpdb->prefix . self::CLIENTS_USERS_TABLE;
		$table_clients 		 = $wpdb->prefix . self::CLIENTS_TABLE;

		if ($user_id == 0)
			$user_id = get_current_user_id();

		$customer = $wpdb->get_results("SELECT c.id, c.name FROM $table_clients_users AS u, $table_clients AS c WHERE u.user_id=$user_id AND u.customer_id=c.id", ARRAY_A);

		return intval($customer[0]["id"])>0 ? intval($customer[0]["id"]) : 0;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerRelatedPosts()
	{
		global $post;


	}


} //fin de classe

?>