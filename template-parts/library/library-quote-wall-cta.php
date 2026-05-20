<?php
/**
 * Quote wall CTA for the main Library page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$is_society = !empty($args['is_society']);
$join_url   = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$quote_url  = home_url('/sss-quote-wall/');

$quotes = post_type_exists('sss_quote')
	? get_posts(
		array(
			'post_type'      => 'sss_quote',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	)
	: array();
?>
<section class="sss-lib__quoteCta" id="quote-wall-preview">
	<div class="sss-lib__quoteCtaCopy">
		<p class="sss-lib__archiveKicker">quote library</p>
		<h2 class="sss-lib__archiveTitle">lines worth reopening.</h2>
		<p class="sss-lib__archiveSub">
			<?php echo esc_html($is_society ? 'Your full quote wall is open.' : 'Preview a few lines. Paid members get the full quote wall.'); ?>
		</p>
		<a class="sss-lib__quoteCtaBtn" href="<?php echo esc_url($quote_url); ?>">
			<?php echo esc_html($is_society ? 'open quote wall' : 'preview quote wall'); ?>
		</a>
	</div>
	<div class="sss-lib__quoteCtaStack" aria-hidden="true">
		<?php foreach ($quotes as $quote) : ?>
			<?php
			if (!$quote instanceof WP_Post) {
				continue;
			}
			$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
			if ('' === $text) {
				$text = trim((string) get_post_meta($quote->ID, 'quote_text', true));
			}
			if ('' === $text) {
				$text = trim((string) get_post_meta($quote->ID, 'quote', true));
			}
			if ('' === $text) {
				$text = trim(wp_strip_all_tags($quote->post_content));
			}
			if ('' === $text) {
				continue;
			}
			?>
			<blockquote><?php echo esc_html(wp_trim_words(wp_strip_all_tags($text), 22)); ?></blockquote>
		<?php endforeach; ?>
	</div>
</section>
