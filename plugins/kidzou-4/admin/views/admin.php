<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Kidzou
 * @author    Guillaume <guillaume@kidzou.fr>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Kidzou
 */
?>

<?php
// if (isset($_POST['submit']))
// {

//  	$flush = trim($_POST['flush_rules']);   

//  	if ($flush==1)
//  	{

// 	    flush_rewrite_rules();

//  		//supprimer les transients relatifs aux metropoles
//  		//qui seront regenerés avec les nouvelles metropoles
//  		delete_transient( 'kz_get_national_metropoles' );
// 	    delete_transient( 'kz_default_metropole' );
// 	    delete_transient( 'kz_covered_metropoles_all_fields' );
// 	    delete_transient( 'kz_covered_metropoles' );

//  	}
// }

// global $kidzou_options;


?>

<!-- <div class="wrap">


	<form method="POST" action="< ?php echo $_SERVER['REQUEST_URI']; ?>" >

	 	<p>
	 		<input type="checkbox" value="1"  id="flush_rules" name="flush_rules">
	 		<span style="padding-left:5px;"><?php _e('Rafraichir les r&egrave;gles de permaliens.<br/><em>Cela est n&eacute;cessaire lorsque vous changez, ajoutez ou supprimez une m&eacute;tropole</em>','Kidzou'); ?></span>
	 	</p>

		 <input name="submit" id="submit" value="Mettre &agrave; jour" type="submit" class="button-primary">

	</form>

</div> -->


<div class="wrap" >

		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<div id="icon-themes" class="icon32"><br></div>
	<h2 class="nav-tab-wrapper">
		<a class='nav-tab' href='#' data-label="listEvents" 	data-bind="click: $root.tabs">Contenus du client</a>
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
		<div class="postbox-container" data-bind="with: $root.chosenClientData()">
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

							</fieldset>
		
							<p>
								<div class="submitbox">
									<a class="submitdelete deletion" href="#" data-bind="click:$root.deleteClient, visible: $root.editMode">Supprimer ce client</a>
									<input type="submit" class="button-primary" value="Enregistrer" data-bind="enable: $root.releaseSubmitButton, visible: $root.editMode" />
								     
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
 								<legend>Saisie des contenus</legend>     																			
								<label for="users">
									Utilisateurs <strong>principaux</strong> autoris&eacute;s &agrave; saisir des contenus<br/>
									<em>Ces utilisateurs ont le droit de g&eacute;rer les contenus cr&eacute;es par les utilisateurs secondaires</em>
								</label>
								<input type="hidden" name="users" data-bind="value: $root.selectedUsers, select2: { multiple: true, 
							            																			minimumInputLength: 2, 
							            																			id : $root.selectedUserId, 
							            																			query: $root.queryUsers, 
							            																			initSelection: $root.initSelectedUsers, 
							            																			formatResult : $root.formatUser, 
							            																			formatSelection : $root.formatUser }, enable: $root.editMode" style="width: 300px">
							    <br/><br/>																		
								<label for="secondusers">Utilisateurs <strong>secondaires</strong> autoris&eacute;s &agrave; saisir des contenus</label>
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
									<input type="submit" class="button-primary" value="Enregistrer" data-bind="enable: $root.releaseSubmitButton, visible: $root.editMode" />
								     
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
					<h3 class='handle'><span>Contenus associ&eacute;s au client</span></h3>
					<div class="inside" >

						<em>Filtrer les &eacute;v&eacute;nements par date : </em><br/>
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
							<legend>Les contenus suivants sont associ&eacute;s au client :</legend>
							<br/>
							<div class="Pager"></div>
							<div class="NoRecords"></div>
							<table class="widefat">
								
								    <thead>
								      <th>Pub.</th>
								      <th>Type</th>
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
											<input type="checkbox" data-bind="disable: function() {return true;}, attr: {value: $data.id()}, initCheckBox: $data.status()==='publish', click: $root.checkUncheckEvent.bind($data)">
										</td>
										<td style="width:10%">
											<span  data-bind="text: $data.type()"></span>
										</td>
										<td style="min-width:35%" class="event_title">
											<a href="#" data-bind="text: $data.title(), css: { draft: $data.status()!='publish' }, attr : { href: $root.eventDetailsLink($data.id()) }" target="_blank"></a>
										</td>
										<td style="min-width:25%">
											<!-- ko if: $data.custom_fields.kz_event_start_date -->
											<span data-bind="date: $data.custom_fields.kz_event_start_date(), 
														stringFormat: 'DD MMM YYYY', 
														dateFormat: 'YYYY-MM-DD HH:mm:ss'" ></span>&nbsp;>&nbsp;
											<span data-bind="date: $data.custom_fields.kz_event_end_date(), 
														stringFormat: 'DD MMM YYYY', 
														dateFormat: 'YYYY-MM-DD HH:mm:ss'" ></span>
											<!-- /ko -->
										</td>
										<td data-bind="text: $data.author.slug()"></td>
										<td>&nbsp;</td>
										<td style="min-width:7%;">&nbsp;<span class="option"><span class="booticon-delete" data-bind="click: $root.detachEvent"></span></span></td>
									</tr>
									<!-- /ko -->
									</tbody>
									<!--tfoot data-bind="if: $root.isMoreEvents()">
								      <tr>
								      	<td colspan="6">
								      		<br/>
								      		<input type="submit" style="width: 100%;" data-bind="click:$root.moreEvents" class="button-secondary" value="Plus d&apos;&eacute;v&egrave;nements" />
								      	</td>
								      </tr>
								    </tfoot-->
							</table>
							</fieldset>
							<!-- input type="submit" class="button-primary" value="Mettre &agrave; jour" data-bind="enable:$root.releaseUpdateEventsButton()"/ -->
						</form>

						<form class="editform" data-bind="submit: $root.doAttachEvents">
						<br/>
							<fieldset>
								<legend>Ajoutez de nouveaux contenus au client :</legend>
								<label for="event">Ajoutez un ou plusieurs contenu(s)</label>
								<input type="hidden" name="event" data-bind="value: $root.attachedEvents, select2: { multiple: true, 
		            																			minimumInputLength: 4,
		            																			ajax : {quietMillis : 400},
		            																			id : $root.selectedEventId, 
		            																			query: $root.queryEvents, 
		            																			initSelection: $root.initSelectedEvents, 
		            																			formatResult : $root.formatEvent, 
		            																			formatSelection : $root.formatEvent }" style="width: 100%">

							</fieldset>
							<br/>
							<input type="submit" class="button-primary" value="Ajouter" data-bind="enable: $root.releaseAttachEventButton"/>
						
						</form>

					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->
		<!-- /ko -->

	</div><!-- metabox-holder -->

</div><!-- wrap -->






