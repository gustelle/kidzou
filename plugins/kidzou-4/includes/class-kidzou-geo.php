<?php

add_action('kidzou_loaded', array('Kidzou_Geo', 'get_instance'));
/* seulement à l'activation du plugin */
// add_action( 'kidzou_activate', array('Kidzou_Geo', 'set_permalink_rules'));

/**
 * Kidzou
 *
 * @package   Kidzou_Geo
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
 * @package Kidzou_Geo
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Geo {


	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	protected static $request_metropole = null;


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

		//Le filtrage n'est pas actif pour certaines requetes, typiquement les API d'export de contenu
		if (preg_match('#\/api\/#', $_SERVER['REQUEST_URI']) ) {

			Kidzou_Utils::log('URI non geo-filtrable : '.$_SERVER['REQUEST_URI']);

		} else {

			add_filter( 'post_link', array( $this, 'rewrite_post_link' ) , 10, 2 );
			add_filter( 'page_link', array( $this, 'rewrite_page_link' ) , 10, 2 );
			add_filter( 'term_link', array( $this, 'rewrite_term_link' ), 10, 3 );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );

			add_action( 'pre_get_posts', array( $this, 'geo_filter_query'), 999 );
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
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function enqueue_geo_scripts()
	{

		// $urladapter = new Kidzou_Geo_URLAdapter();

		// if (!is_admin() && (bool)Kidzou_Utils::get_option('geo_activate',false))
		// {

			wp_enqueue_script('kidzou-geo', plugins_url( '../assets/js/kidzou-geo.js', __FILE__ ) ,array('jquery','kidzou-storage'), Kidzou::VERSION, true);

			$villes = self::get_metropoles();

			$key = Kidzou_Utils::get_option("geo_mapquest_key",'Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6');
	  
			$args = array(
						'geo_activate'				=> (bool)Kidzou_Utils::get_option('geo_activate',false), //par defaut non
						'geo_mapquest_key'			=> $key, 
						'geo_mapquest_reverse_url'	=> "http://open.mapquestapi.com/geocoding/v1/reverse",
						'geo_mapquest_address_url'	=> "http://open.mapquestapi.com/geocoding/v1/address",
						// 'geo_default_metropole'		=> self::get_default_metropole(),
						'geo_cookie_name'			=> "kz_metropole",
						// 'geo_select_cookie_name'	=> "kz_metropole_selected",
						'geo_possible_metropoles'	=> $villes ,
					);

		    wp_localize_script(  'kidzou-geo', 'kidzou_geo_jsvars', $args );
		// }

	}

	/**
	 * déclenchée à l'actication de la geoloc
	 * Mise à jour de la structure des permaliens Category et Tag
	 *
	 * Mise à jour du .htaccess avec les règles de geoloc
	 *
	 * @return void
	 * @author 
	 **/
	public static function set_permalink_rules () {
		
		global $wp_rewrite;

		$wp_rewrite->set_category_base('%kz_metropole%/rubrique/');
		$wp_rewrite->set_tag_base('%kz_metropole%/tag/');

		self::create_rewrite_rules();
		
	}

	/**
	 * déclenchée à la desactivation de la geoloc
	 * Mise à jour de la structure des permaliens Category et Tag
	 *
	 * Mise à jour du .htaccess avec les règles de geoloc
	 *
	 * @return void
	 * @author 
	 **/
	public static function unset_permalink_rules () {
		
		global $wp_rewrite;

		$wp_rewrite->set_category_base('rubrique/');
		$wp_rewrite->set_tag_base('tag/');
		
	}

	

    /**
	 * Les Query en Base sont filtrées en tenant compte de la métropole courante
	 * Celle-ci est soit la metropole passée dans la requete (en provenance du cookie utilisateur), soit la metropole par défaut
	 * les contenus à portée "nationale" sont également remontés
	 *
	 * @since    1.0.0
	 */
	public static function geo_filter_query( $query ) {

		$urladapter = new Kidzou_Geo_URLAdapter();

		if ( $urladapter->is_adaptable() )
		{
			// global $wp_query;
			//les pages woo commerce n'ont pas a etre filtrées par metropole
			//sinon les produits n'apparaissent plus dans les cats...  
	       	$supported_taxonomies = array('age', 'ville', 'divers', 'category','post_tag');
	       	$queried_object = get_queried_object();

	       	if (is_wp_error($queried_object) || $queried_object==null)
	       		return $query;

	       	if (!property_exists($queried_object, 'taxonomy') || !in_array($queried_object->taxonomy, $supported_taxonomies))
	       		return $query;

		    if( !is_admin() && !is_search() ) {

		        $ville_tax_present = false;

		        //reprise des arguments qui auraient pu être passés précédemment par d'autres requetes
		        //d'ou l'importance d'executer celle-ci en dernier
		        $vars = get_query_var('tax_query');

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
		        	$vars[] = self::get_query_args();

		        if (!empty($vars))
		        {
		            //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
		            set_query_var('tax_query', $vars);
		        }


		        return $query;
		    }

		}

	    return $query;
	}


	public static function get_query_args() {

		$the_metropole = array();
  		$the_metropole[] = self::get_request_metropole();

        $national = (array)self::get_national_metropoles(); 
       	$merge = array_merge( $the_metropole, $national );

       	return array(
                  'taxonomy' => 'ville',
                  'field' => 'slug',
                  'terms' => $merge
                );

	}
	/**
	 * la metropole de rattachement de la requete
	 * si aucune metropole ne sort de la requete, et si aucun cookie n'est détecté, la chaine $no_filter est retournée
	 *
	 * @return String (slug)
	 * @author 
	 **/
	public static function get_request_metropole()
	{

		if (self::$request_metropole==null) 
		{
			//d'abord on prend la ville dans l'URI
			$uri = $_SERVER['REQUEST_URI'];

			Kidzou_Utils::log('[get_request_metropole] REQUEST_URI : '. $uri);

			$regexp = self::get_metropole_uri_regexp();

			$cook_m = '';

			//la metropole en provenance du cookie
			if ( isset($_COOKIE['kz_metropole']) )
				$cook_m = strtolower($_COOKIE['kz_metropole']);

			Kidzou_Utils::log('[get_request_metropole] _COOKIE : ' . $cook_m);

			//en dépit du cookie, la valeur de la metropole passée en requete prime
			if (preg_match('#\/'.$regexp.'(/)?#', $uri, $matches)) {

				Kidzou_Utils::log('[get_request_metropole] Regexp identifiée ');
				
				$ret = rtrim($matches[0], '/'); //suppression du slash à la fin
				$metropole = ltrim($ret, '/'); //suppression du slash au début

				Kidzou_Utils::log('[get_request_metropole] Regexp : '. $metropole);

				//avant de renvoyer la valeur, il faut repositionner le cookie s'il n'était pas en cohérence
				//la valeur de metropole passée en requete devient la metropole du cookie
				if ($cook_m!=$metropole && $metropole!='') {

					setcookie("kz_metropole", $metropole);
					// setcookie("kz_metropole_selected", true, time()+(60*60*24), '/' ); //cookie valable 1 jour... 

					self::$request_metropole = $metropole;

					//positionner cette variable pour ne pas aller plus loin
					$cook_m = self::$request_metropole;

					Kidzou_Utils::log('[get_request_metropole] setcookie : '. $cook_m);
				}	

			}

			//si l'URI ne contient pas la ville, on prend celle du cookie, sinon celle en parametre de requete
			if ($cook_m=='' && isset($_GET['kz_metropole']))  {
				$cook_m = strtolower($_GET['kz_metropole']);
				Kidzou_Utils::log('[get_request_metropole] kz_metropole : '. $cook_m);
			} 

			//si rien ne match, on prend la ville par défaut
			if ($cook_m=='')  {
				$cook_m = self::get_default_metropole();
				Kidzou_Utils::log('[get_request_metropole] ville par défaut : '. $cook_m);
			} 

		    $isCovered = false;

		    if ($cook_m!='') 
		    	$isCovered = self::is_metropole($cook_m);

		    // Kidzou_Utils::log('[get_request_metropole] isCovered : '. $isCovered);

		    if ($isCovered) 
		    	self::$request_metropole = $cook_m;
		    else
		    	self::$request_metropole = ''; //on désactive meme la geoloc en laissant la metropole à ''

		    Kidzou_Utils::log('Kidzou_Geo::get_request_metropole() : '. self::$request_metropole );
		}

		return self::$request_metropole;
		
	}

    public static function get_metropoles()
    {

        $result = get_transient('kz_covered_metropoles_all_fields');

	    if (false===$result)
	    {
	        $villes = get_terms( array("ville"), array(
	            "orderby" => "slug",
	            "parent" => 0, //only root terms,
	            "fields" => "all"
	        ) );

	        $result  = array();

	        //sortir les villes à couverture nationale
	        //on prend le premier de la liste
	        foreach ($villes as $key=>$value) {
	            $def = Kidzou_Utils::get_option('geo_national_metropole'); 
	            if ( intval($def) ==  intval($value->term_id) ) {

	            } else {
	                $result[$key] = $value;
	                Kidzou_Utils::log('Kidzou_Geo::get_metropoles() : adding ' . $value->slug);
	            }
	        }   

	        if (!empty($result) && count($result)>0)
	       		set_transient( 'kz_covered_metropoles_all_fields', (array)$result, 60 * 60 * 24 ); //1 jour de cache

	        Kidzou_Utils::log('kz_covered_metropoles_all_fields -> set ' . count($result) . ' result');
	    }

	    return $result;
    }

    /**
	 * retourne un tableau de villes à portée nationale
	 * les villes à portée nationale ont pour vocation de porter des articles à portée nationale 
	 * Les villes à portée nationale doivent être à la racine 
	 *
	 * @return array
	 * @author 
	 **/
	public static function get_national_metropoles()
	{

	    $term = get_term_by('id', Kidzou_Utils::get_option('geo_national_metropole'), 'ville');

	    $result = array();

	    if (!is_wp_error($term) && is_object($term)) {
			array_push($result, $term->slug);
	    }

	    return $result;


	}

	

	/**
	 * la ville (slug) passee en parametre est-elle connue comme metropole dans notre système?
	 *
	 * @return Booléen
	 * @author 
	 **/
	public static function is_metropole($m)
	{

	    if ($m==null || $m=="") return false;

	    //la ville du user est-elle couverte par Kidzou
	    $villes  = self::get_metropoles();

	    $isCovered = false;
	    foreach ($villes as $v) {
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


	public static function rewrite_post_link( $permalink, $post ) {

		$urladapter = new Kidzou_Geo_URLAdapter();

		if ($urladapter->is_adaptable())
		{
			$m = urlencode(self::get_request_metropole());

		    // Check if the %kz_metropole% tag is present in the url:
		    if ( true === strpos( $permalink, '%kz_metropole%' ) ) {

			    // Replace '%kz_metropole%'
			    $permalink = str_replace( '%kz_metropole%', $m , $permalink );

		    } 
			    
			
		}
		 
	    return $permalink;
	}

	/**
	 * Reecriture des pages qui utilisent le tempate 'tous les contenus'
	 * car ces pages sont geolocalisées, c'est à dire que "tous" les contenus sont en fait
	 * filtres par la metropole de rattachement du user
	 *
	 */
	public static function rewrite_page_link( $link, $page ) {

		$urladapter = new Kidzou_Geo_URLAdapter();
		if ($urladapter->is_adaptable())
		{
			$m = urlencode(self::get_request_metropole());

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


	public static function rewrite_term_link( $url, $term, $taxonomy ) {

		$urladapter = new Kidzou_Geo_URLAdapter();

		if ($urladapter->is_adaptable())
		{

			// Check if the %kz_metropole% tag is present in the url:
		    if ( false === strpos( $url, '%kz_metropole%' ) )
		        return $url;
		 
		    $m = urlencode(self::get_request_metropole());
		 
		    // Replace '%kz_metropole%'
		    $url = str_replace( '%kz_metropole%', $m , $url );

		}
	 
	    return $url; 
	}


	/**
	 * les infos d'emplacement géographique d'un post
	 *
	 * @return Tableau contenant les meta de Geoloc d'un post
	 * @author 
	 **/
	public static function get_post_location($post_id=0)
	{

	    if ($post_id==0)
	    {
	        global $post; 
	        $post_id = $post->ID; 
	    }

	    $post = get_post($post_id); 

	   	$type = $post->post_type;

	    $location_name      = get_post_meta($post_id, 'kz_'.$type.'_location_name', TRUE);
	    $location_address   = get_post_meta($post_id, 'kz_'.$type.'_location_address', TRUE);
	    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE);
	    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE);
	    $location_tel   = get_post_meta($post_id, 'kz_'.$type.'_location_phone_number', TRUE);
	    $location_web   = get_post_meta($post_id, 'kz_'.$type.'_location_website', TRUE);
	    $location_city   = get_post_meta($post_id, 'kz_'.$type.'_location_city', TRUE);

	    return array(
	        'location_name' => $location_name,
	        "location_address" => $location_address,
	        "location_latitude" => $location_latitude,
	        "location_longitude" => $location_longitude,
	        "location_tel" => $location_tel,
	        "location_web" => $location_web,
	        "location_city" => $location_city
	    );
	}

	/**
	 * l'URL de la page doit-elle etre préfixée de la metropole du user ?
	 *
	 * @return void
	 * @author 
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
	 * le post est-il associé à un lieu ?
	 *
	 * @return Tableau contenant les meta de Geoloc d'un post
	 * @author 
	 **/
	public static function has_post_location($post_id=0)
	{

	    if ($post_id==0)
	    {
	        global $post;
	        $post_id = $post->ID;
	    }

	    $post = get_post($post_id);

	    $type = $post->post_type;

	    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE);
	    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE);

	    return $location_latitude<>'' && $location_longitude<>'';
	}

	public static function get_metropole_uri_regexp() {

		$regexp = get_transient('kz_metropole_uri_regexp'); 

   		if (false===$regexp) {

   			$villes = self::get_metropoles();

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
	 * Rewrites incluant les metropoles
	 *
	 */
	public static function create_rewrite_rules() {

		if ((bool)Kidzou_Utils::get_option('geo_activate',false)) 
		{
			global $wp_rewrite; 

			$regexp = self::get_metropole_uri_regexp();
			add_rewrite_tag('%kz_metropole%',$regexp, 'kz_metropole=');

			//see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
		    add_rewrite_rule($regexp.'$','index.php?kz_metropole=$matches[1]','top'); //home
		    add_rewrite_rule($regexp.'/offres/page/?([0-9]{1,})/?','index.php?post_type=offres&paged=$matches[2]&kz_metropole=$matches[1]','top');
		    add_rewrite_rule($regexp.'/offres/?','index.php?post_type=offres&kz_metropole=$matches[1]','top');
		   	add_rewrite_rule($regexp.'/(.*)$/?','index.php?pagename=$matches[2]&kz_metropole=$matches[1]','top');
			add_rewrite_rule($regexp.'/(.*)/page/?([0-9]{1,})/?$','index.php?pagename=$matches[2]&paged=$matches[3]&kz_metropole=$matches[1]','top');

			//si la ville n'est pas spécifiée en requete, car le user est arrivé directement sur un post (donc pas préfixé par une ville)
			//et navigue ensuite vers une rubrique ou autre:
			add_rewrite_rule('/?rubrique/(.*)/?','index.php?category_name=$matches[1]','top');

		}
		
	    
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
	                
	            	$def = Kidzou_Utils::get_option('geo_national_metropole');;
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

	public static function get_related_posts() {

		add_filter('crp_posts_join', array(self::get_instance(), 'crp_filter_metropole')) ;

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
			$join .= "
			INNER JOIN wp_term_taxonomy AS tt ON (tt.term_id=".$metropole->term_id." AND tt.taxonomy='ville')
			INNER JOIN wp_term_relationships AS tr ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tr.object_id=ID) ";
		}

		return $join;
	}


} //fin de classe

?>