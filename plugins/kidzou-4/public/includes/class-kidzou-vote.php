<?php

add_action( 'kidzou_loaded', array( 'Kidzou_Vote', 'get_instance' ) );

// wp_clear_scheduled_hook( 'set_featured_index' );

// rafraichir l'index featured en fonction des votes
if( !wp_next_scheduled( 'featured_index' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'featured_index' );
}
 
add_action( 'featured_index', array( Kidzou_Vote::get_instance(), 'update_featured_index') );

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
	 * positionne l'index "featured" en fonction du nombre de votes
	 * les posts featured A et B ne sont pas touchés 
	 * A = Featured
	 * B0x à B1 = Evenement dans les 7 jours, selon recommandation
	 * C0x à C1 = Post recommandés
	 * Z0x à Z1 = Evenement au dela de 7 jours, selon recommandation
	 *
	 */
	public static function update_featured_index() {
		
		//ne pas baser la requete sur une meta
		//car certains posts n'ont pas de meta...
		$args = array(
			'posts_per_page' => -1 //no limit
		);

		$query = new WP_Query( $args );

		$posts = $query->get_posts();

		if ( WP_DEBUG === true )
			error_log( 'update_featured_index : ' . $query->found_posts . ' posts a indexer' );

		$arr = array();

		//ne pas oublier
		require_once( plugin_dir_path( __FILE__ ) . '../../admin/class-kidzou-admin.php' );

		foreach ($posts as $post) {

			$message = "update_featured_index {" . $post->ID . "} " ;

			$count = (int)self::getVoteCount($post->ID);
			$index = (float)($count<2 ? 1 : (1/$count));
			$dec = strstr ( $index, '.' );

			$is_event_7D = false;

			//l'evenement est-il proche ?
			if (Kidzou_Events::isTypeEvent($post->ID)) {

				$current= time();
				$now 	= date('Y-m-d 00:00:00', $current);
				$now_time = new DateTime($now);
				$now_time_plus7 = $now_time->add( new DateInterval('P7D') ); 

				$event_dates = Kidzou_Events::getEventDates($post->ID);
				$event_start = new DateTime($event_dates['start_date']);

				if ($event_start < $now_time_plus7)
					$is_event_7D = true;
			}

			$message .= ' - '.$is_event_7D;
			
			$prefix =  (Kidzou_Events::isFeatured($post->ID) ? "A" : ($is_event_7D ? "B" : "C"));

			$arr['kz_index'] = $prefix.$dec;

			$message .= " : ".$arr['kz_index'];
			Kidzou_Admin::save_meta($post->ID, $arr);

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
				$meta_posts = get_user_meta(intval($user_id), self::$meta_user_votes);
				
				//print_r($wpdb->queries);
				
				$voted_posts = $meta_posts[0]; //print_r($voted_posts);
				//$index_posts = count($voted_posts);

				if(!is_array($voted_posts))
					$voted_posts = array();

				array_push($voted_posts, $id) ;

				// $voted_posts[$index_posts] = $id;

				update_user_meta( $user_id, self::$meta_user_votes, $voted_posts);
			}
			else
			{

				if ( !update_post_meta (intval($id), self::$meta_anomymous_vote, $user_hash ) ) add_post_meta( intval($id), self::$meta_anomymous_vote, $user_hash );
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
				$meta_posts = get_user_meta(intval($user_id), self::$meta_user_votes);
				
				//print_r($wpdb->queries);
				
				$voted_posts = $meta_posts[0];
				//$index_posts = count($voted_posts);

				if(!is_array($voted_posts))
					$voted_posts = array();

				foreach ($voted_posts as $i => $value) {
				    //retrait du vote sur le user
				    if ( intval($value)==intval($id) )
						unset($voted_posts[$i]);
				}

				update_user_meta( $user_id, self::$meta_user_votes, $voted_posts);
			}
			else
				delete_post_meta(intval($id), self::$meta_anomymous_vote, $user_hash );

			//kz_clear_cache();

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
			return ;

		global $wpdb;
			
			$res = $wpdb->get_results(
				"SELECT post_id as id,meta_value as votes FROM $wpdb->postmeta key1 WHERE key1.meta_key='kz_reco_count' AND key1.post_id = $id", ARRAY_A);

		return array(
				"id" 	=> $res[0]['id'],
		      	"votes"	=> $res[0]['votes']
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