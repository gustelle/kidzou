<?php

	$feat_slugs = get_featured_slugs(); 
	
	if (in_category( $feat_slugs ))
	{
		$term_id = get_category_by_slug(
				get_the_slug()
			)->term_id;

		$img = get_tax_meta($term_id,'kz_image');
		$img_link = get_tax_meta($term_id,'kz_image_link');
		$img_date = get_tax_meta($term_id,'kz_image_date'); 

		$datenow = new DateTime("now");
		$dateimg = new DateTime($img_date);

		if (isset($img['src'])) 
		{
			if ( ($dateimg->format('ymd')>$datenow->format('ymd')) || $img_date=='')
			{
				echo '<a href="'.$img_link.'" target="_blank"><img src="'.$img['src'].'" class="radius-light shadow-light catad"></a>';
				echo '<hr class="separator"/>';
			}
		}

		
	}
?>