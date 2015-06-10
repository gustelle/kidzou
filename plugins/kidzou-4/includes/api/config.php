<?php
/*
Controller Name: Config
Controller Description: Accès aux Reglages de Kidzou
Controller Author: Kidzou 
*/

class JSON_API_Config_Controller {

	/**
	 * undocumented function
	 * @param m [cookie|token] la methode d'authentification utilisée pour récupérer la config
	 *
	 * @return Array 
	 **/
	public function all()
	{
		global $json_api;


		if (!$json_api->query->cookie) {
			$json_api->error("You must include a 'cookie' authentication cookie.");
		}		

    	$valid = wp_validate_auth_cookie($json_api->query->cookie, 'logged_in') ? true : false;

    	if (!$valid) {
			$json_api->error("You must login to access these data.");
		}	

		return array("config" => Kidzou_Utils::get_options());
		
	}

	
}	

?>