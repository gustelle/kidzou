<div style="display:none;" id="event-details">
	
	<article class="entry events event-details et-tabs-container et_sliderfx_fade et_sliderauto_false et_slidertype_top_tabs">

		    <div class='et-tabs-content'>
				<div class='et-tabs-content-wrapper'>

				    <div data-content="info" class="content active et_slidecontent">
				 
				    	<header class="entry-header">
							<h2 class="entry-title" itemprop="name" data-bind="text:eventsModel.selectedEvent().title()">Cliquez sur une activit&eacute; de l&apos;agenda pour en d&eacute;couvrir les d&eacute;tails</h2>
							<span>
								<span class="booticon-time"></span><span data-bind="html: eventsModel.selectedEvent().formattedDates()"></span>
							</span>
							<span>
								<span class="booticon-map-marker"></span><span data-bind="html: eventsModel.selectedEvent().venue()"></span>&nbsp;|&nbsp;<a href="#" data-tab="map" class="tab"><span class="booticon-location"></span>Voir sur la carte</a>
							</span>
						</header><!-- .entry-header -->

						<section class="entry-content">
							<p data-bind="html: eventsModel.selectedEvent().content()">...</p> 
						</section><!-- .entry-content -->

						<p data-bind="visible: eventsModel.selectedEvent().link()!=''">
							<span class="booticon-share"></span><a data-bind="attr:{href: eventsModel.selectedEvent().link(), alt : eventsModel.selectedEvent().link_title(), title: eventsModel.selectedEvent().link_title()}, html:eventsModel.selectedEvent().link_title()">Lien</a>
						</p>
						<p data-bind="visible: eventsModel.selectedEvent().isPostAttached()">
							<strong>Voir aussi sur Kidzou : </strong><br/>
							<span class="booticon-share"></span><a data-bind="attr:{href: eventsModel.selectedEvent().post_href(), alt : eventsModel.selectedEvent().post_title(), title: eventsModel.selectedEvent().post_title()}, text: eventsModel.selectedEvent().post_title()">Lien</a>
						</p>

				    </div>

				    <div data-content="map" class="content et_slidecontent">
				    	<p>
					    	<a href="#" data-tab="info" class="tab"><span class="booticon-info"></span>Retour aux informations principales</a>
					    </p>
					    <div class="event-map">	</div><!-- .event-map -->
				    </div>

				</div><!-- et-tabs-content-wrapper -->
			</div><!-- et-tabs-content -->

	</article><!--article -->

</div><!--event-details -->


