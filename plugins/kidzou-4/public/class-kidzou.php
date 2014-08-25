<?php
/**
 * Kidzou
 *
 * @package   Kidzou
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
 * @package Kidzou
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '2014.08.24';

	/**
	 * @TODO - Rename "plugin-name" to the name of your plugin
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'kidzou';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		// add_action( '@TODO', array( $this, 'action_method_name' ) );
		add_filter( 'comments_array', array( $this, 'reverse_comments' ) );
		add_filter( 'excerpt_length', array( $this, 'excerpt_length' ) );
		add_filter('the_excerpt_rss', array( $this, 'rss_post_thumbnail' ) );
		add_filter('the_content_feed', array( $this, 'rss_post_thumbnail' ) );

		add_action("edited_ville",    array( $this, 'edited_ville' ) , 10, 2);


	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
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
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();

					restore_current_blog();
				}

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

					restore_current_blog();

				}

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
		self::refresh_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * rafraichir les rewrite_rules à la modification des villes 
	 *
	 * @since    1.0.0
	 */
	public function edited_ville (  $term_id, $tt_id  ) {

		self::refresh_rewrite_rules();
	}	

	public static function refresh_rewrite_rules() {

		global $wp_rewrite;

	    add_rewrite_tag('%kz_metropole%',$regexp, 'kz_metropole=');

		$wp_rewrite->set_permalink_structure('/%postname%/');
		$wp_rewrite->set_category_base('%kz_metropole%/rubrique/');
		$wp_rewrite->set_tag_base('%kz_metropole%/tag/');

		$regexp = '(';
	    $villes = Kidzou_Geo::get_metropoles();
	    
	    if (!empty($villes)) {
	        $i=0;
	        $count = count($villes);
	        foreach ($villes as $item) {
	            $regexp .= $item->slug;
	            $i++;
	            if ($regexp!=='' && $count>$i) {
	                $regexp .= '|';
	            }
	        }
	    }
	    
	    $regexp .= ')';

	    $wp_rewrite->add_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home

	    // // Add Offre archive (and pagination)
	    // //see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
	    add_rewrite_rule($regexp.'/offres/page/?([0-9]{1,})/?','index.php?post_type=offres&paged=$matches[2]&kz_metropole=$matches[1]','top');
	    add_rewrite_rule($regexp.'/offres/?','index.php?post_type=offres&kz_metropole=$matches[1]','top');


		//ne pas oublier 
		$wp_rewrite->flush_rules();
	}

	public function register_taxonomies() {

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x( 'Ville', 'taxonomy general name' ),
			'singular_name' => _x( 'Ville', 'taxonomy singular name' ),
			'search_items' =>  __( 'Chercher par ville' ),
			'all_items' => __( 'Toutes les villes' ),
			'parent_item' => __( 'Ville Parent' ),
			'parent_item_colon' => __( 'Ville Parent:' ),
			'edit_item' => __( 'Modifier la Ville' ),
			'update_item' => __( 'Mettre à jour la Ville' ),
			'add_new_item' => __( 'Ajouter une ville' ),
			'new_item_name' => __( 'Nom de la nouvelle ville' ),
			'menu_name' => __( 'Ville' ),
			);

		//intégration avec event dans le register_post_type event
		register_taxonomy('ville',array('post','page', 'user'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'ville' ),
			));

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x( 'Divers', 'taxonomy general name' ),
			'singular_name' => _x( 'Divers', 'taxonomy singular name' ),
			'search_items' =>  __( 'Chercher' ),
			'all_items' => __( 'Tous les divers' ),
			'parent_item' => __( 'Cat&eacute; Divers Parent' ),
			'parent_item_colon' => __( 'Divers Parent:' ),
			'edit_item' => __( 'Modifier une cat&eacute;gorie divers' ),
			'update_item' => __( 'Mettre a  jour une cat&eacute;gorie divers' ),
			'add_new_item' => __( 'Ajouter une cat&eacute;gorie divers' ),
			'new_item_name' => __( 'Nouvelle cat&eacute;gorie divers' ),
			'menu_name' => __( 'Divers' ),
			);

		register_taxonomy('divers',array('post','page'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'divers' ),
			// 'capabilities' => array(
			// 	'manage_terms' 	=> 'manage_categories',
			// 	'edit_terms' 	=> 'manage_categories',
			// 	'delete_terms' 	=> 'manage_categories',
			// 	'assign_terms' 	=>	'edit_posts' 
			// )
			));

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x( 'Age', 'taxonomy general name' ),
			'singular_name' => _x( 'Age', 'taxonomy singular name' ),
			'search_items' =>  __( 'Chercher par age' ),
			'all_items' => __( 'Tous les ages' ),
			'parent_item' => __( 'Age Parent' ),
			'parent_item_colon' => __( 'Age Parent:' ),
			'edit_item' => __( 'Modifier l&apos;age' ),
			'update_item' => __( 'Mettre a  jour l&apos;age' ),
			'add_new_item' => __( 'Ajouter un age' ),
			'new_item_name' => __( 'Nom du nouvel age' ),
			'menu_name' => __( 'Tranches d&apos;age' ),
			);

		//le cap "edit_events" peut assigner des ages aux events
		register_taxonomy('age',array('post','page'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'age' ),
			// 'capabilities' => array(
			// 	'manage_terms' 	=> 'manage_categories',
			// 	'edit_terms' 	=> 'manage_categories',
			// 	'delete_terms' 	=> 'manage_categories',
			// 	'assign_terms' 	=>	'edit_posts' 
			// )
			));
	}

	public function register_post_types() {

		//definir les custom post types
		//ne pas faire a chaque appel de page 

		$labels = array(
			'name'               => 'Offres',
			'singular_name'      => 'Offre',
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Ajouter une offre',
			'edit_item'          => 'Modifier l\'offre',
			'new_item'           => 'Nouvelle offre',
			'all_items'          => 'Toutes les offres',
			'view_item'          => 'Voir l\'offre',
			'search_items'       => 'Chercher des offres',
			'not_found'          => 'Aucune offre trouvée',
			'not_found_in_trash' => 'Aucune offre trouvée dans la corbeille',
			'menu_name'          => 'Offres',
			);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position' 	 => 5, //sous les articles dans le menu
			'menu_icon' 		 => 'dashicons-smiley',
			'query_var'          => true,
			'has_archive'        => true,
			'rewrite' 			=> array('slug' => 'offres'),
			'hierarchical'       => false, //pas de hierarchie d'offres
			'supports' 			=> array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions', 'post-formats'),
			'taxonomies' 		=> array('age', 'ville', 'divers', 'category'), //reuse the taxo declared in kidzou plugin
			);

		register_post_type( 'offres', $args );

		$labels = array(
			'name'               => 'Jeux Concours',
			'singular_name'      => 'Jeu Concours',
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Ajouter un concours',
			'edit_item'          => 'Modifier le concours',
			'new_item'           => 'Nouveau concours',
			'all_items'          => 'Tous les concours',
			'view_item'          => 'Voir le concours',
			'search_items'       => 'Chercher des concours',
			'not_found'          => 'Aucun concours trouvé',
			'not_found_in_trash' => 'Aucun concours trouvé dans la corbeille',
			'menu_name'          => 'Jeux concours',
			);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position' 	 => 5, //sous les articles dans le menu
			'menu_icon'			=> 'dashicons-awards',
			'query_var'          => true,
			'has_archive'        => true,
			'rewrite' 			=> array('slug' => 'concours'),
			'hierarchical'       => false, //pas de hierarchie d'offres
			'supports' 			=> array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions', 'post-formats'),
			'taxonomies' 		=> array('age', 'ville', 'divers', 'category'), //reuse the taxo declared in kidzou plugin
			);

		register_post_type( 'concours', $args );

		$labels = array(
			'name'               => 'Evénements',
			'singular_name'      => 'Evénement',
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Ajouter un événement',
			'edit_item'          => 'Modifier l\'événement',
			'new_item'           => 'Nouvel événement',
			'all_items'          => 'Tous les événements',
			'view_item'          => 'Voir l\'événement',
			'search_items'       => 'Chercher des événements',
			'not_found'          => 'Aucun événement trouvé',
			'not_found_in_trash' => 'Aucun événement trouvé dans la corbeille',
			'menu_name'          => 'Evénements',
			);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position' 	 => 5, //sous les articles dans le menu
			'menu_icon' 		 => 'dashicons-calendar', 
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'event' ),
			'capability_type'    => 'event',
			'capabilities' 		 => array(
								        'edit_post'			 => 'edit_event',
								        'edit_posts' 		 => 'edit_events',
								        'edit_others_posts'  => 'edit_others_events',
								        'publish_posts' 	 => 'publish_events',
								        'read_post' 		 => 'read_event',
								        'read_private_posts' => 'read_private_events',
								        'delete_post' 		 => 'delete_event',
								        'delete_private_posts' 		=> 'delete_private_events',
								        'delete_published_posts' 	=> 'delete_published_events',
								        'delete_others_posts' 		=> 'delete_others_events',
								        'edit_private_posts' 		=> 'edit_private_events',
								        'edit_published_posts' 		=> 'edit_published_events',
								        // 'assign_terms' => 'assign_terms'
								    ),
			'map_meta_cap' 		 => true,
			'has_archive'        => true,
			'hierarchical'       => false, //pas de hierarchie d'events
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions'),
			'taxonomies' 		=> array('age', 'ville', 'divers','category'), //reuse the taxo declared in kidzou plugin
			'register_meta_box_cb' => 'add_events_metaboxes'
		);

		register_post_type( 'event', $args );
		
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function reverse_comments($comments) {
		return array_reverse($comments);
	}

	public function kidzou_excerpt_length( $length ) {
		return 200;
	}

	public function rss_post_thumbnail($content) {
		global $post;
		if(has_post_thumbnail($post->ID)) {
		$content = '<p>' . get_the_post_thumbnail($post->ID) .
		'</p>' . get_the_content();
		}
		return $content;
	}

}
