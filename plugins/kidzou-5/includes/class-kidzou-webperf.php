<?php

add_action( 'plugins_loaded', array( 'Kidzou_WebPerf', 'get_instance' ), 100 );


/**
 * Intégration avec Apache mod_pagespeed, notamment pour poser des tags qui empeche mod_pagespeed de toucher certains éléments CSS ou JS
 *
 * @package Kidzou
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
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() { 

		if (!Kidzou_Utils::is_really_admin())
		{

			//protecter des scripts contre l'optimisation defer_javascript 
			add_filter('script_loader_tag', array($this,'no_defer'), 11, 2);

			//supprimer les id des css (https://blog.codecentric.de/en/2011/10/wordpress-and-mod_pagespeed-why-combine_css-does-not-work/)
			add_filter('style_loader_tag', array($this,'remove_style_id'), 11, 2);

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
	 * Utilisée par le Hook <code>script_loader_tag</code> , retravaille le bout de HTML généré par wordpress pour charger les JS (<script>)
	 * afin d'y insérer les propriétés defer et async qui permettent un respectivement de déférer l'execution du JS apres chargement du DOM sans le bloquer et
	 * de déférer le chargement du script en asynchrone sans bloquer le DOM
	 *
	 * @see script_loader_tag
	 */
	public static function no_defer($html, $handle) {

		$list = Kidzou_Utils::get_option('perf_js_no_async', array()) ;
		
		if (!is_admin() && count($list)>0 )
		{
			foreach ($list as $exclusion) {
				if (trim($exclusion)==$handle) return preg_replace("/<script/", "<script data-pagespeed-no-defer", $html);
			}
			return $html;
		}
		return $html;
		
	}

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



} //fin de classe

?>