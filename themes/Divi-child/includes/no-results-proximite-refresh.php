<div id="main-content" class="entry">
<!--If no results are found-->

	<h1><i class="fa pull-left fa-exclamation-circle"></i><?php esc_html_e('Nous ne trouvons rien &agrave; proximit&eacute; imm&eacute;diate de vous...','Divi'); ?></h1>
	<br/>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Contenu", "A Proximite/Affinage", <?php echo Kidzou_Geo::is_request_geolocalized(); ?> , 0);
	});
		
	</script>

</div>
<!--End if no results are found-->