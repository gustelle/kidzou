<?php
/*
Template Name: Full Width Page
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix fullwidth">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
		<?php get_template_part('loop', 'page'); ?>
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->
</div> <!-- end #main_content -->

<?php get_footer(); ?>