<?php


	$home_cats = Array();

	if (function_exists('get_featured_categories')) $home_cats = get_featured_categories();

	$the_cat_id;

	foreach ($home_cats as &$acat)
	{

		$args = apply_filters( 'et_recentwork_args', array(
												'showposts' => (int) get_option('trim_recentwork_posts_num'),
												'cat' => $acat['term_id']
									) );
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {

			while ( $query->have_posts() ) {

				$query->the_post() ;

				kz_entry_content();

			}

		}

		wp_reset_postdata(); wp_reset_query();

	} ?>