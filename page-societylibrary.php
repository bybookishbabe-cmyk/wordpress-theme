<?php
/**
 * Template Name: Society Main Dashboard
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_require_sss_member();
get_header();
while (have_posts()) {
	the_post();
	bbb_render_section('sss-member-dashboard');
	bbb_render_component('sss-folder-tabs');
}
get_footer();
