<?php

add_action( 'kidzou_admin_loaded', array( 'Kidzou_Metaboxes_Event', 'get_instance' ) );


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
		
		//sauvegarde des meta à l'enregistrement
		add_action( 'save_post', array( $this, 'save_metaboxes' ) );

		// Load admin style sheet and JavaScript.
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_geo_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		add_action( 'add_meta_boxes', array( $this, 'event_metaboxes' ) );
	}


	/**
	 * Register and enqueue admin-specific style sheet & scripts.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_styles_scripts() {

		$screen = get_current_screen(); 

		//on a besoin de font awesome dans le paneau d'admin

		if ( in_array($screen->id , $this->screen_with_meta_event)  ) {

		
			wp_enqueue_style( 'kidzou-form', plugins_url( 'assets/css/kidzou-form.css', dirname(__FILE__) )  );

			//datepicker pour les events
			wp_enqueue_style( 'jquery-ui-custom', plugins_url( 'assets/css/jquery-ui-1.10.3.custom.min.css', dirname(__FILE__) ) );	

			wp_enqueue_script('ko',	 		"https://cdnjs.cloudflare.com/ajax/libs/knockout/3.0.0/knockout-min.js",array(), '2.2.1', true);
			wp_enqueue_script('ko-mapping',	"https://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
			
			//validation des champs du formulaire de saisie des events
			wp_enqueue_script('ko-validation',			plugins_url( 'assets/js/knockout.validation.min.js', dirname(__FILE__) ),array("ko"), '1.0', true);
			wp_enqueue_script('ko-validation-locale',	plugins_url( 'assets/js/ko-validation-locales/fr-FR.js', dirname(__FILE__) ),array("ko-validation"), '1.0', true);
			
			wp_enqueue_script('kidzou-storage', plugins_url( '../assets/js/kidzou-storage.js', dirname(__FILE__) ) ,array('jquery'), Kidzou::VERSION, true);

			//gestion des events
			wp_enqueue_script('kidzou-event-metabox', plugins_url( 'assets/js/kidzou-event-metabox.js', dirname(__FILE__) ) ,array('jquery','ko-mapping', 'moment'), Kidzou::VERSION, true);
			wp_enqueue_script('moment',			"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
			wp_enqueue_script('moment-locale',	"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-datepicker-fr', plugins_url( 'assets/js/jquery.ui.datepicker-fr.js', dirname(__FILE__) ), array('jquery-ui-datepicker'),'1.0', true);

			//insertion des metabox et initialisation des JS
			global $post;
			$event_meta 	= Kidzou_Events::getEventDates($post->ID);
		
			$start_date		= $event_meta['start_date'];
			$end_date 		= $event_meta['end_date'];
			$recurrence		= $event_meta['recurrence'];
			$past_dates		= $event_meta['past_dates'];

			$facebook_appId 	= Kidzou_Utils::get_option('fb_app_id','');
			$facebook_appSecret = Kidzou_Utils::get_option('fb_app_secret','');

			// wp_enqueue_script( 'kidzou-admin-script', plugins_url( 'assets/js/admin.js', dirname(__FILE__) ), array( 'jquery' ), Kidzou::VERSION );
			wp_localize_script('kidzou-event-metabox', 'events_jsvars', array(
					'api_getClients'				=> site_url()."/api/clients/getClients/",
					'api_getCustomerPlace'			=> site_url()."/api/clients/getCustomerPlace/",
					'api_addMediaFromURL'			=> site_url()."/api/import/addMediaFromURL/",
					'facebook_appId'				=> $facebook_appId,
					'facebook_appSecret'			=> $facebook_appSecret,
					'start_date'					=> $start_date,
					'end_date'						=> $end_date,
					'recurrence'					=> $recurrence,
					'past_dates'					=> $past_dates,
					'facebook_token'				=> '' //sera mis à jour en ajax
				)
			);



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

	public function event_metaboxes() {

		$screen = get_current_screen(); 

		if ( in_array($screen->id , $this->screen_with_meta_event) ) { 
			// add_meta_box('kz_facebook_metabox', 'Importer un &eacute;v&eacute;nement Facebook', array($this, 'facebook_event_metabox'), $screen->id, 'normal', 'high');
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
		global $post; 

		////////////////////////////////

		// Noncename needed to verify where the data originated
		wp_nonce_field( 'event_metabox', 'event_metabox_nonce' );

		echo '
		<div class="kz_form hide" id="event_form">';

			if (Kidzou_Utils::current_user_is('author')) {

				$facebook_appId 	= Kidzou_Utils::get_option('fb_app_id','');
				$facebook_appSecret = Kidzou_Utils::get_option('fb_app_secret','');

				if ($facebook_appId!='' && $facebook_appSecret!='') {

					echo '
						<h4>Importer un &eacute;v&eacute;nement Facebook</h4>
						<div data-bind="html: eventData().facebookImportMessage" style="display:inline;"></div>
						<ul>
							<li>
								<label for="facebook_url">URL de l&apos;&eacute;v&eacute;nement Facebook:</label>
						    	<input type="text" id="fb_input" placeholder="Ex : https://www.facebook.com/events/1028586230505678/"   data-bind="value: eventData().facebookUrl" /> 
							</li>
						</ul>
					';
				}

			} 

			echo '<h4>Dates de l&apos;&eacute;v&eacute;nement</h4>

			<ul>
				<li>
					<label for="start_date">Date de d&eacute;but:</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" class="date" data-bind="datepicker: eventData().start_date, datepickerOptions: { dateFormat: \'dd MM yy\' }"  /> <!-- required -->
			    	<input type="hidden" name="kz_event_start_date"  data-bind="value: eventData().formattedStartDate" />
			    	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
				</li>
				<li>
					<label for="end_date">Date de fin</label>
			    	<input type="text" placeholder="Ex : 30 Janvier" class="date" data-bind="datepicker: eventData().end_date, datepickerOptions: { dateFormat: \'dd MM yy\' }" />
					<input type="hidden" name="kz_event_end_date"  data-bind="value: eventData().formattedEndDate" />
					<em data-bind="if: eventData().eventDuration()!==\'\'">(<span data-bind="text: eventData().eventDuration"></span>)</em>
					<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
				</li>';

				if (Kidzou_Utils::current_user_is('author')) {
					echo
					'<li>
						<label for="kz_event_is_reccuring">Cet &eacute;v&eacute;nement est r&eacute;current </label>
						<input type="checkbox" name="kz_event_is_reccuring" data-bind="enable: eventData().isReccurenceEnabled, checked: eventData().recurrenceModel().isReccuring" />
					</li>';
				}

			echo '</ul>

			<!-- ko if: eventData().recurrenceModel().isReccuring -->
			<h4>R&eacute;p&eacute;tition de L&apos;&eacute;v&eacute;nement</h4>
			<ul>	
		    	<li data-bind="visible: $root.eventData().recurrenceModel().showSelectRepeat">
		    		<label for="kz_event_reccurence_mod">R&eacute;ccurence:</label>
					<select name="kz_event_reccurence_mod" data-bind="options: $root.eventData().recurrenceModel().repeatOptions,
																		optionsText: \'label\',
												                       	value: $root.eventData().recurrenceModel().selectedRepeat" ></select>
					<input type="hidden" name="kz_event_reccurence_model" data-bind="value: eventData().recurrenceModel().selectedRepeat().value" />

		    	</li>
		    	<li>
		    		<label for="kz_event_reccurence_repeat_select">R&eacute;p&eacute;ter tous les :</label>
					<select name="kz_event_reccurence_repeat_select" data-bind="options: $root.eventData().recurrenceModel().selectedRepeat().repeatEvery,
																		value: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEvery" ></select>

		    	</li>
		    	
		    	<li>
		    		<label for="kz_event_reccurence_repeat_choices">R&eacute;p&eacute;ter le :</label>
		    		<!-- ko if: $root.eventData().recurrenceModel().selectedRepeat().multipleChoice -->
			    		<span data-bind="foreach:  $root.eventData().recurrenceModel().selectedRepeat().repeatEach">
			    			<input type="checkbox" name="kz_event_reccurence_repeat_choices"  data-bind="checked: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems, checkedValue: $data" /><span data-bind="text: $data.label" style="padding-right:6px;"></span>
			    			<input type="hidden" name="kz_event_reccurence_repeat_weekly_items" data-bind="value: $root.eventData().recurrenceModel().repeatItemsValue()" />
			    		</span>
			    	<!-- /ko -->
		    		<!-- ko ifnot: $root.eventData().recurrenceModel().selectedRepeat().multipleChoice -->
			    		<span data-bind="foreach:  $root.eventData().recurrenceModel().selectedRepeat().repeatEach">
			    			<input type="radio" name="kz_event_reccurence_repeat_choices" data-bind="checked: $root.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems, checkedValue: $data" /><span data-bind="text: $data.label" style="padding-right:6px;"></span>
			    			<input type="hidden" name="kz_event_reccurence_repeat_monthly_items" data-bind="value: $root.eventData().recurrenceModel().repeatItemsValue()" />
			    		</span>
		    		<!-- /ko -->
		    	</li>

			</ul>
			<ul>	
				<li>
					<label for="kz_event_reccurence_end_type">L&apos;&eacute;v&eacute;nement prend fin :</label>
		    		<input type="radio" name="kz_event_reccurence_end_type" value="never" data-bind="checked: eventData().recurrenceModel().endType" /> never
		    	</li>
		    	<li>
		    		<label> </label>
		    		<input type="radio" name="kz_event_reccurence_end_type" value="date" data-bind="checked: eventData().recurrenceModel().endType" /> Le
		    		<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: eventData().recurrenceModel().reccurenceEndDate, datepickerOptions: { dateFormat: \'dd MM yy\' }"  /> 
			    	<input type="hidden" name="kz_event_reccurence_end_date" data-bind="value: eventData().recurrenceModel().formattedReccurenceEndDate" />
		    	</li>
			   	<li>
			   		<label> </label>
			    	<input type="radio" name="kz_event_reccurence_end_type" value="occurences" data-bind="checked: eventData().recurrenceModel().endType" /> Apr&egrave;s <input type="text" name="kz_event_reccurence_end_after_occurences" data-bind="value: eventData().recurrenceModel().occurencesNumber" /> occurences
			    </li>
			</ul>
			<ul>	
		    	<li><b>R&eacute;sum&eacute; : <span data-bind="text: eventData().recurrenceModel().recurrenceSummary()" /></b></li>
			</ul>
			<!-- /ko -->';

			if (!empty($past_dates) && count(reset($past_dates))>0)
			{
				echo '<ul><h4>Ev&eacute;nements pass&eacute;s :</h4>';
				foreach ($past_dates as  $value) {
					// Kidzou_Utils::log($value);
					$past_start_date=date_create($value['start_date']);
					$past_end_date=date_create($value['end_date']);
					echo '<li>Du '.date_format($past_start_date,"d/m/Y").' au '.date_format($past_end_date,"d/m/Y").'</li>';
				}
				echo '</ul>';

			}

		echo 
		'</div>';

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
		// Kidzou_Utils::log('save_event_meta',true);
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

		// Kidzou_Utils::log($_POST, true);

		$start_date = (isset($_POST['kz_event_start_date']) ? $_POST['kz_event_start_date'] : '');
		$end_date	= (isset($_POST['kz_event_end_date']) ? $_POST['kz_event_end_date'] : '');

		//les options de récurrence
		$recurrence = array();
		if (isset($_POST['kz_event_is_reccuring']) && $_POST['kz_event_is_reccuring']=='on')
		{
			$recurrence = array(
					"model" 		=> $_POST['kz_event_reccurence_model'],
					"repeatEach" 	=> $_POST['kz_event_reccurence_repeat_select'],
					"repeatItems" 	=> (isset($_POST['kz_event_reccurence_repeat_monthly_items']) ? $_POST['kz_event_reccurence_repeat_monthly_items'] : json_decode($_POST['kz_event_reccurence_repeat_weekly_items'])), 
					"endType" 		=> $_POST['kz_event_reccurence_end_type'],
					"endValue"		=> ($_POST['kz_event_reccurence_end_type']=='date' ? $_POST['kz_event_reccurence_end_date'] : $_POST['kz_event_reccurence_end_after_occurences'])
				);
		}  
		
		//cette metadonnée n'est pas mise à jour dans tous les cas
		//uniquement si le user est admi
		// if ( Kidzou_Utils::current_user_is('administrator') ) {
		// 	// Kidzou_Utils::log('****** featured ? '.$_POST['kz_event_featured'],true);
		// 	$featured = (isset($_POST['kz_event_featured']) && $_POST['kz_event_featured']=='on');
		// } else {
		// 	$featured = Kidzou_Featured::isFeatured($post_id);
		// 	// Kidzou_Utils::log('****** featured : '.$featured,true);	
		// }

		Kidzou_Events::setEventDates($post_id, $start_date, $end_date, $recurrence);
		// Kidzou_Featured::setFeatured($post_id, $featured);


	}



}
