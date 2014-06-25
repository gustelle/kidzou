<?php

// if (is_admin()) 

// {

//ajout de taxonomy ville
// if (!taxonomy_exists('ville')) {
	add_action( 'init', 'create_city_taxonomies', 0 );
// }

//create taxonomy ville
function create_city_taxonomies()
{

  // Add new taxonomy, make it hierarchical (like categories)
  $labels = array(
    'name' => _x( 'Ville', 'taxonomy general name' ),
    'singular_name' => _x( 'Ville', 'taxonomy singular name' ),
    'search_items' =>  __( 'Chercher par ville' ),
    'all_items' => __( 'Toutes les villes' ),
    'parent_item' => __( 'Ville Parent' ),
    'parent_item_colon' => __( 'Ville Parent:' ),
    'edit_item' => __( 'Modifier la Ville' ),
    'update_item' => __( 'Mettre à jour la Ville' ),
    'add_new_item' => __( 'Ajouter une ville' ),
    'new_item_name' => __( 'Nom de la nouvelle ville' ),
    'menu_name' => __( 'Ville' ),
  );

  //intégration avec event dans le register_post_type event
  register_taxonomy('ville',array('post','page', 'user'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'ville' ),
  //   'capabilities' => array(
		// 	'manage_terms' 	=> 'manage_categories',
		// 	'edit_terms' 	=> 'manage_categories',
		// 	'delete_terms' 	=> 'manage_categories',
		// 	'assign_terms' 	=>	'edit_posts' 
		// )
  ));

}



//ajout de taxonomy transverse (loisirs, vacances, week-end...)
// if (!taxonomy_exists('divers')) {
	add_action( 'init', 'create_loisirs_taxonomies', 0 );
// }

//create taxonomy transverse
function create_loisirs_taxonomies()
{

	  // Add new taxonomy, make it hierarchical (like categories)
	  $labels = array(
		'name' => _x( 'Divers', 'taxonomy general name' ),
		'singular_name' => _x( 'Divers', 'taxonomy singular name' ),
		'search_items' =>  __( 'Chercher' ),
		'all_items' => __( 'Tous les divers' ),
		'parent_item' => __( 'Cat&eacute; Divers Parent' ),
		'parent_item_colon' => __( 'Divers Parent:' ),
		'edit_item' => __( 'Modifier une cat&eacute;gorie divers' ),
		'update_item' => __( 'Mettre a  jour une cat&eacute;gorie divers' ),
		'add_new_item' => __( 'Ajouter une cat&eacute;gorie divers' ),
		'new_item_name' => __( 'Nouvelle cat&eacute;gorie divers' ),
		'menu_name' => __( 'Divers' ),
	  );

	  register_taxonomy('divers',array('post','page'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'divers' ),
		// 'capabilities' => array(
		// 	'manage_terms' 	=> 'manage_categories',
		// 	'edit_terms' 	=> 'manage_categories',
		// 	'delete_terms' 	=> 'manage_categories',
		// 	'assign_terms' 	=>	'edit_posts' 
		// )
	  ));


}

//ajout de taxonomy transverse (age)
// if (!taxonomy_exists('age')) {
	add_action( 'init', 'create_age_taxonomies', 0 );
// }

//create taxonomy transverse
function create_age_taxonomies()
{


	  // Add new taxonomy, make it hierarchical (like categories)
	  $labels = array(
		'name' => _x( 'Age', 'taxonomy general name' ),
		'singular_name' => _x( 'Age', 'taxonomy singular name' ),
		'search_items' =>  __( 'Chercher par age' ),
		'all_items' => __( 'Tous les ages' ),
		'parent_item' => __( 'Age Parent' ),
		'parent_item_colon' => __( 'Age Parent:' ),
		'edit_item' => __( 'Modifier l&apos;age' ),
		'update_item' => __( 'Mettre a  jour l&apos;age' ),
		'add_new_item' => __( 'Ajouter un age' ),
		'new_item_name' => __( 'Nom du nouvel age' ),
		'menu_name' => __( 'Tranches d&apos;age' ),
	  );

	  //le cap "edit_events" peut assigner des ages aux events
	  register_taxonomy('age',array('post','page'), array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'age' ),
		// 'capabilities' => array(
		// 	'manage_terms' 	=> 'manage_categories',
		// 	'edit_terms' 	=> 'manage_categories',
		// 	'delete_terms' 	=> 'manage_categories',
		// 	'assign_terms' 	=>	'edit_posts' 
		// )
	  ));


}

// }

?>