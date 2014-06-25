<?php

//deja inclus dans kidzou.php
// require_once( plugin_dir_path( __FILE__ )."/../Tax-Meta-Class/Tax-meta-class/Tax-meta-class.php");

/**
* Affichage d'un bandeau de publicité sur les catégories
* (Ad-Management)
**/
function kz_category_header()
{
	include( plugin_dir_path( __FILE__ ) . '/category-header.php');
}

function get_featured_slugs() 
{

	$featuredCats = get_featured_categories();
	$featuredSlugs = array();
	foreach ($featuredCats as $key => $value) {
		array_push($featuredSlugs, $value["slug"]);
	}

	return $featuredSlugs;
}

?>