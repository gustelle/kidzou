<?php
/**
 *
 * Template Name: Kidzou - Liens
 * Description: A Page Template for displaying usefull links
 *
 */
?>

<?php get_header(); ?>

<div id="main_content" class="clearfix fullwidth">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
		<?php get_template_part('page', 'links'); ?>
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->
</div> <!-- end #main_content -->

<?php get_footer(); ?>
