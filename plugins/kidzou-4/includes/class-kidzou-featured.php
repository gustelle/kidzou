<?php

add_action('kidzou_loaded', array('Kidzou_Featured', 'get_instance'));


/**
 * Kidzou
 *
 * @package   Kidzou_Featured
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
 * @package Kidzou_Events
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Featured {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	public static $meta_featured = 'kz_event_featured';

	/**
	 * les types de posts qui peuvent être mis en avant
	 *
	 */
	public static $supported_post_types = array('post','offres');


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		//mise en avant de posts
		add_filter( 'posts_results', array( $this, 'order_query_by_featured'), PHP_INT_MAX, 2  );		
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



    public static function isFeatured($post_id = 0)
	{

		if ($post_id==0)
		{
			global $post;
			$post_id = $post->ID;
		}

		$featured_index		= get_post_meta($post_id, self::$meta_featured, TRUE);
		$featured 			= ($featured_index == 'A');

		return $featured;
	}

	/**
	 * la liste des post featured 
	 * il s'agit d'un tableau d'objets WP_Post
	 *
	 * @return void
	 * @author 
	 **/
	public static function getFeaturedPosts(  )
	{
		

		$list = get_posts(array(
					'meta_key'         => self::$meta_featured,
					'meta_value'       => 'A',
					'post_type'        => self::$supported_post_types,
				));


		return $list;
	}

	

	/**
	 * les loops secondaires sont tries pour mettre les featured en 1er
	 *
	 */ 
	public function order_query_by_featured($posts, $query) {

		$post_type = $query->get('post_type');

		//le post type est il suporté par le filtre ?
		if (is_array($post_type))
		{
			foreach ($post_type as $key => $value) {
				if (in_array($value, self::$supported_post_types ))
				{
					$supported_query = true;
					break;
				}
			}
		}
		else
			$supported_query = in_array($post_type, self::$supported_post_types ) ;

		//cas spécial des archives : le post type n'est pas spécifié
		//on ouvre au maximim les post types
		if (is_archive() && $query->is_main_query())
		{
			$query->set('post_type', self::$supported_post_types );
			$supported_query = true;
		}

		if (!Kidzou_Utils::is_really_admin()  && !is_search() && $supported_query ) { //

			// Kidzou_Utils::log(' --- order_query_by_featured');

			remove_filter( current_filter(), __FUNCTION__, PHP_INT_MAX, 2 );

			$queried = $query->get_queried_object();

			if (isset($queried->term_taxonomy_id )) {

				$nonfeatured = array();

			    $featured =  self::getFeaturedPosts( );

			    $filtered = array_filter($featured, function($item) use($queried) {
			    	// $queried = $query->get_queried_object(); //obligé de le rappeler dans cette closure
			    	$terms = wp_get_post_terms($item->ID, $queried->taxonomy, array('fields' => 'ids'));
			    	return in_array($queried->term_id, $terms);
			    });

			    $filtered_posts = array_map(function($el) {
			    	return get_post($el->ID);
			    }, $filtered);
			    
			    foreach ( $posts as $a_post ) {
			     
					if ( !self::isFeatured($a_post->ID) ) {

						$nonfeatured[] = $a_post;

					}			    		

			    }
		    
		    	$posts = array_merge( $filtered_posts, $nonfeatured );

			}		   

		} 
		
		return $posts;
	}


	

    

} //fin de classe

?>