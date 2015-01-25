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
	const VERSION = '0115-proximite-refresh';

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public static $version_description = "Correctifs sur la fonction 'a proximite'";

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
	public $plugin_slug = 'kidzou';

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

		//handle dependencies
		add_action( 'tgmpa_register', array( $this, 'register_required_plugins' ) );

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );

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

		add_filter('the_excerpt_rss', array( $this, 'rss_post_thumbnail' ) );
		add_filter('the_content_feed', array( $this, 'rss_post_thumbnail' ) );

		add_filter('json_api_controllers', array( $this, 'add_Kidzou_controller' ));
		add_filter('json_api_vote_controller_path', 	array( $this, 'set_vote_controller_path' )  );
		add_filter('json_api_auth_controller_path', 	array( $this, 'set_auth_controller_path' )  );
		add_filter('json_api_users_controller_path',    array( $this, 'set_users_controller_path' ) );
		add_filter('json_api_clients_controller_path',  array( $this, 'set_clients_controller_path') );
		add_filter('json_api_search_controller_path',  array( $this, 'set_search_controller_path') );
		add_filter('json_api_content_controller_path',  array( $this, 'set_content_controller_path') );

		add_action('wp_footer', array( $this, 'insert_analytics_tag'));

		add_action('wp_head', array( $this, 'insert_pub_header'));

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

		do_action('kidzou_loaded');

		return self::$instance;
	}


	/**
	 * Gestion des dépendances : Liste des plugins que Kidzou requiert pour fonctionner correctement
	 * les plugins sont téléchargés et installés automatiquement
	 * 
	 */
	public function register_required_plugins() {

		/**
	     * Array of plugin arrays. Required keys are name and slug.
	     * If the source is NOT from the .org repo, then source is also required.
	     */
	    $plugins = array(

	        // This is an example of how to include a plugin pre-packaged with a theme.
	        array(
	            'name'               => 'JSON API', // The plugin name.
	            'slug'               => 'json-api', // The plugin slug (typically the folder name).
	            'required'           => true, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	            'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        //ajouter NEXTEND, REDUX

	        array(
	            'name'               => 'Redux Framework', // The plugin name.
	            'slug'               => 'redux-framework', // The plugin slug (typically the folder name).
	            'required'           => true, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	            'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        array(
	            'name'               => 'Google Analytics Dashboard for WP', // The plugin name.
	            'slug'               => 'google-analytics-dashboard-for-wp', // The plugin slug (typically the folder name).
	            'required'           => true, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	            'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        array(
	            'name'               => 'Geo Data Store', // The plugin name.
	            'slug'               => 'geo-data-store', // The plugin slug (typically the folder name).
	            'required'           => true, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	            'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        array(
	            'name'               => 'Contextual Related Posts', // The plugin name.
	            'slug'               => 'contextual-related-posts', // The plugin slug (typically the folder name).
	            'required'           => false, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	            'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        array(
	            'name'               => 'WP Admin UI Customize', // The plugin name.
	            'slug'               => 'wp-admin-ui-customize', // The plugin slug (typically the folder name).
	            'required'           => false, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	        	'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        array(
	            'name'               => 'Capability Manager Enhanced', // The plugin name.
	            'slug'               => 'capability-manager-enhanced', // The plugin slug (typically the folder name).
	            'required'           => false, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	        	'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        ),

	        array(
	            'name'               => 'Erident Custom Login and Dashboard', // The plugin name.
	            'slug'               => 'erident-custom-login-and-dashboard', // The plugin slug (typically the folder name).
	            'required'           => false, // If false, the plugin is only 'recommended' instead of required.
	            'version'            => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher.
	            'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
	            'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
	        	'external_url'       => '', // If set, overrides default API URL and points to an external URL.
	        )

	    );

	    /**
	     * Array of configuration settings. Amend each line as needed.
	     * If you want the default strings to be available under your own theme domain,
	     * leave the strings uncommented.
	     * Some of the strings are added into a sprintf, so see the comments at the
	     * end of each line for what each argument will be.
	     */
	    $config = array(
	        'default_path' => '',                      // Default absolute path to pre-packaged plugins.
	        'menu'         => 'tgmpa-install-plugins', // Menu slug.
	        'has_notices'  => true,                    // Show admin notices or not.
	        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
	        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
	        'is_automatic' => true,                   // Automatically activate plugins after installation or not.
	        'message'      => '',                      // Message to output right before the plugins table.
	        'strings'      => array(
	            'page_title'                      => __( 'Installation des plugins requis par Kidzou', 'tgmpa' ),
	            'menu_title'                      => __( 'Plugins Kidzou', 'tgmpa' ),
	            'installing'                      => __( 'Installation de: %s', 'tgmpa' ), // %s = plugin name.
	            'oops'                            => __( 'Something went wrong with the plugin API.', 'tgmpa' ),
	            'notice_can_install_required'     => _n_noop( 'Kidzou requiert le plugin: %1$s.', 'Kidzou requiert les plugins suivants: %1$s.' ), // %1$s = plugin name(s).
	            'notice_can_install_recommended'  => _n_noop( 'kidzou recommande le plugin suivant: %1$s.', 'Kidzou recommande les plugins suivants: %1$s.' ), // %1$s = plugin name(s).
	            'notice_cannot_install'           => _n_noop( 'Vous n&apos;avez pas les drois suffisants pour installer le plugin %s.', 'Vous n&apos;avez pas les droits suffisants pour installer les plugins %s.' ), // %1$s = plugin name(s).
	            'notice_can_activate_required'    => _n_noop( 'Le plugin suivant est requis, mais est inactif: %1$s.', 'Les plugins suivants sont requis mais inactifs: %1$s.' ), // %1$s = plugin name(s).
	            'notice_can_activate_recommended' => _n_noop( 'Le plugin suivant est recommand&eacute; mais inactif: %1$s.', 'Les plugins suivants sont recommand&eacute;s mais inactifs: %1$s.' ), // %1$s = plugin name(s).
	            'notice_cannot_activate'          => _n_noop( 'Vous n&apos; avez pas les drois suffisants pour activer le plugin %s.', 'Vous n&apos;avez pas les drois suffisants pour activer les plugins suivants %s' ), // %1$s = plugin name(s).
	            'notice_ask_to_update'            => _n_noop( 'Le plugin suivant a besoin d&apos;&ecirc;tre mis a jour pour fonctionner de facon optimale: %1$s.', 'Les plugins suivants ont besoin d&apos;&ecirc;tre mis a jour pour fonctionner de facon optimale: %1$s.' ), // %1$s = plugin name(s).
	            'notice_cannot_update'            => _n_noop( 'Vous n&apos;avez pas les droits suffisants pour mettre a jour le plugin %s', 'Vous n&apos;avez pas les droits suffisants pour mettre a jour les plugins %s' ), // %1$s = plugin name(s).
	            'install_link'                    => _n_noop( 'Installer le plugin', 'Installer les plugins' ),
	            'activate_link'                   => _n_noop( 'Activer le plugin', 'Activer les plugins' ),
	            'return'                          => __( 'Revenir a l&apos;installeur des plugins requis', 'tgmpa' ),
	            'plugin_activated'                => __( 'Activation r&eacute;ussie.', 'tgmpa' ),
	            'complete'                        => __( 'Tous les plugins ont &eacute;t&eacute; install&eacute;s et activ&eacute;s. %s', 'tgmpa' ), // %s = dashboard link.
	            'nag_type'                        => 'updated' // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
	        )
	    );

	    tgmpa( $plugins, $config );

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
	 * @todo mettre dans Kidzou_Geo
	 * @since    1.0.0
	 */
	private static function single_activate() {
		
		global $wp_rewrite;
		
		$wp_rewrite->set_permalink_structure('/%postname%/');
		
		do_action('kidzou_activate');

		self::create_roles();
		self::add_caps();

		flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

		do_action('kidzou_deactivate');

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

		register_taxonomy('ville',array('post','page', 'user'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => 'ville',
			// 'rewrite' => array( 'slug' => 'ville' ),
			));

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x( 'Famille', 'taxonomy general name' ),
			'singular_name' => _x( 'Famille', 'taxonomy singular name' ),
			'search_items' =>  __( 'Chercher' ),
			'all_items' => __( 'Toutes les categories Famille' ),
			'parent_item' => __( 'Cat&eacute;gorie Famille Parent' ),
			'parent_item_colon' => __( 'Famille Parent:' ),
			'edit_item' => __( 'Modifier une cat&eacute;gorie Famille' ),
			'update_item' => __( 'Mettre a  jour une cat&eacute;gorie Famille' ),
			'add_new_item' => __( 'Ajouter une cat&eacute;gorie Famille' ),
			'new_item_name' => __( 'Nouvelle cat&eacute;gorie Famille' ),
			'menu_name' => __( 'Rubrique Famille' ),
			);

		register_taxonomy('divers',array('post','page'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			// 'query_var' => 'famille',
			'rewrite' => array( 'slug' => 'famille' ),
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

		register_taxonomy('age',array('post','page'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => 'age',
			// 'rewrite' => array( 'slug' => 'age' ),
			));
	}


	/**
	 * 
	 *
	 */
	public static function create_roles() {

		do_action('kidzou_create_roles');
	    
	}

	public static function add_caps() {
				
		do_action('kidzou_add_caps');

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
		wp_enqueue_style( 'fontello', "//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css", null, '3.0.2' );
	
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_slug . '-storage', plugins_url( 'kidzou-4/assets/js/kidzou-storage.js' ), array( 'jquery', 'ko', 'ko-mapping' ), self::VERSION, true);
		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);

		if (!Kidzou_Utils::is_really_admin())
		{
			global $kidzou_options;

			wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery', 'ko', 'kidzou-storage'), self::VERSION, true );
			
			wp_localize_script($this->plugin_slug . '-plugin-script', 'kidzou_commons_jsvars', array(
					'msg_wait'			 			 => 'Merci de patienter...',
					'msg_loading'				 	 => 'Chargement en cours...',
					'msg_auth_onprogress'			 => "Connexion en cours, merci de votre patience",
					'msg_auth_success'				 => "Connexion r&eacute;ussie, la page va se recharger...",
					'msg_auth_failed'				 => "Echec de connexion",
					'votable_countText' 			 => "Cool",
					'votable_countText_down'		 => "Pas cool",
					'cfg_lost_password_url'			 =>  site_url().'/wp-login.php?action=lostpassword',
					'cfg_signup_url'				 =>  site_url().'/wp-signup.php',
					'cfg_site_url'		 			 =>  site_url().'/',
					'cfg_debug_mode' 	 			 =>  (bool)Kidzou_Utils::get_option('debug_mode'),
					'api_get_nonce'				 	 =>  site_url().'/api/get_nonce/',
					'api_get_event'					 =>  site_url().'/api/events/get_event/',
					'api_get_votes_status'			 =>  site_url().'/api/vote/get_votes_status/', 
					'api_get_votes_user'			 =>  site_url().'/api/vote/get_votes_user/',
					'api_vote_up'			 		 =>  site_url().'/api/vote/up/',
					'api_vote_down'			 		 =>  site_url().'/api/vote/down/',
					'api_voted_by_user'				 => site_url().'/api/vote/voted_by_user/',
					'api_generate_auth_cookie'		 => site_url().'/api/auth/generate_auth_cookie/',
					'is_admin' 						 => Kidzou_Utils::current_user_is('administrator'),
					'current_user_id'				 => (is_user_logged_in() ? get_current_user_id() : 0),
					// 'analytics_ua'					 => Kidzou_Utils::get_option('analytics_ua', 'UA-23017523-1'),
					// 'analytics_activate'			 => (bool)Kidzou_Utils::get_option('analytics_activate'),
				)
			);
		}

		
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

	public function rss_post_thumbnail($content) {
		global $post;
		if(has_post_thumbnail($post->ID)) {
		$content = '<p>' . get_the_post_thumbnail($post->ID) .
		'</p>' . get_the_content();
		}
		return $content;
	}
	
	public static function post_types() {

		return array('post'); //'event'
	}

	/*JSON API*/
	public function add_Kidzou_controller($controllers) {

	  $controllers[] = 'Vote';
	  $controllers[] = 'Auth';
	  $controllers[] = 'Users';
	  $controllers[] = 'Clients';
	  $controllers[] = 'Search';
	  $controllers[] = 'Content';

	  return $controllers;
	}


	public function set_vote_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/../includes/api/vote.php";
	}
	public function set_auth_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/../includes/api/auth.php";
	}
	public function set_users_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/../includes/api/users.php";
	}
	public function set_clients_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/../includes/api/clients.php";
	}
	public function set_search_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/../includes/api/search.php";
	}
	public function set_content_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/../includes/api/content.php";
	}

	/**
	 * injecte  dans le footer le tag Google Analytics
	 *
	 * @return String
	 * @since proximite
	 * @author 
	 **/
	public static function insert_analytics_tag()
	{
		$activate = (bool)Kidzou_Utils::get_option('analytics_activate',false);

		if ($activate)
		{
			// Kidzou_Utils::log('doing analytics_tag');
			echo sprintf (
					"<script>
						(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
						(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
						m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
						})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

						ga('create',  '%s', 'auto');
						ga('send', 'pageview');

						//tracker allofamille
						ga('create', 'UA-58574680-1', 'auto', {'name': 'af'});
						ga('af.send', 'pageview');

					</script>",
					Kidzou_Utils::get_option('analytics_ua','')
				);
		}

	}

	/**
	 * injecte  dans le footer le tag Google Analytics
	 *
	 * @return String
	 * @since proximite
	 * @author 
	 **/
	public static function insert_pub_header()
	{
		echo Kidzou_Utils::get_option('pub_header','');
	}

}
