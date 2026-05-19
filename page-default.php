<?php
/**
 * Template Name: Default Page
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();

while (have_posts()) {
	the_post();
	?>
	<div class="page-width page-width--narrow section-main-padding">
		<h1 class="main-page-title page-title h0"><?php the_title(); ?></h1>
		<div class="rte"><?php the_content(); ?></div>
	</div>
	<?php
	bbb_render_section('rich-text');
	bbb_render_section('image-with-text');
	bbb_render_section('collapsible-content');
}

get_footer();
