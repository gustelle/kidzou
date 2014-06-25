<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

	$comments = get_comments();
	foreach($comments as $comment) {
		delete_comment_meta($comment->comment_ID, 'featured');
	}

?>