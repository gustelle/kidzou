<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

	$comments = get_comments();
	foreach($comments as $comment) {
		delete_comment_meta($comment->comment_ID, 'featured');
	}

	//notification des evenements
	register_deactivation_hook( __FILE__, 'kz_remove_events_notification' );
	function kz_remove_events_notification(){
	  wp_clear_scheduled_hook( 'kz_notifications' );
	}

?>