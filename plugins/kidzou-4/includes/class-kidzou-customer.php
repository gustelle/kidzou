<?php

add_action( 'plugins_loaded', array( 'Kidzou_Customer', 'get_instance' ), 100 );

/**
 * Cette classe enrigistre le post_type 'customer' et accède aux meta d'un Customer donné, à savoir :
 * * ses API
 * * Autorisation ou non de voir les analytics
 * * ses articles
 *
 * @package Kidzou_Customer
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Customer {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $meta = '';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_customer = 'kz_customer';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_api_key = 'kz_api_key';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_api_quota = 'kz_api_quota';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_api_usage = 'kz_api_usage';

	/**
	 * @todo : transformer en const
	 */ 
	public static $meta_customer_analytics = 'kz_customer_analytics';

	/**
	 * Le post type d'un customer
	 *
	 */
	public static $post_type = 'customer';

	/**
	 * Les post types supportés pour se raccrocher à un customer
	 *
	 */
	public static $supported_post_types = array('post'); //'offres', 'product'



	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 


		add_action('init', array($this, 'register_customer_type'));

		//pour le F.O
		//la classe GADWP_Frontend_Item_Reports n'est pas encore instanciée à cet endroit
		//c'est pourquoi le check se fait sur le Manager
		if (class_exists('GADWP_Manager') )
		{
			add_action('wp', array($this, 'check_customer_analytics'), 0);
		}

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
	 * Si les analytics sont actifs et que le user a le droit
	 * on ouvre les tuyaux pour afficher les analytics en bas de page
	 *
	 * Les analytics peuvent être activés/désactivés d'une facon générale pour tous les customers dans les options Kidzou
	 * et ensuite, on peut activer/désactiver les analytics d'un client en particulier dans la fiche client
	 * 
	 * <em>Cette filtre n'est actif que sur la boucle principale (is_main_query()) pour optimiser (pas besoin sur les autres boucles)</em>
	 *
	 * @return void
	 * @since customer-analytics
	 * @see https://wordpress.org/plugins/google-analytics-dashboard-for-wp/
	 * @author 
	 **/
	public function check_customer_analytics()
	{
		if (!Kidzou_Utils::current_user_can('author'))
		{
			$remove_analytics = false;

			global $post;

			// Kidzou_Utils::log('check_customer_analytics(1)['.$remove_analytics.']',true);

			//activation ou désactivation générale des analytics dans les options Kidzou
			$activate = Kidzou_Utils::get_option('customer_analytics_activate', false);
		
			if ( !$activate || !is_single() || !is_user_logged_in() )
			{
				$remove_analytics = true;
			}
			else
			{	
				//vérif que le customer de la page courante est autorisé à visualiser ses analytics
				$customer_id = self::getCustomerIDByPostID( $post->ID );
				$is_authorized = self::isAnalyticsAuthorizedForCustomer($customer_id);

				if (!$is_authorized)
				{
					$remove_analytics = true;
				}
				else
				{	
					//le client du post
					$current_user_customers = self::getCustomersIDByUserID();

					if ( !in_array($customer_id, $current_user_customers) )
					{
						$remove_analytics = true;
					} 

				}
			}

			if ($remove_analytics)
			{
				Kidzou_Utils::remove_filters_for_anonymous_class('admin_bar_menu', 'GADWP_Frontend_Item_Reports', 'custom_adminbar_node', 999);
				Kidzou_Utils::remove_filters_for_anonymous_class('wp_enqueue_scripts', 'GADWP_Frontend_Setup', 'load_styles_scripts', 10);
			} else {
				//nothing
			}
		}
	}

	

	public function register_customer_type() {

		//definir les custom post types
		//ne pas faire a chaque appel de page 

		// Kidzou_Utils::log('Kidzou_Customer [register_customer_type]',true);

		$labels = array(
			'name'               => 'Clients &amp; Partenaires',
			'singular_name'      => 'Client ou Partenaire',
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Ajouter un client/partenaire',
			'edit_item'          => 'Modifier le client/partenaire',
			'new_item'           => 'Nouveau client/partenaire',
			'all_items'          => 'Tous les clients/partenaires',
			'view_item'          => 'Voir le client/partenaire',
			'search_items'       => 'Chercher des clients/partenaires',
			'not_found'          => 'Aucun client ou partenaire trouvé',
			'not_found_in_trash' => 'Aucun client our partenaire trouvé dans la corbeille',
			'menu_name'          => 'Clients &amp; Partenaires',
			);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_position' 	 => 20, //sous les pages
			'menu_icon' 		 => 'dashicons-businessman',
			'supports' 			=> array( 'title', 'author', 'revisions'),
			);

		register_post_type( self::$post_type, $args );

	}
	

    /**
	 * le customer d'un post, ou 0 si le post n'est pas attaché a un customer
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerIDByPostID($post_id = 0)
	{

		if ($post_id==0)
		{
			global $post; 
			$post_id = $post->ID; 
		}

		// Kidzou_Utils::log('getCustomerIDByPostID '.$post_id, true);

		//si le post est un customer on jette une erreur
		$post = get_post($post_id);

		if (get_post_type($post)==self::$post_type)
			return new WP_Error( 'not_a_post', __( "L'ID correspond déjà à un Client", "kidzou" ) );

		$customer = get_post_meta($post_id, self::$meta_customer, TRUE);

		if (!$customer || $customer=='')
			$customer = 0;

		// Kidzou_Utils::log(array('method', __METHOD__, 'post_id' => $post_id, 'customer'=> $customer) , true);

		return intval($customer);
	}

	/**
	 * le nom du client par son ID
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerNameByCustomerID($customer_id = 0)
	{

		$customer = self::getCustomerByID($customer_id);

		if (!is_wp_error($customer))
			return $customer->post_title;

		return $customer;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomerByID ($customer_id = 0)
	{
		if ($customer_id==0)
			new WP_Error( 'null_customer', __( "L'ID du client est attendu !", "kidzou" ) );

		$customer = get_post($customer_id);

		if (get_post_type($customer)==self::$post_type)
			return $customer;

		return new WP_Error( 'not_a_customer', __( "L'objet correspondant n'est pas un client !", "kidzou" ) );
	}


	/**
	 * les posts d'un customer 
	 *
	 * @return array of posts
	 * @author 
	 **/
	public static function getPostsByCustomerID($customer_id = 0, $settings= array()) {

		$posts = array();
		if ($customer_id==0) {
			$customer_id = self::getCustomerIDByPostID(); //echo $customer_id;
			if ($customer_id==0)
				return $posts;
		}

		global $post;

		$defaults = array(
			'posts_per_page' => 4,
			'post_status'=> 'publish',
			'post__not_in' => array( $post->ID ) //exclure le post courant si on est sur un article
		);

		// Parse incomming $args into an array and merge it with $defaults
		$args = wp_parse_args( $settings, $defaults ); 

		// Declare each item in $args as its own variable i.e. $type, $before.
		extract( $args, EXTR_SKIP );

		$rd_args = array(
			'posts_per_page' => $posts_per_page,
			'post_type' 	=> 'post',
			'meta_query' => array(
				array(
					'key'     => self::$meta_customer,
					'value'   => $customer_id
				),
			),
			'post__not_in'	=> $post__not_in,
			'post_status'	=> $post_status
		);

		$the_query = new WP_Query( $rd_args ); 
		$posts = $the_query->get_posts();
		
		return $posts;
	}

	/**
	 * les users d'un customer 
	 *
	 * @return array of posts
	 * @author 
	 **/
	public static function getUsersByCustomerID($customer_id = 0) {

		$users = array();
		if ($customer_id==0) {
			$customer_id = self::getCustomerIDByPostID(); //echo $customer_id;
			if ($customer_id==0)
				return $users;
		}

		$user_query = new WP_User_Query( array( 
			'meta_key' => self::$meta_customer, 
			'meta_value' => $customer_id , 
			'fields' => 'all' 
			) 
		);
		
		return $user_query->results;
	}

	/**
	 * retourne les clients d'un user
	 *
	 * @return void
	 * @author 
	 **/
	public static function getCustomersIDByUserID($user_id = 0)
	{

		if ($user_id == 0)
			$user_id = get_current_user_id();

		$customer_ids = get_user_meta($user_id, self::$meta_customer, false); 

		//supprimer les révisions et autrs
		return array_filter($customer_ids, function($item) {
			$instance = Kidzou_Customer::get_instance();
			$this_type = $instance::$post_type;

			return get_post_type($item)==$this_type;
		});
	}

	/**
	 * retourne la clé d'API du client
	 *
	 * @return void
	 * @author 
	 **/
	public static function getKey($customer_id = 0)
	{

		if ($customer_id==0) {
			$customer_id = self::getCustomerIDByPostID(); //echo $customer_id;
			if ($customer_id==0)
				return new WP_Error('getKey', 'Aucun client identifié');
		}

		$key	 	= get_post_meta($customer_id, self::$meta_api_key, TRUE);
		if ($key == '') {
			$key = md5(uniqid());
		}

		return $key;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function isAnalyticsAuthorizedForCustomer($customer_id = 0)
	{
		$meta = get_post_meta($customer_id, self::$meta_customer_analytics , TRUE);
		// Kidzou_Utils::log( array('method' => __METHOD__ , 'customer_id' => $customer_id, 'meta' => $meta), true);
		return $meta;
	}

	/**
	 * Enregistrement de la meta 'customer' sur les posts concernés. Cette méthode est indépendant de la metabox pour pouvoir être attaquée depuis des API
	 *
	 * @param $customer_id int le post sur lequel on vient attacher la meta  
	 * @param $posts Array tableau des ID des posts à associer au customer
	 **/
	public static function attach_posts($customer_id, $posts=array())
	{	
		// Kidzou_Utils::log(array('attach_posts'=>$customer_id, 'posts'=>$posts),true);
		
		if (empty($posts))
			return new WP_Error('set_customer_for_posts', 'Aucun ID de post passé dans le tableau');

		$meta = array();
		$meta[Kidzou_Customer::$meta_customer] 	= $customer_id;
		
		foreach ($posts as $mypost) {
			Kidzou_Utils::save_meta($mypost, $meta);
		}

		//ensuite faire un diff pour virer ceux qui ont la meta et qui ne devraient pas
		$args = array(
		   'meta_query' => array(
		       array(
		           'key' => Kidzou_Customer::$meta_customer,
		           'value' => $customer_id,
		           'compare' => '=',
		       )
		   ),
		   'post_per_page' => -1,
		   'post_type' => Kidzou_Customer::$supported_post_types
		);
		$query = new WP_Query($args);

		$old_posts = $query->get_posts();

		foreach ($old_posts as $an_old_one) {
			if (in_array($an_old_one->ID, $posts)) {
				//c'est bon rien à faire
			} else {
				// Kidzou_Utils::log( 'Post '.$an_old_one->ID.' n\'est plus affecte au client '. $post_id );
				
				delete_post_meta($an_old_one->ID, Kidzou_Customer::$meta_customer, $customer_id);
			}
		}		
	}

	/**
	 * Enregistrement de la meta 'customer' sur les users concernés. Un user client est en fait un contributeur pour le client
	 * Cette méthode est indépendant de la metabox pour pouvoir être attaquée depuis des API
	 *
	 * @param $customer_id int le post sur lequel on vient attacher la meta  
	 * @param $users Array tableau des ID des users à associer au customer
	 **/
	public static function set_users($customer_id, $users=array())
	{	
		// Kidzou_Utils::log(array('attach_posts'=>$customer_id, 'posts'=>$posts),true);
		
		if (empty($users))
			return new WP_Error('set_customer_for_posts', 'Aucun ID de user passé dans le tableau');

				$meta = array();
		
		//il faut faire un DIFF :
		//recolter la liste des users existants sur ce client
		//comparer à la liste des users passés dans le POST
		//supprimer, ajouter selon les cas


		//boucle primaire
		//si les users passés dans la req étaient déjà présents en base
		//	si il n'avaient pas capacité edit_others_events, -
		//	sinon -
		//si non
		// 	on ajoute le user à la liste des users du client
		//		si il n'a pas la capacité edit_others_events, -
		foreach ($users as $a_user) {

			//toujours s'assurer qu'il est contrib, ca ne mange pas de pain
			//mais ne pas dégrader son role s'il est éditeur ou admin
			$u = new WP_User( $a_user );
			$better = false;

			$better_roles = array('administrator','editor','author');

			if ( !empty( $u->roles )  ) {
				foreach ( $u->roles as $role )
					if (in_array($role, $better_roles)) {
						$better = true;
						break;
					}
			}

			if (!$better) {

				$a_user = wp_update_user( array( 'ID' => $a_user, 'role' => 'contributor' ) );

				Kidzou_Utils::log( 'User ' . $a_user . ' updated' , true);

			}

		    // add_user_meta( $a_user, Kidzou_Customer::$meta_customer, $post_id, TRUE ); //cette meta est unique
		    $prev_customers = get_user_meta($a_user, self::$meta_customer, false);   //plusieurs meta customer par user

		    Kidzou_Utils::log(  array('prev_customers'=>$prev_customers) ,true );


		    if ( empty($prev_customers) )
		     	$prev_customers = array();

		     if (!in_array($customer_id, $prev_customers)) {
		     	//ajouter la meta qui va bien
				Kidzou_Utils::log(  'User ' . $a_user . ' : add_user_meta'  ,true );
		     	add_user_meta($a_user, self::$meta_customer, $customer_id, false); //pas unique !
		     }
	        
		}

		//boucle secondaire
		//si la base contenait une liste d'utilisateurs pour le client
		//	si le user a été repassé en requette 
		// 		on supprime le role du user user 
		// 		ainsi que la meta client

		$args = array(
			'meta_key'     => self::$meta_customer,
			'meta_value'   => $customer_id,
			'fields' => 'id' //retourne un array(id)
		 );

		$old_users = get_users($args); 

		if (!is_null($old_users)) {

			//boucle complémentaire:
			foreach ($old_users as $a_user) {

				Kidzou_Utils::log( 'Boucle secondaire, User ' . $a_user. ', provient de la requete ? '.in_array($a_user, $users), true );

				//l'utilisateur n'a pas été repassé dans la requete
				//il n'est pas donc plus attaché au client
				if (!in_array($a_user, $users)) {

					$u = new WP_User( $a_user );

					$better = false;

					$better_roles = array('administrator','editor','author');

					if ( !empty( $u->roles ) && is_array( $u->roles ) ) {
						foreach ( $u->roles as $role )
							if (in_array($role, $better_roles)) {
								$better = true;
								break;
							}
					}

					//ne pas dégrader automatiquement le role
					//faire confirmer au user qu'il souhaite dégrader le role
					if (!$better) {
						//privé de gateau
				        // $a_user = wp_update_user( array( 'ID' => $a_user, 'role' => 'subscriber' ) );
					}

			        //suppression de la meta du client dans tous les cas
			        Kidzou_Utils::log( $a_user . ' : suppression de la meta', true );

			        delete_user_meta( $a_user, Kidzou_Customer::$meta_customer, $customer_id );

				}
				
			}

		}
		
			
	}

	/**
	 * Enregistrement de la meta 'analytics' sur le customer, ce qui permet aux users de ce customer de voir les analytics
	 *
	 * @param $is_analytics boolean true si les users du customer sont autorisés à voir les analytics 
	 *
	 **/
	public static function set_analytics($customer_id, $is_analytics = false)
	{	
		$meta = array();

		$meta[Kidzou_Customer::$meta_customer_analytics] = $is_analytics;

		Kidzou_Utils::save_meta($customer_id, $meta);	
	}

	/**
	 * Enregistrement de la meta 'API' sur le customer, ce qui fournit des indications sur les API et leurs quotas autorisés pour le customer
	 *
	 * @deprecated
	 * @param $api_names Array tableau de noms de méthode dans les API customer
	 * @param $key string Clé d'API pour le customer
	 * @param $quota int nombre d'appels possibles pour le customer
	 * @todo seul une API peut être gérée pour l'instant, autrement dit on ne considère que la premiere ligne du tableau $apis
	 **/
	public static function set_api($customer_id, $api_names=array(), $key='', $quota=0)
	{	
		if (empty($api_names))
			return new WP_Error('set_api', 'Aucune API a configurer');

		if ($key=='')
			return new WP_Error('set_api', 'Aucune key pour les API');

		$meta = array();

		$meta[Kidzou_Customer::$meta_api_key] 	= $key;
		$meta[Kidzou_Customer::$meta_api_quota] = array(reset($api_names) => $quota);

		Kidzou_Utils::save_meta($customer_id, $meta);
		
	}

	/**
	 * Enregistrement de la Key du client
	 *
	 * @param $key string Clé d'API pour le customer
	 *
	 **/
	public static function setKey($customer_id, $key='')
	{	

		if ($key=='')
			return new WP_Error('set_api', 'Aucune key pour les API');

		$meta = array();

		$meta[Kidzou_Customer::$meta_api_key] 	= $key;
		// $meta[Kidzou_Customer::$meta_api_quota] = array(reset($api_names) => $quota);

		Kidzou_Utils::save_meta($customer_id, $meta);
		
	}

	/**
	 * Enregistrement du quota pour l'API donnée
	 *
	 * @param $quota int quota d'appel quotidien
	 * @api_name $api_name string nom de méthode de l'API customer concernée par le quota
	 *
	 **/
	public static function setQuota($customer_id, $api_name='', $quota=0)
	{	

		if ($api_name=='')
			return new WP_Error('setQuota', 'Aucune Methode d\'API pour le quota');

		if ($quota==0)
			return new WP_Error('setQuota', 'Aucun quota indiqué');

		$meta = array();

		$meta[self::$meta_api_key]	= self::getKey($customer_id);
		$meta[self::$meta_api_quota] = array($api_name => $quota);

		Kidzou_Utils::save_meta($customer_id, $meta);
		
	}


} //fin de classe

?>