

<!-- hover panel -->

	<aside class="newspan">

		<div>
			<h1>Inscrivez-vous &agrave; la newsletter</h1>
			<p>Nous distribuons la newsletter tous les 15 jours, elle pr&eacute;sente un r&eacute;sum&eacute; de tous les bons plans !</p>
			<form onsubmit="kidzou.trackEvent('Newsletter', 'Inscription','', 0);" action="http://kidzou.us2.list-manage.com/subscribe/post?u=a4602e81288a85bf4c061771b&amp;id=1b5be0ebf3" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
				<input type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="Adresse mail" required />
				<input type="submit" class="newsclickable kz-button" value="S&apos;inscrire" />
			</form>
			<br/>
			<input type="submit" id="no_newsletter" name="no_newsletter" class="kz-button-nothanks newsclickable" value="Je ne souhaite pas m&apos;inscrire &agrave; la newsletter" />
			<hr class="separator"/>
		</div>
		<h1>Suivez-nous sur les r&eacute;seaux ...</h1>
		<p>Nous nous efforcons de rester proche de vous!<br/>Suivez-nous sur :</p>
		<ul class="social-links">
			<li><a class="twitter" 	rel="author" 		href="<?php echo get_option('trim_twitter_url') ?>" 			title="Twitter">Suivez-nous sur Twitter</a></li>
			<li><a class="fb" 		rel="author"		href="<?php echo get_option('trim_facebook_url') ?>" 			title="Facebook">Suivez-nous sur Facebook</a></li>
			<li><a class="gplus" 	rel="author" 		href="https://plus.google.com/103958659862452997072?rel=author" title="Google+">Suivez-nous sur Google+</a></li>
			<li><a class="rss" 		rel="alternate" 	href="<?php echo get_option('trim_rss_url') ?>" 				title="Flux RSS">Suivez-nous par flux RSS</a></li>
		</ul>

	</aside>
	

<!-- /hover panel -->