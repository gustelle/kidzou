
<div class="wrap" >

	<div class="metabox-holder">

		<!-- ko with: $root.message() -->
		<div data-bind="css: $root.message().messageClass">
			<span data-bind="html: $root.message().messageContent"></span>
		</div>
		<!-- /ko -->

		<div class="postbox-container" data-bind="with: publishRequests()">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Ev&egrave;nements &agrave; publier</span></h3>
					<div class="inside" >


					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->

		<div class="postbox-container" >
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Liste des clients</span></h3>
					<div class="inside" >

						<ul data-bind="foreach: clients" class="clients">
							<li data-bind="text: $data.name(),
				               click: $root.selectClient.bind($data)"></li>
						</ul>

						<div class="submitbox">
							<form data-bind="submit: $root.doNewClient" class="editform">
								<input type="submit" class="button-primary" value="Ajouter un client" />
							</form>
						</div>

					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->

		<div class="postbox-container" data-bind="with: chosenClientData()">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Edition client</span></h3>
					<div class="inside">

						<form data-bind="submit: $root.doSaveClient" class="editform">

							<fieldset>
 								<legend>Donn&eacute;s du client</legend>

								<label for="name">Nom du client</label>
								<input data-bind="value: client.name, enable: $root.editMode" name="name" />
								<label for="fiche">Fiche associ&eacute;e</label>
								<input type="hidden" name="fiche" data-bind="value: $root.chosenClientConnection, select2: { multiple: false, 
							            																			minimumInputLength: 2, 
							            																			id : $root.selectedConnectionId, 
							            																			query: $root.queryConnections, 
							            																			initSelection: $root.initSelectedConnection, 
							            																			formatResult : $root.formatConnectionResult, 
							            																			formatSelection : $root.formatConnectionSelection }, enable: $root.editMode" style="width: 300px">
							</fieldset>
							<fieldset>
 								<legend>Saisie des &eacute;v&egrave;nements</legend>     																			
								<label for="users">Utilisateurs autoris&eacute;s &agrave; saisir des &eacute;v&egrave;nements</label>
								<input type="hidden" name="users" data-bind="value: $root.chosenClientUsers, select2: { multiple: true, 
							            																			minimumInputLength: 2, 
							            																			id : $root.selectedUserId, 
							            																			query: $root.queryUsers, 
							            																			initSelection: $root.initSelectedUsers, 
							            																			formatResult : $root.formatUserResult, 
							            																			formatSelection : $root.formatUserSelection }, enable: $root.editMode" style="width: 300px">
							</fieldset>
							<p>
								<div class="submitbox">
									<a class="submitdelete deletion" href="#" data-bind="click:$root.deleteClient, visible: $root.editMode">Supprimer ce client</a>
									<input type="submit" class="button-primary" value="Enregistrer" data-bind="enable:$root.releaseSubmitButton, visible: $root.editMode" />
								     
								</div>
							</p>
						</form>
						<form class="editform">
							<input type="submit" class="button-primary" value="Mode Edition" data-bind="visible: $root.editMode()===false, click: $root.doEdit" />
						</form>
					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->


		<div class="postbox-container"  data-bind="with: chosenClientData()">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Ev&egrave;nements du client</span></h3>
					<div class="inside" >

						<span class="booticon-filter"></span>&nbsp;<a href="#" data-bind="click: $root.getAllEvents">Tous</a>&nbsp;|&nbsp;
						<!-- ko foreach: $root.eventsYears() -->
							<a href="#" data-bind="click: $root.filterEventsByYear.bind($data), text: $data"></a>&nbsp;|&nbsp;
						<!-- /ko -->
						<!-- ko if: $root.filtering() -->
							<!-- ko foreach: $root.eventsMonths() -->
								<a href="#" data-bind="click: $root.filterEventsByMonth.bind($data), text: $data"></a>&nbsp;|&nbsp;
							<!-- /ko -->
						<!-- /ko -->
						<br/><br/>

						<form data-bind="submit: $root.doUpdateEvents" class="editform">

						<fieldset>
							<legend>Mise &agrave; jour des statuts de publication des &eacute;v&egrave;nements :</legend>
							<br/>
							<table class="clients_events">
								
								    <thead>
								      <th>Pub.</th>
								      <th>Titre</th>
								      <th>Dates</th>
								      <th></th>
								    </thead>
								    <tbody>

								    <!-- ko foreach: $root.chosenClientEvents() -->
							  	
									<tr onmouseover="jQuery(this).find('.option').show();jQuery(this).css('background-color','#c6c6c6');" onmouseout="jQuery(this).find('.option').hide();jQuery(this).css('background-color','');">
										<td style="width:5%">
											<input type="checkbox" data-bind="attr: { value: $data.id }, checked: $data.checked">
										</td>
										<td style="min-width:35%" data-bind="text: $data.title, css: { draft: $data.validated()!=='1' }, click: $root.showEventDetails.bind($data)" class="event_title"></td>
										<td style="min-width:25%">
											<span data-bind="date: $data.start_date, 
														stringFormat: 'DD MMM YYYY', 
														dateFormat: 'YYYY-MM-DD HH:mm:ss'" ></span>&nbsp;>&nbsp;
											<span data-bind="date: $data.end_date, 
														stringFormat: 'DD MMM YYYY', 
														dateFormat: 'YYYY-MM-DD HH:mm:ss'" ></span>
										</td>
										<td style="min-width:7%;">&nbsp;<span class="option"><span class="booticon-delete" data-bind="click: $root.detachEvent.bind($data)"></span></span></td>
									</tr>

									<!-- /ko -->
									</tbody>
							</table>
							</fieldset>
							<input type="submit" class="button-primary" value="Mettre &agrave; jour" data-bind="enable:$root.releaseUpdateEventsButton()"/>
						</form>

						<form class="editform" data-bind="submit: $root.doAttachEvents">
						<br/>
							<fieldset>
								<legend>Ajoutez de nouveaux &eacute;v&egrave;nements au client :</legend>
								<label for="event">Ajoutez un ou plusieurs &eacute;v&egrave;nement(s)</label>
								<input type="hidden" name="event" data-bind="value: $root.attachedEvents, select2: { multiple: true, 
		            																			minimumInputLength: 2, 
		            																			id : $root.selectedEventId, 
		            																			query: $root.queryEvents, 
		            																			formatResult : $root.formatEventResult, 
		            																			formatSelection : $root.formatEventSelection }" style="width: 100%">

							</fieldset>
							<br/>
							<input type="submit" class="button-primary" value="Ajouter" data-bind="enable:$root.releaseAttachEventButton()"/>
						
						</form>

					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->


	</div><!-- metabox-holder -->

</div><!-- wrap -->


<script type="text/javascript">
	
	jQuery(document).ready(function() {

		
	
		jQuery("#fiche").select2({

			placeholder: "Selectionnez une fiche Connections",
	        minimumInputLength: 2,
	        initSelection : function (element, callback) {
	        	var value = element.val();
	        	var pieces = value.split("|");
	        	//initValFiche = pieces[0]; console.log("initValFiche " + initValFiche);
		        var data = {id: pieces[0], slug: pieces[1]};
		        callback(data);
		    },
		    id: function(e) { return e.id+"|"+e.slug; },
	        ajax: {
	            url: "<?php echo get_bloginfo('wpurl') ?>/api/connections/get_fiche_by_slug/",
	            dataType: 'json',
	            quietMillis: 100,
	            cache : false,
	            data: function (term, page) { // page is the one-based page number tracked by Select2
	                return {
	                    term: term
	                };
	            },
	            results: function (data, page) {
	                return {results: data.fiches};
	            }
	        },
	        formatResult : function (fiche) {	
		        return fiche.slug;
		    },
		    formatSelection : function (fiche) {
		      	return fiche.slug;
		    }		
			
		});



	});
</script>


