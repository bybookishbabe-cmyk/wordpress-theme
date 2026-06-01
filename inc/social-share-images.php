<?php
/**
 * Social image fallbacks for pages without custom share art.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_social_share_logo_fallback_url(): string {
	return 'https://bybookishbabe.com/wp-content/uploads/2026/05/bybookishbabe.png';
}

function bbb_social_share_slug(): string {
	$path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

	if (function_exists('bbb_current_route_slug')) {
		$slug = bbb_current_route_slug();
		if ('' !== $slug && file_exists(get_theme_file_path('assets/seo/share-cards/' . $slug . '.png'))) {
			return $slug;
		}
	}

	if ('' === $path) {
		return 'home';
	}

	return sanitize_title(basename($path));
}

function bbb_social_share_card_relative_path(): string {
	return 'assets/seo/share-cards/' . bbb_social_share_slug() . '.png';
}

function bbb_social_share_forced_card_slug(): string {
	$path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
	$slug = sanitize_title(basename($path));
	$aliases = array(
		'quote-library'                 => 'sss-quote-wall',
		'sss-quote-wall'                => 'sss-quote-wall',
		'sss-printable-kindle'          => 'sss-printable-kindle-inserts',
		'sss-printable-kindle-inserts'  => 'sss-printable-kindle-inserts',
		'kindle-inserts'                => 'kindle-inserts',
		'reader-quizes'                 => 'reader-quizes',
		'reader-quizzes'                => 'reader-quizes',
		'books-like-fourth-wing'        => 'books-like-fourth-wing',
	);

	return (string) ($aliases[$slug] ?? '');
}

function bbb_social_share_card_url(): string {
	$forced_slug = bbb_social_share_forced_card_slug();
	if ('' !== $forced_slug) {
		return get_theme_file_uri('assets/seo/share-cards/' . $forced_slug . '.png');
	}

	$relative_path = bbb_social_share_card_relative_path();
	return file_exists(get_theme_file_path($relative_path)) ? get_theme_file_uri($relative_path) : '';
}

function bbb_social_share_should_replace_image(string $image): bool {
	if ('' === bbb_social_share_card_url()) {
		return false;
	}

	$image = trim($image);
	return '' === $image || bbb_social_share_logo_fallback_url() === $image;
}

function bbb_social_share_filter_image(string $image): string {
	return bbb_social_share_should_replace_image($image) ? bbb_social_share_card_url() : $image;
}
add_filter('rank_math/opengraph/facebook/image', 'bbb_social_share_filter_image', PHP_INT_MAX);
add_filter('rank_math/opengraph/twitter/image', 'bbb_social_share_filter_image', PHP_INT_MAX);

function bbb_social_share_start_head_buffer(): void {
	$image = bbb_social_share_card_url();
	if ('' === $image) {
		return;
	}

	$GLOBALS['bbb_social_share_head_buffer'] = true;
	ob_start(
		static function (string $html) use ($image): string {
			return str_replace(bbb_social_share_logo_fallback_url(), $image, $html);
		}
	);
}

function bbb_social_share_flush_head_buffer(): void {
	if (empty($GLOBALS['bbb_social_share_head_buffer']) || 0 === ob_get_level()) {
		return;
	}

	unset($GLOBALS['bbb_social_share_head_buffer']);
	ob_end_flush();
}

function bbb_social_share_print_forced_facebook_image(): void {
	$image = bbb_social_share_card_url();
	if ('' === $image || '' === bbb_social_share_forced_card_slug()) {
		return;
	}

	remove_all_actions('rank_math/opengraph/facebook', 30);
	printf('<meta property="og:image" content="%s">%s', esc_url($image), "\n");
	printf('<meta property="og:image:secure_url" content="%s">%s', esc_url($image), "\n");
	printf('<meta property="og:image:width" content="1200">%s', "\n");
	printf('<meta property="og:image:height" content="630">%s', "\n");
	printf('<meta property="og:image:type" content="image/png">%s', "\n");
}
add_action('rank_math/opengraph/facebook', 'bbb_social_share_print_forced_facebook_image', 29);

function bbb_social_share_print_forced_twitter_image(): void {
	$image = bbb_social_share_card_url();
	if ('' === $image || '' === bbb_social_share_forced_card_slug()) {
		return;
	}

	remove_all_actions('rank_math/opengraph/twitter', 30);
	printf('<meta name="twitter:image" content="%s">%s', esc_url($image), "\n");
}
add_action('rank_math/opengraph/twitter', 'bbb_social_share_print_forced_twitter_image', 29);

function bbb_social_share_add_rank_math_image($opengraph_image): void {
	$image = bbb_social_share_card_url();
	if ('' === $image || !is_object($opengraph_image) || !method_exists($opengraph_image, 'add_image')) {
		return;
	}

	$opengraph_image->add_image($image);
}
add_action('rank_math/opengraph/facebook/add_additional_images', 'bbb_social_share_add_rank_math_image', 20);
add_action('rank_math/opengraph/twitter/add_additional_images', 'bbb_social_share_add_rank_math_image', 20);

add_filter(
	'rank_math/json_ld',
	static function (array $data): array {
		$image = bbb_social_share_card_url();
		if ('' === $image) {
			return $data;
		}

		$logo = bbb_social_share_logo_fallback_url();
		$graph = isset($data['@graph']) && is_array($data['@graph']) ? $data['@graph'] : $data;
		foreach ($graph as &$item) {
			if (!is_array($item)) {
				continue;
			}

			if (isset($item['@type']) && 'ImageObject' === $item['@type'] && ($logo === ($item['url'] ?? '') || $logo === ($item['@id'] ?? ''))) {
				$item['@id']    = $image;
				$item['url']    = $image;
				$item['width']  = 1200;
				$item['height'] = 630;
			}

			if (isset($item['primaryImageOfPage']['@id']) && $logo === $item['primaryImageOfPage']['@id']) {
				$item['primaryImageOfPage']['@id'] = $image;
			}
		}
		unset($item);

		if (isset($data['@graph']) && is_array($data['@graph'])) {
			$data['@graph'] = $graph;
			return $data;
		}

		return $graph;
	},
	80
);
