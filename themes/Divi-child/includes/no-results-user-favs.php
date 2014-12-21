<div id="main-content" class="entry" style='margin: 2em;'>
	
	<i class="fa fa-heart-o pull-left fa-2x"></i>
	<h1><?php esc_html_e('Vous n&apos;avez aucun favori ?','Divi'); ?></h1>
	<br/>

	<p><?php esc_html_e('Pour cr&eacute;er un favori, cliquez sur les petits coeurs roses','Divi'); ?></p>
	<br/><br/>

	<h3><?php esc_html_e('Voici quelques autres propositions :','Divi'); ?></h3>
	<p><?php esc_html_e('Commencez &agrave; saisir un mot, des suggestions apparaitront [dans ce cas, cliquez dessus], sinon aucune suggestion n&apos;apparait ou ne vous convient, vous pouvez lancer une recherche en cliquant sur "Rechercher"','Divi'); ?></p>
	<?php echo do_shortcode('[searchbox]'); ?>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Favori Utilisateur", "URL", window.location.pathname , 0);
	});
		
	</script>

</div>
<!--End if no results are found-->