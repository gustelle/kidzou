<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php
	$thumb = '';

	$width = apply_filters('et_image_width',250);
	$height = apply_filters('et_image_height',167);

	$classtext = '';
	$titletext = get_the_title();
	$thumbnail = get_thumbnail($width,$height,'r-work-image',$titletext,$titletext,true,'Work');
	$thumb = $thumbnail["thumb"];
	$postid = $post->ID;

	$category; $parentsIDArray;$parentsNameArray;

	if ( is_single() || is_category() || is_tax() || is_home() || is_search() ) {

		if (has_term('','category')) {

			$category = get_the_category($postid);

			$parents_id  = get_category_parents($category[0], FALSE, ',', TRUE);
			$parents_name  = get_category_parents($category[0], FALSE, ',', FALSE);

			$parentsIDArray=explode(",",$parents_id);
			$parentsNameArray=explode(",",$parents_name);
		}

	}
?>

	<article class="clearfix <?php echo $parentsIDArray[0]; ?>" itemscope itemtype="http://schema.org/BlogPosting">
		<?php if ( 'on' == get_option('trim_show_date_icon_index') ) { ?>
			<span class="post-meta"><?php echo get_the_time( 'D' ); ?><span><?php echo get_the_time( 'd' ); ?></span></span>
		<?php } ?>

	
		<div class="entry-thumbnail">
		<a href="<?php the_permalink(); ?>">
			<?php print_thumbnail($thumb, $thumbnail["use_timthumb"], $titletext, $width, $height, ''); ?>
			<?php echo '<span class="meta '.$parentsIDArray[0].'">'.$parentsNameArray[0].'</span>'; ?>
		</a>
		</div><!-- .entry-thumbnail -->

		<header class="entry-header">
		<h2 class="entry-title" itemprop="headline">
			<a href="<?php the_permalink(); ?>" alt="<?php echo $titletext; ?>"><?php echo $titletext ?></a>
		</h2>

		<?php

			$index_postinfo = get_option('trim_postinfo1');
			if ( $index_postinfo )
			{
				echo '<p class="meta" itemprop="keywords">';
				if ( function_exists( 'kz_postinfo_cats' ) ) kz_postinfo_cats();
				echo '<br/>';
				echo '</p>';
				
				//if( function_exists('kz_post_reco_block') ) echo (kz_post_reco_block(get_the_ID()));
		?>
			<div class="votable radius-light" 	data-post="<?php echo $postid; ?>" 
								 	data-bind="template: { name: 'vote-template', data: votes.getVotableItem(<?php echo $postid; ?>) }"></div>
		<?php

				if( function_exists('kz_post_comment_block') ) echo (kz_post_comment_block( $index_postinfo ));

				// if( function_exists('kz_post_comment_block') ) echo (kz_post_comment_block( $index_postinfo ));
				//echo '<i class="icon-comment"></i>';
				//et_postinfo_meta( $index_postinfo, get_option('trim_date_format'), esc_html__('0 commentaires','Trim'), esc_html__('1 commentaire','Trim'), '% ' . esc_html__('commentaires','Trim') );
			}

		?>

		</header><!-- .entry-header -->
		<section class="entry-content" >
			<a href="<?php the_permalink(); ?>" alt="Cliquez pour lire : <?php echo $titletext; ?>">
			<?php the_excerpt(); ?>
			</a>
			<?php if(function_exists('the_featured_comments')) : if (has_featured_comments($postid)) : ?>
			<blockquote class="featured-comment">
				<a href="<?php the_permalink(); ?>" alt="Cliquez pour lire ce commentaire que nous aimons !"><strong>On aime ce commentaire !<br/></strong>&laquo;&nbsp;<?php echo limit_text(the_featured_comments($postid,1),50) ?>&nbsp;&raquo;</a>
			</blockquote>
			<?php endif;endif; ?>
		</section><!-- .entry-content -->
	</article> 	<!-- end .post-->
<?php
endwhile;
	if (function_exists('wp_pagenavi')) { wp_pagenavi(); }
	else { get_template_part('includes/navigation','entry'); }
else:
	get_template_part('includes/no-results','entry');
endif; ?>