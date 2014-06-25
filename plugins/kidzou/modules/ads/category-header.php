<?php

	// $feat_slugs = get_featured_slugs(); 
	
	// if (in_category( $feat_slugs ))
	// {
		$term_id = get_category_by_slug(
				get_the_slug()
			)->term_id;

		//code HTML en entete prime sur le reste
		$html = get_tax_meta($term_id,'kz_html_code');

		$img = get_tax_meta($term_id,'kz_image');
		$img_link = get_tax_meta($term_id,'kz_image_link');
		$img_date = get_tax_meta($term_id,'kz_image_date'); 

		$datenow = new DateTime("now");
		$dateimg = new DateTime($img_date);

		if ($html!='') 
		{
			echo stripslashes($html); //supprimer les caractères d'échappement ajoutés lors du stockage en base
			echo '<hr class="separator"/>';
		}
		else if (isset($img['src'])) 
		{
			if ( ($dateimg->format('ymd')>$datenow->format('ymd')) || $img_date=='')
			{
				echo '<a href="'.$img_link.'" target="_blank"><img src="'.$img['src'].'" class="radius-light shadow-light catad"></a>';
				echo '<hr class="separator"/>';
			}
		}

		
	// }
?>