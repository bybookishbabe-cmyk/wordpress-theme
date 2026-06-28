<?php
/**
 * Dynamic Pinterest cards for the romance reading bingo result.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_bingo_pin_clean(string $text, int $limit = 140): string {
	$text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');

	return mb_substr($text, 0, $limit);
}

function bbb_bingo_pin_request_text(string $key, string $fallback, int $limit = 140): string {
	if (!isset($_GET[$key])) {
		return $fallback;
	}

	return bbb_bingo_pin_clean((string) wp_unslash($_GET[$key]), $limit);
}

function bbb_bingo_pin_font(string $style): string {
	if (function_exists('bbb_quote_pin_font_path')) {
		return bbb_quote_pin_font_path($style);
	}

	return '';
}

function bbb_bingo_pin_text_width(string $text, string $font, int $size): int {
	if ('' !== $font && function_exists('imagettfbbox')) {
		$box = imagettfbbox($size, 0, $font, $text);
		if (is_array($box)) {
			return abs((int) $box[2] - (int) $box[0]);
		}
	}

	return (int) ceil(mb_strlen($text) * $size * 0.56);
}

function bbb_bingo_pin_wrap(string $text, string $font, int $size, int $max_width, int $max_lines): array {
	$words = preg_split('/\s+/u', $text) ?: array();
	$lines = array();
	$line  = '';

	foreach ($words as $word) {
		$test = '' === $line ? $word : $line . ' ' . $word;
		if (bbb_bingo_pin_text_width($test, $font, $size) <= $max_width) {
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

function bbb_bingo_pin_draw_text(GdImage $image, string $text, int $x, int $y, int $color, string $font, int $size): void {
	if ('' !== $font && function_exists('imagettftext')) {
		imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
		return;
	}

	imagestring($image, 5, $x, $y - $size, $text, $color);
}

function bbb_bingo_pin_draw_wrapped(GdImage $image, string $text, int $x, int $y, int $max_width, int $line_height, int $max_lines, int $color, string $font, int $size): int {
	$lines = bbb_bingo_pin_wrap($text, $font, $size, $max_width, $max_lines);
	foreach ($lines as $index => $line) {
		bbb_bingo_pin_draw_text($image, $line, $x, $y + ($index * $line_height), $color, $font, $size);
	}

	return $y + (count($lines) * $line_height);
}

function bbb_bingo_pin_render(): void {
	if (!isset($_GET['bbb_bingo_pin'])) {
		return;
	}

	if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng')) {
		status_header(500);
		exit;
	}

	$type = bbb_bingo_pin_request_text('type', 'the trope loyalist', 90);
	$copy = bbb_bingo_pin_request_text('copy', 'you got a bingo and found your romance reader type.', 220);
	$book = bbb_bingo_pin_request_text('book', 'browse the library', 90);
	$man  = bbb_bingo_pin_request_text('man', 'take the fictional boyfriend quiz', 90);

	$image = imagecreatetruecolor(1000, 1500);
	if (!$image instanceof GdImage) {
		status_header(500);
		exit;
	}

	imageantialias($image, true);

	$black      = imagecolorallocate($image, 5, 5, 5);
	$panel      = imagecolorallocate($image, 14, 11, 14);
	$soft_panel = imagecolorallocate($image, 28, 22, 28);
	$pink       = imagecolorallocate($image, 255, 138, 199);
	$white      = imagecolorallocate($image, 255, 255, 255);
	$muted      = imagecolorallocate($image, 218, 203, 212);
	$line       = imagecolorallocate($image, 82, 58, 74);

	imagefilledrectangle($image, 0, 0, 1000, 1500, $black);
	for ($y = 0; $y < 1500; $y += 56) {
		imageline($image, 0, $y, 1000, $y, imagecolorallocate($image, 13, 12, 13));
	}
	for ($x = 0; $x < 1000; $x += 56) {
		imageline($image, $x, 0, $x, 1500, imagecolorallocate($image, 13, 12, 13));
	}

	if (function_exists('bbb_quote_pin_image_filled_rounded_rect')) {
		bbb_quote_pin_image_filled_rounded_rect($image, 70, 70, 860, 1360, 18, $panel);
		bbb_quote_pin_image_filled_rounded_rect($image, 120, 820, 760, 225, 18, $soft_panel);
		bbb_quote_pin_image_filled_rounded_rect($image, 120, 1080, 760, 225, 18, $soft_panel);
	} else {
		imagefilledrectangle($image, 70, 70, 930, 1430, $panel);
		imagefilledrectangle($image, 120, 820, 880, 1045, $soft_panel);
		imagefilledrectangle($image, 120, 1080, 880, 1305, $soft_panel);
	}
	imagerectangle($image, 70, 70, 930, 1430, $line);

	$display_font = bbb_bingo_pin_font('italic');
	$bold_font    = bbb_bingo_pin_font('bold');

	bbb_bingo_pin_draw_text($image, '#bookishbabebingo', 120, 150, $pink, $bold_font, 24);
	$next_y = bbb_bingo_pin_draw_wrapped($image, $type, 120, 270, 760, 88, 3, $white, $display_font, 78);
	bbb_bingo_pin_draw_wrapped($image, $copy, 120, $next_y + 42, 760, 46, 4, $muted, $bold_font, 31);

	bbb_bingo_pin_draw_text($image, 'read next', 160, 885, $pink, $bold_font, 24);
	bbb_bingo_pin_draw_wrapped($image, $book, 160, 950, 650, 48, 2, $white, $bold_font, 39);

	bbb_bingo_pin_draw_text($image, 'would ruin you', 160, 1145, $pink, $bold_font, 24);
	bbb_bingo_pin_draw_wrapped($image, $man, 160, 1210, 650, 48, 2, $white, $bold_font, 39);

	$footer = 'bybookishbabe.com/romance-reading-bingo';
	$footer_width = bbb_bingo_pin_text_width($footer, $bold_font, 22);
	bbb_bingo_pin_draw_text($image, $footer, (int) floor((1000 - $footer_width) / 2), 1382, $muted, $bold_font, 22);

	status_header(200);
	header('Content-Type: image/png');
	header('Cache-Control: public, max-age=86400');
	imagepng($image, null, 9);
	imagedestroy($image);
	exit;
}
add_action('template_redirect', 'bbb_bingo_pin_render', -2100);
