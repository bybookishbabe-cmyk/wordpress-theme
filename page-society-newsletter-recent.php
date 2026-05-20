<?php
/**
 * Template Name: society newsletter recent
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$issues = function_exists('bbb_society_get_newsletter_issues') ? bbb_society_get_newsletter_issues(3) : array();

get_header();
?>

<section class="bbb-society-page" aria-labelledby="bbb-newsletter-recent-title">
	<div class="bbb-society-page__inner">
		<header class="bbb-society-page__header">
			<p class="bbb-society-landing__eyebrow">the newsletter</p>
			<h1 id="bbb-newsletter-recent-title">recent issues</h1>
			<p>the latest society dispatches, pulled from the imported newsletter issue shelf.</p>
		</header>

		<?php bbb_society_render_newsletter_issue_grid($issues); ?>
	</div>
</section>

<?php
get_footer();
