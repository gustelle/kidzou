<?php
/*
Controller Name: Social
Controller Description: API d'échange de données avec les réseaux sociaux
Controller Author: Kidzou 
*/

class JSON_API_Social_Controller {

	/**
	 *
	 * Recupere les data de facebook et les injecte dans Kidzou pour transformer le suer FB en user WP
	 * 
	 * @param token
	 * @return WP_User 
	 **/
	public function facebookConnect()
	{
		global $json_api;
		
		if ($json_api->query->fields) {

			$fields = $json_api->query->fields;

		} else {

			$fields = 'id,name,first_name,last_name,email';
		}
		
		if ($json_api->query->ssl) {
			 $enable_ssl = $json_api->query->ssl;
		} else $enable_ssl = true;
	
		if (!$json_api->query->access_token) {
			$json_api->error("You must include a 'access_token' variable. Get the valid access_token for this app from Facebook API.");
		}else{
			
			$url='https://graph.facebook.com/me/?fields='.$fields.'&access_token='.$json_api->query->access_token;
				
				//  Initiate curl
			$ch = curl_init();
			// Enable SSL verification
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $enable_ssl);
			// Will return the response, if false it print the response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Set the url
			curl_setopt($ch, CURLOPT_URL,$url);
			// Execute
			$result=curl_exec($ch);
			// Closing
			curl_close($ch);

			$result = json_decode($result, true);
			
		   if(isset($result["email"])){
		          
	            $user_email = $result["email"];
	           	$email_exists = email_exists($user_email);
				
				if($email_exists) {

					$user = get_user_by( 'email', $user_email );
					$user_id = $user->ID;
					$user_name = $user->user_login;
				}
			   
			    if ( !$user_id && $email_exists == false ) {
					
				  $user_name = strtolower($result['first_name'].'.'.$result['last_name']);
	               				
					while(username_exists($user_name)){		        
					$i++;
					$user_name = strtolower($result['first_name'].'.'.$result['last_name']).'.'.$i;			     
		
				}
					
				$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
				$userdata = array(
				       'user_login'    => $user_name,
					   'user_email'    => $user_email,
				       'user_pass'  => $random_password,
					   'display_name'  => $result["name"],
					   'first_name'  => $result['first_name'],
					   'last_name'  => $result['last_name']
				    );

	            $user_id = wp_insert_user( $userdata ) ;				   
				if($user_id) $user_account = 'user registered.';
					 
	            } else {
					
					if($user_id) $user_account = 'user logged in.';
				}
				
				$expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, true);
		    	$cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
		        
				$response['msg'] = $user_account;
				$response['wp_user_id'] = $user_id;
				$response['cookie'] = $cookie;
				$response['user_login'] = $user_name;	
				
			} else {
				$response['msg'] = "Your 'access_token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.";
			}
		
		}	

		return $response;
	}

	/**
	 *
	 * Recupere les data de google et les injecte dans Kidzou pour transformer le suer FB en user WP
	 * 
	 * @param token
	 * @return WP_User 
	 **/
	public function googleConnect()
	{
		global $json_api;
		
		if ($json_api->query->fields) {

			$fields = $json_api->query->fields;

		} else {

			$fields = 'id,name,first_name,last_name,email';
		}
		
		if ($json_api->query->ssl) {
			 $enable_ssl = $json_api->query->ssl;
		} else $enable_ssl = true;
	
		if (!$json_api->query->access_token) {
			$json_api->error("You must include a 'access_token' variable. Get the valid access_token for this app from Facebook API.");
		}else{
			
			$url='https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token='.$json_api->query->access_token;
				
				//  Initiate curl
			$ch = curl_init();
			// Enable SSL verification
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $enable_ssl);
			// Will return the response, if false it print the response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Set the url
			curl_setopt($ch, CURLOPT_URL,$url);
			// Execute
			$result=curl_exec($ch);
			// Closing
			curl_close($ch);

			$result = json_decode($result, true);

			// Kidzou_Utils::log('googleapis', true);
			Kidzou_Utils::log($result, true);
			
		   if(isset($result["email"])){
		          
	            $user_email = $result["email"];
	           	$email_exists = email_exists($user_email);
				
				if($email_exists) {

					// Kidzou_Utils::log('email_exists', true);

					$user = get_user_by( 'email', $user_email );
					$user_id = $user->ID;
					$user_name = $user->user_login;
				}
			   
			    if ( !$user_id && $email_exists == false ) {
					
				  $user_name = $result['given_name'];
	               				
					while(username_exists($user_name)){		        
						$i++;
						$user_name = $result['given_name'].$i;			     
					}
					
					$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
					$userdata = array(
					       'user_login'    => $user_name,
						   'user_email'    => $user_email,
					       'user_pass'  => $random_password,
						   'display_name'  => $result["name"],
						   'user_nicename' => $result["name"],
						   'first_name'  => $result['given_name'],
						   'last_name'  => $result['family_name'],
						   'user_url'    =>  $result['link'],
					    );

		            $user_id = wp_insert_user( $userdata ) ;				   
					if($user_id) $user_account = 'user registered.';
					 
	            } else {
					
					if($user_id) $user_account = 'user logged in.';
				}
				
				$expiration = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, true);
		    	$cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
		        
				$response['msg'] = $user_account;
				$response['wp_user_id'] = $user_id;
				$response['cookie'] = $cookie;
				$response['user_login'] = $user_name;	
				
			} else {
				$response['msg'] = "Your 'access_token' did not return email of the user. Without 'email' user can't be logged in or registered. Get user email extended permission while joining the Facebook app.";
			}
		
		}	

		return $response;
	}


	
}	

?>