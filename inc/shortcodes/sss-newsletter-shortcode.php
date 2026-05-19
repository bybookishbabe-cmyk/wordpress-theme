<?php
/**
 * Newsletter shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_newsletter_shortcode($atts): string {
	$atts = shortcode_atts(array('handle' => ''), $atts, 'sss_newsletter');
	$handle = trim((string) $atts['handle']);

	return '<div class="bbb-signoff__embed sss-newsletter" data-newsletter-handle="' . esc_attr($handle) . '"><iframe src="https://thesmutandsentimentsociety.substack.com/embed" title="subscribe to the smut &amp; sentiment society" loading="lazy" scrolling="no"></iframe></div>';
}
add_shortcode('sss_newsletter', 'sss_newsletter_shortcode');
