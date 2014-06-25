<?php

/*
Plugin Name: Kidzou Geo
Plugin URI: http://www.kidzou.fr
Description: Solutions de geolocalisation pour Kidzou (Map, Google Places, Geoloc des contenus)
Version: 2014.06.23
Author: Kidzou
Author URI: http://www.kidzou.fr
License: LGPL
*/

define('KIDZOU_GEO_VERSION', '2014.06.23');

require_once (plugin_dir_path( __FILE__ ) . '/kidzou-google-place.php');
require_once (plugin_dir_path( __FILE__ ) . '/kidzou-mapquest.php');


//utilisée à la fois dans l'admin et dans le front, wp_localize_script est isolée ici et pas mutualisée avec kidzou-enequeue


function add_kz_geo_scripts() {

	$villes = kz_covered_metropoles();

    $mapques_key = "Fmjtd%7Cluur2qubnu%2C7a%3Do5-9aanq6";

	$args = array(
				'geo_mapquest_key'			=> $mapques_key,
				'geo_mapquest_reverse_url'	=> "http://www.mapquestapi.com/geocoding/v1/reverse",
				'geo_mapquest_address_url'	=> "http://www.mapquestapi.com/geocoding/v1/address",
				'geo_default_lat'			=> 50.637234, //lille
				'geo_default_lng' 			=> 3.06339, //lille
				'geo_default_metropole'		=> kz_default_metropole(),
				'geo_cookie_name'			=> "kz_metropole",
				'geo_select_cookie_name'	=> "kz_metropole_selected",
				'geo_possible_metropoles'	=> $villes ,
                'geo_icon_url'              => WP_PLUGIN_URL.'/kidzou-geo/images/location_icon2.png'
			);

    wp_enqueue_script('kidzou-geo',     WP_PLUGIN_URL.'/kidzou-geo/js/kidzou-geo.js', array('kidzou'), KIDZOU_GEO_VERSION, true);

    wp_localize_script('kidzou-geo', 'kidzou_geo_jsvars', $args );

    if (is_single() || is_archive() || is_tax() || is_search() || is_page('agenda')) {

        //Besoin de cela en plus pour generer une map sur les posts
        //a mettre dans le header
        wp_enqueue_script('mapquest-map',"http://open.mapquestapi.com/sdk/js/v7.0.s/mqa.toolkit.js?key=" . $mapques_key, array(), KIDZOU_GEO_VERSION, false);
        // wp_enqueue_script('mapquest-mapinit', WP_PLUGIN_URL.'/kidzou/js/front/mapquest-mapinit.js' , array(), KIDZOU_VERSION, false);
	}

	
}

//filtrage du contenu par geoloc
// global $metropole;

add_action( 'pre_get_posts', 'kz_geo_filter_query', 100 );
function kz_geo_filter_query( $query ) {

	// $type = get_query_var('post_type');

	//les pages n'ont pas de taxo "ville", il faut les exclure du filtre
    if( !is_admin() && !is_search() ) {

        $the_metropole = array(kz_get_request_metropole());
        $national = (array)kz_get_national_metropoles(); //array

        //@see http://tommcfarlin.com/pre_get_posts-in-wordpress/
        set_query_var('tax_query', array(
            array(
                  'taxonomy' => 'ville',
                  'field' => 'slug',
                  'terms' => array_merge( $the_metropole, $national)
                )
            )
        );
        
        return $query;
    }
}


//custom permalink structure
add_action('init', 'kz_geo_rewrite', 100); 
function kz_geo_rewrite()
{

	global $wp_rewrite;

	add_rewrite_tag('%kz_metropole%','(valenciennes|amiens|lille|Lille)', 'kz_metropole=');

	$wp_rewrite->add_rule('(valenciennes|amiens|lille|Lille)$','index.php?kz_metropole=$matches[1]','top'); //home
	$wp_rewrite->add_rule('(valenciennes|amiens|lille|Lille)/agenda$','index.php?pagename=agenda&kz_metropole=$matches[1]','top'); //agenda

    // // Add Offre archive (and pagination)
    // //see http://code.tutsplus.com/tutorials/the-rewrite-api-post-types-taxonomies--wp-25488
    add_rewrite_rule("(valenciennes|amiens|lille|Lille)/offres/page/?([0-9]{1,})/?",'index.php?post_type=offres&paged=$matches[2]&kz_metropole=$matches[1]','top');
    add_rewrite_rule("(valenciennes|amiens|lille|Lille)/offres/?",'index.php?post_type=offres&kz_metropole=$matches[1]','top');


	$wp_rewrite->flush_rules();

}

function kz_geo_filter_link( $permalink, $post ) {
    
    // Check if the %kz_metropole% tag is present in the url:
    if ( false === strpos( $permalink, '%kz_metropole%' ) )
        return $permalink;
 
    $m = urlencode(kz_get_request_metropole());
 
    // Replace '%kz_metropole%'
    $permalink = str_replace( '%kz_metropole%', $m , $permalink );
 
    return $permalink;
}
add_filter( 'post_link', 'kz_geo_filter_link' , 10, 2 );

function kz_geo_filter_page( $link, $param ) {
    
    //on ne re-ecrit que l'agenda, car c'est la seule page (pour l'instant) dont le contenu
    //depend de la metropole
    if ( false === strpos( $link, '/agenda' ) )
    	return $link;

    $m = urlencode(kz_get_request_metropole());

    $pos = strpos( $link, '/agenda' );

    $new_link = substr_replace($link, "/".$m, $pos, 0);
 
    return $new_link;
}
add_filter( 'page_link', 'kz_geo_filter_page' , 10, 2 );

function kz_geo_term_filter_link( $url, $term, $taxonomy ) {

	// Check if the %kz_metropole% tag is present in the url:
    if ( false === strpos( $url, '%kz_metropole%' ) )
        return $url;
 
    $m = urlencode(kz_get_request_metropole());
 
    // Replace '%kz_metropole%'
    $url = str_replace( '%kz_metropole%', $m , $url );
 
    return $url; 
}
add_filter( 'term_link', 'kz_geo_term_filter_link', 10, 3 );

// /**
// *
// * les contenu des archives des Offres et Jeux concours dépendent des villes
// * 
// */
// function kz_post_type_filter_link($post_link, $id = 0, $leavename = FALSE) {

//     if ( strpos($post_link, '%kz_metropole%' ) === FALSE  ) 
//       return $post_link;
    
//     $m = urlencode(kz_get_request_metropole());
//     return str_replace('%kz_metropole%', $m, $post_link);
// }
// add_filter( 'post_type_link', 'kz_post_type_filter_link', 10, 3 );

/**
 * lien vers la racine du site (à utiliser à la place de home_url())
 *
 * @return void
 * @author 
 **/
function kz_geo_home_url( ) {

  return get_site_url().'/'.kz_get_request_metropole();

}
// add_filter( 'site_url', 'kz_geo_home_url', 1 );

/**
 * la metropole de rattachement de la requete
 *
 * @return String (slug)
 * @author 
 **/
function kz_get_request_metropole()
{

    $cook_m = strtolower($_GET['kz_metropole']);

    $isCovered = kz_is_metropole_covered($cook_m);

    if (!$isCovered)
        return kz_default_metropole();
    else
        return $cook_m;
}

/**
 * la metropole du post courant
 * si rattaché à plusieurs metropoles (national, lille...) on prend la metropole qui dispose du meta kz_national_ville
 * si aucune metropole ne dispose de cette meta, on prend la premiere de la liste
 *
 * @return Object
 * @author 
 **/
function kz_get_post_metropole( )
{
    global $post;

    $result = get_transient('kz_post_metropole_'. $post->ID ); 

    if (false===$result)
    {

        $terms = wp_get_post_terms( $post->ID, 'ville');

        $roots = array();

        foreach ($terms as $key => $value){
            //get top level parent
            $ancestors = get_ancestors( $value->term_id, 'ville' );
            
            if (count($ancestors)==0) {
                //le terme est déjà à la racine
                array_push($roots, $value);
            
            } else {

                foreach ($ancestors as $ancestor){
                    $ville = get_term_by('id', (int)$ancestor, 'ville');
                    if ($ville->parent == 0) {
                        array_push($roots, $ville);
                    }
                }
            }
            
        }

        //si le post est rattaché à plus d'une metropole
        if (count($roots)>1) {
            $i=0;
            $save_me = $roots[$i];
            foreach ($roots as $root) {
                if (get_tax_meta($root->term_id,'kz_national_ville')!="on") {
                    unset($roots[$i]);
                } else {
                    $save_me = $roots[$i];
                }
                $i++;
            }
            $roots[0] = $save_me;
        }

        $result = $roots[0];

        set_transient( 'kz_post_metropole_'. $post->ID , $result, 60 * 60 * 24 ); //1 jour de cache
        
    }
    
    return $result;
}


/**
 * retourne les ID des villes couvertes
 *
 * @return array(id=>slug)
 * @author 
 **/
function kz_covered_metropoles()
{        
    $result = get_transient('kz_covered_metropoles');

    if (false===$result)
    {

        $villes = get_terms( array("ville"), array(
                "orderby" => "slug",
                "parent" => 0, //only root terms,
                "fields" => "id=>slug"
            ) );

        $exclude_national = array();

        //sortir les villes à couverture nationale
        //on prend le premier de la liste
        foreach ($villes as $key=>$value) {
            $def = get_tax_meta($key,'kz_national_ville');
            if ("on" ==  $def) {
            } else {
                $exclude_national[$key] = $value;
            }
        }   

        $result = (array)$exclude_national;

        set_transient( 'kz_covered_metropoles', (array)$result, 60 * 60 * 24 ); //1 jour de cache
    }

    return $result;
}

/**
 * retourne les villes couvertes avec tous leurs attributs
 *
 * @return void
 * @author 
 **/
function kz_covered_metropoles_all_fields()
{

    $result = get_transient('kz_covered_metropoles_all_fields');

    if (false===$result)
    {
        $villes = get_terms( array("ville"), array(
            "orderby" => "slug",
            "parent" => 0, //only root terms,
            "fields" => "all"
        ) );

        $result  = array();

        //sortir les villes à couverture nationale
        //on prend le premier de la liste
        foreach ($villes as $key=>$value) {
            $def = get_tax_meta($value->term_id,'kz_national_ville');
            if ("on" ==  $def) {
            } else {
                $result[$key] = $value;
            }
        }   

        set_transient( 'kz_covered_metropoles_all_fields', (array)$result, 60 * 60 * 24 ); //1 jour de cache
    }

    return $result;
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_is_metropole_covered($m)
{

    if ($m==null || $m=="")
        return false;

    //la ville du user est-elle couverte par Kidzou
    $villes  = kz_covered_metropoles();
    $isCovered = false;
    foreach ($villes as $key => $value) {
        if (is_string($value) && $m==strtolower($value))
            $isCovered = true;
    }

    return $isCovered;
}

/**
 * undocumented function
 *
 * @return void
 * @author 
 **/
function kz_default_metropole()
{

    $result = get_transient('kz_default_metropole');

    if (false===$result)
    {
        $villes = get_terms( array("ville"), array(
                    "orderby" => "count",
                    "parent" => 0, //only root terms,
                    "fields" => "ids"
                ) );

        //on prend le premier de la liste
        foreach ($villes as $key) {
            $def = get_tax_meta($key,'kz_default_ville');
            if ("on" ==  $def) {
                $the_term = get_term_by('id', $key, 'ville');
                $result = $the_term->slug;
                break;
            }
        }   

        set_transient( 'kz_default_metropole', $result, 60 * 60 * 24 ); //1 jour de cache
    }
    
    return $result;
}

/**
 * retourne un tableau de villes à portée nationale
 * les villes à portée nationale ont pour vocation de porter des articles à portée nationale 
 * Les villes à portée nationale doivent être à la racine 
 *
 * @return array
 * @author 
 **/
function kz_get_national_metropoles()
{


    $result = get_transient('kz_get_national_metropoles');

    if (false===$result)
    {
        $national = array();

        $villes = get_terms( array("ville"), array(
                    "orderby" => "count",
                    "parent" => 0, //only root terms,
                    "fields" => "ids"
                ) );

        //on prend le premier de la liste
        foreach ($villes as $key) {
            $def = get_tax_meta($key,'kz_national_ville');
            if ("on" ==  $def) {
                $the_term = get_term_by('id', $key, 'ville');
                array_push($national, $the_term->slug);
            }
        }   

        $result = $national;

        set_transient( 'kz_get_national_metropoles', (array)$result, 60 * 60 * 24 ); //1 jour de cache
    }

    return $result;
}


/**
 * undocumented function
 *
 * @return Tableau contenant les meta de Geoloc d'un post
 * @author 
 **/
function kz_get_post_geoloc_meta($post_id=0)
{

    if ($post_id==0)
    {
        global $post;
        $post_id = $post->ID;
    }

    $post = get_post($post_id);

    $type = $post->post_type;

    $location_name      = get_post_meta($post_id, 'kz_'.$type.'_location_name', TRUE);
    $location_address   = get_post_meta($post_id, 'kz_'.$type.'_location_address', TRUE);
    $location_latitude  = get_post_meta($post_id, 'kz_'.$type.'_location_latitude', TRUE);
    $location_longitude = get_post_meta($post_id, 'kz_'.$type.'_location_longitude', TRUE);
    $location_tel   = get_post_meta($post_id, 'kz_'.$type.'_location_phone_number', TRUE);
    $location_web   = get_post_meta($post_id, 'kz_'.$type.'_location_website', TRUE);

    return array(
        'location_name' => $location_name,
        "location_address" => $location_address,
        "location_latitude" => $location_latitude,
        "location_longitude" => $location_longitude,
        "location_tel" => $location_tel,
        "location_web" => $location_web
    );
}

add_action( 'save_post', 'kz_delete_geo_transients' );

/**
 * Supprime les transients utilisé dans ce fichier pour cacher les résultats de requete SQL
 *
 * @return void
 * @author 
 **/
function kz_delete_geo_transients($post_id)
{
    delete_transient( 'kz_get_national_metropoles' );
    delete_transient( 'kz_default_metropole' );
    delete_transient( 'kz_covered_metropoles_all_fields' );
    delete_transient( 'kz_covered_metropoles' );
    delete_transient( 'kz_post_metropole_' . $post_id );
}

add_filter('manage_posts_columns', 'kz_geo_table_head');
function kz_geo_table_head($columns) {
    $columns['metropole'] = 'M&eacute;tropole';
    // $columns['ville'] = 'Ville';
    return $columns;
}

add_action( 'manage_posts_custom_column', 'kz_geo_table_content', 10, 2 );
function kz_geo_table_content( $column_name, $post_id ) {

    if ($column_name=='metropole')
        echo (kz_get_post_metropole()->name);

}

add_filter( 'manage_edit-post_sortable_columns', 'kz_geo_table_sorting' );
function kz_geo_table_sorting( $columns ) {
    $columns['metropole'] = 'metropole';
    // $columns['ville'] = 'ville';
    return $columns;
}



?>