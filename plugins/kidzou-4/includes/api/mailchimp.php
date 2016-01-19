<?php
/*
Controller Name: Mailchimp
Controller Description: Accès aux services de Mailchimp
Controller Author: Kidzou 
*/

/**
 * Extension du plugin JSON API, cet End Point permet d'attaquer les WS Mailchimp pour souscrire a la newsletter
 *
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 * @package Kidzou
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

		if ( '' == $key ) 
			$json_api->error("Une clé doit être fournie pour accéder a ce service");

		if ( '' == $list_id ) 
			$json_api->error("Un ID de Liste doit être fourni pour accéder a ce service");

	    if (!wp_verify_nonce($nonce, 'newsletter_subscribe_nonce')) {
	    	$json_api->error("Your 'nonce' value was incorrect. Use the 'get_nonce' API method.");
	    }

	    $fields = Kidzou_Utils::get_option('newsletter_fields', array());
		$is_firstname 	= $fields['firstname'];
		$is_lastname 	= $fields['lastname'];
		$is_zipcode 	= $fields['zipcode'];

		$lastname 	= sanitize_text_field( $json_api->query->lastname );
		$email  	= array( 'email' => sanitize_email( $json_api->query->email ) );
		$firstname 	= sanitize_text_field( $json_api->query->firstname );
		$zipcode 	= sanitize_text_field( $json_api->query->zipcode );

		if ($email['email']==='') {
			return array(
				'result' => 'error', 
				'fields'	=> array('email' => array('message' => __('Veuillez renseigner l&apos;adresse e-mail !','kidzou' ) ))
			);
		}

		if ( $is_zipcode && !preg_match("/^[0-9]{5}$/", $zipcode) ) {
			return array(
				'result' => 'error', 
				'fields'	=> array('zipcode' => array('message' => __('Le code postal est incorrect !','kidzou' ) ))
			);
		}

		$mailchimp = new MailChimp( $key );

		$merge_vars = array();

		if ($is_firstname) 
			$merge_vars['PRENOM'] = $firstname;
		if ($is_lastname) 
			$merge_vars['NOM'] = $lastname;
		if ($is_zipcode) 
			$merge_vars['CODEPOSTAL'] = $zipcode;

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
			'message'	=> $message,
			'fields' => $merge_vars
		);
	}

	
}	

?>