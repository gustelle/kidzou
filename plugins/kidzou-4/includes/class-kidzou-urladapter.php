<?php


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
class Kidzou_Geo_URLAdapter {

	/**
	 * par defaut toutes les requetes sont filtrables par geoloc 
	 * (i.e. toutes les requetes qui vont chercher du contenu en base sont filtrées par geoloc)
	 * 
	 */
	protected $adaptable = true;


	/**
	 * Instanciation impossible de l'exterieur, la classe est statique
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function __construct() { 

		
			
	}

	public function set_adaptable($bool = true) {

		$this->adaptable = $bool;
	}


	/**
	 * les requetes de contenu doivent elle etre filtrées par geoloc ?
	 * 
	 */
	public function is_adaptable( )
	{
		if (is_admin()) {
			Kidzou_Utils::log('Page WP-Admin : pas de filtrage');
			return false;
		}
			
		if (!$this->adaptable) {
			Kidzou_Utils::log('Filtrage desactive pour cette requete');
			return false;
		}

		$active = (bool)Kidzou_Utils::get_option('geo_activate',false);
		if (!$active) {
			Kidzou_Utils::log('Filtrage desactive pour le site');
			return false;
		}

		return true;
	} 

   
	


} //fin de classe

?>