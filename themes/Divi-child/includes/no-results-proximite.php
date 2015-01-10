<div id="main-content" class="entry">
<!--If no results are found-->

	<h1><i class="fa pull-left fa-exclamation-circle"></i><?php esc_html_e('Pas grand chose trouv&eacute; &agrave; proximit&eacute;...','Divi'); ?></h1>
	<br/>

	<p>
		<?php 
		esc_html_e('Avez-vous tent&eacute; d&apos;&eacute;largir le champs des recherches ?','Divi');
		?>
	</p>
	<p>
		<?php
		echo sprintf(
			"<a href='%s' title='%s' class='et_pb_more_button'>%s</a>",
			"",
			__('Chercher plus loin','Divi'),
			__('Chercher plus loin','Divi')
		);
		?>
	</p>
	

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Contenu", "A Proximite/Initial", <?php echo Kidzou_Geo::is_request_geolocalized(); ?> , 0);
	});
		
	</script>

</div>
<!--End if no results are found-->