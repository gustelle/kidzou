<?php
/*
Controller Name: Utils
Controller Description: Fonctions utilitaires diverses
Controller Author: Kidzou 
*/

class JSON_API_Utils_Controller {

	/**
	 *
	 * Recupere l'URL de l'avatar d'un user identifié par son email 
	 * 
	 * @param email
	 * @return URL 
	 **/
	public function get_avatar_url()
	{
		global $json_api;
		
		$email = $json_api->query->email;

		if ( filter_var($email, FILTER_VALIDATE_EMAIL) === false )  
			$json_api->error("email invalide");

		$avatar_url = get_avatar_url($email, array('size'=>64));
		// $pattern = '/src=\'(.+)\'/i';
	    // preg_match('<img(.+)src=(\'|")(^https?://.*)(\'|")(.*)>', $avatar_url, $match);
	    return array('avatar_url'=> $avatar_url);
	}

	
}	

?>