<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Vote', 'get_instance' ) );

/**
 * Kidzou
 *
 * @package   Kidzou_Vote
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
 * @package Kidzou_Vote
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Vote {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '2014.08.24';

	/**
	 * marqueur d'insertion du template dans la page
	 *
	 * @since    1.0.0
	 *
	 * @var      Boolean
	 */
	protected static $is_template_inserted = false;


	// private static $initialized = false;

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

	protected static function set_template($class='', $useCountText=false) {

		$countText = '';

		if ($useCountText)
			$countText .= '<span 	data-bind="text: $data.countText"></span>';

		echo '
		<script type="text/html" id="vote-template">
	    <span class="vote '.$class.'" data-bind="event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }">
			<i data-bind="css : $data.iconClass"></i>
			<span 	data-bind="text: $data.votes"></span>'
			.$countText.'
	    </span>
		</script>';

		self::$is_template_inserted = true;

	}

	public static function vote($id=0, $class='', $useCountText=false) {

		if ($id==0)
		{
			global $post;
			$id = $post->ID;
		}

		if (!self::$is_template_inserted) {
			self::set_template('', $useCountText);
		}

		echo '
		<span class="votable '.$class.'"  
				data-post="'.$id.'" 
				data-bind="template: { name: \'vote-template\', data: votes.getVotableItem('.$id.') }"></span>';

	}

	/**
	 * renvoie l'adresse IP de l'utilisateur
	 * pour securiser les vote des users 
	 *
	 * @return IP Address (String)
	 * @author http://www.media-camp.fr/blog/developpement/recuperer-adresse-ip-visiteur-php
	 **/
	public static function get_ip()
	{
	    if ( isset ( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
	    {
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    }
	    elseif ( isset ( $_SERVER['HTTP_CLIENT_IP'] ) )
	    {
	        $ip  = $_SERVER['HTTP_CLIENT_IP'];
	    }
	    else
	    {
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}

	/**
	 * hash pour identifier un user anonyme entre 2 votes
	 *
	 * @return a hash string to identify "uniquely" an anonymous user
	 * @author Kidzou
	 **/
	public static function hash_anonymous()
	{
	  $ip = self::get_ip(); 
	  $ua = $_SERVER['HTTP_USER_AGENT'];

	  return md5( $ip . $ua );
	}

    

} //fin de classe

?>