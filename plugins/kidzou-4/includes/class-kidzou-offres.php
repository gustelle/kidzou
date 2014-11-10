<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Offres', 'get_instance' ) );

/**
 * Kidzou
 *
 * @package   Kidzou_Offres
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Kidzou_Offres
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Offres {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	// const VERSION = '04-nov';


	// private static $initialized = false;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		add_action('init', array($this, 'register_offres_type'));

	}

	public function register_offres_type() {

		//definir les custom post types
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

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    

} //fin de classe

?>