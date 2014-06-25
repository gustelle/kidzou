<?php
/*
Template Name: Kidzou - Agenda
*/
?>

<?php get_header(); ?>

<div id="main_content" class="clearfix<?php if ( isset($fullwidth) &&  $fullwidth) echo ' fullwidth'; ?>">
	<div id="left_area">
	
		
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>

				<div id="events-management">

					<div id="events">

						<?php 

							if ( function_exists('kz_events_list') ) {

								$events = kz_events_list(); 

								if (count($events)>0)
								{
									global $post;

									if ( function_exists('kz_geo_map_list') ) kz_geo_map_list($events);  

									foreach ($events as $event) {

										$post = $event;

										setup_postdata($event);

										get_template_part('includes/event'); 

									}

									wp_reset_postdata();
								}

							} 
						?>

					</div><!-- end #events -->	

					<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
					<?php edit_post_link(esc_attr__('Edit this page','Trim')); ?>
				
				</div>

			<?php 

			if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
				<div class="entry_content agenda">
						<?php the_content(); ?>

						<header>
							<h1>Les &eacute;v&eacute;nements &agrave; ne pas rater :</h1>
						</header>

						<?php 

							if (function_exists('kz_get_request_metropole')) {

								$metropole = kz_get_request_metropole();

								$query = new WP_Query( 
									array( 'posts_per_page' => 3, 
											'category_name' => 'evenement' ,
											'tax_query' => array(
											        array(
											              'taxonomy' => 'ville',
											              'field' => 'slug',
											              'terms' => $metropole,
											              )
											        )
												) 
								);

								if ( $query->have_posts() ) {

									while ( $query->have_posts() ) {

										$query->the_post() ;

										kz_entry_content();

									}

								}

								wp_reset_postdata(); wp_reset_query();
							}
						?>

				</div>
			<?php endwhile; // end of the loop. ?>			

			

	<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>

	</div> <!-- end #left_area -->

	<!--?php if ( ! $fullwidth ) get_sidebar(); ?-->

</div> <!-- end #main_content -->

<?php get_footer(); ?>





