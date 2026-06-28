<?php
/**
 * Dynamic Pinterest quote cards.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_quote_pin_clean_text(string $text): string {
	return trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
}

function bbb_quote_pin_quote_text(WP_Post $quote): string {
	return function_exists('bbb_bookquote_quote_text')
		? bbb_bookquote_quote_text($quote)
		: bbb_quote_pin_clean_text((string) $quote->post_content);
}

function bbb_quote_pin_book_title(WP_Post $book): string {
	$title = get_the_title($book);

	return function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($title) : $title;
}

function bbb_quote_pin_book_author(WP_Post $book): string {
	$data = function_exists('sss_book_data') ? sss_book_data($book) : array();
	$author = trim((string) ($data['author'] ?? get_post_meta($book->ID, '_bbb_author', true)));

	return function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : $author;
}

function bbb_quote_pin_source_for_quote(WP_Post $quote, int $source_id = 0): string {
	$book = $source_id > 0 ? get_post($source_id) : null;
	if (!$book instanceof WP_Post && function_exists('bbb_quote_wall_book')) {
		$book = bbb_quote_wall_book($quote);
	}

	if ($book instanceof WP_Post) {
		$title = bbb_quote_pin_book_title($book);
		$author = bbb_quote_pin_book_author($book);

		return trim($title . ('' !== $author ? ' by ' . $author : ''));
	}

	$stored_title = trim((string) get_post_meta($quote->ID, '_quote_book_title', true));
	if ('' === $stored_title) {
		$stored_title = trim((string) get_post_meta($quote->ID, 'book_title', true));
	}

	return function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($stored_title) : $stored_title;
}

function bbb_quote_pin_card_url(WP_Post $quote, array $args = array()): string {
	$png_url = bbb_quote_pin_png_card_url($quote, $args);
	if ('' !== $png_url) {
		return $png_url;
	}

	$query = array_filter(
		array(
			'context'   => sanitize_key((string) ($args['context'] ?? 'book')),
			'source_id' => isset($args['source_id']) ? absint($args['source_id']) : 0,
		),
		static fn($value): bool => '' !== (string) $value && '0' !== (string) $value
	);

	$query['bbb_quote_pin_card'] = absint($quote->ID);

	return add_query_arg($query, home_url('/'));
}

function bbb_quote_pin_png_card_url(WP_Post $quote, array $args = array()): string {
	if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
		return '';
	}

	$context = sanitize_key((string) ($args['context'] ?? 'book'));
	$source_id = isset($args['source_id']) ? absint($args['source_id']) : 0;
	$render_source_id = 'book' === $context ? $source_id : 0;
	$quote_text = bbb_quote_pin_quote_text($quote);
	$source = bbb_quote_pin_source_for_quote($quote, $render_source_id);
	$hash = substr(md5($quote_text . '|' . $source . '|quote-pin-v5'), 0, 12);
	$uploads = wp_upload_dir();

	if (!empty($uploads['error'])) {
		return '';
	}

	$directory = trailingslashit($uploads['basedir']) . 'bbb-quote-pins';
	$filename = sprintf('quote-pin-%1$d-%2$s-%3$d-%4$s.png', absint($quote->ID), $context, $source_id, $hash);
	$path = trailingslashit($directory) . $filename;

	if (!file_exists($path)) {
		if (!wp_mkdir_p($directory) || !bbb_quote_pin_render_png_file($quote, $render_source_id, $path)) {
			return '';
		}
	}

	return trailingslashit($uploads['baseurl']) . 'bbb-quote-pins/' . rawurlencode($filename);
}

function bbb_quote_pin_title(WP_Post $quote, array $args = array()): string {
	$context = (string) ($args['context'] ?? 'book');
	$source_id = isset($args['source_id']) ? absint($args['source_id']) : 0;
	$source = bbb_quote_pin_source_for_quote($quote, 'book' === $context ? $source_id : 0);

	if ('boyfriend' === $context && $source_id > 0) {
		$name = get_the_title($source_id);
		return trim($name . ' quote' . ('' !== $source ? ' from ' . $source : ''));
	}

	return trim(('' !== $source ? $source : 'romance book') . ' quote');
}

function bbb_quote_pin_description(WP_Post $quote, array $args = array()): string {
	$context = (string) ($args['context'] ?? 'book');
	$source_id = isset($args['source_id']) ? absint($args['source_id']) : 0;
	$source = bbb_quote_pin_source_for_quote($quote, 'book' === $context ? $source_id : 0);
	$quote_text = bbb_quote_pin_quote_text($quote);

	if ('boyfriend' === $context && $source_id > 0) {
		$name = get_the_title($source_id);
		return trim(sprintf('Save this %1$s fictional boyfriend quote%2$s. "%3$s"', $name, '' !== $source ? ' from ' . $source : '', $quote_text));
	}

	return trim(sprintf('Save this romance book quote%1$s. "%2$s"', '' !== $source ? ' from ' . $source : '', $quote_text));
}

function bbb_quote_pin_wrap_words(string $text, int $max_chars, int $max_lines): array {
	$words = preg_split('/\s+/u', $text) ?: array();
	$lines = array();
	$line = '';

	foreach ($words as $word) {
		$test = '' === $line ? $word : $line . ' ' . $word;
		if (mb_strlen($test) <= $max_chars) {
			$line = $test;
			continue;
		}

		if ('' !== $line) {
			$lines[] = $line;
		}
		$line = $word;

		if (count($lines) >= $max_lines - 1) {
			break;
		}
	}

	if ('' !== $line && count($lines) < $max_lines) {
		$lines[] = $line;
	}

	return array_slice($lines, 0, $max_lines);
}

function bbb_quote_pin_font_path(string $style): string {
	$theme_directory = function_exists('get_template_directory') ? get_template_directory() : '';
	$theme_fonts = '' !== $theme_directory
		? array(
			'italic' => trailingslashit($theme_directory) . 'assets/fonts/LibreBaskerville-Italic.ttf',
			'bold'   => trailingslashit($theme_directory) . 'assets/fonts/LibreBaskerville-Bold.ttf',
		)
		: array();
	$candidates = 'italic' === $style
		? array(
			$theme_fonts['italic'] ?? '',
			'/usr/share/fonts/truetype/dejavu/DejaVuSerif-Italic.ttf',
			'/usr/share/fonts/truetype/liberation/LiberationSerif-Italic.ttf',
			'/usr/share/fonts/dejavu/DejaVuSerif-Italic.ttf',
		)
		: array(
			$theme_fonts['bold'] ?? '',
			'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
			'/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
		);

	foreach ($candidates as $candidate) {
		if (file_exists($candidate)) {
			return $candidate;
		}
	}

	return '';
}

function bbb_quote_pin_text_width(string $text, string $font, int $font_size): int {
	if ('' !== $font && function_exists('imagettfbbox')) {
		$box = imagettfbbox($font_size, 0, $font, $text);
		if (is_array($box)) {
			return abs((int) $box[2] - (int) $box[0]);
		}
	}

	return (int) ceil(mb_strlen($text) * $font_size * 0.58);
}

function bbb_quote_pin_wrap_words_pixels(string $text, string $font, int $font_size, int $max_width, int $max_lines): array {
	$words = preg_split('/\s+/u', $text) ?: array();
	$lines = array();
	$line = '';

	foreach ($words as $word) {
		$test = '' === $line ? $word : $line . ' ' . $word;
		if (bbb_quote_pin_text_width($test, $font, $font_size) <= $max_width) {
			$line = $test;
			continue;
		}

		if ('' !== $line) {
			$lines[] = $line;
		}
		$line = $word;

		if (count($lines) >= $max_lines - 1) {
			break;
		}
	}

	if ('' !== $line && count($lines) < $max_lines) {
		$lines[] = $line;
	}

	return array_slice($lines, 0, $max_lines);
}

function bbb_quote_pin_image_filled_rounded_rect(GdImage $image, int $x, int $y, int $width, int $height, int $radius, int $color): void {
	imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
	imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
	imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
	imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
	imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
	imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
}

function bbb_quote_pin_render_png_file(WP_Post $quote, int $source_id, string $path): bool {
	$image = imagecreatetruecolor(1000, 1500);
	if (!$image instanceof GdImage) {
		return false;
	}

	imageantialias($image, true);

	$black = imagecolorallocate($image, 7, 7, 7);
	$grid = imagecolorallocate($image, 28, 24, 27);
	$shadow = imagecolorallocatealpha($image, 0, 0, 0, 75);
	$card = imagecolorallocate($image, 244, 243, 240);
	$pink = imagecolorallocate($image, 255, 212, 233);
	$text = imagecolorallocate($image, 39, 29, 34);
	$source_color = imagecolorallocate($image, 21, 21, 21);

	imagefilledrectangle($image, 0, 0, 1000, 1500, $black);
	for ($line = 0; $line <= 1500; $line += 72) {
		imageline($image, 0, $line, 1000, $line, $grid);
	}
	for ($line = 0; $line <= 1000; $line += 72) {
		imageline($image, $line, 0, $line, 1500, $grid);
	}

	$card_x = 92;
	$card_y = 318;
	$card_w = 816;
	$card_h = 840;
	bbb_quote_pin_image_filled_rounded_rect($image, $card_x + 16, $card_y + 30, $card_w, $card_h, 24, $shadow);
	bbb_quote_pin_image_filled_rounded_rect($image, $card_x, $card_y, $card_w, $card_h, 24, $card);

	$quote_text = '“' . trim(bbb_quote_pin_quote_text($quote), " \t\n\r\0\x0B\"“”") . '”';
	$source = bbb_quote_pin_source_for_quote($quote, $source_id);
	$quote_font = bbb_quote_pin_font_path('italic');
	$source_font = bbb_quote_pin_font_path('bold');
	$quote_length = mb_strlen($quote_text);
	$font_size = $quote_length > 170 ? 30 : ($quote_length > 105 ? 38 : ($quote_length > 65 ? 44 : 50));
	$max_lines = $quote_length > 170 ? 12 : ($quote_length > 105 ? 9 : ($quote_length > 65 ? 7 : 6));
	$max_width = 700;
	$line_height = (int) round($font_size * 1.2);
	$lines = bbb_quote_pin_wrap_words_pixels($quote_text, $quote_font, $font_size, $max_width, $max_lines);

	while ($font_size > 24 && count($lines) * $line_height > 560) {
		$font_size -= 2;
		$line_height = (int) round($font_size * 1.2);
		$lines = bbb_quote_pin_wrap_words_pixels($quote_text, $quote_font, $font_size, $max_width, $max_lines);
	}

	$total_height = count($lines) * $line_height;
	$quote_top = $card_y + 126;
	$source_y = $card_y + $card_h - 120;
	$quote_midpoint = (int) floor(($quote_top + $source_y - 48) / 2);
	$baseline = $quote_midpoint - (int) floor($total_height / 2) + $line_height;

	foreach ($lines as $index => $line) {
		$y = $baseline + ($index * $line_height);
		$width = min($max_width, bbb_quote_pin_text_width($line, $quote_font, $font_size));
		$x = (int) floor((1000 - $width) / 2);
		imagefilledrectangle($image, $x - 10, $y - (int) round($font_size * 0.8), $x + $width + 10, $y - 4, $pink);
		if ('' !== $quote_font) {
			imagettftext($image, $font_size, 0, $x, $y, $text, $quote_font, $line);
		} else {
			imagestring($image, 5, $x, $y - $font_size, $line, $text);
		}
	}

	if ('' !== $source) {
		$source_text = '— ' . $source;
		$source_size = 24;
		$source_width = bbb_quote_pin_text_width($source_text, $source_font, $source_size);
		$source_x = (int) floor((1000 - $source_width) / 2);
		if ('' !== $source_font) {
			imagettftext($image, $source_size, 0, $source_x, $source_y, $source_color, $source_font, $source_text);
		} else {
			imagestring($image, 5, $source_x, $source_y - 24, $source_text, $source_color);
		}
	}

	$result = imagepng($image, $path, 9);
	imagedestroy($image);

	return (bool) $result;
}

function bbb_quote_pin_render_svg(WP_Post $quote, int $source_id = 0): string {
	$quote_text = trim(bbb_quote_pin_quote_text($quote), " \t\n\r\0\x0B\"“”");
	$source = bbb_quote_pin_source_for_quote($quote, $source_id);
	$quote_length = mb_strlen($quote_text);
	if ($quote_length > 170) {
		$font_size = 30;
		$max_chars = 30;
		$max_lines = 12;
	} elseif ($quote_length > 105) {
		$font_size = 38;
		$max_chars = 25;
		$max_lines = 9;
	} elseif ($quote_length > 65) {
		$font_size = 44;
		$max_chars = 22;
		$max_lines = 7;
	} else {
		$font_size = 50;
		$max_chars = 18;
		$max_lines = 6;
	}
	$lines = bbb_quote_pin_wrap_words('“' . $quote_text . '”', $max_chars, $max_lines);
	$line_height = (int) round($font_size * 1.15);
	$total_height = count($lines) * $line_height;
	$card_y = 318;
	$card_h = 840;
	$quote_top = $card_y + 126;
	$source_y = $card_y + $card_h - 120;
	$quote_midpoint = (int) floor(($quote_top + $source_y - 48) / 2);
	$start_y = $quote_midpoint - (int) floor($total_height / 2) + $line_height;
	$svg_lines = '';

	foreach ($lines as $index => $line) {
		$escaped = esc_html($line);
		$y = $start_y + ($index * $line_height);
		$estimated_width = min(680, max(170, mb_strlen($line) * ($font_size * 0.58)));
		$x = 500 - ($estimated_width / 2);
		$svg_lines .= sprintf(
			'<rect x="%1$.1f" y="%2$d" width="%3$.1f" height="%4$d" fill="#ffd4e9" opacity=".82"/><text x="500" y="%5$d" text-anchor="middle">%6$s</text>',
			$x - 10,
			$y - (int) round($font_size * 0.74),
			$estimated_width + 20,
			(int) round($font_size * 0.78),
			$y,
			$escaped
		);
	}

	$source_svg = '' !== $source
		? sprintf('<text class="source" x="500" y="%d" text-anchor="middle">— %s</text>', $source_y, esc_html($source))
		: '';

	return '<?xml version="1.0" encoding="UTF-8"?>'
		. '<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="1500" viewBox="0 0 1000 1500" role="img" aria-label="' . esc_attr(bbb_quote_pin_title($quote)) . '">'
		. '<defs><pattern id="grid" width="72" height="72" patternUnits="userSpaceOnUse"><path d="M72 0H0V72" fill="none" stroke="#ffffff" stroke-opacity=".045" stroke-width="2"/></pattern><filter id="shadow" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="24" stdDeviation="26" flood-color="#000000" flood-opacity=".36"/></filter><clipPath id="quoteClip"><rect x="132" y="' . esc_attr((string) ($card_y + 72)) . '" width="736" height="' . esc_attr((string) ($card_h - 168)) . '" rx="8"/></clipPath></defs>'
		. '<rect width="1000" height="1500" fill="#070707"/><rect width="1000" height="1500" fill="url(#grid)" opacity=".9"/>'
		. '<rect x="92" y="' . esc_attr((string) $card_y) . '" width="816" height="' . esc_attr((string) $card_h) . '" rx="24" fill="#f4f3f0" filter="url(#shadow)"/>'
		. '<g clip-path="url(#quoteClip)" fill="#271d22" font-family="Georgia, Cormorant Garamond, serif" font-style="italic" font-size="' . esc_attr((string) $font_size) . '" font-weight="400">' . $svg_lines . '</g>'
		. '<g fill="#151515" font-family="Arial, DM Sans, sans-serif" font-size="26" font-weight="800">' . $source_svg . '</g>'
		. '</svg>';
}

function bbb_quote_pin_template_redirect(): void {
	$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
	$quote_id = isset($_GET['bbb_quote_pin_card']) ? absint((string) wp_unslash($_GET['bbb_quote_pin_card'])) : 0;
	if (0 === $quote_id && preg_match('#/quote-pin-card/(\d+)\.svg$#', $path, $matches)) {
		$quote_id = (int) $matches[1];
	}
	if (0 === $quote_id) {
		return;
	}

	$quote = get_post($quote_id);
	if (!$quote instanceof WP_Post || !function_exists('bbb_quote_post_types') || !in_array(get_post_type($quote), bbb_quote_post_types(), true)) {
		status_header(404);
		exit;
	}

	$context = isset($_GET['context']) ? sanitize_key((string) wp_unslash($_GET['context'])) : 'book';
	$source_id = isset($_GET['source_id']) ? absint((string) wp_unslash($_GET['source_id'])) : 0;
	$source_id = 'book' === $context ? $source_id : 0;

	header('Content-Type: image/svg+xml; charset=utf-8');
	header('Cache-Control: public, max-age=86400');
	echo bbb_quote_pin_render_svg($quote, $source_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action('template_redirect', 'bbb_quote_pin_template_redirect', -2000);
