<?php
/**
 * Template Name: Library Shelf
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
while (have_posts()) {
	the_post();
	bbb_render_section('page-shelf');
}
get_footer();
