<?php

add_action( 'plugins_loaded', array( 'Kidzou_Vote', 'get_instance' ), 100 );

// rafraichir l'index featured en fonction des votes
if( !wp_next_scheduled( 'kidzou_votes_scheduler' ) ) {
   wp_schedule_event( time(), 'twicedaily', 'kidzou_votes_scheduler' );
}
 
add_action( 'kidzou_votes_scheduler', array( Kidzou_Vote::get_instance(), 'set_vote_meta') );

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
	 * marqueur d'insertion du template dans la page
	 *
	 * @since    1.0.0
	 *
	 * @var      Boolean
	 */
	// protected static $is_template_inserted = false;

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

		if (!Kidzou_Utils::is_really_admin())
			add_action( 'wp_head', array($this, 'insert_template'));
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
			'is_voted'	=> false
		);

		$query = new Vote_Query( $args );

		// Kidzou_Utils::log("Query set_vote_meta : {$query->request}", true);

		$posts = $query->get_posts();

		Kidzou_Utils::log('set_vote_meta : ' . $query->found_posts . ' meta a creer', true);

		foreach ($posts as $post) {

			$message = "set_vote_meta {" . $post->ID . "} " ;
			add_post_meta($post->ID, self::$meta_vote_count, 0, TRUE);

			Kidzou_Utils::log( $message, true );
			
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

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function insert_template ()
	{
		self::set_template('', false, true);
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

		// self::$is_template_inserted = true;

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

		// if (!self::$is_template_inserted) {
		// 	if ($echo)
		// 		self::set_template('', $useCountText, false, true);
		// 	else
		// 		$out .= self::set_template('', $useCountText, false, false);
		// }

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

	

	public static function plusOne($id=0, $user_hash='') {

		if ($id==0) 
		{
			return array();
		}


	  	$user_id 	= get_current_user_id();//get_user("ID"); //remplacer par get_current_user_id()
	  	$loggedIn 	= is_user_logged_in();

		// Get votes count for the current post
		$meta_count = get_post_meta($id, self::$meta_vote_count, true);
		// $message 	= '';

		//on ne recalcule pas systématiquement le hash du user, 
		//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
		if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
			$user_hash = Kidzou_Utils::hash_anonymous();

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

				// Kidzou_Utils::log($meta_posts);
				
				$voted_posts = $meta_posts[0]; 

				if(!is_array($voted_posts))
					$voted_posts = array();

				//maintenant on stocke le timestamps du vote
				array_push($voted_posts, array('id' => $id, 'timestamp' => time() ) ) ;

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

		$user_id = get_current_user_id();//get_user("ID"); //get_current_user_id()
	  	$loggedIn = is_user_logged_in();

		// Get votes count for the current post
		$meta_count = get_post_meta($id, self::$meta_vote_count, true);
		$message 	= '';

		// Kidzou_Utils::log('minusOne for user '. $user_id . ' [initial] : '. $meta_count );

		//on ne recalcule pas systématiquement le hash du user, 
		//de sorte que si le user anonyme a changé d'adresse IP mais a gardé son hash, il reprend son historique de vote
		if ($user_hash==null || $user_hash=="" || $user_hash=="undefined")
			$user_hash = Kidzou_Utils::hash_anonymous();

		// Use has already voted ?
		if(self::hasAlreadyVoted($id, $loggedIn, $user_id, $user_hash))
		{
			// Kidzou_Utils::log('Update post and user meta');
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

					//il y a eu changement 
					//au debut les values étaient les id
					//mais maintenant les value sont des tableaux (id=>timestamp)
					if ( is_array($value) && isset($value['id']) )
					{
						
						$val = $value['id'];
						// Kidzou_Utils::log('minusOne sur user '. $user_id);
						// Kidzou_Utils::log($value);
						if (intval($val) == intval($id))
							unset($voted_posts[$i]);

					} else if ( intval($value)==intval($id) )
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
	 * le nombre de votes pour un post
	 *
	 * @return Array
	 * @author 
	 **/
	public static function getPostVotes($post_id=0)
	{
		if ($post_id==0) 
		{
			global $post;
			$post_id = $post->ID;
		}

		$results = get_post_meta($post_id, self::$meta_vote_count, true);

		return array(
				"id" 	=> $post_id,
		      	"votes"	=> intval($results)
			);

	}

	/**
	 * le nombre de votes pour une liste de post
	 *
	 * @return Array<id, votes>
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
	 * Retourne le tableau des WP_Post que le user a voté
	 *
	 * @return Array
	 * @since Noel2014
	 * @author Guillaume
	 **/
	public static function getUserVotedPosts( $user_id = 0 )
	{

		if ($user_id == 0)
			$user_id = get_current_user_id();

		$meta = get_user_meta( $user_id, self::$meta_user_votes , false ); 
		$data = $meta[0];

		//gestion du legacy 
		foreach ($data as $key => $value) {
			if (!is_array($value)) {

				$data[$key] = array(
					'id' => $value,
					'timestamp' => 0,
				);

			}
		}

		return $data;

	}

	/**
	 * retourne un tableau d'ID correspondant aux posts que le user a voté 
	 *
	 * @return Array
	 * @deprecated 
	 * @todo c'est une API, bouger cela dans les API...ca sert uniquement en Ajax pour le UI
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
			$user_id = get_current_user_id(); //get_user('ID')

			global $wpdb;
			$res = $wpdb->get_results(
								"SELECT meta_value as serialized FROM $wpdb->usermeta WHERE user_id=$user_id AND meta_key='kz_reco_post_id'",
								ARRAY_A
							);
			$unserialized = maybe_unserialize($res[0]['serialized']);
			// Kidzou_Utils::log('user_id '. $user_id);
			// Kidzou_Utils::log( $unserialized);
			$voted = array();
			if ($unserialized!=null)
			{ 
				foreach ($unserialized as $i => $ares) 
				{
					//gestion du legacy
					//certains items sont les valeurs directes des id
					//d'autres sont un array (id, timestamp)
					if (is_array($ares) && isset($ares['id']))
						array_push($voted, array ('id' => intval($ares['id']) ) ) ;
					else
						array_push($voted, array ('id' => $ares ) ) ;
				}
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
				$user_hash=Kidzou_Utils::hash_anonymous();

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
	public static function hasAlreadyVoted($post_id, $loggedIn='', $user_id=0, $user_hash='')
	{

		// Kidzou_Utils::log('hasAlreadyVoted ? ' );

		if ($loggedIn=='')
			$loggedIn = is_user_logged_in();

		if ($user_id==0 && $loggedIn)
			$user_id = get_current_user_id(); //get_user('ID')

		// Kidzou_Utils::log('hasAlreadyVoted loggedIn ' . $loggedIn);
		// Kidzou_Utils::log('hasAlreadyVoted user_id ' . $user_id);

		if ($loggedIn && $user_id>0)
		{
			// Kidzou_Utils::log('hasAlreadyVoted loggedIn ' );
			//check DB
			$meta_posts = get_user_meta($user_id, self::$meta_user_votes);
			$voted_posts = $meta_posts[0];

			if(!is_array($voted_posts))
				$voted_posts = array();

			if(in_array($post_id, $voted_posts))
				return true;
			else {
				//gestion des nouveaux modes de vote
				//maintenant on tracke les timestamp donc les valeurs sont des array(id, timestamps)
				foreach ($voted_posts as $index => $id_tmsp) {
					if( isset($id_tmsp['id']) && intval($id_tmsp['id'])==intval($post_id) )
						return true;
				}
			}

		}
		else {
			// Kidzou_Utils::log('hasAlreadyVoted anonymous ' );
			if ($user_hash=='') {
				$user_hash = Kidzou_Utils::hash_anonymous();
			}
			// Kidzou_Utils::log('hasAlreadyVoted anonymous ' .$user_hash);
			return self::hasAnonymousAlreadyVoted ($post_id, $user_hash);
		}
			

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