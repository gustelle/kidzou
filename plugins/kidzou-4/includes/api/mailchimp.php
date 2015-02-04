<?php
/*
Controller Name: Mailchimp
Controller Description: Accès aux services de Mailchimp
Controller Author: Kidzou 
*/

/**
 *
 * @api 
 */
class JSON_API_Mailchimp_Controller {

	/**
	 * undocumented function
	 *
	 * @return Array 
	 **/
	public function subscribe()
	{
		global $json_api;

		$key 		= $json_api->query->key;
		$list_id 	= $json_api->query->list_id;
		$nonce 		= $json_api->query->nonce;

		if ( '' === $key ) 
			$json_api->error("Une clé doit être fournie pour accéder a ce service");

		if ( '' === $list_id ) 
			$json_api->error("Un ID de Liste doit être fourni pour accéder a ce service");

	    if (!wp_verify_nonce($nonce, 'newsletter_subscribe_nonce')) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

		$lastname 	= sanitize_text_field( $json_api->query->lastname );
		$email  	= array( 'email' => sanitize_email( $json_api->query->email ) );
		$firstname 	= sanitize_text_field( $json_api->query->firstname );
		$zipcode 	= sanitize_text_field( $json_api->query->zipcode );

		if ($email['email']==='') {
			return array(
				'result' => 'error', 
				'message'	=> 'Veuillez renseigner l&apos;adresse e-mail !'
			);
		}

		$mailchimp = new MailChimp( $key );

		$merge_vars = array(
			'PRENOM' => $firstname,
			'NOM' => $lastname,
			'CODEPOSTAL' => $zipcode
		);

		$retval =  $mailchimp->call('lists/subscribe', array(
			'id'         => $list_id,
			'email'      => $email,
			'merge_vars' => $merge_vars,
			'update_existing'   => true,
		));

		$result = '';
		$message = '';

		if ( isset($retval['error']) ) {
			$result = 'error' ;
			$message = __('Une erreur est survenue, nous en sommes d&eacute;sol&eacute;s','kidzou') ;
		} else {
			$result = 'success' ;
			$message = __('Merci de votre confiance, vous allez recevoir une email pour confirmer votre inscription !','kidzou') ;
		}

		return array(
			'result' => $result, 
			'message'	=> $message
		);
	}

	
}	

?>