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
		add_filter('json_api_vote_controller_path', 			array( $this, 'set_vote_controller_path' )  );
		add_filter('json_api_auth_controller_path', 			array( $this, 'set_auth_controller_path' ) );
		add_filter('json_api_users_controller_path',            array( $this, 'set_users_controller_path' ) );

		//insertion des template de vote
		add_action('wp_footer', array($this, 'votable_template_mini'), 100);
		add_action('wp_footer', array($this, 'votable_template_mega'), 100);


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
	 * @todo mettre dans Kidzou_Geo
	 * @since    1.0.0
	 */
	private static function single_activate() {
		
		global $wp_rewrite;
		
		$wp_rewrite->set_permalink_structure('/%postname%/');
		$wp_rewrite->set_category_base('%kz_metropole%/rubrique/');
		$wp_rewrite->set_tag_base('%kz_metropole%/tag/');

		Kidzou_Geo::create_rewrite_rules();

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
		
		// @TODO: Define deactivation functionality here

		// global $wp_roles;

		// $post_type_details = get_post_type_object( 'event' );
		// $post_type_cap 	= $post_type_details->capability_type;
		// $post_type_caps	= $post_type_details->cap;

		// $administrator     	= $wp_roles->get_role('administrator');
		// $editor     		= $wp_roles->get_role('editor');
		// $pro 				= $wp_roles->get_role('pro');

		// $roles_plus = array();
		// $roles_plus["administrator"] = $administrator;
		// $roles_plus["editor"] = $editor;

		// foreach ( $roles_plus as $key => $role ) {

		// 	// Shared capability required to see post's menu & publish posts
		// 	$role->remove_cap( $post_type_caps->edit_posts );
				
		// 	// Shared capability required to delete posts
		// 	$role->remove_cap( $post_type_caps->delete_posts );
				
		// 	// Allow publish
		// 	$role->remove_cap( $post_type_caps->publish_posts );

		// 	// Allow editing own posts
		// 	$role->remove_cap( $post_type_caps->edit_published_posts );
		// 	$role->remove_cap( $post_type_caps->edit_private_posts );
		// 	$role->remove_cap( $post_type_caps->delete_published_posts );
		// 	$role->remove_cap( $post_type_caps->delete_private_posts );
				
		// 	// Allow editing other's posts
		// 	$role->remove_cap( $post_type_caps->edit_others_posts );
		// 	$role->remove_cap( $post_type_caps->delete_others_posts );
				
		// 	// Allow reading private
		// 	$role->remove_cap( $post_type_caps->read_private_posts);

		// }

		// $roles_mini = array();
		// $roles_mini["pro"] = $pro;

		// foreach ( $roles_mini as $key => $role ) {

		// 	// Shared capability required to see post's menu & publish posts
		// 	$role->remove_cap( $post_type_caps->edit_posts );

		// 	// Allow editing own posts
		// 	$role->remove_cap( $post_type_caps->edit_published_posts );
		// 	$role->remove_cap( $post_type_caps->edit_private_posts );
		// 	// $role->add_cap( $post_type_caps->delete_published_posts );
		// 	$role->remove_cap( $post_type_caps->delete_private_posts );
				
		// 	// Allow reading private
		// 	$role->remove_cap( $post_type_caps->read_private_posts);

		// }

		// remove_role( 'pro' );

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
			'query_var' => true,
			'rewrite' => array( 'slug' => 'age' ),
			));
	}


	/**
	 * 
	 *
	 */
	public static function create_roles() {

		// add_role(
	 //        'pro',
	 //        __( 'Professionnel' ), 
	 //        array(
	 //            'read'          => true,
	 //            'manage_categories' => false,
	 //            'upload_files'  => true,
	 //        )
	 //    );

	    
	}

	public static function add_caps() {

		// global $wp_roles;

		// $administrator     	= $wp_roles->get_role('administrator');
		// $editor     		= $wp_roles->get_role('editor');
		// $pro 				= $wp_roles->get_role('pro');

		// $roles_plus = array();
		// $roles_plus["administrator"] = $administrator;
		// $roles_plus["editor"] = $editor;

		// $post_type_details = get_post_type_object( 'event' );
		// $post_type_cap 	= $post_type_details->capability_type;
		// $post_type_caps	= $post_type_details->cap;

		// foreach ( $roles_plus as $key => $role ) {

		// 	// Shared capability required to see post's menu & publish posts
		// 	$role->add_cap( $post_type_caps->edit_posts ); 
				
		// 	// Shared capability required to delete posts
		// 	$role->add_cap( $post_type_caps->delete_posts );
				
		// 	// Allow publish
		// 	$role->add_cap( $post_type_caps->publish_posts );

		// 	// Allow editing own posts
		// 	$role->add_cap( $post_type_caps->edit_published_posts );
		// 	$role->add_cap( $post_type_caps->edit_private_posts );
		// 	$role->add_cap( $post_type_caps->delete_published_posts );
		// 	$role->add_cap( $post_type_caps->delete_private_posts );
				
		// 	// Allow editing other's posts
		// 	$role->add_cap( $post_type_caps->edit_others_posts );
		// 	$role->add_cap( $post_type_caps->delete_others_posts );
				
		// 	// Allow reading private
		// 	$role->add_cap( $post_type_caps->read_private_posts);

		// }
		

		// // Shared capability required to see post's menu & publish posts
		// $pro->add_cap( $post_type_caps->edit_posts );

		// // Allow editing own posts
		// $pro->add_cap( $post_type_caps->edit_published_posts );
		// $pro->add_cap( $post_type_caps->edit_private_posts );
		// // $role->add_cap( $post_type_caps->delete_published_posts );
		// $pro->add_cap( $post_type_caps->delete_private_posts );
			
		// // Allow reading private
		// $pro->add_cap( $post_type_caps->read_private_posts);

		
		

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
		wp_enqueue_style( 'fontello', "//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css", null, '4.2.0' );
		wp_enqueue_style( 'pnotify', "//cdnjs.cloudflare.com/ajax/libs/pnotify/2.0.0/pnotify.all.min.css", null, '2.0.0' );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery', 'ko' ), self::VERSION );
		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
		wp_enqueue_script('pnotify',	"http://cdnjs.cloudflare.com/ajax/libs/pnotify/2.0.0/pnotify.all.min.js",array("jquery"), '2.0.0', true);


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
				'cfg_debug_mode' 	 			 =>  (bool)get_option("kz_debug_mode"),
				'api_get_nonce'				 	 =>  site_url().'/api/get_nonce/',
				'api_get_event'					 =>  site_url().'/api/events/get_event/',
				'api_get_votes_status'			 =>  site_url().'/api/vote/get_votes_status/', 
				'api_get_votes_user'			 =>  site_url().'/api/vote/get_votes_user/',
				'api_vote_up'			 		 =>  site_url().'/api/vote/up/',
				'api_vote_down'			 		 =>  site_url().'/api/vote/down/',
				'api_generate_auth_cookie'		 => site_url().'/api/auth/generate_auth_cookie/',
				'is_admin' 					=> current_user_can( 'manage_options' )
			)
	);
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

		return array('post', ); //'event'
	}

	/*JSON API*/
	public function add_Kidzou_controller($controllers) {

	  $controllers[] = 'Vote';
	  $controllers[] = 'Auth';
	  $controllers[] = 'Users';
	  // $controllers[] = 'Taxo';

	  return $controllers;
	}


	public function set_vote_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/includes/api/vote.php";
	}
	public function set_auth_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/includes/api/auth.php";
	}
	public function set_users_controller_path() {
	  return plugin_dir_path( __FILE__ ) ."/includes/api/users.php";
	}

	public static function votable_template_mini() {

		echo '
		<script type="text/html" id="vote-template-mini">
	    <span class="vote" data-bind="event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }">
			<i data-bind="css : $data.iconClass"></i>
			<span 	data-bind="text: $data.votes"></span>
	    </span>
		</script>';

	}

	public static function votable_template_mega() {

		echo '
		</script>
		<script type="text/html" id="vote-template-mega">
	    <span class="vote font-2x" data-bind="event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }">
			<i data-bind="css : $data.iconClass"></i>
			<span 	data-bind="text: $data.votes"></span>
	    </span>
		</script>';

	}

	public static function vote_mega($id=0, $class='') {

		if ($id==0)
		{
			global $post;
			$id = $post->ID;
		}

		echo '
		<span class="votable '.$class.'"  
				data-tooltip="'.__('Cela permet aux parents de rep&eacute;rer les sorties les plus int&eacute;ressantes','Kidzou').'"
				data-post="'.$id.'" 
				data-bind="template: { name: \'vote-template-mega\', data: votes.getVotableItem('.$id.') }"></span>';

	}

	public static function vote_mini($id=0, $class='') {

		if ($id==0)
		{
			global $post;
			$id = $post->ID;
		}

		echo '
		<span class="votable '.$class.'"  
				data-post="'.$id.'" 
				data-bind="template: { name: \'vote-template-mini\', data: votes.getVotableItem('.$id.') }"></span>';

	}

}
