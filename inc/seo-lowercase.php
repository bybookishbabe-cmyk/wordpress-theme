<?php
/**
 * Lowercase SEO titles and descriptions for brand consistency.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_lowercase_seo_text($text) {
	if (!is_scalar($text)) {
		return $text;
	}

	if (is_singular('bbb_book')) {
		return $text;
	}

	$text = (string) $text;

	if (function_exists('mb_strtolower')) {
		return mb_strtolower($text, 'UTF-8');
	}

	return strtolower($text);
}

function bbb_lowercase_public_page_title($title, $post_id = 0) {
	if (is_admin() || !is_scalar($title)) {
		return $title;
	}

	$post = $post_id ? get_post((int) $post_id) : null;
	if (!$post instanceof WP_Post || 'page' !== $post->post_type) {
		return $title;
	}

	$legal_slugs = array(
		'accessibility-statement',
		'cookie-policy',
		'data-sharing-opt-out',
		'privacy-policy',
		'privacy-policy-2',
		'refund-policy',
		'return-policy',
		'returns-policy',
		'shipping-policy',
		'terms-and-conditions',
		'terms-of-service',
	);

	if (in_array((string) $post->post_name, $legal_slugs, true)) {
		return $title;
	}

	return bbb_lowercase_seo_text($title);
}
add_filter('the_title', 'bbb_lowercase_public_page_title', PHP_INT_MAX, 2);
add_filter('single_post_title', 'bbb_lowercase_public_page_title', PHP_INT_MAX, 2);

foreach (
	array(
		'pre_get_document_title',
		'rank_math/frontend/title',
		'rank_math/frontend/description',
		'rank_math/opengraph/facebook/title',
		'rank_math/opengraph/facebook/description',
		'rank_math/opengraph/twitter/title',
		'rank_math/opengraph/twitter/description',
		'wpseo_title',
		'wpseo_metadesc',
		'wpseo_opengraph_title',
		'wpseo_opengraph_desc',
		'wpseo_twitter_title',
		'wpseo_twitter_description',
	) as $bbb_seo_lowercase_filter
) {
	add_filter($bbb_seo_lowercase_filter, 'bbb_lowercase_seo_text', PHP_INT_MAX);
}

function bbb_lowercase_rank_math_schema_text(array $data): array {
	$seo_keys = array('description', 'headline', 'name', 'title');

	$lowercase_schema_text = static function ($value) use (&$lowercase_schema_text, $seo_keys) {
		if (!is_array($value)) {
			return $value;
		}

		foreach ($value as $key => $child) {
			if (is_string($key) && in_array($key, $seo_keys, true) && is_string($child)) {
				$value[$key] = bbb_lowercase_seo_text($child);
				continue;
			}

			$value[$key] = $lowercase_schema_text($child);
		}

		return $value;
	};

	return $lowercase_schema_text($data);
}
add_filter('rank_math/json_ld', 'bbb_lowercase_rank_math_schema_text', PHP_INT_MAX);
