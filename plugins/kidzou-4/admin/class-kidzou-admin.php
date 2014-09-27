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
	 * les ecrans qui meritent qu'on y ajoute des meta
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $screen_with_meta = array('post', 'offres');

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

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		//scripts partagés
		add_action( 'admin_enqueue_scripts', array( Kidzou_Geo::get_instance() , 'enqueue_geo_scripts' ) );

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

		//affichage
		add_filter('default_hidden_meta_boxes', array($this,'hide_metaboxes'), 10, 2);
		add_action('wp_dashboard_setup', array($this,'remove_dashboard_widgets') );


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

	public function hide_metaboxes($hidden, $screen) {
       
        $hidden = array('slugdiv');
                
        return $hidden;
	}


	function remove_dashboard_widgets() {

		if (!current_user_can('manage_options')) {

			global $wp_meta_boxes;

			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);

			remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');

		}

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

		// if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
		// 	return;
		// }

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta)  ) {

			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Kidzou::VERSION );
		
			wp_enqueue_style( 'kidzou-place', plugins_url( 'assets/css/kidzou-edit-place.css', __FILE__ ) );
			wp_enqueue_style( 'placecomplete', plugins_url( 'assets/css/jquery.placecomplete.css', __FILE__ ) );
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', __FILE__ )  );

			//datepicker pour les events
			wp_enqueue_style( 'jquery-ui-custom', plugins_url( 'assets/css/jquery-ui-1.10.3.custom.min.css', __FILE__ ) );	

		} elseif (condition) {
			
			wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
			wp_enqueue_style( 'kidzou-admin', plugins_url( 'assets/css/kidzou-client.css', __FILE__ ) );

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

		// if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
		// 	return;
		// }

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta) ) {

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
			//selection des places dans Google Places
			wp_enqueue_script('placecomplete', plugins_url( 'assets/js/jquery.placecomplete.js', __FILE__ ),array('jquery-select2', 'google-maps'), '1.0', true);
			wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', __FILE__ ) ,array('jquery'), '1.0', true);
			wp_enqueue_script('kidzou-geo', plugins_url( '../assets/js/kidzou-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), '1.0', true);
			wp_enqueue_script('kidzou-place', plugins_url( 'assets/js/kidzou-place.js', __FILE__ ) ,array('jquery','ko-mapping'), '1.0', true);
			
			//gestion des events
			wp_enqueue_script('kidzou-event', plugins_url( 'assets/js/kidzou-event.js', __FILE__ ) ,array('jquery','ko-mapping', 'moment'), '1.0', true);
			wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
			wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-datepicker-fr', plugins_url( 'assets/js/jquery.ui.datepicker-fr.js', __FILE__ ), array('jquery-ui-datepicker'),'1.0', true);

		} elseif ( $screen->id == $this->plugin_screen_hook_suffix ) {

			//ecran de gestion des clients

			wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);

			wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
			wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);

			wp_enqueue_script('kidzou-client', plugins_url( 'assets/js/kidzou-client.js', __FILE__ ) , array("jquery","ko"), '0.1', true);

			wp_enqueue_script('jmasonry',		"http://cdnjs.cloudflare.com/ajax/libs/masonry/3.1.2/masonry.pkgd.js",	array('jquery'), '3.1.2', true);
			wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
			wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);

			wp_localize_script('kidzou-client', 'kidzou_jsvars', array(
					// 'api_publishEvent'		 		 => site_url().'/api/events/publishEvent/',
					// 'api_unpublishEvent'			 => site_url().'/api/events/unpublishEvent/',
					'api_getClients'				=> site_url()."/api/clients/getClients/",
					'api_deleteClient'				=> site_url().'/api/clients/deleteClient',
					'api_saveUsers' 				=> site_url().'/api/clients/saveUsers/',
					'api_saveClient'				=> site_url().'/api/clients/saveClient/',
					'api_getClientByID' 			=> site_url().'/api/clients/getClientByID/',
					'api_get_userinfo'			 	=> site_url().'/api/users/get_userinfo/',
					// 'api_get_fiche_by_slug' 		=> site_url().'/api/connections/get_fiche_by_slug/',
					'api_queryAttachableEvents'		=> site_url().'/api/clients/queryAttachableContents/',
					'api_attachToClient'			=> site_url().'/api/clients/attachToClient/',
					'api_detachFromClient' 			=> site_url().'/api/clients/detachFromClient/',
					'api_getContentsByClientID' 	=> site_url()."/api/clients/getContentsByClientID/",
					// 'api_publishRequests' => site_url()."/api/events/publishRequests/",

				)
			);

		}

	}


	public function posts_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta) ) { 

			add_meta_box('kz_client_metabox', 'Client', array($this, 'client_metabox'), $screen->id, 'normal', 'high');
			add_meta_box('kz_event_metabox', 'Evenement', array($this, 'event_metabox'), $screen->id, 'normal', 'high');
			add_meta_box('kz_place_metabox', 'Lieu', array($this, 'place_metabox'), $screen->id, 'normal', 'high');
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


		if (!current_user_can( 'manage_options' )) {

			$customer_id = Kidzou_Customer::getCustomerIDByAuthorID();

		} else {
			
			$customer_id = Kidzou_Customer::getCustomerIDByPostID();
		}

		$customer_name = Kidzou_Customer::getCustomerNameByCustomerID($customer_id);
		

			echo '
			<script>
				var clients = [];
				jQuery(document).ready(function() {

					jQuery.getJSON("'.site_url().'/api/clients/getClients/")
						.done(function (d) {
						if (d && d.clients) {
							for (var i = d.clients.length - 1; i >= 0; i--) {
								clients.push({id : d.clients[i].id, text: d.clients[i].name});
							};
						}

					});
					jQuery("#kz_event_customer").select2({

						placeholder: "Selectionnez un client",
						allowClear : true,
				        data : clients,
				        initSelection : function (element, callback) {
				        	var pieces = element.val().split(":");
				        	var data = {id: pieces[0], text: pieces[1]};
				        	//console.log("pieces[0]" + pieces[0]);
					        callback(data);
					    }
					});
			';
			//on desactive la boite de selection du client si le user est un client
			if (!current_user_can('manage_options')) {
				echo 'jQuery("#kz_event_customer").select2("enable", false);';
			}
			echo '
				});
			</script>';
		?>
		<div class="events_form">

			<?php

			//si le user n'est pas un "pro", on permet des fonctions d'administration supplémentaires
			// if (!current_user_can('pro')) {

			echo '
				<ul>
				<li>
					<label for="kz_event_customer">Nom du client:</label>
					<input type="hidden" name="kz_event_customer" id="kz_event_customer" value="' . $customer_id . ':'.$customer_name.'" style="width:80%" />
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
		global $wpdb;
		
		$checkbox = false;

		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		
		$start_date		= get_post_meta($post->ID, 'kz_event_start_date', TRUE);
		$end_date 		= get_post_meta($post->ID, 'kz_event_end_date', TRUE);

		echo '<script>
		jQuery(document).ready(function() {
			kidzouEventsModule.model.initDates("'.$start_date.'","'.$end_date.'");
		});
		</script>

		<div class="kz_form" id="event_form">';

			//si le user n'est pas un "pro", on permet des fonctions d'administration supplémentaires
			if (!current_user_can('pro')) {

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
			    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().start_date, datepickerOptions: { dateFormat: \'dd MM yy\' }" required />
			    	<input type="hidden" name="kz_event_start_date"  data-bind="value: eventData().formattedStartDate" />
			    	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
				</li>
				<li>
					<label for="end_date">Date de fin</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().end_date, datepickerOptions: { dateFormat: \'dd MM yy\' }" required />
					<input type="hidden" name="kz_event_end_date"  data-bind="value: eventData().formattedEndDate" />
					<em data-bind="if: eventData().eventDuration()!==\'\'">(<span data-bind="text: eventData().eventDuration"></span>)</em>
					<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
				</li>
			</ul>

		</div>';

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
		global $wpdb;
		
		$type = $post->post_type;

		// Noncename needed to verify where the data originated
		echo '<input type="hidden" name="placemeta_noncename" id="placemeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		
		// Get the location data if its already been entered
		$location_name 		= get_post_meta($post->ID, 'kz_' .$type .'_location_name', TRUE);
		$location_address 	= get_post_meta($post->ID, 'kz_' .$type .'_location_address', TRUE);
		$location_website 	= get_post_meta($post->ID, 'kz_' .$type .'_location_website', TRUE);
		$location_phone_number 	= get_post_meta($post->ID, 'kz_' .$type .'_location_phone_number', TRUE);
		$location_city 			= get_post_meta($post->ID, 'kz_' .$type .'_location_city', TRUE);
		$location_latitude 		= get_post_meta($post->ID, 'kz_' .$type .'_location_latitude', TRUE);
		$location_longitude 	= get_post_meta($post->ID, 'kz_' .$type .'_location_longitude', TRUE);

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
			<li><a href="#" data-bind="click: displayGooglePlaceForm">Revenir a la recherche Google</a></li>	
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
		$this->save_event_meta($post_id);
		$this->save_place_meta($post_id);
		$this->save_client_meta($post_id);
		$this->save_post_metropole($post_id);
		
	}

	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 * @author 
	 **/
	public function save_rewrite_meta($post_id) {

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

		
		$meta['rewrite_page'] 			= ($_POST['kz_rewrite_page']=='on');
			

		$this->save_meta($post_id, $events_meta, "kz_");
		
	}

	/**
	 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
	 *
	 * @return void
	 * @author 
	 **/
	public function save_event_meta($post_id)
	{

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['eventmeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		// Is the user allowed to edit the post or page?
		if ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;

		//formatter les dates avant de les sauvegarder 
		//input : 23 Février 2014
		//output : 2014-02-23 00:00:01 (start_date) ou 2014-02-23 23:59:59 (end_date)
		$events_meta['start_date'] 			= $_POST['kz_event_start_date'];
		$events_meta['end_date'] 				= $_POST['kz_event_end_date'];
		
		//cette metadonnée n'est pas mise à jour dans tous les cas
		//uniquement si le user est admin
		if ( current_user_can( 'manage_options' ) ) 
			$events_meta['featured'] 			= ($_POST['kz_event_featured']=='on' ? "A" : "B");
		else {
			if (get_post_meta($post_id, 'kz_event_featured', TRUE)!='')
				$events_meta['featured'] 			= get_post_meta($post_id, 'kz_event_featured', TRUE);
			else
				$events_meta['featured'] 		= "B";
		}
			

		$this->save_meta($post_id, $events_meta, "kz_event_");
	}

	/**
	 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
	 *
	 * @return void
	 * @author 
	 **/
	public function save_place_meta($post_id)
	{

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['placemeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		// Is the user allowed to edit the post or page?
		if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;

		$type = get_post_type($post_id);

		// OK, we're authenticated: we need to find and save the data
		// We'll put it into an array to make it easier to loop though.
		$events_meta['location_name'] 			= $_POST['kz_location_name'];
		$events_meta['location_address'] 		= $_POST['kz_location_address'];
		$events_meta['location_website'] 		= $_POST['kz_location_website'];
		$events_meta['location_phone_number'] 	= $_POST['kz_location_phone_number'];
		$events_meta['location_city'] 			= $_POST['kz_location_city'];
		$events_meta['location_latitude'] 		= $_POST['kz_location_latitude'];
		$events_meta['location_longitude'] 		= $_POST['kz_location_longitude'];

		$prefix = 'kz_' . $type . '_';

		$this->save_meta($post_id, $events_meta, $prefix);
		
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function save_client_meta ($post_id)
	{

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['clientmeta_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		$key = 'kz_event_customer';

		// Is the user allowed to edit the post or page?
		// if ( !current_user_can( 'edit_event', $post_id ) &&  !current_user_can( 'edit_post', $post_id ))
		// 	return $post_id;

		//si le user est un client, on reprend le client associé à l'auteur
		//en theorie il ne peut pas arriver jusqu'ici puisque le nonce n'est pas generé pour un "pro"
		if ( !current_user_can( 'manage_options' )) {
		
			$events_meta[$key] = Kidzou_Customer::getCustomerIDByAuthorID();;
		
		} else {
			// OK, we're authenticated: we need to find and save the data
			// We'll put it into an array to make it easier to loop though.
			$tmp_post = $_POST['kz_event_customer'];
			$tmp_arr = explode(":", $tmp_post );
			$events_meta[$key] 	= $tmp_arr[0];
		}

		//toujours s'assurer que si le client n'est pas positonné, la valeur 0 est enregistrée
		if (strlen($events_meta[$key])==0 || intval($events_meta[$key])<=0)
			$events_meta[$key] = 0;

		$this->save_meta($post_id, $events_meta);
		
	}

	/**
	 * fonction utilitaire
	 */
	protected function save_meta($post_id = 0, $arr = array(), $prefix = '') {

		if ($post_id==0)
			return;

		// Add values of $events_meta as custom fields
		foreach ($arr as $key => $value) { // Cycle through the $events_meta array!
			$pref_key = $prefix.$key; 
			// if( $post->post_type == 'revision' ) return; // Don't store custom data twice
			// $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
			$prev = get_post_meta($post_id, $pref_key, TRUE);
			// if ($pref_key=='kz_event_customer') echo $prev;
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
	 * @return void
	 * @author 
	 **/
	public function save_post_metropole($post_id)
	{
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	    
	    if ( current_user_can( 'manage_options', $post_id )) {

	    
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

	    global $kidzou_options;
	   	$metro_id = $kidzou_options['geo_default_metropole']; //init : ville par defaut

	    $user_id = get_current_user_id();

	    //la metropole est la metropole de rattachement du user
	    $metropoles = wp_get_object_terms( $user_id, 'ville', array('fields' => 'all') );

	    //quid si le user n'a pas de metropole de rattachement ? 

	    if (!empty($metropoles) && count($metropoles)>0) {
		    //bon finalement on prend la premiere metropole
		    $ametro = $metropoles[0];
		    $metro_id = $ametro->term_id;
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
