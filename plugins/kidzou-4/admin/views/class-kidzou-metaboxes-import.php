<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Import', 'get_instance' ), 12);


/**
 * Metabox d'import de contenu
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Metaboxes_Import {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * les ecrans qui supportent cette meta
	 *
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $supported_screens = array('post'); // typiquement pas les "customer"


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		
		//sauvegarde des meta à l'enregistrement
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
	}


	/**
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		$screen = get_current_screen(); 

		//on a besoin de font awesome dans le paneau d'admin

		if ( in_array($screen->id , $this->supported_screens)  ) {

			wp_enqueue_script('moment',			"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
			wp_enqueue_script('moment-locale',	"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);

			wp_enqueue_script('react',				"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js",	array(), '0.14.7', true);

			wp_enqueue_script('kidzou-import-metabox', plugins_url( 'assets/js/kidzou-import-metabox.js', dirname(__FILE__) ) ,array('jquery', 'moment'), Kidzou::VERSION, true);
			// wp_enqueue_script('kidzou-import-metabox', plugins_url( 'assets/js/jsx/kidzou-import-metabox.jsx', dirname(__FILE__) ) ,array('jsx-transformer', 'jquery', 'moment'), Kidzou::VERSION, true);

			$facebook_appId 	= Kidzou_Utils::get_option('fb_app_id','');
			$facebook_appSecret = Kidzou_Utils::get_option('fb_app_secret','');

			wp_localize_script('kidzou-import-metabox', 'import_jsvars', array(
					'api_addMediaFromURL'			=> site_url()."/api/import/addMediaFromURL/",
					'facebook_appId'				=> $facebook_appId,
					'facebook_appSecret'			=> $facebook_appSecret,
					'import_form_parent'			=> '#kz_import_metabox .inside', //noeud DOM dans lequel injecter le form
					'background_import'				=> false
				)
			);

			// add_filter( 'script_loader_tag', array($this, 'jsx_tag'), 10, 3 );

		} 
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
	 * Hook d'ajout de metabox
	 *
	 * @since     1.0.0
	 *
	 */
	public function add_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->supported_screens) ) { 

			add_meta_box('kz_import_metabox', 'Import de contenu', array($this, 'import_metabox'), $screen->id, 'normal', 'high');
		} 
	}

	/**
	 * Changement du type de la balise <script> pour indiquer qu'il s'agit d'un template JSX de ReactJS
	 *
	 * @return void
	 * @author 
	 **/
	public function jsx_tag($tag, $handle, $src ) {
	  if ( 'kidzou-import-metabox' == $handle ) {
	    $tag = str_replace( "<script type='text/javascript'", "<script type='text/jsx'", $tag );
	  }
	  return $tag;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function import_metabox()
	{
		//tout est fait par React
	}



	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 **/
	public function save_metaboxes($post_id) {

		//rien a enregistrer
	}



}
