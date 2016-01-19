<?php 

add_action( 'kidzou_loaded', array( 'Kidzou_Watcher_Widget', 'get_instance' ) );

/**
 *
 * permet d'ajouter le Widget de "Benchmark" de sites concurrents afin de monitorer
 * depuis le dashboard le contenu concurrent
 *
 * @package   Kidzou_Admin
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

class Kidzou_Watcher_Widget {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
 
    private function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'add_watcher_widget' ) );
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
 
 
    function add_watcher_widget() {

    	global $kidzou_options;

 		if ( isset($kidzou_options['widget_watcher_activate']) && 
 				$kidzou_options['widget_watcher_activate'] && 
 				Kidzou_Utils::current_user_is('administrator') ) {

    		 wp_add_dashboard_widget(
	            'kidzou_watcher',
	            'Contenus sous surveillance',
	           	array($this, 'widget_watcher_content')
	        );
    	}
    }

    /**
     * Merci le petit util trouvé ici
     * @link http://simplehtmldom.sourceforge.net/
     *
     */
    function widget_watcher_content() {
    		
		$html = file_get_html('http://www.motherinlille.com/agenda/');

		// Find all images 
		echo '<h2>Mother In Lille</h2><ul>';
		foreach($html->find('.article') as $element)  
		{
			foreach($element->find('.title a') as $li) 
	       	{
	             $title = '<a href="'.$li->href.'">'.$li->plaintext.'</a>';
	       	}
	       	foreach($element->find('.date') as $li) 
	       	{
	             $date = $li->plaintext;
	       	}
			echo '<li>'.$title.' - '.$date.'</li>';
		}
		echo '</ul>';
		echo '<br/>';
		$html = file_get_html('http://www.zenithdelille.com/spectacles-du-zenith.html');
		echo '<h2>Zenith</h2><ul>';
		foreach($html->find('article.spectacle') as $element)  
		{
			foreach($element->find('time .month') as $li) 
	       	{
	             $date = $li->plaintext;
	       	}
	       	foreach($element->find('.infos .title') as $title) 
	       	{
	             $title = $title->plaintext;
	       	}
	       	echo '<li>'.$title.' - '.$date.'</li>';
			
		}
		echo '</ul>';

		       

    }
 
}
 


?>