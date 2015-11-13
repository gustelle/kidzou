<?php

add_action( 'plugins_loaded', array( 'Kidzou_WebPerf', 'get_instance' ), 100 );


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
	 * les JS qui ne doivent pas avoir l'attribut async
	 * cette liste est issue de tests en condition réelle (en prod)
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $js_no_async = array( );  //'jquery-core' , 'jquery-cookie', 'kidzou-storage', 'kidzou-plugin-script', 'ko'

	/**
	 * les CSS qui ne sont pas combinés avec les autres
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $css_no_combine = array(  );  //


	/**
	 * les CSS supprimés de la queue wordpress, donc non rendus en HTML
	 * ils sont chargés par le script de webperf-css
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	public static $css_load_per_js = array();

	/**
	 * les JS supprimés de la queue wordpress, 
	 * ils sont chargés par le script de webperf-js
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	public static $js_async_load = array();

	

	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		if (!Kidzou_Utils::is_really_admin())
		{
			//important de le faire tourner en dernier pour récupérer une liste complete de JS
			add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX);
			add_action( 'wp_print_styles', array( $this, 'enqueue_styles' ), PHP_INT_MAX);

			add_action( 'wp_print_footer_scrits',	array($this,'arrange_footer_scripts'),	PHP_INT_MAX);

			//chargement des scripts du footer en async
			//todo : utiliser add_filter(script_loader_tag)
			//voir https://developer.wordpress.org/reference/classes/wp_scripts/do_item/
			// add_filter( 'clean_url', array($this,'load_js_async'), 11, 1);
			add_filter('script_loader_tag', array($this,'add_aync_attr'), 11, 2);

			//supprimer les id des css (https://blog.codecentric.de/en/2011/10/wordpress-and-mod_pagespeed-why-combine_css-does-not-work/)
			add_filter('style_loader_tag', array($this,'remove_style_id'), 11, 2);

			self::$js_no_async = array_merge(self::$js_no_async, Kidzou_Utils::get_option('perf_js_no_async', array()));

			self::$css_no_combine = array_merge(self::$css_no_combine, Kidzou_Utils::get_option('perf_css_no_combine', array()));

			add_action( 'wp_footer', array($this, 'load_css_async'), PHP_INT_MAX);
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
	 * Force le chargement des scripts dans le footer
	 * Les JS identifiés en option 'perf_js_root_dependency' sont chargés en sync
	 * les autres, sont chargés en async defer 
	 *
	 * @since    1.0.0
	 */
	public static function enqueue_scripts() {

		global $wp_scripts;

		$activate= ((bool)Kidzou_Utils::get_option('perf_activate_js',false)) ;

		if (!is_admin() && $activate )
		{	

			// $js_no_async = array_merge( Kidzou_Utils::get_option('js_no_async', array()) , self::$js_no_async);
			$queue = $wp_scripts->queue;
			$datas = array();

			wp_register_script('kidzou-webperf-js' , plugins_url( '../assets/js/kidzou-webperf-js.js', __FILE__ ), array(  ), Kidzou::VERSION, true);
			wp_enqueue_script('kidzou-webperf-js' );

		    foreach( $queue as $queued ) {

		    		$registered = $wp_scripts->registered[$queued];

	    		 	//ce qui provient de wp_localize_script()
	    			$local_script = $wp_scripts->get_data($queued, 'data');   

	    		 	//s'assurer que ces scripts snt bien dans le footer s'ils ne sont pas chargés par webperf.js		    		
				   	wp_deregister_script($registered->handle);
					wp_dequeue_script( $registered->handle );

					//reattacher en footer
					wp_register_script($registered->handle , $registered->src, $registered->deps, Kidzou::VERSION, true);
					wp_enqueue_script($registered->handle );
					$wp_scripts->add_data( $registered->handle, 'data', $local_script );

		    }


		}
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public static function enqueue_styles() {

		global $wp_styles;

		$activate= ((bool)Kidzou_Utils::get_option('perf_activate_css',false)) ;

		if (!is_admin() && $activate )
		{
			$all_css_in_header = array_merge( Kidzou_Utils::get_option('perf_css_in_header', array()) , self::$css_in_header);

			foreach( $wp_styles->queue as $queued ) {

				$is_exception = in_array($queued, $all_css_in_header);
				if ( !$is_exception )
				{
					$registered = $wp_styles->registered[$queued];

					wp_dequeue_style($registered->handle);
					wp_deregister_style($registered->handle);

					array_push(self::$css_load_per_js, array(
				    			'handle' => $registered->handle,
				    			'src' => $registered->src,
				    			'media' => $registered->args
			    			)
			    		);

				}

			}

			wp_enqueue_script( 'kidzou-webperf-css' , plugins_url( '../assets/js/kidzou-webperf-css.js', __FILE__ ), array(  ), Kidzou::VERSION, true );
			wp_localize_script('kidzou-webperf-css', 'kidzou_webperf_css', array(
					'version' => Kidzou::VERSION,
					'css' => self::$css_load_per_js
				)
			);

		}
	}

	/**
	 * Utilisée par le Hook <code>script_loader_tag</code> , retravaille le bout de HTML généré par wordpress pour charger les JS (<script>)
	 * afin d'y insérer les propriétés defer et async qui permettent un respectivement de déférer l'execution du JS apres chargement du DOM sans le bloquer et
	 * de déférer le chargement du script en asynchrone sans bloquer le DOM
	 *
	 * @see script_loader_tag
	 */
	public static function add_aync_attr($html, $handle) {

		$activate = ((bool)Kidzou_Utils::get_option('perf_activate_js',false)) ;
		// $add_async_attr = ((bool)Kidzou_Utils::get_option('perf_add_async_attr',false)) ;

		if (!is_admin() && $activate && !in_array($handle, self::$js_no_async) )
		{
			//pas d'att async defer si la source n'est pas sépcifiée, cela cause une erreur de validation W3C
			// Kidzou_Utils::log($html . preg_match("/src=/", $html));
			if (!preg_match("/src=/", $html))
				return $html;

			return preg_replace("/<script/", "<script async defer", $html);
		}
		return $html;
		
	}

	//necessaire pour permettre une combinaison des CSS par mod_pagespeed
	/**
	 * Utilisée par le Hook <code>style_loader_tag</code> , retravaille le bout de HTML généré par wordpress pour charger les CSS (<style>)
	 * afin de supprimer l'attribut 'id' qui bloque la concaténation CSS par Mod_PageSpeed
	 *
	 * @see style_loader_tag
	 */
	public static function remove_style_id($link, $handle) {

		// $activate = ((bool)Kidzou_Utils::get_option('perf_activate',false)) ;
		$combine_css = ((bool)Kidzou_Utils::get_option('perf_remove_css_id',false)) ;

		//jquery est vraiment chiant...
		if (!is_admin() && $combine_css)
		{
			return preg_replace("/id='.*-css'/", "", $link);
		}
		return $link;
		
	}

	/**
	 * Si les éléments CSS sont chargés en Asynchrone par JS, cette fonction ajoute la liste des CSS à charger en <noscript>
	 * pour les navigateurs qui n'acceptent pas le JS
	 *
	 * @return void
	 * @author 
	 **/
	public function load_css_async()
	{
		$out = '';
		$css_per_js = ((bool)Kidzou_Utils::get_option('perf_activate_css',false)) ;
		if (!is_admin() && $css_per_js)
		{
			// global $wp_styles;
			$out .= '<noscript>';
			$css = Kidzou_WebPerf::$css_load_per_js;

			foreach ($css as $item) {
				$src = $item['src'];
				$media = $item['media'];
				$ver = Kidzou::VERSION;
				$out .= "<link rel='stylesheet' property='stylesheet' href='$src?ver=$ver' type='text/css' media='$media' />";
			}
			$out .= '</noscript>';
		}

		echo $out;
	}



} //fin de classe

?>