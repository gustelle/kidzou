<?php
/**
 * The template used for displaying page content in links.php
 *	v0.2
 */
?>

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>


<article class="entry post clearfix">

		<h1 class="main_title"><?php the_title(); ?></h1>

		<div class="post-content clearfix">

			<div class="entry_content">
				<?php the_content(); ?>

				<div id="links">

<?php
				$args = array(
					'orderby'          => 'name',
					'order'            => 'ASC',
					'limit'            => -1,
					'category_name'    => 'Partenaires',
					'hide_invisible'   => 1,
					'categorize'       => 0
				);

				$myLinks = get_bookmarks($args);

				foreach($myLinks as $myLink) {
				?>

				<div class="link">

						<h2>
							<a href="<?php echo($myLink->link_url); ?>" target="<?php echo($myLink->link_target); ?>"><?php echo($myLink->link_name); ?></a>
						</h2>

						<p>
							<a href="<?php echo($myLink->link_url); ?>" target="<?php echo($myLink->link_target); ?>">
								<?php if ($myLink->link_image<>'') { ?><img src='<?php echo($myLink->link_image); ?>' alt='' /><?php } ?>
							</a>

							<a href="<?php echo($myLink->link_url); ?>" target="<?php echo($myLink->link_target); ?>">
								<?php echo($myLink->link_description); ?>
							</a>
						</p>

				</div> <!-- link -->

				<?php } ?>

			</div> <!-- links -->

				<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
				<?php edit_post_link(esc_attr__('Edit this page','Trim')); ?>
			</div> <!-- end .entry_content -->
		</div> <!-- end .post-content -->


	</article> <!-- end .post -->


<?php endwhile; // end of the loop. ?>





