<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Featured', 'get_instance' ) );


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
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
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
	 * Ajoute la metabox "Featured" qui contient une checkbox. Uniquement affichée pour les utilisateurs > author
	 *
	 *
	 * @return void
	 * @author 
	 **/
	public function add_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta) && Kidzou_Utils::current_user_is('author')) { 
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
			
		$checkbox = Kidzou_Featured::isFeatured($post->ID);
		echo '	<label for="kz_featured">Mise en avant:</label>
				<input type="checkbox" name="kz_featured"'. ( ($checkbox==1 || $checkbox==true)   ? 'checked="checked"' : '' ).'/>  
				';
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

		$featured = (isset($_POST['kz_featured']) && $_POST['kz_featured']=='on');

		Kidzou_Featured::setFeatured($post_id, $featured);

	}


}
