<?php 

add_action( 'kidzou_loaded', array( 'Kidzou_Widget_Import', 'get_instance' ) );

/**
 * Widget Import de contenu rapide
 *
 * @package   Kidzou
 * @author    Guillaume Patin <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://www.kidzou.fr
 * @copyright 2014 Kidzou
 */

class Kidzou_Widget_Import {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;
 
    private function __construct() {

        add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );

        
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
 
    
 
    function add_widget() {

    	if (Kidzou_Utils::current_user_can('can_import_facebook') ) {

			wp_add_dashboard_widget(
	            'kidzou_import',
	            'Importez un événement Facebook',
	           	array($this, 'widget_content')
	        );
		}
    	

    }

    public function widget_content() {

    	global $kidzou_options;

    	wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

 		wp_enqueue_script('moment',			"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js",	array('jquery'), '2.4.0', true);
		wp_enqueue_script('moment-locale',	"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js",	array('moment'), '2.4.0', true);

		wp_enqueue_script('react',			"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.min.js",	array(), '0.14.7', true);
		wp_enqueue_script('react-dom',		"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.min.js",	array('react'), '0.14.7', true);

		wp_enqueue_script('kidzou-react', 			plugins_url( 'assets/js/kidzou-react.js', dirname(__FILE__) ) ,array('react-dom'), Kidzou::VERSION, true);			
		wp_enqueue_script('kidzou-import-metabox', 	plugins_url( 'assets/js/kidzou-import-metabox.js', dirname(__FILE__) ) ,array('jquery', 'moment', 'react-dom'), Kidzou::VERSION, true);

		$facebook_appId 	= $kidzou_options['fb_app_id'];
		$facebook_appSecret = $kidzou_options['fb_app_secret'];

		wp_localize_script('kidzou-import-metabox', 'import_jsvars', array(
				'api_addMediaFromURL'			=> site_url()."/api/import/addMediaFromURL/",
				'facebook_appId'				=> $facebook_appId,
				'facebook_appSecret'			=> $facebook_appSecret,
				'import_form_parent'			=> '#kidzou_import .inside',
				'background_import'				=> true,
				'api_base'						=> site_url(),
				'author_id'						=> get_current_user_id()
			)
		);
    }
 
}
 


?>