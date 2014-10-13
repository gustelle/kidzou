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

						<div class="et_pb_column et_pb_column_3_4">

							<div class="et_pb_row_inner">

								<div class="et_pb_column et_pb_column_4_4 et_pb_column_inner">

									<?php
										// echo $GLOBALS['wp_query']->request; 

										global $kidzou_options;

										if ( isset($kidzou_options['pub_archive']) && $kidzou_options['pub_archive']<>'')
											echo $kidzou_options['pub_archive'];

										ob_start();
			
										format_fullwidth_portolio_items($wp_query, "on", "off");
	// echo $GLOBALS['wp_query']->request; 

										$posts = ob_get_clean();

										$background_layout = "light";
										$fullwidth = "off";
										$module_id = "";
										$module_class = "";
										$auto = "off";
										$auto_speed = 7000;
										$title = ($name != '' ? __('Tous nos articles dans la rubrique &laquo; ' .$name .' &raquo;' , 'Divi') : '&nbsp;');

										echo format_fullwidth_portfolio($background_layout, $fullwidth, $posts, $module_id, $module_class, $auto, $auto_speed, $title);

									?>

									
								</div>

							</div>
							<div class="et_pb_row_inner">

								<div class="et_pb_column et_pb_column_4_4 et_pb_column_inner">

									<?php echo do_shortcode('[et_pb_signup admin_label="Subscribe" provider="mailchimp" mailchimp_list="8874d33cf7" aweber_list="none" title="Inscrivez-vous à notre Newsletter" button_text="Inscrivez-vous " use_background_color="on" background_color="#ed0a71" background_layout="dark" text_orientation="left"]<p>Nous distribuons la newsletter 1 à 2 fois par mois, elle contient les meilleures recommandations de la communauté des parents Kidzou, ainsi que des jeux concours de temps en temps ! </p>[/et_pb_signup]'); ?>

								</div>

							</div>

						</div>

						<div class="et_pb_column et_pb_column_1_4">

							<?php get_sidebar(); ?>

						</div>

					</div> <!-- .et_pb_row -->
					

				</div>
				
				

			</div> <!-- #left-area -->

			
		<!--</div>  #content-area -->
	<!--<</div> .container -->
<!--</div>  #main-content -->

<?php get_footer(); ?>

