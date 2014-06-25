<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<section id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'index'); ?>
		<?php if ( is_category() && function_exists('kz_category_header') ) kz_category_header();  ?> <!-- Affichage d'un bandeau de publicité sur les catégories -->
		<?php get_template_part('includes/entry', 'index'); ?>
	</section> <!-- end #left_area -->
</div> <!-- end #main_content -->

<?php get_footer(); ?>