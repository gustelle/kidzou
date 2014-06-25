<?php

add_action('admin_print_scripts', 'add_kzscripts_admin');

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function add_kzscripts_admin ()
{
	 wp_enqueue_script( 'jquery-ui-autocomplete' );
}

//http://wp.smashingmagazine.com/2011/10/04/create-custom-post-meta-boxes-wordpress/

/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'kz_post_meta_connections_setup' );
add_action( 'load-post-new.php', 'kz_post_meta_connections_setup' );

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_post_meta_connections_setup ()
{
	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'kz_connections_meta_boxes' );

	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'kz_post_meta_connections_save', 10, 2 );
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_connections_meta_boxes ()
{
	add_meta_box(
		'kz-connections',			// Unique ID
		esc_html__( 'Fiche Associ&eacute;e (Connections)', 'Fiche' ),		// Title
		'kz_connections_meta_box',		// Callback function
		'post',					// Admin page (or post type)
		'side',					// Context
		'default'					// Priority
	);
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_connections_meta_box ($object, $box)
{
	// wp_nonce_field( basename( __FILE__ ), 'kz_connections_nonce' ); 

	echo '
		<p>
		<label for="kz-connections-input">S&eacute;lectionnez la fiche associ&eacute;e :</label>
		<br />
		<input type="hidden" name="kz-connections-id" id="kz-connections-id" value="'.get_post_meta( $object->ID, 'kz_connections', true ).'" />
		<input type="text" name="kz-connections-input" id="kz-connections-input" value="'
			.kz_connections_to_slug (get_post_meta( $object->ID, 'kz_connections', true ) ).
			'" size="30" />
		</p>
		';

	echo '<script>
    	jQuery(document).ready(function() {

    		jQuery("#kz-connections-input").autocomplete({
				minLength: 3,
				delay:400,
				source: function (request, response) {
					jQuery.ajax({
						url : "'.get_bloginfo('wpurl').'/api/connections/get_fiche_by_slug/",
						dataType: "json",
						data: {
							term: request.term
						}, 
						success: function (data) {
			                response(jQuery.map(data.fiches, function (item) {
			                    return {
			                        label: item.slug,
			                        value: item.id
			                    }
			                }));
			            }
			        });
				},
				select: function( event, ui ) { 
					jQuery("#kz-connections-id").attr("value", ui.item.value); 
    				jQuery("#kz-connections-input").attr("value", ui.item.label); 
					return false; 
				},
				change: function (event, ui) {
				 	if ( jQuery("#kz-connections-input").attr("value")=="")
    		 			 jQuery("#kz-connections-id").attr("value", "");
				}
			});
    		
    	});
    	</script>';
}


/**
 * retrouve le slug d'une fiche a partir de son ID
 *
 * @return le slug du style je-suis-un-slug
 * @author 
 **/
function kz_connections_to_slug ($id)
{
	if ($id==null || $id="")
		return "";

	global $wpdb;
	$res = $wpdb->get_var("SELECT slug FROM wp_connections key1 WHERE key1.id = $id");
	return $res;
}


/**
 * exporte tous les slugs des fiches
 *
 * @return un tableau de slugs 
 * @author 
 **/
function kz_connections_export_slugs ( )
{
	global $wpdb;
	$res = $wpdb->get_results("SELECT slug FROM wp_connections ORDER BY slug",ARRAY_A);
	return $res;
}



/**
 * retrouve le slug d'une fiche a partir de son ID
 *
 * @return le slug du style je-suis-un-slug
 * @author 
 **/
function kz_connections_by_id ($id)
{
	global $wpdb;
	$res = $wpdb->get_row("SELECT * FROM wp_connections key1 WHERE key1.id = $id", ARRAY_A);
	// print_r($res['addresses']);
	$res['addresses']	= unserialize_address($res['addresses']);
	$res['options']	= unserialize_options($res['options']);
	return $res;
}

/**
 * retrouve le premier post associé à la fiche, identifiée par son ID
 *
 * @return void
 * @author 
 **/
function kz_post_by_connections_id ($id)
{
	global $wpdb;
	$args = array(
		'meta_key' => 'kz_connections',
		'meta_value' => $id,
		'posts_per_page'	=> 1
	);

	// echo 'meta_value '.$id;

	// $wpdb->show_errors();
	$query = new WP_Query( $args );
	// $wpdb->print_error();

	$posts = $query->posts;
	wp_reset_query();
	return $posts[0];
	
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function unserialize_address ($serialized)
{
	$adr = maybe_unserialize($serialized);
	$adresse = array();
	if ($adr!=null)
	{
		// echo $serialized;
		foreach ($adr as $key => $value) {
			$adrtmp = $adr[$key];
			$adresse['line_1'] 	=$adrtmp['line_1'];
			$adresse['line_2'] 	=$adrtmp['line_2'];
			$adresse['line_3'] 	=$adrtmp['line_3'];
			$adresse['city'] 		=$adrtmp['city'];
			$adresse['zipcode'] 	=$adrtmp['zipcode'];
			$adresse['latitude'] 	=$adrtmp['latitude'];
			$adresse['longitude'] 	=$adrtmp['longitude'];
			return $adresse;
		}
	}

	return $adresse;
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function unserialize_options($serialized)
{
	$options = maybe_unserialize($serialized);
	return $options ;
}

function kz_format_address ($adresse)
{
	return $adresse['line_1'].' '.$adresse['line_2'].' '.$adresse['line_3'].' '.$adresse['zipcode'].' '.$adresse['city'];
}

/**
 * retourne la source du thumb d'une fiche
 *
 * @return void
 * @author 
 * @see http://codex.wordpress.org/Function_Reference/get_children
 **/
function catch_fiche_thumb ($connections_id)
{

	global $wpdb;
	$sql = $wpdb->get_var("SELECT options FROM wp_connections key1 WHERE key1.id = $connections_id");

	$res = maybe_unserialize($sql);

	foreach ($res as $key => $value) {
		return $res['image']['name']['thumbnail'];
	}

	return '';

}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_post_meta_connections_save ($post_id, $post)
{
	/* Verify the nonce before proceeding. */
	// if ( !isset( $_POST['kz_connections_nonce'] ) || !wp_verify_nonce( $_POST['kz_connections_nonce'], basename( __FILE__ ) ) )
	// 	return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	/* Get the posted data and sanitize it for use as an HTML class. */
	$new_meta_value = ( isset( $_POST['kz-connections-id'] ) ? $_POST['kz-connections-id'] : '' );

	/* Get the meta key. */
	$meta_key = 'kz_connections';

	/* Get the meta value of the custom field key. */
	$meta_value = get_post_meta( $post_id, $meta_key, true );

	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, $meta_key, $meta_value );

	// echo 'ben voilà ! ' .$post_id.' '.$new_meta_value ;
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_connections ()
{
	//get post ID
	global $post;

	//Recuperer get_post_meta( $post_id, $meta_key, true );
	$ficheID = get_post_meta( $post->ID, 'kz_connections', true );

	if ($ficheID!=null)
	{
		// echo $shortcode;
		$shortcode  = '[connections id="'.$ficheID.'" ';
		$shortcode .= "addr_format='%label% %line1% %line2% %line3% %zipcode% %city% %state% %country%' enable_search='false' enable_category_select='false' str_work_addr='' str_home_addr='' str_work_phone='T&eacute;l&eacute;phone' str_image='Pas de logo disponible'  str_bio_head='Horaires' str_bio_show='Horaires' str_bio_hide='Fermer les Horaires' str_note_head='Tarifs' str_note_show='Tarifs' str_note_hide='Fermer les Tarifs' str_map_show='Voir la Carte' str_map_hide='Fermer la Carte']";
		echo do_shortcode($shortcode) ;
	}
}

/**
 * retourne l'URL de l'image de la fiche 
 *
 * @return void
 * @author 
 **/
function get_connections_image_base ()
{
	return site_url().'/wp-content/connection_images/';
}
?>