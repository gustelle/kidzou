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

	$kidzou_js 			= "kidzou-".KIDZOU_VERSION.".js";
	$kidzou_actions_js 	= "kidzou-actions-".KIDZOU_VERSION.".js";
	$kidzou_login_js 	= "kidzou-login-".KIDZOU_VERSION.".js";
	$kidzou_tracker_js 	= "kidzou-tracker-".KIDZOU_VERSION.".js";
	$kidzou_message_js 	= "kidzou-message-".KIDZOU_VERSION.".js";
	$kidzou_edit_events_js 	= "kidzou-edit-events-".KIDZOU_VERSION.".js";
	$kidzou_layout_js 	= "kidzou-layout-".KIDZOU_VERSION.".js";
	$kidzou_storage_js 	= "kidzou-storage-".KIDZOU_VERSION.".js";

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
	
	//commons kidzou
	wp_enqueue_script('kidzou-tracker',	 	WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_tracker_js,	array(), KIDZOU_VERSION, true);
	wp_enqueue_script('kidzou-message',	 	WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_message_js,	array('jquery', 'ko', 'kidzou-tracker'), KIDZOU_VERSION, true);
	wp_enqueue_script('kidzou-login',	 	WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_login_js,		array('kidzou-message','vex'), KIDZOU_VERSION, true);
	wp_enqueue_script('kidzou-actions',	 	WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_actions_js,	array('kidzou-tracker'), KIDZOU_VERSION, true);
	wp_enqueue_script('kidzou-layout',	 	WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_layout_js,	array('jquery'), KIDZOU_VERSION, true);
	wp_enqueue_script('kidzou-storage',	 	WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_storage_js,	array('jquery'), KIDZOU_VERSION, true);

	wp_localize_script('kidzou-message', 'kidzou_commons_jsvars', array(
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
				'js_syncEvents_url'				 =>  plugins_url().'/kidzou/js/worker/kidzou-syncEvents-'.KIDZOU_VERSION.'.js',
				'js_syncVotes_url'				 =>  plugins_url().'/kidzou/js/worker/kidzou-syncVotes-'.KIDZOU_VERSION.'.js',
				'js_google_maps'				 =>  'http://maps.google.com/maps/api/js?sensor=false',
				'api_get_nonce'				 	 =>  site_url().'/api/get_nonce/',
				'api_get_event'					 =>  site_url().'/api/events/get_event/',
				'api_get_fiche'					 =>  site_url().'/api/connections/get_fiche/',
				'api_get_votes_status'			 =>  site_url().'/api/vote/get_votes_status/', 
				'api_get_votes_user'			 =>  site_url().'/api/vote/get_votes_user/',
				'api_vote_up'			 		 =>  site_url().'/api/vote/up/',
				'api_vote_down'			 		 =>  site_url().'/api/vote/down/',
				'api_generate_auth_cookie'		 => site_url().'/api/auth/generate_auth_cookie/',
				'api_save_event'		 		 => site_url().'/api/events/saveEvent/'
			)
	);

	if (is_page_template('page-edit-events.php'))
	{
		//j'utilise ko 3.0 à cause du plugin wysiwig de tinymce qui requiert Ko 3
		//malheureusement le reste du site n'est pas (encore) compatible avec ko 3, développé avec ko 2
		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
		//validation des champs du formulaire de saisie des events
		wp_enqueue_script('ko-validation',			WP_PLUGIN_URL.'/kidzou/js/knockout.validation.min.js',array("ko"), '1.0', true);
		wp_enqueue_script('ko-validation-locale',	WP_PLUGIN_URL.'/kidzou/js/ko-validation-locales/fr-FR.js',array("ko-validation"), '1.0', true);
		
		//utilisé pour le formattage des dates
		wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
		wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);

		//datepicker
		wp_enqueue_style( 'jquery-ui-custom', WP_PLUGIN_URL."/kidzou/css/jquery-ui-1.10.3.custom.min.css" );	
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script("jquery-effects-core");
		wp_enqueue_script('jquery-effects-highlight');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-datepicker-fr', WP_PLUGIN_URL.'/kidzou/js/jquery.ui.datepicker-fr.js', array('jquery-ui-datepicker'),'1.0', true);

		//requis par placecomplete
		wp_enqueue_script('jquery-select2', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
		wp_enqueue_script('jquery-select2-locale', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
		wp_enqueue_style( 'jquery-select2', "http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );
		//selection des places dans Google Places
		wp_enqueue_style( 'placecomplete', WP_PLUGIN_URL."/kidzou/css/jquery.placecomplete.css" );
		wp_enqueue_script('placecomplete', WP_PLUGIN_URL."/kidzou/js/jquery.placecomplete.js",array('jquery-select2', 'google-maps'), '1.0', true);
		wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?libraries=places&sensor=false",array() ,"1.0", false);

		wp_enqueue_script('kidzou-edit-events', WP_PLUGIN_URL."/kidzou/js/".$kidzou_edit_events_js ,array('jquery','ko.mapping.js'), KIDZOU_VERSION, true);
				
		//textarea wysiwig pour description des evenements
		// wp_enqueue_script('tinymce', WP_PLUGIN_URL."/kidzou/js/tinymce/tinymce.min.js",array(), '1.0', true);
		// wp_enqueue_script('tinymce-jquery', WP_PLUGIN_URL."/kidzou/js/tinymce/jquery.tinymce.min.js",array('jquery','tinymce'), '1.0', true);
		// wp_enqueue_script('tinymce-ko', WP_PLUGIN_URL."/kidzou/js/knockout.wysiwyg.min.js",array('jquery','tinymce','ko'), '1.0', true);

		wp_localize_script('kidzou-edit-events', 'kidzou_jsvars', array(
				'api_save_event'		 		 => site_url().'/api/events/saveEvent/',
				'api_attach_image'			 	 => site_url().'/api/events/attachImage/',
				'api_user_events'			 	 => site_url().'/api/events/getUserEvents/',
				'api_remove_event'				 => site_url().'/api/events/removeEvent/',
				'api_request_publish'			 => site_url().'/api/events/requestPublish/'
			)
		);
	}
	else {
		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
	}

	wp_enqueue_script('localcache',	WP_PLUGIN_URL."/kidzou/js/local-cache.min.js", array(), '1.0', true);
	
	if (!is_page_template('page-edit-events.php')) {
		wp_enqueue_script('kidzou',		WP_PLUGIN_URL.'/kidzou/js/'.$kidzou_js, array('jquery','localcache','ko', 'vex'), KIDZOU_VERSION, true);
	}
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
	$black_list = array('headjs', 'google-maps'); //best practice de ne pas charger jQuery dans head.js{}
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