<?php
/**
 * Article book quote shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_bookquote_quote_text(WP_Post $quote): string {
	foreach (array('_quote_text', 'quote_text', 'quote', '_bbb_quote') as $key) {
		$text = trim((string) get_post_meta($quote->ID, $key, true));
		if ('' !== $text) {
			return $text;
		}
	}

	return trim(wp_strip_all_tags((string) $quote->post_content));
}

function bbb_bookquote_quote_book_matches(WP_Post $quote, WP_Post $book): bool {
	if (function_exists('bbb_quote_wall_book')) {
		$quote_book = bbb_quote_wall_book($quote);
		if ($quote_book instanceof WP_Post && (int) $quote_book->ID === (int) $book->ID) {
			return true;
		}
	}

	$book_ids = array_filter(array(
		(int) get_post_meta($quote->ID, '_quote_book_id', true),
		(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
		(int) get_post_meta($quote->ID, 'book_id', true),
		(int) get_post_meta($quote->ID, 'library_book_id', true),
	));
	if (in_array((int) $book->ID, $book_ids, true)) {
		return true;
	}

	$book_slug  = sanitize_title((string) get_post_field('post_name', $book->ID));
	$book_title = function_exists('sss_article_match_text') ? sss_article_match_text(get_the_title($book)) : strtolower(get_the_title($book));
	$handles    = array_filter(array(
		(string) get_post_meta($quote->ID, '_quote_book_handle', true),
		(string) get_post_meta($quote->ID, 'book_handle', true),
		(string) get_post_meta($quote->ID, '_bbb_book_handle', true),
	));

	foreach ($handles as $handle) {
		if (sanitize_title($handle) === $book_slug) {
			return true;
		}
	}

	$stored_title = trim((string) get_post_meta($quote->ID, '_quote_book_title', true));
	$stored_title = '' !== $stored_title ? $stored_title : trim((string) get_post_meta($quote->ID, 'book_title', true));
	if ('' !== $stored_title) {
		$stored_match = function_exists('sss_article_match_text') ? sss_article_match_text($stored_title) : strtolower($stored_title);
		if ($stored_match === $book_title) {
			return true;
		}
	}

	return false;
}

function bbb_bookquote_find_quote_for_book(WP_Post $book): ?WP_Post {
	$quote_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array('sss_quote', 'bbb_quote');

	$direct_quotes = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'OR',
				array('key' => '_quote_book_id', 'value' => (string) $book->ID),
				array('key' => '_quote_library_book_id', 'value' => (string) $book->ID),
				array('key' => 'book_id', 'value' => (string) $book->ID),
				array('key' => 'library_book_id', 'value' => (string) $book->ID),
			),
		)
	);
	if ($direct_quotes) {
		return $direct_quotes[0];
	}

	$all_quotes = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		)
	);

	foreach ($all_quotes as $quote) {
		if ($quote instanceof WP_Post && bbb_bookquote_quote_book_matches($quote, $book)) {
			return $quote;
		}
	}

	return null;
}

function bbb_bookquote_render(WP_Post $quote, WP_Post $book): string {
	$text = bbb_bookquote_quote_text($quote);
	if ('' === $text) {
		return '';
	}

	$author = function_exists('sss_article_field') ? (string) sss_article_field('author', $book->ID, '') : '';
	$title  = function_exists('mb_strtolower') ? mb_strtolower(get_the_title($book), 'UTF-8') : strtolower(get_the_title($book));
	$author = function_exists('mb_strtolower') ? mb_strtolower($author, 'UTF-8') : strtolower($author);

	ob_start();
	?>
<blockquote class="bbb-bookquote">
  <p>&ldquo;<?php echo esc_html($text); ?>&rdquo;</p>
  <cite><em><?php echo esc_html($title); ?></em><?php echo '' !== $author ? ' by ' . esc_html($author) : ''; ?></cite>
</blockquote>
	<?php
	return ob_get_clean();
}

function bbb_bookquote_shortcode($atts, ?string $content = null, string $tag = 'bookquote'): string {
	$name = trim((string) $content);
	if ('' === $name && 'bookquote' !== $tag) {
		$name = preg_replace('/^bookquote[:-]?/i', '', $tag) ?? '';
	}
	if ('' === trim($name)) {
		$atts = shortcode_atts(array('name' => ''), is_array($atts) ? $atts : array(), 'bookquote');
		$name = trim((string) $atts['name']);
	}
	if ('' === $name || !function_exists('sss_article_book_from_name')) {
		return '';
	}

	$book = sss_article_book_from_name($name);
	if (!$book instanceof WP_Post) {
		return '';
	}

	$quote = bbb_bookquote_find_quote_for_book($book);
	if (!$quote instanceof WP_Post) {
		return '';
	}

	return bbb_bookquote_render($quote, $book);
}
add_shortcode('bookquote', 'bbb_bookquote_shortcode');
