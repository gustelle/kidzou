<?php

add_action( 'kidzou_loaded', array( 'Kidzou_WebPerf', 'get_instance' ) );


/**
 * Kidzou
 *
 * @package   Kidzou_WebPerf
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
 * @package Kidzou_WebPerf
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_WebPerf {


	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * les JS qui restent dans le footer mais ne sont pas chargés par webperf.js
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $exceptions = array( 'jquery-migrate' ); 

	/**
	 * les JS qui restent tels quels car ils embarquent des variables contextuelles par wp_localize_script
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $do_not_touch = array( 'jquery', 'ko', 'ko-mapping', 'kidzou-storage' ,'kidzou-webperf','jquery-core', 'kidzou-plugin-script', 'kidzou-notif','kidzou-geo' ); //


	/**
	 * les scripts supprimés de la queue wordpress, donc non rendus en HTML
	 * ils sont chargés par le script de webperf
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $removed_from_queue = array();

	

	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		//important de le faire tourner en dernier pour récupérer une liste complete de JS
		add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX);
		add_action( 'wp_print_scripts',	array( $this, 'arrange_scripts'), PHP_INT_MAX);
		
		// add_action( 'wp_footer',	array($this, 'enqueue_scripts'), PHP_INT_MAX);
		// add_action( 'wp_footer',	array($this, 'arrange_scripts'), PHP_INT_MAX);

		add_action( 'wp_print_footer_scrits',	array($this,'arrange_footer_scripts'),	PHP_INT_MAX);

		//chargement des scripts du footer en async
		add_filter( 'clean_url', array($this,'load_js_async'), 11, 1);

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
	 *
	 * @since    1.0.0
	 */
	public static function enqueue_scripts() {

		global $wp_scripts;

		$activate= ((bool)Kidzou_Utils::get_option('perf_activate',false)) ;

		if (!is_admin() && $activate )
		{
			$all_exceptions = array_merge( Kidzou_Utils::get_option('perf_exclude_jshandle', array()) , self::$exceptions);
			$all_not_touch = array_merge( Kidzou_Utils::get_option('perf_do_not_touch', array()) , self::$do_not_touch);

		    foreach( $wp_scripts->queue as $queued ) {

		    	$is_not_touch = in_array($queued, $all_not_touch);

		    	if ( !$is_not_touch ) {

		    		$is_exception = in_array($queued, $all_exceptions);

		    		if ( !$is_exception ) { //wp_script_is( $registered->handle ,'enqueued') && && !in_array($registered->handle, self::$removed_from_queue)

			    		$registered = $wp_scripts->registered[$queued];
			    		array_push(self::$removed_from_queue, array(
				    			'handle' => $queued,
				    			'src' => $registered->src,
				    			'deps' => $registered->deps
			    			)
			    		);

		    		} else {

		    			$registered = $wp_scripts->registered[$queued];
		    			//s'assurer que ces scripts snt bien dans le footer s'ils ne sont pas chargés par webperf.js
			    		wp_deregister_script($registered->handle);
		    			wp_dequeue_script( $registered->handle );
		    			wp_register_script($registered->handle, $registered->src, $registered->deps, Kidzou::VERSION, true);
						wp_enqueue_script( $registered->handle );
		    		}
		    		
		    	}

		    }

		    wp_enqueue_script( 'kidzou-webperf' , plugins_url( '../assets/js/kidzou-webperf.js', __FILE__ ), array(  ), Kidzou::VERSION, true );
			wp_localize_script('kidzou-webperf', 'kidzou_webperf', array(
					'js' => self::$removed_from_queue,
					'version' => Kidzou::VERSION
				)
			);

		}
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public static function arrange_scripts() {

		$activate= ((bool)Kidzou_Utils::get_option('perf_activate',false)) ;

		if (!is_admin() && $activate)
		{
			foreach( self::$removed_from_queue as $registered ) {

	    		//il n'a plus rien à faire dans la queue, il sera chargé par JS 
	    		wp_deregister_script($registered['handle']);
	    		wp_dequeue_script( $registered['handle'] );
	    	
		    }
		}
	
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public static function arrange_footer_scripts() {


	    global $wp_scripts;
	    Kidzou_Utils::log($wp_scripts->registered);
	
	}



	// ACTION wp_footer
	public static function load_js_async($url) {

		$activate= ((bool)Kidzou_Utils::get_option('perf_activate',false)) ;

		//jquery est vraiment chiant...
		if (!is_admin() && $activate && !preg_match('#(jquery.js|knockout-min.js)#', $url) )
		{
			if ( FALSE === strpos( $url, '.js' ) )
		    { // not our file
		    	return $url;
		    }
			return "$url' async='async";
		}
		else
			return $url;
		
	}




} //fin de classe

?>