<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Kidzou
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 * @todo 	  Supprimer les meta sur les posts
 * @todo 	  Supprimer les meta sur les users
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( is_multisite() ) {

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
		/* @TODO: delete all transient, options and files you may have added
		delete_transient( 'TRANSIENT_NAME' );
		delete_option('OPTION_NAME');
		//info: remove custom file directory for main site
		$upload_dir = wp_upload_dir();
		$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
		if (is_dir($directory)) {
			foreach(glob($directory.'*.*') as $v){
				unlink($v);
			}
			rmdir($directory);
		}
		*/
	if ( $blogs ) {

	 	foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			/* @TODO: delete all transient, options and files you may have added
			delete_transient( 'TRANSIENT_NAME' );
			delete_option('OPTION_NAME');
			//info: remove custom file directory for main site
			$upload_dir = wp_upload_dir();
			$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
			if (is_dir($directory)) {
				foreach(glob($directory.'*.*') as $v){
					unlink($v);
				}
				rmdir($directory);
			}
			//info: remove and optimize tables
			$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."TABLE_NAME`");
			$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->prefix."options`");
			*/
			restore_current_blog();
		}
	}

} else {
	/* @TODO: delete all transient, options and files you may have added
	delete_transient( 'TRANSIENT_NAME' );
	delete_option('OPTION_NAME');
	//info: remove custom file directory for main site
	$upload_dir = wp_upload_dir();
	$directory = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "CUSTOM_DIRECTORY_NAME" . DIRECTORY_SEPARATOR;
	if (is_dir($directory)) {
		foreach(glob($directory.'*.*') as $v){
			unlink($v);
		}
		rmdir($directory);
	}
	//info: remove and optimize tables
	$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."TABLE_NAME`");
	$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->prefix."options`");
	*/

	// delete_transient( 'kz_get_national_metropoles' );
    // delete_transient( 'kz_default_metropole' );
    // delete_transient( 'kz_metropoles_incl_national' ); //avec métropoles nationales
    // delete_transient( 'kz_metropoles_excl_national' ); //sans métropoles nationales
    // delete_transient( 'kz_covered_metropoles' );

    delete_transient( 'kz_mailchimp_lists' );

    //supprimer tous les transients des metropoles
 //    $your_transients = $wpdb->get_results(
 //             "SELECT option_name FROM wp_options WHERE option_name LIKE '%kz_post_metropole_%'");

	// foreach ($your_transients as $key => $value) {
 //        //laisser de coté "_transient_" soit 11 chars
 //        $transient_name = substr($value->option_name, 11); 
 //        //laisser de coté les transient de timeout, ils seront supprimés avec leur transient parent
 //        if ( !preg_match('/^timeout/', $transient_name) )
 //           delete_transient( $transient_name );
 //    }

	delete_option('kz_newsletter_auto_display');
	delete_option('kz_map_post_list');
	delete_option('kz_activate_datasync');
	delete_option('kz_debug_mode');
	delete_option('kz_activate_content_tracking');
	// delete_option('kz_activate_syncvotes');
	delete_option('kz_background_link');
	delete_option('kz_db_version');
	delete_option('kz_first_run');
	delete_option('kz_events_notify');
	// delete_option('kz_clients_db_version');
	// delete_option('kz_clients_first_run');

    $GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->prefix."options`");

    //suppression des meta "featured" sur les commentaires
    $comments = get_comments();
	foreach($comments as $comment) {
		delete_comment_meta($comment->comment_ID, 'featured');
	}

	//suppression des taxonomies 
	global $wp_taxonomies;
	$taxonomy = 'ville';
	if ( taxonomy_exists( $taxonomy))
		unset( $wp_taxonomies[$taxonomy]);

	$taxonomy = 'diver';
	if ( taxonomy_exists( $taxonomy))
		unset( $wp_taxonomies[$taxonomy]);

	$taxonomy = 'age';
	if ( taxonomy_exists( $taxonomy))
		unset( $wp_taxonomies[$taxonomy]);

	//suppression des post types
	// ???

	global $wp_rewrite;

	$wp_rewrite->set_category_base('category/');
	$wp_rewrite->set_tag_base('tag/');

	$wp_rewrite->flush_rules();
    
}