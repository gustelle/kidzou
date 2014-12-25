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

						<p id="footer-info"><i class="fa fa-copyright"></i><?php _e('Copyright Kidzou 2014','Divi'); ?></p>
					</div>	<!-- .container -->
				</div>
			</footer> <!-- #main-footer -->
		</div> <!-- #et-main-area -->

<?php endif; // ! is_page_template( 'page-template-blank.php' ) ?>

	</div> <!-- #page-container -->

	<?php wp_footer(); ?>

<!-- </div> .habillage -->

<?php
	
	$css_per_js = ((bool)Kidzou_Utils::get_option('perf_activate',false)) ;
	if (!is_admin() && $css_per_js)
	{
		// global $wp_styles;
		echo '<noscript>';
		$css = Kidzou_WebPerf::$css_load_per_js;

		foreach ($css as $item) {
			$src = $item['src'];
			$media = $item['media'];
			$ver = Kidzou::VERSION;
			echo "<link rel='stylesheet'  href='$src?ver=$ver' type='text/css' media='$media' />";
		}
		echo '</noscript>';
	}

?>

</body>
</html>