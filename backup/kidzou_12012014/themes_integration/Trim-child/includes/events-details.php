<div style="display:none;" id="event-details">
	
	<article class="entry events event-details et-tabs-container et_sliderfx_fade et_sliderauto_false et_slidertype_top_tabs">

		    <div class='et-tabs-content'>
				<div class='et-tabs-content-wrapper'>

				    <div data-content="info" class="content active et_slidecontent">
				 
				    	<header class="entry-header">
							<h2 class="entry-title" itemprop="name" data-bind="text:events.selectedEvent().title()">Cliquez sur une activit&eacute; de l&apos;agenda pour en d&eacute;couvrir les d&eacute;tails</h2>
							<span>
								<span class="booticon-time"></span><span data-bind="html: events.selectedEvent().formattedDates()"></span>
							</span>
							<span>
								<span class="booticon-map-marker"></span><span data-bind="html: events.selectedEvent().venue()"></span>&nbsp;|&nbsp;<a data-tab="map" class="tab"><span class="booticon-location"></span>Voir sur la carte</a>
							</span>
						</header><!-- .entry-header -->

						<section class="entry-content">
							<p data-bind="html: events.selectedEvent().content()">...</p> 
						</section><!-- .entry-content -->

						<p data-bind="visible: events.selectedEvent().link()!=''">
							<span class="booticon-share"></span><a data-bind="attr:{href: events.selectedEvent().link(), alt : events.selectedEvent().link_title(), title: events.selectedEvent().link_title()}, html:events.selectedEvent().link_title()">Lien</a>
						</p>
						<p data-bind="visible: events.selectedEvent().isPostAttached()">
							<strong>Voir aussi sur Kidzou : </strong><br/>
							<span class="booticon-share"></span><a data-bind="attr:{href: events.selectedEvent().post_href(), alt : events.selectedEvent().post_title(), title: events.selectedEvent().post_title()}, text: events.selectedEvent().post_title()">Lien</a>
						</p>

				    </div>

				    <div data-content="map" class="content et_slidecontent">
				    	<p>
					    	<a data-tab="info" class="tab"><span class="booticon-info"></span>Retour aux informations principales</a>
					    </p>
					    <div class="event-map">	</div><!-- .event-map -->
				    </div>

				</div><!-- et-tabs-content-wrapper -->
			</div><!-- et-tabs-content -->

	</article><!--article -->

</div><!--event-details -->


