				<footer id="footer" itemscope itemtype="http://schema.org/WPFooter">
					<div id="footer-widgets" class="clearfix">
						<?php
							$footer_sidebars = array('footer-area-1','footer-area-2','footer-area-3');
							if ( is_active_sidebar( $footer_sidebars[0] ) || is_active_sidebar( $footer_sidebars[1] ) || is_active_sidebar( $footer_sidebars[2] ) ) {
								foreach ( $footer_sidebars as $key => $footer_sidebar ){
									if ( is_active_sidebar( $footer_sidebar ) ) {
										echo '<nav class="footer-widget' . (  2 == $key ? ' last' : '' ) . '">';
										dynamic_sidebar( $footer_sidebar );
										echo '</nav>';
									}
								}
							}
						?>
					</div> <!-- end #footer-widgets -->
				</footer> <!-- end #footer -->
			</div> <!-- end #content -->
		</div> <!-- end #wrapper -->

		<p id="copyright"><a rel="author" href="https://plus.google.com/103958659862452997072?rel=author"><?php printf( __('Copyright %s', 'Trim'), 'Kidzou 2013' ); ?></a></p>
	</div> <!-- end #container -->

	<?php wp_footer(); ?>
</body>
</html>