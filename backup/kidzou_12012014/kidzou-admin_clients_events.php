
<?php 
	
	if( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) && !isset($_POST['formsubmit']) ){

		$pageURL = 'admin.php?page=hc_rse_add_event';
		$id = $_GET['edit_id'];
		$liste	= get_upcoming_events_by_customer(intval($id));

?>
	<div class="postbox-container" style="width:49%">
		<div class="meta-box-sortables ui-sortable">
			<div class="postbox">
				<div class="handlediv" title="Cliquer pour inverser."><br /></div>
				<h3 class='hndle'><span>Validation des &eacute;v&egrave;nements</span></h3>
				<div class="inside">
					<form method="post" action="">
							<input type="hidden" name="eventssubmit" value="1"/>
							<?php if( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) ): ?>
								<input type="hidden" name="edit" value="1"/>
							<?php endif; ?>
							<table class="form-table">
								<tbody>

										<?php 

										foreach ( $liste as $event ) 
										{ 

										?>
												<tr>
													<th><label for="validate">Validation</label></th>
													<td>
														<a href="<?php echo $pageURL; ?>&edit_id=<?php echo $event->id;?>"><?php echo $event->title; ?></a>
													</td>
												</tr>

										<?php 
										} 
										?>


									
									
									<tr>
										<td colspan="2"><input type="submit" class="button-primary" value="<?php echo ( isset( $_GET['edit_id'] ) && is_numeric( $_GET['edit_id'] ) ) ?  "Mise &agrave; jour" : "Ajout"; ?>"/>
									</tr>

								</tbody>
							</table>
						</form>

				</div>
			</div>
		</div>
	</div>

<?php 
	} 
?>