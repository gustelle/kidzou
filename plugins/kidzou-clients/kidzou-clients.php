<?php
/*
Plugin Name: Kidzou Client
Plugin URI: http://www.kidzou.fr
Description: Gestion de clients dans Kidzou
Version: 2014.03.29
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

global $kz_clients_db_version;
$kz_clients_db_version = "1.1";

define( 'KZ_CLIENTS_TABLE_NAME' , 'clients' );
define( 'KZ_CLIENTS_USERS_TABLE_NAME' , 'clients_users' );

//When the plugin is activated, install the database
register_activation_hook( __FILE__ , 'kz_clients_install' );

//When the plugin is loaded, check for DB updates and first run
add_action( 'plugins_loaded' , 'kz_update_clients_db_check' );
add_action( 'plugins_loaded' , 'kz_first_run_clients_check' );

//API
function add_kz_clients_controller($controllers) {
  $controllers[] = 'Clients';
  return $controllers;
}

function set_clients_controller_path() {
  return plugin_dir_path( __FILE__ ) ."/api/clients.php";
}
add_filter('json_api_controllers', 'add_kz_clients_controller');
add_filter('json_api_clients_controller_path',  'set_clients_controller_path');


function kz_clients() 
{
	?>
	<h1>Gestion des Clients</h1>
	<?php

	wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
	wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);

	wp_enqueue_script('kidzou-client', WP_PLUGIN_URL.'/kidzou-clients/js/kidzou-client.js'	,array("jquery","ko"), '0.1', true);
	wp_enqueue_style( 'kidzou-admin', WP_PLUGIN_URL.'/kidzou-clients/css/kidzou-client.css' );

	wp_enqueue_script('jmasonry',		"http://cdnjs.cloudflare.com/ajax/libs/masonry/3.1.2/masonry.pkgd.js",	array('jquery'), '3.1.2', true);
	wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
	wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);

	wp_localize_script('kidzou-client', 'kidzou_jsvars', array(
			'api_publishEvent'		 		 => site_url().'/api/events/publishEvent/',
			'api_unpublishEvent'			 => site_url().'/api/events/unpublishEvent/',
			'api_getClients'				=> site_url()."/api/clients/getClients/",
			'api_deleteClient'				=> site_url().'/api/clients/deleteClient',
			'api_saveUsers' 				=> site_url().'/api/clients/saveUsers/',
			'api_saveClient'				=> site_url().'/api/clients/saveClient/',
			'api_getClientByID' 			=> site_url().'/api/clients/getClientByID/',
			'api_get_userinfo'			 	=> site_url().'/api/users/get_userinfo/',
			'api_get_fiche_by_slug' 		=> site_url().'/api/connections/get_fiche_by_slug/',
			'api_queryAttachableEvents'		=> site_url().'/api/clients/queryAttachableContents/',
			'api_attachToClient'			=> site_url().'/api/clients/attachToClient/',
			'api_detachFromClient' 			=> site_url().'/api/clients/detachFromClient/',
			'api_getContentsByClientID' 		=> site_url()."/api/clients/getContentsByClientID/",
			// 'api_publishRequests' => site_url()."/api/events/publishRequests/",

		)
	);

	require_once (plugin_dir_path( __FILE__ ) . '/kidzou-admin_clients.php'); 
}

/**
 * Creates the db schema
 *
 * @global type $wpdb
 * @global string $kz_db_version
 *
 * @return void
 */
function kz_clients_install(){
	global $wpdb;
	global $kz_clients_db_version;
	$table_clients = $wpdb->prefix . KZ_CLIENTS_TABLE_NAME;
	$table_clients_users = $wpdb->prefix . KZ_CLIENTS_USERS_TABLE_NAME;

	$sql = "CREATE TABLE $table_clients (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        connections_id bigint(20) DEFAULT 0 NOT NULL,
        UNIQUE KEY id (id)
       )CHARSET=utf8;";

	$sql .= "CREATE TABLE $table_clients_users (
        customer_id mediumint(9) NOT NULL DEFAULT 0,
        user_id bigint(20) NOT NULL DEFAULT 0
       )CHARSET=utf8;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	// $wpdb->show_errors();
	$ret = dbDelta( $sql );
	// $wpdb->print_error();
	update_site_option( 'kz_clients_db_version' , $kz_clients_db_version );
}

/**
 * Checks if we need to update the db schema
 *
 * If the site_option value doesn't match the version defined at the top of
 * this file, the install routine is run.
 *
 * @global string $kz_db_version
 * @return void
 */
function kz_update_clients_db_check(){
	global $kz_clients_db_version;
	if ( get_site_option( 'kz_clients_db_version' ) != $kz_clients_db_version ) {
		kz_clients_install();
	}
}

/**
 * Checks for first run
 *
 * @return void
 */
function kz_first_run_clients_check(){
	if ( get_site_option( 'kz_clients_first_run' , 'fasly' ) === 'fasly' ) {
		//Set site option so show we've run this plugin at least once.
		update_site_option( 'kz_clients_first_run' , 'woot' );
	}
}


/* Add meta boxes on the 'add_meta_boxes' hook. */
add_action( 'add_meta_boxes', 'kz_client_meta_boxes' );

/* Save post meta on the 'save_post' hook. */
add_action( 'save_post', 'kz_meta_client_save' );
add_action( 'save_post_event', 'kz_meta_client_save' );

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_client_meta_boxes ()
{
	add_meta_box('kz_client', 'Client', 'kz_client', 'post', 'normal', 'high');
	add_meta_box('kz_client', 'Client', 'kz_client', 'event', 'normal', 'high');
	add_meta_box('kz_client', 'Client', 'kz_client', 'offres', 'normal', 'high');
	add_meta_box('kz_client', 'Client', 'kz_client', 'concours', 'normal', 'high');
}

/**
 * retourne le client d'un user
 *
 * @return void
 * @author 
 **/
function kz_customer_per_user()
{
	global $wpdb;

	$table_clients_users = $wpdb->prefix . "clients_users";
	$table_clients 		 = $wpdb->prefix . "clients";

	$user_id 	= get_current_user_id();

	$customer 	= $wpdb->get_results("SELECT c.id, c.name FROM $table_clients_users AS u, $table_clients AS c WHERE u.user_id=$user_id AND u.customer_id=c.id", ARRAY_A);

	return array(
			"id" => $customer[0]["id"],
			"name" => $customer[0]["name"]
 		);
}

/**
 * retourne le client d'un post
 *
 * @return void
 * @author 
 **/
function kz_customer_per_post()
{
	global $wpdb;
	global $post; 

	$table_clients 		 = $wpdb->prefix . "clients";

	$customer_id 	= get_post_meta($post->ID, 'kz_event_customer', TRUE);
		
	if ($customer_id!='' && intval($customer_id)>0) {
		$customer 	= $wpdb->get_results("SELECT c.id, c.name FROM $table_clients AS c WHERE c.id=$customer_id", ARRAY_A);

		return array(
			"id" => $customer[0]["id"],
			"name" => $customer[0]["name"]
 		);
	}

	return array(
			"id" => 0,
			"name" => ""
		);
	
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_client ()
{

	global $post; 

	echo '<input type="hidden" name="clientmeta_noncename" id="clientmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';


	if (current_user_can( 'pro' )) {

		$customer = kz_customer_per_user();

	} else {
		

		$customer = kz_customer_per_post();
	}
	

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
		if (current_user_can('pro')) {
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
				<input type="hidden" name="kz_event_customer" id="kz_event_customer" value="' . $customer["id"] . ':'.$customer["name"].'" style="width:80%" />
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
function kz_meta_client_save ($post_id)
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
	if ( current_user_can( 'pro' )) {
		
		$customer = kz_customer_per_user();
		$events_meta[$key] = $customer["id"];
	
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


	// Add values of $events_meta as custom fields
	foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
		// if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		// $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		$prev = get_post_meta($post_id, $key, TRUE);
		if(strlen($prev)>0) { // If the custom field already has a value
			update_post_meta($post_id, $key, $value, $prev);
		} else { // If the custom field doesn't have a value
			// if ($prev=='') delete_post_meta($post_id, $key);
			add_post_meta($post_id, $key, $value, TRUE);
		}
		// if(!$value) delete_post_meta($post_id, $key); // Delete if blank
	}
	
}

add_action( 'admin_enqueue_scripts', 'kz_enqueue_clients_scripts' );

function kz_enqueue_clients_scripts() {

		//requis par placecomplete
		wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
		wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
		wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
		//selection des places dans Google Places
	
}

/**
 * retourne la ligne client en provenance de la BDD
 *
 * @return void
 * @author 
 **/
function kz_client_by_id($id)
{
	if ($id==null || !intval($id)>0)
		return null;

	global $wpdb;
	$table_clients = $wpdb->prefix . "clients";
	$result = $wpdb->get_row("SELECT * FROM $table_clients WHERE id = $id LIMIT 1", ARRAY_A);

	return $result;
}

/**
 * liste des posts pour un evenement
 *
 * @return void
 * @author 
 **/
function kz_customer_related_posts() {

	require_once (plugin_dir_path( __FILE__ ) . '/includes/posts-list.php');
}

?>