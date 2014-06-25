<?php
/*
Template Name: Kidzou - Saisie d'&apos;&eacute;v&egrave;nements
*/
?>

<?php get_header(); ?>

<?php 
	
	global $wpdb;

	$table_events = $wpdb->prefix . "reallysimpleevents";
	$events;

	if (is_user_logged_in()) {
		global $current_user;
	  	get_currentuserinfo();

	  	$modified_by = $current_user->ID;

	  	$myrows = $wpdb->get_results( "SELECT * FROM $table_events e WHERE e.modified_by=$modified_by AND e.status='draft'" );

	  	echo '<script> head.ready( function() {var events='.json_encode( array("events" => $myrows) ).';kidzouEventsModule.model.setEvents(events);});</script>';
	
	
?>


<div id="main_content" class="clearfix<?php if ( $fullwidth ) echo ' fullwidth'; ?>">
	<div id="left_area">
	
			<div class="entry clearfix post">

				<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
					<div class="entry_content">
							<?php the_content(); ?>
					</div>
				<?php endwhile; // end of the loop. ?>	
					
					<div class='et-tabs-container et_slidertype_top_tabs events_container'>
						
						<ul class='et-tabs-control' data-bind="foreach: tabs">
							<li data-bind="attr : {'id' : 'tab_' + $index() },css: { active: $root.selectedTab() === $data }"><a href='#' data-bind="text: $data.name, click: function() {$root.selectTab($data)}"></a></li> 
						</ul> <!-- .et-tabs-control --> 
						<div class='et-tabs-content'>
							<div class='et-tabs-content-wrapper'>
								<div class='et_slidecontent' style="display:block;">
									
									<!-- ko if: selectedTab().options.status==='draft' -->
									<p>Retrouver ici tous vos &eacute;v&eacute;nements en mode <strong><cite>Brouillon</cite></strong>, i.e. ces &eacute;v&eacute;nements ne sont pas visibles par les internautes et l&apos;&eacute;quipe kidzou n&apos;en tient pas compte.<br/>
									Vos brouillons sont <strong>automatiquement sauvegard&eacute;s</strong> lorsque vous les modifiez !</p>
									<!-- /ko -->

									<!-- ko if: selectedTab().options.status==='requested' -->
									<p>Les &eacute;v&eacute;nements list&eacute;s ici sont en <strong>attente de validation</strong> par l&apos;&eacute;quipe Kidzou. Ils seront visibles par les internautes d&egrave;s que Kidzou les validera.<br/>
									Vous pouvez modifier les &eacute;v&eacute;nements list&eacute;s autant que vous le souhaitez tant qu&apos;ils sont en attente de validation.<br/>
									</p>
									<!-- /ko -->

									<!-- ko if: selectedTab().options.status==='approved' -->
									<p>Les &eacute;v&eacute;nements list&eacute;s ici ne sont plus modifiables, ils ont &eacute;t&eacute; publi&eacute;s sur le site</p>
									<!-- /ko -->

									<div class="events-list">

										<!-- ko if: selectedTab().options.status==='draft' -->
										
										<article data-bind="click: function () { $root.openBox($element) }" class="selectableEvent shadow-light events entry clearfix">

											<div class="entry-thumbnail">
												<span class="bigplus">+</span>
											</div><!-- .entry-thumbnail -->

											<header class="entry-header">
												<h2 class="entry-title">
													Ajoutez votre &eacute;v&eacute;nement
												</h2>												
											</header><!-- .entry-header -->

										</article>

										<!-- /ko -->
										
										<!-- ko foreach: { data : eventsList, as : 'event'} -->

											<article data-bind="attr: { 'id' : function(){'event_' + event.id()} } , click: function () { $root.openBox($element, $index() ) }" class="selectableEvent shadow-light events entry clearfix">

												<div class="entry-thumbnail">
													<img data-bind="attr:{src: event.image}" />
												</div><!-- .entry-thumbnail -->

												<header class="entry-header">
													<h2 class="entry-title" data-bind="text: event.title"></h2>
													<span><span class="booticon-time"></span><!-- ko text: $root.eventDatesFormatter(event) --><!-- /ko --></span>
													<span><span class="booticon-map-marker"></span><!-- ko text: event.venue --><!-- /ko --></span>
												</header><!-- .entry-header -->

											</article>

										<!-- /ko -->

									</div>
									
								</div> 
								<div class='et_slidecontent'>
									<p>Suspendisse elementum tincidunt mi, non dictum nibh molestie a. In placerat rutrum felis, eu lacinia nunc eleifend vitae. Pellentesque vitae porttitor mi. Nulla lobortis, justo nec eleifend cursus, dolor velit accumsan tortor, non euismod metus lorem in mauris. In non ultrices est. Etiam at leo quam.</p>
								</div> 
								<div class='et_slidecontent'>
									<p>Integer a lorem vel nisl vestibulum feugiat. Nulla rhoncus tellus quis lorem ullamcorper tempor. Proin aliquam feugiat pharetra. Ut vel massa ut mauris bibendum euismod. Sed rutrum placerat lacus eget dignissim. Nullam rhoncus aliquet blandit. Fusce diam enim, aliquet eu cursus molestie, vulputate et turpis.</p>
								</div>
							</div>
						</div>
					</div> <!-- .tabs-left -->
				
					
				<div class='garage' style="display:none;">
					<div class='action_box' id="action_box" >

						<!--span class="close" data-bind="click: function(){ closeBox($element)}">X</span-->

						<form class="events_form" >
						    <ul>
						        <li>
						        	<br/>
						             <h2>Editez votre &eacute;v&eacute;nement</h2>
						             <span class="warning" id="serverMessage" style="margin-left:1em;"></span>
						        </li>
						        <li>
						        	<span class="required_notification">* D&eacute;note un champ obligatoire</span>
						        	<p class="steps">
										<span data-bind="css: {'activeStep' : formStep()===1}, click: editPlace"><span class="booticon-map-marker"></span>Lieu</span>
										<span data-bind="css: {'activeStep' : formStep()===2}, click: editDates"><span class="booticon-time"></span>Dates</span>	
										<span data-bind="css: {'activeStep' : formStep()===3}, click: editDesc"><span class="booticon-edit"></span>D&eacute;crivez votre &eacute;v&eacute;nement</span>	
									</p>
						        </li>
						        <!-- ko if: formStep()==1 -->
									<!-- ko ifnot: customPlace() -->
									<li>
										<label for="place">Lieu de l'&eacute;v&eacute;nement:</label>
										<a href="#" data-bind="click: displayCustomPlaceForm">Vous ne trouvez pas votre bonheur dans cette liste?</a><br/>
										<input type="hidden" name="place" data-bind="disable: isPublished(), placecomplete:{
																						placeholderText: 'Ou cela se passe-t-il ?',
																						minimumInputLength: 2,
																						allowClear:true,
																					    requestParams: {
																					        types: ['establishment']
																					    }}, event: {'placecomplete:selected':completePlace}" style="width:300px;"/>
										
									</li>
									<!-- /ko -->
									<!-- ko if: customPlace() -->
									<li>
										<label for="name">Nom du lieu:</label>
										<a href="#" data-bind="click: displayGooglePlaceForm">Revenir a la recherche Google</a><br/>
										<input type="text" name="name" placeholder="Ex: chez Gaspard" data-bind="disable: isPublished(),value: eventData().place().venue" required>

									</li>
									<li>
										<label for="address">Adresse:</label>
										<input type="text" name="address" placeholder="Ex: 13 Boulevard Louis XIV 59800 Lille" data-bind="disable: isPublished(),value: eventData().place().address" required>
									</li>
									<li>
										<label for="website">Site web:</label>
										<input type="text" name="website" placeholder="Ex: http://www.kidzou.fr" data-bind="disable: isPublished(),value: eventData().place().website" >
									</li>
									<li>
										<label for="phone_number">Tel:</label>
										<input type="text" name="phone_number" placeholder="Ex : 03 20 30 40 50" data-bind="disable: isPublished(),value: eventData().place().phone_number" >
									</li>
									
										
									<!-- /ko -->
								<!-- /ko -->
								<!-- ko if: formStep()==2 -->
									<li>
										<label for="start_date">Date de d&eacute;but:</label>
						            	<input type="text" name="start_date"  placeholder="Ex : 30 Janvier" data-bind="disable: isPublished(),datepicker: eventData().formattedStartDate, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
						            	<span data-bind="validationMessage: eventData().formattedStartDate" class="form_hint"></span>
									</li>
									<li>
										<label for="end_date">Date de fin</label>
						            	<input type="text" name="end_date"  placeholder="Ex : 30 Janvier" data-bind="disable: isPublished(),datepicker: eventData().formattedEndDate, datepickerOptions: { dateFormat: 'dd MM yy' }" required />
										<em data-bind="if: eventData().eventDuration()!==''">(<span data-bind="text: eventData().eventDuration"></span>)</em>
										<span data-bind="validationMessage: eventData().formattedEndDate" class="form_hint"></span>
									</li>
									
							
								<!-- /ko -->
								<!-- ko if: formStep()==3 -->
									<li>
										<label for="title">Titre:</label>
						            	<input type="text" name="title"  placeholder="Le titre de l'événement" data-bind="disable: isPublished(),value: eventData().title" required />
									</li>
									<li>
										<label for="image">Image:</label>
						            	<input type="file" id="file" name="image" accept="image/*" data-bind="disable: isPublished(),file: eventMedia().imageFile, target: eventData().image, fileObjectURL: eventMedia().imageObjectURL, fileBinaryData: eventMedia().imageBinary" />
										<br/><em>Pour un rendu optimal, votre image doit &ecirc;tre au format 320px * 240px</em>
									</li>
									
									<!-- ko if: eventData().image()!=='' -->
									<li>
										<label for="thumb">Miniature</label>
										<img class="thumb_download" name="thumb" data-bind="attr: { src: eventData().image }"/>
									</li>
									<!-- /ko -->
									<li>
										<label for="description">Description:</label>
						            	<textarea name="description" data-bind="disable: isPublished(),value: eventData().extra_info" rows="15" placeholder="Entrez ici une description de l'événement" required></textarea>
									</li>
									
								<!-- /ko -->
								</ul>

								<div class="actions">

								<!-- ko if: isDraft() -->
						       
						        	<div class="one_third">

						        		
						        		<!-- ko if: isFormComplete() -->
											<p class="inline_block"><a class="readmore" href="#" data-bind="click: function() {$root.requestPublish()}">Demander la publication</a></p>
											<p>
												L&apos;&eacute;quipe de Kidzou v&eacute;rifiera le contenu de votre &eacute;v&eacute;nement et proc&eacute;dera &agrave; la publication dans les meilleurs d&eacute;lais 
											</p>
										<!-- /ko -->
										<!-- ko ifnot: isFormComplete() -->
											<p><em>Votre formulaire n&apos;est pas complet ou comporte des erreurs.<br/><strong>Vous ne pouvez pas en demander la publication pour l&apos;instant</strong></em></p>
										<!-- /ko -->
										
									</div>
									
									<div class="one_third">

										<!-- ko if: eventData().id()>0 -->
										<p class="inline_block"><a class="readmore" href="#" data-bind="click: function() {$root.duplicateEvent()}">Dupliquer ce brouillon</a></p>
										<p>
											<em>Une copie de ce brouillon sera cr&eacute;&eacute;e. Vous pouvez modifier cet &eacute;v&eacute;nement ind&eacute;pendamment de sa copie</em>
										</p>
										<!-- /ko -->
									</div>
									<div class="one_third last">
										<!-- ko if: eventData().id()>0 -->
										<p class="inline_block"><a class="readmore" href="#" data-bind="click: function() {$root.removeEvent()}">Supprimer ce brouillon</a></p>
										<p>
											<em>Toutes les donn&eacute;es de cet &eacute;v&eacute;nement seront effac&eacute;es : Attention, la suppression est irr&eacute;versible</em>
										</p>
										<!-- /ko -->
									</div>

								<!-- /ko -->

								<!-- ko if: isRequested() -->
									<div class="one_half">
										<p><em>Vous pouvez continuer &agrave; modifier l&apos;&eacute;v&eacute;nement tant qu&apos;il n&apos;est pas publi&eacute;</em></p>
									</div>
									<div class="one_half last">
										<p class="inline_block"><a class="readmore" href="#" data-bind="click: removeEvent">Supprimer cet &eacute;v&eacute;nement</a></p>
										<p>
											<em>Toutes les donn&eacute;es de cet &eacute;v&eacute;nement seront effac&eacute;es : Attention, la suppression est irr&eacute;versible</em>
										</p>
									</div>

								<!-- /ko -->
									
						        </div>
						    
						</form>							
						
						
					</div> <!-- .action_box -->

				</div>
				

				<?php wp_link_pages(array('before' => '<p><strong>'.esc_attr__('Pages','Trim').':</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
				<?php edit_post_link(esc_attr__('Edit this page','Trim')); ?>
			
			</div>


		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>

	</div> <!-- end #left_area -->

	<!--?php if ( ! $fullwidth ) get_sidebar(); ?-->

</div> <!-- end #main_content -->

<?php } else {?>

	<div id="main_content" class="clearfix<?php if ( $fullwidth ) echo ' fullwidth'; ?>">

		<div id="left_area">
	
			<div class="entry clearfix post">
				Cette page est uniquement accessible aux utilisateurs identifi&eacutes;, merci de vous connecter pour publier vos &eacute;v&eacute;nements !
			</div>

		</div>

	</div>

<?php } ?>

<?php get_footer(); ?>









