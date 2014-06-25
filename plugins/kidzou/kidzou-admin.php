<?php


add_action('admin_menu', 'kz_menu');

function kz_menu() {

  	add_menu_page('Kidzou', 'Kidzou', 'manage_options', __FILE__, 'main_screen',plugins_url('kidzou/images/kidzou_16.png'));

	//Gestion des configurations
	add_submenu_page( __FILE__,
		              'R&eacute;glages' ,
		              'R&eacute;glages' ,
		              'manage_options' ,
		              __FILE__,
		              'kz_options'
		            );

	// http://stackoverflow.com/questions/2240460/how-to-add-new-custom-submenu-under-another-plugins-menu
	add_submenu_page( __FILE__,
		              'Gestion des Clients' ,
		              'Gestion des Clients' ,
		              'manage_options' ,
		              'kidzou-clients/kidzou-clients.php',
		              'kz_clients'
		            );

}

/** 
* @deprecated
**/

function main_screen () 
{

}


/**
**/
function kz_options()
{

	//lien du fond d'écran
	$background_link 		 = get_option("kz_background_link");

	//afficher le paneau d'inscription à la newsletter à l'ouverture du site?
	$isNewsletterAutoDisplay = get_option("kz_newsletter_auto_display");


	//affichage des logs dans la console
	$isDebugMode 			 = get_option("kz_debug_mode");

	$isMapOnPostList 			 = get_option("kz_map_post_list");
;

	if ($_POST['submit'])
	{
		$url = trim($_POST['background_link']);
		if (filter_var($url, FILTER_VALIDATE_URL))	
			$background_link = $url;
		else
			$background_link = "";

		$mail = filter_var( trim($_POST['events_notify']), FILTER_VALIDATE_EMAIL);	

		$isNewsletterAutoDisplay = trim($_POST['newsletter-auto-display']);
		if($isNewsletterAutoDisplay !=1)	$isNewsletterAutoDisplay = 0;

		$isDebugMode = trim($_POST['debug-mode']);
		if($isDebugMode !=1)	$isDebugMode = 0;

		$isMapOnPostList = trim($_POST['map-post-list']);
		if($isMapOnPostList !=1)	$isMapOnPostList = 0;

		if ( get_option( "kz_background_link" ) != $background_link )
		    update_option( "kz_background_link", $background_link );
		else
		    add_option( "kz_background_link", $background_link );

		if ( get_option( "kz_newsletter_auto_display" ) != $isNewsletterAutoDisplay )
		    update_option( "kz_newsletter_auto_display", $isNewsletterAutoDisplay );
		else
		    add_option( "kz_newsletter_auto_display", $isNewsletterAutoDisplay );

		if ( get_option( "kz_debug_mode" ) != $isDebugMode )
		    update_option( "kz_debug_mode", $isDebugMode );
		else
		    add_option( "kz_debug_mode", $isDebugMode );


		if ( get_option( "kz_map_post_list" ) != $isMapOnPostList )
		    update_option( "kz_map_post_list", $isMapOnPostList );
		else
		    add_option( "kz_map_post_list", $isMapOnPostList );

	    	
	}

?>
<div class="wrap">
	 <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>" >


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
	 		<input type="checkbox" value="1" <?php checked( $isMapOnPostList, 1 ); ?>  id="map-post-list" name="map-post-list">
	 		<span style="padding-left:5px;">Afficher une carte sur une liste de posts</span>
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



?>
