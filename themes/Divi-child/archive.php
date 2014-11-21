<?php get_header(); ?>

<div id="main-content">
	<!-- <div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area"> -->

				<?php 

					global $wp_query;

					// if ( WP_DEBUG === true && current_user_can('manage_options') )
					// 	error_log( $wp_query->request );

					$name = '';
					// print_r($wp_query);

					if (is_tax()) {
						
						$taxes = $wp_query->tax_query->queries; //print_r($wp_query);
						foreach ($taxes as $tax) {
							if ($tax['taxonomy']!='ville') {
								$slug = $tax['terms'][0];
								$term = get_term_by('slug', $slug, $tax['taxonomy']);
								$name = $term->name;
							}	
						}

					} elseif (is_category() ) {
						$name = $wp_query->queried_object->name;
					} else {
						//
					}
					
					if ($name != '') {
						echo do_shortcode(
						'[et_pb_section fullwidth="on" specialty="off" background_color="#2ea3f2" inner_shadow="on" parallax="off"]
							[et_pb_fullwidth_header admin_label="Fullwidth Header" title="'.$name.'" subhead="Près de chez vous" background_layout="dark" text_orientation="left" /]
						[/et_pb_section]');
					} else {
						echo do_shortcode(
						'[et_pb_section fullwidth="on" specialty="off" background_color="#2ea3f2" inner_shadow="on" parallax="off"]
							[et_pb_fullwidth_header admin_label="Fullwidth Header" title="" subhead="" background_layout="dark" text_orientation="left" /]
						[/et_pb_section]');
					}
					

				?>
				<div class="et_pb_section et_section_regular">

					<div class="et_pb_row">

						<div class="et_pb_column et_pb_column_4_4">

							<!-- <div class="et_pb_row_inner"> -->

								<div class="et_pb_portfolio_grid clearfix et_pb_bg_layout_light ">

									<?php

										global $kidzou_options;

										$with_votes = true;
										$show_title = 'on';
										$show_categories = 'on';
										$show_pagination = 'on';
										$filter = 'none';
										$fullwidth = 'off';
										$background_layout = 'light';
										$module_id ='';
										$module_class = '';
										$show_ad = 'on';

										if ( isset($kidzou_options['pub_archive']) && $kidzou_options['pub_archive']<>'')
											echo $kidzou_options['pub_archive'];

										ob_start();

										$categories_included = array();

										$index = 0;

										if ( have_posts() ) {

											while ( have_posts() ) {

												if ($index==2 && $show_ad=='on') {

													//insertion de pub
													global $kidzou_options;

													if ( isset($kidzou_options['pub_portfolio']) && trim($kidzou_options['pub_portfolio'])!='') {

														$output = sprintf(
															'<div id="pub_portfolio" class="%1$s" data-content="%3$s">
																%2$s
															</div>',
															'et_pb_portfolio_item kz_portfolio_item ad',
															$kidzou_options['pub_portfolio'],
															__('Publicite','Divi')
														);

														echo $output;

													}
														

												} else {

													the_post(); 

													$categories = get_the_terms( get_the_ID(), 'category' );
													if ( $categories ) {
														foreach ( $categories as $category ) {
															$categories_included[] = $category->term_id;
														}
													}
													?>

													<div id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_portfolio_item kz_portfolio_item' ); ?>>

														<?php
														$thumb = '';

														$width = 400;
														$height = 284;

														$classtext = '';
														$titletext = get_the_title();
														$thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'et-pb-portfolio-image' );
														$thumb = $thumbnail["thumb"];

														// print_r($thumb);

														if ( '' !== $thumb ) : ?>
															<a href="<?php the_permalink(); ?>">
															<?php if ( 'on' !== $fullwidth ) : ?>
																<span class="et_portfolio_image">
															<?php endif; ?>
															<?php if ( $with_votes  ) 
																	Kidzou_Vote::vote(get_the_ID(), 'hovertext votable_template'); ?>
																	<?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
															<?php if ( 'on' !== $fullwidth ) : ?>
																	<span class="et_overlay"></span>
																</span>
															<?php endif; ?>
															</a>
													<?php
														endif;
													?>

														<?php if ( 'on' === $show_title ) : ?>
															<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
														<?php endif; ?>

														<?php if ( 'on' === $show_categories ) : ?>
															<p class="post-meta"><?php echo get_the_term_list( get_the_ID(), 'category', '', ', ' ); ?></p>
														<?php endif; ?>

														<?php

														if (Kidzou_Events::isTypeEvent()) {

															$location = Kidzou_Events::getEventDates(get_the_ID());

															$start 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['start_date']);
															$end 	= DateTime::createFromFormat('Y-m-d H:i:s', $location['end_date']);
															$formatted = '';
															// setlocale(LC_TIME, "fr_FR"); 

															$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
															$formatter->setPattern('EEEE dd MMMM');

															if ($start->format("Y-m-d") == $end->format("Y-m-d"))
																$formatted = __( 'Le ', 'Divi'). $formatter->format($start) ;
															else
																$formatted = __( 'Du ', 'Divi'). $formatter->format($start).__(' au ', 'Divi').$formatter->format($end);
														?>
															<?php echo '<div class="portfolio_dates"><i class="fa fa-calendar"></i>'.$formatted.'</div>'; ?>
														
														<?php } ?>

													</div> <!-- .et_pb_portfolio_item -->

									<?php
												//fin de test sur $index
												}

												$index++;

											//fin de boucle while
											}

											if ( 'on' === $show_pagination && ! is_search() ) {
												echo '</div> <!-- .et_pb_portfolio -->';

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

										$filters_html = '';
										$category_filters = '';
										// echo $module_class." ".stristr($module_class,'nofilter');
										if ($filter!='none' ) {

											$terms = get_terms( $filter ); //, $terms_args 

											$category_filters = '<ul class="clearfix">';
											
											foreach ( $terms as $term  ) {
												$category_filters .= sprintf( '<li class="et_pb_portfolio_filter"><a href="%3$s" title="%4$s">%2$s</a></li>',
													esc_attr( $term->slug ),
													esc_html( $term->name ),
													get_term_link( $term, $filter ),
													__('Voir tous les articles dans ').$term->name
												);
											}
											$category_filters .= '</ul>';

											$filters_html = '<div class="et_pb_portfolio_filters clearfix">%7$s</div><!-- .et_pb_portfolio_filters -->';
										}
											

										$output = sprintf(
											'<div%5$s class="%1$s%3$s%6$s">
												<div class="et_pb_filterable_portfolio ">
													'.$filters_html.'
												</div>
												%2$s
											%4$s',
											( 'on' === $fullwidth ? 'et_pb_portfolio' : 'et_pb_portfolio_grid clearfix' ),
											$posts,
											esc_attr( $class ),
											( ! $container_is_closed ? '</div> <!-- .et_pb_portfolio -->' : '' ),
											( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
											( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' ),
											$category_filters
										);
								

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
											$key = $keys[2];
											
											echo do_shortcode('[et_pb_signup admin_label="Subscribe" provider="mailchimp" mailchimp_list="'.$key.'" aweber_list="none" title="Inscrivez-vous à notre Newsletter" button_text="Inscrivez-vous " use_background_color="on" background_color="#ed0a71" background_layout="dark" text_orientation="left"]<p>Nous distribuons la newsletter 1 à 2 fois par mois, elle contient les meilleures recommandations de la communauté des parents Kidzou, ainsi que des jeux concours de temps en temps ! </p>[/et_pb_signup]'); 
										}
									?>

								<!-- </div> -->

							<!-- </div> -->

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

