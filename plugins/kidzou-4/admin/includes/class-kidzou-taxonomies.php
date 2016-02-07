<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Taxonomies', 'get_instance' ) );

/**
 * Gestion des règles de rewrite en fonction 
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Taxonomies {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	// /**
	//  *
	//  * @var      array
	//  */
	// protected static $coords = array();


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );

		//nettoyage des transients de geoloc lorsque la taxo "ville bouge"
		//merci https://www.dougv.com/2014/06/25/hooking-wordpress-taxonomy-changes-with-the-plugins-api/
		add_action('create_ville', 	array( $this, 'rebuild_geo_rules') );
		add_action('edit_ville', 	array( $this, 'rebuild_geo_rules') );
		add_action('delete_ville', 	array( $this, 'rebuild_geo_rules') );

		//Plugin Geo Data Store
		// if (class_exists('sc_GeoDataStore')) {

		// 	//hook sur le plugin pour intégration spécifique
		// 	remove_action( 'added_post_meta', array( 'sc_GeoDataStore', 'after_post_meta' ), 10 );
		// 	remove_action( 'updated_post_meta', array( 'sc_GeoDataStore', 'after_post_meta' ), 10);
  //           remove_action( 'updated_postmeta', array( 'sc_GeoDataStore', 'after_post_meta' ), 10);

  //           add_action( 'added_post_meta', array( $this, 'after_post_meta' ), 10, 4 );
		// 	add_action( 'updated_post_meta', array( $this, 'after_post_meta' ), 10, 4);
  //           add_action( 'updated_postmeta', array( $this, 'after_post_meta' ), 10, 4);

		// 	//intégration 'standard' du plugin
		// 	add_filter( 'sc_geodatastore_meta_keys', array( $this, 'store_geo_data') );

		// 	/**
		// 	 * Au changement de statut d'un post on resynchronise le Geo Data Store
		// 	 *
		// 	 * @see Geo Data Store
		// 	 * @link http://codex.wordpress.org/Post_Status_Transitions
		// 	 */
		// 	add_action(  'transition_post_status',  array($this,'on_all_status_transitions'), 10, 3 );

		// }

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



	/**
	 * déclenchée à l'actication de la geoloc
	 * Mise à jour de la structure des permaliens Category et Tag
	 *
	 * Mise à jour du .htaccess avec les règles de geoloc
	 *
	 * @return void
	 * @author 
	 **/
	public static function set_permalink_rules () {
		
		global $wp_rewrite;

		$wp_rewrite->set_category_base( Kidzou_Metropole::REWRITE_TAG . '/rubrique/');
		$wp_rewrite->set_tag_base( Kidzou_Metropole::REWRITE_TAG . '/tag/');

		flush_rewrite_rules();
		
	}

	/**
	 * déclenchée à la desactivation de la geoloc
	 * Mise à jour de la structure des permaliens Category et Tag
	 *
	 * Mise à jour du .htaccess avec les règles de geoloc
	 *
	 * @return void
	 * @author 
	 **/
	public static function unset_permalink_rules () {
		
		global $wp_rewrite;

		$wp_rewrite->set_category_base('rubrique/');
		$wp_rewrite->set_tag_base('tag/');

		flush_rewrite_rules();
		
	}

	/**
	 * permet de reconstruire les regles de ré-ecriture de permaliens et de nettoyer les caches des metropoles
	 *
	 * @return void
	 * @author 
	 **/
	public static function rebuild_geo_rules()
	{

		//nettoyager les transients
		delete_transient('kz_metropoles_incl_national'); //avec métropoles nationales
		delete_transient('kz_metropoles_excl_national'); //sans métropoles nationales
		delete_transient('kz_metropole_uri_regexp');

		//si la geoloc est active uniquement
		if ((bool)Kidzou_Utils::get_option('geo_activate',false)) 
		{
			self::set_permalink_rules();
		}
		else
		{
			self::unset_permalink_rules();
		}

        flush_rewrite_rules();
		// Kidzou_Utils::log('Rewrite rules rafraichies et transients de geoloc nettoyes');

	}


}
