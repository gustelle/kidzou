<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Admin_Events', 'get_instance' ) );

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
 * 
 * @todo Décharger la classe Admin dans cette class pour y voir clair dans le code
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Admin_Events {

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
		
		//sauvegarde des meta à l'enregistrement
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );
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
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 **/
	public function save_metaboxes($post_id) {

		$this->unarchive_event($post_id);

	}

	/**
	 * <p>
	 * Un événement obsolète est automatiquement dépublié ou archivé par le système.
	 * Lorsqu'un événement est marqué par la meta "archive" , le système sait que le traitement est déjà passé sur cet evenement et ne cherche pas à repasser dessus 
	 * <br/>
	 * Lorsqu'un user reactualise un événement (changement de dates), il faut supprimer cette meta pour que le traitement puisse repasser dessus et le ré-archiver ou dépublier selon préférence de l'utilisateur
	 * </p>
	 *
	 * @internal
	 *
	 **/
	private function unarchive_event($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		delete_post_meta($post_id, Kidzou_Events::$meta_archive); 

		Kidzou_Utils::log('Evenement '.$post_id. ' désarchivé');
	}

}
