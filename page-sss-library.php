<?php
/**
 * Template Name: SSS Library Books
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_require_sss_member();
get_header();
while (have_posts()) {
	the_post();
	bbb_render_component('sss-folder-tabs');
	bbb_render_section('sss-library-page');
}
get_footer();
