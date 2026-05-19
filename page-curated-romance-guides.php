<?php
/**
 * Legacy Shopify romance guides archive route.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$GLOBALS['bbb_forced_blog_category'] = 'curated-romance-guides';

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('component-card', 'assets/css/component-card.css', array('bbb-bookshelf-signup'));
	bbb_enqueue_css('component-article-card', 'assets/css/component-article-card.css', array('component-card'));
	bbb_enqueue_css('section-main-blog', 'assets/css/section-main-blog.css', array('component-article-card'));
	bbb_enqueue_js('blog-trope-rotator', 'assets/js/blog-trope-rotator.js', array(), true);
}

require get_theme_file_path('archive.php');
