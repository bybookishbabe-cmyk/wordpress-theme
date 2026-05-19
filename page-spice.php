<?php
/**
 * Template Name: Romance Books by Spice Level
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
while (have_posts()) {
	the_post();
	bbb_render_section('page-spice');
}
get_footer();
