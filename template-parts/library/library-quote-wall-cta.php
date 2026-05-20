<?php
/**
 * Quote wall CTA for the main Library page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$is_society = !empty($args['is_society']);
$quote_url  = home_url('/sss-quote-wall/');

$quote_post_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
$quotes = $quote_post_types
	? get_posts(
		array(
			'post_type'      => $quote_post_types,
			'post_status'    => array('publish', 'draft'),
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	)
	: array();
if (!$quotes && function_exists('bbb_quote_export_entries')) {
	$quotes = bbb_quote_export_entries(3);
}
?>
<section class="sss-lib__quoteCta" id="quote-wall-preview">
	<div class="sss-lib__quoteCtaCopy">
		<p class="sss-lib__archiveKicker">quote library</p>
		<h2 class="sss-lib__archiveTitle">lines worth reopening.</h2>
		<p class="sss-lib__archiveSub">
			<?php echo esc_html($is_society ? 'a private little archive of the lines worth keeping.' : 'preview a few lines. paid members get the full quote wall.'); ?>
		</p>
		<a class="sss-lib__quoteCtaBtn" href="<?php echo esc_url($quote_url); ?>">
			<?php echo esc_html($is_society ? 'open quote wall' : 'preview quote wall'); ?>
		</a>
	</div>
	<div class="sss-lib__quoteCtaStack" aria-hidden="true">
		<?php if (!$quotes) : ?>
			<blockquote>Saved lines, soft damage, and the quotes worth returning to will live here.</blockquote>
		<?php endif; ?>
		<?php foreach ($quotes as $quote) : ?>
			<?php
			if ($quote instanceof WP_Post) {
				$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
				if ('' === $text) {
					$text = trim((string) get_post_meta($quote->ID, 'quote_text', true));
				}
				if ('' === $text) {
					$text = trim((string) get_post_meta($quote->ID, 'quote', true));
				}
				if ('' === $text) {
					$text = trim((string) get_post_meta($quote->ID, '_bbb_quote', true));
				}
				if ('' === $text) {
					$text = trim(wp_strip_all_tags($quote->post_content));
				}
			} elseif (is_array($quote)) {
				$text = trim((string) ($quote['text'] ?? ''));
			} else {
				continue;
			}
			if ('' === $text) {
				continue;
			}
			?>
			<blockquote><?php echo esc_html(wp_trim_words(wp_strip_all_tags($text), 22)); ?></blockquote>
		<?php endforeach; ?>
	</div>
</section>
