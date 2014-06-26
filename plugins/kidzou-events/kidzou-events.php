<?php
/*
Plugin Name: Kidzou Events
Plugin URI: http://www.kidzou.fr
Description: Gestion d'événements - requiert l'installation de Kidzou Geo
Version: 2014.06.23
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

require_once (plugin_dir_path( __FILE__ ) . '/kidzou-event-editor.php');

global $kz_events_db_version;
$kz_events_db_version = "1.1";

define( 'KZ_EVENTS_NOTIFICATIONS' , 'events_notifications' );


//plugin install procedure
register_activation_hook( __FILE__, 'kidzou_events_install' );
register_deactivation_hook( __FILE__, 'kidzou_events_uninstall' );

function kidzou_events_install() {


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
function kz_update_events_db_check(){
	// global $kz_events_db_version;
	// if ( get_site_option( 'kz_events_db_version' ) != $kz_events_db_version ) {
	// 	kidzou_events_install();
	// }
}

/**
 * Checks for first run
 *
 * @return void
 */
function kz_first_events_run_check(){
	// if ( get_site_option( 'kz_events_first_run' , 'fasly' ) === 'fasly' ) {
	// 	//Set site option so show we've run this plugin at least once.
	// 	update_site_option( 'kz_events_first_run' , 'woot' );
	// }
}

function kidzou_events_uninstall() {

	remove_role( 'pro' );
	echo '<div class="updated">
       <p>Kidzou Events d&eacute;sinstall&eacute;.</p>
    </div>';

}

//masquer la boite "Identifiant" qui apparait et qui ne sert à rien
add_filter('default_hidden_meta_boxes', 'kidzou_hide_metaboxes', 10, 2);
function kidzou_hide_metaboxes($hidden, $screen) {
       
        $hidden = array('slugdiv');
                // removed 'postexcerpt',
        return $hidden;
}

//custom dashboard dans l'admin (suprresion du widget "quoi de neuf dans wordpress..")
add_action('wp_dashboard_setup', 'remove_dashboard_widgets' );

function remove_dashboard_widgets() {
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

// echo 'event ? '.post_type_exists('event');

if (post_type_exists('event')=== FALSE) {
	add_action('init', 'create_events_post_type');
}

function create_events_post_type() {

	// echo 'create_events_post_type';

	//ne pas faire a chaque appel de page 

	$labels = array(
	    'name'               => 'Evénements',
	    'singular_name'      => 'Evénement',
	    'add_new'            => 'Ajouter',
	    'add_new_item'       => 'Ajouter un événement',
	    'edit_item'          => 'Modifier l\'événement',
	    'new_item'           => 'Nouvel événement',
	    'all_items'          => 'Tous les événements',
	    'view_item'          => 'Voir l\'événement',
	    'search_items'       => 'Chercher des événements',
	    'not_found'          => 'Aucun événement trouvé',
	    'not_found_in_trash' => 'Aucun événement trouvé dans la corbeille',
	    'menu_name'          => 'Evénements',
	  );

	  $args = array(
	    'labels'             => $labels,
	    'public'             => true,
	    'publicly_queryable' => true,
	    'show_ui'            => true,
	    'show_in_menu'       => true,
	    'menu_position' 	 => 5, //sous les articles dans le menu
	    // 'menu_icon' 		 => 'dashicon-calendar', //plus tard
	    'query_var'          => true,
	    'rewrite'            => array( 'slug' => 'event' ),
	    'capability_type'    => 'event',
	    'capabilities' 		 => array(
							        'edit_post'			 => 'edit_event',
							        'edit_posts' 		 => 'edit_events',
							        'edit_others_posts'  => 'edit_others_events',
							        'publish_posts' 	 => 'publish_events',
							        'read_post' 		 => 'read_event',
							        'read_private_posts' => 'read_private_events',
							        'delete_post' 		 => 'delete_event',
							        'delete_private_posts' 		=> 'delete_private_events',
							        'delete_published_posts' 	=> 'delete_published_events',
							        'delete_others_posts' 		=> 'delete_others_events',
							        'edit_private_posts' 		=> 'edit_private_events',
							        'edit_published_posts' 		=> 'edit_published_events',
							        // 'assign_terms' => 'assign_terms'
							    ),
	    'map_meta_cap' 		 => true,
	    'has_archive'        => true,
	    'hierarchical'       => false, //pas de hierarchie d'events
	    'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions'),
	    'taxonomies' 		=> array('age', 'ville', 'divers','category'), //reuse the taxo declared in kidzou plugin
	    'register_meta_box_cb' => 'add_events_metaboxes'
	  );

  register_post_type( 'event', $args );

  flush_rewrite_rules();

  
}


/**
 * customiser la liste des evenements pour afficher dates 
 *
 * @return void
 * @author 
 **/
add_filter('manage_event_posts_columns', 'kz_event_table_head');
function kz_event_table_head( $defaults ) {
    $defaults['event_date_debut']  = 'Date de début';
    $defaults['event_date_fin']  = 'Date de Fin';

    //pas besoin d'afficher cette colonne pour les pro 
    if (!current_user_can('pro')) {
    	$defaults['customer'] = 'Client';
    }

    $defaults['post_thumbs'] = 'Image à la une';
    return $defaults;
}
add_action( 'manage_event_posts_custom_column', 'kz_event_table_content', 10, 2 );
function kz_event_table_content( $column_name, $post_id ) {

    setlocale(LC_TIME, 'fr_FR');
    $format = "d M y";
    if ($column_name == 'event_date_debut') {
		$event_date_debut = get_post_meta( $post_id, 'kz_event_start_date', true );
			echo  date( $format, strtotime( $event_date_debut ) );
    }
    if ($column_name == 'event_date_fin') {
		$event_date_fin = get_post_meta( $post_id, 'kz_event_end_date', true );
			echo  date( $format, strtotime( $event_date_fin ) );
    }
    if ($column_name == 'customer') {
		$event_customer = get_post_meta( $post_id, 'kz_event_customer', true );
			if (function_exists('kz_client_by_id')) {
				$client = kz_client_by_id($event_customer);
				echo  $client["name"];
			}
    }
    if($column_name === 'post_thumbs'){
        echo the_post_thumbnail( array(100,66) );
    }
}

add_filter( 'manage_edit-event_sortable_columns', 'kz_event_table_sorting' );
function kz_event_table_sorting( $columns ) {
	$columns['event_date_debut'] = 'event_date_deb';
	$columns['event_date_fin'] = 'event_date_fin';
	// $columns['customer'] = 'customer';
	return $columns;
}

add_filter( 'request', 'kz_event_date_deb_column_orderby' );
function kz_event_date_deb_column_orderby( $vars ) {
    if ( isset( $vars['orderby'] ) && 'event_date_deb' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key' => 'kz_event_start_date',
            'orderby' => 'meta_value'
        ) );
    }
    return $vars;
}
add_filter( 'request', 'kz_event_date_fin_column_orderby' );
function kz_event_date_fin_column_orderby( $vars ) {
    if ( isset( $vars['orderby'] ) && 'event_date_fin' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key' => 'kz_event_end_date',
            'orderby' => 'meta_value'
        ) );
    }
    return $vars;
}


function add_events_metaboxes() {
	add_meta_box('kz_event_metabox', 'Evenement', 'kz_event_metabox', 'event', 'normal', 'high');
	add_meta_box('kz_place_metabox', 'Lieu', 'kz_place_metabox', 'event', 'normal', 'high');

	//si le user n'est pas admin, la meta "Ville" est = la ville de rattachement du user
	// if ( current_user_can( 'manage_options' )) 
	// 	add_meta_box('kz_event_metropole', 'Metropole', 'kz_event_metropole', 'event', 'side', 'default');
}

//custom actions déclenchées lors de la sauvegarde des posts de type 'event'
// add_action( 'save_post_event', 'kz_save_event_info' );
add_action( 'save_post_event', 'kz_save_place_info' );
add_action( 'save_post_event', 'kz_save_event_info' );
add_action( 'save_post_event', 'kz_save_event_metropole' ); //à faire dans kidzou-geo plutot

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_event_metabox()
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
	</script>';

	?>
	<div class="kz_form" id="event_form">

		<?php

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
				
		} ?>

		<h4>Dates de l'&eacute;v&eacute;nement</h4>

		<ul>
			<li>
				<label for="start_date">Date de d&eacute;but:</label>
		    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().start_date, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
		    	<input type="hidden" name="kz_event_start_date"  data-bind="value: eventData().formattedStartDate" />
		    	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
			</li>
			<li>
				<label for="end_date">Date de fin</label>
		    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().end_date, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
				<input type="hidden" name="kz_event_end_date"  data-bind="value: eventData().formattedEndDate" />
				<em data-bind="if: eventData().eventDuration()!==''">(<span data-bind="text: eventData().eventDuration"></span>)</em>
				<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
			</li>
		</ul>

	</div>

	<?php
}

/**
 * kz_event_featured : stockage des valeurs A/B pour des problématiques de non stockage si valeur numérique à 0
 *
 * @return void
 * @author 
 **/
function kz_save_event_info($post_id)
{

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['eventmeta_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_event', $post_id ))
		return $post_id;

	//formatter les dates avant de les sauvegarder 
	//input : 23 Février 2014
	//output : 2014-02-23 00:00:01 (start_date) ou 2014-02-23 23:59:59 (end_date)
	$events_meta['kz_event_start_date'] 			= $_POST['kz_event_start_date'];
	$events_meta['kz_event_end_date'] 				= $_POST['kz_event_end_date'];
	
	//cette metadonnée n'est pas mise à jour dans tous les cas
	//uniquement si le user n'est pas "pro"
	if ( !current_user_can( 'pro' ) ) 
		$events_meta['kz_event_featured'] 			= ($_POST['kz_event_featured']=='on' ? "A" : "B");
	else {
		if (get_post_meta($post_id, 'kz_event_featured', TRUE)!='')
			$events_meta['kz_event_featured'] 			= get_post_meta($post_id, 'kz_event_featured', TRUE);
		else
			$events_meta['kz_event_featured'] 		= "B";
	}
		

	// Add values of $events_meta as custom fields
	foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
		// if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		// $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		$prev = get_post_meta($post_id, $key, TRUE);
		if($prev && $prev!='') { // If the custom field already has a value
			update_post_meta($post_id, $key, $value, $prev);
		} else { // If the custom field doesn't have a value
			if ($prev=='') delete_post_meta($post_id, $key);
			add_post_meta($post_id, $key, $value, TRUE);
		}
		if(!$value) delete_post_meta($post_id, $key); // Delete if blank
	}
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_save_event_metropole($post_id)
{
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    
    if ( current_user_can( 'manage_options', $post_id )) {

    	// if( !isset( $_POST['kz_event_metropole_nonce'] ) || !wp_verify_nonce( $_POST['kz_event_metropole_nonce'], 'kz_save_event_nonce' ) ) return;
 
    	// $metropole = $_POST['kz_event_metropole'];
    	// $result = wp_set_post_terms( $post_id, array( intval($metropole) ), 'ville' );
    
    } else {

    	//la metropole est la metropole de rattachement du user
    	if (function_exists('kz_set_post_user_metropole'))
    		kz_set_post_user_metropole($post_id);
    } 

    
}



add_action('wp_enqueue_scripts', 'add_kz_events_scripts');
/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function add_kz_events_scripts()
{
	wp_enqueue_style( 'kidzou-events', WP_PLUGIN_URL."/kidzou-events/css/kidzou-events.css" );
}

/**
 * utilisation des JS lors de la saisie des events (sur la page d'eition d'un event)
 *
 * @return void
 * @author 
 **/
add_action( 'admin_enqueue_scripts', 'kz_enqueue_events_scripts' );

function kz_enqueue_events_scripts() {

	$screen = get_current_screen();

	//si on est entrain d'éditer un post de type event...
	if ($screen->post_type == 'event' ) {

		if (!wp_style_is( 'kidzou-form', 'enqueued' ) )
			wp_enqueue_style( 'kidzou-form', WP_PLUGIN_URL."/kidzou/css/kidzou-form.css" );

		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
		wp_enqueue_script('ko-validation',			WP_PLUGIN_URL.'/kidzou/js/admin/knockout.validation.min.js',array("ko"), '1.0', true);
		wp_enqueue_script('ko-validation-locale',	WP_PLUGIN_URL.'/kidzou/js/admin/ko-validation-locales/fr-FR.js',array("ko-validation"), '1.0', true);
				
		//utilisé pour le formattage des dates
		wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
		wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);

		//datepicker
		wp_enqueue_style( 'jquery-ui-custom', WP_PLUGIN_URL."/kidzou/css/jquery-ui-1.10.3.custom.min.css" );	
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-datepicker-fr', WP_PLUGIN_URL.'/kidzou/js/jquery.ui.datepicker-fr.js', array('jquery-ui-datepicker'),'1.0', true);

		wp_enqueue_script('kidzou-events', WP_PLUGIN_URL."/kidzou-events/js/kidzou-edit-event.js" ,array('jquery', 'ko', 'ko-validation'), KIDZOU_VERSION, true);

	} 
	
}


/**
 * Construit une WP_Query contenant des evenements sur une metropole donnée, dans un intervalle donné
 *
 * @return WP_Query
 * @author 
 **/
function kz_events_list( $interval_days = 7, $ppp=-1 )
{
	$metropole = '';

	if (function_exists('kz_get_request_metropole'))
		$metropole = kz_get_request_metropole();

	$interval = 'P7D';

	switch ($interval_days) {
		case 7:
			$interval = 'P7D';
			break;

		case 30:
			$interval = 'P30D';
			break;
		
		default:
			$interval = 'P7D';
			break;
	}

	$current= time();
	$start 	= date('Y-m-d 00:00:00', $current);
	$end_time 	= new DateTime($start);

	$end_time 	= $end_time->add( new DateInterval($interval) ); 

	$end 	= $end_time->format('Y-m-d 23:59:59');

	$meta_q = array(
                   array(
                         'key' => 'kz_event_start_date',
                         'value' => $end,
                         'compare' => '<=',
                         'type' => 'DATETIME'
                        )
                   ,
					array(
                         'key' => 'kz_event_end_date',
                         'value' => $start,
                         'compare' => '>=',
                         'type' => 'DATETIME'
                        )
		    	);

	if ($metropole!='')
		$args = array(
			'post_type'=> 'event',
			'meta_key' => 'kz_event_start_date' , //kz_event_featured
			'orderby' => 'meta_value',
			'order' => 'ASC' ,
			'posts_per_page' => $ppp, 
			'meta_query' => $meta_q,
		    'tax_query' => array(
		        array(
		              'taxonomy' => 'ville',
		              'field' => 'slug',
		              'terms' => $metropole,
		              )
		    )
		);
	else
		$args = array(
			'post_type'=> 'event',
			'meta_key' => 'kz_event_start_date' , //kz_event_featured
			'orderby' => 'meta_value',
			'order' => 'ASC' ,
			'posts_per_page' => $ppp, 
			'meta_query' => $meta_q
		);

	$query = new WP_Query($args );	

	$list = 	$query->get_posts(); 

	//les featured en premier
	uasort($list, "kz_event_featured_cmp");

	return $list;
}

/**
 * compare 2 events et place les featured en premier, mais maintien l'ordre des dates (orderby kz_event_end_date)
 * pour rappel, un event featured à un meta "featured"='A' alors qu'un event normal a sa "featured"='B'
 *
 * @return void
 * @author 
 **/
function kz_event_featured_cmp($a, $b)
{
	$featured_a = the_event_meta("featured", $a->ID);
	$featured_b = the_event_meta("featured", $b->ID);

	// pas de distinction de featured, c'est la start_date qui prime
	if (strcmp($featured_a, $featured_b)==0) {

		$start_a = the_event_meta("start_date", $a->ID);
		$start_b = the_event_meta("start_date", $b->ID);
		return strcmp($start_a, $start_b);
	}

	return strcmp($featured_a, $featured_b);
}


/**
 * Teasing des events (a utiliser sur la home par exemple), retourne 3 events sur les 7 jours à venir
 *
 * @return array of posts
 * @author 
 **/
function kz_events_teaser() {

	return kz_events_list(7, 3); //7 days, 3 results
}

/**
 * liste d'evenements relatifs à un client
 *
 * @return array of posts
 * @author 
 **/
function kz_the_customer_events() {

	$current_customer = the_event_meta('customer');

	if ($current_customer!='' && intval($current_customer)>0)
	{
		$posts = kz_events_list(30, -1);

		return array_filter($posts, function($elem) use($current_customer){
					$event_customer = the_event_meta('customer', $elem->ID ); 
                    return $event_customer==$current_customer;
                 });
	}

	return $posts;

}


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function get_event_meta($event_id = 0)
{

	if ($event_id==0)
	{
		global $post;
		$event_id = $post->ID;
	}

	$start_date 		= get_post_meta($event_id, 'kz_event_start_date', TRUE);
	$end_date   		= get_post_meta($event_id, 'kz_event_end_date', TRUE);
	$location_name   	= get_post_meta($event_id, 'kz_event_location_name', TRUE);
	$location_address   = get_post_meta($event_id, 'kz_event_location_address', TRUE);
	$location_city 		= get_post_meta($event_id, 'kz_event_location_city', TRUE);
	$featured_index		= get_post_meta($event_id, 'kz_event_featured', TRUE);
	$customer			= get_post_meta($event_id, 'kz_event_customer', TRUE);
	$featured 			= ($featured_index == 'A');

	return array(
			"start_date" => $start_date,
			"end_date" => $end_date,
			"location_name" => $location_name,
			"location_address" => $location_address,
			"location_venue" => $location_city,
			"featured" => $featured,
			"customer" => $customer
		);
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function the_event_meta($meta='', $event_id=0)
{

	if ($meta=='')
		return '';
	
	if ($event_id==0)
	{
		global $post;
		$event_id = $post->ID;
	}

	$prefix = 'kz_event_';
	$meta = get_post_meta($event_id, $prefix. $meta, TRUE);

	return $meta;
}




/**
 * undocumented function
 *
 * @return true si l'événement est en cours, false si il est terminé ou pas visible
 * @author 
 **/
function  kz_is_event_active()
{

	if (get_post_type()=='event')
	{
		$meta = get_event_meta();
		
		$end_time = new DateTime($meta["end_date"]);

		$current= time();

		if ($end_time->getTimestamp() < $current)
			return false;

		return true;

	}
	return false;
}


/**
 * undocumented function
 *
 * @return true si l'événement est en cours, false si il est terminé ou pas visible
 * @author 
 **/
function  kz_is_event($event_id=0)
{	

	if ($event_id==0)
	{
		global $post;
		$event_id = $post->ID;
	}

	$post = get_post($event_id);
	$type = get_post_type($post);

	return $type=='event';
}


?>