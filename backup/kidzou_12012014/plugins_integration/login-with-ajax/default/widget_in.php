<?php 
/*
 * This is the page users will see logged in. 
 * You can edit this, but for upgrade safety you should copy and modify this file into your template folder.
 * The location from within your template folder is plugins/login-with-ajax/ (create these directories if they don't exist)
*/


 		//le <a id="logout"> est important pour pouvoir detruire le cookie des recos lors de la deconnexion
 		$name = get_user('displayname')!='' ? get_user('displayname') : (get_user('firstname')!='' ? get_user('firstname') : get_user('username'));
 	    $out = 'Bienvenue '.$name.' ! | <a id="logout" href="'.wp_logout_url( $redirect_url ).'" title="Logout">D&eacute;connexion</a>';

 	    echo $out;

?>

