<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Featured', 'get_instance' ), 11 );


/**
 * Cette classe gère les metabox de Mise en avant des articles via une checkbox
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Metaboxes_Featured {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  d'evenement
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta = array('post'); // typiquement pas les "customer"


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		
		//sauvegarde des meta à l'enregistrement
		add_action( 'kidzou_save_metabox', array( $this, 'save_metaboxes' ) );
		add_action( 'kidzou_add_metabox', array( $this, 'add_metaboxes' ) );
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
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		global $post;
		// $screen = get_current_screen(); 

		// if ( in_array($screen->id , $this->screen_with_meta)  ) { 

			wp_enqueue_script('react',			"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js",	array('jquery'), '0.14.7', true);
			wp_enqueue_script('react-dom',		"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js",	array('react'), '0.14.7', true);

			wp_enqueue_style( 'kidzou-form', 	plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

			wp_enqueue_script('kidzou-react', 	plugins_url( 'assets/js/kidzou-react.js', dirname(__FILE__) ) ,array('react-dom'), Kidzou::VERSION, true);			

			$featured_vars = array();
			$featured_vars['is_featured'] 		= Kidzou_Featured::isFeatured($post->ID);
			$featured_vars['api_base'] 			= site_url();
			$featured_vars['api_save_featured'] 	= site_url()."/api/content/featured/";

			wp_enqueue_script( 'kidzou-featured-metabox', plugins_url( '/assets/js/kidzou-featured-metabox.js', dirname(__FILE__) ), array( 'jquery', 'kidzou-react'), Kidzou::VERSION, true);
			wp_localize_script('kidzou-featured-metabox', 'featured_jsvars', 	$featured_vars);

		// }

	}

	/**
	 * Ajoute la metabox "Featured" qui contient une checkbox. Uniquement affichée pour les utilisateurs > author
	 *
	 *
	 * @return void
	 * @author 
	 **/
	public function add_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta) && Kidzou_Utils::current_user_can('can_edit_featured')) { 
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
			add_meta_box('kz_featured_metabox', 'Mise en avant', array($this, 'add_featured_metabox'), $screen->id, 'normal', 'high');
		} 

	}

	/**
	 *
	 *
	 * @return void
	 * @author 
	 **/
	public function add_featured_metabox()
	{
		global $post; 

		$checkbox = false;

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'featured_metabox', 'featured_metabox_nonce' );
		echo '<div class="react-content"></div>';

	}



	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 **/
	public function save_metaboxes($post_id) {

		$this->save_featured_meta($post_id);

	}


	/**
	 *
	 * @return void
	 * @author 
	 **/
	private function save_featured_meta($post_id)
	{

		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slug = 'post';

	    // If this isn't a 'post', don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		// Check if our nonce is set.
		if ( ! isset( $_POST['featured_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['featured_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'featured_metabox' ) )
			return $post_id;

		// Kidzou_Utils::log('save_featured_meta '.$_POST['kz_featured'],true );

		$featured = (isset($_POST['kz_featured']) && $_POST['kz_featured']=='on');

		Kidzou_Featured::setFeatured($post_id, $featured);

	}


}
