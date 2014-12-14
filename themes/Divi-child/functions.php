<?php

add_action( 'after_setup_theme', 'override_divi_parent_functions');


/**
 * surcharger le pagebuilder parent afin de ne pas limiter le portfolio aux projets
 */
require_once( get_stylesheet_directory() . '/et-pagebuilder/et-pagebuilder.php' );

/**
 * widget specifique pour afficher les contenus d'un client dans la sidebar
 */
require_once( get_stylesheet_directory() . '/widget-customer-posts.php' );

/**
 * shortcodes spécifiques Kidzou
 *
 * @see http://www.themelab.com/2010/07/11/remove-code-wordpress-header/
 * @return void
 * @author 
 **/ 
function override_divi_parent_functions() 
{	
	//surcharge pour avoir des thumbs carrées de taille 225
	global $et_theme_image_sizes;
	add_theme_support( 'post-thumbnails' ); //normalement déjà supporté par le parent mais bon...
	$et_theme_image_sizes['400x284'] = "post_gallery";  //nécessaire car utilisé dans la fonction print_thumbnail
	add_image_size( 'post_gallery', 400, 284, true ); //crop
	add_image_size( 'post_gallery_featured', 600, 284, true ); //crop

	//suppression du custom post type "project"
	remove_action('init','et_pb_register_posttypes', 0); //meme ordre que le parent
    add_action('init','kz_register_divi_layouts', 0); 


    //nouveau shotcode kidzou pour ajouter les post types de kidzou
    //et ne pas utiliser les taxonomies de Divi (project_category)
    //copié sur functions.php du parent
    add_shortcode('kz_pb_blog','kz_pb_blog');
    add_shortcode('kz_pb_portfolio','kz_pb_portfolio');
    add_shortcode('kz_pb_fullwidth_portfolio','kz_pb_fullwidth_portfolio');
    add_shortcode('kz_pb_filterable_portfolio','kz_pb_filterable_portfolio');
    add_shortcode('searchbox','searchbox');

    remove_shortcode('et_pb_fullwidth_map');
    remove_shortcode('et_pb_map');
    add_shortcode( 'et_pb_fullwidth_map', 'kz_pb_map' );
	add_shortcode( 'et_pb_map', 'kz_pb_map' );

	//ajout du codepostal dans le formulaire d'inscription à la newsletter
	remove_shortcode('et_pb_signup');
	add_shortcode( 'et_pb_signup', 'kz_pb_signup' );

	remove_action('wp_ajax_et_pb_submit_subscribe_form','et_pb_submit_subscribe_form'); //meme ordre que le parent
	remove_action('wp_ajax_nopriv_et_pb_submit_subscribe_form','et_pb_submit_subscribe_form'); //meme ordre que le parent
	
	add_action( 'wp_ajax_et_pb_submit_subscribe_form', 'kz_pb_submit_subscribe_form' );
	add_action( 'wp_ajax_nopriv_et_pb_submit_subscribe_form', 'kz_pb_submit_subscribe_form' );

	//image gallery incluse manuellement au bon endroit dans single.php
	remove_filter( 'the_content', 'easy_image_gallery_append_to_content' ); 

	//inviter l'utilisateur à scroller
	add_filter( 'excerpt_length', 'custom_excerpt_length' , 999 );
	// add_filter('excerpt_more', 'excerpt_more_invite_scroll');

	//permettre l'execution de shortcodes dans la sidebar
	//pour notamment inclure dans la sidebar le widget newsletter
	add_filter('widget_text', 'do_shortcode');

	//surcharge des JS du parent
	add_action( 'wp_enqueue_scripts', 'kz_divi_load_scripts' ,99);

	//Alterer les queries pour les archives afin de trier par reco count
	add_action( "pre_get_posts", "filter_archive_query" );

	//gestion de l'habillage publicitaire
	//passer en dernier pour retirer et_fixed_nav pour rendre la barre de header floattante
	add_filter( 'body_class', 'kz_add_class_habillage', 100 ); 

	//optimisation de performance
	//pas besoin de passer par cette fonction, les css sont dans style.css
	remove_action( 'wp_head', 'et_divi_add_customizer_css' );

	//script utilisé dans les shortcodes 
	// add_action('init', 'kz_register_shortcode_script');
	// add_action('wp_head', 'kz_print_shortcode_script', 1);

}

function custom_excerpt_length( $length ) {
	return 180;
}

//lors d'un habillage, le header ne peut pas etre fixe
//et le body est doté d'une classe qui permet de contraindre le container
function kz_add_class_habillage( $classes ){

	global $kidzou_options;	

	if (isset($kidzou_options['pub_habillage']) && trim($kidzou_options['pub_habillage'])<>'') {

		$is_habillage = ( trim( Kidzou_Utils::get_option('pub_habillage') )!='' );

		if ($is_habillage) {

			$classes[] = 'kz_habillage';
			if (in_array('et_fixed_nav', $classes)) {
				$key = array_search('et_fixed_nav', $classes);
				unset($classes[$key]);
			}
		}
		
	}
	
	return $classes;
}

function kz_habillage() {

	global $kidzou_options;

	if (isset($kidzou_options['pub_habillage']) && $kidzou_options['pub_habillage']!='')
		echo $kidzou_options['pub_habillage'];

}


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function filter_archive_query($query)
{
	if (is_archive() && $query->is_main_query() && !is_admin()) {

		//pas de limite sur le nombre de posts dans un categorie
		$query->set('nopaging', true);
		$query->set('posts_per_page', '-1' ); 
		
	}
}

/**
 * Suppression du custom.js natif de Divi pour enregistrer le custom.js de Kidzou qui permet un filtrage correct par isotope du portefeuille de posts
 * parce que index.php mélange la vue "filterable portfolio" et "masonry grid" de Divi
 *
 * @return void
 * @author 
 **/
function kz_divi_load_scripts ()
{
	wp_dequeue_script( 'divi-custom-script' );
	wp_enqueue_script( 'kidzou-custom-script',  get_stylesheet_directory_uri().'/js/custom.js', array( 'jquery', 'jquery-ui-autocomplete' ), '1.0.0', true );

	$terms = get_terms(array('category', 'divers', 'ville', 'age'), array("fields", "all") );

	$items = array();

	foreach ($terms as $term) {

		if ($term->taxonomy == 'divers')
			$tax = 'famille';
		elseif ($term->taxonomy == 'category') 
			$tax = 'rubrique';
		else
			$tax = $term->taxonomy;
		
		$items[] = array("id" => $tax.'/'.$term->slug, "label" => $term->name);
	}

	wp_localize_script( 'kidzou-custom-script', 'et_custom', array(
		'ajaxurl'             => admin_url( 'admin-ajax.php' ),
		'images_uri'          => get_template_directory_uri() . '/images',
		'et_load_nonce'       => wp_create_nonce( 'et_load_nonce' ),
		'subscription_failed' => __( 'Please, check the fields below to make sure you entered the correct information.', 'Divi' ),
		'fill'                => esc_html__( 'Fill', 'Divi' ),
		'field'               => esc_html__( 'field', 'Divi' ),
		'invalid'             => esc_html__( 'Invalid email', 'Divi' ),
		'captcha'             => esc_html__( 'Captcha', 'Divi' ),
		'prev'				  => esc_html__( 'Prev', 'Divi' ),
		'next'				  => esc_html__( 'Next', 'Divi' ),
		"terms_list" 		=> $items, 
		'no_results'		=> __('Aucun r&eacute;sultat trouv&eacute; !','Divi'),
		'results' 			=> __('Utilisez les fl&egrave;ches pour naviguer dans les resultats', 'Divi'),
		'suggest_title' 	=> __('Quelques suggestions de cat&eacute;gories : ','Divi'),
		'site_url' 			=> site_url()
	) );
}

function kz_mailchimp_key()
{
	return '1b5be0ebf3';
}


/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function get_post_footer()
{
	
	$lists = et_pb_get_mailchimp_lists();

	if(!empty($lists) && is_array($lists)) {
		$key = kz_mailchimp_key();

		$posts_ids_objects = Kidzou_Geo::get_related_posts();
		$ids = array();

		foreach ($posts_ids_objects as $id_object) {
		    $ids[]   = $id_object->ID;
		}
		$ids_list = implode(',', $ids);	

		echo do_shortcode('
			[et_pb_section fullwidth="off" specialty="off"]
				[et_pb_row]
				<h2>D&apos;autres sorties sympa :</h2>
					[et_pb_column type="4_4"]
						[kz_pb_portfolio admin_label="Portfolio" fullwidth="off" posts_number="3" post__in="'.$ids_list.'" show_title="on" show_categories="on" show_pagination="off" show_filters="off" background_layout="light" show_ad="off" /]
					[/et_pb_column]
				[/et_pb_row]
				[et_pb_row]
					[et_pb_column type="4_4"]
						[et_pb_signup admin_label="Subscribe" provider="mailchimp" mailchimp_list="'.$key.'" aweber_list="none" title="'.__('Inscrivez-vous à notre Newsletter','Divi').'" button_text="'.__('Inscrivez-vous ','Divi').'" use_background_color="on" background_color="#ed0a71" background_layout="dark" text_orientation="left"]'.__('<p>Nous distribuons la newsletter 1 à 2 fois par mois, elle contient les meilleures recommandations de la communaut&eacute; des parents Kidzou, ainsi que des jeux concours de temps en temps ! </p>','Divi').'[/et_pb_signup]
					[/et_pb_column]
				[/et_pb_row]
			[/et_pb_section]');
	}
	
}

/**
 * le formulaire de souscription newsletter, à la sauce Kidzou (avec le codepostal)
 *
 */
function kz_pb_signup( $atts, $content = null ) {

	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'title' => '',
			'button_text' => __( 'Subscribe', 'Divi' ),
			'background_color' => et_get_option( 'accent_color', '#7EBEC5' ),
			'background_layout' => 'dark',
			'mailchimp_list' => '',
			'aweber_list' => '',
			'text_orientation' => 'left',
			'use_background_color' => 'on',
			'provider' => 'mailchimp',
			'feedburner_uri' => '',
		), $atts
	) );

	$class = " et_pb_bg_layout_{$background_layout} et_pb_text_align_{$text_orientation}";

	$form = '';

	$firstname     = __( 'First Name', 'Divi' );
	$lastname      = __( 'Last Name', 'Divi' );
	$email_address = __( 'Email Address', 'Divi' );
	$zipcode   = __( 'Code Postal', 'Divi' );

	// Kidzou_Utils::log('mailchimp_list '.$mailchimp_list);

	switch ( $provider ) {
		case 'mailchimp' :
			if ( ! in_array( $mailchimp_list, array( '', 'none' ) ) ) {
				$form = sprintf( '
					<div class="et_pb_newsletter_form">
						<div class="et_pb_newsletter_result"></div>
						<p>
							<label class="et_pb_contact_form_label" for="et_pb_signup_firstname" style="display: none;">%3$s</label>
							<input id="et_pb_signup_firstname" class="input" type="text" value="%4$s" name="et_pb_signup_firstname">
						</p>
						<p>
							<label class="et_pb_contact_form_label" for="et_pb_signup_lastname" style="display: none;">%5$s</label>
							<input id="et_pb_signup_lastname" class="input" type="text" value="%6$s" name="et_pb_signup_lastname">
						</p>
						<p>
							<label class="et_pb_contact_form_label" for="et_pb_signup_email" style="display: none;">%7$s</label>
							<input id="et_pb_signup_email" class="input" type="text" value="%8$s" name="et_pb_signup_email">
						</p>
						<p>
							<label class="et_pb_contact_form_label" for="et_pb_signup_zipcode" style="display: none;">%9$s</label>
							<input id="et_pb_signup_zipcode" class="input" type="text" value="%10$s" name="et_pb_signup_zipcode">
						</p>
						<p><a class="et_pb_newsletter_button" href="#"><span class="et_subscribe_loader"></span><span class="et_pb_newsletter_button_text">%1$s</span></a></p>
						<input type="hidden" value="%2$s" name="et_pb_signup_list_id" />
					</div>',
					esc_html( $button_text ),
					( ! in_array( $mailchimp_list, array( '', 'none' ) ) ? esc_attr( $mailchimp_list ) : '' ),
					esc_html( $firstname ),
					esc_attr( $firstname ),
					esc_html( $lastname ),
					esc_attr( $lastname ),
					esc_html( $email_address ),
					esc_attr( $email_address ),
					esc_html( $zipcode ),
					esc_attr( $zipcode )
				);
			}

			break;
		case 'feedburner':
			$form = sprintf( '
				<div class="et_pb_newsletter_form et_pb_feedburner_form">
					<form action="http://feedburner.google.com/fb/a/mailverify" method="post" target="popupwindow" onsubmit="window.open(\'http://feedburner.google.com/fb/a/mailverify?uri=%4$s\', \'popupwindow\', \'scrollbars=yes,width=550,height=520\'); return true">
					<p>
						<label class="et_pb_contact_form_label" for="email" style="display: none;">%2$s</label>
						<input id="email" class="input" type="text" value="%3$s" name="email">
					</p>
					<p><button class="et_pb_newsletter_button" type="submit">%1$s</button></p>
					<input type="hidden" value="%4$s" name="uri" />
					<input type="hidden" name="loc" value="%5$s" />
				</div>',
				esc_html( $button_text ),
				esc_html( $email_address ),
				esc_attr( $email_address ),
				esc_attr( $feedburner_uri ),
				esc_attr( get_locale() )
			);

			break;
		case 'aweber' :
			$firstname = __( 'Name', 'Divi' );

			if ( ! in_array( $aweber_list, array( '', 'none' ) ) ) {
				$form = sprintf( '
					<div class="et_pb_newsletter_form" data-service="aweber">
						<div class="et_pb_newsletter_result"></div>
						<p>
							<label class="et_pb_contact_form_label" for="et_pb_signup_firstname" style="display: none;">%3$s</label>
							<input id="et_pb_signup_firstname" class="input" type="text" value="%4$s" name="et_pb_signup_firstname">
						</p>
						<p>
							<label class="et_pb_contact_form_label" for="et_pb_signup_email" style="display: none;">%5$s</label>
							<input id="et_pb_signup_email" class="input" type="text" value="%6$s" name="et_pb_signup_email">
						</p>
						<p><a class="et_pb_newsletter_button" href="#"><span class="et_subscribe_loader"></span><span class="et_pb_newsletter_button_text">%1$s</span></a></p>
						<input type="hidden" value="%2$s" name="et_pb_signup_list_id" />
					</div>',
					esc_html( $button_text ),
					( ! in_array( $aweber_list, array( '', 'none' ) ) ? esc_attr( $aweber_list ) : '' ),
					esc_html( $firstname ),
					esc_attr( $firstname ),
					esc_html( $email_address ),
					esc_attr( $email_address )
				);
			}

			break;
	}

	// Kidzou_Utils::log($form);

	$output = sprintf(
		'<div%6$s class="et_pb_newsletter clearfix%4$s%7$s%8$s"%5$s>
			<div class="et_pb_newsletter_description">
				%1$s
				%2$s
			</div>
			%3$s
		</div>',
		( '' !== $title ? '<h2>' . esc_html( $title ) . '</h2>' : '' ),
		do_shortcode( et_pb_fix_shortcodes( $content ) ),
		$form,
		esc_attr( $class ),
		( 'on' === $use_background_color
			? sprintf( ' style="background-color: %1$s;"', esc_attr( $background_color ) )
			: ''
		),
		( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
		( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
		( 'on' !== $use_background_color ? ' et_pb_no_bg' : '' )
	);

	// Kidzou_Utils::log($output);

	return $output;
}


/**
 * validation du formulaire de souscription newsletter, à la sauce Kidzou (avec le codepostal)
 *
 */
function kz_pb_submit_subscribe_form() {

	if ( ! wp_verify_nonce( $_POST['et_load_nonce'], 'et_load_nonce' ) ) die( json_encode( array( 'error' => __( 'Configuration error', 'Divi' ) ) ) );

	$service = sanitize_text_field( $_POST['et_service'] );

	$list_id = sanitize_text_field( $_POST['et_list_id'] );

	$email = sanitize_email( $_POST['et_email'] );

	$firstname = sanitize_text_field( $_POST['et_firstname'] );

	$zipcode = sanitize_text_field( $_POST['kz_zipcode'] );

	// Kidzou_Utils::log('zipcode : '.$zipcode);

	if ( '' === $firstname ) die( json_encode( array( 'error' => __( 'Please enter first name', 'Divi' ) ) ) );

	if ( ! is_email( sanitize_email( $_POST['et_email'] ) ) ) die( json_encode( array( 'error' => __( 'Incorrect email', 'Divi' ) ) ) );

	if ( !  preg_match('#^[0-9]{5}$#',$zipcode) ) die( json_encode( array( 'error' => __( 'Le Code Postal est incorrect', 'Divi' ) ) ) );

	if ( '' == $list_id ) die( json_encode( array( 'error' => __( 'Configuration error: List is not defined', 'Divi' ) ) ) );

	$success_message = __( '<h2 class="et_pb_subscribed">Subscribed - look for the confirmation email!</h2>', 'Divi' );

	switch ( $service ) {
		case 'mailchimp' :
			$lastname = sanitize_text_field( $_POST['et_lastname'] );
			$email = array( 'email' => $email );

			if ( ! class_exists( 'MailChimp' ) )
				require_once( get_template_directory() . '/includes/subscription/mailchimp/mailchimp.php' );

			$mailchimp_api_key = et_get_option( 'divi_mailchimp_api_key' );

			if ( '' === $mailchimp_api_key ) die( json_encode( array( 'error' => __( 'Configuration error: api key is not defined', 'Divi' ) ) ) );


				$mailchimp = new MailChimp( $mailchimp_api_key );

				$merge_vars = array(
					'PRENOM' => $firstname,
					'NOM' => $lastname,
					'CODEPOSTAL' => $zipcode
				);

				$retval =  $mailchimp->call('lists/subscribe', array(
					'id'         => $list_id,
					'email'      => $email,
					'merge_vars' => $merge_vars,
				));

				if ( isset($retval['error']) ) {
					if ( '214' == $retval['code'] ){
						$error_message = str_replace( 'Click here to update your profile.', '', $retval['error'] );
						$result = json_encode( array( 'success' => $error_message ) );
					} else {
						$result = json_encode( array( 'success' => $retval['error'] ) );
					}
				} else {
					$result = json_encode( array( 'success' => $success_message ) );
				}

			die( $result );
			break;
		case 'aweber' :
			if ( ! class_exists( 'AWeberAPI' ) ) {
				require_once( get_template_directory() . '/includes/subscription/aweber/aweber_api.php' );
			}

			$account = et_pb_get_aweber_account();

			if ( ! $account ) {
				die( json_encode( array( 'error' => __( 'Aweber: Wrong configuration data', 'Divi' ) ) ) );
			}

			try {
				$list_url = "/accounts/{$account->id}/lists/{$list_id}";
				$list = $account->loadFromUrl( $list_url );

				$new_subscriber = $list->subscribers->create(
					array(
						'email' => $email,
						'name'  => $firstname,
					)
				);

				die( json_encode( array( 'success' => $success_message ) ) );
			} catch ( Exception $exc ) {
				die( json_encode( array( 'error' => $exc->message ) ) );
			}

			break;
	}

	die();
}


// function kz_register_shortcode_script() {

// }

// function kz_print_shortcode_script() {


// 	wp_enqueue_script(  );

// }

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function searchbox()
{


	$output = sprintf(
		'<form class="kz_searchbox" method="get" action="%2$s">
			<input id="kz_searchinput" placeholder="%1$s" type="text" autocomplete="off" name="s">
			<a id="kz_searchbutton" class="et_pb_more_button" href="#">Rechercher</a>
		</form>
		',
		__('Ex: Roubaix, Animaux, 3-6 ans...','Divi'),
		site_url()
	);

	return $output ;
}
	
function kz_register_divi_layouts() {

	$labels = array(
		'name'               => _x( 'Layouts', 'Layout type general name', 'Divi' ),
		'singular_name'      => _x( 'Layout', 'Layout type singular name', 'Divi' ),
		'add_new'            => _x( 'Add New', 'Layout item', 'Divi' ),
		'add_new_item'       => __( 'Add New Layout', 'Divi' ),
		'edit_item'          => __( 'Edit Layout', 'Divi' ),
		'new_item'           => __( 'New Layout', 'Divi' ),
		'all_items'          => __( 'All Layouts', 'Divi' ),
		'view_item'          => __( 'View Layout', 'Divi' ),
		'search_items'       => __( 'Search Layouts', 'Divi' ),
		'not_found'          => __( 'Nothing found', 'Divi' ),
		'not_found_in_trash' => __( 'Nothing found in Trash', 'Divi' ),
		'parent_item_colon'  => '',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false,
		'can_export'         => true,
		'query_var'          => false,
		'has_archive'        => false,
		'capability_type'    => 'post',
		'hierarchical'       => false,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields' ),
	);

	register_post_type( 'et_pb_layout', apply_filters( 'et_pb_layout_args', $args ) );
}	

function kz_pb_blog( $atts ) {

	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'fullwidth' => 'on',
			'posts_number' => 10,
			'include_categories' => '',
			'meta_date' => 'M j, Y',
			'show_thumbnail' => 'on',
			'show_content' => 'off',
			'show_author' => 'on',
			'show_date' => 'on',
			'show_categories' => 'on',
			'show_pagination' => 'on',
			'background_layout' => 'light',
		), $atts
	) );

	global $paged;

	$container_is_closed = false;

	if ( 'on' !== $fullwidth ){
		wp_enqueue_script( 'jquery-masonry-3' );
	}

	$args = array( 'posts_per_page' => (int) $posts_number );

	$et_paged = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );

	if ( is_front_page() ) {
		$paged = $et_paged;
	}

	if ( '' !== $include_categories )
		$args['cat'] = $include_categories;

	if ( ! is_search() ) {
		$args['paged'] = $et_paged;
	}

	$args['post_type'] = Kidzou::post_types();

	ob_start();

	query_posts( $args );

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();

			$post_format = get_post_format();

			$thumb = '';

			$width = 'on' === $fullwidth ? 1080 : 400;
			$width = (int) apply_filters( 'et_pb_blog_image_width', $width );

			$height = 'on' === $fullwidth ? 675 : 250;
			$height = (int) apply_filters( 'et_pb_blog_image_height', $height );
			$classtext = 'on' === $fullwidth ? 'et_pb_post_main_image' : '';
			$titletext = get_the_title();
			$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
			$thumb = $thumbnail["thumb"];

			$no_thumb_class = '' === $thumb || 'off' === $show_thumbnail ? ' et_pb_no_thumb' : '';

			if ( in_array( $post_format, array( 'video', 'gallery' ) ) ) {
				$no_thumb_class = '';
			} ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' . $no_thumb_class ); ?>>

		<?php
			et_divi_post_format_content();

			if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
				if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) :
					printf(
						'<div class="et_main_video_container">
							%1$s
						</div>',
						$first_video
					);
				elseif ( 'gallery' === $post_format ) :
					et_gallery_images();
				elseif ( '' !== $thumb && 'on' === $show_thumbnail ) :
					if ( 'on' !== $fullwidth ) echo '<div class="et_pb_image_container">'; ?>
						<a href="<?php the_permalink(); ?>">
							<?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
						</a>
				<?php
					if ( 'on' !== $fullwidth ) echo '</div> <!-- .et_pb_image_container -->';
				endif;
			} ?>

		<?php if ( 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote', 'gallery' ) ) ) { ?>
			<?php if ( ! in_array( $post_format, array( 'link', 'audio' ) ) ) { ?>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php } ?>

			<?php
				if ( 'on' === $show_author || 'on' === $show_date || 'on' === $show_categories ) {
					printf( '<p class="post-meta">%1$s %2$s %3$s</p>',
						(
							'on' === $show_author
								? sprintf( __( 'by %s |', 'Divi' ), et_get_the_author_posts_link() )
								: ''
						),
						(
							'on' === $show_date
								? sprintf( __( '%s |', 'Divi' ), get_the_date( $meta_date ) )
								: ''
						),
						(
							'on' === $show_categories
								? get_the_category_list(', ')
								: ''
						)
					);
				}

				if ( 'on' === $show_content ) {
					global $more;
					$more = null;

					the_content( __( 'read more...', 'Divi' ) );
				} else {
					if ( has_excerpt() ) {
						the_excerpt();
					} else {
						truncate_post( 270 );
					}
				} ?>
		<?php } // 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote', 'gallery' ?>

		</article> <!-- .et_pb_post -->
<?php
		} // endwhile

		if ( 'on' === $show_pagination && ! is_search() ) {
			echo '</div> <!-- .et_pb_posts -->';

			$container_is_closed = true;

			if ( function_exists( 'wp_pagenavi' ) )
				wp_pagenavi();
			else
				get_template_part( 'includes/navigation', 'index' );
		}

		wp_reset_query();
	} else {
		get_template_part( 'includes/no-results', 'index' );
	}

	$posts = ob_get_contents();

	ob_end_clean();

	$class = " et_pb_bg_layout_{$background_layout}";

	$output = sprintf(
		'<div%5$s class="%1$s%3$s%6$s">
			%2$s
		%4$s',
		( 'on' === $fullwidth ? 'et_pb_posts' : 'et_pb_blog_grid clearfix' ),
		$posts,
		esc_attr( $class ),
		( ! $container_is_closed ? '</div> <!-- .et_pb_posts -->' : '' ),
		( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
		( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
	);

	if ( 'on' !== $fullwidth )
		$output = sprintf( '<div id="et_pb_blog_grid_wrapper" class="et_pb_blog_grid_wrapper">%1$s</div>', $output );

	return $output;
}

/**
 * genere un portfolio incluant les post_types specifiques de Kidzou (les offres n'apparaissent pas dans le portfolio)
 * et utilise la taxonomy 'category' et non pas 'project_category'
 *
 * nous avons étendu également les options : 
 * post__in
 * with_votes (true/false) pour utiliser le systeme de votes kidzou
 *
 * Ajout également d'un filtre de catégories configurable (show_filters = on|off)
 *
 */
function kz_pb_portfolio( $atts ) {
	
	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'fullwidth' => 'on',
			'posts_number' => 10,
			'include_categories' => '',
			'show_title' => 'on',
			'show_categories' => 'on',
			'show_pagination' => 'on',
			'background_layout' => 'light',
			'post__in' => '', //extension kidzou pour afficher un portfolio d'articles 
			'with_votes' => true, //systeme de vote Kidzou, par défaut non affiché
			// 'show_filters' => 'on',
			'show_ad' => 'on',
			'filter' => 'none',
			'orderby' => 'publish_date'
		), $atts
	) );

	global $paged;

	$container_is_closed = false;

	$base_args = array(
		'posts_per_page' => (int) $posts_number,
		'post_type'      => Kidzou::post_types(),
	);

	$args = array();

	switch ($orderby) {
		case 'reco':
			$args = array_merge($base_args, array(
					'meta_key' => Kidzou_Vote::$meta_vote_count,
					'orderby' => array('meta_value_num'=>'DESC'),
				)
			);
			break;

		case 'event_dates':
			$args = array_merge($base_args, array(
					'meta_key' => 'kz_event_start_date' , //kz_event_featured
					'orderby' => array('meta_value' => 'ASC'),
				)
			);
			break;
		
		default:
			$args = array_merge($base_args, array(
					'orderby' => array('date'=>'DESC'),
				)
			);
			break;
	}

	if ( '' !== $post__in )
		$args['post__in'] = explode(",", $post__in);

	$et_paged = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );

	if ( is_front_page() ) {
		$paged = $et_paged;
	}

	if ( '' !== $include_categories )
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'category', //project_category
				'field' => 'id',
				'terms' => explode( ',', $include_categories ),
				'operator' => 'IN',
			)
		);

	if ( ! is_search() ) {
		$args['paged'] = $et_paged;
	}

	ob_start();

	query_posts( $args );

	$categories_included = array();

	$index = 0;
	$inserted = false;

	if ( have_posts() ) {

		while ( have_posts() ) {

			$insert = false;

			//si le précédent post était featured, la pub vient tout de suite...
			if (Kidzou_Events::isFeatured() && !$inserted && $show_ad=='on')
				$insert = true;
			else if ($index==2 && !$inserted && $show_ad=='on')
				$insert = true;

			if ($insert) {

				$inserted = true;

				//insertion de pub
				global $kidzou_options;

				if ( isset($kidzou_options['pub_portfolio']) && trim($kidzou_options['pub_portfolio'])!='') {

					$output = sprintf(
						'<div id="pub_portfolio" class="%1$s" data-content="%3$s">
							%2$s
						</div>',
						'et_pb_portfolio_item kz_portfolio_item ad',
						$kidzou_options['pub_portfolio'],
						__('Publicite','Divi')
					);

					echo $output;

				}
					

			} else {

				the_post(); 

				$categories = get_the_terms( get_the_ID(), 'category' );
				if ( $categories ) {
					foreach ( $categories as $category ) {
						$categories_included[] = $category->term_id;
					}
				}
				?>

				<?php
					$featured = Kidzou_Events::isFeatured();
					$kz_class = 'kz_portfolio_item '.($featured ? 'kz_portfolio_item_featured': '');
				?>

				<div id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_portfolio_item '.$kz_class ); ?>>

					<?php 
					
					$thumb = '';

					$width = ('on' === $fullwidth ?  1080 : ($featured ? 600 : 400)); 
					$height = 'on' === $fullwidth ?  9999 : 284;
					$classtext = 'on' === $fullwidth ? 'et_pb_post_main_image' : '';
					$titletext = get_the_title();
					$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false ); //, 'et-pb-portfolio-image' 
					
					$thumb = $thumbnail["thumb"];

					$event_meta = '';
					$output = '';

					if (Kidzou_Events::isTypeEvent()) {

						$location = Kidzou_Events::getEventDates(get_the_ID());

						$start 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['start_date']);
						$end 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['end_date']);
						$formatted = '';
						
						//bon OK c'est un hack pour régler un pb d'affichage
						//la date de fin s'affiche au lendemain de la date souhaitée
						$end->sub(new DateInterval('PT1H'));
						
						$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
						$formatter->setPattern('EEEE dd MMMM');

						if ($start->format("Y-m-d") == $end->format("Y-m-d"))
							$formatted = __( 'Le ', 'Divi' ).$formatter->format($start);
						else
							$formatted = __( 'Du ','Divi').$formatter->format($start).__(' au ','Divi').$formatter->format($end);
					
					 	$event_meta = '<div class="portfolio_dates"><i class="fa fa-calendar"></i>'.$formatted.'</div>'; 
					
					} 

					if ( '' !== $thumb ) : ?>
						
						<?php

						if ( $featured ) {

							$fb = '';
							// if ( shortcode_exists( 'easy-social-share' ) )
							// 	$fb = do_shortcode('[easy-share buttons="facebook" template="tiny-retina" counters=1 counter_pos="hidden" native="no" hide_total="yes" facebook_text="Facebook"]');

							$output = sprintf("<div class='kz_portfolio_featured_hover'>
													%s 
													<a href='%s'><h2>%s</h2></a>
													%s
													%s
													%s
												</div>",
									Kidzou_Vote::get_vote_template(get_the_ID(), 'font-2x', false, false),
									get_permalink(),
									get_the_title(),
									kz_get_post_meta(),
									$event_meta,
									$fb);
							
						} else if ( $with_votes ) {
							$output = Kidzou_Vote::get_vote_template(get_the_ID(), 'hovertext votable_template', false, false);
						}

						$image = print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height , '', false); //pas d'echo 

						if ($featured) {

							echo sprintf("
									
										%s <a href='%s'>%s</a>								
								
								",
								$output,
								get_permalink(),
								$image
								);

						} else if ( 'on' !== $fullwidth ) { 
							echo sprintf("
									<a href='%s'>
										<span class='et_portfolio_image'>
											%s %s
											<span class='et_overlay'></span>
										</span><!--  et_portfolio_image -->
									</a>
								",
								get_permalink(),
								$output,
								$image
								);

						} 
						?>

				<?php
					endif;
				?>

					<?php if ( 'on' === $show_title && !$featured) : ?>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php endif; ?>

					<?php if ( 'on' === $show_categories && !$featured ) : ?>
						<p class="post-meta"><?php echo get_the_term_list( get_the_ID(), 'category', '', ', ' ); ?></p>
					<?php endif; ?>

					<?php if (!$featured) echo $event_meta; ?>

				</div> <!-- .et_pb_portfolio_item -->

<?php
			//fin de test sur $index
			}

			$index++;

		//fin de boucle while
		}

		if ( 'on' === $show_pagination && ! is_search() ) {
			echo '</div> <!-- .et_pb_portfolio -->';

			$container_is_closed = true;

			if ( function_exists( 'wp_pagenavi' ) )
				wp_pagenavi();
			else
				get_template_part( 'includes/navigation', 'index' );
		}

		wp_reset_query();
	} else {
		get_template_part( 'includes/no-results', 'index' );
	}

	$posts = ob_get_contents();

	ob_end_clean();

	$class = " et_pb_bg_layout_{$background_layout}";

	$filters_html = '';
	$category_filters = '';
	// echo $module_class." ".stristr($module_class,'nofilter');
	if ($filter!='none' ) {

		$terms = get_terms( $filter ); //, $terms_args 

		$category_filters = '<ul class="clearfix">';
		
		foreach ( $terms as $term  ) {
			$category_filters .= sprintf( '<li class="et_pb_portfolio_filter"><a href="%3$s" title="%4$s">%2$s</a></li>',
				esc_attr( $term->slug ),
				esc_html( $term->name ),
				get_term_link( $term, $filter ),
				__('Voir tous les articles dans ').$term->name
			);
		}
		$category_filters .= '</ul>';

		$filters_html = '<div class="et_pb_portfolio_filters clearfix">%7$s</div><!-- .et_pb_portfolio_filters -->';
	}
		

	$output = sprintf(
		'<div%5$s class="%1$s%3$s%6$s">
			<div class="et_pb_filterable_portfolio ">
				'.$filters_html.'
			</div>
			%2$s
		%4$s',
		( 'on' === $fullwidth ? 'et_pb_portfolio' : 'et_pb_portfolio_grid clearfix' ),
		$posts,
		esc_attr( $class ),
		( ! $container_is_closed ? '</div> <!-- .et_pb_portfolio -->' : '' ),
		( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
		( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
		$category_filters
	);

	return $output;
	
}

function kz_pb_filterable_portfolio( $atts ) {
	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'fullwidth' => 'on',
			'posts_number' => 10,
			'include_categories' => '',
			'show_title' => 'on',
			'show_categories' => 'on',
			'show_pagination' => 'on',
			'background_layout' => 'light',
		), $atts
	) );

	wp_enqueue_script( 'jquery-masonry-3' );
	wp_enqueue_script( 'hashchange' );

	$args = array();

	if( 'on' === $show_pagination ) {
		$args['nopaging'] = true;
	} else {
		$args['posts_per_page'] = (int) $posts_number;
	}

	if ( '' !== $include_categories ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'category',
				'field' => 'id',
				'terms' => explode( ',', $include_categories ),
				'operator' => 'IN',
			)
		);
	}

	$projects = get_portfolio_items( $args );

	$categories_included = array();
	ob_start();
	if( $projects->post_count > 0 ) {
		while ( $projects->have_posts() ) {
			$projects->the_post();

			$category_classes = array();
			$categories = get_the_terms( get_the_ID(), 'category' );
			if ( $categories ) {
				foreach ( $categories as $category ) {
					$category_classes[] = 'project_category_' . $category->slug;
					$categories_included[] = $category->term_id;
				}
			}

			$category_classes = implode( ' ', $category_classes );

			?>
			<div id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_portfolio_item kz_portfolio_item ' . $category_classes ); ?>>
			<?php
				$thumb = '';

				$width = 'on' === $fullwidth ?  1080 : 400;
				$width = (int) apply_filters( 'et_pb_portfolio_image_width', $width );

				$height = 'on' === $fullwidth ?  9999 : 284;
				$height = (int) apply_filters( 'et_pb_portfolio_image_height', $height );
				$classtext = 'on' === $fullwidth ? 'et_pb_post_main_image' : '';
				$titletext = get_the_title();
				$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'et-pb-portfolio-image' );
				$thumb = $thumbnail["thumb"];

				if ( '' !== $thumb ) : ?>
					<a href="<?php the_permalink(); ?>">
					<?php if ( 'on' !== $fullwidth ) : ?>
						<span class="et_portfolio_image">
					<?php endif; ?>
						<?php Kidzou_Vote::vote(get_the_ID(), 'hovertext votable_template'); ?>
						<?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
					<?php if ( 'on' !== $fullwidth ) : ?>
							<span class="et_overlay"></span>
						</span>
					<?php endif; ?>
					</a>
			<?php
				endif;
			?>

			<?php if ( 'on' === $show_title ) : ?>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php endif; ?>

			
			<!-- <p class="comments"><a><i class='fa fa-comment-o'></i>3</a></p> -->
			<?php if ( 'on' === $show_categories ) : ?>
				<p class="post-meta"><?php echo get_the_term_list( get_the_ID(), 'category', '', ', ' ); ?></p> 
			<?php endif; ?>

			</div><!-- .et_pb_portfolio_item -->
			<?php
		}
	}

	$posts = ob_get_clean();

	$categories_included = array_unique( $categories_included );
	$terms_args = array(
		'include' => $categories_included,
		'orderby' => 'name',
		'order' => 'ASC',
	);
	$terms = get_terms( 'category', $terms_args );

	$category_filters = '<ul class="clearfix">';
	$category_filters .= sprintf( '<li class="et_pb_portfolio_filter et_pb_portfolio_filter_all"><a href="#" class="active" data-category-slug="all">%1$s</a></li>',
		esc_html__( 'All', 'Divi' )
	);
	foreach ( $terms as $term  ) {
		$category_filters .= sprintf( '<li class="et_pb_portfolio_filter"><a href="#" data-category-slug="%1$s">%2$s</a></li>',
			esc_attr( $term->slug ),
			esc_html( $term->name )
		);
	}
	$category_filters .= '</ul>';

	$class = " et_pb_bg_layout_{$background_layout}";

	$output = sprintf(
		'<div%5$s class="et_pb_filterable_portfolio %1$s%4$s%6$s" data-posts-number="%7$d">
			<div class="et_pb_portfolio_filters clearfix">%2$s</div><!-- .et_pb_portfolio_filters -->

			<div class="et_pb_portfolio_items_wrapper %8$s">
				<div class="column_width"></div>
				<div class="gutter_width"></div>
				<div class="et_pb_portfolio_items">%3$s</div><!-- .et_pb_portfolio_items -->
			</div>
			%9$s
		</div> <!-- .et_pb_filterable_portfolio -->',
		( 'on' === $fullwidth ? 'et_pb_filterable_portfolio_fullwidth' : 'et_pb_filterable_portfolio_grid clearfix' ),
		$category_filters,
		$posts,
		esc_attr( $class ),
		( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
		( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
		esc_attr( $posts_number),
		('on' === $show_pagination ? '' : 'no_pagination' ),
		('on' === $show_pagination ? '<div class="et_pb_portofolio_pagination"></div>' : '' )
	);

	return $output;
}

/**
 * Option ajoutée 'post__in' pour formatter les Contextual Related Posts en portfolio
 *
 */
function kz_pb_fullwidth_portfolio( $atts ) {
	extract( shortcode_atts( array(
			'title' => '',
			'module_id' => '',
			'module_class' => '',
			'fullwidth' => 'on',
			'include_categories' => '',
			'posts_number' => '',
			'show_title' => 'on',
			'show_date' => 'on',
			'background_layout' => 'light',
			'auto' => 'off',
			'auto_speed' => 7000,
			'post__in' => ''
		), $atts
	) );

	$args = array();
	if ( is_numeric( $posts_number ) && $posts_number > 0 ) {
		$args['posts_per_page'] = $posts_number;
	} else {
		$args['nopaging'] = true;
	}

	if ( '' !== $include_categories ) {
		// $args['tax_query'] = array(
		// 	array(
		// 		'taxonomy' => 'category',
		// 		'field' => 'id',
		// 		'terms' => explode( ',', $include_categories ),
		// 		'operator' => 'IN'
		// 	)
		// );
		$args['category__in'] = explode( ',', $include_categories );
	}

	// Kidzou_Utils::log($args);

	$projects = get_portfolio_items( $args );

	ob_start();
	
	format_fullwidth_portolio_items($projects, $show_title, $show_date);

	$posts = ob_get_clean();

	$output = format_fullwidth_portfolio($background_layout, $fullwidth, $posts, $module_id, $module_class, $auto, $auto_speed, $title);

	return $output;
}

/**
 * utilisé par kz_pb_fullwidth_portfolio et par les archives
 *
 */
function format_fullwidth_portolio_items($projects, $show_title = "on", $show_date = "on") {

	// print_r($projects);

	if( $projects->post_count > 0 ) {

		//echo 'count :' .$projects->post_count;

		while ( $projects->have_posts() ) {

			$projects->the_post();
			?>
			<div id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_portfolio_item ' ); ?>>
			<?php
				$thumb = '';

				$width = 320;
				$width = (int) apply_filters( 'et_pb_portfolio_image_width', $width );

				$height = 241;
				$height = (int) apply_filters( 'et_pb_portfolio_image_height', $height );

				list($thumb_src, $thumb_width, $thumb_height) = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), array( $width, $height ) );

				$orientation = ( $thumb_height > $thumb_width ) ? 'portrait' : 'landscape';

				if ( '' !== $thumb_src ) : ?>
					<div class="et_pb_portfolio_image <?php esc_attr_e( $orientation ); ?>">

						<a href="<?php the_permalink(); ?>">
							
							<img src="<?php esc_attr_e( $thumb_src); ?>" alt="<?php esc_attr_e( get_the_title() ); ?>"/>
							<div class="meta">
								<span class="et_overlay"></span>
								<?php if ( 'on' === $show_title ) : ?>
									<h3><?php the_title(); ?></h3>
								<?php endif; ?>

								<?php if ( 'on' === $show_date ) : ?>
									<p class="post-meta"><?php echo get_the_date(); ?></p>
								<?php endif; ?>
							</div>
						</a>
					</div>
			<?php endif; ?>
			</div>
			<?php
		}
	}

}

function format_fullwidth_portfolio ($background_layout, $fullwidth, $posts, $module_id, $module_class, $auto, $auto_speed, $title) {

	$class = " et_pb_bg_layout_{$background_layout}";

	$output = sprintf(
		'<div%4$s class="et_pb_fullwidth_portfolio %1$s%3$s%5$s" data-auto-rotate="%6$s" data-auto-rotate-speed="%7$s">
			%8$s
			<div class="et_pb_portfolio_items clearfix" data-columns="">
				%2$s
			</div><!-- .et_pb_portfolio_items -->
		</div> <!-- .et_pb_fullwidth_portfolio -->',
		( 'on' === $fullwidth ? 'et_pb_fullwidth_portfolio_carousel' : 'et_pb_fullwidth_portfolio_grid clearfix' ),
		$posts,
		esc_attr( $class ),
		( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
		( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
		( '' !== $auto && in_array( $auto, array('on', 'off') ) ? esc_attr( $auto ) : 'off' ),
		( '' !== $auto_speed && is_numeric( $auto_speed ) ? esc_attr( $auto_speed ) : '7000' ),
		( '' !== $title ? sprintf( '<h2>%s</h2>', esc_html( $title ) ) : '' )
	);

	return $output;

}

/**
 * en remplacement de get_portfolio_projects dans le theme parent qui ne requete que des CT dy type "project"
 * utilisé dans les shortcodes ci-dessus 
 *
 */
function get_portfolio_items( $args = array() ) {

	$default_args = array(
		'post_type' => Kidzou::post_types(),
		'tax_query' => array(
			Kidzou_Geo::get_query_args()
		),

	);

	$args = wp_parse_args( $args, $default_args );

	$q = new WP_Query( $args );

	return $q;

}

/**
 * Adaptation du parent pour tenir compte des post types sur lesquels kidzou utilise le pagebuilder
 * 
 */
function et_single_settings_meta_box( $post ) {
	$post_id = get_the_ID();

	wp_nonce_field( basename( __FILE__ ), 'et_settings_nonce' );

	$page_layout = get_post_meta( $post_id, '_et_pb_page_layout', true );

	$page_layouts = array(
		'et_right_sidebar'   => __( 'Right Sidebar', 'Divi' ),
   		'et_left_sidebar'    => __( 'Left Sidebar', 'Divi' ),
   		'et_full_width_page' => __( 'Full Width', 'Divi' ),
	);

	$layouts        = array(
		'light' => __( 'Light', 'Divi' ),
		'dark'  => __( 'Dark', 'Divi' ),
	);
	$post_bg_color  = ( $bg_color = get_post_meta( $post_id, '_et_post_bg_color', true ) ) && '' !== $bg_color
		? $bg_color
		: '#ffffff';
	$post_use_bg_color = get_post_meta( $post_id, '_et_post_use_bg_color', true )
		? true
		: false;
	$post_bg_layout = ( $layout = get_post_meta( $post_id, '_et_post_bg_layout', true ) ) && '' !== $layout
		? $layout
		: 'light'; ?>

	<p class="et_pb_page_settings et_pb_page_layout_settings">
		<label for="et_pb_page_layout" style="display: block; font-weight: bold; margin-bottom: 5px;"><?php esc_html_e( 'Page Layout', 'Divi' ); ?>: </label>

		<select id="et_pb_page_layout" name="et_pb_page_layout">
		<?php
		foreach ( $page_layouts as $layout_value => $layout_name ) {
			printf( '<option value="%2$s"%3$s>%1$s</option>',
				esc_html( $layout_name ),
				esc_attr( $layout_value ),
				selected( $layout_value, $page_layout )
			);
		} ?>
		</select>
	</p>
<?php if ( in_array( $post->post_type, array_merge( array('page', 'project' ), Kidzou::post_types() ) ) ) : ?>
	<p class="et_pb_page_settings" style="display: none;">
		<input type="hidden" id="et_pb_use_builder" name="et_pb_use_builder" value="<?php echo esc_attr( get_post_meta( $post_id, '_et_pb_use_builder', true ) ); ?>" />
		<textarea id="et_pb_old_content" name="et_pb_old_content"><?php echo esc_attr( get_post_meta( $post_id, '_et_pb_old_content', true ) ); ?></textarea>
	</p>
<?php endif; ?>

<?php if ( 'post' === $post->post_type ) : ?>
	<p class="et_divi_quote_settings et_divi_audio_settings et_divi_link_settings et_divi_format_setting">
		<label for="et_post_use_bg_color" style="display: block; font-weight: bold; margin-bottom: 5px;">
			<input name="et_post_use_bg_color" type="checkbox" id="et_post_use_bg_color" <?php checked( $post_use_bg_color ); ?> />
			<?php esc_html_e( 'Use Background Color', 'Divi' ); ?></label>
	</p>

	<p class="et_post_bg_color_setting et_divi_format_setting">
		<label for="et_post_bg_color" style="display: block; font-weight: bold; margin-bottom: 5px;"><?php esc_html_e( 'Background Color', 'Divi' ); ?>: </label>
		<input id="et_post_bg_color" name="et_post_bg_color" class="color-picker-hex" type="text" maxlength="7" placeholder="<?php esc_attr_e( 'Hex Value', 'Divi' ); ?>" value="<?php echo esc_attr( $post_bg_color ); ?>" data-default-color="#ffffff" />
	</p>

	<p class="et_divi_quote_settings et_divi_audio_settings et_divi_link_settings et_divi_format_setting">
		<label for="et_post_bg_layout" style="font-weight: bold; margin-bottom: 5px;"><?php esc_html_e( 'Text Color', 'Divi' ); ?>: </label>
		<select id="et_post_bg_layout" name="et_post_bg_layout">
	<?php
		foreach ( $layouts as $layout_name => $layout_title )
			printf( '<option value="%s"%s>%s</option>',
				esc_attr( $layout_name ),
				selected( $layout_name, $post_bg_layout, false ),
				esc_html( $layout_title )
			);
	?>
		</select>
	</p>
<?php endif;

}

/**
 * surcharge du shortcode pour pouvoir l'inclure dans un tab (map_inside)
 *
 */
function kz_pb_map( $atts, $content = '' ) {
	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'address_lat' => '',
			'address_lng' => '',
			'zoom_level' => 18
		), $atts
	) );

	if (!wp_script_is( 'google-maps-api', 'enqueued' )) 
		wp_enqueue_script( 'google-maps-api' );

	$all_pins_content = do_shortcode( et_pb_fix_shortcodes( $content ) );

	$output = sprintf(
		'<div class="et_pb_map_container">
			<div class="et_pb_map map_inside" data-center-lat="%1$s" data-center-lng="%2$s" data-zoom="%3$d"></div>
			%4$s
		</div>',

		esc_attr( $address_lat ),
		esc_attr( $address_lng ),
		esc_attr( $zoom_level ),
		$all_pins_content
	);

	return $output;
}



/**
 * surcharge du parent pour les galleries d'image dans les posts (utilisation du format post_gallery)
 *
 */
function et_gallery_images() {
	$output = $images_ids = '';

	if ( function_exists( 'get_post_galleries' ) ) {
		$galleries = get_post_galleries( get_the_ID(), false );

		if ( empty( $galleries ) ) return false;

		foreach ( $galleries as $gallery ) {
			// Grabs all attachments ids from one or multiple galleries in the post
			$images_ids .= ( '' !== $images_ids ? ',' : '' ) . $gallery['ids'];
		}

		$attachments_ids = explode( ',', $images_ids );
		// Removes duplicate attachments ids
		$attachments_ids = array_unique( $attachments_ids );
	} else {
		$pattern = get_shortcode_regex();
		preg_match( "/$pattern/s", get_the_content(), $match );
		$atts = shortcode_parse_atts( $match[3] );

		if ( isset( $atts['ids'] ) )
			$attachments_ids = explode( ',', $atts['ids'] );
		else
			return false;
	}

	$slides = '';

	foreach ( $attachments_ids as $attachment_id ) {
		$attachment_attributes = wp_get_attachment_image_src( $attachment_id, 'et-pb-post-main-image-fullwidth' );
		$attachment_image = ! is_single() ? $attachment_attributes[0] : wp_get_attachment_image( $attachment_id, 'post_gallery' ); 

		if ( ! is_single() ) {
			$slides .= sprintf(
				'<div class="et_pb_slide" style="background: url(%1$s);"></div>',
				esc_attr( $attachment_image )
			);
		} else {
			$full_image = wp_get_attachment_image_src( $attachment_id, 'full' );
			$full_image_url = $full_image[0];
			$attachment = get_post( $attachment_id );

			$slides .= sprintf(
				'<li class="et_gallery_item post_gallery_item">
					<a href="%1$s" title="%3$s">
						<span class="et_portfolio_image">
							%2$s
							<span class="et_overlay"></span>
						</span>
					</a>
				</li>',
				esc_url( $full_image_url ),
				$attachment_image,
				esc_attr( $attachment->post_title )
			);
		}
	}

	if ( ! is_single() ) {
		$output =
			'<div class="et_pb_slider et_pb_slider_fullwidth_off">
				<div class="et_pb_slides">
					%1$s
				</div>
			</div>';
	} else {
		$output =
			'<ul class="et_post_gallery clearfix">
				%1$s
			</ul>';
	}

	printf( $output, $slides );
}

/**
 * inclusion des custom taxonomies de Kidzou
 *
 * @return void
 * @author 
 **/
function et_postinfo_meta( $postinfo, $date_format, $comment_zero, $comment_one, $comment_more ){
	global $themename;

	$postinfo_meta = '';

	if ( in_array( 'author', $postinfo ) )
		$postinfo_meta .= ' ' . esc_html__('by',$themename) . ' ' . et_get_the_author_posts_link();

	if ( in_array( 'date', $postinfo ) ) {
		if ( in_array( 'author', $postinfo ) ) $postinfo_meta .= ' | ';
		$postinfo_meta .= get_the_time( $date_format );
	}

	if ( in_array( 'categories', $postinfo ) ){
		if ( in_array( 'author', $postinfo ) || in_array( 'date', $postinfo ) ) $postinfo_meta .= ' | ';
		$postinfo_meta .= get_the_category_list(', ');
		global $post;
		$terms_d = wp_get_post_terms( $post->ID, 'divers' );
		$terms_a = wp_get_post_terms( $post->ID, 'age' );
		$terms = array_merge($terms_d, $terms_a);
		foreach ($terms as $term) {
			$term_link = get_term_link( $term );
   
		    // If there was an error, continue to the next term.
		    if ( is_wp_error( $term_link ) ) {
		        continue;
		    }
			$postinfo_meta .= ', <a href="' . esc_url( $term_link ) . '">'.$term->name.'</a>';
		}
	}

	if ( in_array( 'comments', $postinfo ) ){
		if ( in_array( 'author', $postinfo ) || in_array( 'date', $postinfo ) || in_array( 'categories', $postinfo ) ) 
			$postinfo_meta .= ' <br/><i class="fa fa-comments-o"></i> ';
		$postinfo_meta .= et_get_comments_popup_link( $comment_zero, $comment_one, $comment_more );
	}

	echo $postinfo_meta;
}

function kz_postinfo_meta( $postinfo, $date_format, $comment_zero, $comment_one, $comment_more ){
	global $themename;

	$postinfo_meta = '';

	if ( in_array( 'author', $postinfo ) )
		$postinfo_meta .= ' ' . esc_html__('by',$themename) . ' ' . et_get_the_author_posts_link();

	if ( in_array( 'date', $postinfo ) ) {
		if ( in_array( 'author', $postinfo ) ) $postinfo_meta .= ' | ';
		$postinfo_meta .= get_the_time( $date_format );
	}

	if ( in_array( 'categories', $postinfo ) ){
		if ( in_array( 'author', $postinfo ) || in_array( 'date', $postinfo ) ) $postinfo_meta .= ' | ';
		$postinfo_meta .= get_the_category_list(', ');
	}

	if ( in_array( 'comments', $postinfo ) ){
		if ( in_array( 'author', $postinfo ) || in_array( 'date', $postinfo ) || in_array( 'categories', $postinfo ) ) $postinfo_meta .= ' | ';
		$postinfo_meta .= et_get_comments_popup_link( $comment_zero, $comment_one, $comment_more );
	}

	return $postinfo_meta;
}

function kz_get_post_meta() {
	$postinfo = is_single() ? et_get_option( 'divi_postinfo2' ) : et_get_option( 'divi_postinfo1' );
	$out = '';

	if ( $postinfo ) :
		$out .= '<p class="post-meta">';
		$out .= kz_postinfo_meta( $postinfo, et_get_option( 'divi_date_format', 'M j, Y' ), esc_html__( '0 comments', 'Divi' ), esc_html__( '1 comment', 'Divi' ), '% ' . esc_html__( 'comments', 'Divi' ) );
		$out .= '</p>';
	endif;

	return $out;
}




?>