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
		// remove_action( 'init', array( 'cnScript', 'registerScripts' ) );
		// remove_action( 'init', array( 'cnScript', 'registerCSS' ) );
		// echo 'remove_action : ';
		remove_action( 'wp_enqueue_scripts', array( 'cnScript', 'enqueueScripts' ) );
		remove_action( 'wp_enqueue_scripts', array( 'cnScript', 'enqueueStyles' ) );
	}
	// echo 'removed : ';
    //pagenavi
    //non utilisé, inclus dans le style du theme 
    wp_dequeue_style( 'wp-pagenavi' ); //marche pas

    //login-with-ajax
    // wp_dequeue_style( "login-with-ajax" );
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

	$kidzou_js = "kidzou-".KIDZOU_VERSION.".js";

	//jquery n'est pas enqueued ?? on le force
	wp_deregister_script( 'jquery' ); // deregisters the default WordPress jQuery  
    wp_register_script('jquery', "http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js", false);
    wp_enqueue_script('jquery');

	wp_enqueue_script('headjs',	 			"http://cdnjs.cloudflare.com/ajax/libs/headjs/0.99/head.load.min.js",	array('jquery'), '0.99', true);

	if (is_single()) 
		wp_enqueue_script('portamento',		WP_PLUGIN_URL.'/kidzou/js/portamento-min.js',array('jquery'), '1.0', true);

	if (is_home() || is_page_template('page-links.php'))
	{
		wp_enqueue_script('imagesloaded',	"http://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.0.4/jquery.imagesloaded.min.js",	array('jquery'), '3.0.4', true);
		wp_enqueue_script('jmasonry',		"http://cdnjs.cloudflare.com/ajax/libs/masonry/2.1.05/jquery.masonry.min.js",	array('jquery','imagesloaded'), '2.1.05', true);
	}

	wp_enqueue_script('vex',	 	WP_PLUGIN_URL.'/kidzou/js/vex.combined.min.js',array('jquery'), '1.3.3', true);
	//wp_enqueue_style( 'vex', 	 	WP_PLUGIN_URL.'/kidzou/css/vex.css' );
	//wp_enqueue_style( 'vex-theme', 	WP_PLUGIN_URL.'/kidzou/css/vex-theme-top-w750.css' );

	wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
	wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
	
	wp_enqueue_script('localcache',	WP_PLUGIN_URL."/kidzou/js/local-cache.min.js", array(), '1.0', true);
	wp_enqueue_script('kidzou',		WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_js, array('jquery','localcache','ko', 'vex'), KIDZOU_VERSION, true);

	$worker_path = (KIDZOU_VERSION='dev') ? '/kidzou/js/worker/kidzou-syncEvents-' : '/kidzou/js/worker/dist/kidzou-syncEvents-';
	echo 'worker path:'.$worker_path;
	wp_localize_script('kidzou', 'kidzou_jsvars', array(
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
				'cfg_activate_datasync'	 		 =>  (bool)get_option("kz_activate_datasync"),
				'cfg_activate_syncvotes'	 	 =>  (bool)get_option("kz_activate_syncvotes"),
				'cfg_activate_content_tracking'	 =>  (bool)get_option("kz_activate_content_tracking"),
				'cfg_debug_mode' 	 			 =>  (bool)get_option("kz_debug_mode"),
				'js_gomap_url'		 			 =>  site_url().'/wp-content/plugins/connections/js/jquery.gomap-1.3.2.min.js',
				'js_syncEvents_url'				 =>  plugins_url().$worker_path.KIDZOU_VERSION.'.js',
				'js_syncVotes_url'				 =>  plugins_url().$worker_path.KIDZOU_VERSION.'.js',
				'js_google_maps'				 =>  'http://maps.google.com/maps/api/js?sensor=false',
				'api_get_nonce'				 	 =>  site_url().'/api/get_nonce/',
				'api_get_event'					 =>  site_url().'/api/events/get_event/',
				'api_get_fiche'					 =>  site_url().'/api/connections/get_fiche/',
				'api_get_votes_status'			 =>  site_url().'/api/vote/get_votes_status/', 
				'api_get_votes_user'			 =>  site_url().'/api/vote/get_votes_user/',
				'api_vote_up'			 		 =>  site_url().'/api/vote/up/',
				'api_vote_down'			 		 =>  site_url().'/api/vote/down/',
				'api_generate_auth_cookie'		 => site_url().'/api/auth/generate_auth_cookie/'
			)
	);

}

add_action('wp_print_scripts','kz_head_js',1000);

/**
 * undocumented function
 *
 * @see http://bostinno.streetwise.co/channels/using-head-js-for-wordpress/
 * @return void
 * @author 
 **/
function kz_head_js()
{
	global $wp_scripts;

	if (is_admin()) 
		return;

	//si le shortcode [connections] est dans la page ou si une fiche est directement attachée en metadonnée
	//on ne compacte pas les scripts par head.js car il y a un interaction avec les scripts de connections (en particulier la map....)
	//mais ceci n'est vrai que si une single ou un page 'connections' est affichée car pour le reste on utilise des excerpts
	if (isFiche())
	{
		//echo '<script type="text/javascript" src="'.WP_PLUGIN_URL.'/kidzou/js/head.load.min.js'.'"></script>';
		return;
	}

	$in_queue = $wp_scripts->queue; 
	$blacklist = kz_head_js_blacklist();
	
	//make sure it's only called once, not to pack the scripts in the footer
	//avoid more scripts in the footer  && (did_action( 'wp_print_scripts' ) === 1)
	if( !empty($in_queue) )
	{
		$scripts = array();
		foreach($in_queue as $script)
		{
			//verifier que le script n'est pas deja imprimé sur la page
			//if ( !kz_head_js_skip($script) )
			//{
				//et qu'il n'est pas de type src
				if (is_array($wp_scripts->registered[$script]->extra) and isset($wp_scripts->registered[$script]->extra['data']))
				{
					echo '<script type="text/javascript">'.$wp_scripts->registered[$script]->extra['data'].'</script>';
				}

				$src = $wp_scripts->registered[$script]->src;
				$src = ( (preg_match('/^(http|https)\:\/\//', $src)) ? '' : get_bloginfo('url') ) . $src;

				if(in_array($script, $blacklist))
				{
					echo '<script type="text/javascript" src="'.$src.'"></script>';
					continue;
				}
				else
				{
					$scripts[] = '{"' . $script . '":"' . $src . '"}';
				}

				// if (!property_exists($wp_scripts, 'headjs_enqueued'))
				// {
				// 	echo '<script type="text/javascript" src="'.WP_PLUGIN_URL.'/kidzou/js/head.load.min.js'.'"></script>';
				// 	$wp_scripts->headjs_enqueued = true;
				// }
			//}
		}

		echo '<script type="text/javascript">head.js('. implode(",", $scripts). ');</script>';

	}
	
	$wp_scripts->queue = array();
}

/**
 * la liste des handles blacklistés par head.js
 *
 * @return Un tableau de handles
 * @author 
 **/
function kz_head_js_blacklist()
{
	$black_list = array('headjs'); //best practice de ne pas charger jQuery dans head.js{}
	return $black_list;
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