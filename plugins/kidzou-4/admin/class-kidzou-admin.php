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
	 * les ecrans qui meritent qu'on y ajoute des meta  d'evenement
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_event = array('post'); //, 'offres'

	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  client
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_client = array('post'); //'offres', 'product'

	/**
	 * les ecrans customer, ils sont particuliers et ne bénéficient pas des 
	 * meta communes aux écrans $screen_with_meta
	 * 
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
     *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $customer_screen = array('customer');

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
		$plugin = Kidzou::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		//scripts partagés
		//todo : clarifier pourquoi on a besoin de ca
		// add_action( 'admin_enqueue_scripts', array( Kidzou_Geo::get_instance() , 'enqueue_geo_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );


		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		add_action( 'add_meta_boxes', array( $this, 'posts_metaboxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'page_rewrite_metabox') );

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

		$screen = get_current_screen(); 

		//on a besoin de font awesome dans le paneau d'admin
		wp_enqueue_style( 'fontello', "//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css", null, '3.0.2' );

		if ( in_array($screen->id , $this->screen_with_meta_event)  ) {

			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Kidzou::VERSION );
		
			wp_enqueue_style( 'kidzou-place', plugins_url( 'assets/css/kidzou-edit-place.css', __FILE__ ) );
			wp_enqueue_style( 'placecomplete', plugins_url( 'assets/css/jquery.placecomplete.css', __FILE__ ) );
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', __FILE__ )  );

			//datepicker pour les events
			wp_enqueue_style( 'jquery-ui-custom', plugins_url( 'assets/css/jquery-ui-1.10.3.custom.min.css', __FILE__ ) );	


		} elseif ($screen->id == $this->plugin_screen_hook_suffix || in_array($screen->id, $this->customer_screen)) {
			
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
			wp_enqueue_style( 'kidzou-admin', plugins_url( 'assets/css/kidzou-client.css', __FILE__ ) );

			wp_enqueue_style( 'kidzou-place', plugins_url( 'assets/css/kidzou-edit-place.css', __FILE__ ) );
			wp_enqueue_style( 'placecomplete', plugins_url( 'assets/css/jquery.placecomplete.css', __FILE__ ) );
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', __FILE__ )  );


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

		$screen = get_current_screen(); 

		// Kidzou_Utils::log('enqueue_admin_scripts : '. $screen->id, true);

		//ajout de la meta client
		if (in_array($screen->id , $this->screen_with_meta_client) ) {

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Kidzou::VERSION );

			wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
			
			//validation des champs du formulaire de saisie des events
			wp_enqueue_script('ko-validation',			plugins_url( 'assets/js/knockout.validation.min.js', __FILE__ ),array("ko"), '1.0', true);
			wp_enqueue_script('ko-validation-locale',	plugins_url( 'assets/js/ko-validation-locales/fr-FR.js', __FILE__ ),array("ko-validation"), '1.0', true);
			
			//requis par placecomplete
			wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
			wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );

		}  

		if ( in_array($screen->id , $this->screen_with_meta_event) ) {

			//selection des places dans Google Places
			wp_enqueue_script('placecomplete', plugins_url( 'assets/js/jquery.placecomplete.js', __FILE__ ),array('jquery-select2', 'google-maps'), '1.0', true);
			
			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', __FILE__ ) ,array('jquery'), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-place', plugins_url( 'assets/js/kidzou-place.js', __FILE__ ) ,array('jquery','ko-mapping'), Kidzou::VERSION, true);
			
			//gestion des events
			wp_enqueue_script('kidzou-event', plugins_url( 'assets/js/kidzou-event.js', __FILE__ ) ,array('jquery','ko-mapping', 'moment'), Kidzou::VERSION, true);
			wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
			wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-datepicker-fr', plugins_url( 'assets/js/jquery.ui.datepicker-fr.js', __FILE__ ), array('jquery-ui-datepicker'),'1.0', true);


		} elseif ( $screen->id == $this->plugin_screen_hook_suffix || in_array($screen->id, $this->customer_screen)) {

			//ecran de gestion des clients
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Kidzou::VERSION );

			wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);

			wp_enqueue_script('ko-validation',			plugins_url( 'assets/js/knockout.validation.min.js', __FILE__ ),array("ko"), '1.0', true);
			wp_enqueue_script('ko-validation-locale',	plugins_url( 'assets/js/ko-validation-locales/fr-FR.js', __FILE__ ),array("ko-validation"), '1.0', true);

			//requis par placecomplete
			wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
			wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );

			//selection des places dans Google Places
			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);
			wp_enqueue_script('placecomplete', plugins_url( 'assets/js/jquery.placecomplete.js', __FILE__ ),array('jquery-select2', 'google-maps'), '1.0', true);
			
			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', __FILE__ ) ,array('jquery'), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-place', plugins_url( 'assets/js/kidzou-place.js', __FILE__ ) ,array('jquery','ko-mapping'), Kidzou::VERSION, true);


		}

		wp_localize_script('kidzou-admin-script', 'client_jsvars', array(
				'api_getClients'				=> site_url()."/api/clients/getClients/",
				// 'api_deleteClient'				=> site_url().'/api/clients/deleteClient',
				'api_saveUsers' 				=> site_url().'/api/clients/saveUsers/',
				'api_saveClient'				=> site_url().'/api/clients/saveClient/',
				// 'api_getClientByID' 			=> site_url().'/api/clients/getClientByID/',
				'api_get_userinfo'			 	=> site_url().'/api/search/getUsersBy/',
				// 'api_get_userinfo'			 	=> site_url().'/api/user/get_userinfo/',
				'api_queryAttachableEvents'		=> site_url().'/api/clients/queryAttachableContents/',
				'api_queryAttachablePosts'		=> site_url().'/api/clients/queryAttachablePosts/',
				'api_attachToClient'			=> site_url().'/api/clients/attachToClient/',
				'api_detachFromClient' 			=> site_url().'/api/clients/detachFromClient/',
				'api_getContentsByClientID' 	=> site_url()."/api/clients/getContentsByClientID/",
				'api_getCustomerPlace'			=> site_url()."/api/clients/getCustomerPlace",
				// 'is_user_admin'					=> current_user_can('manage_options')

			)
		);

	}


	public function posts_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta_client) ) { 

			add_meta_box('kz_client_metabox', 'Client', array($this, 'client_metabox'), $screen->id, 'normal', 'high'); 
		
		} 

		if ( in_array($screen->id , $this->screen_with_meta_event) ) { 

			add_meta_box('kz_event_metabox', 'Evenement', array($this, 'event_metabox'), $screen->id, 'normal', 'high');
			add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');
		
		} elseif ( in_array($screen->id, $this->customer_screen) ) {
			
			add_meta_box('kz_customer_posts_metabox', 'Articles associés', array($this, 'customer_posts_metabox'), $screen->id, 'normal', 'high');
			add_meta_box('kz_customer_apis', 'API', array($this, 'customer_apis'), $screen->id, 'normal', 'high');
			add_meta_box('kz_customer_users_metabox', 'Utilisateurs', array($this, 'customer_users_metabox'), $screen->id, 'normal', 'high');

			//lieu par défaut d'un customer
			add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');

		}

		/**
		 * Permet d'attacher des metabox additionnelles
		 * 
		 * @since customer-analytics
		**/

		do_action('kidzou_add_metabox');

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function customer_posts_metabox()
	{
		Kidzou_Utils::log( 'Kidzou_Admin [customer_posts_metabox]',true);

		global $post;

		$args = array(
			'post_type' => Kidzou_Customer::$supported_post_types,
		   'meta_query' => array(
		       array(
		           'key' => Kidzou_Customer::$meta_customer,
		           'value' => $post->ID,
		           'compare' => '=',
		       )
		   ),
		   'post_per_page' => -1
		);
		$query = new WP_Query($args);

		$posts = $query->get_posts();

		$posts_list = '';

		if ( !empty($posts) ) {
			foreach ($posts as $mypost) {
				
				if ($posts_list!='')
					$posts_list .= '|';

				// $post_o = get_post( $mypost );
				$posts_list .= $mypost->ID.'#'.$mypost->post_title; 
			}
		}

		wp_reset_query();

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_posts_metabox', 'customer_posts_metabox_nonce' );

		$output = sprintf('
			<ul>
				<li>
					<label for="customer_posts" style="display:block;">
						Articles appartenant au client :
					</label>
					<input type="hidden" name="customer_posts" id="customer_posts" value="%1$s" style="width:100%% ; display:block;" />
				</li>
				
			</ul>',
			$posts_list
			);

		echo $output;
		
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function customer_users_metabox()
	{	
		Kidzou_Utils::log('Kidzou_Admin [customer_users_metabox]',true);
		
		global $post;

		$post_id = $post->ID; //echo $post_id;

		$user_query = new WP_User_Query( array( 
			'meta_key' => Kidzou_Customer::$meta_customer, 
			'meta_value' => $post_id , 
			'fields' => array('ID', 'user_login') 
			) 
		);
		$main_users = "";

		if ( !empty($user_query->results) ) {
			foreach ($user_query->results as $main) {
				
				if ($main_users!='')
					$main_users .= '|';

				$id = $main->ID;
				$login = $main->user_login;

				$main_users .= $id.'#'.$login; 
			}
		}


		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_users_metabox', 'customer_users_metabox_nonce' );

		$output = sprintf('
			<ul>
				<li>
					<label for="main_users_input" style="display:block;">
						Utilisateurs autoris&eacute;s &agrave; saisir des contenus<br/>
						<strong>La recherche se fait par login ou email</strong>
					</label>
					<input type="hidden" name="main_users_input" id="main_users_input" value="%1$s" style="width:50%% ; display:block;" />
				</li>
				
			</ul>',
			$main_users
		);


		echo $output;


	}

	/**
	 * Metabox de l'écran "customer" pour la Gestion des
	 * <ul> 
	 * <li>Quota</li>
	 * <li>API disponibles</li>
	 * <li>Token</li>
	 * </ul>
	 *
	 * @todo Actuellement seule l'API "excerpts" est geree dans cette fonction
	 *
	 * @return void
	 * @author 
	 **/
	public function customer_apis()
	{
		Kidzou_Utils::log('Kidzou_Admin [customer_apis]',true);
		global $post;

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_apis_metabox', 'customer_apis_metabox_nonce' );

		//La clé d'API d'un client est unique pour toutes les API
		$key	 	= get_post_meta($post->ID, Kidzou_Customer::$meta_api_key, TRUE);

		if ($key == '') {
			$key = md5(uniqid());
		}

		//actuellement $api_names ne sert à rien dans le code
		//C'est pour ouvrir la voie vers une généralisation de la gestion des API
		$api_names = Kidzou_API::getAPINames();
		Kidzou_Utils::log(array('api_names' => $api_names),true);
 		
 		//todo : c'est ici qu'on fait référence en dur à l'API excerpts
 		//pour généraliser cette fonction, il faudrait boucler sur toutes les API 
 		//il faudrait gérer dans les options la liste des API ouvertes puis les récupérer ici par un get_option()
		$open_apis = array();
		$open_apis[0] = 'excerpts';

		$quota = Kidzou_API::getQuota($key, $open_apis[0]); //$api_names[i]
		$usage = Kidzou_API::getCurrentUsage($key, $open_apis[0]); //$api_names[i]

		$output = sprintf('
			<h4>API d&apos;acc&egrave;s au r&eacute;sum&eacute; des contenus</h4>
			<input type="hidden" name="api_name_0" value="%1$s"  />
			<ul>
				<li>
					<label for="customer_api_key_text">Cl&eacute; de s&eacute;curit&eacute;:</label>
					<input type="hidden" name="customer_api_key" value="%2$s"  />
			  		%3$s
				</li>
				<li>
					<label for="customer_api_quota">Quota d&apos;appel par jour:</label>
			  		<input type="text" name="customer_api_quota" value="%4$s"  />
				</li>
				<li>
					Utilisation en cours: %5$s
				</li>
			</ul>
			<ul>
				<li>
					<strong>URL &agrave; communiquer au client:</strong><br/>
					<a target="_blank" href="%6$s">%6$s</a>
				</li>
			</ul>
		',
		$open_apis[0],
		$key,
		$key,
		$quota,
		$usage,
		site_url().'/api/content/excerpts/?key='.$key.'&date_from=YYYY-MM-DD'
		);

		echo $output;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function client_metabox()
	{ 
		Kidzou_Utils::log('Kidzou_Admin [client_metabox]',true);
		global $post; 

		echo '<input type="hidden" name="clientmeta_noncename" id="clientmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		$customer_id =0;
		$customer_name = '';
		
		$customer_id = Kidzou_Customer::getCustomerIDByPostID();
	
		if (is_wp_error($customer_id))
			$customer_id=0;
		else {
			$customer_name = Kidzou_Customer::getCustomerNameByCustomerID($customer_id);
			if (is_wp_error($customer_name))
				$customer_name = '';
		}

		$clients = array();
		$args = array(
				'post_type' => Kidzou_Customer::$post_type, 
				'order' => 'ASC', 
				'orderby' => 'title', 
				'posts_per_page' => -1,
			);

		$q = null;

		//il faut que le client soit > contributeur pour voir tous els clients
		if ( !Kidzou_Utils::current_user_is('author') ) {

			$user_customers = Kidzou_Customer::getCustomersIDByUserID();
		
			//si le user est affecté à au moins un client, on filtre la liste des clients

			if (count($user_customers)>0)
			{
				$q = new WP_Query(
					array_merge($args, array('post__in' => $user_customers))
				);
			} 

			//si le user n'est affecté à aucun client on ne fait rien
			//dans ce cas $q est null
			
		} else {
			$q = new WP_Query( $args );
		}
			

		if (null!=$q)
		{
			$posts = $q->get_posts();

			foreach ($posts as $mypost) {
				$clients[] = array("id" => $mypost->ID, "text" => $mypost->post_title);
			}

			wp_reset_query();
		}
		

		//pre-selection s'il n'y en a qu'un
		if (count($clients)==1) {
			$customer_id = $clients[0]['id'];
			$customer_name = $clients[0]['text'];
		}

		echo sprintf('<script>var clients = %1$s;</script>', json_encode($clients));
		
		?>
		<div class="events_form">

			<?php

			echo '
				<ul>
				<li>
					<label for="kz_customer">Nom du client:</label>
					<input type="hidden" name="kz_customer" id="kz_customer" value="' . $customer_id . '#'.$customer_name.'" style="width:80%" />
				</li>
				</ul>';

			?>
			
		</div>

		<?php
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function event_metabox()
	{
		global $post; 

		////////////////////////////////

		$checkbox = false;

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'event_metabox', 'event_metabox_nonce' );
		
		$start_date		= get_post_meta($post->ID, Kidzou_Events::$meta_start_date, TRUE);
		$end_date 		= get_post_meta($post->ID, Kidzou_Events::$meta_end_date, TRUE);
		$recurrence		= get_post_meta($post->ID, Kidzou_Events::$meta_recurring, FALSE);
		$past_dates		= get_post_meta($post->ID, Kidzou_Events::$meta_past_dates, FALSE);

		echo '<script>
		jQuery(document).ready(function() {
			kidzouEventsModule.model.initDates("'.$start_date.'","'.$end_date.'", '.json_encode($recurrence).');
		});
		</script>

		<div class="kz_form" id="event_form">';

			//si le user n'est pas un "pro", on permet des fonctions d'administration supplémentaires
			if (Kidzou_Utils::current_user_is('administrator')) {

				echo '<h4>Fonctions client</h4>
						<ul>';
				$checkbox = get_post_meta($post->ID, 'kz_event_featured', TRUE);
				echo '	<li>
							<label for="kz_event_featured">Mise en avant:</label>
							<input type="checkbox" name="kz_event_featured"'. ( $checkbox == 'A' ? 'checked="checked"' : '' ).'/>  
						</li>
						</ul>';
					
			} 

			echo '<h4>Dates de l&apos;&eacute;v&eacute;nement</h4>

			<ul>
				<li>
					<label for="start_date">Date de d&eacute;but:</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().start_date, datepickerOptions: { dateFormat: \'dd MM yy\' }"  /> <!-- required -->
			    	<input type="hidden" name="kz_event_start_date"  data-bind="value: eventData().formattedStartDate" />
			    	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
				</li>
				<li>
					<label for="end_date">Date de fin</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().end_date, datepickerOptions: { dateFormat: \'dd MM yy\' }" />
					<input type="hidden" name="kz_event_end_date"  data-bind="value: eventData().formattedEndDate" />
					<em data-bind="if: eventData().eventDuration()!==\'\'">(<span data-bind="text: eventData().eventDuration"></span>)</em>
					<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
				</li>';

				if (Kidzou_Utils::current_user_is('author')) {
					echo
					'<li>
						<label for="kz_event_is_reccuring">Cet &eacute;v&eacute;nement est r&eacute;current </label>
						<input type="checkbox" name="kz_event_is_reccuring" data-bind="enable: eventData().isReccurenceEnabled, checked: eventData().recurrenceModel().isReccuring" />
					</li>';
				}

			echo '</ul>

			<!-- ko if: eventData().recurrenceModel().isReccuring -->
			<h4>R&eacute;p&eacute;tition de L&apos;&eacute;v&eacute;nement</h4>
			<ul>	
		    	<li data-bind="visible: $root.eventData().recurrenceModel().showSelectRepeat">
		    		<label for="kz_event_reccurence_mod">R&eacute;ccurence:</label>
					<select name="kz_event_reccurence_mod" data-bind="options: $root.eventData().recurrenceModel().repeatOptions,
																		optionsText: \'label\',
												                       	value: $root.eventData().recurrenceModel().selectedRepeat" ></select>
					<input type="hidden" name="kz_event_reccurence_model" data-bind="value: eventData().recurrenceModel().selectedRepeat().value" />

		    	</li>
		    	<li>
		    		<label for="kz_event_reccurence_repeat_select">R&eacute;p&eacute;ter tous les :</label>
					<select name="kz_event_reccurence_repeat_select" data-bind="options: $root.eventData().recurrenceModel().selectedRepeat().repeatEvery,
																		value: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEvery" ></select>

		    	</li>
		    	
		    	<li>
		    		<label for="kz_event_reccurence_repeat_choices">R&eacute;p&eacute;ter le :</label>
		    		<!-- ko if: $root.eventData().recurrenceModel().selectedRepeat().multipleChoice -->
			    		<span data-bind="foreach:  $root.eventData().recurrenceModel().selectedRepeat().repeatEach">
			    			<input type="checkbox" name="kz_event_reccurence_repeat_choices"  data-bind="checked: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems, checkedValue: $data" /><span data-bind="text: $data.label" style="padding-right:6px;"></span>
			    			<input type="hidden" name="kz_event_reccurence_repeat_weekly_items" data-bind="value: $root.eventData().recurrenceModel().repeatItemsValue()" />
			    		</span>
			    	<!-- /ko -->
		    		<!-- ko ifnot: $root.eventData().recurrenceModel().selectedRepeat().multipleChoice -->
			    		<span data-bind="foreach:  $root.eventData().recurrenceModel().selectedRepeat().repeatEach">
			    			<input type="radio" name="kz_event_reccurence_repeat_choices" data-bind="checked: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems, checkedValue: $data" /><span data-bind="text: $data.label" style="padding-right:6px;"></span>
			    			<input type="hidden" name="kz_event_reccurence_repeat_monthly_items" data-bind="value: $root.eventData().recurrenceModel().repeatItemsValue()" />
			    		</span>
		    		<!-- /ko -->
		    	</li>

			</ul>
			<ul>	
				<li>
					<label for="kz_event_reccurence_end_type">L&apos;&eacute;v&eacute;nement prend fin :</label>
		    		<input type="radio" name="kz_event_reccurence_end_type" value="never" data-bind="checked: eventData().recurrenceModel().endType" /> never
		    	</li>
		    	<li>
		    		<label> </label>
		    		<input type="radio" name="kz_event_reccurence_end_type" value="date" data-bind="checked: eventData().recurrenceModel().endType" /> Le
		    		<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().recurrenceModel().reccurenceEndDate, datepickerOptions: { dateFormat: \'dd MM yy\' }"  /> 
			    	<input type="hidden" name="kz_event_reccurence_end_date" data-bind="value: eventData().recurrenceModel().formattedReccurenceEndDate" />
		    	</li>
			   	<li>
			   		<label> </label>
			    	<input type="radio" name="kz_event_reccurence_end_type" value="occurences" data-bind="checked: eventData().recurrenceModel().endType" /> Apr&egrave;s <input type="text" name="kz_event_reccurence_end_after_occurences" data-bind="value: eventData().recurrenceModel().occurencesNumber" /> occurences
			    </li>
			</ul>
			<ul>	
		    	<li><b>R&eacute;sum&eacute; : <span data-bind="text: eventData().recurrenceModel().recurrenceSummary()" /></b></li>
			</ul>
			<!-- /ko -->';

			if (!empty($past_dates) && count($past_dates[0])>0)
			{
				echo '<ul><h4>Ev&eacute;nements pass&eacute;s :</h4>';
				foreach ($past_dates[0] as  $value) {
					// Kidzou_Utils::log($value);
					$past_start_date=date_create($value['start_date']);
					$past_end_date=date_create($value['end_date']);
					echo '<li>Du '.date_format($past_start_date,"d/m/Y").' au '.date_format($past_end_date,"d/m/Y").'</li>';
				}
				echo '</ul>';

			}

		echo 
		'</div>';

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function place_metabox()
	{
		global $post;
		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="placemeta_noncename" id="placemeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		$location = Kidzou_GeoHelper::get_post_location($post->ID); 

		// Kidzou_Utils::log(array('location'=>$location), true);

		// Get the location data if its already been entered
		$location_name 		= $location['location_name'];
		$location_address 	= $location['location_address'];
		$location_website 	= $location['location_web'];
		$location_phone_number 	= $location['location_tel'];
		$location_city 			= $location['location_city'];
		$location_latitude 		= $location['location_latitude'];
		$location_longitude 	= $location['location_longitude'];

		//si aucune méta de lieu n'est pré-existante, on prend celle du client associé au post
		if ($location_name=='') {

			$id = 0;

			if (!Kidzou_Utils::current_user_is('administrator') ) {

				$res = Kidzou_Customer::getCustomersIDByUserID();//print_r($res);

				//on prend le premier s'il n'y en a qu'un
				if (is_array($res) && count($res)==1) {
					$vals =array_values($res);
					$id = $vals[0];
				}
					
			} else {
				
				$id = Kidzou_Customer::getCustomerIDByPostID();
				if (is_wp_error($id))
					$id=0;
			}

			$location = Kidzou_GeoHelper::get_post_location($id);

			if (isset($location['location_name']) && $location['location_name']!='') {

				$location_name 		= $location['location_name'];
				$location_address 	= $location['location_address'];
				$location_website 	= $location['location_web'];
				$location_phone_number 	= $location['location_tel'];
				$location_city 			= $location['location_city'];
				$location_latitude 		= $location['location_latitude'];
				$location_longitude 	= $location['location_longitude'];
			}

		}

		echo '<script>
		jQuery(document).ready(function() {
			kidzouPlaceModule.model.initPlace("'.$location_name.'","'.$location_address.'","'.$location_website.'","'.$location_phone_number.'","'.$location_city.'","'.$location_latitude.'","'.$location_longitude.'");
		});
		</script>

		<div class="kz_form" id="place_form">

			<input type="hidden" name="kz_location_latitude" id="kz_location_latitude" data-bind="value: placeData().place().lat" />
			<input type="hidden" name="kz_location_longitude" id="kz_location_longitude" data-bind="value: placeData().place().lng" />

			<h4>Cela se passe o&ugrave; ?</h4>
			<ul>
			<!-- ko ifnot: customPlace() -->
			<li>
				<input type="hidden" name="place" data-bind="placecomplete:{
																placeholderText: \'Ou cela se passe-t-il ?\',
																minimumInputLength: 2,
																allowClear:true,
															    requestParams: {
															        types: [\'establishment\']
															    }}, event: {\'placecomplete:selected\':completePlace}" style="width:80%" >
				<br/><br/>
				<em>
					<a href="#" data-bind="click: displayCustomPlaceForm">Vous ne trouvez pas votre bonheur dans cette liste?</a><br/>
				</em>
			</li>
			<!-- /ko -->
			<!-- ko if: customPlace() -->
			<li>
				<label for="kz_location_name">Nom du lieu:</label>
				<input type="text" name="kz_location_name" placeholder="Ex: chez Gaspard" data-bind="value: placeData().place().venue" required>

			</li>
			<li>
				<label for="kz_location_address">Adresse:</label>
				<input type="text" name="kz_location_address" placeholder="Ex: 13 Boulevard Louis XIV 59800 Lille" data-bind="value: placeData().place().address" required>
			</li>
			<li>
				<label for="kz_location_city">Quartier / Ville:</label>
				<input type="text" name="kz_location_city" placeholder="Ex: Lille Sud" data-bind="value: placeData().place().city" required>

			</li>
			<li>
				<label for="kz_location_latitude">Latitude:</label>
				<input type="text" name="kz_location_latitude" placeholder="Ex : 50.625935" data-bind="value: placeData().place().lat" >
			</li>
			<li>
				<label for="kz_location_longitude">Longitude:</label>
				<input type="text" name="kz_location_longitude" placeholder="Ex : 3.0462689999999384" data-bind="value: placeData().place().lng" >
			</li>
			<li>
				<label for="kz_location_website">Site web:</label>
				<input type="text" name="kz_location_website" placeholder="Ex: http://www.kidzou.fr" data-bind="value: placeData().place().website" >
			</li>
			<li>
				<label for="kz_location_phone_number">Tel:</label>
				<input type="text" name="kz_location_phone_number" placeholder="Ex : 03 20 30 40 50" data-bind="value: placeData().place().phone_number" >
			</li>

			<li>
				
			</li>
			<li><button data-bind="click: displayGooglePlaceForm" class="button button-primary button-large">Changer de lieu</button></li>	
			<!-- /ko -->

			</ul>

		</div>';
	}

	/**
	 * uniquement sur les "pages" : ajout d'une meta pour savoir s'il faut préfixer l'url de la metropole courante
	 *
	 * @return void
	 * @author 
	 **/
	public function page_rewrite_metabox () {

		add_meta_box(
			'page_rewrite',
			__( 'Re-&eacute;criture d&apos;URL', 'kidzou' ),
			array($this, 'render_rewrite_metabox'),
			'page',
			'normal',
			'high'
		);
		
	}

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

		//
		$this->save_event_meta($post_id);
		$this->save_client_meta($post_id);

		//
		$this->save_post_metropole($post_id);
		$this->set_post_metropole($post_id);

		//et pour les clients
		$this->set_customer_users($post_id);
		$this->set_customer_posts($post_id);
		$this->set_customer_apis($post_id);

		//pour tout le monde
		$this->save_place_meta($post_id);

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
	 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
	 *
	 * @return void
	 * @author 
	 **/
	public function save_event_meta($post_id)
	{

		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		// Check if our nonce is set.
		if ( ! isset( $_POST['event_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['event_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'event_metabox' ) )
			return $post_id;


		//formatter les dates avant de les sauvegarder 
		//input : 23 Février 2014
		//output : 2014-02-23 00:00:01 (start_date) ou 2014-02-23 23:59:59 (end_date)
		$events_meta['start_date'] 			= (isset($_POST['kz_event_start_date']) ? $_POST['kz_event_start_date'] : '');
		$events_meta['end_date'] 				= (isset($_POST['kz_event_end_date']) ? $_POST['kz_event_end_date'] : '');

		//les options de récurrence
		if (isset($_POST['kz_event_is_reccuring']) && $_POST['kz_event_is_reccuring']=='on')
		{
			$events_meta['recurrence'] = array(
					"model" => $_POST['kz_event_reccurence_model'],
					"repeatEach" => $_POST['kz_event_reccurence_repeat_select'],
					"repeatItems" => (isset($_POST['kz_event_reccurence_repeat_monthly_items']) ? $_POST['kz_event_reccurence_repeat_monthly_items'] : json_decode($_POST['kz_event_reccurence_repeat_weekly_items'])), 
					"endType" 	=> $_POST['kz_event_reccurence_end_type'],
					"endValue"	=> ($_POST['kz_event_reccurence_end_type']=='date' ? $_POST['kz_event_reccurence_end_date'] : $_POST['kz_event_reccurence_end_after_occurences'])
				);
		}
		
		//cette metadonnée n'est pas mise à jour dans tous les cas
		//uniquement si le user est admi
		// echo ''
		if ( Kidzou_Utils::current_user_is('administrator') ) 
			$events_meta['featured'] 			= (isset($_POST['kz_event_featured']) && $_POST['kz_event_featured']=='on' ? "A" : "B");
		else {
			if (get_post_meta($post_id, 'kz_event_featured', TRUE)!='') {
				$events_meta['featured'] 			= get_post_meta($post_id, 'kz_event_featured', TRUE);
			} else {
				$events_meta['featured'] = "B";//($events_meta['start_date']!='' ? "B" : "Z");
			}
				
		}


		self::save_meta($post_id, $events_meta, "kz_event_");

	}

	/**
	 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
	 *
	 * @return void
	 * @author 
	 **/
	public function save_place_meta($post_id)
	{	
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		if ( ! isset( $_POST['placemeta_noncename'] ) )
			return $post_id;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['placemeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		// Is the user allowed to edit the post or page?
		if ( !Kidzou_Utils::current_user_is('contributor') )
			return $post_id;

		$type = get_post_type($post_id);

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.
		$events_meta['location_name'] 			= (isset($_POST['kz_location_name']) ? $_POST['kz_location_name'] : '');
		$events_meta['location_address'] 		= (isset($_POST['kz_location_address']) ? $_POST['kz_location_address'] : '');
		$events_meta['location_website'] 		= (isset($_POST['kz_location_website']) ? $_POST['kz_location_website'] : '');
		$events_meta['location_phone_number'] 	= (isset($_POST['kz_location_phone_number']) ? $_POST['kz_location_phone_number'] : '');
		$events_meta['location_city'] 			= (isset($_POST['kz_location_city']) ? $_POST['kz_location_city'] : '');
		$events_meta['location_latitude'] 		= (isset($_POST['kz_location_latitude']) ? $_POST['kz_location_latitude'] : '');
		$events_meta['location_longitude'] 		= (isset($_POST['kz_location_longitude']) ? $_POST['kz_location_longitude'] : '');

		$prefix = 'kz_' . $type . '_';

		self::save_meta($post_id, $events_meta, $prefix);
		
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function save_client_meta ($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		// Kidzou_Utils::log('save_client_meta');

		if ( !isset( $_POST['clientmeta_noncename'] ) && !Kidzou_Utils::current_user_is('author') ) {

			//OK, le user ne voit pas la meta client, c'est peut-être qu'il n'a pas le droit de voir la metabox
			//Car elle a été cachée par un autre plugin
			//dans ce cas, le post est rattaché à la méta "client" du user courant

			$current_user_customers = Kidzou_Customer::getCustomersIDByUserID(); //c'est un tableau

			Kidzou_Utils::log($current_user_customers);

			if (count($current_user_customers)>0)
			{
				self::save_meta($post_id, array(
					Kidzou_Customer::$meta_customer => $current_user_customers[0] //on prend le premier
					)
				); 
			}

			//pas la peine d'aller plus loin
			return $post_id;
		
		}
			

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset( $_POST['clientmeta_noncename'] ) || !wp_verify_nonce( $_POST['clientmeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		$key = Kidzou_Customer::$meta_customer;

		$tmp_post = $_POST[$key];
		$tmp_arr = explode("#", $tmp_post );
		$events_meta[$key] 	= $tmp_arr[0];

		//toujours s'assurer que si le client n'est pas positonné, la valeur 0 est enregistrée
		if (strlen($events_meta[$key])==0 || intval($events_meta[$key])<=0)
			$events_meta[$key] = 0;

		self::save_meta($post_id, $events_meta);
		
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	function set_customer_users ($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slug = 'customer';

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		if ( ! isset( $_POST['customer_users_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['customer_users_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'customer_users_metabox' ) )
			return $post_id;

		// seuls les users sont autorisés
		if ( !Kidzou_Utils::current_user_is('author') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		$main = array();
		$meta = array();

		$tmp_post = $_POST['main_users_input'];

		Kidzou_Utils::log( 'set_customer_users, reception de ' . $tmp_post  );

		$tmp_token = explode("|", $tmp_post );
		foreach ($tmp_token as $tok) {
			$pieces = explode("#", $tok );
			if (intval($pieces[0])>0)
				$main[] = intval($pieces[0]);
		}

		//sauvegarder également coté user pour donner les rôles
		
		//il faut faire un DIFF :
		//recolter la liste des users existants sur ce client
		//comparer à la liste des users passés dans le POST
		//supprimer, ajouter selon les cas


		//boucle primaire
		//si les users passés dans la req étaient déjà présents en base
		//	si il n'avaient pas capacité edit_others_events, -
		//	sinon -
		//si non
		// 	on ajoute le user à la liste des users du client
		//		si il n'a pas la capacité edit_others_events, -
		foreach ($main as $a_user) {

			//toujours s'assurer qu'il est contrib, ca ne mange pas de pain
			//mais ne pas dégrader son role s'il est éditeur ou admin
			$u = new WP_User( $a_user );
			$better = false;

			$better_roles = array('administrator','editor','author');

			if ( !empty( $u->roles )  ) {
				foreach ( $u->roles as $role )
					if (in_array($role, $better_roles)) {
						$better = true;
						break;
					}
			}

			if (!$better) {

				$a_user = wp_update_user( array( 'ID' => $a_user, 'role' => 'contributor' ) );

				Kidzou_Utils::log( 'User ' . $a_user . ' updated' );

			}

			 //ajouter la meta qui va bien
			Kidzou_Utils::log(  'User ' . $a_user . ' : add_user_meta'   );

		    // add_user_meta( $a_user, Kidzou_Customer::$meta_customer, $post_id, TRUE ); //cette meta est unique
		    $prev_customers = get_user_meta($a_user, Kidzou_Customer::$meta_customer, false);   //plusieurs meta customer par user

		    if ( empty($prev_customers) )
		     	$prev_customers = array();

		     if (!in_array($post_id, $prev_customers))
		     	add_user_meta($a_user, Kidzou_Customer::$meta_customer, $post_id, false); //pas unique !
	        
		}

		//boucle secondaire
		//si la base contenait une liste d'utilisateurs pour le client
		//	si le user a été repassé en requette 
		// 		on supprime le role du user user 
		// 		ainsi que la meta client

		$args = array(
			'meta_key'     => Kidzou_Customer::$meta_customer,
			'meta_value'   => $post_id,
			'fields' => 'id' //retourne un array(id)
		 );

		$old_users = get_users($args); 

		if (!is_null($old_users)) {

			//boucle complémentaire:
			foreach ($old_users as $a_user) {

				Kidzou_Utils::log( 'Boucle secondaire, User ' . $a_user );

				//l'utilisateur n'a pas été repassé dans la requete
				//il n'est pas donc plus attaché au client
				if (!in_array($a_user, $main)) {

					$u = new WP_User( $user->ID );

					$better = false;

					$better_roles = array('administrator','editor','author');

					if ( !empty( $u->roles ) && is_array( $u->roles ) ) {
						foreach ( $u->roles as $role )
							if (in_array($role, $better_roles)) {
								$better = true;
								break;
							}
					}

					//ne pas dégrader automatiquement le role
					//faire confirmer au user qu'il souhaite dégrader le role
					if (!$better) {
						//privé de gateau
				        // $a_user = wp_update_user( array( 'ID' => $a_user, 'role' => 'subscriber' ) );
					}

			        //suppression de la meta du client dans tous les cas
			        Kidzou_Utils::log( $a_user . ' : suppression de la meta' );

			        delete_user_meta( $a_user, Kidzou_Customer::$meta_customer, $post_id );

				}
				
			}

		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	function set_customer_posts ($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slug = 'customer';

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		if ( ! isset( $_POST['customer_posts_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['customer_posts_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'customer_posts_metabox' ) )
			return $post_id;

		// seuls les users sont autorisés
		if ( !Kidzou_Utils::current_user_is('author') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		$posts = array();
		$meta = array();

		$tmp_post = $_POST['customer_posts'];
		$tmp_token = explode("|", $tmp_post );

		Kidzou_Utils::log(  'set_customer_posts : Reception de ' .$tmp_post );

		foreach ($tmp_token as $tok) {
			$pieces = explode("#", $tok );
			$posts[] = $pieces[0];
		}

		$meta[Kidzou_Customer::$meta_customer] 	= $post_id;

		foreach ($posts as $mypost) {
			self::save_meta($mypost, $meta);
		}


		//ensuite faire un diff pour virer ceux qui ont la meta et qui ne devraient pas
		$args = array(
		   'meta_query' => array(
		       array(
		           'key' => Kidzou_Customer::$meta_customer,
		           'value' => $post_id,
		           'compare' => '=',
		       )
		   ),
		   'post_per_page' => -1,
		   'post_type' => Kidzou_Customer::$supported_post_types
		);
		$query = new WP_Query($args);

		$old_posts = $query->get_posts();

		foreach ($old_posts as $an_old_one) {
			if (in_array($an_old_one->ID, $posts)) {
				//c'est bon rien à faire
			} else {
				Kidzou_Utils::log( 'Post '.$an_old_one->ID.' n\'est plus affecte au client '. $post_id );
				
				delete_post_meta($an_old_one->ID, Kidzou_Customer::$meta_customer, $post_id);
			}
		}
		
	}

	/**
	 * Sauvegarde en base des Quota et de la clé du client, tels que définis dans la Metabox sur l'écran "customer"
	 *
	 * @todo Actuellement seule une API est gérée 
	 * 
	 * @return void
	 * @author 
	 **/
	function set_customer_apis ($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slug = 'customer';

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		if ( ! isset( $_POST['customer_apis_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['customer_apis_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'customer_apis_metabox' ) )
			return $post_id;

		// seuls les users sont autorisés
		if ( !Kidzou_Utils::current_user_is('author') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		$key = $_POST['customer_api_key'];
		$quota = $_POST['customer_api_quota'];
		
		$meta[Kidzou_Customer::$meta_api_key] 	= $key;

		//todo : actuellement seule une API est gérée
		$api_names = array();
		$api_names[0] = $_POST['api_name_0'];

		$meta[Kidzou_Customer::$meta_api_quota] = array($api_names[0] => $quota);

		self::save_meta($post_id, $meta);
	}

	/**
	 * fonction utilitaire
	 */
	public static function save_meta($post_id = 0, $arr = array(), $prefix = '') {

		if ($post_id==0)
			return;

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
