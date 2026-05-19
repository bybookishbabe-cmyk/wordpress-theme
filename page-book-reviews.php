<?php
/**
 * Template Name: Book Reviews
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-book-reviews-page', 'assets/css/book-reviews-page.css', array('bbb-bookshelf-signup'));
}

get_header();

if (have_posts()) {
	while (have_posts()) {
		the_post();
		bbb_render_section('book-reviews-page');
	}
} else {
	bbb_render_section('book-reviews-page');
}
get_footer();
