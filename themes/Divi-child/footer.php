<?php
if ( ! is_page_template( 'page-template-blank.php' ) ) : ?>

			<footer id="main-footer">
				<?php get_sidebar( 'footer' ); ?>


		<?php
			if ( has_nav_menu( 'footer-menu' ) ) : ?>

				<div id="et-footer-nav">
					<div class="container">
						<?php
							wp_nav_menu( array(
								'theme_location' => 'footer-menu',
								'depth'          => '1',
								'menu_class'     => 'bottom-nav',
								'container'      => '',
								'fallback_cb'    => '',
							) );
						?>
					</div>
				</div> <!-- #et-footer-nav -->

			<?php endif; ?>

				<div id="footer-bottom">
					<div class="container clearfix">
				<?php
					if ( false !== et_get_option( 'show_footer_social_icons', true ) ) {
						get_template_part( 'includes/social_icons', 'footer' );
					}
				?>

						<p id="footer-info"><i class="fa fa-copyright"></i><?php _e('Copyright Kidzou 2015','Divi'); ?></p>
					</div>	<!-- .container -->
				</div>
			</footer> <!-- #main-footer -->
		</div> <!-- #et-main-area -->

<?php endif; // ! is_page_template( 'page-template-blank.php' ) ?>

	</div> <!-- #page-container -->

	<?php wp_footer(); ?>

</body>

<!-- 
	this adds the class "js" to the <html> element when Javascript is enable
	@see https://css-tricks.com/snippets/javascript/css-for-when-javascript-is-enabled/
-->
<script type="text/javascript">
	document.documentElement.className = 'js';
</script>

<!-- 
	SiteLinks searchbox
	@see https://developers.google.com/structured-data/slsb-overview
-->
<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "WebSite",
  "url": "http://www.kidzou.fr/",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "http://www.kidzou.fr?s={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
</html>