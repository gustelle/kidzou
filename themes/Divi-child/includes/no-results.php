 <div> 
<!--If no results are found-->

	
	<i class="fa fa-frown-o pull-left fa-2x"></i>
	<h1><?php esc_html_e('No Results Found','Divi'); ?></h1>
	<!-- p><?php esc_html_e('The page you requested could not be found. Try refining your search, or use the navigation above to locate the post.','Divi'); ?></p -->
	<br/>

	<h2><?php esc_html_e('Avez-vous accept&eacute; de vous localiser ?','Divi'); ?></h2>
	<p><?php esc_html_e('Nos contenus sont localis&eacute;s, si vous refusez de vous localiser, certains contenus vous seront invisibles. Il se peut &eacute;galement qu&apos;une erreur technique soit survenue pour vous localiser.','Divi'); ?></p>
	<p><?php esc_html_e('Vous pouvez forcer votre g&eacute;olocalisation en cliquant sur l&apos;une de nos m&eacute;tropoles ci-dessous:','Divi'); ?></p>
	<p>
		<?php
		$metropoles = Kidzou_GeoHelper::get_metropoles();
		foreach ($metropoles as $m) {
			echo sprintf("<a href='%s' title='%s' class='et_pb_more_button metropole'><i class='fa fa-map-marker pull-left fa-2x'></i>%s</a>", site_url().'/'.$m->slug, $m->name, $m->name);
		}
		?>
	</p>
	<br/><br/>

	<h3><?php esc_html_e('Utilisez notre moteur de recherche :','Divi'); ?></h3>
	<?php echo do_shortcode('[searchbox]'); ?>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Contenu", "URL", window.location.pathname , 0);
	});
		
	</script>

</div>
<!--End if no results are found -->