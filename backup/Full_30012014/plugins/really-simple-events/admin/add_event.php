<?php

	global $wpdb;

	$pageURL = 'admin.php?page=hc_rse_add_event';

	//Event variable
	$title 		= "";
	$startDate 	= "";
	$showTime 	= 0;
	$extraInfo 	= "";
	$link_full	= "";
	$link_href	= "";
	$event_connections_slug = "";
	$event_connections_id = 0;
	$image 			= "";
	$address_san 	= "";
	$venue 			= "";
	$featured 		= 0;
	$extra_venue 	= 0;
	$customer 		= "0:";
	$status 		= "draft"; //non publié par défaut
	$website 		= "";
	$phone_number	= "";
	$modified_by 	= 0; //admin par défaut
						 //dans cet écran, on n'écrit pas le modified_by car nous sommes dans une interface d'admin
						 //et modified_by correspond à un user qui saisi dans le site, en amont de cet écran

	$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;
	$table_clients = $wpdb->prefix . "clients";
	$dateFormatPattern = "#(\d{4})-(\d{2})-(\d{2})\s(\d{2})\:(\d{2})#";
	$errorMsg 	= "";
	$updateMsg 	= "";
	$modification_date = date( 'Y-m-d H:i:s' );

	//If editing get values from database
	if( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) ){
		$pageURL .= "&edit_id=" . $_GET['edit_id'];
		$event 		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id=%d" , $_GET['edit_id'] ) );
		$title 		= $event->title;
		$startDate 	= date( 'Y-m-d H:i' , strtotime( $event->start_date ) );
		$showTime 	= $event->show_time;
		$extraInfo 	= $event->extra_info;
		$customer 	= $event->customer . ":".$wpdb->get_var( $wpdb->prepare("SELECT c.name FROM $table_clients as c WHERE id=%d" , $event->customer ) ) ;
		$status		= $event->status;
		$website 	= $event->website;
		$phone_number = $event->phone_number;
		$modified_by	= $event->modified_by;

		// echo 'modified_by '.$modified_by;
		
		$link_tab 	= hc_rse_parse_link($event->link);
		$link_href	= $link_tab['link'];

		$event_connections_id = $event->connections_id;
		if ($event_connections_id>0) 
			$event_connections_slug =  kz_connections_to_slug($event_connections_id); 

		$venue 		= $event->venue;
		$image 		= $event->image;
		$endDate 	= date( 'Y-m-d H:i' , strtotime( $event->end_date ) );

		$address_san = kz_sanitize_text(  $event->address );	
		$featured 	= $event->featured;
		$extra_venue = $event->extra_venue;

	}

	//Check post for other variables
	if( isset($_POST['formsubmit']) && $_POST['formsubmit'] == 1 ){
		//Setup vars from post
		if( isset( $_POST['title'] ) ) $title = $_POST['title'];
		if( isset( $_POST['start_date'] ) ) $startDate = $_POST['start_date'];		
		$showTime = (isset( $_POST['show_time'] ) && $_POST['show_time'] === "on" ) ? 1 : 0;
		
		if( isset( $_POST['extra_info'] ) ) $extraInfo = $_POST['extra_info'];
		if( isset( $_POST['link'] ) ) $link_full = kz_rse_formatlink($_POST['link']); //pour des facilités de saisie à l'import, le texte est figé...
		
		//ajout process de validation des evenements par les users 
		$customer_id = 0;
		// echo $_POST['customer'];
		if( isset( $_POST['customer'] ) && intval($_POST['customer'])>0 ) {
			// $arr = explode(":", $_POST['customer'] ); //obligé de passer par là pour ovh (php 5.3)
			$customer_id = intval($_POST['customer']);
			$customer = $_POST['customer'].":".$wpdb->get_var( $wpdb->prepare("SELECT c.name FROM $table_clients as c WHERE id=%d" , $customer_id ) ) ;
		} else {
			//remise à zero du customer si il n'est pas passé dans le form
			$customer = "0:";
		}

		// echo $customer."/".$customer_id;
		
		//si l'evenement n'est pas coché pour publication, on reprend le statut précédent 
		//(dans le pire des cas, il est initialisé à "draft")
		if ( isset( $_POST['validated'] )  && ($_POST['validated'] === "on") )
				$status = "approved";
		//si pas posté mais précédememnt en "approved", il repasse en "requested" (il a été décoché )
		elseif ($status==="approved") 
				$status = "requested";

		//ajout connexion avec les fiches Connections
		if( isset( $_POST['event-fiche-id'] ) && intval($_POST['event-fiche-id'])>0 )
		{
			$event_connections_id = intval($_POST['event-fiche-id']);
			$event_connections_slug = kz_connections_to_slug($event_connections_id);
		} 
		else 
			$event_connections_id = 0;

		$extra_venue 		= (isset( $_POST['extra_venue'] ) && $_POST['extra_venue'] === "on" ) ? 1 : 0;
		
		if ($extra_venue)
		{
			if( isset( $_POST['event-nomlieu'] ) ) $venue = $_POST['event-nomlieu'];
			if( isset( $_POST['event-address'] ) ) $address_san = kz_sanitize_text($_POST['event-address']);
		}
		else
		{
			$venue 			= "";
			$address_san 	= "";
		}

		if( isset( $_POST['website'] ) ) $website = $_POST['website']; 
		if( isset( $_POST['phone_number'] ) ) $phone_number = $_POST['phone_number'];

		if( isset( $_POST['end_date'] ) &&  $_POST['end_date']!='' ) 
			$endDate = $_POST['end_date'];
		else {
			$start_day_arr 	= explode(" ", $_POST['start_date']);
			$start_day 		= $start_day_arr[0]." 00:00:00";
			$endtime		= new DateTime($start_day);
			$endtime->add(new DateInterval('PT23H59M59S'));//fin à 23:59:59 par défaut
			$endDate		= $endtime->format('Y-m-d H:i:s');
		}

		$featured 			= (isset( $_POST['featured'] ) && $_POST['featured'] === "on" ) ? 1 : 0;

		//echo 'ecriture customer '.$customer;
		if( isset( $_POST['event-image'] ) ) $image = $_POST['event-image'];

		//create error msg if no title is provided
		if( $title === "" ) $errorMsg .= __( 'Please enter a title' , 'hc_rse' ) . '<br/>';
		//create error msg if wrong date format
		if(  ! preg_match( $dateFormatPattern , $startDate ) ) $errorMsg .= __( 'Date/Time should be in the following format: yyyy-mm-dd HH:MM' , 'hc_rse' ) . '<br/>';
		if(  ! preg_match( $dateFormatPattern , $endDate ) ) $errorMsg .= __( 'Date/Time should be in the following format: yyyy-mm-dd HH:MM' , 'hc_rse' ) . '<br/>';

		//If all is valid, add to our database
		if( $errorMsg === "" ){
				
			$tableCols = array(
							    "title" 		=> $title ,
							    "start_date" 	=> $startDate,
							    "end_date" 		=> $endDate,
							    "show_time" 	=> $showTime,
							    "extra_info" 	=> $extraInfo,
							    "link" 			=> $link_full,
							    "connections_id"=> $event_connections_id,
							    "venue" 		=> $venue,
							    "address" 		=> $address_san,
							    "image" 		=> $image,
							    "featured" 		=> $featured,
							    "extra_venue"	=> $extra_venue,
							    "modification_date" => $modification_date,
							    "status" 		=> $status,
							    "website" 		=> $website,
							    "phone_number"	=> $phone_number,
							    "customer"		=> $customer_id
							  );

			if( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) ){
				
				$isInserted = $wpdb->update( $table_name ,
						                     $tableCols ,
											 array( 'ID' => $_GET['edit_id'] )
										   );
				// $wpdb->show_errors();
				// $wpdb->print_error();

				//pas beau, création de dépendance avec JSON API -- passer par un système de notifications
				//pour notifier JSON API que le cache doit être supprimé
				removeJSONCache( $_GET['edit_id']  );

			}else{ //new record
				$isInserted = $wpdb->insert( $table_name , $tableCols );
				// $wpdb->show_errors();
				// $wpdb->print_error();
				
				//Horrible way to redirect! @TODO fix this rubbish...
				?>
				<script type="text/javascript">
					window.location="<?php echo $pageURL . "&edit_id=" . $wpdb->insert_id . '&msg=added'?>";
				</script>
				<?php
				exit();
			}

			if( ! $isInserted ){
				$errorMsg .= __( 'Could not create event! HELP!!' , 'hc_rse' );
			}else{
				$updateMsg .= __( 'Event Updated: ' , 'hc_rse' ) . stripslashes( $title );
			}
		}
	}
?>
<div class="wrap">
	<h2><?php echo ( isset( $_GET['edit_id'] ) ) ?  __( 'Edit Event' , 'hc_rse' ) :  __( 'Add Event' , 'hc_rse' ); ?></h2>
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
				<strong><?php echo ( isset( $_GET['msg'] ) && $_GET['msg'] == 'added' ) ?  __( 'Event Added: ' , 'hc_rse' ) . stripslashes( $title ) : $updateMsg; ?></strong>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<input type="hidden" name="formsubmit" value="1"/>
		<?php if( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) ): ?>
			<input type="hidden" name="edit" value="1"/>
		<?php endif; ?>
		<table id="past-events" class="form-table">
			<tbody>
				<tr>
					<th><label for="title"><?php _e( 'Event Title' , 'hc_rse' ); ?></label></th>
					<td><input class="regular-text ltr" type="text" id="title" name="title" value="<?php echo stripslashes( $title ) ; ?>"/></td>
				</tr>
				<tr>
					<th><label for="featured">Featured</label></th>
					<td>
						<input class="" type="checkbox" id="featured" name="featured" <?php echo ( $featured == 1 ) ? 'checked="checked"' : ''; ?>/>
						<p class="description">Si coch&eacute; l'&eacute;v&egrave;nement sera mis en avant dans les r&eacute;sultats</p>
					</td>
				</tr>
				<tr>
					<th><label for="start_date"><?php _e( 'Event Date/Time' , 'hc_rse' ); ?></label></th>
					<td>
						<input class="regular-text ltr date_pick" type="text" id="start_date" name="start_date" value="<?php echo $startDate; ?>"/>
					</td>
				</tr>
				<tr>
					<th><label for="end_date">Date de fin</label></th>
					<td>
						<input class="regular-text ltr date_pick" type="text" id="end_date" name="end_date" value="<?php echo $endDate; ?>"/>
						<p class="description">Si blanc, l'&eacute;v&eacute;nement durera 1 seul jour</p>
					</td>
				</tr>
				<!--tr>
					<th><label for="show_time">< ?php _e( 'Show Event Time?' , 'hc_rse' ); ?></label></th>
					<td>
						<input class="" type="checkbox" id="show_time" name="show_time" < ?php echo ( $showTime == 1 ) ? 'checked="checked"' : ''; ?>/>
						<p class="description">< ?php _e( '(Keep un-checked if you are just concerned with dates and do not wish to show the time value for this event)' , 'hc_rse' ); ?></p>
					</td>
				</tr-->
				<tr style="border-top:1px solid #c6c6c6;">
					<th><label for="customer">Client</label></th>
					<td>
						<input type="hidden" name="customer" id="customer" value="<?php echo $customer ; ?>" style="width:300px"/>
					</td>
				</tr>
				<tr>
					<th><label for="validated">Publier cet &eacute;v&egrave;nement</label></th>
					<td>
						<input class="" type="checkbox" id="validated" name="validated" <?php echo ( $status == "approved" ) ? 'checked="checked"' : ''; ?>/>
						<p class="description">Si coch&eacute; l'&eacute;v&egrave;nement sera visible sur le site</p>
					</td>
				</tr> 
				<tr>
					<th><label for="modified_by">Saisi par</label></th>
					<td>
						<?php 
							$user_data = get_userdata($modified_by);
							if(!$user_data)
								echo 'Admin';
							else 
								echo $user_data->user_login; 
						?>
					</td>
				</tr>
				<tr>
					<th><label for="event-fiche-slug">Fiche (Connections)</label></th>
					<td>
						<input type="hidden" name="event-fiche-id" id="event-fiche-id" value="<?php echo $event_connections_id ; ?>" />
						<input class="regular-text ltr" type="text" id="event-fiche-slug" name="event-fiche-slug" value="<?php echo $event_connections_slug ; ?>" onblur="hideOrShowVenueOptions();"/>
						<p class="description">Si une fiche est utilis&eacute;e, l&apos;adresse de la fiche est utilis&eacute;e</p>
					</td>
				</tr>
				<tr style="display:none;" id="tr_extra_venue">
					<th><label for="show_time">Forcer l&apos;adresse</label></th>
					<td>
						<input class="" type="checkbox" id="extra_venue" name="extra_venue" <?php echo ( $extra_venue == 1 ) ? 'checked="checked"' : ''; ?>/>
						<p class="description">Cochez cette case si l&apos;&eacute;v&egrave;nement se passe ailleurs que le lieu renseign&eacute; dans la fiche</p>
					</td>
				</tr>
				<tr>
					<th><label for="event-nomlieu">Lieu (Ville/Quartier)</label></th>
					<td>
						<input class="regular-text ltr" type="text" id="event-nomlieu" name="event-nomlieu" value="<?php echo stripslashes($venue) ; ?>"/>
						<p class="description" id="comment-nomlieu" style="display:none;color:#c6c6c6"><strong>utilisation de la fiche</strong></p>
					</td>
				</tr>
				<tr>
					<th><label for="event-address">Adresse exacte</label></th>
					<td>
						<textarea id="event-address" name="event-address" rows="3" cols="50" maxlength="255">
							<?php echo stripslashes($address_san) ; ?>
						</textarea>
						<p class="description" id="comment-address" style="display:none;color:#c6c6c6"><strong>utilisation de la fiche</strong></p>
					</td>
				</tr>
				<tr>
					<th><label for="website">Site web</label></th>
					<td>
						<input class="regular-text ltr" type="text" id="website" name="website" value="<?php echo $website; ?>"/>
					</td>
				</tr>
				<tr>
					<th><label for="phone_number">T&eacute;l&eacute;phone</label></th>
					<td>
						<input class="regular-text ltr" type="text" id="phone_number" name="phone_number" value="<?php echo $phone_number; ?>"/>
					</td>
				</tr>
				<tr style="border-top:1px solid #c6c6c6;">
					<th><label for="event-image">Image / Affiche</label></th>
					<td>
						 <span class='upload'>
					        <input type='text' id='event-image' class='regular-text text-upload' name='event-image' value='<?php echo $image ; ?>'/>
					        <input type='button' class='button button-upload' value='Ajouter une image'/></br>
					        <img style='max-width: 300px; display: block;' src='<?php echo $image ; ?>' class='preview-upload' />
					    </span>
					</td>
				</tr>
				<tr>
					<th><label for="extra_info"><?php _e( 'Extra Event Info' , 'hc_rse' ); ?></label></th>
					<td>
						<?php wp_editor( stripslashes( $extraInfo ) , 'extra_info' , array( 'media_buttons' => true ) ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="link"><?php _e( 'Event Link' , 'hc_rse' ); ?></label></th>
					<td>
						<input class="regular-text ltr" type="text" id="link" name="link" value="<?php echo stripslashes( $link_href ) ; ?>"/>
						<p class="description">
							Entrez une URL commençant par http:// ou https://<br/>
						</p>
					</td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" class="button-primary" value="<?php echo ( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) ) ?  __( 'Update Event' , 'hc_rse' ) : __( 'Add Event' , 'hc_rse' ); ?>"/>
				</tr>
			</tbody>
		</table>
	</form>
</div>

<script>

	function hideOrShowVenueOptions() {
		
		//console.log("hideOrShowVenueOptions " + (jQuery("#event-fiche-slug").attr("value")==="") );

		if (jQuery("#event-fiche-slug").attr("value")==="")
		{
			//on n'utilise pas de fiche
			jQuery("#event-fiche-id").attr("value", 0); 
			jQuery("#tr_extra_venue").hide();

			//remettre à zero la checkbox
			jQuery("#extra_venue").attr("checked",true);
			syncExtraVenueFields();

		}
		else
		{
			//utilisaion d'une fiche
			jQuery("#tr_extra_venue").show();

			//verification de la checkbox
			if (jQuery("#event-nomlieu").attr("value")!=="")
			{
				jQuery("#extra_venue").attr("checked",true);
				syncExtraVenueFields();
			}
			else
			{
				jQuery("#extra_venue").attr("checked",false);
				syncExtraVenueFields();
			}
		}
		
	}

	function syncExtraVenueFields() {

		if (jQuery("#extra_venue").is(':checked'))
		{
			jQuery("#comment-nomlieu").hide();
			jQuery("#comment-address").hide();
			jQuery("#event-nomlieu").prop("disabled", false);
			jQuery("#event-address").prop("disabled", false);
		}
		else 
		{
			jQuery("#comment-nomlieu").show();
			jQuery("#comment-address").show();
			jQuery("#event-nomlieu").attr("value","");
			jQuery("#event-address").attr("value","");
			jQuery("#event-nomlieu").prop("disabled", true);
			jQuery("#event-address").prop("disabled", true);
		}
		
	}

	hideOrShowVenueOptions();

	jQuery(document).ready(function() {

		var clients = [];

		jQuery.getJSON("<?php echo site_url(); ?>/api/clients/getClients/")
			.done(function (d) {

			for (var i = d.clients.length - 1; i >= 0; i--) {
				clients.push({id : d.clients[i].id, text: d.clients[i].name});
			};

		});

		jQuery("#extra_venue").change(function() {
			syncExtraVenueFields();
		});

		//selection d'une fiche par son slug
		jQuery("#event-fiche-slug").autocomplete({
			minLength: 3,
			delay:400,
			source: function (request, response) {
				jQuery.ajax({
					url : "<?php echo site_url(); ?>/api/connections/get_fiche_by_slug/",
					dataType: "json",
					data: {	term: request.term	}, 
					success: function (data) {
		                response(jQuery.map(data.fiches, function (item) {
		                    return {
		                        label: item.slug,
		                        value: item.id      
		                    }
		                }));
		            }
		        });
			},
			select: function( event, ui ) { 
				jQuery("#event-fiche-id").attr("value", ui.item.value); 
				jQuery("#event-fiche-slug").attr("value", ui.item.label); 
				hideOrShowVenueOptions();
				return false; 
			}

		});

		jQuery("#customer").select2({

			placeholder: "Selectionnez un client",
			allowClear : true,
	        data : clients,
	        initSelection : function (element, callback) {
	        	var pieces = element.val().split(":");
	        	var data = {id: pieces[0], text: pieces[1]};
		        callback(data);
		    }
		});

		jQuery("#customer").on("select2-selecting", function(e) {
			jQuery.ajax({
					url : "<?php echo site_url() ?>/api/clients/getClientByID/",
					dataType: "json",
					data: {	id: e.val }, 
					success: function (data) {
						//si une fiche était saisie précédemment, on ne l'écrase pas !
						//car un evenement client peut avoir lieu ailleurs qu'a l'adresse habituelle
						var conn_id = jQuery("#event-fiche-id").attr("value");
						if (conn_id==="" || conn_id===null || parseInt(conn_id)===0) {
							jQuery("#event-fiche-id").attr("value", data.client.connections_id);
							jQuery("#event-fiche-slug").attr("value", data.client.connections_slug);
							hideOrShowVenueOptions();
						}
						
		            }
		        });
		});

		jQuery( ".button-upload" ).click( function() {
	        // Get the Text element.
	        var text = jQuery( this ).siblings( ".text-upload" );
	 
	        // Show WP Media Uploader popup
	        tb_show( 'Ajouter une image', 'media-upload.php?type=image&TB_iframe=true&post_id=0', false );
	 
	        // Re-define the global function 'send_to_editor'
	        // Define where the new value will be sent to
	        window.send_to_editor = function( html ) {
	            // Get the URL of new image
	            var src = jQuery( 'img', html ).attr( 'src' );
	            // Send this value to the Text field.
	            text.attr( 'value', src ).trigger( 'change' );
	            tb_remove(); // Then close the popup window
	        }
	        return false;
	    } );

	    jQuery( ".text-upload" ).bind( 'change', function() {
            // Get the value of current object
            var url = this.value;
            // Determine the Preview field
            var preview = jQuery( this ).siblings( ".preview-upload" );
            // Bind the value to Preview field
            jQuery( preview ).attr( 'src', url );
        } );

	});
    </script>

