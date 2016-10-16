<?php

/**
 * Cette classe contient les spécifiques Kidzou de l'Admin Wordpress
 *
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
	// protected $plugin_screen_hook_suffix = null;

	
	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  client
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_client = array('post'); //'offres', 'product'


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/**
		 * certains hook ont besoin d'etre déclarés tres tot
		 * par secu je les déclar là
		 * @see  http://wordpress.stackexchange.com/questions/50738/why-do-some-hooks-not-work-inside-class-context
		 * 
		 */ 
		add_action('wp_loaded', array($this, 'init'));

		add_action( 'admin_notices', array($this, 'notify_admin') );


	}

	function init() {

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );


		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		add_action( 'add_meta_boxes', array( $this, 'posts_metaboxes' ) );

		//sauvegarde des meta à l'enregistrement
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );
	
		//http://wordpress.stackexchange.com/questions/25894/how-can-i-organize-the-uploads-folder-by-slug-or-id-or-filetype-or-author
		add_filter('wp_handle_upload_prefilter', array($this, 'handle_upload_prefilter'));
		add_filter('wp_handle_upload', array($this,'handle_upload'));
		

		/**
		 * les users sont rattachés à une metropole  
		 * cela permet de rattacher automatiquement des contenus edités par les contrib à des metropoles
		 * sans que les contrib aient à saisir cette métadata
		 *
		 * Ajout également d'une metadata pour savoir si le user
		 * a la carte famille
		 *
		 **/
		// add_action( 'edit_user_profile', array($this,'enrich_profile') );
		// add_action( 'edit_user_profile_update', array($this,'save_user_profile') );


		/**
		 * filtre la liste des evenements dans l'écran d'admin pour que les 'pro', contrib et auteurs
		 * ne voient que LEURS contenus, et pas ceux saisis par les autres dans l'admin
		 **/
		add_filter('parse_query', array($this, 'contrib_contents_filter' ));

		/**
		 * Ajout d'un filtre par "ville" sur les listes de post
		 *
		 * @link http://wordpress.stackexchange.com/questions/578/adding-a-taxonomy-filter-to-admin-list-for-a-custom-post-type
		 */
		add_action('restrict_manage_posts',array($this, 'filter_posts_list'));

		
		/**
		 * Ajout d'un contenu par defaut lors de l'édition de contenu
		 *
		 * @link https://developer.wordpress.org/reference/hooks/default_content/
		 */
		add_filter( 'default_content', array($this, 'kz_default_content'), 99, 2 );

		/**
		 * Ajout d'un contenu par defaut lors de l'édition de contenu
		 *
		 * @link https://developer.wordpress.org/reference/hooks/default_title/
		 */
		add_filter( 'default_title', array($this, 'kz_default_title'), 99, 2 );

		/**
		 * Ajout d'un contenu par defaut lors de l'édition de contenu
		 *
		 * @link https://developer.wordpress.org/reference/hooks/enter_title_here/
		 */
		add_filter( 'enter_title_here', array($this, 'default_placeholder'), 99, 2 );


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

		do_action('kidzou_admin_loaded');

		return self::$instance;
	}


	/**
	 *
	 * @todo Generer ici les Notifications Kidzou dans l'interface d'admin
	 * @see Hook 'admin_notices'
	 **/
	public function notify_admin ()
	{
		
	}
	

	/**
	 * filtre la liste des evenements dans l'écran d'admin pour que les 'pro' et contrib 
	 * ne voient que LEURS contenus, et pas ceux saisis par les autres dans l'admin
	 *
	 * @return void
	 * @see http://shinephp.com/hide-draft-and-pending-posts-from-other-authors/ 
	 * @param WP_Query $wp_query Query wordpress à filtrer 
	 **/
	public function contrib_contents_filter( $wp_query ) {

	    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-admin/edit.php' ) !== false  && ( !Kidzou_Utils::current_user_is('author') )) {
		        global $current_user;
		        $wp_query->set( 'author', $current_user->ID );
		    }
	}

	/**
	*
	* @uses Kidzou_Admin::custom_upload_dir()
	*/
	public function handle_upload_prefilter( $file )
	{
	    add_filter('upload_dir', array($this,'custom_upload_dir'));
	    return $file;
	}

	/**
	*
	* @uses Kidzou_Admin::custom_upload_dir()
	*/
	public function handle_upload( $fileinfo )
	{
	    remove_filter('upload_dir', array($this,'custom_upload_dir'));
	    return $fileinfo;
	}

	/**
	*
	* Organize Upload folder per author login
	* 
	* @link http://wordpress.stackexchange.com/questions/25894/how-can-i-organize-the-uploads-folder-by-slug-or-id-or-filetype-or-author
	* @param object $path  
	*/
	public function custom_upload_dir($path)
	{   
	    /*
	     * Determines if uploading from inside a post/page/cpt - if not, default Upload folder is used
	     */
	    $use_default_dir = ( isset($_REQUEST['post_id'] ) && $_REQUEST['post_id'] == 0 ) ? true : false; 
	    if( !empty( $path['error'] ) || $use_default_dir )
	        return $path; //error or uploading not from a post/page/cpt 

		$the_post = get_post($_REQUEST['post_id']);
		$the_author = get_user_by('id', $the_post->post_author);
		$customdir = '/' . $the_author->data->user_login; //alternative : display_name

		//Bugfix : suppression de Whitespaces dans les login (ex: "Barraca Zem")
		//ce qui cause des soucis à l'affichage
		$customdir = preg_replace('/\s+/','',$customdir);

	    $path['path']    = str_replace($path['subdir'], '', $path['path']); //remove default subdir (year/month)
	    $path['url']     = str_replace($path['subdir'], '', $path['url']);      
	    $path['subdir']  = $customdir;
	    $path['path']   .= $customdir; 
	    $path['url']    .= $customdir;  

	    return $path;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 *
	 */
	public function enqueue_admin_styles() {

		//on a besoin de font awesome dans le paneau d'admin
		wp_enqueue_style( 'fontello', "https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css", null, '3.0.2' );
		wp_enqueue_style( 'kidzou-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Kidzou::VERSION );

	}


	/**
	 * Ajout de metabox sur les posts 
	 * 
	 * @see Kidzou_Admin::render_rewrite_metabox() Metabox de ré-ecriture du permalien d'une page en fonction de la metropole courante du user
	 */
	public function posts_metaboxes() {

		// add_meta_box(
		// 	'page_rewrite',
		// 	__( 'Re-&eacute;criture d&apos;URL', 'kidzou' ),
		// 	array($this, 'render_rewrite_metabox'),
		// 	'page',
		// 	'normal',
		// 	'high'
		// );


		/**
		 * Permet d'attacher des metabox additionnelles
		 * 
		 * @since customer-analytics
		**/

		do_action('kidzou_add_metabox');

	}

	

	/**
	 *  sauvegarde des meta a l'enregistrement d'un post 
	 *
	 * @param int $post_id The ID of the post currently being edited.
	 *
	 **/
	public function save_metaboxes($post_id) {

		// $this->save_rewrite_meta($post_id);
		// $this->save_post_metropole($post_id);

		/**
		 * Permet d'attacher des metabox additionnelles
		 * 
		 * @since customer-analytics
		**/

		do_action('kidzou_save_metabox', $post_id);

		
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
		// $this->plugin_screen_hook_suffix = add_options_page(
		// 	__( 'Options de Kidzou', $this->plugin_slug ),
		// 	__( 'Kidzou', $this->plugin_slug ),
		// 	'manage_options',
		// 	$this->plugin_slug,
		// 	array( $this, 'display_plugin_admin_page' )
		// );

	}

	/**
	 * Lors de l'édition d'un contenu, par défaut le placeholder du titre
	 *
	 * @return string placeholder du titre du post
	 */
	public function default_placeholder($title, $post) {
	    return 'Saisissez le titre de votre article';
	}

	/**
	 * Lors de l'édition d'un contenu, par défaut on propose un titre vide
	 *
	 * @return string le titre du post par défaut
	 */
	public function kz_default_title($title, $post) {
	    return '';
	}


	/**
	 * Lors de l'édition d'un contenu, par défaut on propose le contenu pré-saisi dans les options Kidzou
	 *
	 * @return string le contenu affiché dans l'éditeur wordpress
	 */
	public function kz_default_content($content, $post) {
		$content = Kidzou_Utils::get_option('default_content');
    	return $content;
	}

	/**
	 *
	 * Filtrage des listes de post par Taxonomie
	 * appelé par le hook <code>restrict_manage_posts</code>
	 *
	 * Uniquement accessible aux Auteurs ou +
	 *
	 * @link http://wordpress.stackexchange.com/questions/578/adding-a-taxonomy-filter-to-admin-list-for-a-custom-post-type
	 */
	public function filter_posts_list() {
		global $typenow;
 
		// an array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array('ville');
	 
		// must set this to the post type you want the filter(s) displayed on
		if( $typenow == 'post' && Kidzou_Utils::current_user_is('author') ){
	 
			foreach ($taxonomies as $tax_slug) {
				$tax_obj = get_taxonomy($tax_slug);
				$tax_name = $tax_obj->labels->name;
				$terms = get_terms($tax_slug);
				if(count($terms) > 0) {
					echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
					echo "<option value=''>Voir toutes les $tax_name</option>";
					foreach ($terms as $term) { 
						echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>'; 
					}
					echo "</select>";
				}
			}

		}
	}

	/**
	 * legacy method qui renvoit vers une classe Utilitaire
	 * afin de partager cette methode avec des API qui ne doivent pas dépendre des composants d'admin
	 */
	public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {
		Kidzou_Utils::save_meta($post_id, $arr, $prefix);
	}

}
