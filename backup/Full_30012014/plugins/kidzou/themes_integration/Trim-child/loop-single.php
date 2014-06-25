<?php 

	if ( have_posts() ) while ( have_posts() ) : the_post(); 

	if ( $post == '' ) global $post;
		$postid = $post->ID;

?>
	<?php if (get_option('trim_integration_single_top') <> '' && get_option('trim_integrate_singletop_enable') == 'on') echo (get_option('trim_integration_single_top')); ?>

	<article class="entry post clearfix" itemscope itemtype="http://schema.org/BlogPosting" id="kz-article">
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
					
					echo "</div> <!-- /ported -->";
				}
			?>
			
		<header>

		<div class="post-content clearfix" id="post-content">
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
			<?php } 

			$events = get_upcoming_events_by_post($post->ID);  
			
			if (count ($events) >0) { ?>

				<div id="events-management">
	
					<section id="events">

						<header>
							<h2>L&apos;agenda des prochains jours :</h2>
						</header>
						
						<?php include(locate_template('includes/events-list.php')); ?>
						<?php include(locate_template('includes/events-details.php')); ?>

					</section><!-- end #events -->

				</div> <!-- end #event-management -->
			
			<?php } ?>
	
			<section class="entry_content" itemprop="articleBody"><?php the_content(); ?></section> <!-- end .entry_content -->
			<?php 
				if(function_exists('kz_connections')) 
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
		if(function_exists('echo_ald_crp')) 
		{
			echo '<aside>';
			echo_ald_crp(); 
			echo '</aside>';
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