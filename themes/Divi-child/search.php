<?php get_header(); ?>

<div id="main-content">
	<!-- <div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area"> -->

				<div class="et_pb_section et_section_regular">

					<div class="et_pb_row">

						<div class="et_pb_column et_pb_column_4_4">

							<div class="et_pb_blog_grid_wrapper" id='et_pb_blog_grid_wrapper'>

								<div class="et_pb_blog_grid clearfix et_pb_bg_layout_light">

									<?php

										// global $kidzou_options;

										$show_thumbnail = 'on';
										$fullwidth = 'off';
										$show_author = 'off';
										$show_date = 'off';
										$show_categories = 'on';
										$show_content = 'off';
										$show_pagination = 'on';
										$background_layout = 'light';
										$module_id = '';
										$module_class = '';
										$container_is_closed = false;
										$meta_date = 'j M';

										wp_enqueue_script( 'jquery-masonry-3' );

										ob_start();

										if ( have_posts() ) {
											while ( have_posts() ) {
												the_post();

												$post_format = get_post_format();

												$thumb = '';

												$width = 400;
												// $width = (int) apply_filters( 'et_pb_blog_image_width', $width );

												$height = 250;
												// $height = (int) apply_filters( 'et_pb_blog_image_height', $height );
												$classtext = '';
												$titletext = get_the_title();
												$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
												$thumb = $thumbnail["thumb"];

												$no_thumb_class = '' === $thumb || 'off' === $show_thumbnail ? ' et_pb_no_thumb' : '';

												if ( in_array( $post_format, array( 'video', 'gallery' ) ) ) {
													$no_thumb_class = '';
												} ?>

											<article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post kz_search' . $no_thumb_class ); ?>>

											<?php
												et_divi_post_format_content();

												if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
													if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) :
														printf(
															'<div class="et_main_video_container">
																%1$s
															</div>',
															$first_video
														);
													elseif ( 'gallery' === $post_format ) :
														et_gallery_images();
													elseif ( '' !== $thumb && 'on' === $show_thumbnail ) :
														if ( 'on' !== $fullwidth ) echo '<div class="et_pb_image_container">'; ?>
															<a href="<?php the_permalink(); ?>">
																<?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
															</a>
													<?php
														if ( 'on' !== $fullwidth ) echo '</div> <!-- .et_pb_image_container -->';
													endif;
												} ?>

											<?php if ( 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote', 'gallery' ) ) ) { ?>
												<?php if ( ! in_array( $post_format, array( 'link', 'audio' ) ) ) { ?>
													<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
												<?php } ?>

												<?php
													if ( 'on' === $show_author || 'on' === $show_date || 'on' === $show_categories ) {
														printf( '<p class="post-meta">%1$s %2$s %3$s</p>',
															(
																'on' === $show_author
																	? sprintf( __( 'by %s |', 'Divi' ), et_get_the_author_posts_link() )
																	: ''
															),
															(
																'on' === $show_date
																	? sprintf( __( '%s |', 'Divi' ), get_the_date( $meta_date ) )
																	: ''
															),
															(
																'on' === $show_categories
																	? get_the_category_list(', ')
																	: ''
															)
														);
													}

													if ( 'on' === $show_content ) {
														global $more;
														$more = null;

														the_content( __( 'read more...', 'Divi' ) );
													} else {
														if ( has_excerpt() ) {
															the_excerpt();
														} else {
															truncate_post( 270 );
														}
													} ?>
											<?php } // 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote', 'gallery' ?>

											</article> <!-- .et_pb_post -->
									<?php
											} // endwhile

											if ( 'on' === $show_pagination && ! is_search() ) {
												echo '</div> <!-- .et_pb_posts -->';

												$container_is_closed = true;

												if ( function_exists( 'wp_pagenavi' ) )
													wp_pagenavi();
												else
													get_template_part( 'includes/navigation', 'index' );
											}

											wp_reset_query();
										} else {
											get_template_part( 'includes/no-results', 'index' );
										}

										$posts = ob_get_contents();

										ob_end_clean();

										$class = " et_pb_bg_layout_{$background_layout}";

										$output = sprintf(
											'<div%5$s class="%1$s%3$s%6$s">
												%2$s
											%4$s',
											( 'on' === $fullwidth ? 'et_pb_posts' : 'et_pb_blog_grid clearfix' ),
											$posts,
											esc_attr( $class ),
											( ! $container_is_closed ? '</div> <!-- .et_pb_posts -->' : '' ),
											( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
											( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
										);

										if ( 'on' !== $fullwidth )
											$output = sprintf( '<div id="et_pb_blog_grid_wrapper" class="et_pb_blog_grid_wrapper">%1$s</div>', $output );
								

										echo $output;

									?>

									
								</div>

							<!-- </div> -->
							<!-- <div class="et_pb_row_inner"> -->

								<!-- <div class="et_pb_column et_pb_column_4_4 "> -->

								<hr class="et_pb_space et_pb_divider" style="border-color: #c6c6c6;">

									<?php 
										$lists = et_pb_get_mailchimp_lists();

										if(!empty($lists) && is_array($lists)) {
											$keys = array_keys($lists);
											$key = $keys[1];
											echo do_shortcode('[et_pb_signup admin_label="Subscribe" provider="mailchimp" mailchimp_list="'.$key.'" aweber_list="none" title="Inscrivez-vous à notre Newsletter" button_text="Inscrivez-vous " use_background_color="on" background_color="#ed0a71" background_layout="dark" text_orientation="left"]<p>Nous distribuons la newsletter 1 à 2 fois par mois, elle contient les meilleures recommandations de la communauté des parents Kidzou, ainsi que des jeux concours de temps en temps ! </p>[/et_pb_signup]'); 
										}
									?>

								<!-- </div> -->

							</div>

						</div>

						<!-- <div class="et_pb_column et_pb_column_1_4">

							< ? php get_sidebar(); ?>

						</div> -->

					</div> <!--.et_pb_row-->
					

				</div>
				
				

			</div> <!-- #left-area -->

			
		<!--</div>  #content-area -->
	<!--<</div> .container -->
<!--</div>  #main-content -->

<?php get_footer(); ?>

