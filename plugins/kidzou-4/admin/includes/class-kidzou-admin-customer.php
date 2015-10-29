<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Admin_Customer', 'get_instance' ) );

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
class Kidzou_Admin_Customer {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

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
	 * les ecrans qui meritent qu'on y ajoute des meta client
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_client = array('post'); // typiquement pas les "customer"


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		//pour le B.O (partie admin)
		add_action( 'kidzou_add_metabox', array( $this, 'add_metaboxes') );
		add_action( 'kidzou_save_metabox', array( $this, 'save_metaboxes'), 10, 1);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		
	}

	/**
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		$screen = get_current_screen(); 


		if ( in_array($screen->id, $this->customer_screen) ) { //$screen->id == $this->plugin_screen_hook_suffix ||
			
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
			wp_enqueue_style( 'kidzou-admin', plugins_url( 'assets/css/kidzou-client.css', dirname(__FILE__) ) );

			wp_enqueue_style( 'kidzou-place', plugins_url( 'assets/css/kidzou-edit-place.css', dirname(__FILE__) ) );
			wp_enqueue_style( 'placecomplete', plugins_url( 'assets/css/jquery.placecomplete.css', dirname(__FILE__) ) );
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

			wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);

			wp_enqueue_script('ko-validation',			plugins_url( 'assets/js/knockout.validation.min.js', dirname(__FILE__) ),array("ko"), '1.0', true);
			wp_enqueue_script('ko-validation-locale',	plugins_url( 'assets/js/ko-validation-locales/fr-FR.js', dirname(__FILE__) ),array("ko-validation"), '1.0', true);

			//requis par placecomplete
			wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
			wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );

			//selection des places dans Google Places
			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);
			wp_enqueue_script('placecomplete', plugins_url( 'assets/js/jquery.placecomplete.js', dirname(__FILE__) ),array('jquery-select2', 'google-maps'), '1.0', true);
			
			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', dirname(__FILE__) ) ,array('jquery'), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-place', plugins_url( 'assets/js/kidzou-place.js', dirname(__FILE__) ) ,array('jquery','ko-mapping'), Kidzou::VERSION, true);

			//ecran de gestion des clients
			wp_enqueue_script( 'kidzou-admin-script', plugins_url( 'assets/js/admin.js', dirname(__FILE__) ), array( 'jquery' ), Kidzou::VERSION );
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
	 * Ajout des metabox supplémnetaires à celles gérées par Kidzou_Admin
	 *
	 * @return void
	 * @since customer-analytics
	 * @author 
	 **/
	public function add_metaboxes()
	{
		// Kidzou_Utils::log('Kidzou_Admin_Customer [add_metaboxes]', true);
		$screen = get_current_screen(); 

		if ($screen->id =='customer' ) {

			add_meta_box('kz_customer_analytics_metabox', 'Google Analytics', array($this, 'add_analytics_metabox'), $screen->id, 'normal', 'high'); 
			
			add_meta_box('kz_customer_posts_metabox', 'Articles associés', array($this, 'customer_posts_metabox'), $screen->id, 'normal', 'high');
			add_meta_box('kz_customer_apis', 'API', array($this, 'customer_apis'), $screen->id, 'normal', 'high');
			add_meta_box('kz_customer_users_metabox', 'Utilisateurs', array($this, 'customer_users_metabox'), $screen->id, 'normal', 'high');

			//lieu par défaut d'un customer
			// add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');

		
		} elseif ( in_array($screen->id , $this->screen_with_meta_client) ) { 

			add_meta_box('kz_client_metabox', 'Client', array($this, 'client_metabox'), $screen->id, 'normal', 'high'); 
		
		} 

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
	 * Ajout d'une metabox pour autoriser ou non les users du customer à visualiser leurs analytics 
	 *
	 * @return HTML
	 * @since customer-analytics
	 * @author 
	 **/
	public function add_analytics_metabox()
	{
		global $post;

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'analytics_metabox', 'analytics_metabox_nonce' );

		$checkbox = get_post_meta($post->ID, Kidzou_Customer::$meta_customer_analytics , TRUE);

		echo 	'<ul>
					<li>
						<label for="kz_customer_analytics">Autoriser les utilisateurs de ce client &agrave; visualiser les analytics:</label>
						<input type="checkbox" name="'.Kidzou_Customer::$meta_customer_analytics.'"'. ( $checkbox ? 'checked="checked"' : '' ).'/>  
					</li>
				</ul>';
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function customer_posts_metabox()
	{
		// Kidzou_Utils::log( 'Kidzou_Admin [customer_posts_metabox]',true);

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
		// Kidzou_Utils::log('Kidzou_Admin [customer_users_metabox]',true);
		
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
		// Kidzou_Utils::log('Kidzou_Admin [customer_apis]',true);
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
	 *  sauvegarde des meta lors de l'enregistrement d'un customer
	 *
	 * @return void
	 * @since customer-analytics
	 * @author 
	 **/
	public function save_metaboxes($post_id) {

		$this->save_analytics_metabox($post_id);

		//
		// $this->save_event_meta($post_id);
		$this->save_client_meta($post_id);

		// //
		// $this->save_post_metropole($post_id);
		// $this->set_post_metropole($post_id);

		//et pour les clients
		$this->set_customer_users($post_id);
		$this->set_customer_posts($post_id);
		$this->set_customer_apis($post_id);

		//pour tout le monde
		// $this->save_place_meta($post_id);
		
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


		$slug = 'post';

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		if ( !isset( $_POST['clientmeta_noncename'] ) && !Kidzou_Utils::current_user_is('author') ) {

			//OK, le user ne voit pas la meta client, c'est peut-être qu'il n'a pas le droit de voir la metabox
			//Car elle a été cachée par un autre plugin
			//dans ce cas, le post est rattaché à la méta "client" du user courant

			$current_user_customers = Kidzou_Customer::getCustomersIDByUserID(); //c'est un tableau

			// Kidzou_Utils::log($current_user_customers);

			if (count($current_user_customers)>0)
			{
				Kidzou_Admin::save_meta($post_id, array(
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

		Kidzou_Admin::save_meta($post_id, $events_meta);
		
	}

	/**
	 * sauvegarde de la meta self::$meta_customer_analytics 
	 * qui indique si les users du client peuvent visualiser sur le front les analytics de leurs pages
	 *
	 * @return void
	 * @since customer-analytics
	 * @author 
	 **/
	public function save_analytics_metabox($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;


		$slug = 'customer';

	    // If this isn't a 'book' post, don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		// Check if our nonce is set.
		if ( ! isset( $_POST['analytics_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['analytics_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'analytics_metabox' ) )
			return $post_id;

		$meta = array();

		if ( !isset($_POST[Kidzou_Customer::$meta_customer_analytics]) )
			$meta[Kidzou_Customer::$meta_customer_analytics] = false;
		else
			$meta[Kidzou_Customer::$meta_customer_analytics] = ($_POST[Kidzou_Customer::$meta_customer_analytics]=='on');
			

		Kidzou_Admin::save_meta($post_id, $meta);
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
			Kidzou_Admin::save_meta($mypost, $meta);
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

		Kidzou_Admin::save_meta($post_id, $meta);
	}

	

}
