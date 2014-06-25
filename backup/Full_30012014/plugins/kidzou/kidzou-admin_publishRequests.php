
<div class="wrap" >

	<div class="metabox-holder">

		<!-- ko with: $root.message() -->
		<div data-bind="css: $root.message().messageClass">
			<span data-bind="html: $root.message().messageContent"></span>
		</div>
		<!-- /ko -->

		<div class="postbox-container" style="width:100%;">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Ev&egrave;nements &agrave; publier</span></h3>
					<div class="inside" >

						<!-- ko if: $root.filtering() -->
						<span class="booticon-filter"></span>&nbsp;<a href="#" data-bind="click: $root.getAllRequests">Supprimer les filtres</a>&nbsp;|&nbsp;
						<!-- /ko -->
						<!-- ko foreach: $root.eventsYears() -->
							<a href="#" data-bind="click: $root.filterEventsByYear.bind($data), text: $data"></a>&nbsp;|&nbsp;
						<!-- /ko -->
						<!-- ko if: $root.filtering() -->
							<!-- ko foreach: $root.eventsMonths() -->
								<a href="#" data-bind="click: $root.filterEventsByMonth.bind($data), text: $data"></a>&nbsp;|&nbsp;
							<!-- /ko -->
						<!-- /ko -->
						<br/><br/>

						<fieldset>
							<legend>Mise &agrave; jour des statuts de publication des &eacute;v&egrave;nements :</legend>
							<br/>
							<div class="Pager"></div>
							<div class="NoRecords"></div>
							<table class="widefat">
								
								    <thead>
								      <th>Pub.</th>
								      <th>Titre</th>
								      <th>Dates</th>
								      <th>Demandeur</th>
								      <th>Client</th>
								      
								    </thead>
								    <tbody>

								    <!-- ko foreach: $root.requests() -->
									<tr onmouseover="jQuery(this).find('.option').show();jQuery(this).css('background-color','#c6c6c6');" onmouseout="jQuery(this).find('.option').hide();jQuery(this).css('background-color','');">
										<td style="width:5%">
											<input type="checkbox" data-bind="attr: {value: $data.id()}, initCheckBox: $data.status()==='approved', click: $root.checkUncheckEvent.bind($data)">
										</td>
										<td style="min-width:35%" class="event_title">
											<a href="#" data-bind="text: $data.title(), css: { draft: $data.status()!='approved' }, attr : { href: $root.eventDetailsLink($data.id()) }" target="_blank"></a>
										</td>
										<td style="min-width:25%">
											<span data-bind="date: $data.start_date(), 
														stringFormat: 'DD MMM YYYY', 
														dateFormat: 'YYYY-MM-DD HH:mm:ss'" ></span>&nbsp;>&nbsp;
											<span data-bind="date: $data.end_date(), 
														stringFormat: 'DD MMM YYYY', 
														dateFormat: 'YYYY-MM-DD HH:mm:ss'" ></span>
										</td>
										<td data-bind="text: $data.modified_by.data.user_login()"></td>
										<td data-bind="text: $data.customer.data.name()"></td>
							
									</tr>
									<!-- /ko -->
									</tbody>
									<tfoot data-bind="if: $root.isMoreEvents()">
								      <tr>
								      	<td colspan="5">
								      		<br/>
								      		<input type="submit" style="width: 100%;" data-bind="click:$root.moreEvents" class="button-secondary" value="Plus d&apos;&eacute;v&egrave;nements" />
								      	</td>
								      </tr>
								    </tfoot>
							</table>
							</fieldset>
							

					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->

	</div><!-- metabox-holder -->

</div><!-- wrap -->





