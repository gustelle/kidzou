<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Admin_Customer', 'get_instance' ) );

/**
 * Kidzou
 *
 * @package   Kidzou_Admin
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Admin_Customer {

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

		//pour le B.O (partie admin)
		add_action( 'kidzou_add_metabox', array( $this, 'add_metaboxes') );
		add_action( 'kidzou_save_metabox', array( $this, 'save_metaboxes'), 10, 1);
		
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
	 * Ajout des metabox supplémnetaires à celles gérées par Kidzou_Admin
	 *
	 * @return void
	 * @since customer-analytics
	 * @author 
	 **/
	public function add_metaboxes()
	{
		// Kidzou_Utils::log('Kidzou_Admin_Customer [add_metaboxes]', true);
		$screen = get_current_screen(); 

		if ($screen->id =='customer' )
			add_meta_box('kz_customer_analytics_metabox', 'Google Analytics', array($this, 'add_analytics_metabox'), $screen->id, 'normal', 'high'); 

	}

	/**
	 * Ajout d'une metabox pour autoriser ou non les users du customer à visualiser leurs analytics 
	 *
	 * @return HTML
	 * @since customer-analytics
	 * @author 
	 **/
	public function add_analytics_metabox()
	{
		global $post;

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'analytics_metabox', 'analytics_metabox_nonce' );

		$checkbox = get_post_meta($post->ID, Kidzou_Customer::$meta_customer_analytics , TRUE);

		echo 	'<ul>
					<li>
						<label for="kz_customer_analytics">Autoriser les utilisateurs de ce client &agrave; visualiser les analytics:</label>
						<input type="checkbox" name="'.Kidzou_Customer::$meta_customer_analytics.'"'. ( $checkbox ? 'checked="checked"' : '' ).'/>  
					</li>
				</ul>';
	}

	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un customer
	 *
	 * @return void
	 * @since customer-analytics
	 * @author 
	 **/
	public function save_metaboxes($post_id) {

		$this->save_analytics_metabox($post_id);
		
	}

	/**
	 * sauvegarde de la meta self::$meta_customer_analytics 
	 * qui indique si les users du client peuvent visualiser sur le front les analytics de leurs pages
	 *
	 * @return void
	 * @since customer-analytics
	 * @author 
	 **/
	public function save_analytics_metabox($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		// Check if our nonce is set.
		if ( ! isset( $_POST['analytics_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['analytics_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'analytics_metabox' ) )
			return $post_id;

		$meta = array();

		if ( !isset($_POST[Kidzou_Customer::$meta_customer_analytics]) )
			$meta[Kidzou_Customer::$meta_customer_analytics] = false;
		else
			$meta[Kidzou_Customer::$meta_customer_analytics] = ($_POST[Kidzou_Customer::$meta_customer_analytics]=='on');
			

		Kidzou_Admin::save_meta($post_id, $meta);
	}

}
