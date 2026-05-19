<?php
/**
 * Template Name: Library
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

wp_enqueue_script('bbb-supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', array(), null, false);
wp_enqueue_style('bbb-sss-library', get_template_directory_uri() . '/assets/css/sss-library.css', array(), wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-sss-library', get_template_directory_uri() . '/assets/js/sss-library.js', array('bbb-supabase'), wp_get_theme()->get('Version'), false);

get_header();

$all_books        = sss_get_all_books();
$all_public_books = $all_books;
?>

<section class="sss-lib sss-lib--public" id="sss-lib-public" data-sss-lib="public">
	<div class="sss-lib__wrap">
		<?php get_template_part('template-parts/library/library-header'); ?>
		<?php get_template_part('template-parts/library/library-trending-shelf', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-jump-nav', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-spice-tease'); ?>
		<?php get_template_part('template-parts/library/library-rec-demo'); ?>
		<?php get_template_part('template-parts/library/library-my-shelf'); ?>
		<?php get_template_part('template-parts/library/library-society-classics', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-starter-pack', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-books-of-month', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-mood-shelves', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-full-archive', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-modal'); ?>
		<?php get_template_part('template-parts/library/library-notepad'); ?>
		<?php get_template_part('template-parts/library/library-floating-ui', null, array('books' => $all_public_books)); ?>
	</div>
</section>

<?php get_footer(); ?>
