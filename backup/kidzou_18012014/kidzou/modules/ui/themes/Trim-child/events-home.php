<?php 
	$events = get_teaser_events_7days( ); 
	if (count($events)>0) { 
?>
<div id="events-management">
	
	<section id="events" class="events-home">

		<header>
			<h1>L&apos;agenda des prochains jours :</h1>
		</header>

		<?php get_template_part('includes/events', 'home'); ?>

		<footer>
			<a class="readmore" href="<?php  bloginfo('url'); ?>/agenda">L&apos;agenda de la semaine</a>
		</footer>

	</section><!-- end #events -->

</div> <!-- end #event-management -->
<?php } ?>