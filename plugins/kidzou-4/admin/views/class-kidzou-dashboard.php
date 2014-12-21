<?php 

add_action( 'kidzou_loaded', array( 'Kidzou_Dashboard_Widgets', 'get_instance' ) );

/**
 * Kidzou Admin
 *
 * @package   Kidzou_Dashboard_Widgets
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

class Kidzou_Dashboard_Widgets {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
 
    private function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
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
 
    function remove_dashboard_widgets() {


    	//pour tout le monde
    	remove_meta_box( 'dashboard_primary', 'dashboard', 'side');
 	
 		if (!Kidzou_Utils::current_user_is('administrator')) {

 			remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');
 		}
 			
    }
 
    function add_dashboard_widgets() {

    	global $kidzou_options;

    	//uniquement poru ceux qui peuvent contribuer
    	if (isset($kidzou_options['widget_guidelines_activate']) && $kidzou_options['widget_guidelines_activate'] && Kidzou_Utils::current_user_is('contributor') ) {

    		 wp_add_dashboard_widget(
	            'kidzou_contributor_guidelines',
	            $kidzou_options['widget_guidelines_title'],
	           	array($this, 'widget_guidelines_content')
	        );
    	}

    }

    public function widget_guidelines_content() {

		global $kidzou_options;

    	echo $kidzou_options['widget_guidelines_body'];

    }
 
}
 


?>