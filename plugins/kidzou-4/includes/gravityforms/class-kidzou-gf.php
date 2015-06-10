<?php

/** 
 * Intégraiton avec Gravity Forms
 *
 */
class Kidzou_GF  {

	/**
	 *
	 *
	 * @since     0.1
	 */
	public function __construct() { 

	}

	/**
	 * Send out our notifications
	 * @param  int $form_id  Form ID
	 * @param  int $entry_id Entry ID
	 * @return bool
	 */

	// public static function send_notifications($form_id, $entry_id){
	// 	Kidzou_Utils::log('send_notifications ' . $form_id.', '.$entry_id, true);
	//     // Get the array info for our forms and entries
	//     // that we need to send notifications for
	//     $form = RGFormsModel::get_form_meta($form_id);
	//     $entry = RGFormsModel::get_lead($entry_id);
	//     // Loop through all the notifications for the
	//     // form so we know which ones to send
	//     $notification_ids = array();
	//     Kidzou_Utils::log($form['notifications'], true);
	//     foreach($form['notifications'] as $id => $info){
	//       array_push($notification_ids, $id);
	//     }
	//     // Send the notifications
	//     // GFCommon::send_notifications($notification_ids, $form, $entry);
	//   }


	/**
	 * un media est rejeté
	 * 
	 * @todo que fait-on ? doit-on le supprimer ?
	 */
	public static function remove_media( $entry, $form ) {

		Kidzou_Utils::log('Media rejeté' ,true);

		//positionner le statut à "rejected" pour notification du user

	}

	/**
	 * Lorsqu'un media est approuvé, il est attaché au post parent
	 *  
	 */
	public static function accept_media( $entry, $form ) {

		// self::send_notifications($form['id'],$entry['id']);

		// $config_form_id        = Kidzou_Utils::get_option('gf_form_id', '1');
		$config_image_field    = Kidzou_Utils::get_option('gf_field_photo', '1');
		$config_login_field    = Kidzou_Utils::get_option('gf_field_user_id', '1');
		$config_post_field     = Kidzou_Utils::get_option('gf_field_post_id', '1');
		$config_comment_field  = Kidzou_Utils::get_option('gf_field_comment', '1');

		$upload_dir = wp_upload_dir();
		$filename 	= 'filename.jpg';

		$url = $entry[$config_image_field]; 

		$parts = parse_url($url);
		$filename = basename($parts['path']);

		// $filename should be the path to a file in the upload directory.
		$filename = $upload_dir['path'].DIRECTORY_SEPARATOR.$filename;

		// The ID of the post this attachment is for.
		$parent_post_id = $entry[$config_post_field];

		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $filename ), null );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $filename ), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		Kidzou_Utils::add_to_gallery($parent_post_id, $attach_id);

		//ajout du commentaire sur l'attachement
		if ($entry[$config_comment_field]!='') {

			$time = current_time('mysql');
			$user = get_user_by('login', $entry[$config_login_field]);

			$data = array(
			    'comment_post_ID' 		=> $attach_id ,
			    'comment_author' 		=> $entry[$config_login_field],
			    'comment_author_email' 	=> $user->user_email,
			    'comment_author_url' 	=> $user->user_url,
			    'comment_content' 		=> $entry[$config_comment_field],
			    'user_id' => $user->ID,
			    'comment_date' => $time,
			    'comment_approved' => 0,
			);

			wp_insert_comment($data);

		}
		
		//positionner le statut à "accepted" pour notification du user

	}


	
}


?>