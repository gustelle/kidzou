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
 * Classe mutualisée entre Front End et Admin (Back End)
 *
 *
 * @package Kidzou_Notif
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
		if  (!Kidzou_Utils::is_really_admin())
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
	 * en effet, le Hook d'appel `wp_enqueue_scripts` n'est pas appelé dans l'Admin WP
	 *
	 */
	public function enqueue_scripts() {

		global $kidzou_options;

		$kidzou_instance = Kidzou::get_instance();

		wp_enqueue_style( 'endbox', plugins_url( 'kidzou-4/public/assets/css/endpage-box.css' ), array(), Kidzou::VERSION );

		wp_enqueue_script('endbox',	 plugins_url( 'kidzou-4/public/assets/js/jquery.endpage-box.min.js' ),array(), Kidzou::VERSION, true);
		wp_enqueue_script( 'kidzou-notif', plugins_url( 'kidzou-4/public/assets/js/kidzou-notif.js' ), array('jquery', 'ko', 'endbox','kidzou-plugin-script', 'kidzou-storage'), Kidzou::VERSION, true);

		wp_localize_script('kidzou-notif', 'kidzou_notif', array(
				'messages'				=> self::get_messages(),
				'activate'				=> (bool)Kidzou_Utils::get_option('notifications_activate', false),
				'message_title'			=> Kidzou_Utils::get_option('notifications_message_title', ''),
				'newsletter_once'		=> Kidzou_Utils::get_option('notifications_newsletter_once', true),
				'newsletter_nomobile'	=> Kidzou_Utils::get_option('notifications_newsletter_nomobile', true),
			)
		);

	}

	/**
	 * La Liste des messages pour notification dans le front-end
	 * 
	 * <p>Fournit Un tableau associatif avec le contexte des messages (la fréquence) et le contenu des messages
	 * les contenus sont cachés par des `transient` WP</p>
	 *
	 * <p>L'ordre des messages respecte les préférences sélectionnées dans les réglages Kidzou</p>
	 *
	 * <p>Les résultats sont cachés dans un <code>transient</code> pendant 1j</p>
	 * 
	 * @todo faire évoluer l'appel à la méthode interne get_vote_message() pour y injecter les classes et styles disponibles dans les réglages Kidzou
	 * @return Array Context et Messages
	 *
	 **/
	public static function get_messages()
	{
		// Kidzou_Utils::log();
		global $post;

		$messages = array();
		$content = array();

		//Der erreurs surviennent parfois ?! 
		if ( !is_wp_error($post) && $post!=null) 
		{
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

						$order = Kidzou_Utils::get_option('notifications_messages_order', array());

						foreach ($order as $key => $value) {

							if ((bool)$value) {

								switch ($key) {
									case 'newsletter':
										// Kidzou_Utils::log('Message newsletter', true);
										$content[] = self::get_newsletter_message(
												Kidzou_Utils::get_option('notifications_icon_class', ''),
												Kidzou_Utils::get_option('notifications_icon_style', '')
											);
										break;

									case 'vote':
										$content[] = self::get_vote_message(
												Kidzou_Utils::get_option('notifications_icon_class', ''),
												Kidzou_Utils::get_option('notifications_icon_style', '')
											);
										
										break;

									case 'featured':
										
										$posts_list = Kidzou_Featured::getFeaturedPosts();

										foreach ($posts_list as $post) {

											setup_postdata( $post ); 
						
											$content[] = self::get_post_message();

										}
										wp_reset_postdata();

										// Kidzou_Utils::log('Message featured', true);
										
										break;

									case 'cats':

										$cats = Kidzou_Utils::get_option('notifications_include_categories');
										//inclure des catégories supplémentaires
										if ($cats!=null && count($cats)>0) {
											$cats_list = implode(",", $cats);
											$include_posts = get_posts(array('category' => $cats_list));
											foreach ($include_posts as $post) {

												setup_postdata( $post ); 
							
												$content[] = array(
														'id'		=> get_the_ID(),
														'title' 	=> get_the_title(),
														'body' 		=> get_the_excerpt(),
														'target' 	=> get_permalink(),
														'icon' 		=> get_the_post_thumbnail( $post->ID, 'thumbnail' ),
													);

											}
											wp_reset_postdata();
										}
										// Kidzou_Utils::log('Message cats', true);
										break;
		
									default:
										# code...
										break;
								}
							}
							
						}

						if (!empty($content) && count($content)>0)
							set_transient( 'kz_notifications_content_' . $post_type, (array)$content, 60 * 60 * 24 ); //1 jour de cache

					}

				}  //si dans le bon type de contenu
			
			} //si actif
		}

		$messages['content'] = $content;

		Kidzou_Utils::log($messages, true);

		return $messages;
	}

	/**
	 *
	 * @internal
	 */
	private static function get_vote_message($icon_class='', $icon_style='') {

		$icon = sprintf('<i class="fa fa-heart-o fa-3x vote %1$s" style="%2$s"></i>',
			$icon_class,
			$icon_style);

		return array(
				'id'		=> 'vote',
				'title' 	=> __( 'Vous aimez cette sortie ?', 'kidzou' ),
				'body' 		=> __( 'Recommandez cette sortie aux autres parents afin de les aider &agrave; identifier rapidement les meilleurs plans. Cliquez sur le coeur en haut de page ! ', 'kidzou' ),
				'target' 	=> '#',
				'icon' 		=> $icon,
			);

	}

	/**
	 *
	 * @internal
	 */
	private static function get_newsletter_message($icon_class='', $icon_style='') {

		$body = Kidzou::get_newsletter_form();

		$icon = sprintf('<i class="fa fa-newspaper-o fa-3x %1$s" style="%2$s"></i>',
			$icon_class,
			$icon_style);

		return array(
				'id'		=> 'newsletter',
				'title' 	=> __( 'Tenez-vous inform&eacute;(e) !', 'kidzou' ),
				'body' 		=> $body,
				'target' 	=> '#',
				'icon' 		=> $icon,
			);

	}

	/**
	 *
	 * @internal
	 */
	private static function get_post_message() {

		global $post;

		return array(
					'id'		=> get_the_ID(),
					'title' 	=> get_the_title(),
					'body' 		=> get_the_excerpt(),
					'target' 	=> get_permalink(),
					'icon' 		=> get_the_post_thumbnail( $post->ID, 'thumbnail' ),
				);

	}
	

	public static function cleanup_transients() {
		delete_transient('kz_notifications_content_offres');
        delete_transient('kz_notifications_content_page');
        delete_transient('kz_notifications_content_post');

	}


} //fin de classe

?>