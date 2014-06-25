<?php

	global $wpdb;

	$pageURL = 'admin.php?page=clients';

	$errorMsg 	= "";
	$updateMsg 	= "";

	//variable
	$name 			= "";
	$users 			= array( );
	$users_stringified = "";
	$connections_id	= 0;
	$connections_slug = "";
	$connections_stringified = "";
	$customer_id = 0;

	$table_clients = $wpdb->prefix . KZ_CLIENTS_TABLE_NAME;
	$table_clients_users = $wpdb->prefix . KZ_CLIENTS_USERS_TABLE_NAME;

	$disabled = false;

	//ajout = formsubmit
	//edition = edit_id
	//mise à jour = formsubmit + edit_id
	//suppression = formdelete + delete_id

	//If editing get values from database
	if(  (isset( $_GET['view_id'] ) && is_numeric( $_GET['view_id'] )) || 
		 (isset( $_POST['edit_id'] ) && is_numeric( $_POST['edit_id'] )) ) {

		if (isset( $_GET['view_id'] ) && is_numeric( $_GET['view_id'] )) {
			$customer_id = intval($_GET['view_id']) ;
			$pageURL .= "&view_id=" . $_GET['view_id'];
			$disabled = true; //mode view !
		}
		if (isset( $_POST['edit_id'] ) && is_numeric( $_POST['edit_id'] )) {
			$customer_id = intval($_POST['edit_id']) ;
			$pageURL .= "&edit_id=" . $_POST['edit_id'];
			$disabled = false; //mode edit !
		}

	} 

	//mode edition de données (préparaiton à la mise à jour)
	if(    !isset($_POST['formsubmit']) 
		&& !isset($_POST['formdelete'])  
		&& isset( $_GET['view_id'] )
		&& is_numeric( $_GET['view_id'] ) ) {

		//$customer_id = intval($_GET['edit_id']);
		
		$client 	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_clients WHERE id=%d" , $customer_id));

		$users_join = $wpdb->get_results(
			"SELECT u.id, u.user_login FROM $table_clients_users c, wp_users u WHERE c.customer_id=$customer_id AND c.user_id=u.id", ARRAY_A);

		foreach ($users_join as $key => $value) {
			if ($users_stringified!=="")
				$users_stringified .= ",";
			$users_stringified .= implode("|", $value);
		}

		$name 		= $client->name;

		$connections_id = $client->connections_id;
		if ($connections_id>0)
			$connections_slug =  kz_connections_to_slug($connections_id); 

		$connections_stringified = $connections_id."|".$connections_slug;

	}

	//Mise à jour
	else if( isset($_POST['formsubmit']) && $_POST['formsubmit'] == 1 ){

		//Setup vars from post
		if( isset( $_POST['name'] ) ) $name = $_POST['name'];
		if (isset($_POST['users'])) $users_pipe = explode(",", $_POST['users']);

		$users_stringified = $_POST['users'];
		$i=0;
		foreach ($users_pipe as $a_user) {
			$pieces = explode("|", $a_user);
			$users[$i] = $pieces[0];
			$i++;
		}

		if (count($users)<1 || !$users[0]>0)
			$errorMsg = "Au minimum un utilisateur par client !";

		if( isset( $_POST['fiche'] ) && intval($_POST['fiche'])>0 )
		{
			$connections_id = intval($_POST['fiche']);
			$connections_slug = kz_connections_to_slug($connections_id);
			$connections_stringified = $connections_id."|".$connections_slug;
		} 

		//create error msg if no title is provided
		if( $name === "" ) $errorMsg .= 'Merci de renseigner le nom du client<br/>';

		//If all is valid, add to our database
		if( $errorMsg === "" ){
				
			$table_clients_cols = array( 
				"name" => $name,
				"connections_id" => $connections_id );

			if( $customer_id!==null && $customer_id>0 ){

				//insertion
				
				$isInserted = $wpdb->update( $table_clients ,
						                     $table_clients_cols ,
											 array( 'ID' => $customer_id )
										   );

				//il faut faire un DIFF :
				//recolter la liste des users existants sur ce client
				//comparer à la liste des users passés dans le POST
				//supprimer, ajouter selon les cas
				$old_users = $wpdb->get_col("SELECT DISTINCT user_id FROM $table_clients_users WHERE customer_id = $customer_id");

				//boucle primaire
				foreach ($users as $a_user) {

					if (!is_null($old_users) && in_array($a_user, $old_users)) {
						//do nothing
					} else {
						//echo "insert user : " . $a_user;
						//insert row
						$users_cols = array( 
							"user_id" => $a_user,
							"customer_id" => $customer_id );
						
						//$wpdb->show_errors();
					    $isInserted = $wpdb->insert( $table_clients_users ,
								                     $users_cols,
								                     array( '%d' ) );
					    //$wpdb->print_error();
					}
				}

				if (!is_null($old_users)) {

					//boucle complémentaire:
					foreach ($old_users as $a_user) {
						//print_r($old_users);

						if (in_array($a_user, $users)) {
							//do nothing
						} else {
							//echo "delete user : " . $a_user;
							//delete row
							$users_cols = array( 
								"customer_id" => $customer_id,
								"user_id" => $a_user );
							
						    $wpdb->delete( $table_clients_users ,
									                     $users_cols,
									                     array( '%d', '%d') );

						    $isDeleted = true;
							
						}
					}

				}

			}else{ //new record

				//$wpdb->show_errors();
				
				$isInserted = $wpdb->insert( $table_clients , $table_clients_cols );

				$customer_id = $wpdb->insert_id; 

				//dans le cas d'une creation de client, il n'y a pas lieu de faire un update de la table clients_users
				//uniquement un insert
				foreach ($users as $user) {

					$users_cols = array( 
						"user_id" => $user,
						'customer_id' => $customer_id);
					
					//$wpdb->show_errors();
				    $isInserted = $wpdb->insert( $table_clients_users ,
							                     $users_cols  );
				    //$wpdb->print_error();
				}

				//$wpdb->print_error();
				
				//Horrible way to redirect! @TODO fix this rubbish...
				?>
			<script type="text/javascript">
			window.location= '<?php echo $pageURL; ?>' + "&msg=added&view_id=" + '<?php echo $customer_id; ?>';
			</script>
				<?php
				exit();
			}

			if( ! $isInserted && !$isDeleted ){
				$errorMsg .= "Impossible de cr&eacute;er le client";
			}else {
				$updateMsg .= "Client mis &agrave; jour : " . stripslashes( $name );
			}
		}
	}

	//Suppression 
	else if ( isset($_POST['formdelete']) && $_POST['formdelete'] == 1) {

		$customer_id = intval($_POST["delete_id"]);
		$disabled = false; //mode ajout !

		$cust_cols = array( "id" => $customer_id);

	    $wpdb->delete( $table_clients ,
	                     $cust_cols,
	                     array( '%d') );

	    $cust_cols = array( "customer_id" => $customer_id);

	    $wpdb->delete( $table_clients_users ,
	                     $cust_cols,
	                     array( '%d') );

		$updateMsg .= "Client Supprimé " ;
	}

?>
<div class="wrap" >

	<h2><?php echo ( isset( $_GET['edit_id'] ) ) ?  "Mise &agrave; jour" :  "Ajout:" ?></h2>
	<?php if( $errorMsg != "" ): ?>
		<div class="error">
			<p>
				<?php echo $errorMsg; ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if( isset( $_GET['msg'] ) || $updateMsg != "" ): ?>
		<div class="updated">
			<p>
				<strong><?php echo ( isset( $_GET['msg'] ) && $_GET['msg'] == 'added' ) ?  "Client mise &agrave; jour : " . stripslashes( $title ) : $updateMsg; ?></strong>
			</p>
		</div>
	<?php endif; ?>


	<div class="metabox-holder">
		<div class="postbox-container" style="width:49%;margin-right:1em;">
			<div class="meta-box-sortables ui-sortable">
				<div class="postbox"> 
					<div class="handlediv" title="Cliquer pour inverser."><br /></div>
					<h3 class='hndle'><span>Edition de clients</span></h3>
					<div class="inside">

						<form method="post" action="">
							
							<table class="form-table">
								<tbody>
									<tr>
										<th><label for="name">Nom de client</label></th>
										<td><input class="regular-text ltr" type="text" id="name" name="name" value="<?php echo stripslashes( $name ) ; ?>" 
											<?php 
											if ($disabled) { echo 'disabled';} 
											?>/>
										</td>
									</tr>
									<tr>
										<th><label for="fiche">Fiche (Connections)</label></th>
										<td>
											<input type="hidden" name="fiche" id="fiche" value="<?php echo $connections_stringified ; ?>" style="width:100%;" 
											<?php 
											if ($disabled) { echo 'disabled';} 
											?>/>
										</td>
									</tr>

									<tr>
										<th><label for="users">Utilisateurs autoris&eacute;s &agrave; cr&eacute;er des &eacute;v&egrave;nements</label></th>
										<td>
											<input type="hidden" name="users" id="users" value="<?php echo $users_stringified; ?>" style="width:100%;" 
											<?php 
											if ($disabled) { echo 'disabled';} 
											?>/>
										</td>
									</tr>
									
									<tr>
										<td colspan="2" class="submitbox">

										<?php if ( !isset($_POST['formsubmit']) && !isset($_GET['view_id'])) { ?>
											
											<input type="submit" class="button-primary" value="Ajout" />
											<input type="hidden" name="formsubmit" value="1"/>
										
										<?php } else if ( isset($_POST['formdelete']) ) { //on vient de supprimer un client ?>
											
											<input type="submit" class="button-primary" value="Ajout" />
											<input type="hidden" name="formsubmit" value="1"/>

										<?php } else if ( !isset($_POST['formsubmit']) && isset($_GET['view_id']) && !isset($_POST['edit_id'])) {?>
											<input type="submit" class="button-primary" value="Mode Edition" />
											<input type="hidden" name="edit_id" value="<?php echo $_GET['view_id'];?>"/>

										<?php } else  {?>

											<input type="submit" class="button-primary" value="Mise &agrave; jour" />

											<br/><br/>
											<a class="submitdelete deletion deletecustomer-confirm" href="#">Supprimer ce client</a>

											<input type="hidden" name="edit_id" value="<?php echo $_POST['edit_id'];?>"/>
											<input type="hidden" name="formsubmit" value="1"/>
										<?php } ?>
									</tr>

								</tbody>
							</table>
						</form>

						<form method="post" action="" id="deletecustomerform">
							<input type="hidden" name="delete_id" value="<?php echo $_POST['edit_id'];?>"/>
							<input type="hidden" name="formdelete" value="1"/>
						</form>

					</div><!-- inside -->
				</div><!-- postbox-->
			</div><!-- sortable-->
		</div><!-- container-->

		<?php require_once (plugin_dir_path( __FILE__ ) . '/kidzou-admin_clients_liste.php');  ?>

		<?php require_once (plugin_dir_path( __FILE__ ) . '/kidzou-admin_clients_events.php');  ?>

	</div><!-- metabox-holder -->

</div><!-- wrap -->

<script type="text/javascript">
	
	jQuery(document).ready(function() {

		jQuery("#users").select2({

			placeholder: "Selectionnez des utilisateurs",
	        minimumInputLength: 2,
	        multiple: true,
	        id: function(e) { return e.id+"|"+e.user_login; },
	        initSelection : function (element, callback) {
		        var data = [];
		        var value = element.val();
		        jQuery(value.split(",")).each(function () {
		        	var pieces = this.split("|");
		            data.push({id: pieces[0], user_login: pieces[1]});
		        });
		        callback(data);
		    },
	        ajax: {
	            url: "<?php echo get_bloginfo('wpurl') ?>/api/users/get_userinfo/",
	            dataType: 'json',
	            quietMillis: 100,
	            cache : false,
	            data: function (term, page) { // page is the one-based page number tracked by Select2
	                return {
	                    term: term,
	                    term_field: 'user_login'
	                };
	            },
	            results: function (data, page) {
	                return {results: data.status};
	            }
	        },
	        formatResult : function (user) {	
		        return user.user_login;
		    },
		    formatSelection : function (user) {
		      	return user.user_login;
		    }		
		});
	
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


		//dialogs
		vex.defaultOptions.className 			= 'vex-theme-default';
		vex.dialog.buttons.YES.text 			= 'Confirmer';
		vex.dialog.buttons.NO.text 				= 'Annuler';

		jQuery('.deletecustomer-confirm').click(function(){

			vex.dialog.confirm({
		            message: "<strong>Certain de vouloir supprimer ce client ? </strong><br/>Les &eacute;v&egrave;nements du client seront attribu&eacute;s &agrave; l&apos;administrateur !",
		            callback: function (data) {
		            	//console.log("Confirmation de la suppression");
		            	document.getElementById("deletecustomerform").submit();
		            }
		        });
		});

	});
</script>


