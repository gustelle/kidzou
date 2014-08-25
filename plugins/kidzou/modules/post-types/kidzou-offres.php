<?php

if (post_type_exists('offer')=== FALSE) {
	add_action('init', 'create_offers_post_type');
}

function create_offers_post_type() {


	//ne pas faire a chaque appel de page 

	$labels = array(
	    'name'               => 'Offres',
	    'singular_name'      => 'Offre',
	    'add_new'            => 'Ajouter',
	    'add_new_item'       => 'Ajouter une offre',
	    'edit_item'          => 'Modifier l\'offre',
	    'new_item'           => 'Nouvelle offre',
	    'all_items'          => 'Toutes les offres',
	    'view_item'          => 'Voir l\'offre',
	    'search_items'       => 'Chercher des offres',
	    'not_found'          => 'Aucune offre trouvée',
	    'not_found_in_trash' => 'Aucune offre trouvée dans la corbeille',
	    'menu_name'          => 'Offres',
	  );

	  $args = array(
	    'labels'             => $labels,
	    'public'             => true,
	    'publicly_queryable' => true,
	    'show_ui'            => true,
	    'show_in_menu'       => true,
	    'menu_position' 	 => 5, //sous les articles dans le menu
	    'menu_icon' 		 => 'dashicons-smiley',
	    'query_var'          => true,
	    'has_archive'        => true,
	    'rewrite' 			=> array('slug' => 'offres'),
	    'hierarchical'       => false, //pas de hierarchie d'offres
	    'supports' 			=> array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions', 'post-formats'),
	    'taxonomies' 		=> array('age', 'ville', 'divers', 'category'), //reuse the taxo declared in kidzou plugin
	  );

  register_post_type( 'offres', $args );

  flush_rewrite_rules();

}

add_filter( 'default_content', 'kz_offre_editor_content' );

/**
 * permet de structurer l'edition d'une offre
 * 
 * @see http://www.smashingmagazine.com/2011/10/14/advanced-layout-templates-in-wordpress-content-editor/
 * @return void
 * @author 
 **/
function kz_offre_editor_content( $content ) {

	global $current_screen;
	if ( $current_screen->post_type == 'offres' ) {
			
		$content = '
		  	[one_half]Insérez une image ici[/one_half]
		  	[one_half_last]
		  		[box type="bio"]<strong>Tarif réduit xxx</strong><br/>
				lorem ipsum ...
				[/box]
			[/one_half_last]<br/>
			[one_half]

				&nbsp;
				<ul>
					<li><strong>Age</strong> : xxx</li>
					<li><strong>Nom</strong>: xxx</li>
					<li><strong>Adresse</strong> : xxxx</li>
					<li><strong>Téléphone</strong>: xx xx xx xx xx</li>
					<li><strong>Site Internet</strong> : <a href="#" target="_blank">xxxx</a></li>
				</ul>

			[/one_half]
			[one_half_last]

				&nbsp;
				<h3>Tarif réduit xxx</h3>
				Offre réservée aux porteurs de la Carte Famille Kidzou. Si vous ne l&apos;avez pas, commandez-l&agrave; tout de suite  pour 9,90 &euro; seulement : [button type="big" newwindow="yes" link="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=8AC4KUBLMR3SY" ] Je commande la Carte Famille Kidzou[/button] [button color="orange" newwindow="yes" link="http://www.kidzou.fr/la-carte-famille-kidzou/" ] En savoir plus sur la carte famille Kidzou[/button] 

			[/one_half_last]<br/>
			<p style="text-align: right;">&gt; <a href="http://www.kidzou.fr/category/offres/">Retour aux offres</a></p>
			<strong>Détails de l&apos;offre</strong> : Tarif r&eacute;duit accord&eacute; au d&eacute;tenteur de la carte famille Kidzou.
			<br/>
		';
	}
	return $content;
}

/**
 * undocumented function
 *
 * @return true si l'événement est en cours, false si il est terminé ou pas visible
 * @author 
 **/
function  kz_is_offre()
{
	global $post;
	$type = get_post_type($post);
	return $type=='offres';
}

add_action( 'save_post_offres', 'kz_save_offres_metropole' );

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_save_offres_metropole($post_id)
{
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    
    if ( current_user_can( 'manage_options', $post_id )) {

    
    } else {

    	if (function_exists('kz_set_post_user_metropole'))
    		kz_set_post_user_metropole($post_id);
    } 

    
}



?>