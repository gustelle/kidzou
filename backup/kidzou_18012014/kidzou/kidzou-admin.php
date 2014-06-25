<?php

global $kz_db_version;
$kz_db_version = "0.9";

define( 'KZ_CLIENTS_TABLE_NAME' , 'clients' );
define( 'KZ_CLIENTS_USERS_TABLE_NAME' , 'clients_users' );

//When the plugin is activated, install the database
register_activation_hook( __FILE__ , 'kz_plugin_install' );

//When the plugin is loaded, check for DB updates and first run
add_action( 'plugins_loaded' , 'kz_update_db_check' );
add_action( 'plugins_loaded' , 'kz_first_run_check' );

add_action('admin_menu', 'kz_menu');

function kz_menu() {
  	add_menu_page('Kidzou', 'Kidzou', 'manage_options', 'kidzou', 'main_screen',plugins_url('kidzou/images/kidzou_16.png'));

  	//Gestion des configurations
	add_submenu_page( 'kidzou',
		              'Mises &agrave; jour' ,
		              'Mises &agrave; jour' ,
		              'manage_options' ,
		              'kidzou',
		              'main_screen'
		            );

	//Gestion des configurations
	add_submenu_page( 'kidzou',
		              'R&eacute;glages' ,
		              'R&eacute;glages' ,
		              'manage_options' ,
		              'options',
		              'kz_options'
		            );

	//Gestion des clients
	add_submenu_page( 'kidzou',
		              'Clients' ,
		              'Clients' ,
		              'manage_options' ,
		              'clients',
		              'kz_clients'
		            );

}

function main_screen () 
{

	$deploy = 0;

	if ($_POST['submit']) {

		$deploy = trim($_POST['deploy']);
		if($deploy !=1)	$deploy = 0;

		if ($deploy>0)
		{
			kz_override_theme();
			kz_override_connections();
			kz_override_nextend_fb();
			kz_override_nextend_google();
			kz_override_rse();
			kz_override_supercache();
			kz_install_roles();
		}
	}
?>
	<div class="wrap">
	 <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>" >

	 	<h2>Mise &agrave; jour du site</h2>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $deploy, 1 ); ?>  id="deploy" name="deploy">
			<span style="padding-left:5px;">Mettre &agrave; jour le site</span>
	 	</p>

		 <input name="submit" id="submit" value="Mettre &agrave; jour" type="submit" class="button-primary">

	</form>
</div>
<?php

}

function kz_clients() 
{
	?>
	<h1>Gestion des Clients</h1>
	<?php

	wp_enqueue_script('jquery-select2', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
	wp_enqueue_script('jquery-select2-locale', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
	wp_enqueue_style( 'jquery-select2', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
	
	wp_enqueue_script('vex',	 	WP_PLUGIN_URL.'/kidzou/js/vex.combined.min.js',array('jquery'), '1.3.3', true);
	wp_enqueue_style( 'vex', 	 	WP_PLUGIN_URL.'/kidzou/css/vex.css' );
	wp_enqueue_style( 'vex-theme', 	WP_PLUGIN_URL.'/kidzou/css/vex-theme-default.css' );

	wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
	wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
	wp_enqueue_script('kidzou-client', WP_PLUGIN_URL.'/kidzou/js/kidzou-client.js'	,array("jquery","ko"), '0.1', true);
	wp_enqueue_style( 'kidzou-admin', WP_PLUGIN_URL.'/kidzou/css/kidzou-admin.css' );

	//wp_enqueue_script('jmasonry',		"http://cdnjs.cloudflare.com/ajax/libs/masonry/3.1.2/masonry.pkgd.js",	array('jquery'), '3.1.2', true);
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
			'api_get_userinfo' => site_url().'/api/users/get_userinfo/',
			'api_get_fiche_by_slug' => site_url().'/api/connections/get_fiche_by_slug/',
			'api_queryAttachableEvents' => site_url().'/api/events/queryAttachableEvents/',
			'api_attachToClient'=> site_url().'/api/events/attachToClient/',
			'api_detachFromClient' => site_url().'/api/events/detachFromClient/',
			'api_getEventsByClientID' => site_url()."/api/clients/getEventsByClientID/",
			'api_publishRequests' => site_url()."/api/events/publishRequests/",

		)
	);

	require_once (plugin_dir_path( __FILE__ ) . '/kidzou-admin_clients.php'); 
}

function kz_options()
{

	//lien du fond d'écran
	$background_link 		 = get_option("kz_background_link");

	//afficher le paneau d'inscription à la newsletter à l'ouverture du site?
	$isNewsletterAutoDisplay = get_option("kz_newsletter_auto_display");

	//activation d'un worker dans le browser pour stocker en local les evenements
	$isActivateDatasync 	 = get_option("kz_activate_datasync");

	//les votes sont-ils synchronisés par un worker en background
	$isActivateSyncvotes 	 = get_option("kz_activate_syncvotes"); 

	//affichage des logs dans la console
	$isDebugMode 			 = get_option("kz_debug_mode");


	$flush_eventscache = 0;

	if ($_POST['submit'])
	{
		$url = trim($_POST['background_link']);
		if (filter_var($url, FILTER_VALIDATE_URL))	
			$background_link = $url;
		else
			$background_link = "";

		$isNewsletterAutoDisplay = trim($_POST['newsletter-auto-display']);
		if($isNewsletterAutoDisplay !=1)	$isNewsletterAutoDisplay = 0;

		$isActivateDatasync = trim($_POST['activate-data-sync']);
		if($isActivateDatasync !=1)	$isActivateDatasync = 0;

		$isActivateContentTracking = trim($_POST['content-tracking']);
		if($isActivateContentTracking !=1)	$isActivateContentTracking = 0;

		$isActivateSyncvotes = trim($_POST['activate-sync-votes']);
		if($isActivateSyncvotes !=1)	$isActivateSyncvotes = 0;

		$isDebugMode = trim($_POST['debug-mode']);
		if($isDebugMode !=1)	$isDebugMode = 0;


		$flush_eventscache = trim($_POST['flush_eventscache']);
		if($flush_eventscache !=1)	$flush_eventscache = 0;

		if ( get_option( "kz_background_link" ) != $background_link )
		    update_option( "kz_background_link", $background_link );
		else
		    add_option( "kz_background_link", $background_link );

		if ( get_option( "kz_newsletter_auto_display" ) != $isNewsletterAutoDisplay )
		    update_option( "kz_newsletter_auto_display", $isNewsletterAutoDisplay );
		else
		    add_option( "kz_newsletter_auto_display", $isNewsletterAutoDisplay );

		if ( get_option( "kz_activate_datasync" ) != $isActivateDatasync )
		    update_option( "kz_activate_datasync", $isActivateDatasync );
		else
		    add_option( "kz_activate_datasync", $isActivateDatasync );

		if ( get_option( "kz_activate_content_tracking" ) != $isActivateContentTracking )
		    update_option( "kz_activate_content_tracking", $isActivateContentTracking );
		else
		    add_option( "kz_activate_content_tracking", $isActivateContentTracking );

		if ( get_option( "kz_debug_mode" ) != $isDebugMode )
		    update_option( "kz_debug_mode", $isDebugMode );
		else
		    add_option( "kz_debug_mode", $isDebugMode );

		if ( get_option( "kz_activate_syncvotes" ) != $isActivateSyncvotes )
		    update_option( "kz_activate_syncvotes", $isActivateSyncvotes );
		else
		    add_option( "kz_activate_syncvotes", $isActivateSyncvotes );

		if ($flush_eventscache>0)
			kz_flushJSONCache();
	    	
	}

?>
<div class="wrap">
	 <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>" >

	 	<h2>Gestion des &eacute;v&egrave;nements</h2>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $flush_eventscache, 1 ); ?>  id="flush_eventscache" name="flush_eventscache">
			<span style="padding-left:5px;">Suppression du cache des &eacute;v&egrave;nements</span>
	 	</p>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $isActivateDatasync, 1 ); ?>  id="activate-data-sync" name="activate-data-sync">
	 		<span style="padding-left:5px;">Cacher les contenus des fiches evenement dans le navigateur (am&eacute;liore les perfs)</span>
	 	</p>

	 	<h2>Newsletter</h2>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $isNewsletterAutoDisplay, 1 ); ?>  id="newsletter-auto-display" name="newsletter-auto-display">
	 		<span style="padding-left:5px;">Afficher le paneau d&apos;inscription &agrave; la Newsletter au chargement de la page</span>
	 	</p>

	 	<h2>Autre</h2>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $isDebugMode, 1 ); ?>  id="debug-mode" name="debug-mode">
	 		<span style="padding-left:5px;">Mode debug (active les messages dans les console Javascript)</span>
	 	</p>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $isActivateContentTracking, 1 ); ?>  id="content-tracking" name="content-tracking">
	 		<span style="padding-left:5px;">Tracking avanc&eacute; du comportement de lecture des utilisateurs (temps de scroll)</span>
	 	</p>
	 	<p>
	 		<input type="checkbox" value="1" <?php checked( $isActivateSyncvotes, 1 ); ?>  id="activate-sync-votes" name="activate-sync-votes">
	 		<span style="padding-left:5px;">Rafraichissement des recommandations d&apos;un article par un Worker du navigateur (exp&eacute;rimental)</span>
	 	</p>
	 	<p>
	 		<input type="text" value="<?php echo $background_link; ?>" id="background_link" name="background_link">
	 		<span style="padding-left:5px;">Lien du fond d&apos;&eacute;cran</span>
	 	</p>

		 <input name="submit" id="submit" value="Mettre &agrave; jour" type="submit" class="button-primary">

	</form>
</div>
<?php
}

/**
 * surcharge specifique pour cacher les flux JSON (marquage JSON par <json> en fin de flux)
 *
 * @return void
 * @author 
 **/
function kz_flushJSONCache()
{

	if ( function_exists( 'flushJSONCache' ))  
	{
		flushJSONCache();
		echo "<div id='message' class='updated'><p>Cache n&eacute;ttoy&eacute;</p></div>";
	}
	else
	{
		echo "<div id='message' class='error'><p>Erreur lors de la suppression du cache</p></div>";
	}
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_install_roles()
{

	//remove_role('events_contributor');
	//remove_role('events_editor');
	//remove_role('client_manager');
	
	//gestionnaire client
	//add_role( 'client_manager', 'Responsable client', array( 
	//	'edit_others_events' => true,
	//	'read' => true
	//	) 
	//); 

	//$role_admin = get_role( 'administrator' );
	//$role_admin->remove_cap( 'edit_others_events' ); 

}


/**
 * surcharge specifique pour cacher les flux JSON (marquage JSON par <json> en fin de flux)
 *
 * @return void
 * @author 
 **/
function kz_override_supercache()
{

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/wp-super-cache", plugin_dir_path( __FILE__ )."../wp-super-cache");

	echo "<div id='message' class='updated'><p>WP Super cache Effectu&eacute;</p></div>";
}


/**
* appele quand on accede à l admin de wordpress
* donc les modifications ne sont pas mises à jour tant que admin n'est pas accede
* cela evite des soucis de performance si la fonction override_theme doit etre appelee a chaque request
*/
function kz_override_theme() {

	$out = "<ul>";

	recurse_copy( plugin_dir_path( __FILE__ )."themes_integration/Trim/", get_template_directory());

	$out .= '<li>Trim -> '.get_template_directory().'</li>';

	recurse_copy( plugin_dir_path( __FILE__ )."themes_integration/Trim-child/", get_stylesheet_directory());

	//copy spécifique du style.css de /css/dist vers la racine
	$bool = copy ( plugin_dir_path( __FILE__ )."themes_integration/Trim-child/css/dist/style.css" , get_stylesheet_directory()."/style.css");

	$out .= '<li>Trim Child -> '.get_stylesheet_directory().'</li>';
	if (!$bool)
		$out .= '<li>Erreur lors de la copie de style.css';

	$out .= "</ul>";

	echo "<div id='message' class='updated'><p>".$out."</p></div>";

}

function kz_override_connections() {


	$out = "<ul>";

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/connections/images", plugin_dir_path( __FILE__ )."../connections/images");

	$out .= '<li>Connections Images</li>';

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/connections/includes", plugin_dir_path( __FILE__ )."../connections/includes");

	$out .= '<li>Connections Includes</li>';

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/connections/templates", plugin_dir_path( __FILE__ )."../../connections_templates");

	$out .= '<li>Connections Template cMap</li>';

	$out .= "</ul>";

	echo "<div id='message' class='updated'><p>".$out."</p></div>";

}

//Nextend Facebook connect
function kz_override_nextend_fb() {

	$out = "<ul>";

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/nextend-facebook-connect/", plugin_dir_path( __FILE__ )."../nextend-facebook-connect");

	$out .= '<li>nextend-facebook-connect</li>';

	$out .= "</ul>";

	echo "<div id='message' class='updated'><p>".$out."</p></div>";

}

//Nextend Facebook connect
function kz_override_nextend_google() {

	$out = "<ul>";

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/nextend-google-connect/", plugin_dir_path( __FILE__ )."../nextend-google-connect");

	$out .= '<li>nextend-google-connect</li>';

	$out .= "</ul>";

	echo "<div id='message' class='updated'><p>".$out."</p></div>";

}


/**
 * specificités Kidzou pour Really Simple Events
 *
 * @return void
 * @author
 **/
function kz_override_rse()
{
	$out = "<ul>";

	recurse_copy( plugin_dir_path( __FILE__ )."plugins_integration/really-simple-events/", plugin_dir_path( __FILE__ )."../really-simple-events");

	$out .= '<li>RSE -> '.plugin_dir_path( __FILE__ ).'../really-simple-events</li>';

	$out .= "</ul>";

	echo "<div id='message' class='updated'><p>".$out."</p></div>";

}

/**
 * copy un répertoire entier
 *
 * @return void
 * @author
 * @see http://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
 **/
function recurse_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

//ajout de taxonomy ville
if (!taxonomy_exists('ville')) {
	add_action( 'init', 'create_city_taxonomies', 0 );
}

//create taxonomy ville
function create_city_taxonomies()
{

  // Add new taxonomy, make it hierarchical (like categories)
  $labels = array(
    'name' => _x( 'Ville', 'taxonomy general name' ),
    'singular_name' => _x( 'Ville', 'taxonomy singular name' ),
    'search_items' =>  __( 'Chercher par ville' ),
    'all_items' => __( 'Toutes les villes' ),
    'parent_item' => __( 'Ville Parent' ),
    'parent_item_colon' => __( 'Ville Parent:' ),
    'edit_item' => __( 'Modifier la Ville' ),
    'update_item' => __( 'Mettre Ã  jour la Ville' ),
    'add_new_item' => __( 'Ajouter une ville' ),
    'new_item_name' => __( 'Nom de la nouvelle ville' ),
    'menu_name' => __( 'Ville' ),
  );

  register_taxonomy('ville','post', array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'ville' ),
  ));

}

//ajout de taxonomy transverse (loisirs, vacances, week-end...)
if (!taxonomy_exists('divers')) {
	add_action( 'init', 'create_loisirs_taxonomies', 0 );
}

//create taxonomy transverse
function create_loisirs_taxonomies()
{

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

	  register_taxonomy('divers','post', array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'divers' ),
	  ));


}

//ajout de taxonomy transverse (age)
if (!taxonomy_exists('age')) {
	add_action( 'init', 'create_age_taxonomies', 0 );
}

//create taxonomy transverse
function create_age_taxonomies()
{


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

	  register_taxonomy('age','post', array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'age' ),
	  ));


}

// Add an edit option to comment editing screen

add_action( 'add_meta_boxes_comment', 'extend_comment_add_meta_box' );
function extend_comment_add_meta_box() {
    add_meta_box( 'title', __( 'Extension Kidzou' ), 'extend_comment_meta_box', 'comment', 'normal', 'high' );
}

function extend_comment_meta_box ( $comment ) {

    $featured = get_comment_meta( $comment->comment_ID, 'featured', true );
    wp_nonce_field( 'extend_comment_update', 'extend_comment_update', false );

?>
    <p>
    	<label for="kz_is_featured">
    		<strong>Commentaire Featured ?</strong>
    	</label><br/>
    	<input type="checkbox" value="1" <?php checked( $featured, 1 ); ?>  id="kz_is_featured" name="kz_is_featured">
    	<span style="padding-left:5px;">Si vous cochez cette case, ce commentaire sera affich&eacute; sur la home page</span>
    </p>

<?php

}

// Update comment meta data from comment editing screen

add_action( 'edit_comment', 'kidzou_save_comment' );
function kidzou_save_comment( $comment_id ) {

	$isFeatured = trim($_POST['kz_is_featured']);
	if($isFeatured !=1)	$isFeatured = 0;

	update_comment_meta( $comment_id, 'featured', $isFeatured );

}

/**
 * Creates the db schema
 *
 * @global type $wpdb
 * @global string $kz_db_version
 *
 * @return void
 */
function kz_plugin_install(){
	global $wpdb;
	global $kz_db_version;
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
	$ret = dbDelta( $sql );
	update_site_option( 'kz_db_version' , $kz_db_version );
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
function kz_update_db_check(){
	global $kz_db_version;
	if ( get_site_option( 'kz_db_version' ) != $kz_db_version ) {
		kz_plugin_install();
	}
}

/**
 * Checks for first run
 *
 * @return void
 */
function kz_first_run_check(){
	if ( get_site_option( 'kz_first_run' , 'fasly' ) === 'fasly' ) {
		//Set site option so show we've run this plugin at least once.
		update_site_option( 'kz_first_run' , 'woot' );
	}
}

?>