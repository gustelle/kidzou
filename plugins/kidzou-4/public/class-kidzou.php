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
		add_action( 'init', array( $this, 'create_rewrite_rules' ) );

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

		add_filter( 'post_link', array( 'Kidzou_Geo', 'rewrite_post_link' ) , 10, 2 );
		add_filter( 'page_link', array( 'Kidzou_Geo', 'rewrite_page_link' ) , 10, 2 );
		add_filter( 'term_link', array( 'Kidzou_Geo', 'rewrite_term_link' ), 10, 3 );

		/*
		 * @TODO ce hook ne fonctionne passs
		 */
		add_action("edit_ville",    array( $this, 'edit_tax_ville' ) , 10, 2);
		add_action( 'pre_get_posts', array( $this, 'geo_filter_query' ), 100 );


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
		
		global $wp_rewrite;
		
		$wp_rewrite->set_permalink_structure('/%postname%/');
		$wp_rewrite->set_category_base('%kz_metropole%/rubrique/');
		$wp_rewrite->set_tag_base('%kz_metropole%/tag/');

		self::create_rewrite_rules();
		self::create_roles();

		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
		remove_role( 'pro' );
		flush_rewrite_rules();
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

	/**
	 * pour rafraichir les rewrite_rules à la modification des villes 
	 *
	 * @since    1.0.0
	 */
	public function edit_tax_ville (  $term_id, $tt_id  ) {

		flush_rewrite_rules();
	}	

	public static function create_rewrite_rules() {
		
	    $villes = Kidzou_Geo::get_metropoles();

	    if (!empty($villes)) {

	    	$regexp = '(';
	        $i=0;
	        $count = count($villes);
	        foreach ($villes as $item) {
	            $regexp .= $item->slug;
	            $i++;
	            if ($regexp!=='' && $count>$i) {
	                $regexp .= '|';
	            }
	        }
	        $regexp .= ')';

			add_rewrite_tag('%kz_metropole%',$regexp, 'kz_metropole=');

			//see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
		    add_rewrite_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home
		    add_rewrite_rule($regexp.'/offres/page/?([0-9]{1,})/?','index.php?post_type=offres&paged=$matches[2]&kz_metropole=$matches[1]','top');
		    add_rewrite_rule($regexp.'/offres/?','index.php?post_type=offres&kz_metropole=$matches[1]','top');

	    }
	    
	}

	//les caps d'edition seront ajoutes ad-hoc lors des requetes dans admin
	public static function create_roles() {

		add_role(
	        'pro',
	        __( 'Professionnel' ), 
	        array(
	            'read'          => true,
	            'manage_categories' => false,
	            'upload_files'  => true,
	        )
	    );
	    
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

	/**
	 * Les Query en Base sont filtrées en tenant compte de la métropole courante
	 * Celle-ci est soit la metropole passée dans la requete (en provenance du cookie utilisateur), soit la metropole par défaut
	 * les contenus à portée "nationale" sont également remontés
	 *
	 * @since    1.0.0
	 */
	public function geo_filter_query( $query ) {

		//les pages n'ont pas de taxo "ville", il faut les exclure du filtre
	    if( !is_admin() && !is_search() ) {

	        $the_metropole = array(Kidzou_Geo::get_request_metropole());
	        $national = (array)Kidzou_Geo::get_national_metropoles(); 
	        $merge = array_merge( $the_metropole, $national);

	        // print_r($merge);

	        if (!empty($merge))
	        {
	            //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
	            set_query_var('tax_query', array(
	                array(
	                      'taxonomy' => 'ville',
	                      'field' => 'slug',
	                      'terms' => $merge
	                    )
	                )
	            );

	        }

	        // print_r($query);
	        
	        return $query;
	    }
	}

}
