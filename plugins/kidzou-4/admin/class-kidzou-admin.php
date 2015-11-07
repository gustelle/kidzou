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
	// protected $plugin_screen_hook_suffix = null;

	
	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  client
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_client = array('post'); //'offres', 'product'

	// *
	//  * les ecrans customer, ils sont particuliers et ne bénéficient pas des 
	//  * meta communes aux écrans $screen_with_meta
	//  * 
	//  *
	//  * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
 //     *
	//  * @since    1.0.0
	//  *
	//  * @var      string
	 
	// public $customer_screen = array('customer');

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 * @TODO:
		 *
		 * - Rename "Plugin_Name" to the name of your initial plugin class
		 *
		 */
		// $plugin = Kidzou::get_instance();
		// $this->plugin_slug = $plugin->get_plugin_slug();

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
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		//scripts partagés
		//todo : clarifier pourquoi on a besoin de ca
		// add_action( 'admin_enqueue_scripts', array( Kidzou_Geo::get_instance() , 'enqueue_geo_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );


		// Add an action link pointing to the options page.
		// $plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		add_action( 'add_meta_boxes', array( $this, 'posts_metaboxes' ) );
		// add_action( 'add_meta_boxes', array( $this, 'page_rewrite_metabox') );

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
		add_action( 'edit_user_profile', array($this,'enrich_profile') );
		add_action( 'edit_user_profile_update', array($this,'save_user_profile') );


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
		add_filter( 'default_content', array($this, 'kz_default_content') );


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
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function notify_admin ()
	{
		// if (Kidzou_Utils::current_user_is('author'))
		// {
		// 	echo '
		// 	<div class="updated">
		//         <p>'.Kidzou::$version_description.'</p>
		//     </div>';
		// }
		
	}
	

	/**
	 * filtre la liste des evenements dans l'écran d'admin pour que les 'pro' et contrib 
	 * ne voient que LEURS contenus, et pas ceux saisis par les autres dans l'admin
	 *
	 * @return void
	 * @see http://shinephp.com/hide-draft-and-pending-posts-from-other-authors/ 
	 **/
	public function contrib_contents_filter( $wp_query ) {

	    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-admin/edit.php' ) !== false  && ( !Kidzou_Utils::current_user_is('author') )) {
		        global $current_user;
		        $wp_query->set( 'author', $current_user->ID );

		        // Kidzou_Utils::log("Kidzou_Admin [contrib_contents_filter]", true);
		    }
	}

	/**
	 * Adds an additional settings section on the edit user/profile page in the admin.  This section allows admins to 
	 * select a metropole from a checkbox of terms from the profession taxonomy.  This is just one example of 
	 * many ways this can be handled.
	 *
	 * @param object $user The user object currently being edited.
	 */
	public function enrich_profile( $user ) {

	    $tax = get_taxonomy( 'ville' );

	    /* Make sure the user is admin. */
	    if ( !Kidzou_Utils::current_user_is('admin'))
	        return;

	    /* Get the terms of the 'profession' taxonomy. */
	    $values = Kidzou_GeoHelper::get_metropoles();

	    //valeur déjà enregistrée pour l'event ?
	    $metros = wp_get_object_terms($user->ID, 'ville', array("fields" => "all"));
	    $metro = $metros[0]; //le premier (normalement contient 1 seul resultat)

	    $radio = empty($metro) ? '' : $metro->term_id;

	    wp_nonce_field( 'kz_save_user_nonce', 'kz_user_info_nonce' );

	    echo '<h3>Infos Kidzou</h3>';
	    echo '<table class="form-table">';

	    if ( user_can( $user->ID, 'edit_posts' )  ) {

	        echo '<tr><th><label for="kz_user_metropole">M&eacute;tropole sur laquelle le user pourra publier</label></th><td>';
	        foreach ($values as $value) {
	            $id = $value->term_id;
	        ?>  
	                <input type="radio" name="kz_user_metropole" id="kz_user_metropole_<?php echo $value->slug; ?>" value="<?php echo $id; ?>" <?php echo ($radio == $id)? 'checked="checked"':''; ?>/> <?php echo $value->name; ?><br />
	            
	        <?php   
	        }
	    }
	    
	    $card = get_user_meta( $user->ID, 'kz_has_family_card', TRUE );
	    $val = '1';
	    if (!$card || $card!=='1') $val = '0';

	    echo '</td/></tr>';


	    echo '<tr><th><label for="kz_has_family_card">L&apos;utilisateur a la carte famille</label></th><td>';
	    echo '<input type="checkbox" name="kz_has_family_card" value="1" '.($val !== "0" ? 'checked="checked"':'').'/> <br />';
	    echo '</td></tr>';



	    echo '</table>';
	}

	/**
	 * déclenchée sur la sauvegarde du user profile dans l'admin pour enregistrer les meta kidzou:
	 * - Metropole du user
	 * - Info de Carte famille (obsolete)
	 *
	 * @return void
	 * @author 
	 **/
	public function save_user_profile($user_id) {

	    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	    	return;
	    
	    if( !isset( $_POST['kz_user_info_nonce'] ) || !wp_verify_nonce( $_POST['kz_user_info_nonce'], 'kz_save_user_nonce' ) ) 
	    	return;

	    if ( !Kidzou_Utils::current_user_is('admin')) 
	    	return;

	    //meta metropole
	    $set = isset( $_POST['kz_user_metropole']) ;
	    if ( !$set ) {
	    	$metropole_slug = Kidzou_GeoHelper::get_default_metropole();
	    	$metropole = get_term_by( 'slug', $metropole_slug, 'ville' );
	    } else {
	    	$metropole = $_POST['kz_user_metropole'];
	    }
	    	
	    $result = wp_set_object_terms( $user_id, array( intval($metropole) ), 'ville' );

	    // meta de la carte famille
	    if (!isset($_POST['kz_has_family_card'])) {
	    	$card = '0';
	    } else {
	    	$card = $_POST['kz_has_family_card']; 
	    }

	    update_user_meta( $user_id, 'kz_has_family_card', $card );
	    
	}

	/**
	 * La liste des métropoles du User
	 *
	 * @return array
	 * @author 
	 **/
	public static function get_user_metropoles ($user_id)
	{
	    if (!$user_id)
	        $user_id = get_current_user_id();

	    $meta = wp_get_object_terms( $user_id, 'ville', array('fields' => 'all') );

	    return (array)$meta;
	}

	
	/**
	 * Rattachement automatique du post à la metropole du user
	 *
	 * @return void
	 * @todo gérer le cas ou le user n'a pas de metropole de rattachement  
	 **/
	public function set_post_metropole($post_id)
	{
	    if (!$post_id) return;

	    if (!Kidzou_Utils::current_user_is('author')) {

	    	//la metropole est la metropole de rattachement du user
		    $metropoles = (array)self::get_user_metropoles(get_current_user_id());
		    $ametro = $metropoles[0];
		    $metro_id = $ametro->term_id;

		    $result = wp_set_post_terms( $post_id, array( intval($metro_id) ), 'ville' );
	    }

	}

	/**
	 * Retourne True si le user courant a la carte famille
	 *
	 * @deprecated
	 * @return Booléen
	 * @author 
	 **/
	public function has_family_card()
	{

	    if (Kidzou_Utils::current_user_is('admin'))
	        return true;

	    $current_user = wp_get_current_user();

	    $umeta = get_user_meta($current_user->ID, 'kz_has_family_card', TRUE);

	    return ($umeta!='' && intval($umeta)==1);
	}	

	/**
	* @deprecated
	*/
	public function handle_upload_prefilter( $file )
	{
	    add_filter('upload_dir', array($this,'custom_upload_dir'));
	    return $file;
	}

	/**
	* @deprecated
	*/
	public function handle_upload( $fileinfo )
	{
	    remove_filter('upload_dir', array($this,'custom_upload_dir'));
	    return $fileinfo;
	}

	/**
	* Organize Upload folder per author
	* 
	* @deprecated
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
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		//on a besoin de font awesome dans le paneau d'admin
		wp_enqueue_style( 'fontello', "//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css", null, '3.0.2' );
		wp_enqueue_style( 'kidzou-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Kidzou::VERSION );

	}



	public function posts_metaboxes() {

		add_meta_box(
			'page_rewrite',
			__( 'Re-&eacute;criture d&apos;URL', 'kidzou' ),
			array($this, 'render_rewrite_metabox'),
			'page',
			'normal',
			'high'
		);

		// $screen = get_current_screen(); 

		// if ( in_array($screen->id , $this->screen_with_meta_client) ) { 

		// 	add_meta_box('kz_client_metabox', 'Client', array($this, 'client_metabox'), $screen->id, 'normal', 'high'); 
		
		// } 

		// if ( in_array($screen->id , $this->screen_with_meta_event) ) { 

		// 	add_meta_box('kz_event_metabox', 'Evenement', array($this, 'event_metabox'), $screen->id, 'normal', 'high');
		// 	add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');
		
		// } else
		// if ( in_array($screen->id, $this->customer_screen) ) {
			
		// 	add_meta_box('kz_customer_posts_metabox', 'Articles associés', array($this, 'customer_posts_metabox'), $screen->id, 'normal', 'high');
		// 	add_meta_box('kz_customer_apis', 'API', array($this, 'customer_apis'), $screen->id, 'normal', 'high');
		// 	add_meta_box('kz_customer_users_metabox', 'Utilisateurs', array($this, 'customer_users_metabox'), $screen->id, 'normal', 'high');

		// 	//lieu par défaut d'un customer
		// 	add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');

		// }

		/**
		 * Permet d'attacher des metabox additionnelles
		 * 
		 * @since customer-analytics
		**/

		do_action('kidzou_add_metabox');

	}

	

	


	// *
	//  * uniquement sur les "pages" : ajout d'une meta pour savoir s'il faut préfixer l'url de la metropole courante
	//  *
	//  * @return void
	//  * @author 
	//  *
	// public function page_rewrite_metabox () {

		
		
	// }

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function render_rewrite_metabox ($post) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'rewrite_metabox', 'rewrite_metabox_nonce' );

		$checkbox = get_post_meta($post->ID, 'kz_rewrite_page', TRUE);
		echo '	
					<label for="kz_rewrite_page">Pr&eacute;fixer l&apos;URL de cette page par la m&eacute;tropole de l&apos;utilisateur :</label>
					<input type="checkbox" name="kz_rewrite_page"'. ( $checkbox ? 'checked="checked"' : '' ).'/>  
				';
		
	}

	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 * @author 
	 **/
	public function save_metaboxes($post_id) {

		$this->save_rewrite_meta($post_id);

		// //
		// $this->save_event_meta($post_id);
		// $this->save_client_meta($post_id);

		// //
		$this->save_post_metropole($post_id);
		$this->set_post_metropole($post_id);

		// //et pour les clients
		// $this->set_customer_users($post_id);
		// $this->set_customer_posts($post_id);
		// $this->set_customer_apis($post_id);

		// //pour tout le monde
		// $this->save_place_meta($post_id);

		/**
		 * Permet d'attacher des metabox additionnelles
		 * 
		 * @since customer-analytics
		**/

		do_action('kidzou_save_metabox', $post_id);

		
	}

	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 * @author 
	 **/
	public function save_rewrite_meta($post_id) {

		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		// Check if our nonce is set.
		if ( ! isset( $_POST['rewrite_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['rewrite_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'rewrite_metabox' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		$meta = array();

		if (!isset($_POST['kz_rewrite_page']))
			$meta['rewrite_page'] = false;
		else
			$meta['rewrite_page'] = ($_POST['kz_rewrite_page']=='on');
			

		self::save_meta($post_id, $meta, "kz_");
		
	}



	/**
	 * rattache un post à une metropole
	 * 
	 * les "editeurs" ou supérieur peuvent forcer la metropole (saisie manuelle)
	 * les autres : la metropole est rattachée automatiquement en fonction de leur profil
	 *
	 * @return void
	 * @author 
	 **/
	public function save_post_metropole($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;
	    
	    if ( Kidzou_Utils::current_user_is('author') ) {

	    	
	    } else {

	    	//la metropole est la metropole de rattachement du user
	    	$this->set_user_metropole($post_id);
	    } 

	}

	/**
	 * rattache un post à la metropole du user (définie dans son profil)
	 * Si pas définie dans le profil, on rattache le post à la ville par defaut
	 *
	 * @return void
	 * @author 
	 **/
	public function set_user_metropole($post_id)
	{
	    if (!$post_id) return;

	    if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

	    $user_id = get_current_user_id();

	    //la metropole est la metropole de rattachement du user
	    $metropoles = wp_get_object_terms( $user_id, 'ville', array('fields' => 'all') );

	    if (!empty($metropoles) && count($metropoles)>0) {
		    //bon finalement on prend la premiere metropole
		    $ametro = $metropoles[0];
		    $metro_id = $ametro->term_id;
	    } else {

	    	$metro_id = Kidzou_Utils::get_option('geo_default_metropole'); //init : ville par defaut
	    }

	    $result = wp_set_post_terms( $post_id, array( intval($metro_id) ), 'ville' );
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

	// /**
	//  * Render the settings page for this plugin.
	//  *
	//  * @since    1.0.0
	//  */
	// public function display_plugin_admin_page() {
	// 	include_once( 'views/admin.php' );
	// }

	// /**
	//  * Add settings action link to the plugins page.
	//  *
	//  * @since    1.0.0
	//  */
	// public function add_action_links( $links ) {

	// 	return array_merge(
	// 		array(
	// 			'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
	// 		),
	// 		$links
	// 	);

	// }

	/**
	 * Lors de l'édition d'un contenu, par défaut on propose le contenu pré-saisi dans les options Kidzou
	 *
	 */
	public function kz_default_content() {

		$content = Kidzou_Utils::get_option('default_content');
    	return $content;
	}

	/**
	 *
	 * <p>
	 * Filtrage des listes de post par Taxonomie
	 * appelé par le hook <code>restrict_manage_posts</code>
	 * </p>
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
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	// public function action_method_name() {
	// 	// @TODO: Define your action hook callback here
	// }

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	// public function filter_method_name() {
	// 	// @TODO: Define your filter hook callback here
	// }

	/**
	 * fonction utilitaire utilisée par les autres classes d'admin
	 *
	 */
	public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {

		if ($post_id==0)
			return;

		// Kidzou_Utils::log(array('save_meta'=> array('post_id'=>$post_id, "arr"=>$arr, "prefix"=>$prefix )), true);

		// Add values of $events_meta as custom fields
		foreach ($arr as $key => $value) { // Cycle through the $events_meta array!

			$pref_key = $prefix.$key; 
			$prev = get_post_meta($post_id, $pref_key, TRUE);

			if ($prev!='') { // If the custom field already has a value
				update_post_meta($post_id, $pref_key, $value);
			} else { // If the custom field doesn't have a value
				if ($prev=='') delete_post_meta($post_id, $pref_key);
				add_post_meta($post_id, $pref_key, $value, TRUE);
			}
			if(!$value) delete_post_meta($post_id, $pref_key); // Delete if blank
		}

	}

}
