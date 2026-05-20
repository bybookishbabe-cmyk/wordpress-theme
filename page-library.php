<?php
/**
 * Template Name: Library
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

wp_enqueue_script('bbb-supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', array(), null, false);
if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-sss-library', 'assets/css/sss-library.css');
} else {
	wp_enqueue_style('bbb-sss-library', get_template_directory_uri() . '/assets/css/sss-library.css', array(), wp_get_theme()->get('Version'));
}
if (function_exists('bbb_enqueue_js')) {
	bbb_enqueue_js('bbb-sss-library', 'assets/js/sss-library.js', array('bbb-supabase'), false);
} else {
	wp_enqueue_script('bbb-sss-library', get_template_directory_uri() . '/assets/js/sss-library.js', array('bbb-supabase'), wp_get_theme()->get('Version'), false);
}

get_header();

$all_books        = sss_get_all_books();
$is_society       = function_exists('bbb_reader_is_society') ? bbb_reader_is_society() : false;
$all_public_books = array_values(
	array_filter(
		$all_books,
		static fn(WP_Post $book): bool => !function_exists('sss_book_is_private') || !sss_book_is_private($book->ID)
	)
);
?>

<section class="sss-lib sss-lib--public<?php echo $is_society ? ' sss-lib--society-unlocked' : ''; ?>" id="sss-lib-public" data-sss-lib="<?php echo esc_attr($is_society ? 'society' : 'public'); ?>">
	<div class="sss-lib__wrap">
		<?php get_template_part('template-parts/library/library-header'); ?>
		<?php get_template_part('template-parts/library/library-trending-shelf', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-jump-nav', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-spice-tease'); ?>
		<?php get_template_part('template-parts/library/library-rec-demo'); ?>
		<?php get_template_part('template-parts/library/library-my-shelf'); ?>
		<?php get_template_part('template-parts/library/library-society-layer', null, array('mode' => 'private_shelf', 'books' => $all_books, 'public_books' => $all_public_books, 'is_society' => $is_society)); ?>
		<?php get_template_part('template-parts/library/library-society-classics', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-starter-pack', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-books-of-month', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-society-layer', null, array('mode' => 'matchmaker', 'books' => $all_books, 'public_books' => $all_public_books, 'is_society' => $is_society)); ?>
		<?php get_template_part('template-parts/library/library-quote-wall-cta', null, array('is_society' => $is_society)); ?>
		<?php get_template_part('template-parts/library/library-mood-shelves', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-full-archive', null, array('books' => $all_public_books)); ?>
		<?php get_template_part('template-parts/library/library-modal'); ?>
		<?php get_template_part('template-parts/library/library-notepad'); ?>
		<?php get_template_part('template-parts/library/library-floating-ui', null, array('books' => $all_public_books)); ?>
	</div>
</section>

<?php get_footer(); ?>
