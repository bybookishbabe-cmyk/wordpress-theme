<?php
/**
 * Template Name: SSS Library Books
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_reader_is_society') || !bbb_reader_is_society()) {
	get_header();
	if (function_exists('bbb_society_render_locked_preview_page')) {
		bbb_society_render_locked_preview_page(
			array(
				'access'      => 'paid',
				'kicker'      => 'paid society preview',
				'title'       => 'the society library',
				'intro'       => 'preview the private library before unlocking the full archive, matchmaker, votes, and member-only recs.',
				'panel_title' => 'upgrade to open the library',
				'panel_copy'  => 'paid society members can browse the full member library, private shelf logic, and exclusive book tools.',
				'items'       => array(
					'full society book archive',
					'private shelf and member-only filters',
					'fictional boyfriend votes and library tools',
				),
			)
		);
	}
	get_footer();
	return;
}

get_header();
while (have_posts()) {
	the_post();
	bbb_render_component('sss-folder-tabs');
	bbb_render_section('sss-library-page');
}
get_footer();
