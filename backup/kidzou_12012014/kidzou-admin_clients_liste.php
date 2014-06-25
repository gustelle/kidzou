<?php 
	
	$pageURL = 'admin.php?page=clients';
	$liste	= $wpdb->get_results( "SELECT * FROM $table_clients"  );

?>
	<div class="postbox-container" style="width:49%">
	<div class="meta-box-sortables ui-sortable">
		<div class="postbox">
			<div class="handlediv" title="Cliquer pour inverser."><br /></div>
			<h3 class='hndle'><span>Liste des clients</span></h3>
			<div class="inside">
				<ul>
<?php 

	foreach ( $liste as $client ) 
	{ 

?>
		<li><a href="<?php echo $pageURL; ?>&view_id=<?php echo $client->id; ?>"><?php echo $client->name; ?></a></li>

<?php } ?>
				</ul>
			</div>
		</div>
	</div>
</div>


