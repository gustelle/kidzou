<div id="main-content" class="entry">

	<?php $locator = new Kidzou_Geolocator(); ?>
<!--If no results are found-->

	<h1 class="centerme"><?php esc_html_e('Nous ne trouvons rien &agrave; proximit&eacute; imm&eacute;diate ...','Divi'); ?></h1>
	<br/>

	<p>
		<?php
		echo sprintf(
			"<a title='%s' class='et_pb_more_button load_more_results centerme'>%s</a>",
			__('Chercher plus loin','Divi'),
			__('Chercher plus loin','Divi')
		);
		?>
	</p>

	<!-- <hr class="et_pb_space et_pb_divider" /> -->

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Contenu", "A Proximite/Affinage", <?php echo $locator->is_request_geolocalized(); ?> , 0);
	});
		
	</script>

</div>
<!--End if no results are found-->