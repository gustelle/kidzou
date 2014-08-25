<?php

add_action( 'login_enqueue_scripts', 'kz_login_logo' );
//login screens
//can be useful for people receiving emails linking to this screen
function kz_login_logo() { ?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url(<?php echo content_url(); ?>/uploads/2012/12/LOGO-130X360PX-BL.png);
            background-size: 320px auto;
            margin-bottom: 30px;
            height: 130px;
            width: auto;
        } 
        body.login div#login h1 + a, body.login div#login h1 + a+a {padding: 0 0.3em 0.5em 1em;display: block; text-decoration: none;}

    </style>
    <!-- add also Nextend Connect -->
<?php }

add_filter( 'login_message', 'kz_social_login' );

/**
 * ajout des boutons de login Nextend
 *
 * @return void
 * @author 
 **/
function kz_social_login()
{
	return 	'<a class="social" href="http://www.kidzou.fr/wp-login.php?loginGoogle=1" rel="nofollow" ><div class="new-google-btn new-google-13"><div class="new-google-13-1"></div></div>&nbsp;&nbsp;Identifiez vous avec Google</a>'.
			'<a class="social" href="http://www.kidzou.fr/wp-login.php?loginFacebook=1" rel="nofollow"><div class="new-fb-btn new-fb-13"><div class="new-fb-13-1"></div></div>&nbsp;&nbsp;Identifiez vous avec Facebook</a>';
}


add_action( 'wp', 'kz_plugins_override' );

/**
 * Optimisation des JS chargés 
 *
 * @return void
 * @author 
 **/
function kz_plugins_override() {

    //connections
    //dans les pages (home, search, etc) des fiches peuvent être présentes dans les contenus
    //référencés mais ne sont pas affichés (ne sont affichés que les excerpts, or les fiches ne sont pas dans les excerpts)
	// echo 'isFiche : '.isFiche();
    if ( !isFiche() )
	{
		remove_action( 'wp_enqueue_scripts', array( 'cnScript', 'enqueueScripts' ) );
		remove_action( 'wp_enqueue_scripts', array( 'cnScript', 'enqueueStyles' ) );
	}
	// echo 'removed : ';
    //pagenavi
    //non utilisé, inclus dans le style du theme 
    wp_dequeue_style( 'wp-pagenavi' ); //marche pas
}


add_action('wp_head', 'kz_meta');

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_meta ()
{
	echo '<link href="https://plus.google.com/103958659862452997072/" rel="author" />';
}

add_action('wp_enqueue_scripts', 'add_kzscripts');

function add_kzscripts() {

	$js_path = (KIDZOU_VERSION!='dev' ? "dist/" : "" );
	$kidzou_js 			= $js_path."kidzou.".(KIDZOU_VERSION!='dev' ? KIDZOU_VERSION : "concat" ).".js";

	wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
	wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);

	//stockage local des données 
	//utilisé pour les votes des users
	wp_enqueue_script('localcache',	WP_PLUGIN_URL."/kidzou/js/front/dist/local-cache.min.js", array(), '1.0', true);
	
	// if (!is_page_template('page-edit-events.php')) {
	wp_enqueue_script('kidzou',		WP_PLUGIN_URL.'/kidzou/js/front/'.$kidzou_js, array('jquery','localcache'), KIDZOU_VERSION, true);
	// }

	wp_localize_script('kidzou', 'kidzou_commons_jsvars', array(
				'msg_wait'			 			 => 'Merci de patienter...',
				'msg_loading'				 	 => 'Chargement en cours...',
				'msg_auth_onprogress'			 => "Connexion en cours, merci de votre patience",
				'msg_auth_success'				 => "Connexion r&eacute;ussie, la page va se recharger...",
				'msg_auth_failed'				 => "Echec de connexion",
				'votable_countText' 			 => "&nbsp;recommandations",
				'votable_countText_down'		 => "&nbsp;Je ne recommande plus",
				'cfg_background_link' 	 		 =>  get_option("kz_background_link"),
				'cfg_newsletter_auto_display' 	 =>  (bool)get_option("kz_newsletter_auto_display"),
				'cfg_newsletter_delay' 	 		 =>  3000,
				'cfg_connections_image_base'	 =>  get_connections_image_base() ,
				'cfg_lost_password_url'			 =>  site_url().'/wp-login.php?action=lostpassword',
				'cfg_signup_url'				 =>  site_url().'/wp-signup.php',
				'cfg_site_url'		 			 =>  site_url().'/',
				'cfg_debug_mode' 	 			 =>  (bool)get_option("kz_debug_mode"),
				'js_gomap_url'		 			 =>  site_url().'/wp-content/plugins/connections/js/jquery.gomap-1.3.2.min.js',
				'js_google_maps'				 =>  'http://maps.google.com/maps/api/js?sensor=false',
				'api_get_nonce'				 	 =>  site_url().'/api/get_nonce/',
				'api_get_event'					 =>  site_url().'/api/events/get_event/',
				'api_get_fiche'					 =>  site_url().'/api/connections/get_fiche/',
				'api_get_votes_status'			 =>  site_url().'/api/vote/get_votes_status/', 
				'api_get_votes_user'			 =>  site_url().'/api/vote/get_votes_user/',
				'api_vote_up'			 		 =>  site_url().'/api/vote/up/',
				'api_vote_down'			 		 =>  site_url().'/api/vote/down/',
				'api_generate_auth_cookie'		 => site_url().'/api/auth/generate_auth_cookie/',
				'api_save_event'		 		 => site_url().'/api/events/saveEvent/',
				'api_get_geoposts'	 		=> site_url().'/api/tax/get_posts/',
				'is_admin' 					=> current_user_can( 'manage_options' )
			)
	);
	
	if (function_exists('add_kz_geo_scripts'))
		add_kz_geo_scripts(); //si le plugin est installé

	
}

/**
 * detecte la présence d'une shortcode [$the_shortcode] dans le contenu du post 
 *
 * @return TRUE si le shortcode [$the_shortcode] est détecté dans le contenu
 * @author 
 **/
function isShortcode($the_shortcode)
{
	global $post;
	$pattern = get_shortcode_regex();

    if (  preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches )
        && array_key_exists( 2, $matches )
        && in_array( $the_shortcode, $matches[2] ) )
    {
        return true;
    }

    return false;
}

/**
 * detecte la présence d'une fiche attachée en tant que metadonnée au post
 * OU la présence du shortcode [connections] dans le post
 *
 * @return TRUE si une fiche est attachée en meta du post courant OU le shortcode [connections] est détecté dans le contenu
 * @author 
 **/
function isFiche ()
{
	if (is_page('annuaire'))
		return true;
	elseif (is_home() || is_search() || is_category() || is_404() || is_tag() || is_tax() || is_page()) 
		return false;
	elseif (isShortcode('connections'))
		return true;

	global $post;

	$ficheID = get_post_meta( $post->ID, 'kz_connections', true );

	if ($ficheID!=null)
		return true;

	return false; 

}

?>