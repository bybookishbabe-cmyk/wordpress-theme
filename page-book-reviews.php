<?php
/**
 * Template Name: Book Reviews
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
while (have_posts()) {
	the_post();
	bbb_render_section('book-reviews-page');
}
get_footer();
