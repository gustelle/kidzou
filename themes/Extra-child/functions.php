<?php

/**
 * Le thème hérite des CSS du parent
 *
 */
add_action( 'wp_enqueue_scripts', 'extra_child_enqueue_styles' );
function extra_child_enqueue_styles() {
	$parent_style = 'extra-parent-style';
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'extra-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );

    //ajout de font-awesome pour les icones sur les adresse et dates
    wp_enqueue_style( 'font-awesome',
        is_ssl() ? 'https://netdna.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.css' : 'http://netdna.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.css',
        array(),
        '4.6.3'
    );

    //sur les single, affichage d'une carte google maps (non prévu dans le thème)
    if (is_single() && Kidzou_Geoloc::has_post_location()) {
        wp_enqueue_script( 'google-maps-api', esc_url( add_query_arg( array( 'key' => et_pb_get_google_api_key(), 'callback' => 'initMap' ), is_ssl() ? 'https://maps.googleapis.com/maps/api/js' : 'http://maps.googleapis.com/maps/api/js' ) ), array(), '3', true );
    }
    
}

/**
 * quelques CSS complémentaires dans le thème enfant sur l'admin
 *
 */
add_action( 'admin_enqueue_scripts', 'extra_child_admin_enqueue_styles' );
function extra_child_admin_enqueue_styles() {
    wp_enqueue_style( 'extra-child-style', get_stylesheet_directory_uri() . '/includes/builder/styles/style.css');
}


/**
 * modules.php étend des classes PHP définies dans le thème parent
 * celui-ci doit donc être chargé d'abord 
 *
 */
add_action( 'admin_init', 'after_init', PHP_INT_MAX);
add_action( 'wp', 'after_init', PHP_INT_MAX);
function after_init() {

	/**
	 * Compléter le Extra Category Builder avec les Events de Kidzou
	 */
	$stylesheet_directory = get_stylesheet_directory();
	require_once $stylesheet_directory.'/includes/modules.php';
}

/**
 * Suppression du Post Type 'Projects' inutilisé dans notre thème
 *
 **/
add_action('init', 'unregister_projects_post_type', PHP_INT_MAX);
function unregister_projects_post_type(){
    unregister_post_type( 'project' );
}


/**
 * Une string formattée avec les dates d'événement.
 * Typiquement "Du ... au ..." ou alors "Le ..."
 *
 * @param style 'long' or 'short' 
 * 
 */
function the_event_dates($style='long') {

    $dates = Kidzou_Events::getEventDates();
    $start  = DateTime::createFromFormat('Y-m-d H:i:s', $dates['start_date'], new DateTimeZone('Europe/Paris'));
    $end    = DateTime::createFromFormat('Y-m-d H:i:s', $dates['end_date'], new DateTimeZone('Europe/Paris'));

    $formatter = new IntlDateFormatter('fr_FR',
                                        IntlDateFormatter::SHORT,
                                        IntlDateFormatter::NONE,
                                        'Europe/Paris',
                                        IntlDateFormatter::GREGORIAN,
                                        'dd/MM/yyyy');

    if ($style=='long')
        $formatter->setPattern('cccc dd LLLL');
    else 
        $formatter->setPattern('dd LLL');

    if ($start->format("Y-m-d") == $end->format("Y-m-d")) {
        echo sprintf('<p><i class="fa fa-calendar fa-fw"></i> Le %1$s</p>',
                        $formatter->format($start));
    } else {
        echo sprintf('<p><i class="fa fa-calendar fa-fw"></i> Du %1$s au %2$s</p>',
                        $formatter->format($start),
                        $formatter->format($end));
    }
}


?>