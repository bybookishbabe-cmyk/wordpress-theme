<?php
/**
 * Legacy Shopify romance guides archive route.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$GLOBALS['bbb_forced_blog_category'] = 'curated-romance-guides';

global $wp_query;
if ($wp_query instanceof WP_Query) {
	$wp_query->is_404     = false;
	$wp_query->is_archive = true;
	$wp_query->is_home    = false;
	$wp_query->is_page    = false;
}
status_header(200);

$bbb_guides_title       = 'curated romance reading guides by trope & mood | bybookishbabe';
$bbb_guides_description = "curated romance reading guides organized by trope, mood, and series. find exactly what to read next with bybookishbabe's handpicked lists.";
add_filter('pre_get_document_title', static fn(): string => $bbb_guides_title, 99);
add_filter('rank_math/frontend/title', static fn(): string => $bbb_guides_title, 99);
add_filter('rank_math/frontend/description', static fn(): string => $bbb_guides_description, 99);
add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';

		return $robots;
	},
	99
);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('component-card', 'assets/css/component-card.css', array('bbb-bookshelf-signup'));
	bbb_enqueue_css('component-article-card', 'assets/css/component-article-card.css', array('component-card'));
	bbb_enqueue_css('section-main-blog', 'assets/css/section-main-blog.css', array('component-article-card'));
	bbb_enqueue_js('blog-trope-rotator', 'assets/js/blog-trope-rotator.js', array(), true);
}

require get_theme_file_path('archive.php');
