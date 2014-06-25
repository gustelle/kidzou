<?php
/*
Template Name: Kidzou - Agenda
*/
?>

<?php get_header(); ?>

<div id="main_content" class="clearfix<?php if ( $fullwidth ) echo ' fullwidth'; ?>">
	<div id="left_area">
	
		
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
	
			<?php 

			$check = get_teaser_events_7days(1); 
			if (count($check)>0) { 

			?>

				<div id="events-management">

					<div id="events">
		
						<header>
							<h1>L&apos;agenda des 7 prochains jours :</h1>
						</header>

						<!--div id="timeline"--><!--/div-->

						<?php get_template_part('includes/events', 'agenda'); ?>

					</div><!-- end #events -->	

					<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
					<?php edit_post_link(esc_attr__('Edit this page','Trim')); ?>
				
				</div>

			<?php } else { ?>

				<div class="entry">
					
					<!--If no results are found-->

					<div class="et-box et-warning">
						<div class="et-box-content">
							<h1>
								D&eacute;sol&eacute;, nous n&apos;avons rien &agrave; vous proposer cette semaine :-( 
							</h1>
							<p>
								Pour proposer une activit&eacute; et la r&eacute;f&eacute;rencer sur notre site, n&apos;h&eacute;sitez pas &agrave; nous contacter en 
								utilisant le <a alt="Nous contacter" href="<?php echo get_bloginfo('url') ;?>/contact/">Le formulaire de contact</a>
							</p>
						</div>
					</div>

				</div>


			<?php } ?>

			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
				<div class="entry_content">
						<?php the_content(); ?>
				</div>
			<?php endwhile; // end of the loop. ?>


	<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>

	</div> <!-- end #left_area -->

	<!--?php if ( ! $fullwidth ) get_sidebar(); ?-->

</div> <!-- end #main_content -->

<?php get_footer(); ?>





