<?php get_header(); ?>

<div id="main-content">
	<!-- <div class="container">
		<div id="content-area" class="clearfix"> -->
			<!-- <div id="left-area"> -->
			<?php while ( have_posts() ) : the_post(); ?>
				<?php if (et_get_option('divi_integration_single_top') <> '' && et_get_option('divi_integrate_singletop_enable') == 'on') echo(et_get_option('divi_integration_single_top')); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' ); ?>>

					<div class="entry-content">
						<!-- template kidzou -->
						
						<div class="et_pb_section et_section_specialty">
			
			<div class="et_pb_row">
				
				<div class="et_pb_column et_pb_column_3_4">
					<div class="et_pb_row_inner">
						<div class="et_pb_column et_pb_column_3_8 et_pb_column_inner">
							<?php

								$thumb = '';

								$width = (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );

								$height = (int) apply_filters( 'et_pb_index_blog_image_height', 675 );
								$classtext = 'et_featured_image';
								$titletext = get_the_title();
								$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
								$thumb = $thumbnail["thumb"];

								print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height , 'et_pb_image et-waypoint et_pb_image et_pb_animation_left et-animated');
					
							?>

						</div> <!-- .et_pb_column -->
						<div class="et_pb_column et_pb_column_3_8 et_pb_column_inner">
							<div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">
							
								<?php Kidzou_Vote::vote(get_the_ID(), 'font-2x'); ?>
								
								<h1><?php the_title(); ?></h1>
								<?php et_divi_post_meta(); ?>

							</div> <!-- .et_pb_text -->

							<?php

							if (Kidzou_GeoHelper::has_post_location()) {

								$location = Kidzou_GeoHelper::get_post_location();
							?>
								<div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">
									<?php if (isset($location['location_address']) && $location['location_address']<>'') echo '<p class="location"><i class="fa fa-map-marker"></i>'.$location['location_address'].'</p>'; ?>
									<?php if (isset($location['location_tel']) && $location['location_tel']<>'')  echo '<p class="location"><i class="fa fa-phone"></i>'.$location['location_tel'].'</p>'; ?>
									<?php if (isset($location['location_web']) && $location['location_web']<>'')  echo '<p class="location"><i class="fa fa-tablet"></i><a target="_blank" href="'.$location['location_web'].'">'.__('Visiter le site web','Divi').'</a></p>'; ?>

								</div> <!-- .et_pb_text --><hr class="et_pb_space" />
							
							<?php } ?>

							<?php

							if (Kidzou_Events::isTypeEvent()) {

								$location = Kidzou_Events::getEventDates();

								$start 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['start_date']);
								$end 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['end_date']);

								//bon OK c'est un hack pour régler un pb d'affichage
								//la date de fin s'affiche au lendemain de la date souhaitée
								$end->sub(new DateInterval('PT1H'));

								$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
								$formatter->setPattern('EEEE dd MMMM');

								$formatted = '';


								if ($start->format("Y-m-d") == $end->format("Y-m-d"))
									$formatted = __( 'Le ', 'Divi').  $formatter->format($start) ;
								else
									$formatted = __( 'Du ', 'Divi').  $formatter->format($start).__(' au ', 'Divi'). $formatter->format($end);
							?>
								<div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">
									<?php echo '<p class="location font-2x"><i class="fa fa-calendar"></i>'.$formatted.'</p>'; ?>
								</div> <!-- .et_pb_text -->
							
							<?php } ?>


							<?php 
								//easy social share buttons
								if ( shortcode_exists( 'easy-social-share' ) )
									echo do_shortcode('[easy-social-share counters=0 hide_names="yes" counter_pos="hidden" native="no" hide_total="yes"]');
							?>

							
							<!-- <div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_left content_preview">	
								<?php the_excerpt(); ?>
							</div> et_pb_text --> 
						</div> <!-- .et_pb_column -->
					</div> <!-- .et_pb_row_inner -->

					<div class="et_pb_row_inner">
						<div class="et_pb_column et_pb_column_4_4 et_pb_column_inner">
							<div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_justify">
									<?php
										global $kidzou_options;

										if ( isset($kidzou_options['pub_post']) && trim($kidzou_options['pub_post'])!='') {
											echo '<div class="post_ad ad" data-content="'.__('Publicite','Divi').'">';
												echo $kidzou_options['pub_post'];
											echo '</div>';
										}
											
									?>
							
								<?php the_content(); ?>


								<div class="post_format_block">
									<?php 

									//post format

									$post_format = get_post_format();

									if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) {
										printf(
											'<div class="et_main_video_container">
												%1$s
											</div>',
											$first_video
										);
									} else if ( ! in_array( $post_format, array( 'gallery', 'link', 'quote' ) ) && 'on' === et_get_option( 'divi_thumbnails', 'on' ) && '' !== $thumb ) {
										print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height );
									} else if ( 'gallery' === $post_format ) {
										// _e('<h2>Quelques photos :</h2>','Divi');
										et_gallery_images();
									}

									$text_color_class = et_divi_get_post_text_color();

									$inline_style = et_divi_get_post_bg_inline_style();

									switch ( $post_format ) {
										case 'audio' :
											printf(
												'<div class="et_audio_content%1$s"%2$s>
													%3$s
												</div>',
												esc_attr( $text_color_class ),
												$inline_style,
												et_pb_get_audio_player()
											);

											break;
										case 'quote' :
											printf(
												'<div class="et_quote_content%2$s"%3$s>
													%1$s
												</div> <!-- .et_quote_content -->',
												et_get_blockquote_in_content(),
												esc_attr( $text_color_class ),
												$inline_style
											);

											break;
										case 'link' :
											printf(
												'<div class="et_link_content%3$s"%4$s>
													<a href="%1$s" class="et_link_main_url">%2$s</a>
												</div> <!-- .et_link_content -->',
												esc_url( et_get_link_url() ),
												esc_html( et_get_link_url() ),
												esc_attr( $text_color_class ),
												$inline_style
											);

											break;
									}
								?>
								</div> <!-- .post_format_block -->

								<?php 
								//easy social share buttons
									// if ( shortcode_exists( 'easy-social-like' ) )
									// 	echo do_shortcode('[easy-social-like facebook="true" twitter_tweet="true" skinned="true" skin="metro" counters="true"]');
								?>

								<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'Divi' ), 'after' => '</div>' ) );?>

							</div> <!-- .et_pb_text -->
						</div> <!-- .et_pb_column -->
					</div> <!-- .et_pb_row_inner -->
		
					<div class="et_pb_row_inner post_inner_content">
						<div class="et_pb_column et_pb_column_4_4 et_pb_column_inner">

					<?php 

						if (Kidzou_GeoHelper::has_post_location()) { 

							$location = Kidzou_GeoHelper::get_post_location();

					?>

						<div class="et_pb_tabs">
							<ul class="et_pb_tabs_controls clearfix">
								<li class="et_pb_tab_active"><strong><?php echo $location['location_name']; ?></strong></li>
							</ul>
							<div class="et_pb_all_tabs">
								<div class="et_pb_tab clearfix et_pb_active_content">

									<?php 
										echo do_shortcode('[et_pb_map admin_label="Map" address="'.$location['location_address'].'" zoom_level="15" address_lat="'.$location['location_latitude'].'" address_lng="'.$location['location_longitude'].'"][et_pb_map_pin title="'.$location['location_name'].'" pin_address="'.$location['location_address'].'" pin_address_lat="'.$location['location_latitude'].'" pin_address_lng="'.$location['location_longitude'].'"]
											<p><strong>'.$location['location_name'].'</strong></p>'.
												 (isset($location['location_address']) && $location['location_address']<>'' ? '<p class="location"><i class="fa fa-map-marker"></i>'.$location['location_address'].'</p>' : '').
												 (isset($location['location_tel']) && $location['location_tel']<>'' ? '<p class="location"><i class="fa fa-phone"></i>'.$location['location_tel'].'</p>':'').
												 (isset($location['location_web']) && $location['location_web']<>'' ?  '<p class="location"><i class="fa fa-tablet"></i>'.$location['location_web'].'</p>':'').
											'[/et_pb_map_pin]
										[/et_pb_map]');
									?>
							
									
								</div> <!-- .et_pb_tab -->
							</div> <!-- .et_pb_all_tabs -->
						</div> <!-- .et_pb_tabs -->

					<?php } ?>

					<?php
						if ( et_get_option('divi_468_enable') == 'on' ){
							echo '<div class="et-single-post-ad">';
							if ( et_get_option('divi_468_adsense') <> '' ) echo( et_get_option('divi_468_adsense') );
							else { ?>
								<a href="<?php echo esc_url(et_get_option('divi_468_url')); ?>"><img src="<?php echo esc_attr(et_get_option('divi_468_image')); ?>" alt="468 ad" class="foursixeight" /></a>
					<?php 	}
							echo '</div> <!-- .et-single-post-ad -->';
						}
					?>

					<?php
						if ( comments_open() && 'on' == et_get_option( 'divi_show_postcomments', 'on' ) )
							comments_template( '', true );
					?>
					
				<!-- </div> -->
			<!-- </div> -->
						

				<?php if (et_get_option('divi_integration_single_bottom') <> '' && et_get_option('divi_integrate_singlebottom_enable') == 'on') echo(et_get_option('divi_integration_single_bottom')); ?>


			</div> <!-- .et_pb_column -->
			</div> <!-- .et_pb_row_inner -->
		</div> <!-- .et_pb_column -->
		<div class="et_pb_column et_pb_column_1_4">
			<div class="et_pb_widget_area et_pb_widget_area_right clearfix et_pb_bg_layout_light">
				<?php get_sidebar(); ?>
			</div>

		</div> <!-- .et_pb_column -->
			</div> <!-- .et_pb_row -->
		</div> <!-- .et_pb_section -->

		<?php get_post_footer(); ?>						
					</div> <!-- .entry-content -->
					
				</article> <!-- .et_pb_post -->
				
			<?php endwhile; ?>
			<!-- </div> #left-area -->

			
		<!-- </div> #content-area -->
	<!-- </div> .container -->
</div> <!-- #main-content -->

<?php get_footer(); ?>

