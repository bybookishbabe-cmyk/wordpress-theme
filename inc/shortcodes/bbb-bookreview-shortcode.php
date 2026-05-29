<?php
/**
 * Spoiler-free book review snapshot shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_bookreview_clean_vibe_text(string $text): string {
	$text  = trim(wp_strip_all_tags($text));
	$lines = preg_split('/\R/u', $text) ?: array($text);
	$clean = array();

	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ('' === $line) {
			continue;
		}

		$line = preg_replace('/\s*🌶.*$/u', '', $line) ?? $line;
		$line = preg_replace('/\s*(?:—|-|–)?\s*(?:\d+\s*\/\s*5|high|medium|low|mild|soft)\s+spice\.?.*$/iu', '', $line) ?? $line;
		$line = preg_replace('/\s*(?:—|-|–)\s*$/u', '', $line) ?? $line;
		$line = trim($line);

		if ('' !== $line) {
			$clean[] = $line;
		}
	}

	return implode("\n", $clean);
}

function bbb_bookreview_book_from_atts(array $atts): ?WP_Post {
	if (!empty($atts['name']) && function_exists('sss_article_book_from_name')) {
		$book = sss_article_book_from_name((string) $atts['name']);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	if (!empty($atts['id'])) {
		$book = get_post((int) $atts['id']);
		if ($book instanceof WP_Post && in_array($book->post_type, array('bbb_book', 'sss_book'), true)) {
			return $book;
		}
	}

	$post_id = (int) ($atts['post_id'] ?? get_the_ID());
	if ($post_id > 0 && function_exists('sss_article_books_for_post')) {
		$books = sss_article_books_for_post($post_id);
		$book  = $books[max(0, (int) ($atts['index'] ?? 1) - 1)] ?? null;

		return $book instanceof WP_Post ? $book : null;
	}

	return null;
}

function bbb_bookreview_meta(int $book_id, string $key): string {
	$value = get_post_meta($book_id, $key, true);

	return is_scalar($value) ? trim((string) $value) : '';
}

function bbb_bookreview_render_for_book(WP_Post $book): string {
	$book_id = (int) $book->ID;
	$data    = function_exists('sss_article_book_data') ? sss_article_book_data($book_id) : array();
	$title   = strtolower(get_the_title($book));
	$tropes  = array_filter(array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array())));
	$spice   = max(0, min(5, (int) ($data['spice'] ?? get_post_meta($book_id, '_bbb_spice', true))));

	$verdict          = bbb_bookreview_meta($book_id, '_bbb_verdict');
	$vibe_description = bbb_bookreview_clean_vibe_text(bbb_bookreview_meta($book_id, '_bbb_vibe_description'));
	$spice_words      = bbb_bookreview_meta($book_id, '_bbb_spice_words');
	$read_this_if     = bbb_bookreview_meta($book_id, '_bbb_read_this_if');
	$skip_this_if     = bbb_bookreview_meta($book_id, '_bbb_skip_this_if');
	$content_warnings = bbb_bookreview_meta($book_id, '_bbb_content_warnings');
	$standalone_hea   = bbb_bookreview_meta($book_id, '_bbb_standalone_hea');

	if ('' === $verdict) {
		$verdict = (string) ($data['mini'] ?? '');
	}
	if ('' === $vibe_description) {
		$vibe_description = implode(', ', $tropes);
	}
	if ('' === $spice_words && $spice > 0) {
		$spice_words = str_repeat('🌶', $spice) . ' — ' . $spice . '/5. read this when you want the romance to bring the heat level shown on the book page.';
	}
	if ('' === $read_this_if) {
		$read_this_if = 'you want ' . strtolower(implode(', ', array_slice($tropes, 0, 3))) . ', a clear spice rating, and a quick gut-check before you commit.';
	}
	if ('' === $skip_this_if) {
		$skip_this_if = 'you need something softer, lighter, or lower-stress right now.';
	}
	if ('' === $content_warnings) {
		$content_warnings = 'check the book page and detailed reviews first if you need exact trigger guidance.';
	}
	if ('' === $standalone_hea) {
		$standalone_hea = !empty($data['standalone']) ? 'standalone — safe to read without committing to a series.' : 'check the book page before committing if you want strict standalone reads.';
	}

	$quote_markup = '';
	if (function_exists('bbb_bookquote_find_quote_for_book') && function_exists('bbb_bookquote_render')) {
		$quote = bbb_bookquote_find_quote_for_book($book);
		if ($quote instanceof WP_Post) {
			$quote_markup = bbb_bookquote_render($quote, $book);
		}
	}

	ob_start();
	?>
<section class="bbb-review-snapshot">
  <p class="bbb-review-snapshot__kicker">review</p>
  <h2 class="bbb-review-snapshot__title">no spoilers edition</h2>
  <?php if ('' !== $verdict) : ?>
  <div class="bbb-review-snapshot__card bbb-review-snapshot__card--feature"><h3>verdict</h3><p><?php echo esc_html(strtolower($verdict)); ?></p></div>
  <?php endif; ?>
  <?php if ('' !== $vibe_description) : ?>
  <div class="bbb-review-snapshot__card"><h3>vibe</h3><p><?php echo esc_html(strtolower($vibe_description)); ?></p></div>
  <?php endif; ?>
  <?php if ('' !== $spice_words) : ?>
  <div class="bbb-review-snapshot__card"><h3>spice</h3><p><?php echo esc_html(strtolower($spice_words)); ?></p></div>
  <?php endif; ?>
  <?php if ('' !== $quote_markup) : ?>
  <?php echo $quote_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <?php endif; ?>
  <div class="bbb-review-snapshot__card"><h3>reader fit</h3><div class="bbb-review-snapshot__fit"><div class="bbb-review-snapshot__fitCard bbb-review-snapshot__fitCard--read"><strong>read this if</strong><p><?php echo esc_html(strtolower($read_this_if)); ?></p></div><div class="bbb-review-snapshot__fitCard bbb-review-snapshot__fitCard--skip"><strong>skip this if</strong><p><?php echo esc_html(strtolower($skip_this_if)); ?></p></div></div></div>
  <div class="bbb-review-snapshot__card bbb-review-snapshot__card--warning"><h3>content warnings</h3><p><?php echo esc_html(strtolower($content_warnings)); ?></p></div>
  <div class="bbb-review-snapshot__card bbb-review-snapshot__card--status"><h3>standalone + hea</h3><p><?php echo esc_html(strtolower($standalone_hea)); ?></p></div>
</section>
	<?php
	return ob_get_clean();
}

function bbb_bookreview_shortcode($atts): string {
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'index'   => 1,
			'name'    => '',
			'post_id' => get_the_ID(),
		),
		$atts,
		'bookreview'
	);

	$book = bbb_bookreview_book_from_atts($atts);
	if (!$book instanceof WP_Post) {
		return '';
	}

	return bbb_bookreview_render_for_book($book);
}
add_shortcode('bookreview', 'bbb_bookreview_shortcode');
