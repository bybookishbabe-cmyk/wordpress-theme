<?php
/**
 * Template Name: Our Story
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
while (have_posts()) {
	the_post();
	bbb_render_section('bbb-our-story');
}
get_footer();
