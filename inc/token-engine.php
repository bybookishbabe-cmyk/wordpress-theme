<?php
/**
 * Article token preprocessor.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_content_has_pillar(string $content): bool {
	$tokens = array('[pillar]', '[Pillar]', '[PILLAR]', '[pillarnav]', '[Pillarnav]', '[PILLARNAV]', '[pillar nav]', '[Pillar Nav]', '[PILLAR NAV]');
	foreach ($tokens as $token) {
		if (str_contains($content, $token)) {
			return true;
		}
	}

	return false;
}

function sss_token_engine(string $content, int $post_id): string {
	$books = function_exists('get_field') ? get_field('book', $post_id) : array();
	$books = is_array($books) ? array_values(array_filter($books)) : array();
	$trope = function_exists('get_field') ? get_field('trope', $post_id) : null;

	$content = preg_replace_callback(
		'/\[book:(\d+)\]/i',
		static function (array $matches) use ($books, $trope, $post_id): string {
			$index = max(1, (int) $matches[1]);
			$name  = $books ? 'sss_book' : ($trope ? 'sss_book_trope' : 'sss_book');

			return sprintf('[%s index="%d" post_id="%d"]', $name, $index, $post_id);
		},
		$content
	) ?? $content;

	$map = array(
		'/\[bookcard\]/i'              => '[sss_bookcard post_id="' . $post_id . '"]',
		'/\[pillar\s*bookcard\]/i'     => '[sss_pillar_bookcard post_id="' . $post_id . '"]',
		'/\[library\]/i'               => '[sss_library post_id="' . $post_id . '"]',
		'/\[signoff\]/i'               => '[sss_signoff]',
		'/\[(?:specific|specific\s+links|looking\s+for\s+something\s+specific)\]/i' => '[sss_specific_links post_id="' . $post_id . '"]',
		'/\[bigspecific\]/i'           => '[sss_bigspecific]',
		'/\[read\s*next\]/i'           => '[sss_readnext post_id="' . $post_id . '"]',
		'/\[series\]/i'                => '[sss_series post_id="' . $post_id . '"]',
		'/\[(?:pillar|pillar\s*nav)\]/i' => '[sss_pillar_nav post_id="' . $post_id . '"]',
		'/\[newsletter(?:\s+preview)?\]/i' => '[sss_newsletter]',
	);

	foreach ($map as $pattern => $replacement) {
		$content = preg_replace($pattern, $replacement, $content) ?? $content;
	}

	$content = preg_replace('/<p\b[^>]*>\s*(\[faq\])/i', '$1', $content) ?? $content;
	$content = preg_replace('/(\[\/faq\])\s*<\/p>/i', '$1', $content) ?? $content;

	$content = preg_replace_callback(
		'/\[newsletter:([a-z0-9_-]+)\]/i',
		static fn(array $matches): string => '[sss_newsletter handle="' . esc_attr($matches[1]) . '"]',
		$content
	) ?? $content;

	$content = preg_replace_callback(
		'/\[specific:([a-z0-9_-]+)\]/i',
		static fn(array $matches): string => '[sss_specific_links cluster="' . esc_attr($matches[1]) . '" post_id="' . $post_id . '"]',
		$content
	) ?? $content;

	$content = preg_replace_callback(
		'/\[spice:([^\]]+)\]/i',
		static function (array $matches): string {
			$value = strtolower(trim($matches[1]));
			$levels = array(
				'1' => 1, 'soft' => 1, 'soft open door' => 1,
				'2' => 2, 'some heat' => 2,
				'3' => 3, 'medium' => 3, 'balanced' => 3,
				'4' => 4, 'hot' => 4, 'high' => 4, 'high spice' => 4,
				'5' => 5, 'feral' => 5, 'wreck me' => 5,
			);
			$level = $levels[$value] ?? 0;

			return $level ? '[sss_spice level="' . $level . '"]' : $matches[0];
		},
		$content
	) ?? $content;

	$content = preg_replace(
		'/<p>\s*\[quickstats(?::(\d+))?\]\s*<\/p>|\[quickstats(?::(\d+))?\]/i',
		'[sss_quickstats index="${1}${2}" post_id="' . $post_id . '"]',
		$content
	) ?? $content;
	$content = preg_replace('/index="" /', 'index="1" ', $content) ?? $content;

	$button = '<div class="article-inline-book-export"><button type="button" class="sss-lib__exportBtn guide-bookcard__export" data-guide-export-inline data-guide-title="' . esc_attr(get_the_title($post_id)) . '">save this list</button><div class="guide-bookcard__affiliate-note">some links may be affiliate links, so thank you for supporting the recs. &lt;3</div></div>';

	return preg_replace('/(\[sss_book\s+index="1"\s+post_id="' . preg_quote((string) $post_id, '/') . '"\])/', $button . '$1', $content, 1) ?? $content;
}
