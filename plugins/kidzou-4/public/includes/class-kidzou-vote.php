<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Vote', 'get_instance' ) );

// rafraichir l'index featured en fonction des votes
if( !wp_next_scheduled( 'set_featured_index' ) ) {
   wp_schedule_event( time(), 'daily', 'set_featured_index' );
}
 
add_action( 'set_featured_index', array( Kidzou_Vote::get_instance(), 'set_featured_index') );

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

	public static $meta_vote_count = 'kz_reco_count';


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

	/**
	 * positionne l'index "featured" en fonction du nombre de votes
	 * les posts featured A et B ne sont pas touchés 
	 * A = Featured
	 * B = Evenement
	 * C -> Z = Selon vote des users
	 * 		<20 : Z
	 * 		<50 : Y
	 * 		<100 : X
	 * 		<200 : W
	 * 		<300 : V
	 * 		<400 : U
	 * 		<500 : T
	 * 		>500 : S
	 *
	 */
	public static function set_featured_index() {
		
		//le post le plus recommandé est en index S
		$args = array(
			'meta_key'   => self::$meta_vote_count,
			'orderby'    => 'meta_value_num',
			'order'      => 'DESC',
			'posts_per_page' => -1 //no limit
		);

		$query = new WP_Query( $args );

		$posts = $query->get_posts();

		$arr = array();

		//ne pas oublier
		require_once( plugin_dir_path( __FILE__ ) . '../../admin/class-kidzou-admin.php' );

		foreach ($posts as $post) {

			$message = "set_featured_index {" . $post->ID . "} " ;

			if ( !Kidzou_Events::isFeatured($post->ID) && !Kidzou_Events::isTypeEvent($post->ID) ) {

				$count = self::getVoteCount($post->ID);
				
				if ($count<20) {
					$arr[Kidzou_Events::$meta_featured] = 'Z';
				} elseif ($count<50) {
					$arr[Kidzou_Events::$meta_featured] = 'Y';
				} elseif ($count<100) {
					$arr[Kidzou_Events::$meta_featured] = 'X';
				} elseif ($count<200) {
					$arr[Kidzou_Events::$meta_featured] = 'W';
				} elseif ($count<300) {
					$arr[Kidzou_Events::$meta_featured] = 'V';
				} elseif ($count<400) {
					$arr[Kidzou_Events::$meta_featured] = 'U';
				} elseif ($count<500) {
					$arr[Kidzou_Events::$meta_featured] = 'T';
				} else {
					$arr[Kidzou_Events::$meta_featured] = 'S';
				}

				$message .= " : ".$arr[Kidzou_Events::$meta_featured];

				Kidzou_Admin::save_meta($post->ID, $arr);
			}

			else 
				$message .= " : already existing";

			if ( WP_DEBUG === true )
				error_log( $message );
			
		}

	}

	public static function getVoteCount($post_id = 0) {

		if ($post_id==0)
		{
			global $post;
			$post_id = $post->ID;
		}

		$count		= get_post_meta($post_id, self::$meta_vote_count, TRUE);

		return intval($count);
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