<?php
/**
 * Template Name: Romance Library
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
while (have_posts()) {
	the_post();
	bbb_render_section('public-library-page');
}
get_footer();
