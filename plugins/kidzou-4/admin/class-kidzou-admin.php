<?php
/**
 * Kidzou
 *
 * @package   Kidzou_Admin
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 * @TODO:
		 *
		 * - Rename "Plugin_Name" to the name of your initial plugin class
		 *
		 */
		$plugin = Kidzou::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		//ajouter les meta 
		add_action( 'init', array( $this, 'add_taxonomy_meta' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );

		add_action('admin_init', array($this, 'add_caps'));


	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Kidzou::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Kidzou::VERSION );
		}

	}

	/**
	 * la taxonomy "ville" a besoin de meta pour identifier les métropoles et les villes à portée nationale.
	 * cela est utilisé ensuite pour filtrer les queries de contenu par métropole, la métropole étant récupérer d'un cookie en provenance de la requete
	 *
	 * @since    1.0.0
	 */
	public function add_taxonomy_meta() {

		$prefix = 'kz_';

		$config = array(
		'id' => 'meta_box',          // meta box id, unique per meta box
		'title' => 'Metadonnees de Ville',          // meta box title
		'pages' => array('ville'),        // taxonomy name, accept categories, post_tag and custom taxonomies
		'context' => 'normal',            // where the meta box appear: normal (default), advanced, side; optional
		'fields' => array(),            // list of meta fields (can be added by field arrays)
		'local_images' => false,          // Use local or hosted images (meta box images for add/remove)
		'use_with_theme' => false          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
		);

		$my_meta =  new Tax_Meta_Class($config);

		//checkbox field
		$my_meta->addCheckbox($prefix.'default_ville',array('name'=> __('Ville par défaut ','tax-meta')));
		$my_meta->addCheckbox($prefix.'national_ville',array('name'=> __('Visibilite nationale pour les articles ranges dans cette categorie. Les villes a portee nationale doivent etre a la racine','tax-meta')));

		$my_meta->Finish();


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
			// 'register_meta_box_cb' => 'add_metabox'
		);

		register_post_type( 'event', $args );

		//rafraichir les rewrite rules
		flush_rewrite_rules();
		
	}

	public function add_metaboxes() {

		// if (is_admin()) {
			// echo 'yeop';
			// add_events_metaboxes();
			add_meta_box('kz_event_metabox', 'Evenement', 'kz_event_metabox', 'event', 'normal', 'high');
			add_meta_box('kz_place_metabox', 'Lieu', 'kz_place_metabox', 'event', 'normal', 'high');
		// }

	}

	public function add_caps() {

		$administrator     = get_role('administrator');
		$editor     	= get_role('editor');
		$pro 	= get_role('pro');

		foreach ( array('delete_private','edit','edit_private','read_private') as $cap ) {
			$administrator->add_cap( "{$cap}_events" );
			$pro->add_cap("${cap}_events");
			$editor->add_cap("${cap}_events");
		}

		//et en plus pour eux
		foreach ( array('publish','delete','delete_others','edit_others', 'edit_published', 'delete_published') as $cap ) {
			$administrator->add_cap( "{$cap}_events" );
			$editor->add_cap("${cap}_events");
		}


	}


	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'Page Title' to the title of your plugin admin page
		 * - Change 'Menu Text' to the text for menu item for the plugin settings page
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Options de Kidzou', $this->plugin_slug ),
			__( 'Kidzou', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

}
