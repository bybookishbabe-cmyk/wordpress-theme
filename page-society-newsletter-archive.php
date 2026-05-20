<?php
/**
 * Template Name: society newsletter archive
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$issues = function_exists('bbb_society_get_newsletter_issues') ? bbb_society_get_newsletter_issues(-1) : array();

get_header();
?>

<section class="bbb-society-page" aria-labelledby="bbb-newsletter-archive-title">
	<div class="bbb-society-page__inner">
		<header class="bbb-society-page__header">
			<p class="bbb-society-landing__eyebrow">the newsletter</p>
			<h1 id="bbb-newsletter-archive-title">full archive</h1>
			<p>every imported smut and sentiment society issue in one place.</p>
		</header>

		<?php bbb_society_render_newsletter_issue_grid($issues); ?>
	</div>
</section>

<?php
get_footer();
