

<div class="events-list">

	<?php 

		global $post;

		setlocale(LC_ALL, 'fr_FR');

		$meta = get_event_meta();

		$start_date 		= $meta['start_date'];
		$end_date   		= $meta['end_date'];
		$location_name   	= $meta['location_name'];
		$location_address   = $meta['location_address'];
		$location_city 		= $meta['location_venue'];
		$featured 			= $meta['featured'];

		$start_date_time 	= strtotime($start_date);
		$end_date_time 		= strtotime($end_date);

		$current_time 	= time();

		$is_event_today = false;
		if ($current_time<=$end_date_time && $current_time>=$start_date_time)
			$is_event_today = true;

		$image_url = wp_get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail');

	?>

	<article 	class="shadow-light events entry clearfix <?php if ($is_event_today) echo 'today'; ?>" 
					itemscope 
					itemtype="http://schema.org/Event">

			<meta itemprop="startDate" 	content="<?php echo $start_date; ?>">
			<meta itemprop="endDate" 	content="<?php echo $end_date; ?>">
			<meta itemprop="url" 		content="<?php echo get_permalink(); ?>">
			<meta itemprop="name" 		content="<?php echo $post->post_title; ?>">
			<meta itemprop="image" 		content="<?php echo $image_url[0]; ?>"> 
			<meta itemprop="location" 	content="<?php echo $location_address; ?>">

			<!--div class="date"-->
				<?php if ($is_event_today) {?>
					<span class="post-meta today">Aujourd&apos;<span>hui</span></span>
				<?php } else { ?>
					<span class="post-meta <?php if ($featured) {echo 'featured-event';} ?>"><?php echo strftime("%a", $start_date_time); ?><span><?php echo strftime("%d", $start_date_time ); ?></span></span> <!-- //->format( 'd' )-->
				<?php } ?>
			<!--/div-->
			<a href="<?php echo get_permalink($post->ID); ?>" title="<?php the_title(); ?>">
			<div class="entry-thumbnail">
										
					<?php the_post_thumbnail( array(250,167) ); ?>
				
			</div><!-- .entry-thumbnail -->
			</a>

			<header class="entry-header">
				<h2 class="entry-title">
					<a href="<?php echo get_permalink($post->ID); ?>" title="<?php the_title(); ?>">
						<?php the_title(); ?>
					</a>
				</h2>
				<span>
					<span class="booticon-time"></span>
					<a href="<?php echo get_permalink($post->ID); ?>" title="<?php the_title(); ?>">
						<?php echo ($end_date!=null && $end_date!='' && $end_date!=$start_date) ? 'Du ' : 'Le '; ?>
							<?php echo strftime("%d/%m", $start_date_time); ?>
						<?php echo ($end_date!=null && $end_date!='' && $end_date!=$start_date) ? (' au ' ) : ''; ?>
							<?php echo strftime("%d/%m", $end_date_time); ?>
					</a>
				</span>
				
				<span><span class="booticon-map-marker"></span>
					<a href="<?php echo get_permalink($post->ID); ?>" title="<?php the_title(); ?>">
						<?php echo ($location_city!='' ? $location_city : $location_name); ?></span>
					</a>
			</header><!-- .entry-header -->

		</article>


</div> <!-- /events-list -->



