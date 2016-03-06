<?php get_header(); ?>

<div id="main-content">
	<!-- <div class="container">
		<div id="content-area" class="clearfix">
			<div id="left-area"> -->

				<?php 

					global $wp_query;

					$name = '';

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

										$filter = 'none';
										$background_layout = 'light';
										$fullwidth = 'off';
										$module_id = '';
										$module_class = '';
										$render_votes = 'on';
										$show_categories = 'on';

										ob_start();

										if ( have_posts() ) {

											$posts = $wp_query->posts;

											$doShowVotes 	= ($render_votes=='on' ? true: false);
											$doShowCats 	= ($show_categories=='on' ? true: false);
 	
											render_react_portfolio(true, $posts, false, $doShowVotes, $doShowCats); 

											if ( ! is_search() ) { //'on' === $show_pagination && 
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
											
											$key = kz_mailchimp_key();
											
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

