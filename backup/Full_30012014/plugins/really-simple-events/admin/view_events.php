<?php
	
	//import de events
	if ((isset($_FILES['import_events']['tmp_name'])&&($_FILES['import_events']['error'] == UPLOAD_ERR_OK))) {    
		
		$chemin_destination = plugin_dir_path( __FILE__ );     
		move_uploaded_file($_FILES['import_events']['tmp_name'], $chemin_destination.$_FILES['import_events']['name']);    
		require_once plugin_dir_path( __FILE__).'import_events.php';
		$results = do_import_events($_FILES['import_events']['name']);

		echo '<div class="updated"><p><strong>';
		echo '<ul><li> Insertions: '.$results['inserted'].'</li>';
		echo '<li> Modifications: '.$results['modified'].'</li>';
		echo '<li> Suppressions: '.$results['deleted'].'</li></ul>';
		echo '</strong></p></div>';

	}     

	global $wpdb;
	$table_name = $wpdb->prefix . HC_RSE_TABLE_NAME;

	if(isset($_GET['delete_id'] ) && is_numeric( $_GET['delete_id'] ) ){
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id=%d", $_GET['delete_id'] ) );
		?>
		<script type="text/javascript">
			window.location = "sadmin.php?page=hc_rse_event&msg=deleted";
		</script>
		<?php
		exit();
	}

	//selectionner un random pour être certain de ne pas
	//passer par le cache DB Reloaded
	$upcoming_events = $wpdb->get_results( "SELECT *, ".rand()." FROM $table_name WHERE ( start_date >= NOW() OR end_date >= NOW() ) ORDER BY start_date ASC" );
	$past_events = $wpdb->get_results( "SELECT *, ".rand()." FROM $table_name WHERE ( start_date < NOW() AND end_date < NOW() ) ORDER BY start_date DESC" );

	/**
	 * Given a WPDB result set for the events table, prints the events table body
	 * @param Object $events - WPDB result set
	 */
	function hs_rse_print_event_rows( $events = array() ){


		//If there's nothing to print, exit function
		if( ! is_array( $events ) || count( $events ) == 0 ) return;

		foreach( $events as $event ): ?>

			<tr>
				<td>
					<?php echo date( get_site_option( 'hc_rse_date_format', 'jS M Y' ) , strtotime( $event->start_date ) ); ?> 
				</td>
				<td>
					<?php echo ( strtotime( $event->end_date ) >= strtotime( $event->start_date ) ) ? date( get_site_option( 'hc_rse_date_format', 'jS M Y' ) , strtotime( $event->end_date ) ) : '&nbsp;' ; ?> 
				</td>
				<td>
					<span style="font-size:120% !important;"><?php echo apply_filters( 'the_content' , stripslashes( $event->title ) ); ?></span>
					<section class="hidden">
						<?php echo apply_filters( 'the_content' , stripslashes( $event->extra_info ) ); ?>
					</section>
				</td>
				<td>
					<?php echo ( $event->featured)==1 ? 'Featured' : '&nbsp;' ; ?> 
				</td>
				<td>
					<?php echo kz_connections_to_slug($event->connections_id); ?>
				</td>
				<td>
					<img src="<?php echo stripslashes( $event->image ) ?>" style="max-width:50px;"/>
				</td>
				<td class="actions">
					<a href="admin.php?page=hc_rse_add_event&edit_id=<?php echo $event->id; ?>"><?php _e( 'Edit' , 'hc_rse' ); ?></a>&nbsp;&nbsp;|&nbsp;
					<a class="hc_rse_delete" href="admin.php?page=hc_rse_event&delete_id=<?php echo $event->id; ?>"><?php _e( 'Delete' , 'hc_rse' ); ?></a>
				</td>
			</tr>
		<?php endforeach;
	}
?>
<div class="wrap">
	<h2 id="page-title"><?php _e( 'Events (Upcoming)' , 'hc_rse' ); ?></h2>
	<div class="updated hidden" id="msgbox">
		<p>
			<strong><?php _e( 'Event Deleted' , 'hc_rse' ); ?></strong>
		</p>
	</div>

	<style>
		#export_p {height: 20px; }
		#export_p, #import_p, #upload {display: none;}
		a[href$='.xls'], a[href$='.xlsx'] {
		    background: transparent url(<?php bloginfo('url'); ?>/wp-content/plugins/really-simple-events/css/images/excel.jpg) center left no-repeat;
		    background-size: 40px;
		    padding-left: 50px;
		}
	</style>

	<script>
		function doExportEvents() {
			jQuery('#export_p').hide(); //si le user avait deja genere un export et en genere un nouveau
			jQuery('#msgbox p strong').html('<p>Merci de patienter, l&apos;export est en cours...').removeClass('hidden');
			jQuery('#msgbox').removeClass('hidden');
			jQuery.getJSON("<?php bloginfo('wpurl'); ?>/api/events/export_events/", {},
				function(data) {
					console.log('data ' + JSON.stringify(data)) ;
					jQuery('#msgbox').addClass('hidden');
					jQuery('#export_p').show();
		        }
		    );
		}

		function doImportEvents() {
			jQuery('#import_p').show();
			jQuery('#upload').show();
		}
	</script>

	<div>
		<h3>Import / Export :</h3> 
		<input value="Exporter les &eacute;v&egrave;nements" type="submit" class="button-primary" onclick="doExportEvents();">
		<input value="Importer des &eacute;v&egrave;nements" type="submit" class="button-primary" onclick="doImportEvents();">
		<p id="export_p">
			<a href="<?php echo plugins_url(); ?>/kidzou/modules/json/events.xlsx"><strong>Export des &eacute;v&egrave;nements</strong></a>
		<p>
		<p id="import_p">
			Importer des &eacute;v&egrave;nements :
			<form method="post" action="" enctype="multipart/form-data" id="upload">        
		          <input type="file" name="import_events">    
		          <input type="submit" value="Importer le fichier">    
			</form>
			<a href="<?php echo plugins_url(); ?>/kidzou/modules/json/import_template.xlsx"><strong>Mod&egrave;le d'import</strong></a>
		</p>
		<p>&nbsp;</p>
	</div>


	<?php if( $past_events ): ?>		
		<div id="table-switcher">
			<?php _e( 'Upcoming Events' , 'hc_rse' ); ?>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#past-events"><?php _e( 'Past' , 'hc_rse' ); ?></a>
		</div>
	<?php endif; ?>
	<?php if( $upcoming_events ): ?>
		<table id="upcoming-events" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th><?php _e( 'Date' , 'hc_rse' ); ?></th>
					<th>Date Fin</th>
					<th><?php _e( 'Title' , 'hc_rse' ); ?></th>
					<th>Featured</th>
					<th>Fiche associ&eacute;</th>
					<th>Image</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php hs_rse_print_event_rows( $upcoming_events ); ?>
			</tbody>
		</table>
	<?php elseif( $past_events ): ?>
		<p id="no-upcoming"><?php _e( 'No upcoming events to show, go and ' , 'hc_rse' ); ?> <a href="admin.php?page=hc_rse_add_event"><?php _e( 'add one' , 'hc_rse'); ?></a>.
	<?php endif; ?>


	<p id="no-events-mgs" <?php if($past_events || $upcoming_events) echo 'class="hidden"';?>><?php _e( 'No events to show, go and ' , 'hc_rse' ); ?> <a href="admin.php?page=hc_rse_add_event"><?php _e( 'add one' , 'hc_rse' ); ?></a>.


	<?php if( $past_events ): ?>
		<table id="past-events" class="wp-list-table widefat fixed hidden">
			<thead>
				<tr>
					<th><?php _e( 'Date' , 'hc_rse' ); ?></th>
					<th>Date Fin</th>
					<th><?php _e( 'Title' , 'hc_rse' ); ?></th>
					<th>Featured</th>
					<th>Fiche associ&eacute;</th>
					<th>Image</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php hs_rse_print_event_rows( $past_events ); ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

