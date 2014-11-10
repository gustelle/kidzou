<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Vote', 'get_instance' ) );

// wp_clear_scheduled_hook( 'set_featured_index' );

// rafraichir l'index featured en fonction des votes
if( !wp_next_scheduled( 'init_vote_meta' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'init_vote_meta' );
}
 
add_action( 'init_vote_meta', array( Kidzou_Vote::get_instance(), 'set_vote_meta') );

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
	// const VERSION = '04-nov';

	/**
	 * marqueur d'insertion du template dans la page
	 *
	 * @since    1.0.0
	 *
	 * @var      Boolean
	 */
	protected static $is_template_inserted = false;

	public static $meta_vote_count = 'kz_reco_count';

	public static $meta_user_votes = 'kz_reco_post_id';

	public static $meta_anomymous_vote = 'kz_anonymous_user';


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
	 * positionne la meta pour les posts qui n'ont jamais été recommandés
	 *
	 */
	public static function set_vote_meta() {
		
		//voir http://wordpress.stackexchange.com/questions/80303/query-all-posts-where-a-meta-key-does-not-exist
		$args = array(
			'posts_per_page' => -1,
			'meta_query' => array(
			   'relation' => 'OR',
			    array(
			     'key' => self::$meta_vote_count,
			     'compare' => 'NOT EXISTS', // works!
			     'value' => '' // This is ignored, but is necessary...
			    ),
			    array(
			     'key' => self::$meta_vote_count,
			     'value' => 1
			    )
			)
		);

		$query = new WP_Query( $args );

		$posts = $query->get_posts();

		Kidzou_Utils::log('set_vote_meta : ' . $query->found_posts . ' meta a creer');

		foreach ($posts as $post) {

			$message = "set_vote_meta {" . $post->ID . "} " ;
			add_post_meta($post->ID, $meta_vote_count, 0, TRUE);

			Kidzou_Utils::log( $message );
			
		}

	}

	public static function getVoteCount($post_id = 0) {

		if ($post_id==0)
		{
			global $post;
			$post_id = $post->ID;
		}

		$count		= get_post_meta($post_id, self::$meta_vote_count, TRUE);

		if ($count=='')
			$count=0;

		return intval($count);
	}

	protected static function set_template($class='', $useCountText=false, $echo=true) {

		$countText = '';

		if ($useCountText)
			$countText .= '<span 	data-bind="text: $data.countText"></span>';

		$out = sprintf('
		<script type="text/html" id="vote-template">
	    <span class="vote %s" data-bind="event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }">
			<i data-bind="css : $data.iconClass"></i>
			<span 	data-bind="text: $data.votes"></span>
			%s
	    </span>
		</script>',
		$class,
		$countText);

		self::$is_template_inserted = true;

		if ($echo)
			echo $out;
		else
			return $out;

	}

	public static function get_vote_template($id=0, $class='', $useCountText=false, $echo=true) {

		if ($id==0)
		{
			global $post;
			$id = $post->ID;
		}

		$out ='';

		if (!self::$is_template_inserted) {
			if ($echo)
				self::set_template('', $useCountText, false, true);
			else
				$out .= self::set_template('', $useCountText, false, false);
		}

		$apost = get_post();
		$slug = $apost->post_name;

		$out .= sprintf(
				"<span class='votable %s' data-post='%s' data-slug='%s' data-bind=\"template: { name: 'vote-template', data: votes.getVotableItem(%s) }\"></span>",
				$class,
				$id,
				$slug,
				$id);

		if ($echo)
			echo $out;
		else
			return $out;

	}


	public static function vote($id=0, $class='', $useCountText=false) {

		echo self::get_vote_template($id, $class, $useCountText, false);

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

	public static function plusOne($id=0, $user_hash='') {

		if ($id==0) 
		{
			return array();
		}


	  	$user_id 	= get_user("ID");
	  	$loggedIn 	= is_user_logged_in();

		// Get votes count for the current post
		$meta_count = get_post_meta($id, self::$meta_vote_count, true);
		// $message 	= '';

		//on ne recalcule pas systématiquement le hash du user, 
		//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
		if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
			$user_hash = self::hash_anonymous();

		// Use has already voted ?
		if(!self::hasAlreadyVoted($id, $loggedIn, $user_id, $user_hash))
		{

			update_post_meta($id, self::$meta_vote_count, ++$meta_count);

			//update les user meta pour indiquer les posts qu'il recommande
			//cela améliore les perfs à terme par rapport à updater les meta du posts avec la liste des users
			//car la liste des posts recommandés est chargée au chargement de la page si le user n'a pas de cookie
			//afin qu il retrouve ses petits...par ex si son cookie est expiré ou si il utilise un autre device

			if ($loggedIn)
			{
				//@todo : tracker le timestamp du vote pour reutilisation analytique
				$meta_posts = get_user_meta(intval($user_id), self::$meta_user_votes);

				Kidzou_Utils::log($meta_posts);
				
				$voted_posts = $meta_posts[0]; 

				if(!is_array($voted_posts))
					$voted_posts = array();

				array_push($voted_posts, $id) ;

				//@todo : tracker le timestamp du vote pour reutilisation analytique
				update_user_meta( $user_id, self::$meta_user_votes, $voted_posts);
			}
			else
			{
				//vote anonyme
				$found = false;
				$hashes = get_post_meta(intval($id), self::$meta_anomymous_vote, FALSE); //il y a plusieurs votes anonymes par post
				foreach ($hashes as $hash) {
					if ($hash==$user_hash)
						$found=true;
				}

				if ($found) { // If the custom field already has a value
					//ben rien, le user avait déjà voté
				} else { 
					add_post_meta( intval($id), self::$meta_anomymous_vote, $user_hash, FALSE ); //plusieurs valeurs possibles sur cette meta
				}
			}

		}
		
		return array('user_hash' => $user_hash);
	}

	public static function minusOne($id=0, $user_hash='') {

		if ($id==0) 
		{
			return array();
		}

		$user_id = get_user("ID");
	  	$loggedIn = is_user_logged_in();

		// Get votes count for the current post
		$meta_count = get_post_meta($id, self::$meta_vote_count, true);
		$message 	= '';

		//on ne recalcule pas systématiquement le hash du user, 
		//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
		if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
			$user_hash = self::hash_anonymous();

		// Use has already voted ?
		if(self::hasAlreadyVoted($id, $loggedIn, $user_id, $user_hash))
		{
			update_post_meta($id, self::$meta_vote_count, --$meta_count);

			//update les user meta pour indiquer les posts qu'il recommande
			//cela améliore les perfs à terme par rapport à updater les meta du posts avec la liste des users
			//car la liste des posts recommandés est chargée au chargement de la page si le user n'a pas de cookie
			//afin qu il retrouve ses petits...par ex si son cookie est expiré ou si il utilise un autre device

			if ($loggedIn)
			{
				//@todo : tracker le timestamp du vote pour reutilisation analytique
				$meta_posts = get_user_meta(intval($user_id), self::$meta_user_votes);
				
				$voted_posts = $meta_posts[0];

				if(!is_array($voted_posts))
					$voted_posts = array();

				foreach ($voted_posts as $i => $value) {
				    //retrait du vote sur le user
				    if ( intval($value)==intval($id) )
						unset($voted_posts[$i]);
				}

				//@todo : tracker le timestamp du vote pour reutilisation analytique
				update_user_meta( $user_id, self::$meta_user_votes, $voted_posts);
			}
			else
				delete_post_meta(intval($id), self::$meta_anomymous_vote, $user_hash );

		}
		
		return array("user_hash" => $user_hash);

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getPostVotes($post_id=0)
	{
		if ($post_id==0) 
		{
			global $post;
			$post_id = $post->ID;
		}

		// global $wpdb;
			
		// 	$res = $wpdb->get_results(
		// 		"SELECT post_id as id,meta_value as votes FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_reco_count' AND key1.post_id = $id", ARRAY_A);

		$results = get_post_meta($id, self::$meta_vote_count, true);
		// Kidzou_Utils::log('votes :'.$res[0]['votes']);
		// Kidzou_Utils::log($results);

		return array(
				"id" 	=> $post_id,
		      	"votes"	=> intval($results)
			);

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getPostsListVotes($list_array=array())
	{
		if (empty($list_array))
			return ;

		global $wpdb;
		$list  = '('.implode(",", $list_array).')'; //echo $list;

		//attention à cette requete
		//ajout de DISTINCT et suppression de la limite car certains couples ID|META_VALUE peuvent être multiples !?
		$res = $wpdb->get_results(
			"SELECT DISTINCT post_id as id,meta_value as votes FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_reco_count' AND key1.post_id in $list ", ARRAY_A); //LIMIT $limit
		
		$status = array();

		$status['status'] = array(); $i=0;
		foreach ($res as &$ares) 
		{
			$status['status'][$i] = &$ares;
			$i++;
		}

		return $status;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getUserVotes($user_hash='') 
	{

		$loggedIn   = is_user_logged_in();
		$voted_posts= array();

		if ($loggedIn)
		//recup des posts recommandes
		//par le user courant, si celui-ci n'est pas anonyme
		{
			//les posts que le user courant recommande sont dans les meta users
			//et non dans les meta post !
			$user_id = intval(get_user('ID'));

			global $wpdb;
			$res = $wpdb->get_results(
								"SELECT meta_value as serialized FROM $wpdb->usermeta WHERE user_id=$user_id AND meta_key='kz_reco_post_id'",
								ARRAY_A
							);
			$unserialized = maybe_unserialize($res[0]['serialized']);//print_r($unserialized);
			$voted = array();
			if ($unserialized!=null)
			{ 
				foreach ($unserialized as &$ares) 
					array_push($voted, array ('id' => intval($ares))) ;
			}

			$voted_posts['voted'] = $voted;
			$voted_posts['user'] = $user_id;
		}
		else 
		//le user est anonyme, on travaille avec son IP+UA pour l'identifier
		{
				
			//verification des données en base
			//le PK pour vérifier les données étant md5(IP+UA)
			global $wpdb;

			//on ne recalcule pas systématiquement le hash du user, 
			//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
			if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
				$user_hash=Kidzou_Vote::hash_anonymous();

			//$hash = hash_anonymous();
			// AND key1.post_id in $list LIMIT $limit
			$res = $wpdb->get_results(
								"SELECT DISTINCT post_id as id FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_anonymous_user' AND key1.meta_value='$user_hash' ", 
								ARRAY_A
							);

			$voted_posts['voted'] 		= $res;
			$voted_posts['user_hash'] 	= $user_hash;
		}

		return $voted_posts;
	}

	/**
	 * - si le user est loguué, on utilise son ID
	 * - si le user est anonymous, on utilise son hash
	 *
	 * @return TRUE si le user a déjà voté le post
	 * @author Kidzou
	 **/
	public static function hasAlreadyVoted($post_id, $loggedIn, $user_id, $user_hash)
	{

		if ($loggedIn)
		{
			//check DB
			$meta_posts = get_user_meta($user_id, self::$meta_user_votes);
			$voted_posts = $meta_posts[0];

			if(!is_array($voted_posts))
				$voted_posts = array();

			if(in_array($post_id, $voted_posts))
				return true;

		}
		else
			return self::hasAnonymousAlreadyVoted ($post_id, $user_hash);

		return false;

	}


	/**
	 * checke if un user anonyme a deja vote pour le poste concerné
	 *
	 * @return TRUE si le user anonyme a deja voté
	 * @author Kidzou
	 **/
	public static function hasAnonymousAlreadyVoted($post_id, $user_hash)
	{
		global $wpdb;
		// $hash = hash_anonymous();
		$res = $wpdb->get_var(
			"SELECT count(*) FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_anonymous_user' AND key1.meta_value='$user_hash' AND key1.post_id=$post_id LIMIT 1"
		);

		if (intval($res)>0)
			return true;

		return false;
	}

    

} //fin de classe

?>