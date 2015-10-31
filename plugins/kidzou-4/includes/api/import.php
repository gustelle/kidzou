<?php
/*
Controller Name: Import
Controller Description: Import de contenu
Controller Author: Kidzou 
*/

class JSON_API_Import_Controller {

	/** 
	 * Permet d'importer un media stocké sur une URL web
	 *
	 */
	public function addMediaFromURL() {

		global $json_api;

		if ( $_SERVER['REQUEST_METHOD']!='POST' ) $json_api->error("Utilisez la methode POST pour cette API");
		

		if ( !isset($_POST['url']) || !isset($_POST['title']) ||  !isset($_POST['post_id'])) $json_api->error("Données manquantes");

		$url = $_POST['url'];
		$title = $_POST['title'];
		$post_id = $_POST['post_id'];

		if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) $json_api->error("L'URL fournie n'est pas acceptée");

		//@see https://codex.wordpress.org/Function_Reference/media_handle_sideload
		// Need to require this files
		if ( !function_exists('media_handle_upload') ) {
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');
		}

		$tmp = download_url( $url );
		if( is_wp_error( $tmp ) ){
			$json_api->error("Erreur lors de la recuperation de l'URL");
		}

		$file_array = array();

		// Set variables for storage
		// fix file filename for query strings
		preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png|webp)/i', $url, $matches);
		$file_array['name'] = $title.'.'.$matches[1];
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff
		Kidzou_Utils::log($file_array,true);
		$attach_id = media_handle_sideload( $file_array, $post_id, $title );

		// If error storing permanently, unlink
		if ( is_wp_error($attach_id) ) {
			@unlink($file_array['tmp_name']);
			$json_api->error($attach_id->get_error_messages());
		}

		$src = wp_get_attachment_url( $attach_id );

		set_post_thumbnail( $post_id, $attach_id );

		return array('src' => $src);
	}

	
}	

?>