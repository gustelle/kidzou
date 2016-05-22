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
	}


}
