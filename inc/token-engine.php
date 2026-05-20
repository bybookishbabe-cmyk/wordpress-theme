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

	$block_tokens = '(?:book(?::\d+)?|bookcard|pillar|pillar\s*nav|pillar\s*bookcard|library|read\s*next|weekly\s+obsession|series|ku|quickstats(?::\d+)?|newsletter(?:\s+preview)?|newsletter:[^\]]+|specific|specific\s+links|looking\s+for\s+something\s+specific|specific:[^\]]+|bigspecific)';
	$block_token  = '\[' . $block_tokens . '\]';
	$content = preg_replace('/<p\b[^>]*>\s*(' . $block_token . '(?:\s*(?:<br\s*\/?>)?\s*' . $block_token . ')*)\s*<\/p>/i', '$1', $content) ?? $content;

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
		'/\[book\]/i'                  => '[sss_book index="1" post_id="' . $post_id . '"]',
		'/\[bookcard\]/i'              => '[sss_bookcard post_id="' . $post_id . '"]',
		'/\[pillar\s*bookcard\]/i'     => '[sss_pillar_bookcard post_id="' . $post_id . '"]',
		'/\[library\]/i'               => '[sss_library post_id="' . $post_id . '"]',
		'/\[signoff\]/i'               => '[sss_signoff]',
		'/\[(?:specific|specific\s+links|looking\s+for\s+something\s+specific)\]/i' => '[sss_specific_links post_id="' . $post_id . '"]',
		'/\[bigspecific\]/i'           => '[sss_bigspecific]',
		'/\[read\s*next\]/i'           => '[sss_readnext post_id="' . $post_id . '"]',
		'/\[weekly\s+obsession\]/i'    => '[sss_weekly_obsession]',
		'/\[series\]/i'                => '[sss_series post_id="' . $post_id . '"]',
		'/\[(?:pillar|pillar\s*nav)\]/i' => '[sss_pillar_nav post_id="' . $post_id . '"]',
		'/\[newsletter(?:\s+preview)?\]/i' => '[sss_newsletter]',
		'/\[ku\]/i'                    => '[sss_ku post_id="' . $post_id . '"]',
	);

	foreach ($map as $pattern => $replacement) {
		$content = preg_replace($pattern, $replacement, $content) ?? $content;
	}

	if (preg_match('/\[faq\]/i', $content)) {
		$content = preg_replace('/<h[1-4]\b[^>]*>\s*frequently\s+asked\s+questions\s*<\/h[1-4]>\s*(?:<hr\s*\/?>\s*)?/i', '', $content) ?? $content;
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

	return $content;
}
