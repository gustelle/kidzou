<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Customer_Metaboxes', 'get_instance' ) );

/**
 * Cette classe gère les Metaboxes utiles à la gestion des données Customer dans les écrans d'admin
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Customer_Metaboxes {

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

		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		
	}

	// /**
	//  * Recherche de la metropole la plus proche du lieu de rattachement
	//  *
	//  * @return void
	//  * @author 
	//  **/
	// public function enqueue_geo_scripts()
	// {

	// 	$screen = get_current_screen(); 
	// 	$events = Kidzou_Events_Metaboxes::get_instance();
	// 	$customer = Kidzou_Customer_Metaboxes::get_instance();

	// 	if (in_array($screen->id , $events->screen_with_meta_event) || in_array($screen->id, $customer->customer_screen)) {

	// 		wp_enqueue_script('kidzou-admin-geo', plugins_url( '../assets/js/kidzou-admin-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

	// 		$villes = Kidzou_Metropole::get_metropoles();

	// 		$key = Kidzou_Utils::get_option("geo_mapquest_key",'Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6');
	  
	// 		$args = array(
	// 					// 'geo_activate'				=> (bool)Kidzou_Utils::get_option('geo_activate',false), //par defaut non
	// 					'geo_mapquest_key'			=> $key, 
	// 					'geo_mapquest_reverse_url'	=> "http://open.mapquestapi.com/geocoding/v1/reverse",
	// 					'geo_mapquest_address_url'	=> "http://open.mapquestapi.com/geocoding/v1/address",
	// 					// 'geo_cookie_name'			=> $locator::COOKIE_METRO,
	// 					'geo_possible_metropoles'	=> $villes ,
	// 					// 'geo_coords'				=> $locator::COOKIE_COORDS,
	// 				);

	// 	    wp_localize_script(  'kidzou-admin-geo', 'kidzou_admin_geo_jsvars', $args );
	// 	}
		
	// }

	/**
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		$screen = get_current_screen(); 

		// if ( in_array($screen->id, $this->customer_screen) ) { 

		// 	// requis par placecomplete
		// 	wp_enqueue_script('selectize', 	"https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.1/js/standalone/selectize.js",array(), '0.12.1', true);
		// 	wp_enqueue_style( 'selectize', 	"https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.1/css/selectize.default.min.css" );

		// 	// ecran de gestion des clients
		// 	wp_enqueue_script( 'kidzou-customer-script', plugins_url( 'assets/js/kidzou-customer.js', dirname(__FILE__) ), array( 'jquery' ), Kidzou::VERSION );
		// 	wp_localize_script('kidzou-customer-script', 'client_jsvars', array(
		// 		'api_get_userinfo'			 	=> site_url().'/api/search/getUsersBy/',
		// 		'api_queryAttachablePosts'		=> site_url().'/api/clients/queryAttachablePosts/',
		// 		)
		// 	);


		// } else
		if ( in_array($screen->id , $this->screen_with_meta_client) || in_array($screen->id, $this->customer_screen) ) { 

			wp_enqueue_script('selectize', 	"https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.1/js/standalone/selectize.js",array(), '0.12.1', true);
			wp_enqueue_style( 'selectize', 	"https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.1/css/selectize.default.min.css" );
			
			//sur les post on a besoin d'une meta client
			wp_enqueue_script( 'kidzou-customer-script', plugins_url( 'assets/js/kidzou-customer.js', dirname(__FILE__) ), array( 'jquery' ), Kidzou::VERSION );
			wp_localize_script('kidzou-customer-script', 'client_jsvars', array(
				'api_getCustomerPlace'			=> site_url()."/api/clients/getCustomerPlace",
				'api_get_userinfo'			 	=> site_url().'/api/search/getUsersBy/',
				'api_queryAttachablePosts'		=> site_url().'/api/clients/queryAttachablePosts/',
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
		
		} elseif ( in_array($screen->id , $this->screen_with_meta_client) ) { 

			if (Kidzou_Utils::current_user_is('author')) {
				//par sécu, les users qui sont contributeurs ne voient même pas la metabox de sélection du client
				add_meta_box('kz_client_metabox', 'Client', array($this, 'client_metabox'), $screen->id, 'normal', 'high'); 
			}
		
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
		global $post; 

		echo '<input type="hidden" name="clientmeta_noncename" id="clientmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		
		$customer_id = Kidzou_Customer::getCustomerIDByPostID();
	
		if (is_wp_error($customer_id))
			$customer_id=0;

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
		

		//pre-selection s'il n'y en a qu'un
		if (count($clients)==1) {
			$customer_id = $clients[0]['id'];
		}

		echo sprintf('
				<script>
					jQuery(document).ready(function() {
						jQuery("select[name=\'customer_select\']").selectize({
							mode: "single",
							options : %1$s,
							valueField: \'id\',
							labelField: \'name\',
							sortField: [
								{field: \'name\', direction: \'asc\'},
							],
							searchField : [
								\'name\'
							],
							render: {
								item: function(item, escape) {
									return \'<div>\' + escape(item.name) + \'</div>\';
								},
								option: function(item, escape) {
									if (typeof item.location==\'undefined\' || typeof item.location.location_address==\'undefined\' || item.location.location_address==\'\') 
										return \'<div>\' + escape(item.name) + \'</div>\';
									return \'<div>\' + escape(item.name) + \'<br/><em style="font-size:smaller">\' + escape(item.location.location_address) + \', \' + escape(item.location.location_city) + \'</em></div>\';
								}
							},
							onItemAdd : function(value, item) {

								if (window.kidzouPlaceModule) {

									jQuery.get(client_jsvars.api_getCustomerPlace, { 
					   					id 	: value
									}).done(function(data) {
										// console.log(data);
										if (data.status===\'ok\' && data.location.location_name!=\'\') {
											kidzouPlaceModule.model.proposePlace(\'customer\', {
													name 		: data.location.location_name,
								        			address 	: data.location.location_address,
								        			website 	: data.location.location_web, //website
								        			phone		: data.location.location_tel, //phone
								        			city 		: data.location.location_city,
								        			latitude	: data.location.location_latitude,
								        			longitude 	: data.location.location_longitude,
								        			opening_hours : \'\' //opening hours
												});
										} 
									});

								}
							
							}
						});
					});
				</script>
			', json_encode($clients));
	
		//le post a déjà un customer 
		if ($customer_id>0) {
			echo '
				<script>
					jQuery(document).ready(function() {
						//Charger la select avec le client du post
						//ne pas déclencher le onItemAdd...
						jQuery("select[name=\'customer_select\']").selectize()[0].selectize.addItem('.$customer_id.', true);
					});
				</script>
				';
		}
		

		echo '
			<div class="kz_form hide" id="customer_form">
				<ul>
				<!-- selectize ne fonctionne que si l\'element est dans le DOM , il faut donc utiliser un bind "visible" et non "if" -->
				<li data-bind="visible: !editMode()">
					<label for="customer_select">Nom du client:</label>
					<select name="customer_select"></select>
					<br/><br/>
					<em><a href="#" data-bind="click: displayEditCustomerForm">Cr&eacute;er un nouveau client</a></em>
				</li>
				<!-- ko if: editMode() -->
				<li>
					<label for="customer_input">Nom du client:</label> 
					<input type="text" name="customer_input" placeholder="Le nom du client" data-bind="value: customerName" required>
				</li>
				<li>
					<button data-bind="click: displayCustomerSelect" class="button button-large">Choisir un client existant</button>
					<button data-bind="click: createCustomer, html: creationStatus, disable: creationFailure" class="button button-primary button-large"></button>
				</li>
				<!-- /ko -->
				</ul>
			</div>';
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
	 * les posts rattachés au client
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
		$init_options = '';
		foreach ($posts as $init_post){
		    $init_options .= '<option value="'.$init_post->ID.'" selected>'.$init_post->post_title.'</option>';
		}

		// Kidzou_Utils::log(array('init_posts'=>$init_posts),true);

		wp_reset_query();

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_posts_metabox', 'customer_posts_metabox_nonce' );

		$output = sprintf('
				<script>
					jQuery(document).ready(function() {

						jQuery("#customer_posts").selectize({
						    options : [],
						    create: false,
						    hideSelected : true,
						    valueField: \'id\',
						    labelField: \'title\',
						    searchField: \'title\',
						    delimiter: \',\',
						    plugins: [\'remove_button\'],
						    render: {
						    	item: function(item, escape) {
						            return \'<div><span class="name">\' + escape(item.title) + \'</span></div>\';
						        },
						        option: function(item, escape) {
						            return 	\'<div><span class="title"><span class="name">\' + escape(item.title) +
						            		 \'</span></span></div>\';
						        }
						    },
						    load: function(query, callback) {
						        if (!query.length) return callback();
						        jQuery.ajax({
						            url: client_jsvars.api_queryAttachablePosts ,
						            data: {
						                term: query,
						            },
						            error: function() {
						                callback();
						            },
						            success: function(data) { 
						            	console.debug(data);
						                callback(data.posts);
						            }
						        });
						    }
						});
					});
				</script>
				<label for="customer_posts[]" style="display:block;">
					Articles appartenant au client :
				</label>
				<br/>
				<select multiple="multiple" name="customer_posts[]" id="customer_posts" placeholder="rechercher par titre..." style="width:80%;">%1$s</select>
					',
				$init_options
			);

		echo $output;	
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
		
		global $post;

		$post_id = $post->ID; //echo $post_id;

		$user_query = new WP_User_Query( array( 
			'meta_key' => Kidzou_Customer::$meta_customer, 
			'meta_value' => $post_id , 
			'fields' => array('ID', 'display_name', 'user_email') 
			) 
		);
		$main_users = '';
		if ( !empty($user_query->results) ) {
			foreach ($user_query->results as $main) {
				$main_users .= '<option value="'.$main->ID.'" data-data=\''.json_encode($main).'\' selected>'.$main->display_name.'</option>';
			}
		}


		// Noncename needed to verify where the data originated
		wp_nonce_field( 'customer_users_metabox', 'customer_users_metabox_nonce' );

		$output = sprintf('
				<script>
					jQuery(document).ready(function() {

						jQuery("#customer_users").selectize({
						    options : [],
						    hideSelected : true,
						    create: false,
						    valueField: \'ID\',
						    labelField: \'display_name\',
						    searchField: [\'display_name\',\'user_email\'],
						    delimiter: \',\',
						    plugins: [\'remove_button\'],
						    render: {
						    	item: function(item, escape) { 
						            return \'<div><span class="name">\' + escape(item.display_name) + \'</span><span class="email">\' + escape(item.user_email) + \'</span></div>\';
						        },
						        option: function(item, escape) {
						            return 	\'<div><span class="label">\' + escape(item.display_name) + \'</span><span class="caption">\' + escape(item.user_email) + \'</span></div>\';
						        }
						    },
						    load: function(query, callback) {
						        if (!query.length) return callback();
						        jQuery.ajax({
						            url: client_jsvars.api_get_userinfo ,
						            data: {
						                term: query,
						            },
						            error: function() {
						                callback();
						            },
						            success: function(data) {
						            	callback(data.status.map(function(item) {
										    return {
										        ID: item.data.ID,
										        display_name : item.data.display_name,
										        user_email : item.data.user_email
										    };
										}));
						            }
						        });
						    }
						});
					});
				</script>
				<label for="customer_users[]" style="display:block;">
					Utilisateurs autoris&eacute;s &agrave; saisir des contenus<br/>
					<strong>La recherche se fait par login ou email</strong>
				</label>
				<br/>
				<select multiple="multiple" name="customer_users[]" id="customer_users" class="contacts" placeholder="rechercher par email ou login..." style="width:80%;">%1$s</select>
			',
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
		// Kidzou_Utils::log(array('api_names' => $api_names),true);
 		
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

		if ( !Kidzou_Utils::current_user_is('author') ) {

			//le user ne voit pas la meta client s'il n'est pas au moins auteur
			//dans ce cas on rattache le post au client du user

			$current_user_customers = Kidzou_Customer::getCustomersIDByUserID(); //c'est un tableau

			if (count($current_user_customers)>0)
			{
				Kidzou_Admin::save_meta($post_id, array(
					Kidzou_Customer::$meta_customer => $current_user_customers[0] //on prend le premier client du user courant
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

		// $meta = array();

		// if ( !isset($_POST[Kidzou_Customer::$meta_customer_analytics]) )
		// 	$meta[Kidzou_Customer::$meta_customer_analytics] = false;
		// else
		// 	$meta[Kidzou_Customer::$meta_customer_analytics] = ($_POST[Kidzou_Customer::$meta_customer_analytics]=='on');
		$is_analytics = false;
		if ( isset($_POST[Kidzou_Customer::$meta_customer_analytics]) ) {
			$is_analytics = ($_POST[Kidzou_Customer::$meta_customer_analytics]=='on');
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
		if ( !Kidzou_Utils::current_user_is('author') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		// $main = array();
		$meta = array();

		$customer_users = (isset($_POST['customer_users']) ? $_POST['customer_users'] : array());

		// Kidzou_Utils::log(array("_POST"=>$_POST ), true);

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
		foreach ($customer_users as $a_user) {

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

				Kidzou_Utils::log( 'Boucle secondaire, User ' . $a_user. ', provient de la requete ? '.in_array($a_user, $customer_users) );

				//l'utilisateur n'a pas été repassé dans la requete
				//il n'est pas donc plus attaché au client
				if (!in_array($a_user, $customer_users)) {

					$u = new WP_User( $a_user );

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
		if ( !Kidzou_Utils::current_user_is('author') )
			return $post_id;

		$posts = (isset($_POST['customer_posts']) ? $_POST['customer_posts'] : array());

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
		if ( !Kidzou_Utils::current_user_is('author') )
			return $post_id;

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.

		$key = $_POST['customer_api_key'];
		$quota = $_POST['customer_api_quota'];

		// //todo : actuellement seule une API est gérée
		$api_names = array();
		$api_names[0] = $_POST['api_name_0'];

		Kidzou_Customer::set_api($post_id, $api_names, $key, $quota);
	}

	

}
