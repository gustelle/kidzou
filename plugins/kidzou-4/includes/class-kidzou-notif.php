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

		wp_enqueue_script('endbox',	 plugins_url( 'kidzou-4/public/assets/js/jquery.endpage-box.min.js' ),array(), Kidzou::VERSION, true);
		wp_enqueue_style( 'endbox', plugins_url( 'kidzou-4/public/assets/css/endpage-box.css' ), null, Kidzou::VERSION );
		// wp_enqueue_style( 'ns-other', plugins_url( 'kidzou-4/public/assets/css/ns-style-other.css' ), null, Kidzou::VERSION );

		wp_localize_script($kidzou_instance->get_plugin_slug() . '-plugin-script', 'kidzou_notif', array(
				
				'messages'				=> self::get_messages(),
				'activate'				=> (bool)Kidzou_Utils::get_option('notifications_activate', false),
				// 'delay'					=> (int)Kidzou_Utils::get_option('notifications_delay', 0),
				// 'duration'				=> (int)Kidzou_Utils::get_option('notifications_duration', 3)
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
		global $post;

		$messages = array();
		$content = array();
		$current_post_id = $post->ID;

		$activate = (bool)Kidzou_Utils::get_option('notifications_activate', false);
		$notification_types = Kidzou_Utils::get_option('notifications_post_type', array());
		$post_type = get_post_type( $current_post_id );
		$frequency = Kidzou_Utils::get_option('notifications_context');

		if ($frequency == 'page')
			$messages['context'] = $post->ID;
		else 
			$messages['context'] = $frequency;

		if ($activate && ( is_page() || is_single() ) ) 
		{
			//seulement si les notifs sont activées pour le type de post courant
			if (isset($notification_types[$post_type]) && $notification_types[$post_type]) {

				$content = get_transient('kz_notifications_content_' .$post_type);

				if ( false===$content || empty($content) ) {
					
					$cats = Kidzou_Utils::get_option('notifications_include_categories');

					//pour les single, on pousse les reco dans la liste des messages
					if (is_single()) {

						$content[] = array(
								'id'		=> 'vote',
								'title' 	=> __( 'Vous aimez cette sortie ?', 'kidzou' ),
								'body' 		=> __( 'Recommandez cette sortie aux autres parents afin de les aider &agrave; identifier rapidement les meilleurs plans. Cliquez sur le coeur en haut de page ! ', 'kidzou' ),
								'target' 	=> '#',
								'icon' 		=> '<i class="fa fa-heart-o fa-3x vote"></i>',
							);

						Kidzou_Utils::log('taille du content ' . count($content));
					}

					$featured = Kidzou_Events::getFeaturedPosts();
					$include_posts = array();

					//inclure des catégories supplémentaires
					if ($cats!=null && count($cats)>0) {
						$cats_list = implode(",", $cats);
						$include_posts = get_posts(array('category' => $cats_list));
					}

					$posts_list = array_merge($featured, $include_posts);

					// Kidzou_Utils::log('posts list');
					// Kidzou_Utils::log($posts_list);

					foreach ($posts_list as $post) {

						setup_postdata( $post ); 

						//si l'utilisateur est déjà sur le featured... on n'envoie pas la notification
						// if ( get_the_ID() != $current_post_id ) {

							// Kidzou_Utils::log('ajout dans content : ' . get_the_ID());

							$content[] = array(
									'id'		=> get_the_ID(),
									'title' 	=> get_the_title(),
									'body' 		=> get_the_excerpt(),
									'target' 	=> get_permalink(),
									'icon' 		=> get_the_post_thumbnail( $post->ID, 'thumbnail' ),
								);

							// Kidzou_Utils::log('taille du content ' . count($content));
						// }
					}
					
					wp_reset_postdata();

					set_transient( 'kz_notifications_content_' . $post_type, (array)$content, 60 * 60 * 24 ); //1 jour de cache
					Kidzou_Utils::log('kz_notifications_content ' . count($content));

				}

			}  //si dans le bon type de contenu
		
		} //si actif

		$messages['content'] = $content;

		return $messages;
	}

} //fin de classe

?>