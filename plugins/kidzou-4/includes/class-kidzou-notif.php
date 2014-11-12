<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Notif', 'get_instance' ) );


/**
 * Kidzou
 *
 * @package   Kidzou_Notif
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
 * @package Kidzou_API
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Notif {


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

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

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
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		global $kidzou_options;

		$kidzou_instance = Kidzou::get_instance();

		wp_localize_script($kidzou_instance->get_plugin_slug() . '-plugin-script', 'kidzou_notif', array(
				
				'messages'				=> self::get_messages()
			)
		);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function get_messages()
	{
		$messages = array();
		$content = array();
		global $post;

		$messages['context'] = $post->ID;

		array_push($content, array(
				'id'		=> 'vote',
				'title' 	=> __( 'Vous aimez cette sortie ?', 'kidzou' ),
				'body' 		=> __( 'Recommandez cette sortie aux autres parents afin de les aider &agrave; identifier rapidement les meilleurs plans. Pour cela, cliquez sur le coeur en haut de page ! Les sorties les plus recommand&eacute;es remontent en t&ecirc;te de liste dans la page des recommandaitons ...', 'kidzou' ),
				'target' 	=> 'http://#',
				'icon' 		=> '<i class="fa fa-heart"></i>',
			));

		$featured = Kidzou_Events::getFeaturedPosts();

		// Kidzou_Utils::log($featured);

		global $post;
		foreach ($featured as $post) {
			setup_postdata( $post ); 
			array_push($content, array(
				'id'		=> get_the_ID(),
				'title' 	=> get_the_title(),
				'body' 		=> get_the_excerpt(),
				'target' 	=> 'todo',
				'icon' 		=> 'todo',
			));
		}
		wp_reset_postdata();

		$messages['content'] = $content;

		return $messages;
	}

} //fin de classe

?>