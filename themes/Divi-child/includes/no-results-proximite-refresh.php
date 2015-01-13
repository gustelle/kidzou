<div id="main-content" class="entry">
<!--If no results are found-->

	<h1 class="centerbox"><?php esc_html_e('Nous ne trouvons rien &agrave; proximit&eacute; imm&eacute;diate ...','Divi'); ?></h1>
	<br/>

	<p class="centerbox">
		<?php
		echo sprintf(
			"<a href='%s' title='%s' class='et_pb_more_button'>%s</a>",
			"",
			__('Chercher plus loin','Divi'),
			__('Chercher plus loin','Divi')
		);
		?>
	</p>

	<!-- <hr class="et_pb_space et_pb_divider" /> -->

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Contenu", "A Proximite/Affinage", <?php echo Kidzou_Geo::is_request_geolocalized(); ?> , 0);
	});
		
	</script>

</div>
<!--End if no results are found-->