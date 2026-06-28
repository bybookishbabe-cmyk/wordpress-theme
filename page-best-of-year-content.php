<?php
/**
 * Template Name: Best of Year Content Wrapper
 *
 * Keeps editor content, including [bestofyear], inside the normal blog column.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('section-blog-post', 'assets/css/section-blog-post.css', array('bbb-bookshelf-signup'));
	bbb_enqueue_css('blog-system', 'assets/css/blog-system.css', array('section-blog-post'));
}

get_header();

while (have_posts()) {
	the_post();
	?>
	<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
		<article class="article-template">
			<div class="article-template__content page-width page-width--narrow rte">
				<?php the_content(); ?>
			</div>
		</article>
	</main>
	<?php
}

get_footer();
