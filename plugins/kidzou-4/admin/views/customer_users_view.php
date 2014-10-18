<h3 class='handle'><span>Gestion des utilisateurs du client</span></h3>
<div class="inside">

	

		<fieldset>
			<legend>Saisie des contenus</legend>     																			
			<label for="users">
				Utilisateurs <strong>principaux</strong> autoris&eacute;s &agrave; saisir des contenus<br/>
				<em>Ces utilisateurs ont le droit de g&eacute;rer les contenus cr&eacute;es par les utilisateurs secondaires</em>
			</label>
			<input type="hidden" name="users" id="users" />
		    <br/><br/>																		
			<label for="secondusers">Utilisateurs <strong>secondaires</strong> autoris&eacute;s &agrave; saisir des contenus</label>
			<input type="hidden" name="secondusers" data-bind="value: $root.selectedSecondUsers, select2: { multiple: true, 
		            																			minimumInputLength: 2, 
		            																			data : kidzou_jsvars.main_users,
		            																			id : $root.selectedUserId, 
		            																			initSelection: $root.initSelectedSecondUsers, 
		            																			formatResult : $root.formatUser, 
		            																			formatSelection : $root.formatUser }" style="width: 70%">
		</fieldset>
		
	
	
</div><!-- inside -->