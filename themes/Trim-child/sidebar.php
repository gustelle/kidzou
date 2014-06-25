<?php if ( is_active_sidebar( 'sidebar' ) ){ ?>
	<aside id="sidebar" itemscope itemtype="http://schema.org/WPSideBar">
		<?php dynamic_sidebar( 'sidebar' ); ?>
	</aside> <!-- end #sidebar -->
<?php } ?>