 <div class="box_margin_bottom box_centered"> 
<!--If no results are found-->

	<div>

	<i class="fa fa-frown-o pull-left fa-2x"></i>
	<h1><?php esc_html_e('Nous ne trouvons aucun r&eacute;sultat sur la m&eacute;tropole','Divi'); ?></h1>

	<p>
		<?php
		$url = site_url().'?s='.get_search_query(). '&region';
		// echo $url;
		echo sprintf(
				"<a title='%s' class='et_pb_more_button' href='%s'>%s</a>",
				__('Etendre la recherche &agrave; toute la r&eacute;gion','Divi'),
				$url,
				__('Etendre la recherche &agrave; toute la r&eacute;gion','Divi')
			);
		?>
	</p>

	</div>

	
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.kidzouTracker)
	  		kidzouTracker.trackEvent("Aucun Resultat", "Search", window.location.pathname , 0);
	});
		
	</script>

</div>
<!--End if no results are found -->