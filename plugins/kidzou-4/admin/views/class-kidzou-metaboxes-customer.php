<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Customer', 'get_instance' ), 10 );

/**
 * Cette classe gère les Metaboxes utiles à la gestion des données Customer dans les écrans d'admin
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Metaboxes_Customer {

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
		global $post;

		if ( in_array($screen->id , $this->screen_with_meta_client) || in_array($screen->id, $this->customer_screen) ) { 

			wp_enqueue_script('react',			"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js",	array('jquery'), '0.14.7', true);
			wp_enqueue_script('react-dom',		"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js",	array('react'), '0.14.7', true);

			//inline edit
			wp_enqueue_script('react-inline-edit', plugins_url( 'assets/js/lib/react-inline-edit.js', dirname(__FILE__) ), array('react'), '1.0', true);

			//dependances pour ReactSelect
			wp_enqueue_script( 'classNames', 			plugins_url( '/assets/js/lib/classNames.js', dirname(__FILE__) ), array( ), '1.0', true);
			wp_enqueue_script( 'react-input-autosize', 	plugins_url( '/assets/js/lib/react-input-autosize.min.js', dirname(__FILE__) ), array( 'react'), '1.0', true);
			wp_enqueue_script( 'react-select', 			plugins_url( '/assets/js/lib/react-select.js', dirname(__FILE__) ), array( 'react', 'react-input-autosize', 'classNames'), '1.0', true);

			wp_enqueue_style( 'react-select', 	plugins_url( 'assets/css/lib/react-select.css', dirname(__FILE__) )  );
			wp_enqueue_style( 'kidzou-form', 	plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

			wp_enqueue_script('kidzou-react', 	plugins_url( 'assets/js/kidzou-react.js', dirname(__FILE__) ) ,array('react-dom'), Kidzou::VERSION, true);			

			//////////////////////////////////////////////////
			$customer_id = 0;
			if (in_array($screen->id , $this->screen_with_meta_client))
				$customer_id = Kidzou_Customer::getCustomerIDByPostID();
			
			if (in_array($screen->id , $this->customer_screen))
				$customer_id = $post->ID;
			
			if (is_wp_error($customer_id))
				$customer_id=0;

			$posts = array();
			if ($customer_id>0) {
				$posts = Kidzou_Customer::getPostsByCustomerID($customer_id, array('posts_per_page'=> -1, 'post_status'=>'any'));
			}

			//populer la liste des clients pour les écrans qui utilisent la sélection de clients
			if (in_array($screen->id , $this->screen_with_meta_client)) {

				$args = array();

				$args['api_save_place'] 	= site_url()."/api/content/place/";
				$args['api_create_post'] 	= site_url()."/api/posts/create_post/";
				$args['api_getCustomerPlace']	= site_url()."/api/clients/getCustomerPlace/";

				$args['customer_posts'] = array_map(function($item){
					return array('id'=>$item->ID, 'title'=>$item->post_title);
				}, $posts);
				
				//pour preselection du client 
				$args['customer_id'] 		=  $customer_id;
				$args['clients_list'] 		=  self::getClients();
				$args['api_attach_posts'] 	= site_url()."/api/clients/posts/";
				$args['api_base'] 			= site_url();
				$args['admin_url'] 			= admin_url();
			} 

			//selection de users et de posts sur l'écran customer
			if (in_array($screen->id , $this->customer_screen)) {

				//partie users
				$users = array(
					'api_get_userinfo'			 	=> site_url().'/api/search/getUsersBy/');

				$customer_users = Kidzou_Customer::getUsersByCustomerID($customer_id);
				$users['customer_users'] = array_map(function($item){
					return array('id'=>$item->ID, 'title'=> $item->display_name.' ('.$item->user_email.') ');
				}, $customer_users);
				$users['api_base'] 			= site_url();
				$users['api_attach_users'] 	= site_url()."/api/clients/users/";

				//////////////////////////////////////////////////////////////
				//partie "posts"
				$customer_posts = array(
					'api_queryAttachablePosts'		=> site_url().'/api/clients/queryAttachablePosts/');

				$customer_posts['customer_posts'] = array_map(function($item){
					return array('id'=>$item->ID, 'title'=>$item->post_title);
				}, $posts);
				$customer_posts['api_base'] 			= site_url();
				$customer_posts['api_attach_posts'] 	= site_url()."/api/clients/posts/";

				//////////////////////////////////////////////////////////////			
				//partie API
				$key = Kidzou_Customer::getKey($post->ID);

				//actuellement $api_names ne sert à rien dans le code
				//C'est pour ouvrir la voie vers une généralisation de la gestion des API
				$api_names = Kidzou_API::getAPINames();
		 		
		 		//todo : c'est ici qu'on fait référence en dur à l'API excerpts
		 		//pour généraliser cette fonction, il faudrait boucler sur toutes les API 
		 		//il faudrait gérer dans les options la liste des API ouvertes puis les récupérer ici par un get_option()
				$open_apis = array();
				$open_apis[0] = 'excerpts';

				$quota = Kidzou_API::getQuotaByAPIName($key, $open_apis[0]); //$api_names[i]
				$usage = Kidzou_API::getCurrentUsage($key, $open_apis[0]); //$api_names[i]

				$customer_api = array();
				$customer_api['quota'] 	= array($open_apis[0] => $quota);
				$customer_api['usage']	= $usage;
				$customer_api['key']	= $key;
				$customer_api['api_base'] 			= site_url();
				$customer_api['api_save_quota'] 	= site_url()."/api/clients/quota/";

				//////////////////////////////////////////////////////////////			
				//partie Analytics
				$customer_ana = array();
				$customer_ana['is_analytics'] 		= Kidzou_Customer::isAnalyticsAuthorizedForCustomer($customer_id);
				$customer_ana['api_base'] 			= site_url();
				$customer_ana['api_save_analytics'] = site_url()."/api/clients/analytics/";

				wp_enqueue_script( 'kidzou-customer-posts-metabox', plugins_url( '/assets/js/kidzou-customer-posts-metabox.js', dirname(__FILE__) ), array( 'jquery', 'react-select', 'kidzou-react' ), Kidzou::VERSION, true);
				wp_enqueue_script( 'kidzou-customer-users-metabox', plugins_url( '/assets/js/kidzou-customer-users-metabox.js', dirname(__FILE__) ), array( 'jquery', 'react-select', 'kidzou-react' ), Kidzou::VERSION, true);
				wp_enqueue_script( 'kidzou-customer-api-metabox', 	plugins_url( '/assets/js/kidzou-customer-api-metabox.js', dirname(__FILE__) ), array( 'jquery', 'kidzou-react', 'react-inline-edit' ), Kidzou::VERSION, true);
				wp_enqueue_script( 'kidzou-customer-analytics-metabox', 	plugins_url( '/assets/js/kidzou-customer-analytics-metabox.js', dirname(__FILE__) ), array( 'jquery', 'kidzou-react' ), Kidzou::VERSION, true);
				
				wp_localize_script('kidzou-customer-posts-metabox', 'customer_posts_jsvars', 	$customer_posts);
				wp_localize_script('kidzou-customer-users-metabox', 'customer_users_jsvars', 	$users);
				wp_localize_script('kidzou-customer-api-metabox', 	'customer_api_jsvars', 		$customer_api);
				wp_localize_script('kidzou-customer-analytics-metabox', 'customer_analytics_jsvars', 	$customer_ana);
			}
		
			//sur les post on a besoin d'une meta client
			//selection de users et de posts sur l'écran customer
			if (in_array($screen->id , $this->screen_with_meta_client)) {
				wp_enqueue_script( 'kidzou-customer-metabox', plugins_url( '/assets/js/kidzou-customer-metabox.js', dirname(__FILE__) ), array( 'jquery', 'react-select', 'kidzou-react' ), Kidzou::VERSION, true);
				wp_localize_script('kidzou-customer-metabox', 'client_jsvars', $args);
			}

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
	 * Internal usage only, returrns an array of clients passed to JS scripts
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	private static function getClients() {

		$clients = array();
		$args = array(
				'post_type' => Kidzou_Customer::$post_type, 
				'order' => 'ASC', 
				'orderby' => 'title', 
				'posts_per_page' => -1,
			);

		$q = new WP_Query( $args );

		if (null!=$q)
		{
			$posts = $q->get_posts();

			foreach ($posts as $mypost) {
				$clients[] = array(
						"id" => $mypost->ID, 
						"name" => $mypost->post_title,
						"location" => Kidzou_Geoloc::get_post_location($mypost->ID)
					);
			}

			wp_reset_query();
		}
		return $clients;
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
		
		} elseif ( in_array($screen->id , $this->screen_with_meta_client) && Kidzou_Utils::current_user_can('can_edit_customer') ) { 

			//par sécu, les users qui sont contributeurs ne voient même pas la metabox de sélection du client
			add_meta_box('kz_client_metabox', 'Client', array($this, 'client_metabox'), $screen->id, 'normal', 'high'); 
		
		} 

	}

	/**
	 * Sélection / création d'un client depuis l'écran d'édition d'un post
	 *
	 * @author 
	 **/
	public function client_metabox()
	{ 
		echo '<input type="hidden" name="clientmeta_noncename" id="clientmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		echo '<div class="react-content"></div>';
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
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'analytics_metabox', 'analytics_metabox_nonce' );
		echo '<div class="react-content"></div>';
	}

	/**
	 * les posts rattachés au client dans l'écran customer
	 *
	 * @return void
	 * @author 
	 **/
	public function customer_posts_metabox()
	{
		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_posts_metabox', 'customer_posts_metabox_nonce' );
		echo '<div class="react-content"></div>';
	}

	/**
	 * Dans l'écran customer, on peut affecter des users au client
	 * cela aura pour effet que les users s'ils sont contributeurs et qu'ils créent du contenu, leur posts sont automatiquement affectés au client
	 *
	 * @return void
	 * @author 
	 **/
	public function customer_users_metabox()
	{	
		
		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_users_metabox', 'customer_users_metabox_nonce' );
		echo '<div class="react-content"></div>';

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
		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_apis_metabox', 'customer_apis_metabox_nonce' );
		echo '<div class="react-content"></div>';

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

		//pour les posts
		$this->save_client_meta($post_id);

		//et pour les clients
		$this->set_customer_users($post_id);
		$this->set_customer_posts($post_id);
		$this->set_customer_apis($post_id);
		
	}

	/**
	 * A l'enregistrement d'un post, on associé le client
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

		if ( !Kidzou_Utils::current_user_can('can_edit_customer') ) {

			//le user ne voit pas la meta client s'il n'est pas au moins auteur
			//dans ce cas on rattache le post au client du user

			$current_customers = Kidzou_Customer::getCustomersIDByUserID(); //c'est un tableau

			if (count($current_customers)>0)
			{
				// Kidzou_Utils::log(reset($current_customers), true);
				Kidzou_Utils::save_meta($post_id, array(
					Kidzou_Customer::$meta_customer => reset($current_customers) //on prend le premier client du user courant
					)
				); 
			}

			//pas la peine d'aller plus loin
			return $post_id;
		}

		// si le user voit la meta_client, on vérifie les data envoyées
		if ( !isset( $_POST['clientmeta_noncename'] ) || !wp_verify_nonce( $_POST['clientmeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		$key = Kidzou_Customer::$meta_customer;

		$events_meta[$key] 	= $_POST['customer_select'];

		//toujours s'assurer que si le client n'est pas positonné, la valeur 0 est enregistrée
		if (strlen($events_meta[$key])==0 || intval($events_meta[$key])<=0)
			$events_meta[$key] = 0;

		Kidzou_Utils::save_meta($post_id, $events_meta);
		
	}

	/**
	 * sauvegarde de la meta self::$meta_customer_analytics 
	 * qui indique si les users du client peuvent visualiser sur le front les analytics de leurs pages
	 *
	 * @return void
	 * @since customer-analytics
	 * @param $post_id int ID du customer 
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


		$is_analytics = false;
		if ( isset($_POST['kz_customer_analytics']) ) {
			$is_analytics = ($_POST['kz_customer_analytics']=='on');
		}

		Kidzou_Customer::set_analytics($post_id, $is_analytics);
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
		if ( !Kidzou_Utils::current_user_can('can_edit_customer') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		$meta = array();

		$customer_users = (isset($_POST['customer_users']) ? explode(",", $_POST['customer_users']) : array());

		Kidzou_Customer::set_users($post_id, $customer_users);

	}

	/**
	 * Permet d'associer une liste de posts à un customer identifié par $post_id
	 * 
	 * La liste des posts à associer est passée dans  $_POST['customer_posts'] comme un tableau de ID 
	 *
	 * @param  $post_id int identifiant du customer
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
		if ( !Kidzou_Utils::current_user_can('can_edit_customer') )
			return $post_id;

		$posts = (isset($_POST['customer_posts']) ? explode(",", $_POST['customer_posts']) : array());

		Kidzou_Customer::attach_posts($post_id, $posts);
		
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
		if ( !Kidzou_Utils::current_user_can('can_edit_customer') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		$key 	= Kidzou_Customer::getKey($post_id);
		$quota 	= $_POST['kz_quota'];

		// //todo : actuellement seule une API est gérée
		$api_names = array();
		$api_names[0] = 'excerpts';//$_POST['api_name_0'];

		Kidzou_Customer::set_api($post_id, $api_names, $key, $quota);
	}

	

}
