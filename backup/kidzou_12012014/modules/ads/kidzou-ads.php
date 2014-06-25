<?php


/**
* Affichage d'un bandeau de publicité sur les catégories
* (Ad-Management)
**/
function kz_category_header()
{
	include( plugin_dir_path( __FILE__ ) . '/category-header.php');
}

function get_featured_slugs() {

	// if (KIDZOU_DEBUG)
	// 	$date_deb = microtime();

	$featuredCats = get_featured_categories();
	$featuredSlugs = array();
	foreach ($featuredCats as $key => $value) {
		array_push($featuredSlugs, $value["slug"]);
	}

	// if (KIDZOU_DEBUG)
		//echo "Time elapsed : ".(microtime() - $date_deb)." ms";

	return $featuredSlugs;
}

?>