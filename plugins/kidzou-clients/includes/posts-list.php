<div class="events-list">

	<?php 

	global $post;

	$args = array();
	$customer_id = the_event_meta('customer');
	$current_post = $post->ID;

	if ( $customer_id!='' && intval($customer_id)>0 )
	{

		$args = array(
				'post_type' => array('post','offres','concours', 'event'), 
				'post__not_in' => array($current_post),
				'post_status' => 'publish' ,
				'meta_query' => array(
					array(
						'key' => 'kz_event_customer',
						'value' => $customer_id ,
						'compare' => '=',
						'type' => 'NUMERIC'
					)
				)
			);
	}

	if (count($args)>0) {

		$the_query = new WP_Query( $args );

		// echo $the_query->request;

		if ($the_query->have_posts()) { 

			echo '<header>
				<h2>Articles associ&eacute;s :</h2>
			</header>';


			while ( $the_query->have_posts() ) { 

				$the_query->the_post();

				if (function_exists('kz_entry_content')) kz_entry_content();
			}

			/* Restore original Post Data */
			wp_reset_postdata();
		}

	}

	?>

</div> <!-- /events-list -->