<?php
/**
 * Shopify-faithful homepage shell.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
?>
<div class="bbb-home bbb-home--shopify">
	<?php
	get_template_part('template-parts/hero-smut-sentiment');
	get_template_part('template-parts/homepage/weekly-obsession');
	bbb_render_section('trending-romance-reads');
	get_template_part('template-parts/sections/browse-by-trope');
	get_template_part('template-parts/home/featured-romance-lists');
	get_template_part('template-parts/home/library-preview');
	get_template_part('template-parts/sections/section-society-hero');
	get_template_part('template-parts/home/quiz-nudge');
	get_template_part('template-parts/home/threads');
	bbb_render_section('homepage-book-boyfriends');
	get_template_part('template-parts/home/connect-cards');
	?>
</div>
<?php
get_footer();
