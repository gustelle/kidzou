

<?php 

if ( function_exists( 'kz_events_teaser' ) ) {

	global $post;

	$events = kz_events_teaser(); 

	$ih = is_home();

	if (count($events)>0)
	{
		echo '<div id="events-management">
						<section id="events" class="'.( $ih ? "events-home" : "").'">
							<header>
								<h2>L&apos;agenda des prochains jours :</h2>
							</header>';

		foreach ($events as $event) {

			$post = $event;

			setup_postdata($event);

			get_template_part('includes/event'); 

			# code...
		}

		echo '  			<footer>
								<a class="readmore" href="'; echo kz_url(bloginfo('url').'/agenda'); echo '">L&apos;agenda de la semaine</a>
							</footer>
						</section><!-- end #events -->
					</div> <!-- end #event-management -->';

		wp_reset_postdata();
	}

}

?>

	


