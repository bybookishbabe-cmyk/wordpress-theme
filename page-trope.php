<?php
/**
 * Template Name: Library Trope
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();

if (have_posts()) {
	the_post();
}

$term = function_exists('bbb_get_page_taxonomy_term') ? bbb_get_page_taxonomy_term('trope') : null;

if ($term instanceof WP_Term && function_exists('bbb_render_book_taxonomy_page')) {
	bbb_render_book_taxonomy_page($term);
} else {
	?>
	<section class="page-width page-width--narrow section-main-padding bbb-waiting-template">
		<p class="bbb-waiting-template__kicker">wordpress route ready</p>
		<h1 class="main-page-title page-title h0"><?php the_title(); ?></h1>
		<div class="rte"><p>waiting on trope data</p></div>
	</section>
	<?php
}

get_footer();
