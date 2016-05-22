<?php

add_action('plugins_loaded', array('Kidzou_Metropole', 'get_instance'), 100);


/**
 * Cette classe gère les métropoles du système et filtre les contenus appelés par métropole
 *
 * @package Kidzou
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Metropole {

	const COOKIE_METRO = 'kz_metropole';

	/**
	 * Necessaire pour retrouver la métropole à partir de lat/lng stockés éventuellement en cookie
	 */
	const COOKIE_COORDS = 'kz_coords';

	const REWRITE_TAG = '%kz_metropole%';

	/**
	 *  ne jamais accéder directement à cette variable, seulement utilisé par $this->get_request_metropole()
	 */
	protected $request_metropole = '';

	/**
	 * déstinée à etre un Booleén, la valeur initiale est une chaine vide pour marquer que la valeur n'est pas initialisée
	 * 
	 */
	protected $is_request_filter = '';

	/**
	 * Instance of this class.
	 *
	 *
	 * @var      object Kidzou_Geo
	 */
	protected static $instance = null;

	/**
	 * le tableau des post types qui supportent le rattachement d'un post à une metropole
	 * ce tableau est complété à l'init par les post types additionnels ajoutés par l'admin
	 */
	protected static $supported_post_types = array('post', 'page'); //'offres'


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 
		
		//ce hook est sensible
		//mieux vaut qu'il reste en dehors de toute affaire et qu'il ait son propre if ()
		add_action( 'init', array( $this, 'create_rewrite_rules' ),90 );

		if (!Kidzou_Utils::is_really_admin())
		{

			//Le filtrage n'est pas actif pour certaines requetes, typiquement les API
			add_filter( 'post_link', array( $this, 'rewrite_post_link' ) , 10, 2 );
			add_filter( 'page_link', array( $this, 'rewrite_page_link' ) , 10, 2 );
			add_filter( 'term_link', array( $this, 'rewrite_term_link' ), 10, 3 );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );

			add_action( 'pre_get_posts', array( $this, 'geo_filter_query'), 999 );
		}

		self::init();	
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
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private static function init()
	{
		//extension des post types supportés
		self::add_supported_post_types();
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	private static function add_supported_post_types()
	{
		$more_types = Kidzou_Utils::get_option('geo_supported_post_types', array());

		foreach ($more_types as $key => $value) {
			array_push(self::$supported_post_types, $value);
		}
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public static function get_supported_post_types()
	{
		return self::$supported_post_types;
	}

	/**
	 * Injecte les Javascripts nécessaires à la détermination de la métropole la plus proche du user. Cette métropole est fournie par MapQuest en fonction de lat/lng du user
	 * le script détermine si la métropole remontée par MapQuest est disponible dans le système.
	 *
	 *
	 * @return void
	 * @author 
	 **/
	public function enqueue_geo_scripts()
	{
		// $locator = self::$locator;
		wp_enqueue_script('kidzou-metropole', plugins_url( '../assets/js/kidzou-metropole.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

		$villes = self::get_metropoles();

		$key = Kidzou_Utils::get_option("geo_mapquest_key",'Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6');
  
		$args = array(
					'geo_activate'				=> (bool)Kidzou_Utils::get_option('geo_activate',false), //par defaut non
					'geo_mapquest_key'			=> $key, 
					'geo_mapquest_reverse_url'	=> "https://open.mapquestapi.com/geocoding/v1/reverse",
					'geo_mapquest_address_url'	=> "https://open.mapquestapi.com/geocoding/v1/address",
					'geo_cookie_name'			=> self::COOKIE_METRO,
					'geo_possible_metropoles'	=> $villes ,
					'geo_coords'				=> self::COOKIE_COORDS,
				);

	    wp_localize_script(  'kidzou-metropole', 'kidzou_geo_jsvars', $args );
		
	}


	/**
	 * True|False selon que le user choisisse d'injecter la metropole courante dans l'URL de la page
	 *
	 * Ce Booléen est une option représentée dans l'admin via une checkbox 
	 *
	 * @return Boolean
	 *
	 * @see  Kidzou_Admin::posts_metaboxes() 	Les Metabox des posts 
	 **/
	public static function is_page_rewrite ($post_id=0)
	{
		if ($post_id==0)
	    {
	        global $post;
	        $post_id = $post->ID;
	    }

	    return get_post_meta($post_id, 'kz_rewrite_page', TRUE);
	}

	/**
	 * la metropole de rattachement de la requete, il est important de toujours passer par cette fonction et jamais directement par $this->request_metropole pour accéder à sa valuer
	 * car la variable est mise à jour dans le get
	 *
	 * @return String (slug)
	 **/
	public function get_request_metropole()
	{
		if ($this->request_metropole=='' && !Kidzou_Utils::is_really_admin())
			$this->set_request_metropole();

		return $this->request_metropole;
	}

	/**
	 * Recuperation de la metropole passée en requete ou en Cookie
	 * 
	 *
	 **/
	private function set_request_metropole()
	{
		//d'abord on prend la ville dans l'URI
		$uri = $_SERVER['REQUEST_URI'];

		$regexp = self::get_metropole_uri_regexp();

		$cook_m = '';

		//la metropole en provenance du cookie
		if ( isset($_COOKIE[self::COOKIE_METRO]) )
			$cook_m = strtolower($_COOKIE[self::COOKIE_METRO]);

		//en dépit du cookie, la valeur de la metropole passée en requete prime
		if (preg_match('#\/'.$regexp.'\/?#', $uri, $matches)) {

			// Kidzou_Utils::log('[get_request_metropole] Regexp identifiée ');
			
			$ret = rtrim($matches[0], '/'); //suppression du slash à la fin
			$metropole = ltrim($ret, '/'); //suppression du slash au début

			// Kidzou_Utils::log('[set_request_metropole] Regexp : '. $metropole);

			//avant de renvoyer la valeur, il faut repositionner le cookie s'il n'était pas en cohérence
			//la valeur de metropole passée en requete devient la metropole du cookie
			if ($cook_m!=$metropole && $metropole!='') {

				setcookie(self::COOKIE_METRO, $metropole);

				$this->request_metropole = $metropole;

				//positionner cette variable pour ne pas aller plus loin
				$cook_m = $this->request_metropole;

			}	

		}

		//si l'URI ne contient pas la ville, on prend celle du cookie, sinon celle en parametre de requete
		if ($cook_m=='' && isset($_GET[self::COOKIE_METRO]))  {
			$cook_m = strtolower($_GET[self::COOKIE_METRO]);
			// Kidzou_Utils::log('[get_request_metropole] kz_metropole : '. $cook_m);
		} 

		//si rien ne match, on prend la ville par défaut
		if ($cook_m=='')  {
			$cook_m = self::get_default_metropole();
			// Kidzou_Utils::log('[get_request_metropole] ville par défaut : '. $cook_m);
		} 

	    $isCovered = false;

	    if ($cook_m!='') 
	    	$isCovered = self::is_metropole($cook_m);

	    if ($isCovered) 
	    	$this->request_metropole = $cook_m;
	    else
	    	$this->request_metropole = ''; //on désactive meme la geoloc en laissant la metropole à ''
	}

	/**
	 * positionnement du booléean qui indique si la requete doit etre filtrée par metropole
	 * i.e. est-ce que les contenus de la requetes sont filtrés ou non par métropole de rattachement des posts
	 *
	 * Cas particulier : si la métropole passée dans la requete HTTP correspond à la métropole à portée nationale, on positionne le booléen à FALS de sorte que les contenus ne sont pas filtrés
	 *
	 * @since proximite 
	 **/
	private function set_request_filter()
	{
		//mise à jour du param de filtrage de requete 
		$bypass_param = Kidzou_Utils::get_option('geo_bypass_param', 'region');
		$is_bypass_param = isset($_GET[$bypass_param]);

		//possibilité de bypasser les filtrages de contenus pour des URL qui matchent certaines Regexp
		$bypass_url = Kidzou_Utils::get_option('geo_bypass_regexp', '\/api\/');
		$is_bypass_url = preg_match( '#'.  $bypass_url .'#', $_SERVER['REQUEST_URI'] );

		//Cas particulier de la métropole à portée nationale 
		$is_national = ($this->get_request_metropole() == self::get_national_metropole());

		if ( Kidzou_Utils::is_really_admin() || 
			$is_bypass_url || 
			$is_bypass_param  ||
			$is_national ) {

			$this->is_request_filter = false;

		} else {

			$filter_active = (bool)Kidzou_Utils::get_option('geo_activate',false);
			
			if (!$filter_active) {

				// Kidzou_Utils::log('		Filtrage desactive dans les options', true);
			
				$this->is_request_filter = false;
			
			} else {

				//si la geoloc est active mais qu'aucune metropole n'est détectée en requete
				//on renvoie la chaine '' pour pouvoir ré-ecrire l'URL en supprimant les %kz_metropole%
				if ($this->get_request_metropole()=='' ) {

					$this->is_request_filter = false;

				} else {
					
					$this->is_request_filter = true;
				}

			}
		}
	}

		/**
	 * si une metropole est transmise en requete pour filtrage
	 *
	 */
	public function is_request_metro_filter()
	{
		if ($this->is_request_filter=='' && !Kidzou_Utils::is_really_admin())
			$this->set_request_filter();

		return $this->is_request_filter;
	}




	/**
	 * intégration avec le plugin Contextual Relatif Posts
	 *
	 */ 
	public function get_related_posts() {

		if (!function_exists('get_crp_posts_id'))
			return;

		add_filter('crp_posts_join', array($this, 'crp_filter_metropole')) ;

		//nouveau filtre CRP très restrictif : seuls les posts les plus récents sont vus
		//et ce filtre est trop restrictif et privilégie la récence à la pertinence
		add_filter('crp_posts_from_date',  function() {
			global $wpdb;
			$current_time = current_time( 'timestamp', 0 );
			$from_date = $current_time - ( 365 * DAY_IN_SECONDS ); //1 an
			$from_date = gmdate( 'Y-m-d H:i:s' , $from_date );
			return " AND ".$wpdb->posts.".post_date >= '".$from_date."'";
		});

		return get_crp_posts_id();

	}

	/**
	 * Filtrage des Contextual Related Posts par Metropole   
	 *
	 * @see Contextual Related Posts
	 * @return void
	 * @author 
	 **/
	public function crp_filter_metropole()
	{
		$join = ''; 

		$metropole = self::get_post_metropole(); //object
		if ($metropole!=null) {
			global $wpdb;
			$join .= "
			INNER JOIN ".$wpdb->term_taxonomy ." AS tt ON (tt.term_id=".$metropole->term_id." AND tt.taxonomy='ville')
			INNER JOIN ".$wpdb->term_relationships ." AS tr ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tr.object_id=ID) ";
		}
		return $join;
	}

	/**
	 * la metropole du post courant
	 * si rattaché à plusieurs metropoles (national, lille...) on prend la metropole qui dispose du meta kz_national_ville
	 * si aucune metropole ne dispose de cette meta, on prend la premiere de la liste
	 *
	 * @return Object
	 * @author 
	 **/
	public static function get_post_metropole( )
	{
	    global $post; 

	    $result = get_transient('kz_post_metropole_'. $post->ID ); 

	    if (false===$result)
	    {

	        $terms = wp_get_post_terms( $post->ID, 'ville');

	        $roots = array();

	        foreach ($terms as $key => $value){
	            //get top level parent
	            $ancestors = get_ancestors( $value->term_id, 'ville' );
	            
	            if (count($ancestors)==0) {
	                //le terme est déjà à la racine
	                array_push($roots, $value);
	            
	            } else {

	                foreach ($ancestors as $ancestor){
	                    $ville = get_term_by('id', (int)$ancestor, 'ville');
	                    if ($ville->parent == 0) {
	                        array_push($roots, $ville);
	                    }
	                }
	            }
	            
	        }

	        //si le post est rattaché à plus d'une metropole
	        if (count($roots)>1) {
	            $i=0;
	            $save_me = $roots[$i];
	            foreach ($roots as $root) {
	                
	            	$def = Kidzou_Utils::get_option('geo_national_metropole');
	            	if ( intval($def)!=intval($root->term_id) ) {
	                    unset($roots[$i]);
	                } else {
	                    $save_me = $roots[$i];
	                }
	                $i++;
	            }
	            $roots[0] = $save_me;
	        }

	        $result = $roots[0];

	        set_transient( 'kz_post_metropole_'. $post->ID , $result, 60 * 60 * 24 ); //1 jour de cache
	        
	    }
	    
	    return $result;
	}


	/**
	 * La liste des metropoles supportées par le système, autrement dit, les metropoles à la racine de la taxonomie "ville"
	 *
	 * @param $include_national Boolean True si la tableau renvoie une métropole "nationale" 
	 * @return Array Tableau des métropoles
	 */ 
    public static function get_metropoles($include_national = false)
    {

    	$transient_name = $include_national ? 'kz_metropoles_incl_national' : 'kz_metropoles_excl_national';
        $result = get_transient($transient_name);
        // Kidzou_Utils::log($result, true);

	    if (false===$result)
	    {
	        $villes = get_terms( array("ville"), array(
	            "orderby" => "slug",
	            "parent" => 0, //only root terms,
	            "fields" => "all"
	        ) );

	        $result  = array();

	        if (!is_wp_error($villes)) {

	        	//sortir les villes à couverture nationale
		        //on prend le premier de la liste
		        foreach ($villes as $key=>$value) {
		            $def = self::get_national_metropole(); //slug only //Kidzou_Utils::get_option('geo_national_metropole'); 
		            // Kidzou_Utils::log('get_metropoles -> '. $include_national, true);
		            if ( $def ==  $value->slug && !$include_national ) {

		            } else {
		                $result[$key] = $value;
		            }
		        }   

		        if (!empty($result) && count($result)>0) {
		        	set_transient( $transient_name, (array)$result, 60 * 60 * 24 ); //1 jour de cache
		        }
		       		
	        } else {
	        	// Kidzou_Utils::log($villes, true);
	        }
	    }

	    return $result;
    }

    /**
	 * retourne le chemin d'URI (slug) ou l'objet 'Term' de la métropole à portée nationale. elle a pour vocation de porter des articles à portée nationale . La ville à portée nationale doit être à la racine de la Taxonomy Ville, elle est sélectionnée dans les Réglages Kidzou
	 *
	 * @param $args Tableau de params array('fields'=>slug)|array('fields'=>all)
	 * @return Mixed Le slug ou lobjet de la metropole à portée nationale
	 **/
	public static function get_national_metropole($args = array('fields'=>'slug'))
	{
		// Kidzou_Utils::log('get_national_metropole', );
		$national = get_term_by('id', Kidzou_Utils::get_option('geo_national_metropole'), 'ville');
		
		if (!is_wp_error($national) && is_object($national)) {
			if ($args['fields']=='all')
				return $national;
			else if ($args['fields']=='slug')
				return $national->slug;
			else return new WP_Error( 'Unvalid param', 'Cette fonction accepte soit "slug" soit "all" en parametre' );
		}

		return $national; //propager l'erreur

	}

	

	/**
	 * la ville (slug) passee en parametre est-elle connue comme metropole dans notre système?
	 *
	 * @return Booléen
	 * @author 
	 **/
	public static function is_metropole($m)
	{

		// Kidzou_Utils::log('is_metropole : '. $m);
	    if ($m==null || $m=="") return false;

	    //la ville du user est-elle couverte par Kidzou
	    $villes  = self::get_metropoles(true);

	    $isCovered = false;
	    foreach ($villes as $v) {
	    	// error_log(print_r($v), true);
	        if ($v->slug == $m)
	            $isCovered = true;
	    }

	    return $isCovered;
	}

	/**
	 * 
	 *
	 * @return le slug de la ville par defaut, selectionnée dans les options
	 * @author 
	 **/
	public static function get_default_metropole()
	{

	    $term = get_term_by('id', Kidzou_Utils::get_option('geo_default_metropole') , 'ville');

	    if (!is_wp_error($term) && is_object($term))
	    	return $term->slug;

	    return '';
	}



	/**
	 * fournit le REGEX des metropoles dans une URI, y compris la métropole à portée nationale
	 *
	 * @return String du genre (metropole1|metropole2|...)
	 */ 
	public static function get_metropole_uri_regexp() {

		$regexp = get_transient('kz_metropole_uri_regexp'); 

   		if (false===$regexp) {

   			$villes = self::get_metropoles(true);

	    	$regexp = '(';
	        $i=0;
	        $count = count($villes);
	        foreach ($villes as $item) {
	            $regexp .= $item->slug;
	            $i++;
	            if ($regexp!=='' && $count>$i) {
	                $regexp .= '|';
	            }
	        }
	        $regexp .= ')'; //'|'.self::$no_filter.
	
			if ($regexp != '()')
				set_transient( 'kz_metropole_uri_regexp' , $regexp, 60 * 60 * 24 ); //1 jour de cache
   		}

   		return $regexp;

	}

	/**
	 * Fonction interne utilisée par la WP_Query, cette fonction récupère la metropole indiquée en requete HTTP 
	 * et retourne les args qui vont permettre de filtrer les contenus WP
	 *
	 * A noter que la metropole indiquée en requete HTTP est complétée par les métropoles "NATIONALES" 
	 *
	 * @see Kidzou_Metropole::get_national_metropole() 
	 * @see https://codex.wordpress.org/Class_Reference/WP_Query
	 * @return Array
	 * @internal 
	 **/
	private function get_metropole_args(  ) {

		// $locator = self::$this;

		if ( $this->is_request_metro_filter() )
		{
			$the_metropole = array();
	  		$the_metropole[] = $this->get_request_metropole();

	  		if ($this->get_request_metropole()!=self::get_national_metropole())
	       		array_push($the_metropole, self::get_national_metropole());

	       	$args = array(
	                  'taxonomy' => 'ville',
	                  'field' => 'slug',
	                  'terms' => $the_metropole
	                );

	       	// Kidzou_Utils::log(array('get_metropole_args'=>$args),true);
	       	return $args;
		}

		return array();
		
	}

	/**
	 * Les WP_Query utilisées sont filtrées en tenant compte de la métropole passée en requete
	 * Celle-ci est :
	 * * soit la metropole passée dans la requete (en provenance du cookie utilisateur), `
	 * * soit la metropole par défaut
	 *
	 * les contenus à portée "nationale" sont également remontés par la WP_Query
	 * 
	 * @see https://codex.wordpress.org/Class_Reference/WP_Query Documentation de WP_Query
	 * @since 0215-fix31 : filtrage des recherches par métropole
	 */
	public function geo_filter_query( $query ) {

		if ( $this->is_request_metro_filter() )
		{
			$post_type = $query->get('post_type');

			//le post type est il suporté par le filtre ?
			if (is_array($post_type))
			{
				foreach ($post_type as $key => $value) {
					if (in_array($value, self::get_supported_post_types() ))
					{
						$supported_query = true;
						break;
					}
				}
			} else 
				$supported_query = in_array($post_type, self::get_supported_post_types() ) ;

			//cas spécial des archives : le post type n'est pas spécifié
			//on ouvre au maximim les post types
			if (is_archive() && $query->is_main_query())
			{
				$query->set('post_type', self::get_supported_post_types() );
				$supported_query = true;
			}

			//cas du search, le post type n'est pas spécifié mais on filtre par métropole qd même
			if ($query->is_search)
			{
				// Kidzou_Utils::log('Search queries DO support pre_get_posts');
				$supported_query = true;
			}

		    if( !is_admin() && $supported_query ) { //&& !is_search()

				//reprise des arguments qui auraient pu être passés précédemment par d'autres requetes
		        //d'ou l'importance d'executer celle-ci en dernier
		        $vars = $query->get('tax_query');

		        $ville_tax_present = false;

		        if (isset($vars['taxonomy']) && $vars['taxonomy']=='ville')
		        	$ville_tax_present = true;

		        else if (is_array($vars)) {
		        	foreach ($vars as $key => $value) {
			        	
		        		if (is_array($value)) {
		        			foreach ($value as $k => $v) {
			        			if ($k == 'taxonomy' && $v=='ville') {
			        				$ville_tax_present = true;
			        				// echo 'found';
			        			}
			        				
			        		}

		        		}
			        		
			        }

		        }

	        	if (!$ville_tax_present)
	        	{
	        		$vars[] = $this->get_metropole_args( );
	        	}
	            //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
	            $query->set('tax_query', $vars);

		        return $query;
		    } 
		}

	    return $query;
	}

	/**
	 * Re-ecriture des requetes HTTP pour tenir compte de la metropole 
	 * 
	 * Les contenus rendus par WP sont filtrés en fonction de la metropole contenue dans la requete
	 *
	 * Par example /lille est ré-ecrit en index.php?kz_metropole=lille
	 *
	 * Par example /lille/agenda est ré-ecrit en index.php?pagename=agenda&kz_metropole=lille
	 *
	 */
	public function create_rewrite_rules() {

		if ((bool)Kidzou_Utils::get_option('geo_activate',false)) 
		{
			global $wp_rewrite; 

			$regexp = self::get_metropole_uri_regexp();
			add_rewrite_tag( self::REWRITE_TAG ,$regexp, 'kz_metropole=');

			//see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
		    add_rewrite_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home
		   	add_rewrite_rule($regexp.'/(.*)$/?','index.php?pagename=$matches[2]&kz_metropole=$matches[1]','top');
			add_rewrite_rule($regexp.'/(.*)/page/?([0-9]{1,})/?$','index.php?pagename=$matches[2]&paged=$matches[3]&kz_metropole=$matches[1]','top');

			//si la ville n'est pas spécifiée en requete, car le user est arrivé directement sur un post (donc pas préfixé par une ville)
			//et navigue ensuite vers une rubrique ou autre:
			add_rewrite_rule('/?rubrique/(.*)/?','index.php?category_name=$matches[1]','top');

			flush_rewrite_rules();
		}
		
	    
	}

	/**
	 * Cette fonction utilise le hook post_link pour injecter la métropole "courante" du user (celle passée en requete ou celle détectée par geoloc) dans le permalink d'un post
	 * 
	 * NB : Ce hook tient compte du REWRITE_TAG %kz_metropole% indiqué dans les options WP 
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/post_link 	Documentation du Hook post_link
	 * @see https://www.kidzou.fr/wp-admin/options-permalink.php 	Réglages des permaliens dans l'admin Wordpress
	 */
	public  function rewrite_post_link( $permalink, $post ) {

		if ($this->is_request_metro_filter() )
		{
			$m = urlencode($this->get_request_metropole());

		    // Check if the %kz_metropole% tag is present in the url:
		    if ( true === strpos( $permalink, self::REWRITE_TAG ) ) {

			    // Replace '%kz_metropole%'
			    $permalink = str_replace( self::REWRITE_TAG, $m , $permalink );

		    } 
			    
		}
		 
	    return $permalink;
	}

	/**
	 * Injection de la métropole "courante" du user (celle passée en requete ou celle détectée par geoloc) dans le permalink d'une page
	 * Cette technique permet d'améliorer le SEO puisque une même page peut être référencée plusieurs fois selon la métropole
	 * Par exemple : /lille/ma-page ou /valenciennes/ma-page
	 *
	 * @see https://developer.wordpress.org/reference/hooks/page_link/	Documentation du Hook page_link
	 * @see Kidzou_Metropole::is_page_rewrite() 	Booleen qui détermine si le permalien de la page doit être ré-ecrit pour y injecter la metropole
	 */
	public function rewrite_page_link( $link, $page ) {

		if ($this->is_request_metro_filter())
		{
			$m = urlencode($this->get_request_metropole());

			$rewrite = self::is_page_rewrite($page);

			$post = get_post($page);

			if ($rewrite) {

				$pos = strpos( $link, '/'. $post->post_name );
				$new_link = substr_replace($link, "/".$m, $pos, 0);
				return $new_link;
			}
		}

		return $link;
	    
	}

	/**
	 * Récriture des liens vers les taxonomies
	 *
	 */
	public function rewrite_term_link( $url, $term, $taxonomy ) {

		if ($this->is_request_metro_filter())
		{

			// Check if the %kz_metropole% tag is present in the url:
		    if ( false === strpos( $url, self::REWRITE_TAG ) )
		        return $url;
		 
		    $m = urlencode($this->get_request_metropole());
		 
		    // Replace '%kz_metropole%'
		    $url = str_replace( self::REWRITE_TAG, $m , $url );

		}

		//supprimer le TAG si pas de metropole en requete
		if (preg_match('/'.self::REWRITE_TAG.'/', $url))
			$url = str_replace( self::REWRITE_TAG, '' , $url );
	 
	    return $url; 
	}

	/**
	 * Le post est associé à la metropole du user
	 * 
	 * @see Kidzou_Admin::enrich_profile() Association d'un user avec une métropole
	 * @param int $post_id The ID of the post currently being edited.
	 **/
	private function set_user_metropole($post_id, $user_id=0)
	{
	    if (!$post_id) return;

	    if ($user_id=0) {
	    	$user_id = get_current_user_id();
	    }

	    if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

	    //la metropole est la metropole de rattachement du user
	    $metropoles = wp_get_object_terms( $user_id, 'ville', array('fields' => 'all') );

	    if (!empty($metropoles) && count($metropoles)>0) {
		    //bon finalement on prend la premiere metropole
		    $ametro = $metropoles[0];
		    $metro_id = $ametro->term_id;
	    } else {

	    	$metro_id = Kidzou_Utils::get_option('geo_default_metropole'); //init : ville par defaut
	    }

	    $result = wp_set_post_terms( $post_id, array( intval($metro_id) ), 'ville' );
	}



} //fin de classe

?>