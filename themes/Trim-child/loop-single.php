<?php 

	if ( have_posts() ) while ( have_posts() ) : the_post(); 

	if ( $post == '' ) global $post;
		$postid = $post->ID;

	$itemtype = (get_post_type()=='event' ? "http://schema.org/Event" : "http://schema.org/BlogPosting")

?>
	<?php if (get_option('trim_integration_single_top') <> '' && get_option('trim_integrate_singletop_enable') == 'on') echo (get_option('trim_integration_single_top')); ?>

	<article class="entry post clearfix" itemscope itemtype="<?php echo $itemtype; ?>" id="kz-article">

		<?php if ( function_exists( 'kz_is_event' ) &&  kz_is_event() ) { 

			$meta = get_event_meta();

			$start_date 		= $meta["start_date"];
			$end_date   		= $meta["end_date"];
			$location_name   	= $meta["location_name"];
			$location_address   = $meta["location_address"];
			$location_city 		= $meta["location_venue"];
			$image_url = wp_get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail');

		?>

			<meta itemprop="startDate" 	content="<?php echo $start_date; ?>">
			<meta itemprop="endDate" 	content="<?php echo $end_date; ?>">
			<meta itemprop="url" 		content="<?php echo $post->guid; ?>">
			<meta itemprop="name" 		content="<?php echo $post->post_title; ?>">
			<meta itemprop="image" 		content="<?php echo $image_url[0]; ?>"> 
			<meta itemprop="location" 	content="<?php echo $location_name.' '.$location_address; ?>">

		<?php } ?>

		<?php if ( 'on' == get_option('trim_show_date_icon_single') ) { ?>
			<span class="post-meta"><?php echo get_the_time( 'D' ); ?><span><?php echo get_the_time( 'd' ); ?></span></span>
		<?php } ?>
		<header>
			<h1 class="main_title" itemprop="headline"><?php the_title(); ?></h1>

			<?php
				$index_postinfo = get_option('trim_postinfo2');
				if ( $index_postinfo ){
					echo '<p class="meta" itemprop="keywords">';
					if ( function_exists( 'kz_postinfo_cats' ) ) kz_postinfo_cats( );
					//et_postinfo_meta( $index_postinfo, get_option('trim_date_format'), esc_html__('0 commentaires','Trim'), esc_html__('1 commentaire','Trim'), '% ' . esc_html__('commentaires','Trim') );
					echo '</p>';
			?>

					<div id="ported" class="<?php if (is_user_logged_in()) echo 'offset30'; ?>">
							
						<div class="votable radius-light" 	data-post="<?php echo $postid; ?>" 
											 	data-bind="template: { name: 'vote-template', data: votes.getVotableItem(<?php echo $postid; ?>) }"></div>

			<?php
						if( function_exists('kz_post_comment_block') ) echo (kz_post_comment_block( $index_postinfo ));
						if( function_exists('kz_fb_share') ) echo (kz_fb_share( ));
						if ( function_exists( 'kz_is_event' ) &&  kz_is_event() ) { 

							setlocale(LC_TIME, 'fr_FR');

							$sdt = new DateTime($start_date);
							$start_date_format = $sdt->format('d/m/y');

							$edt = new DateTime($end_date);
							$end_date_format = $edt->format('d/m/y');

							echo '<hr/><div class="et-box et-shadow">
									<div class="et-box-content">		
										<span class="booticon-time"></span>Du <strong>'.$start_date_format.'</strong> au <strong>'.$end_date_format.'</strong>&nbsp;&nbsp;&nbsp;&nbsp;
										<span class="booticon-map-marker"></span>'.$location_city.'
									</div></div>';
						}
					
					echo "</div> <!-- /ported -->";
				}
			?>
			
		<header>
	

		<div class="post-content clearfix" id="post-content">

			<?php if ( function_exists( 'kz_is_event' ) &&  kz_is_event() ) { ?>
				<?php if ( function_exists( 'kz_is_event_active' ) &&  !kz_is_event_active() ) { ?>
					<div class="et-box et-warning">
						<div class="et-box-content">
							<h1>Oops...Cet &eacute;v&eacute;nement est termin&eacute; !</h1>
						</div>
					</div>
				<?php } ?>
			<?php } ?>

			<?php
				$thumb = '';
				$width = (int) apply_filters('et_image_width',481);
				$height = (int) apply_filters('et_image_height',230);
				$classtext = '';
				$titletext = get_the_title();
				$thumbnail = get_thumbnail($width,$height,$classtext,$titletext,$titletext,false,'Entry');
				$thumb = $thumbnail["thumb"];
			?>
			<?php if ( '' != $thumb && 'on' == get_option('trim_thumbnails') ) { ?>
			<div class="featured_box">
				<a href="<?php the_permalink(); ?>">
					<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, $classtext); ?>
				</a>
			</div> 	<!-- end .featured_box -->
			<?php } ?>

			<?php if ( function_exists( 'kz_is_event' ) && !kz_is_event() ) { ?>
			<section>
				<?php 
					$events = kz_the_customer_events(); 
					if (count($events)>0)
					{
						foreach ($events as $event) {

							$post = $event;

							setup_postdata($event);

							get_template_part('includes/event'); 

						}
						wp_reset_postdata();
					}

				?>
			</section><!-- end #events -->
			<?php } ?>
	
			<section class="entry_content" itemprop="articleBody"><?php the_content(); ?></section> <!-- end .entry_content -->

			<?php if ( function_exists( 'kz_map' )  ) { ?>
		
			<section class="map">
				<?php kz_map(); ?>
			</section><!-- end #events -->
			<?php } ?>

			<?php if ( function_exists( 'kz_customer_related_posts' ) ) { ?>
			<section class="related">
				<?php kz_customer_related_posts(); ?>
			</section><!-- end #events -->
			<?php } ?>

			<?php 
				if(function_exists('kz_connections') && function_exists( 'kz_is_map' ) && !kz_is_map() ) 
				{
					echo '<section>';
					kz_connections();
					echo '</section>'; 
				}
			?>
			<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			<?php edit_post_link(esc_attr__('Edit this page','Trim')); ?>
		</div> <!-- end .post-content -->
		<?php 
		if ( function_exists( 'kz_is_event' ) && !kz_is_event() ) { 

			if(function_exists('echo_ald_crp')) 
			{
				echo '<aside>';
				echo_ald_crp(); 
				echo '</aside>';
			}
			
		} else {
			get_template_part('events-home');
		}
		?>
	</article> <!-- end .post -->

	<?php if (get_option('trim_integration_single_bottom') <> '' && get_option('trim_integrate_singlebottom_enable') == 'on') echo(get_option('trim_integration_single_bottom')); ?>

	<?php
		if ( get_option('trim_468_enable') == 'on' ){
			if ( get_option('trim_468_adsense') <> '' ) echo( get_option('trim_468_adsense') );
			else { ?>
			   <a href="<?php echo esc_url(get_option('trim_468_url')); ?>"><img src="<?php echo esc_attr(get_option('trim_468_image')); ?>" alt="468 ad" class="foursixeight" /></a>
	<?php 	}
		}
	?>

	<?php
		if ( 'on' == get_option('trim_show_postcomments') ) comments_template('', true);
	?>
<?php endwhile; // end of the loop. ?>