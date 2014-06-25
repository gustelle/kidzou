<?php 
	

	$events = get_upcoming_events_7days_nogroup( ); 
	
	include(locate_template('includes/events-list.php'));
	include(locate_template('includes/events-details.php'));

?>


		
	
