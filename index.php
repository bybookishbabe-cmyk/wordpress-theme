<?php
/**
 * Main fallback template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (is_singular('post')) {
	$single_post_template = locate_template('single-post.php');
	if ('' !== $single_post_template) {
		require $single_post_template;
		return;
	}
}

get_header();

if (is_home()) {
	get_template_part('template-parts/home/quiz-nudge');
}

if (have_posts()) {
	while (have_posts()) {
		the_post();
		the_content();
	}
} else {
	echo '<section class="page-width"><p>' . esc_html__('No content found.', 'bybookishbabe-shopify-port') . '</p></section>';
}

get_footer();
