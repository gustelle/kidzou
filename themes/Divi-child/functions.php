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
 * shortcodes spécifiques Kidzou et surcharge des shortcodes Divi
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
    // add_shortcode('kz_pb_blog','kz_pb_blog');
    add_shortcode('kz_pb_portfolio','kz_pb_portfolio');
    add_shortcode('kz_pb_fullwidth_portfolio','kz_pb_fullwidth_portfolio');
    add_shortcode('kz_pb_filterable_portfolio','kz_pb_filterable_portfolio');
    add_shortcode('searchbox','searchbox');
    add_shortcode('kz_pb_user_favs','kz_pb_user_favs');
    

    remove_shortcode('et_pb_fullwidth_map');
    remove_shortcode('et_pb_map');
    add_shortcode( 'et_pb_fullwidth_map', 'kz_pb_map' );
	add_shortcode( 'et_pb_map', 'kz_pb_map' );

	//nouveau shortcode pour afficher tous les lieux sur une carte plein écran
	add_shortcode( 'kz_pb_fullwidth_map', 'kz_pb_fullwidth_map' );

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

	//ajout de CSS
	add_action( 'wp_enqueue_scripts', 'kz_divi_load_styles' ,100);

	//Alterer les queries des archives : pas de limite / pas de paging
	add_action( "pre_get_posts", "filter_archive_query" );

	//gestion de l'habillage publicitaire
	//passer en dernier pour retirer et_fixed_nav pour rendre la barre de header floattante
	add_filter( 'body_class', 'kz_add_class_habillage', 100 ); 

	//optimisation de performance
	//pas besoin de passer par cette fonction, les css sont dans style.css
	remove_action( 'wp_head', 'et_divi_add_customizer_css' );

	//ajout d'un menu pour switcher de metropole
	add_action('kz_metropole_nav', 'kz_metropole_nav');

	//ne pas afficher le formulaire de login lorsque le user est déjà logué
	remove_shortcode('et_pb_login');
	add_shortcode( 'et_pb_login', 'kz_pb_login' );

	//formulaire de saisie d'événement
	add_shortcode( 'event_form', 'kz_event_form' );

}


function custom_excerpt_length( $length ) {
	return 180;
}

//lors d'un habillage, le header ne peut pas etre fixe
//et le body est doté d'une classe qui permet de contraindre le container
function kz_add_class_habillage( $classes ){

	$is_habillage = ( trim( Kidzou_Utils::get_option('pub_habillage') )!='' );

	if ($is_habillage) {

		$classes[] = 'kz_habillage';
		if (in_array('et_fixed_nav', $classes)) {
			$key = array_search('et_fixed_nav', $classes);
			unset($classes[$key]);
		}
	}
		
	return $classes;
}

function kz_habillage() {

	// global $kidzou_options;
	$is_habillage = ( trim( Kidzou_Utils::get_option('pub_habillage') )!='' );

	if ($is_habillage)
		echo Kidzou_Utils::get_option('pub_habillage');
}


/**
 * Affiche une <select> box qui contient les metropoles gérées dans le système. Lorsque le user sélectionne une métropole, le contenu se rafraichit pour n'aficher que les posts attachés à la métropole sélectionnée
 * * soit l'URI en cours contient une métropole, elle est remplacée (ex: /lille/agenda -> /valenciennes/agenda)
 * * soit elle ne contient pas de métropole, le user est redirigé vers la home (ex: /ma-page -> /lille)
 *
 * @uses Kidzou_Metropole::get_metropoles($bool)
 * @uses Kidzou_Metropole::get_national_metropole()
 * @uses Kidzou_Metropole::get_metropole_uri_regexp()
 * @uses Kidzou_Geolocator
 * 
 * @return HTML
 **/
function  kz_metropole_nav()
{
	//les différentes métropoles dispo
	$active = Kidzou_Utils::get_option('geo_activate', false);
	if ($active)
	{
		$metropoles = Kidzou_Metropole::get_metropoles(); //inclure la métropole "nationale" qui regroupe toutes les métropoles 

		//Affichage par ordre Alpha
		//Attention le tri se fait sur les libéllés (name) et pas les slugs (id technique) car le user ne voit que les libellés
		//ex: m1{ slug:lille, name: Lille } et m2{ slug:littoral, name: Dunkerque } => un tri sur les slugs placerait Lille avant Dunkerque, alors que D < L 
		usort($metropoles, function($m1, $m2) {
			return strcmp ( $m1->name, $m2->name); //comparaison de strings par ordre naturel
		});

		//ajouter en fin de tableau la ville à portée nationale
		$metropoles[] = Kidzou_Metropole::get_national_metropole(array('fields'=>'all'));
			
		$ttes_metros = '';

		if (count($metropoles)>1) 
		{
			$locator = Kidzou_Metropole::get_instance();
			$current_metropole = $locator->get_request_metropole();

			$uri = $_SERVER['REQUEST_URI'];
			$regexp = Kidzou_Metropole::get_metropole_uri_regexp();

			$ttes_metros .= '<select onchange="window.location = this.value;" class="selectBox">';

			$i=0;
			foreach ($metropoles as $m) {

				if (is_object($m)) {
					$selected = ($m->slug == $current_metropole);
					$pattern = '#\/'.$regexp.'\/?#';
					
					if (preg_match($pattern, $uri, $matches)) {
						$new_url = site_url().preg_replace($regexp, $m->slug, $uri);
					} else {
						$new_url = site_url().'/'.$m->slug;
					} 

					$ttes_metros .= sprintf(
						'<option value="%2$s" %1$s>%3$s</option>',
						($selected ? 'selected' : ''),
						$new_url,
						$m->name
					);

					$i++;
				}

			}

			$ttes_metros .= '</select>';
		}

		echo $ttes_metros;	
	}
}

/**
 * Pas de pagination sur les Archives (au sens WP du terme)
 * 
 * @uses is_archive() pour déterminer si la page en cours est une page d'archive
 * @return void
 * @Hook 
 *
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
	wp_enqueue_script( 'kidzou-custom-script',  get_stylesheet_directory_uri().'/js/custom.js', array( 'jquery', 'jquery-ui-autocomplete' ), Kidzou::VERSION, true );

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

function kz_divi_load_styles ()
{
	wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', false, '4.6.3' );
}

/**
 *
 */
function kz_mailchimp_key()
{
	return Kidzou_Utils::get_option('mailchimp_list', '');
}


/**
 *
 * Affiche une [et_pb_section] contenant un formulaire Mailchimp + Related Posts
 *
 * @todo l'affichage des CRP doit être indépendant de Mailchimp...
 *
 **/
function get_post_footer()
{
	$locator = Kidzou_Metropole::get_instance();
	$key = kz_mailchimp_key();

	if ($key!='') {

		$posts_ids_objects = $locator->get_related_posts();
		$ids = array();

		foreach ($posts_ids_objects as $id_object) {
		    $ids[]   = $id_object->ID;
		}
		
		$crp = '';

		if (count($ids)>0)
		{
			$ids_list = implode(',', $ids);	
			$crp = '[et_pb_row]
				<h2>D&apos;autres sorties sympa :</h2>
					[et_pb_column type="4_4"]
						[kz_pb_portfolio admin_label="Portfolio" 	fullwidth="off" 
																	render_featured="off" 
																	render_votes="off" 
																	posts_number="3" 
																	post__in="'.$ids_list.'" 
																	show_title="on" 
																	show_categories="off" 
																	show_pagination="off" 
																	show_filters="off" 
																	background_layout="light" show_ad="off" /]
					[/et_pb_column]
				[/et_pb_row]';
		}

		$out = sprintf('
			[et_pb_section fullwidth="off" specialty="off"]
				%s
				[et_pb_row]
					[et_pb_column type="4_4"]
						[et_pb_signup admin_label="Subscribe" provider="mailchimp" mailchimp_list="'.$key.'" aweber_list="none" title="'.__('Inscrivez-vous à notre Newsletter','Divi').'" button_text="'.__('Inscrivez-vous ','Divi').'" use_background_color="on" background_color="#ed0a71" background_layout="dark" text_orientation="left"]'.__('<p>Nous distribuons la newsletter 1 à 2 fois par mois, elle contient les meilleures recommandations de la communaut&eacute; des parents Kidzou, ainsi que des jeux concours de temps en temps ! </p>','Divi').'[/et_pb_signup]
					[/et_pb_column]
				[/et_pb_row]
			[/et_pb_section]',
			$crp
			);

		echo do_shortcode($out);
	}	
}

/**
 * Le formulaire de login n'est pas affiché qd le user est logué
 */
function kz_pb_login( $atts, $content = null ) 
{
	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'title' => '',
			'background_color' => et_get_option( 'accent_color', '#7EBEC5' ),
			'background_layout' => 'dark',
			'text_orientation' => 'left',
			'use_background_color' => 'on',
			'current_page_redirect' => 'off',
		), $atts
	) );

	$output = '';

	if (!is_user_logged_in()) {

		$redirect_url = 'on' === $current_page_redirect
			? ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
			: '';

		$class = " et_pb_bg_layout_{$background_layout} et_pb_text_align_{$text_orientation}";

		$form = '';

		// if ( !is_user_logged_in() ) {
			$username = __( 'Username', 'Divi' );
			$password = __( 'Password', 'Divi' );

			$form = sprintf( '
				<div class="et_pb_newsletter_form et_pb_login_form">
					<form action="%7$s" method="post">
						<p>
							<label class="et_pb_contact_form_label" for="user_login" style="display: none;">%3$s</label>
							<input id="user_login" placeholder="%4$s" class="input" type="text" value="" name="log" />
						</p>
						<p>
							<label class="et_pb_contact_form_label" for="user_pass" style="display: none;">%5$s</label>
							<input id="user_pass" placeholder="%6$s" class="input" type="password" value="" name="pwd" />
						</p>
						<p class="et_pb_forgot_password"><a href="%2$s">%1$s</a></p>
						<p>
							<button type="submit" class="et_pb_newsletter_button">%8$s</button>
							%9$s
						</p>
					</form>
				</div>',
				__( 'Forgot your password?', 'Divi' ),
				esc_url( wp_lostpassword_url() ),
				esc_html( $username ),
				esc_attr( $username ),
				esc_html( $password ),
				esc_attr( $password ),
				esc_url( site_url( 'wp-login.php' ) ),
				__( 'Login', 'Divi' ),
				( 'on' === $current_page_redirect
					? sprintf( '<input type="hidden" name="redirect_to" value="%1$s" />',  $redirect_url )
					: ''
				)
			);
		// }

		$output = sprintf(
			'<div%6$s class="et_pb_newsletter et_pb_login clearfix%4$s%7$s"%5$s>
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
			( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
		);
	}

	return $output;
}

/**
 * Le formulaire de saisie d'événement est un composant React
 *
 * Le user est redirigé si il n'est pas logué
 */
function kz_event_form( $atts, $content = null ) 
{

	$form = '';

    if (!is_user_logged_in()) {

        $form = sprintf(
        	do_shortcode('[et_pb_section]
        		[et_pb_row]
        			[et_pb_column type="1_2"]
        				[et_pb_text admin_label="Text" background_layout="light" text_orientation="left"]<h2>Vous devez vous connecter pour consulter ce contenu !</h2>[/et_pb_text]
        				[et_pb_text admin_label="Text" background_layout="light" text_orientation="left"]<p>Connectez-vous par votre r&eaucte;seau social favori : </p>[TheChamp-Login][/et_pb_text]
        			[/et_pb_column]
        			[et_pb_column type="1_2"]
        				[et_pb_login admin_label="Login" title="Connexion" current_page_redirect="off" use_background_color="off" background_color="#1ea4e8" background_layout="light" text_orientation="left"]
							Vous pouvez utiliser un identifiant Kidzou ou ré-utiliser vos identifiants Facebook ou Google+
						[/et_pb_login]
					[/et_pb_column]
				[/et_pb_row]
			[/et_pb_section]')
		);
    } else {

    	wp_enqueue_script('react',			'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js',			array('classnames'), '0.14.7', true);
		wp_enqueue_script('react-dom',		'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js',		array('react'), '0.14.7', true);	
		wp_enqueue_script('classnames',		'https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js',		array(), '2.2.3', true);
		wp_enqueue_script('moment',			'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js',	array('jquery'), '2.11.2', true);
		wp_enqueue_script('moment-locale',	'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js',		array('moment'), '2.11.2', true);
		wp_enqueue_script('tweenmax',		'https://cdnjs.cloudflare.com/ajax/libs/gsap/1.18.2/TweenMax.min.js',		array(), '1.18.2', true);
		wp_enqueue_script('fbImport', 		get_stylesheet_directory_uri().'/js/fbImport.js', 							array(), Kidzou::VERSION, true); 
		wp_enqueue_script('progressButton', get_stylesheet_directory_uri().'/js/lib/progressButton.js', 				array('react-dom'), Kidzou::VERSION, true); 
		wp_enqueue_script('dropZone',		get_stylesheet_directory_uri().'/js/lib/dropZone.js', 						array('react-dom'), Kidzou::VERSION, true); 
		wp_enqueue_script('daypicker-locale-utils', get_stylesheet_directory_uri().'/js/lib/react-DayPicker-LocaleUtils.js', array('moment' ), '1.0', true);
		wp_enqueue_script('daypicker-date-utils', 	get_stylesheet_directory_uri().'/js/lib/react-DayPicker-DateUtils.js',  array( ), '1.0', true);
		wp_enqueue_script('daypicker', 		get_stylesheet_directory_uri().'/js/lib/react-DayPicker.js' , 		array( 'daypicker-locale-utils', 'daypicker-date-utils', 'react-dom'), '1.0', true);
		wp_enqueue_script('radio-group', 	get_stylesheet_directory_uri().'/js/lib/react-radio-group.js', 		array('react-dom'), '1.0', true);
		wp_enqueue_script('geosuggest', 	get_stylesheet_directory_uri().'/js/lib/react-geosuggest.min.js', 	array('react-dom', 'google-maps'), '1.0', true);
		wp_enqueue_script('google-maps', 	"https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

		wp_enqueue_script('reactForm', 		get_stylesheet_directory_uri().'/js/reactForm.js' ,				array('react-dom'), Kidzou::VERSION, true);			
		wp_enqueue_script( 'eventForm',  	get_stylesheet_directory_uri().'/js/eventForm.js', 				array( 'react-dom', 'progressButton', 'dropZone', 'daypicker', 'radio-group' ), Kidzou::VERSION, true );

		wp_localize_script('fbImport', 'import_jsvars', array(
				'facebook_appId'		=> Kidzou_Utils::get_option('fb_app_id',''),
				'facebook_appSecret'	=> Kidzou_Utils::get_option('fb_app_secret',''),
				'api_create_post'		=> site_url()."/api/content/create_post/",
				'api_create_post_nonce'	=> site_url().'/api/get_nonce/?controller=content&method=create_post',
				'api_key'				=> Kidzou_Utils::get_option('api_public_key')[0],
			)
		);
		
		wp_enqueue_style('progressButton', 	get_stylesheet_directory_uri().'/js/lib/css/progressButton.css', array(), Kidzou::VERSION);
		wp_enqueue_style('daypicker', 		get_stylesheet_directory_uri().'/js/lib/css/react-DayPicker.css', array(), Kidzou::VERSION);
		wp_enqueue_style('geosuggest', 		get_stylesheet_directory_uri().'/js/lib/css/geosuggest.css', array(), Kidzou::VERSION);

		////////////////////////////////////////////////////////////////////
		///
		/// Reactisation du bazar
		///
		////////////////////////////////////////////////////////////////////

		if (!class_exists('ReactJS'))
			include get_stylesheet_directory().'/includes/react/ReactJS.php';

		$react = new ReactJS(
		  	// location of React's code and dependencies
			file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js').
			file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js').
			file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom-server.min.js').
			file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js').
			file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js').
			file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js'),
			// app code
			file_get_contents('js/lib/dropZone.js', 					FILE_USE_INCLUDE_PATH).
			file_get_contents('js/lib/progressButton.js', 				FILE_USE_INCLUDE_PATH).
			file_get_contents('js/lib/react-DayPicker-LocaleUtils.js', 	FILE_USE_INCLUDE_PATH).
			file_get_contents('js/lib/react-DayPicker-DateUtils.js', 	FILE_USE_INCLUDE_PATH).
			file_get_contents('js/lib/react-radio-group.js', 			FILE_USE_INCLUDE_PATH).
			file_get_contents('js/eventForm.js', 						FILE_USE_INCLUDE_PATH).
			file_get_contents('js/reactForm.js', 						FILE_USE_INCLUDE_PATH).
			file_get_contents('js/lib/react-geosuggest.min.js',			FILE_USE_INCLUDE_PATH).
			file_get_contents('js/lib/react-DayPicker.js', 				FILE_USE_INCLUDE_PATH)
		);
		
	 	////////////////////////////////////////////////////////////////////
	 	////////////////////////////////////////////////////////////////////
	 	//////////////////////////////////////////////////////////////////// 

		$data = array();
		$react->setComponent('EventForm', $data);

		$form = sprintf('<div id="react_event_form">%1s</div>',
			$react->getMarkup()
		);

		$footer_script = $react->getJS('#react_event_form', "EventForm");
			 	
	 	//injecter les scripts en footer pour éviter de polluer le HTML
	 	add_action( 'wp_footer', function() use ($footer_script) { 
	 		echo '<script>'.$footer_script.'</script>';
	 	}, 999 );
    } 

    return $form;
}

/**
 * le formulaire de souscription newsletter, à la sauce Kidzou (avec le codepostal)
 *
 */
function kz_pb_signup( $atts, $content = null ) 
{

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

			// $mailchimp_api_key = et_get_option( 'divi_mailchimp_api_key' );
			$mailchimp_api_key = Kidzou_Utils::get_option('mailchimp_key', '');

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



/** 
 * Rendu d'un portfolio de posts via ReactJS executé sur le serveur par V8JS
 *
 * @param $postList Array
 * @param $show_ad on|off rendu ou non d'une pub
 * @param $render_votes rendu ou non de l'icone de vote et du nb de votes
 */
function render_react_portfolio($show_ad = false, $posts = array(), $animate=true, $render_votes = true, $show_categories = true) {

	wp_enqueue_script('react',			'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js',			array('classnames'), '0.14.7', true);
	wp_enqueue_script('react-dom',		'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js',		array('react'), '0.14.7', true);	
	wp_enqueue_script('classnames',		'https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js',		array(), '2.2.3', true);
	wp_enqueue_script('moment',			'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js',	array('jquery'), '2.11.2', true);
	wp_enqueue_script('moment-locale',	'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js',		array('moment'), '2.11.2', true);
	wp_enqueue_script('tweenmax',		'https://cdnjs.cloudflare.com/ajax/libs/gsap/1.18.2/TweenMax.min.js',		array(), '1.18.2', true);

	wp_enqueue_script( 'storage', plugins_url( ).'/kidzou-4/assets/js/kidzou-storage.js', array( ), Kidzou::VERSION, true); // 'ko', 'ko-mapping'

	////////////////////////////////////////////////////////////////////
	///
	/// Reactisation du bazar
	///
	////////////////////////////////////////////////////////////////////

	if (!class_exists('ReactJS'))
		include get_stylesheet_directory().'/includes/react/ReactJS.php';

	$react = new ReactJS(
	  	// location of React's code and dependencies
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom-server.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js'),
		// file_get_contents(WP_CONTENT_DIR.'/../wp-includes/js/jquery/jquery.js', FILE_USE_INCLUDE_PATH),
		// app code
		file_get_contents('js/portfolio.js', FILE_USE_INCLUDE_PATH)
	);
	
	wp_enqueue_script( 'portfolio-components',  get_stylesheet_directory_uri().'/js/portfolio.js', array( 'react-dom', 'storage' ), Kidzou::VERSION, true );

 	////////////////////////////////////////////////////////////////////
 	////////////////////////////////////////////////////////////////////
 	//////////////////////////////////////////////////////////////////// 

	$data = array();
 	$data['apis'] 		= array('getVotes'		=> site_url().'/api/vote/get_votes_status/',
								'voteUp'		=> site_url().'/api/vote/up/',
								'voteDown'		=> site_url().'/api/vote/down/',
								'userVotes'  	=> site_url().'/api/vote/get_votes_user/',
								'getNonce'		=> site_url().'/api/get_nonce/');

 	$data['current_user_id'] = (is_user_logged_in() ? get_current_user_id() : 0);

 	$data['posts'] 		= array();
 	$data['ad']			= Kidzou_Utils::get_option('pub_portfolio');
 	$data['show_ad']	= $show_ad;
 	$data['animate']			= $animate;
 	$data['render_votes']		= $render_votes;
 	$data['show_categories'] 	= $show_categories;
 	
 	global $post;

 	foreach ($posts as $post){
 		
 		setup_postdata($post);

 		$width 		= Kidzou_Featured::isFeatured() ? 600 : 400; 
		$height 	= 284;
		$classtext 	= '';
		$titletext 	= get_the_title();
		$thumbnail 	= get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false ); //, 'et-pb-portfolio-image' 

    	$data['posts'][] = array( 
					'ID' 		=> $post->ID,
					'title'		=> $post->post_title,
					'slug'		=> $post->post_name,
					'permalink'	=> get_the_permalink(),
					'featured'	=> Kidzou_Featured::isFeatured(),
					'location' 	=> Kidzou_Geoloc::get_post_location(),
					'dates' 	=> Kidzou_Events::getEventDates(),
					'thumbnail'	=> print_thumbnail($thumbnail , $thumbnail["use_timthumb"], $titletext, $width, $height , '', false ),
					'excerpt'	=> get_the_excerpt(),
					'terms'		=> get_the_term_list( get_the_ID(), "category", '', ', ' ),
					'post_meta'	=> kz_get_post_meta());
	}

	$react->setComponent('Portfolio', $data);

	printf("<div id='react_ptf'>%1s</div>",
		$react->getMarkup()
	);

	$footer_script = $react->getJS('#react_ptf', "Portfolio");
		 	
 	//injecter les scripts en footer pour éviter de polluer le HTML
 	add_action( 'wp_footer', function() use ($footer_script) { 
 		echo '<script>'.$footer_script.'</script>';
 	}, 999 );
}


/** 
 * Rendu d'un 'coeur' de vote sur un single
 *
 */
function kz_vote_single() {

	wp_enqueue_script('react',			'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js',			array('classnames'), '0.14.7', true);
	wp_enqueue_script('react-dom',		'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js',		array('react'), '0.14.7', true);	
	wp_enqueue_script('classnames',		'https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js',		array(), '2.2.3', true);
	wp_enqueue_script('tweenmax',		'https://cdnjs.cloudflare.com/ajax/libs/gsap/1.18.2/TweenMax.min.js',		array(), '1.18.2', true);
	
	wp_enqueue_script( 'storage', plugins_url( ).'/kidzou-4/assets/js/kidzou-storage.js', array( ), Kidzou::VERSION, true); // 'ko', 'ko-mapping'

	////////////////////////////////////////////////////////////////////
	///
	/// Reactisation du bazar
	///
	////////////////////////////////////////////////////////////////////

	if (!class_exists('ReactJS'))
		include get_stylesheet_directory().'/includes/react/ReactJS.php';

	$react = new ReactJS(
	  	// location of React's code and dependencies
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom-server.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js'),
		// app code
		file_get_contents('js/portfolio.js', FILE_USE_INCLUDE_PATH)
	);
	
	wp_enqueue_script( 'portfolio-components',  get_stylesheet_directory_uri().'/js/portfolio.js', array( 'react' ), Kidzou::VERSION, false );

 	////////////////////////////////////////////////////////////////////
 	////////////////////////////////////////////////////////////////////
 	//////////////////////////////////////////////////////////////////// 

	global $post;
	
	$data = array();
	$data['context'] 	= 'single';
 	$data['apis'] 		= array('getVotes'		=> site_url().'/api/vote/get_votes_status/',
								'voteUp'		=> site_url().'/api/vote/up/',
								'voteDown'		=> site_url().'/api/vote/down/',
								'isVotedByUser' => site_url().'/api/vote/isVotedByUser/',
								'getNonce'		=> site_url().'/api/get_nonce/');

 	$data['current_user_id'] = (is_user_logged_in() ? get_current_user_id() : 0);
 	$data['ID'] = $post->ID;

	$react->setComponent('Vote', $data);

	printf("<div id='react_vote'>%1s</div>",
		$react->getMarkup()
	);

	$footer_script = $react->getJS('#react_vote', "Vote");
		 	
 	//injecter les scripts en footer pour éviter de polluer le HTML
 	add_action( 'wp_footer', function() use ($footer_script) { 
 		echo '<script>'.$footer_script.'</script>';
 	}, 999 );
}

/**
 * Rendu du fly-in de notification sur les posts
 *
 */
function kz_notification() {

	global $post;

	wp_enqueue_style( 'endbox', 	get_stylesheet_directory_uri().'/js/css/endpage-box.css' , array(), Kidzou::VERSION );

	wp_enqueue_script('react',			'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js',			array('classnames'), '0.14.7', true);
	wp_enqueue_script('react-dom',		'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js',		array('react'), '0.14.7', true);	
	wp_enqueue_script('classnames',		'https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js',		array(), '2.2.3', true);
	wp_enqueue_script( 'storage', 		plugins_url( ).'/kidzou-4/assets/js/kidzou-storage.js', array( ), Kidzou::VERSION, true); // 'ko', 'ko-mapping'

	// wp_enqueue_script( 'portfolio-components', get_stylesheet_directory_uri().'/js/portfolio.js', array('react-dom'), Kidzou::VERSION, true); //ko

	wp_enqueue_script('endbox',	 	get_stylesheet_directory_uri().'/js/jquery.endpage-box.min.js' ,array('jquery'), Kidzou::VERSION, true);
	wp_enqueue_script('notif', 		get_stylesheet_directory_uri().'/js/notif.js', array('portfolio-components'), Kidzou::VERSION, true); //ko

	wp_localize_script('notif', 'kidzou_notif', array(
			'messages'				=> Kidzou_Notif::get_messages(),
			'activate'				=> Kidzou_Notif::isActive(),
			'message_title'			=> __( 'A voir &eacute;galement :', 'Divi' ),
			'newsletter_context'	=> Kidzou_Notif::getNewsletterFrequency(),
			'newsletter_nomobile'	=> !Kidzou_Notif::isActiveOnMobile(),
			'api_voted_by_user'		=> site_url().'/api/vote/voted_by_user/',
			'current_user_id'		=> (is_user_logged_in() ? get_current_user_id() : 0),
			'slug'					=> $post->post_name,
			'vote_apis'				=> array('getVotes'		=> site_url().'/api/vote/get_votes_status/',
											'voteUp'		=> site_url().'/api/vote/up/',
											'voteDown'		=> site_url().'/api/vote/down/',
											'isVotedByUser' => site_url().'/api/vote/isVotedByUser/',
											'getNonce'		=> site_url().'/api/get_nonce/')
		)
	);

}



/**
 * Rendu d'un post au sein d'un portfolio 
 *
 * @param fullwidth on|off
 * @param render_featured : True si les posts featured doivent etre rendus différemment des autres
 */
function kz_render_post($post, $fullwidth, $show_title, $show_categories, $background_layout, $distance = '', $render_featured = true) {

	$category_classes = array();
	$categories = get_the_terms( get_the_ID(), 'category' );
	if ( $categories ) {
		foreach ( $categories as $category ) {
			$category_classes[] = 'project_category_' . $category->slug;
			$categories_included[] = $category->term_id;
		}
	}

	$category_classes = implode( ' ', $category_classes );

	$featured = (Kidzou_Featured::isFeatured() && $render_featured);
	$kz_class = 'kz_portfolio_item '.($featured ? 'kz_portfolio_item_featured': '');

	$thumb = '';

	$width = ('on' === $fullwidth ?  1080 : ($featured ? 600 : 400)); 
	$height = 'on' === $fullwidth ?  9999 : 284;
	$classtext = 'on' === $fullwidth ? 'et_pb_post_main_image' : '';
	$titletext = get_the_title();
	$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false ); //, 'et-pb-portfolio-image' 
	
	$thumb = $thumbnail["thumb"];

	$event_meta = '';
	$location_meta = '';
	$output = '';

	//les posts dont l'adresse est renseignée : on affiche la ville pour donner un repère rapide au user
	if (Kidzou_Geoloc::has_post_location()) {
		$location = Kidzou_Geoloc::get_post_location();
		$location_meta = '<div class="portfolio_meta"><i class="fa fa-map-marker"></i>'.$location['location_city'].'</div>'; 
	}

	//pour les posts de type event, la date est affichée
	if (Kidzou_Events::isTypeEvent()) {

		$location = Kidzou_Events::getEventDates();

		$start 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['start_date'], new DateTimeZone('Europe/Paris'));
		$end 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['end_date'], new DateTimeZone('Europe/Paris'));

		$formatter = new IntlDateFormatter('fr_FR',
                                            IntlDateFormatter::SHORT,
                                            IntlDateFormatter::NONE,
                                            'Europe/Paris',
                                            IntlDateFormatter::GREGORIAN,
                                            'dd/MM/yyyy');

		$formatter->setPattern('cccc dd LLLL');

		$formatted = '';

		//mieux vaut prévenir les erreurs que les guérir
		//c'est arrivé pour je ne sais quelle raison que les dates soient en erreur auquel cas 
		//DateTime::createFromFormat() retourne "false"
		if ($start!==false && $end!==false) {

			if ($start->format("Y-m-d") == $end->format("Y-m-d"))
				$formatted = __( 'Le ', 'Divi' ).$formatter->format($start);
			else
				$formatted = __( 'Du ','Divi').$formatter->format($start).__(' au ','Divi').$formatter->format($end);
		
		 	$event_meta = '<div class="portfolio_meta"><i class="fa fa-calendar"></i>'.$formatted.'</div>'; 
		}
	} 

	//la distance au post est affichée de facon "intelligente"
	if ($distance != '') {

		if (floatval($distance)<1) {
			$distance = (round($distance, 2)*1000). ' m'; 
		} else {
			$distance = round($distance, 1) . ' Km'; 
		}

		$distance = '<div class="portfolio_meta"><i class="fa fa-location-arrow"></i>'.$distance.'</div>' ;
	}

	//rendu du thumbnail
	if ( '' !== $thumb ) {

		$image = print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height , '', false); //pas d'echo 

		//Rendu des post featured
		if ( $featured ) {

			//ultérieur, pour intégration Facebook ?
			$fb = '';

			$output .= sprintf("<div class='kz_portfolio_featured_hover'>
									%s 
									<a href='%s'><span class='votable'></span><h2>%s</h2></a>
									%s
									%s
									%s
									%s
									%s
								</div>",
					'',// Kidzou_Vote::get_vote_template(get_the_ID(), 'font-2x', false, false),
					get_permalink(),
					get_the_title(),
					kz_get_post_meta(),
					$distance,
					$event_meta,
					$location_meta,
					$fb);

			$output = sprintf("
						%s <a href='%s'>%s</a>								
				",
				$output,
				get_permalink(),
				$image
				);
		} else  {
			//$output .= Kidzou_Vote::get_vote_template(get_the_ID(), 'hovertext votable_template', false, false);

			if ( 'on' !== $fullwidth ) { 
			
				$output = sprintf("
					<a href='%s'>
						<span class='et_portfolio_image'>
							<span class='votable'></span> %s %s
							<span class='et_overlay'></span>
						</span><!--  et_portfolio_image -->
					</a>
					",
					get_permalink(),
					$output,
					$image
				);

			} 
		}

	}

	//le titre
	if ( 'on' === $show_title && !$featured) {
		$output .= '<h2><a href="'.get_the_permalink().'">'.get_the_title().'</a></h2>';
	}

	//les cats
	if ( 'on' === $show_categories && !$featured ) {
		$output .= '<p class="post-meta">'.get_the_term_list( get_the_ID(), "category", '', ', ' ).'</p>';
	}

	if (!$featured) {
		$output .= $event_meta.$location_meta;
		$output .= $distance;
	}

	//pour des raisons de SEO (Code to Text Ratio) on rend le short desc du post meme s'il n'est pas affiché
	$output .= '<div style="display:none;">'.get_the_excerpt().'</div>';

	return sprintf("<div id='post-%1s' class='%2s'>%3s</div>",
		get_the_ID(),
		implode(' ', get_post_class( 'et_pb_portfolio_item '.$kz_class. ' '. $category_classes, get_the_ID() )),
		$output
	);
}


/** 
 * Rendu d'un single via ReactJS executé sur le serveur par V8JS
 * @todo
 */
function render_react_single() {

	if (!is_single()) return;

	/**
	 *
	 * Hack pour utiliser the_content() et autres en dehors de the_loop
	 */
	global $post;
    setup_postdata( $post );

	$data = array();

	$data['social_sharing'] = do_shortcode('[TheChamp-Sharing]');
	$data['single_top'] 	= (et_get_option('divi_integration_single_top') <> '' && et_get_option('divi_integrate_singletop_enable') == 'on' ? et_get_option('divi_integration_single_top') : '');
	$data['post_class'] 	= get_post_class('et_pb_post');
	$data['title'] 			= get_the_title();
	$data['ID'] 			= get_the_ID();
	$data['admin_url'] 		= admin_url();
	$data['is_preview'] 	= is_preview(); 

	$width 		= (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );
	$height 	= (int) apply_filters( 'et_pb_index_blog_image_height', 675 );
	$thumbnail 	= get_thumbnail( $width, $height, '', $data['title'], $data['title'], false, 'Blogimage' );
	$thumb 		= $thumbnail["thumb"];
	$data['html_thumb'] = print_thumbnail( $thumb, $thumbnail["use_timthumb"], $data['title'], $width, $height , 'et_pb_image et-waypoint et_pb_image et_pb_animation_left et-animated', false);

	ob_start();
	et_divi_post_meta();
	$data['meta'] 		= ob_get_contents();ob_end_clean();

	$data['has_location'] 	= Kidzou_Geoloc::has_post_location();
	$data['location'] 		= Kidzou_Geoloc::get_post_location();
	$data['is_event'] 		= Kidzou_Events::isTypeEvent();
	$data['dates'] 			= Kidzou_Events::getEventDates(); 
	$data['pub'] 			= ( Kidzou_Utils::get_option('pub_post')!='' ? Kidzou_Utils::get_option('pub_post') : '');
	
	////////////////////////////

	ob_start();
	the_content(); //passer par les filtres 'the_content' et pas par get_the_content() pour obtenir notamment les CRP...
	$data['content'] = ob_get_contents();ob_end_clean();
	////////////////////////////

	$post_format = get_post_format();
	$post_format_content = '';

	if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) {
		$post_format_content = $first_video;
	} else if ( ! in_array( $post_format, array( 'gallery', 'link', 'quote' ) ) && 'on' === et_get_option( 'divi_thumbnails', 'on' ) && '' !== $thumb ) {
		$post_format_content = print_thumbnail( $thumb, $thumbnail["use_timthumb"], $title, $width, $height, '', false );
	} else if ( 'gallery' === $post_format ) {
		ob_start();
		et_gallery_images();
		$post_format_content = ob_get_contents();ob_end_clean();
	}

	$text_color_class 	= et_divi_get_post_text_color();
	$inline_style 		= et_divi_get_post_bg_inline_style();

	switch ( $post_format ) {
		case 'audio' :
			ob_start();
			printf(
				'<div class="et_audio_content%1$s"%2$s>
					%3$s
				</div>',
				esc_attr( $text_color_class ),
				$inline_style,
				et_pb_get_audio_player()
			);
			$post_format_content = ob_get_contents();ob_end_clean();
			break;
		case 'quote' :
			ob_start();
			printf(
				'<div class="et_quote_content%2$s"%3$s>
					%1$s
				</div> <!-- .et_quote_content -->',
				et_get_blockquote_in_content(),
				esc_attr( $text_color_class ),
				$inline_style
			);
			$post_format_content = ob_get_contents();ob_end_clean();
			break;
		case 'link' :
			ob_start();
			printf(
				'<div class="et_link_content%3$s"%4$s>
					<a href="%1$s" class="et_link_main_url">%2$s</a>
				</div> <!-- .et_link_content -->',
				esc_url( et_get_link_url() ),
				esc_html( et_get_link_url() ),
				esc_attr( $text_color_class ),
				$inline_style
			);
			$post_format_content = ob_get_contents();ob_end_clean();
			break;
	}

	$data['post_format_content'] = $post_format_content;

	////////////////////////////

	ob_start();
	wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'Divi' ), 'after' => '</div>' ) );
	$data['link_pages'] = ob_get_contents();ob_end_clean();

	////////////////////////////
	$data['map'] = '';
	if ($data['has_location']) {
		$location = $data['location'];
		$data['map'] = do_shortcode('[et_pb_map admin_label="Map" address="'.$location['location_address'].'" zoom_level="15" address_lat="'.$location['location_latitude'].'" address_lng="'.$location['location_longitude'].'"]'.
										'[et_pb_map_pin title="'.$location['location_name'].'" pin_address="'.$location['location_address'].'" pin_address_lat="'.$location['location_latitude'].'" pin_address_lng="'.$location['location_longitude'].'"]
															<p><strong>'.$location['location_name'].'</strong></p>'.
																 (isset($location['location_address']) && $location['location_address']<>'' ? '<p class="location"><i class="fa fa-map-marker"></i>'.$location['location_address'].'</p>' : '').
																 (isset($location['location_tel']) && $location['location_tel']<>'' ? '<p class="location"><i class="fa fa-phone"></i>'.$location['location_tel'].'</p>':'').
																 (isset($location['location_web']) && $location['location_web']<>'' ?  '<p class="location"><i class="fa fa-tablet"></i>'.$location['location_web'].'</p>':'').
										'[/et_pb_map_pin]
									[/et_pb_map]');
	}
	
	////////////////////////////

	$data['is_ad'] 	= et_get_option('divi_468_enable') == 'on';
	$data['adsense'] 	= ( et_get_option('divi_468_enable') == 'on' && et_get_option('divi_468_adsense') <> '' ? et_get_option('divi_468_adsense') : '');
	$data['adimg_url'] 	= ( et_get_option('divi_468_enable') == 'on' && et_get_option('divi_468_url') <> '' ? esc_url(et_get_option('divi_468_url')) : '');	
	$data['adimg_img'] 	= ( et_get_option('divi_468_enable') == 'on' && et_get_option('divi_468_url') <> '' ? esc_attr(et_get_option('divi_468_image')) : '');		
	
	////////////////////////////

	$comments = '';
	if ( comments_open() && 'on' == et_get_option( 'divi_show_postcomments', 'on' ) ) {
		ob_start();
		comments_template( '', true );	
		$comments = ob_get_contents();ob_end_clean();
	}
	$data['comments'] = $comments;
	
	////////////////////////////

	$data['single_bottom'] =  (et_get_option('divi_integration_single_bottom') <> '' && et_get_option('divi_integrate_singlebottom_enable') == 'on' ? et_get_option('divi_integration_single_bottom') : '');												

	////////////////////////////
	ob_start();
	get_sidebar();
	$data['sidebar'] = ob_get_contents();ob_end_clean();

	////////////////////////////
	ob_start();
	get_post_footer();
	$data['footer'] = ob_get_contents();ob_end_clean();
	

	////////////////////////////////////////////////////////////////////
	///
	/// Reactisation du bazar
	///
	////////////////////////////////////////////////////////////////////

	if (!class_exists('ReactJS'))
		include get_stylesheet_directory().'/includes/react/ReactJS.php';

	$react = new ReactJS(
	  	// location of React's code and dependencies
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom-server.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js').
		file_get_contents('https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js'),
		// file_get_contents(WP_CONTENT_DIR.'/../wp-includes/js/jquery/jquery.js', FILE_USE_INCLUDE_PATH),
		// app code
		file_get_contents('js/portfolio.js', FILE_USE_INCLUDE_PATH).
		file_get_contents('js/single.js', FILE_USE_INCLUDE_PATH)
	);

	wp_enqueue_script('react',			'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js',			array('classnames'), '0.14.7', true);
	wp_enqueue_script('react-dom',		'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js',		array('react'), '0.14.7', true);	
	wp_enqueue_script('classnames',		'https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js',		array(), '2.2.3', true);
	wp_enqueue_script('tweenmax',		'https://cdnjs.cloudflare.com/ajax/libs/gsap/1.18.2/TweenMax.min.js',		array(), '1.18.2', true);
	
	wp_enqueue_script( 'storage', plugins_url( ).'/kidzou-4/assets/js/kidzou-storage.js', array( ), Kidzou::VERSION, true); // 'ko', 'ko-mapping'
	wp_enqueue_script( 'portfolio-components',  get_stylesheet_directory_uri().'/js/portfolio.js', array( 'react-dom', 'storage'), Kidzou::VERSION, true );
	
 	////////////////////////////////////////////////////////////////////
 	////////////////////////////////////////////////////////////////////
 	//////////////////////////////////////////////////////////////////// 

	$react->setComponent('Post', $data);

	ob_start();
	get_header();
	$header = ob_get_contents();ob_end_clean();

	ob_start();
	kz_notification();
	kz_single_vote($data['ID']);
	get_footer();
	$footer = ob_get_contents();ob_end_clean();

	printf("%1s<div id='main-content'>%2s</div>%3s",
		$header,
		$react->getMarkup(),
		$footer
	);

	$footer_script = $react->getJS('#main-content', "Post");
		 	
 	//injecter les scripts en footer pour éviter de polluer le HTML
 	add_action( 'wp_footer', function() use ($footer_script) { 
 		echo '<script>'.$footer_script.'</script>';
 	}, 999 );

 	/** 
 	 * Fin du hack sur the_content() et autres 
 	 */
 	wp_reset_postdata( $post );

}


/**
 * Rendu du composant de vote dans le DOM (pas sur le serveur)
 * afin de forcer l'update des data
 *
 * @param $post_id int ID du post concerné
 */
function kz_single_vote($post_id=0) {

	if (!is_single()) return;

	if ($post_id==0) {
		global $post;
		$post_id = $post->ID;
	}

	wp_enqueue_script('react',			'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js',			array('classnames'), '0.14.7', true);
	wp_enqueue_script('react-dom',		'https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js',		array('react'), '0.14.7', true);	
	wp_enqueue_script('classnames',		'https://cdnjs.cloudflare.com/ajax/libs/classnames/2.2.3/index.min.js',		array(), '2.2.3', true);
	wp_enqueue_script( 'storage', 		plugins_url( ).'/kidzou-4/assets/js/kidzou-storage.js', array(), Kidzou::VERSION, true); // 'ko', 'ko-mapping'

	wp_enqueue_script('singleVote', 	get_stylesheet_directory_uri().'/js/singleVote.js', array('storage','portfolio-components'), Kidzou::VERSION, true); //ko

	wp_localize_script('singleVote', 'singleVote_jsvars', 
		array('apis'=>
			array(	'getVotes'		=> site_url().'/api/vote/get_votes_status/',
					'voteUp'		=> site_url().'/api/vote/up/',
					'voteDown'		=> site_url().'/api/vote/down/',
					'isVotedByUser' => site_url().'/api/vote/isVotedByUser/',
					'getNonce'		=> site_url().'/api/get_nonce/'),
			'ID' => $post_id ,
			'current_user_id' => (is_user_logged_in() ? get_current_user_id() : 0)
		)
	);

}




/**
 * genere un portfolio incluant les post_types specifiques de Kidzou (les offres n'apparaissent pas dans le portfolio)
 * et utilise la taxonomy 'category' et non pas 'project_category'
 *
 * nous avons étendu également les options : 
 * post__in
 * with_votes (true/false) pour utiliser le systeme de votes kidzou
 *
 * Ajout également d'un filtre de catégories configurable (filter = none|taxonomy)
 * Veillez bien à ce que le filtre soit le nom du taxonomie
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
			'show_ad' => 'on',
			'show_filters' => 'off',
			'render_votes' => 'on',
			'filter' => 'none', //nom d'une taxonomie par laquelle on va pouvoir filtrer
			'orderby' => 'publish_date',
			'render_featured' => 'on' //faut-il rendre les featured différemment des autres ?
		), $atts
	) );

	//inclure ces scripts pour ne pas corrompre custom.js qui fait référence 
	//à ces librairies pour les et_pb_portfolio_filter
	//même si notre portfolio n'est pas filtrable, il inclut un filtre "fake" de navigation qui renvoie vers les taxonomies
	wp_enqueue_script( 'jquery-masonry-3' );
	wp_enqueue_script( 'hashchange' );

	global $paged;

	$container_is_closed = false;

	$args = array(
		'posts_per_page' => (int) $posts_number,
		'post_type'      => Kidzou::post_types(),
		'orderby' 		=> array('date'=>'DESC'), //la base
	);

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

	switch ($orderby) {
		case 'reco':
			$query = new Vote_Query($args);
			break;

		case 'event_dates':
			$query = new Event_Query($args);
			break;
		
		default:
			$query = new WP_Query($args);
			break;
	}

	ob_start();

	$index = 0;
	$inserted = false;

	// Pagination fix
	// http://wordpress.stackexchange.com/questions/120407/how-to-fix-pagination-for-custom-loops
	global $wp_query;
	$temp_query = $wp_query;
	$wp_query   = NULL;
	$wp_query   = $query;
	$filter_terms = [];	

	if ( $query->have_posts() ) {

		$posts = $query->posts;	

		$doShowAd 		= ($show_ad=='on' ? true: false);
		$doShowVotes 	= ($render_votes=='on' ? true: false);
		$doShowCats 	= ($show_categories=='on' ? true: false);

		render_react_portfolio($doShowAd, $posts, true, $doShowVotes, $doShowCats);

		if ( 'on' === $show_pagination && !is_search() ) {
			echo '</div> <!-- .et_pb_portfolio -->';

			$container_is_closed = true;

			if ( function_exists( 'wp_pagenavi' ) )
				wp_pagenavi();
			else
				get_template_part( 'includes/navigation', 'index' );
		}

	} else {
		get_template_part( 'includes/no-results', 'index' );
	}

	$posts = ob_get_contents();

	ob_end_clean();

	$class = " et_pb_bg_layout_{$background_layout}";

	$filters_html = '';
	$category_filters = '';

	if ($filter!='none') {

		$category_filters = '<ul class="clearfix">';

		//assurer l'unicité des filtres de navigation
		//l'unicité est assurée par le slug
		$unique_terms = array_filter($filter_terms, function($obj)
		{
		    static $slugsList = array();
		    if(in_array($obj->slug, $slugsList)) {
		        return false;
		    }
		    $slugsList[] = $obj->slug;
		    return true;
		});
		
		foreach ( $unique_terms as $term  ) {
			$category_filters .= sprintf( '<li class="et_pb_portfolio_filter"><a href="%3$s" title="%4$s">%2$s</a></li>',
				esc_attr( $term->slug ),
				esc_html( $term->name ),
				get_term_link( $term, $filter ),
				__('Voir tous les articles dans ').$term->name
			);
		}
		$category_filters .= '</ul>';

		$filters_html = sprintf(
						'<div class="et_pb_filterable_portfolio ">
							<div class="et_pb_portfolio_filters clearfix">
								%1$s
							</div><!-- .et_pb_portfolio_filters -->
						</div>',
						$category_filters);
	}		

	$output = sprintf(
		'<div%5$s class="%1$s%3$s%6$s">
			%7$s
			%2$s
		%4$s',
		( 'on' === $fullwidth ? 'et_pb_portfolio' : 'et_pb_portfolio_grid clearfix' ),
		$posts,
		esc_attr( $class ),
		( ! $container_is_closed ? '</div> <!-- .et_pb_portfolio -->' : '' ),
		( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
		( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
		$filters_html
	);

	//hack pour pagination
	// Reset main query object
	//@see http://wordpress.stackexchange.com/questions/120407/how-to-fix-pagination-for-custom-loops
	$wp_query = NULL;
	$wp_query = $temp_query;

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
					<?php endif; 
						// kz_vote_single();
						//Kidzou_Vote::vote(get_the_ID(), 'hovertext votable_template'); 
						print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
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
 * genere un portfolio des favoris utilisateur
 *
 */
function kz_pb_user_favs( $atts ) {
	
	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'fullwidth' => 'on',
			'show_title' => 'on',
			'show_categories' => 'on',
			'background_layout' => 'light',
			'show_pagination' => 'off'
		), $atts
	) );

	wp_enqueue_script( 'jquery-masonry-3' );
	wp_enqueue_script( 'hashchange' );

	$container_is_closed = false;

	//recuperer les votes au format WP_Post
	$voted =  Kidzou_Vote::getUserVotedPosts( get_current_user_id(), array('fields'=>'all'));
	

	if ( count($voted)>0 )
	{

		ob_start();

		render_react_portfolio(false, $voted, false, false, true);

		$posts = ob_get_contents(); ob_end_clean();

		$category_filters = '';

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
			0,//esc_attr( $posts_number),
			('on' === $show_pagination ? '' : 'no_pagination' ),
			('on' === $show_pagination ? '<div class="et_pb_portofolio_pagination"></div>' : '' )
		);

		$output .= '<div class="waiting vote"><i class="fa fa-spinner fa-spin fa-2x pull-left"></i><h1>Hum...Patience petit scarab&eacute;e</h1></div>';

		return $output;
	}
	else
	{
		ob_start();
		
		get_template_part( 'includes/no-results', 'user-favs' );

		ob_get_contents();

		ob_end_clean();
		
		return $out;
	}	
}


/**
 * Construit un tableau de data à utiliser par les Markers Google Maps coté JS 
 *
 * @return Array
 * @author 
 **/
function kz_get_map_markers ($ids)
{

	$pins = array();

	if (!empty($ids))
	{	
		global $post;

		foreach ($ids as $key=>$value) 
		{
			$post = get_post($value->post_id);
			setup_postdata($post);

			$thumbnail = get_thumbnail( 40, 40, '', get_the_title() , get_the_title() , false );
			// Kidzou_Utils::log($thumbnail);
			$thumb = $thumbnail["thumb"];
			$img = print_thumbnail( $thumb, $thumbnail["use_timthumb"], $post->post_title, 40, 40, 'kz_pb_map_marker', false);

			$content = '<a title="'.get_the_permalink().'" href="'.get_the_permalink().'">'.
					$img. '<br/>'. get_the_title().
				'</a>';

			array_push($pins, array(
					'latitude' => $value->latitude,
					'longitude'=> $value->longitude,
					'id'	=> get_the_ID()
					// 'title'		=> get_the_title() ,
					// 'content'	=> $content
				));
			
		}

		wp_reset_postdata();
	}
	

	return $pins;
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
		
		$args['category__in'] = explode( ',', $include_categories );
	}


	$projects = get_portfolio_items( $args );

	// Kidzou_Utils::log($projects);

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
				// $thumb = '';
				$width = 400;
				$height = 250;

				$classtext = 'et_pb_post_main_image';
				$titletext = get_the_title();
				$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false );
				$thumb = $thumbnail["thumb"];
				$orientation = 'landscape';

				if ( '' !== $thumb ) : ?>
					<div class="et_pb_portfolio_image <?php esc_attr_e( $orientation ); ?>">

						<a href="<?php the_permalink(); ?>">
							
							<!-- img src="< ? php esc_attr_e( $thumb_src); ? >" alt="< ? php esc_attr_e( get_the_title() ); ? >"/ -->
							<?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
							<div class="meta">
								<span class="et_overlay"></span>
								<?php if ( 'on' === $show_title ) : ?>
									<h3><?php the_title(); ?></h3>
								<?php endif; ?>

								<?php if ( 'on' === $show_date ) : ?>
									<p class="post-meta"><?php echo get_the_date(); ?></p>
								<?php endif; ?>

								<!-- pour des raisons de SEO (Code to Text Ratio) on rend le short desc du post meme s'il n'est pas affiché -->
								<?php echo '<div style="display:none;">'.get_the_excerpt().'</div>'; ?>


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

	$args['post_type'] = kidzou::post_types();
	return new WP_Query($args);
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
function kz_pb_fullwidth_map( $atts, $content = '' ) {
	
	extract( shortcode_atts( array(
			'module_id' => '',
			'module_class' => '',
			'zoom'	=> 13,
		), $atts
	) );

	$locator = Kidzou_Geoloc::get_instance();

	$radius = 1000; //on simule l'infini...

	//generer le scripts qui va checker que lat/lng sont présents en localStorage
	//si lat/lng sont détéctés, déclencher un ajax pour charger le portfolio
	wp_enqueue_script(
		'custom-proxi',
		get_stylesheet_directory_uri() . '/js/custom-proxi.js',
		array( 'jquery', 'google-maps-api' ),
		Kidzou::VERSION,
		true 
	);

	//le clusterer sert à regrouper les icons pour une meilleure perfromance d'affichage
	wp_enqueue_script(
		'marker-clusterer',
		get_stylesheet_directory_uri() . '/js/markerclusterer_compiled.js',
		array( 'custom-proxi' ),
		Kidzou::VERSION,
		true 
	);

	$is_geolocalized = $locator->is_request_geolocalized();

	if (!wp_script_is( 'google-maps-api', 'enqueued' ) && $display_mode!='simple') 
		wp_enqueue_script( 'google-maps-api' );

	//initialement : récupérer les coords 
	$coords = $locator->get_request_coords();
	$ids = $locator->getPostsNearToMeInRadius($coords['latitude'], $coords['longitude'], $radius);
	$pins = kz_get_map_markers($ids);

	wp_localize_script( 'custom-proxi', 'kidzou_proxi', array(
		// 'ajaxurl'           	=> admin_url( 'admin-ajax.php' ),
		'wait_geoloc_message' 	=> '<h2><i class="fa fa-spinner fa-spin pull-left"></i>Nous sommes entrain de d&eacute;terminer votre position...</h2>',
		'wait_load_message' 	=> '<h2><i class="fa fa-map-marker  pull-left"></i>Chargement des r&eacute;sultats...</h2>',
		'wait_refreshing' 		=> '<h5><i class="fa fa-spinner fa-spin"></i>Actualisation de la carte</h5>',
		'wait_geoloc_progress' 	=> '<h4><i class="fa fa-spinner fa-spin pull-left"></i>Actualisation de votre position</h4><br/>',
		'title' 				=> '<h1><i class="fa fa-map-marker pull-left"></i>A faire pr&egrave;s de chez vous</h1>',
		'refresh_message'		=> 'Ces r&eacute;sultats ne vous paraissent pas pertinents&nbsp;?&nbsp;<a title="Rafraichir les r&eacute;sultats">Rafraichir les r&eacute;sultats</a><br/><br/>',
		'display_mode'			=> 'fullwidth',
		'geoloc_error_msg'			=> __('<h4><i class="fa fa-warning  pull-left"></i>Votre localisation &agrave; &eacute;chou&eacute;, nouvelle tentative en cours...</h4>','Divi'),
		'geoloc_pleaseaccept_msg'	=> __('<h4><i class="fa fa-warning pull-left"></i>Pour des r&eacute;sultats plus pertinents, acceptez la geolocalisation de votre navigateur !</h4>','Divi'),
		'markers'				=> $pins,
		'zoom'					=> $zoom,
		'scrollwheel'			=> 'off',
		'page_container_selector'=> '#proxi_content',
		'map_selector'			=> 	'#proxi_content .et_pb_map',
		'map_container'			=> 	'#proxi_content .et_pb_map_container',
		'more_results_selector' => 	'#proxi_content .more_results',
		'more_results_cta_selector'	=> '.load_more_results',
		'message_selector'		=> 	'#proxi_content .message',
		'portfolio_selector'	=> 	'#proxi_content .et_pb_portfolio_results',
		'distance_message_selector'	=> '.distance_message',
		'api_get_place'			=> site_url()."/api/content/get_place/",
		'api_public_key'		=> Kidzou_Utils::get_option('api_public_key', '')

	) );
		
	$out = sprintf(
			'<div%5$s class="et_pb_map_container%6$s">
				<div class="et_pb_map" data-center_lat="%1$s" data-center_lng="%2$s" data-zoom="%3$d" data-mouse_wheel="%7$s"></div>
			</div>',
			esc_attr( $coords['latitude'] ),
			esc_attr( $coords['longitude'] ),
			$zoom, //zoom level
			'',//$pins,
			( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
			( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
			'off' //mousewheel
		);

	return sprintf(
		'<div id="proxi_content">
			<div class="results">
				%1$s
			</div>
		</div>',
		$out
	);	
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
		wp_enqueue_script( 'google-maps-api', add_query_arg( array( 'v' => 3, 'sensor' => 'false' ), is_ssl() ? 'https://maps-api-ssl.google.com/maps/api/js' : 'http://maps.google.com/maps/api/js' ), array(), '3', true );

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

		global $post;
		$terms = wp_get_post_terms( $post->ID, array('category','age', 'divers') );
		$index = 0;
		foreach ($terms as $term) {

			$term_link = get_term_link( $term );
   
		    // If there was an error, continue to the next term.
		    if ( is_wp_error( $term_link ) ) {
		        continue;
		    }
		    if ($index>0) $postinfo_meta .= ', ';

			$postinfo_meta .= '<a href="' . esc_url( $term_link ) . '">'.$term->name.'</a> ';

			$index++;
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