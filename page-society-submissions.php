<?php
/**
 * Template Name: Society Submissions
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();

$rendered = false;
while (have_posts()) {
	the_post();
	bbb_render_section('newsletter-submissions-page', array('page_id' => get_the_ID()));
	$rendered = true;
}

if (!$rendered) {
	bbb_render_section('newsletter-submissions-page', array('page_id' => 0));
}

get_footer();
