
<div class="wrap" >

	<div id="icon-themes" class="icon32"><br></div>
	<h2 class="nav-tab-wrapper">
		<a class='nav-tab' href='#' data-label="listEvents" 	data-bind="click: $root.tabs">Gestion des &eacute;v&egrave;nements</a>
		<a class='nav-tab' href='#' data-label="editCustomer"  	data-bind="click: $root.tabs">Edition client</a>
		<a class='nav-tab' href='#' data-label="listUsers" 		data-bind="click: $root.tabs">Gestion des utilisateurs</a>
	</h2>

	<div class="metabox-holder">

		<!-- ko with: $root.message() -->
		<div data-bind="css: $root.message().messageClass">
			<span data-bind="html: $root.message().messageContent"></span>
		</div>
		<!-- /ko -->

		<form class="editform">
		
			<input type="hidden" name="filterByCustomer" data-bind="value: selectedClient, event: {'select2-close':selectClient, 'select2-clearing': resetSelectedClient  },select2: { 
																						allowClear: true,
																						placeholder: 'Filtrer par client',
            																			id : selectedClientId, 
            																			data : clients,
            																			initSelection: initSelectedClient }" style="width: 300px">
           <input type="submit" class="button-primary" data-bind="click: doNewClient" value="Ajouter un client" />

		</form>

		<!-- ko if: $root.currentTab() == 'editCustomer' -->
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
								<input data-bind="value: name, enable: $root.editMode" name="name" />
								<label for="fiche">Fiche associ&eacute;e</label>
								<input type="hidden" name="fiche" data-bind="value: $root.selectedConnection, event: {'select2-close': $root.selectConnection, 'select2-clearing': $root.resetSelectedConnection  }, select2: { multiple: false, 
					            																			minimumInputLength: 2, 
					            																			id : $root.selectedConnectionId, 
					            																			query: $root.queryConnections, 
					            																			initSelection: $root.initSelectedConnection, 
					            																			formatResult : $root.formatConnection, 
					            																			formatSelection : $root.formatConnection }, enable: $root.editMode" style="width: 300px">
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
		<!-- /ko -->

		<!-- ko if: $root.currentTab() == 'listUsers' -->
		<div class="postbox-container" data-bind="with: chosenClientData()">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Gestion des utilisateurs du client</span></h3>
					<div class="inside">

						<form data-bind="submit: $root.doSaveUsers" class="editform">

							<fieldset>
 								<legend>Saisie des &eacute;v&egrave;nements</legend>     																			
								<label for="users">
									Utilisateurs <strong>principaux</strong> autoris&eacute;s &agrave; saisir des &eacute;v&egrave;nements<br/>
									<em>Ces utilisateurs ont le droit de g&eacute;rer les &eacute;v&egrave;nements cr&eacute;es par les utilisateurs secondaires</em>
								</label>
								<input type="hidden" name="users" data-bind="value: $root.selectedUsers, select2: { multiple: true, 
							            																			minimumInputLength: 2, 
							            																			id : $root.selectedUserId, 
							            																			query: $root.queryUsers, 
							            																			initSelection: $root.initSelectedUsers, 
							            																			formatResult : $root.formatUser, 
							            																			formatSelection : $root.formatUser }, enable: $root.editMode" style="width: 300px">
							    <br/><br/>																		
								<label for="secondusers">Utilisateurs <strong>secondaires</strong> autoris&eacute;s &agrave; saisir des &eacute;v&egrave;nements</label>
								<input type="hidden" name="secondusers" data-bind="value: $root.selectedSecondUsers, select2: { multiple: true, 
							            																			minimumInputLength: 2, 
							            																			id : $root.selectedUserId, 
							            																			query: $root.queryUsers, 
							            																			initSelection: $root.initSelectedSecondUsers, 
							            																			formatResult : $root.formatUser, 
							            																			formatSelection : $root.formatUser }, enable: $root.editMode" style="width: 300px">
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
		<div class="postbox-container" data-bind="with: !chosenClientData()">
			<p>
			<em>S&eacute;lectionnez un client pour l&apos;&eacute;diter !</em>
			</p>
		</div>
		<!-- /ko -->

		<!-- ko if: $root.currentTab() == 'listEvents' -->
		<div class="postbox-container"  data-bind="with: chosenClientData()" style="width:100%;">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='handle'><span>Ev&egrave;nements du client</span></h3>
					<div class="inside" >

						<!-- ko if: $root.filtering() -->
						<span class="booticon-filter"></span>&nbsp;<a href="#" data-bind="click: $root.getAllEvents">Supprimer les filtres</a>&nbsp;|&nbsp;
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

						<form > <!-- data-bind="submit: $root.doUpdateEvents" class="editform"-->

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
								      <th>Detach.</th>
								    </thead>
								    <tbody>

								    <!-- ko foreach: $root.chosenClientEvents() -->
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
										<td data-bind="text: $root.chosenClientData().name()"></td>
										<td style="min-width:7%;">&nbsp;<span class="option"><span class="booticon-delete" data-bind="click: $root.detachEvent"></span></span></td>
									</tr>
									<!-- /ko -->
									</tbody>
									<tfoot data-bind="if: $root.isMoreEvents()">
								      <tr>
								      	<td colspan="6">
								      		<br/>
								      		<input type="submit" style="width: 100%;" data-bind="click:$root.moreEvents" class="button-secondary" value="Plus d&apos;&eacute;v&egrave;nements" />
								      	</td>
								      </tr>
								    </tfoot>
							</table>
							</fieldset>
							<!-- input type="submit" class="button-primary" value="Mettre &agrave; jour" data-bind="enable:$root.releaseUpdateEventsButton()"/ -->
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
		            																			initSelection: $root.initSelectedEvents, 
		            																			formatResult : $root.formatEvent, 
		            																			formatSelection : $root.formatEvent }" style="width: 100%">

							</fieldset>
							<br/>
							<input type="submit" class="button-primary" value="Ajouter" data-bind="enable:$root.releaseAttachEventButton()"/>
						
						</form>

					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->
		<!-- /ko -->

	</div><!-- metabox-holder -->

</div><!-- wrap -->




