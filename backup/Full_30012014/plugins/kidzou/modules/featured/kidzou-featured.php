<?php

//featured categories
//retourne un tableau [[term_id,slug,name],[term_id,slug,name],[term_id,slug,name]]
//utilisation dans la Home Page pour afficher par exemple Evenements, Plein Air et A l'abri

function get_featured_categories () {

	// if (KIDZOU_DEBUG)
	// 	$date_deb = microtime();

	global $wpdb;

	$cats = $wpdb->get_results("

				SELECT
					option_name
				FROM
					$wpdb->options key1
				WHERE
					key1.option_name like 'tax_meta_%'

			",ARRAY_N);

	$featuredCats = array(0 => array(), 1 => array(), 2 => array());
	foreach ($cats as $key => $value) {
		$pieces = explode("_", $value[0]);
		$position = intval(get_tax_meta($pieces[2],'kz_featured_order'));
		if ($position>0)
			$featuredCats[$position-1] = array("term_id" => $pieces[2],
												"slug" => get_term_by('id', $pieces[2], 'category')->slug,
												"name" => get_term_by('id', $pieces[2], 'category')->name);
	}

	// if (KIDZOU_DEBUG)
		//echo "Time elapsed : ".(microtime() - $date_deb)." ms";

	//print_r($featuredCats);
	//Array ( [0] => Array ( [term_id] => 4 [slug] => evenement [name] => Evenement ) [1] => Array ( [term_id] => 12 [slug] => plein-air [name] => PLEIN AIR ) [2] => Array ( [term_id] => 19 [slug] => interieur [name] => INTERIEUR ) )

	return $featuredCats;
}


function has_featured_comments ($postid) {

	global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( "

			SELECT count(*)
				FROM
					$wpdb->commentmeta key1
				INNER JOIN
					$wpdb->comments key2
	            ON
	            	key1.comment_id = key2.comment_id
				WHERE
					key1.meta_key='featured' and
					key1.meta_value='1' and
					key2.comment_post_ID=%d",
				$postid
	));

	if ($count>0) {
		return true;
	}

	return false;
}

//retourne le contenu d un featured comment
function the_featured_comments ($postid,$nb) {

	if (!$nb) $nb=1;

	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( "

			SELECT key2.comment_author, key2.comment_content
				FROM
					$wpdb->commentmeta key1
				INNER JOIN
					$wpdb->comments key2
				ON
					key1.comment_id = key2.comment_id
				WHERE
					key1.meta_key='featured' and
					key1.meta_value='1' and
					key2.comment_post_ID=%d
				limit %d",
				$postid,
				$nb
	));

	$content = "";

	foreach($results as $comment) {
		$content .= $comment->comment_content." par ".$comment->comment_author;
		if ($nb>1)
			$content .= "<br/>";
	}

	return $content;

}

?>