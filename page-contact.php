<?php
/**
 * Template Name: Contact
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
	bbb_render_section('contact-form');
}
get_footer();
