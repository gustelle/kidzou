<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Event', 'get_instance' ), 13);


/**
 * Cette classe gère les metabox des événements (dates, recurrence) dans les écrans d'admin 
 *
 * @package Kidzou_Admin
 * @author  Guillaume Patin <guillaume@kidzou.fr>
 */
class Kidzou_Metaboxes_Event {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * les ecrans qui meritent qu'on y ajoute des meta  d'evenement
	 *
	 * Cette variable est public, est est utlisée dans <code>Kidzou_Admin_geo->enqueue_geo_scripts()</code>
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	public $screen_with_meta_event = array('post'); // typiquement pas les "customer"


	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		
		add_action( 'kidzou_add_metabox', array( $this, 'event_metaboxes' ) );
		add_action( 'kidzou_save_metabox', array( $this, 'save_metaboxes' ) );
	}


	/**
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		// $screen = get_current_screen(); 

		//on a besoin de font awesome dans le paneau d'admin

		// if ( in_array($screen->id , $this->screen_with_meta_event)  ) {
		
		wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

		//gestion des events
		wp_enqueue_script('moment',			"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js",	array('jquery'), '2.11.2', true);
		wp_enqueue_script('moment-locale',	"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/locale/fr.js",	array('moment'), '2.11.2', true);

		wp_enqueue_script('react',			"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react.js",	array('jquery'), '0.14.7', true);
		wp_enqueue_script('react-dom',		"https://cdnjs.cloudflare.com/ajax/libs/react/0.14.7/react-dom.js",	array('react'), '0.14.7', true);
		
		wp_enqueue_script( 	'daypicker-locale-utils', 	plugins_url( '/assets/js/lib/react-DayPicker-LocaleUtils.js', dirname(__FILE__) ), array('moment' ), '1.0', true);
		wp_enqueue_script( 	'daypicker-date-utils', 	plugins_url( '/assets/js/lib/react-DayPicker-DateUtils.js', dirname(__FILE__) ), array( ), '1.0', true);
		wp_enqueue_script( 	'daypicker', 		plugins_url( '/assets/js/lib/react-DayPicker.js', dirname(__FILE__) ), array( 'daypicker-locale-utils', 'daypicker-date-utils', 'react-dom'), '1.0', true);
		wp_enqueue_style( 	'daypicker', 		plugins_url( 'assets/css/lib/react-DayPicker.css', dirname(__FILE__) )  );

		wp_enqueue_script( 'radio-group', 	plugins_url( '/assets/js/lib/react-radio-group.js', dirname(__FILE__) ), array(  'react-dom'), '1.0', true);

		wp_enqueue_script('kidzou-react', 	plugins_url( 'assets/js/kidzou-react.js', dirname(__FILE__) ) ,array('react-dom'), Kidzou::VERSION, true);			
		wp_enqueue_script('kidzou-event-metabox', plugins_url( 'assets/js/kidzou-event-metabox.js', dirname(__FILE__) ) ,array('kidzou-react', 'daypicker'), Kidzou::VERSION, true);

		//insertion des metabox et initialisation des JS
		global $post;
		$event_meta 	= Kidzou_Events::getEventDates($post->ID);
	
		$start_date		= $event_meta['start_date'];
		$end_date 		= $event_meta['end_date'];
		$recurrence		= $event_meta['recurrence'];
		$past_dates		= $event_meta['past_dates'];

		wp_localize_script('kidzou-event-metabox', 'event_jsvars', array(
				'start_date'					=> $start_date,
				'end_date'						=> $end_date,
				'recurrence'					=> $recurrence,
				'past_dates'					=> $past_dates,
				'api_base'						=> site_url(),
				'api_save_event'				=> site_url()."/api/content/eventData/",
				'allow_recurrence'				=> Kidzou_Utils::current_user_can('can_set_event_recurrence')
			)
		);



		// } 
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

	public function event_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta_event) ) { 
			
			// Load admin style sheet and JavaScript.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

			add_meta_box('kz_event_metabox', 'Evenement', array($this, 'event_metabox'), $screen->id, 'normal', 'high');
		} 
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function event_metabox()
	{
		// Noncename needed to verify where the data originated
		wp_nonce_field( 'event_metabox', 'event_metabox_nonce' );
		echo '<div class="react-content"></div>';

	}



	/**
	 *  sauvegarde des meta lors de l'enregistrement d'un post ou d'une page
	 *
	 * @return void
	 **/
	public function save_metaboxes($post_id) {

		$this->unarchive_event($post_id);

		$this->save_event_meta($post_id);

	}

	/**
	 * <p>
	 * Un événement obsolète est automatiquement dépublié ou archivé par le système.
	 * Lorsqu'un événement est marqué par la meta "archive" par le cron, le système sait que le traitement est déjà passé sur cet evenement et ne cherche pas à repasser dessus 
	 * <br/>
	 * Lorsqu'un user reactualise un événement (changement de dates), il faut supprimer cette meta pour que le traitement puisse repasser dessus et le ré-archiver ou dépublier selon préférence de l'utilisateur
	 * </p>
	 *
	 * @internal
	 *
	 **/
	private function unarchive_event($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		delete_post_meta($post_id, Kidzou_Events::$meta_archive); 

		// Kidzou_Utils::log('Evenement '.$post_id. ' désarchivé');
	}

	/**
	 *
	 * @return void
	 * @author 
	 **/
	private function save_event_meta($post_id)
	{
		if( wp_is_post_revision( $post_id) || wp_is_post_autosave( $post_id ) ) 
			return ;

		$slug = 'post';

	    // If this isn't a 'post', don't update it.
	    if ( !isset($_POST['post_type']) || $slug != $_POST['post_type'] ) {
	        return;
	    }

		// Check if our nonce is set.
		if ( ! isset( $_POST['event_metabox_nonce'] ) )
			return $post_id;

		$nonce = $_POST['event_metabox_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'event_metabox' ) )
			return $post_id;

		$start_date = (isset($_POST['kz_event_start_date']) ? $_POST['kz_event_start_date'] : '');
		$end_date	= (isset($_POST['kz_event_end_date']) ? $_POST['kz_event_end_date'] : '');

		//les options de récurrence
		$recurrence = array();
		if (isset($_POST['kz_event_is_reccuring']) && $_POST['kz_event_is_reccuring']=='on')
		{
			$recurrence = array(
					"model" 		=> $_POST['kz_event_reccurence_model'],
					"repeatEach" 	=> $_POST['kz_event_reccurence_repeat_select'],
					"repeatItems" 	=> $_POST['kz_event_reccurence_repeat_items'], 
					"endType" 		=> $_POST['kz_event_reccurence_end_type'],
					"endValue"		=> $_POST['kz_event_reccurence_end_value']
				);
		}  
		
		Kidzou_Events::setEventDates($post_id, $start_date, $end_date, $recurrence);

	}



}
