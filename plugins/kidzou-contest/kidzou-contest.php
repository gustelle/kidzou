<?php

/*
Plugin Name: Kidzou Contest
Plugin URI: http://www.kidzou.fr
Description: Jeux concours sur Kidzou
Version: 2014.06.23
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

define('KIDZOU_CONTEST_VERSION', '2014.06.23');

require_once (plugin_dir_path( __FILE__ ) . '/kidzou-contest-csvexport.php'); 

//API
function add_kz_contest_controller($controllers) {
  $controllers[] = 'Contest';
  return $controllers;
}

function set_contest_controller_path() {
  return plugin_dir_path( __FILE__ ) ."/api/contest.php";
}
add_filter('json_api_controllers', 'add_kz_contest_controller');
add_filter('json_api_contest_controller_path',  'set_contest_controller_path');


if (post_type_exists('concours')=== FALSE) {
	add_action('init', 'create_concours_post_type');
}

function create_concours_post_type() {


	//ne pas faire a chaque appel de page 

	$labels = array(
	    'name'               => 'Jeux Concours',
	    'singular_name'      => 'Jeu Concours',
	    'add_new'            => 'Ajouter',
	    'add_new_item'       => 'Ajouter un concours',
	    'edit_item'          => 'Modifier le concours',
	    'new_item'           => 'Nouveau concours',
	    'all_items'          => 'Tous les concours',
	    'view_item'          => 'Voir le concours',
	    'search_items'       => 'Chercher des concours',
	    'not_found'          => 'Aucun concours trouvé',
	    'not_found_in_trash' => 'Aucun concours trouvé dans la corbeille',
	    'menu_name'          => 'Jeux concours',
	  );

	  $args = array(
	    'labels'             => $labels,
	    'public'             => true,
	    'publicly_queryable' => true,
	    'show_ui'            => true,
	    'show_in_menu'       => true,
	    'menu_position' 	 => 5, //sous les articles dans le menu
	    'menu_icon'			=> 'dashicons-awards',
	    'query_var'          => true,
	    'has_archive'        => true,
	    'rewrite' 			=> array('slug' => 'concours'),
	    'hierarchical'       => false, //pas de hierarchie d'offres
	    'supports' 			=> array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'custom-fields', 'revisions', 'post-formats'),
	    'taxonomies' 		=> array('age', 'ville', 'divers', 'category'), //reuse the taxo declared in kidzou plugin
	  );

  register_post_type( 'concours', $args );

  flush_rewrite_rules();

}

add_filter( 'default_content', 'kz_concours_editor_content' );

/**
 * permet de structurer l'edition d'une offre
 * 
 * @see http://www.smashingmagazine.com/2011/10/14/advanced-layout-templates-in-wordpress-content-editor/
 * @return void
 * @author 
 **/
function kz_concours_editor_content( $content ) {

	global $current_screen;
	if ( $current_screen->post_type == 'concours' ) {
			
		$content = '
		  	[one_half] Image [/one_half]
		  	[one_half_last]
		  		[box type="bio"]<strong>Titre du jeu concours</strong><br/>
				Lorem ipsum
				[/box]
			[/one_half_last]

			[one_half]

				<strong>sous-titre</strong><br/>

				description

			[/one_half]

			[one_half_last]
				Explications des regles du concours

			[/one_half_last]

			[form]

				[input required="true"] La r&eacute;ponse &agrave; cette question est obligatoire[/input]

				[input type="radio" value="male"  champ="sexe" ] Homme[/input]

				[input type="radio" value="female" champ="sexe" ] Femme[/input]

				[input type="email" champ="email" required="true"] Votre mail[/input]

				[input type="textarea"  champ="Adresse"] Votre adresse[/input]

			[/form]
		';
	}
	return $content;
}

/**
 * utilisation des JS lors de la saisie des events (sur la page d'eition d'un event)
 *
 * @return void
 * @author 
 **/
add_action( 'admin_enqueue_scripts', 'kz_enqueue_contest_scripts' );

function kz_enqueue_contest_scripts() {

	$screen = get_current_screen();

	//si on est entrain d'éditer un post de type event...
	if ($screen->post_type == 'concours' ) {

		wp_enqueue_script('ko',	 		"http://cdnjs.cloudflare.com/ajax/libs/knockout/2.2.1/knockout-min.js",array(), '2.2.1', true);
		wp_enqueue_script('ko-mapping',	"http://cdnjs.cloudflare.com/ajax/libs/knockout.mapping/2.3.5/knockout.mapping.js",array("ko"), '2.3.5', true);
		wp_enqueue_script('ko-validation',			WP_PLUGIN_URL.'/kidzou/js/admin/knockout.validation.min.js',array("ko"), '1.0', true);
		wp_enqueue_script('ko-validation-locale',	WP_PLUGIN_URL.'/kidzou/js/admin/ko-validation-locales/fr-FR.js',array("ko-validation"), '1.0', true);
				
		//utilisé pour le formattage des dates
		wp_enqueue_script('moment',			"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/moment.min.js",	array('jquery'), '2.4.0', true);
		wp_enqueue_script('moment-locale',	"http://cdnjs.cloudflare.com/ajax/libs/moment.js/2.4.0/lang/fr.js",	array('moment'), '2.4.0', true);

		//datepicker
		wp_enqueue_style( 'jquery-ui-custom', WP_PLUGIN_URL."/kidzou/css/jquery-ui-1.10.3.custom.min.css" );	
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker');

		//mettre cette ressource en shared dans kidzou
		wp_enqueue_script('jquery-ui-datepicker-fr', WP_PLUGIN_URL.'/kidzou/js/jquery.ui.datepicker-fr.js', array('jquery-ui-datepicker'),'1.0', true);

		//necessaire pour le people picker
		wp_enqueue_script('jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.min.js",array('jquery'), '1.0', true);
		wp_enqueue_script('jquery-select2-locale', 	"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2_locale_fr.min.js",array('jquery-select2'), '1.0', true);
		wp_enqueue_style( 'jquery-select2', 		"http://cdnjs.cloudflare.com/ajax/libs/select2/3.4.4/select2.css" );

		//editeur kidzou
		wp_enqueue_script('kidzou-contest', WP_PLUGIN_URL."/kidzou-contest/js/kidzou-edit-contest.js" ,array('jquery', 'ko', 'ko-validation', 'jquery-select2'), KIDZOU_CONTEST_VERSION, true);

		wp_localize_script('kidzou-contest', 'kidzou_contest_jsvars', array(
			'api_contest_get_participants'	 	=> site_url().'/api/contest/get_participants/',
		)
	);
	} 
	
}

add_action( 'add_meta_boxes', 'kz_contest_add_meta_box' );
add_action( 'delete_post', 'kz_delete_concours' );

/**
 * on garde une trace de sa participation au concours, même s'il n'existe plus...
 *
 * @return void
 * @author 
 **/
function kz_delete_concours ()
{
	// global $post; 

	// $user_query = new WP_User_Query(
	// 		array(
	// 			'meta_key'	  	=>	'kz_contest_'.$post_id,
	// 			'meta_value'	=>	'',
	// 			'meta_compare'	=> 	'EXISTS'
	// 		)
	// 	);

	// // Get the results from the query, returning the first user
	// $participants = $user_query->get_results();

	// if (is_array($participants))
	// {
	// 	//il y a eu des participants à ce concours
	// 	//y'a du boulot pour mettre d'équerre
	// 	foreach ($participants as $a_participant) {

	// 		$a_participant_id = $a_participant->ID;
			
	// 		//si le participant n'est pas winner
	// 		// if (!in_array($a_participant_id, $winners_id)) {
				
	// 			//parcourir les winners du user et supprimer ce concours s'il y était
	// 			$a_participant_winners = get_user_meta( $a_participant_id, 'kz_contests_winners', TRUE );
				
	// 			if (is_array($a_participant_winners)) {
	// 				$i=0;
	// 				foreach ($a_participant_winners as $a_participant_a_winner) {

	// 					//si le post est repéré dans la liste des concours gagnants du user
	// 					//on le supprime
	// 					if (intval($a_participant_a_winner) == $post_id) {

	// 						unset($a_participant_winners[$i]);
	// 						if (count($a_participant_winners)==0)
	// 							delete_user_meta($a_participant_id, 'kz_contests_winners');
	// 						else
	// 							update_user_meta( $a_participant_id, 'kz_contests_winners' , $a_participant_winners );
	// 					}
	// 					$i++;
	// 				}
	// 			} else {
	// 				//inconsistence
	// 				delete_user_meta($a_participant_id, 'kz_contests_winners');
	// 			}

	// 			//parcourir tous les concours auxquels il a participé et supprimer ce concours
	// 		// }
	// 	}

	// }

}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_contest_add_meta_box()
{
	add_meta_box('kz_contest_metabox', 'Jeu concours', 'kz_contest_metabox', 'concours', 'normal', 'high');
}


/**
 * contient date de fermeture du concours, est-ce qu'il faut avoir la carte famille, et la liste des joueurs qui ont répondu
 *
 * @return void
 * @author 
 **/
function kz_contest_metabox( )
{

	global $post; 
	
	// Noncename needed to verify where the data originated
	echo '<input type="hidden" name="contestmeta_noncename" id="contestmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
	
	$end_date 		= get_post_meta($post->ID, 'kz_contest_end_date', TRUE);
	$restricted 	= kz_is_contest_restricted($post->ID);
	$winners 		= get_post_meta($post->ID, 'kz_contest_winners', TRUE);
	$winners_list 	= '';

	if (is_array($winners) && count($winners)>0)
	{
		foreach ($winners as $winner) {
			# liste d'ID
			$winner_o = get_user_by( 'id', $winner );
			if ($winners_list!='') $winners_list .=',';
			$winners_list .= $winner_o->ID.':'.$winner_o->user_login.':'.$winner_o->user_email;
		}
	}

	echo '<script>
	jQuery(document).ready(function() {
		kidzouContestModule.model.initDates("'.$end_date.'");
		kidzouContestModule.model.selectedWinners("'.$winners_list.'"); //initialisation de la select des winners
	});
	</script>';

	?>
	<div class="kz_form" id="contest_form">

		<ul>
			<li>
				<label for="kz_contest_restricted">Restriction aux propri&eacute;taires de la carte famille ?</label>
		    	<input type="checkbox" value="1" name="kz_contest_restricted" <?php echo ($restricted ? 'checked="checked"' : ''); ?> />
			</li>
			<li>
				<label for="end_date">Date de fin</label>
		    	<input type="text" placeholder="Ex : 30 Janvier" data-bind="datepicker: contestData().end_date, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
				<input type="hidden" name="kz_contest_end_date"  data-bind="value: contestData().formattedEndDate" />
				<span data-bind="validationMessage: contestData().formattedEndDate" class="form_hint"></span>
			</li>
			<li>
				Nombre de Participants : 
		    	<?php
		    	$users = get_post_meta( $post->ID, 'kz_contest_users', TRUE );
		    	if (is_array($users))
		    		echo count($users).' ( <a href="'.site_url().'/wp-admin/admin.php?page=kidzou-contest-csvexport.php&download_report&post_id='.$post->ID.'">Export des donn&eacute;es du Jeu Concours</a> ) ';
		    	else
		    		echo '<em>Aucun participant</em>';
		    	?>
			</li>
			<li>
				<label>Gagnants: </label>
				<input type="hidden" name="kz_contest_winners" data-bind="value: $root.selectedWinners, select2: { multiple: true, 
							            																			minimumInputLength: 2, 
							            																			id : $root.selectedWinnerId, 
							            																			query: $root.queryWinners, 
							            																			initSelection: $root.initSelectedWinners, 
							            																			formatResult : $root.formatWinner, 
							            																			formatSelection : $root.formatWinner }" style="width: 25em;">
			</li>
		</ul>

	</div>

	<?php
}

do_action( 'save_post_concours', 'kz_save_concours_meta' );

/**
 * undocumented function
 *
 * @return true si l'événement est en cours, false si il est terminé ou pas visible
 * @author 
 **/
function  kz_is_concours()
{
	global $post;
	$type = get_post_type($post);
	return $type=='concours';
}

add_action( 'save_post_concours', 'kz_save_concours_meta' );

/**
 * 
 *
 * @return void
 * @author 
 **/
function kz_save_concours_meta($post_id)
{

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['contestmeta_noncename'], plugin_basename(__FILE__) )) {
		return $post_id;
	}
	// Is the user allowed to edit the post or page?
	if ( !current_user_can( 'edit_post', $post_id ))
		return $post_id;

	$contest_meta['kz_contest_restricted']	= $_POST['kz_contest_restricted'];
	$contest_meta['kz_contest_end_date']	= $_POST['kz_contest_end_date'];

	$winners = explode(",", $_POST['kz_contest_winners'] );
	$winners_id = array();

	if (count($winners)>0)
	{
		foreach ($winners as $winner) {
			$pieces = explode(":", $winner);
			if ($winner!='' && intval($winner)>0)
				array_push($winners_id, intval($pieces[0]));
		}
	}


	$contest_meta['kz_contest_winners']		= $winners_id;

	//on sauvegarde également les concours que les winners ont gagné dans les user meta
	foreach ($winners_id as $winner) {

		//liste des concours que le winner a déjà gagné
		//cette meta est réutilisée dans la vue "profil" du user
		$contests_winners = get_user_meta( $winner, 'kz_contests_winners', TRUE ); 
		if (is_array($contests_winners))
		{
			if (!in_array($post_id, $contests_winners))
				array_push($contests_winners, $post_id);
		}
		else 
		{
			$contests_winners = array(0 => $post_id);
		}
		update_user_meta( $winner, 'kz_contests_winners' , $contests_winners ); //liste des concours par user
	}

	// -----------------------------------------

	//boucle inverse pour mettre en cohérence:
	//si le user n'est plus dans la liste des $winners_id, parcourir le tableau des users qui ont un kz_contests_winners et supprimer la valeur post_id
	$user_query = new WP_User_Query(
			array(
				'meta_key'	  	=>	'kz_contest_'.$post_id,
				'meta_value'	=>	'',
				'meta_compare'	=> 	'EXISTS'
			)
		);

	// Get the results from the query, returning the first user
	$participants = $user_query->get_results();

	if ( is_array($participants) )  //&& count($former_winners)!=count($winners_id) 
	{
		
		foreach ($participants as $a_participant) {

			$a_participant_id = $a_participant->ID;
			
			//si le participant n'est pas winner
			if (!in_array($a_participant_id, $winners_id)) {
				
				//parcourir la meta du user 
				$a_participant_winners = get_user_meta( $a_participant_id, 'kz_contests_winners', TRUE );
				
				if (is_array($a_participant_winners)) {
					$i=0;
					foreach ($a_participant_winners as $a_participant_a_winner) {

						//si le post est repéré dans la liste des concours gagnants du user
						//on le supprime
						if (intval($a_participant_a_winner) == $post_id) {

							unset($a_participant_winners[$i]);
							if (count($a_participant_winners)==0)
								delete_user_meta($a_participant_id, 'kz_contests_winners');
							else
								update_user_meta( $a_participant_id, 'kz_contests_winners' , $a_participant_winners );
						}
						$i++;
					}
				} else {
					//inconsistence
					delete_user_meta($a_participant_id, 'kz_contests_winners');
				}
			}
		}
	}

	// -----------------------------------------
	
	// Add values of $events_meta as custom fields
	foreach ($contest_meta as $key => $value) { // Cycle through the $events_meta array!
		// if( $post->post_type == 'revision' ) return; // Don't store custom data twice
		// $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
		$prev = get_post_meta($post_id, $key, TRUE);
		if($prev && $prev!='') { // If the custom field already has a value
			update_post_meta($post_id, $key, $value, $prev);
		} else { // If the custom field doesn't have a value
			if ($prev=='') delete_post_meta($post_id, $key);
			add_post_meta($post_id, $key, $value, TRUE);
		}
		if(!$value || $value='' || ( is_array($value) && empty($value))) delete_post_meta($post_id, $key); // Delete if blank
	}
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_save_concours_metropole($post_id)
{
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    
    if ( current_user_can( 'manage_options', $post_id )) {

    
    } else {

    	if (function_exists('kz_set_post_user_metropole'))
    		kz_set_post_user_metropole($post_id);
    } 

    
}

add_action('wp_enqueue_scripts', 'add_kz_concours_assets');
/**
 * attache les assets CSS et JS à la page si pas déjà fait
 *
 * @return void
 * @author 
 **/
function add_kz_concours_assets()
{
	if (!wp_style_is( 'kidzou-form', 'enqueued' ) )
			// wp_enqueue_style( 'kidzou-form', WP_PLUGIN_URL."/kidzou/css/kidzou-form.css" );

	if (!wp_script_is( 'kidzou-contest', 'enqueued' ) ) {
		
		wp_register_script( 'kidzou-contest', plugin_dir_url(__FILE__).'js/kidzou-contest.js', array("jquery"), KIDZOU_CONTEST_VERSION, TRUE);
       	wp_enqueue_script( 'kidzou-contest' );
       	
       	wp_localize_script('kidzou-contest', 'kidzou_contest_jsvars', array(
				'api_contest_participate'	=> site_url().'/api/contest/participate',
				'api_get_nonce'				=> site_url().'/api/get_nonce/',
			)
		);
	}
}


add_shortcode('form', 	'kz_form_shortcode');
add_shortcode('input', 	'kz_input_shortcode');


/**
 * [form class="dd" action='/toto' title='Rempli moi ca illico !']
 *
 * @return 
 * @author 
 * @see http://webdesign.tutsplus.com/tutorials/bring-your-forms-up-to-date-with-css3-and-html5-validation--webdesign-4738
 **/
function kz_form_shortcode( $params, $content = null ) {

	global $post;

	$post_id = $post->ID;

	$display = false;

	//restreint aux membres qui ont la carte famille
	$restricted = kz_is_contest_restricted();
	$open = kz_is_contest_open();
	$family = kz_user_has_family_card();

	if ( $open &&  $family)
		$display = true;

	//l'utilisateur doit être connecté
	else if ($open && !$restricted && is_user_logged_in())
		$display = true;

	if ($display)
	{
		extract(shortcode_atts(array(
			'class' => 'kz_form',
			'title'	=> 'Remplissez le formulaire ci-dessous pour participer'
		), $params));

		return "<hr/><br/><form id='contest' class='".$class."'><input type='hidden' name='post_id' value='".$post_id."'/><ul><li><h2>".$title."</h2><span class='kz_form_required_notification'>* Les ast&eacute;risques indiquent un champ obligatoire</span></li><li><span id='contestMessage'></span></li>".do_shortcode($content)."<li><button type='submit'>Participer</button></li></ul></form>";

	} else if ($open) {

		if ($restricted) {

			$phrase = 'Ce concours est ouvert uniquement aux membres Kidzou ayant la carte famille.';
			if (!is_user_logged_in())
				$phrase .= '<br/><em>Si vous poss&eacute;dez la carte famille, <a href="#" class="login">connectez-vous pour participer &agrave; ce jeu concours !</a></em>';
			else
				$phrase .= '<a href="http://www.kidzou.fr/la-carte-famille-kidzou/">En savoir plus sur la carte famille Kidzou</a>';

			return do_shortcode('[box type="info"]'.$phrase.'[/box]');
		}
		else if (!is_user_logged_in())
			return do_shortcode('[box type="info"] Connectez-vous pour participer &agrave; ce jeu concours.<br/><a href="#" class="login">Pour vous connecter, suivez ce lien !</a>[/box]');		
		else
			return do_shortcode('[box type="info"] Ce concours est ouvert uniquement aux membres Kidzou ayant la carte famille, <a href="http://www.kidzou.fr/la-carte-famille-kidzou/">En savoir plus sur la carte famille Kidzou</a></em>[/box]');

	} else {

		//not open
		return do_shortcode('[box type="warning"] Ce concours est termin&eacute;! [/box]');
	}

}


/**
 * [input type="radio"]libellé[/input]
 *
 * @return 
 * @author 
 * @see http://webdesign.tutsplus.com/tutorials/bring-your-forms-up-to-date-with-css3-and-html5-validation--webdesign-4738
 **/
function kz_input_shortcode( $params, $content = null ) {

	//recuperer les data en base pour le user courant:
	global $post;

	$post_id = $post->ID;

	$current_user = wp_get_current_user();

	$umeta = get_user_meta($current_user->ID, 'kz_contest_'.$post_id, TRUE);

	extract(shortcode_atts(array(
		'type' => 'text',
		'value'	=> '',
		'required' => 'false',
		'champ' 	=> '', //nom du champ a stocker en metadata du user
	), $params)); 

	$input = '';

	if ($champ=='')
		$champ = $content;

	$champ = str_replace(' ', '_', $champ); // Replaces all spaces with hyphens.
    $champ = preg_replace('/[^A-Za-z0-9\-]/', '', $champ); // Removes special chars.

    $name = $champ; //le nom du champ en base est transmis comme le nom du input transmis dans la requete 

    $checked = "";

    //le user avait déjà répondu au questionnaire, on reprend ses données
	if ($umeta!='')
	{
		foreach ($umeta as $m) {

			$checkable = ($type=='radio' || $type=='checkbox');

			if ($m["name"]==$name && !$checkable)
				$value = $m["value"];
			else if ($m["name"]==$name && $checkable && $value==$m["value"])
				$checked = " checked='checked' ";
		}
	}

	switch ($type) {
		case 'radio':
			$input = "<label>&nbsp;</label><input type='radio' value='".$value."' name='".$name."' ".($required=='true' ? 'required' : '').$checked."/>".$content.($required=='true' ? '<span class="kz_form_hint">Ce champ est obligatoire</span>' : '');
			break;

		case 'textarea':
			$input = "<label for='".$name."'>".$content."</label><textarea cols='40' rows='6' name='".$name."' ".($required=='true' ? 'required' : '').">".$value."</textarea>".($required=='true' ? '<span class="kz_form_hint">Ce champ est obligatoire</span>' : '');
			break;

		case 'text':
			$input = "<label for='".$name."'>".$content."</label><input type='text' value='".$value."' name='".$name."' ".($required=='true' ? 'required' : '')."/>".($required=='true' ? '<span class="kz_form_hint">Ce champ est obligatoire</span>' : '');
			break;

		case 'email':
			$input = "<label for='".$name."'>".$content."</label><input type='email' value='".$value."' pattern='^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$' name='".$name."' ".($required=='true' ? 'required' : '')."/>".($required=='true' ? '<span class="kz_form_hint">Email au format contact@kidzou.fr</span>' : '');
			break;

		case 'checkbox':
			$input = "<label>&nbsp;</label><input type='checkbox' value='".$value."' name='".$name."' ".($required=='true' ? 'required' : '').$checked."/>".$content.($required=='true' ? '<span class="kz_form_hint">Ce champ est obligatoire</span>' : '');
			break;

		default:
			$input = "<label for='".$name."'>".$content."</label><input type='text' value='".$value."' name='".$name."' ".($required=='true' ? 'required' : '')."/>".($required=='true' ? '<span class="kz_form_hint">Ce champ est obligatoire</span>' : '');
			break;
	}

	return "<li>".$input."</li>";
}


/**
 * Removes mismatched </p> and <p> tags from a string
 * 
 * @author https://plus.google.com/114591575651122645032?rel=author
 * @see http://www.wpexplorer.com/clean-up-wordpress-shortcode-formatting/
 */
if( !function_exists('wpex_fix_shortcodes') ) {
	function wpex_fix_shortcodes($content){   
		$array = array (
			'<p>[' => '[', 
			']</p>' => ']', 
			']<br />' => ']'
		);
		$content = strtr($content, $array);
		return $content;
	}
	add_filter('the_content', 'wpex_fix_shortcodes');
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_is_contest_restricted($post_id=0)
{
	if ($post_id==0)
	{
		global $post;
		$post_id = $post->ID;
	}

	$meta = get_post_meta( $post_id, 'kz_contest_restricted', TRUE );

	if ($meta!='' && intval($meta)==1)
		return TRUE;

	return FALSE;

	// return $restricted!='' && intval($restricted)==1;
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_is_contest_open($post_id=0)
{
	if ($post_id==0)
	{
		global $post;
		$post_id = $post->ID;
	}
	
	$end = get_post_meta( $post_id, 'kz_contest_end_date', TRUE );

	return (strtotime($end) > strtotime('now')); 

}

?>