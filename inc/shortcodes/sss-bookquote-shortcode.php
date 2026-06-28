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
		if ($quote_book instanceof WP_Post) {
			return false;
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
	if ($book_ids) {
		return false;
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

function bbb_bookquote_normalize_selector(string $value): string {
	$value = html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES, get_bloginfo('charset'));
	$value = strtolower($value);
	$value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

	return trim((string) preg_replace('/\s+/', ' ', $value));
}

function bbb_bookquote_match_tokens(string $value): array {
	$value = bbb_bookquote_normalize_selector($value);
	if ('' === $value) {
		return array();
	}

	return array_values(
		array_filter(
			explode(' ', $value),
			static fn(string $token): bool => !in_array($token, array('a', 'an', 'and', 'between', 'the'), true)
		)
	);
}

function bbb_bookquote_tokens_match(string $needle, string $haystack): bool {
	$needle_tokens = bbb_bookquote_match_tokens($needle);
	if (!$needle_tokens) {
		return false;
	}

	$haystack_tokens = bbb_bookquote_match_tokens($haystack);
	if (!$haystack_tokens) {
		return false;
	}

	return count(array_diff($needle_tokens, $haystack_tokens)) === 0;
}

function bbb_bookquote_book_from_name(string $name): ?WP_Post {
	$name = trim(wp_strip_all_tags($name));
	if ('' === $name) {
		return null;
	}

	if (function_exists('sss_article_book_from_name')) {
		$book = sss_article_book_from_name($name);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
	if (!$post_types) {
		return null;
	}

	$books = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ($books as $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}

		if (bbb_bookquote_tokens_match($name, get_the_title($book) . ' ' . (string) $book->post_name)) {
			return $book;
		}
	}

	return null;
}

function bbb_bookquote_all_for_book(WP_Post $book): array {
	if (function_exists('bbb_book_quote_posts')) {
		return bbb_book_quote_posts($book);
	}

	$quote_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array('sss_quote', 'bbb_quote');
	$quote_types = array_values(array_filter($quote_types, 'post_type_exists'));
	if (!$quote_types) {
		return array();
	}

	$quotes = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	return array_values(
		array_filter(
			$quotes,
			static fn($quote): bool => $quote instanceof WP_Post && bbb_bookquote_quote_book_matches($quote, $book)
		)
	);
}

function bbb_bookquote_all_quotes(): array {
	$quote_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array('sss_quote', 'bbb_quote');
	$quote_types = array_values(array_filter($quote_types, 'post_type_exists'));
	if (!$quote_types) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
}

function bbb_bookquote_quote_matches_selector(WP_Post $quote, string $selector): bool {
	$selector = trim(wp_strip_all_tags($selector));
	if ('' === $selector) {
		return true;
	}

	if (ctype_digit($selector) && (int) $quote->ID === (int) $selector) {
		return true;
	}

	$selector_slug = sanitize_title($selector);
	$quote_title   = get_the_title($quote);
	$quote_text    = bbb_bookquote_quote_text($quote);
	if ('' !== $selector_slug && in_array($selector_slug, array(sanitize_title((string) $quote->post_name), sanitize_title($quote_title)), true)) {
		return true;
	}

	$selector_match = bbb_bookquote_normalize_selector($selector);
	$quote_match    = bbb_bookquote_normalize_selector($quote_text);
	$title_match    = bbb_bookquote_normalize_selector($quote_title);

	return '' !== $selector_match
		&& (
			$selector_match === $quote_match
			|| $selector_match === $title_match
			|| str_contains($quote_match, $selector_match)
			|| str_contains($title_match, $selector_match)
		);
}

function bbb_bookquote_find_specific_quote_for_book(WP_Post $book, string $selector): ?WP_Post {
	$selector = trim(wp_strip_all_tags($selector));
	if ('' === $selector) {
		return bbb_bookquote_find_quote_for_book($book);
	}

	$quotes = bbb_bookquote_all_for_book($book);
	if (!$quotes) {
		return null;
	}

	if (ctype_digit($selector)) {
		$quote_id = (int) $selector;
		foreach ($quotes as $quote) {
			if ($quote instanceof WP_Post && bbb_bookquote_quote_matches_selector($quote, (string) $quote_id)) {
				return $quote;
			}
		}
	}

	foreach ($quotes as $quote) {
		if ($quote instanceof WP_Post && bbb_bookquote_quote_matches_selector($quote, $selector)) {
			return $quote;
		}
	}

	return null;
}

function bbb_bookquote_find_specific_quote_global(string $selector): ?WP_Post {
	foreach (bbb_bookquote_all_quotes() as $quote) {
		if ($quote instanceof WP_Post && bbb_bookquote_quote_matches_selector($quote, $selector)) {
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
	$title  = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book);
	$author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : $author;

	ob_start();
	?>
<blockquote class="bbb-bookquote">
  <p>&ldquo;<?php echo esc_html($text); ?>&rdquo;</p>
  <cite><em><?php echo esc_html($title); ?></em><?php echo '' !== $author ? ' by ' . esc_html($author) : ''; ?></cite>
</blockquote>
	<?php
	return ob_get_clean();
}

function bbb_bookquote_specific_render(WP_Post $quote, WP_Post $book): string {
	$text = bbb_bookquote_quote_text($quote);
	if ('' === $text) {
		return '';
	}

	$title      = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book);
	$author     = function_exists('sss_article_field') ? (string) sss_article_field('author', $book->ID, '') : '';
	$author     = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : $author;
	$quotes_url = function_exists('bbb_book_quotes_url') ? bbb_book_quotes_url((int) $book->ID) : home_url('/sss-quote-wall/');
	$quote_url  = $quotes_url . '#quote-' . (string) $quote->ID;

	ob_start();
	?>
<figure class="bbb-specific-quote">
  <blockquote class="bbb-specific-quote__quote">
    <p>&ldquo;<?php echo esc_html($text); ?>&rdquo;</p>
  </blockquote>
  <figcaption class="bbb-specific-quote__source">
    <span><em><?php echo esc_html($title); ?></em><?php echo '' !== $author ? ' by ' . esc_html($author) : ''; ?></span>
    <a class="bbb-specific-quote__link" href="<?php echo esc_url($quote_url); ?>">all <?php echo esc_html($title); ?> quotes</a>
  </figcaption>
</figure>
	<?php
	return ob_get_clean();
}

function bbb_bookquote_specific_render_text(string $text, WP_Post $book): string {
	$text = trim(wp_strip_all_tags($text));
	if ('' === $text) {
		return '';
	}

	$title      = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book);
	$author     = function_exists('sss_article_field') ? (string) sss_article_field('author', $book->ID, '') : '';
	$author     = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : $author;
	$quotes_url = function_exists('bbb_book_quotes_url') ? bbb_book_quotes_url((int) $book->ID) : home_url('/sss-quote-wall/');

	ob_start();
	?>
<figure class="bbb-specific-quote">
  <blockquote class="bbb-specific-quote__quote">
    <p>&ldquo;<?php echo esc_html($text); ?>&rdquo;</p>
  </blockquote>
  <figcaption class="bbb-specific-quote__source">
    <span><em><?php echo esc_html($title); ?></em><?php echo '' !== $author ? ' by ' . esc_html($author) : ''; ?></span>
    <a class="bbb-specific-quote__link" href="<?php echo esc_url($quotes_url); ?>">all <?php echo esc_html($title); ?> quotes</a>
  </figcaption>
</figure>
	<?php
	return ob_get_clean();
}

function bbb_bookquote_specific_render_named_text(string $text, string $book_name): string {
	$text = trim(wp_strip_all_tags($text));
	$title = trim(wp_strip_all_tags($book_name));
	if ('' === $text || '' === $title) {
		return '';
	}

	$quotes_url = home_url('/sss-quote-wall/');

	ob_start();
	?>
<figure class="bbb-specific-quote">
  <blockquote class="bbb-specific-quote__quote">
    <p>&ldquo;<?php echo esc_html($text); ?>&rdquo;</p>
  </blockquote>
  <figcaption class="bbb-specific-quote__source">
    <span><em><?php echo esc_html($title); ?></em></span>
    <a class="bbb-specific-quote__link" href="<?php echo esc_url($quotes_url); ?>">all <?php echo esc_html($title); ?> quotes</a>
  </figcaption>
</figure>
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
	if ('' === $name) {
		return '';
	}

	$book = bbb_bookquote_book_from_name($name);
	if (!$book instanceof WP_Post) {
		return bbb_bookquote_specific_render_named_text((string) $atts['quote'], $name);
	}

	$quote = bbb_bookquote_find_quote_for_book($book);
	if (!$quote instanceof WP_Post) {
		return '';
	}

	return bbb_bookquote_render($quote, $book);
}
add_shortcode('bookquote', 'bbb_bookquote_shortcode');

function bbb_specific_bookquote_shortcode($atts): string {
	$atts = shortcode_atts(
		array(
			'book'  => '',
			'name'  => '',
			'quote' => '',
			'id'    => '',
		),
		is_array($atts) ? $atts : array(),
		'specificbookquote'
	);

	$name = trim((string) ($atts['book'] ?: $atts['name']));
	if ('' === $name) {
		return '';
	}

	$book = bbb_bookquote_book_from_name($name);
	if (!$book instanceof WP_Post) {
		return bbb_bookquote_specific_render_named_text((string) $atts['quote'], $name);
	}

	$selector = trim((string) ($atts['id'] ?: $atts['quote']));
	$quote    = bbb_bookquote_find_specific_quote_for_book($book, $selector);
	if (!$quote instanceof WP_Post && '' !== $selector) {
		$quote = bbb_bookquote_find_specific_quote_global($selector);
	}
	if (!$quote instanceof WP_Post) {
		return bbb_bookquote_specific_render_text($selector, $book);
	}

	return bbb_bookquote_specific_render($quote, $book);
}
add_shortcode('specificbookquote', 'bbb_specific_bookquote_shortcode');

function bbb_specificquote_shortcode($atts): string {
	$raw = '';
	if (is_array($atts)) {
		$raw = trim(implode(' ', array_map('strval', $atts)));
	} else {
		$raw = trim((string) $atts);
	}

	$raw = preg_replace('/^\s*:/', '', $raw) ?? $raw;
	$parts = array_map(
		static fn($part): string => trim(wp_strip_all_tags((string) $part)),
		explode('|', $raw, 2)
	);

	$name = $parts[0] ?? '';
	if ('' === $name) {
		return '';
	}

	return bbb_specific_bookquote_shortcode(
		array(
			'book'  => $name,
			'quote' => $parts[1] ?? '',
		)
	);
}
add_shortcode('specificquote', 'bbb_specificquote_shortcode');

function bbb_specificquote_content_tokens(string $content): string {
	return preg_replace_callback(
		'/\[specificquote:([^\]\r\n]+)\]/i',
		static function (array $matches): string {
			$parts = array_map(
				static fn($part): string => trim(wp_strip_all_tags((string) $part)),
				explode('|', (string) $matches[1], 2)
			);
			$name = $parts[0] ?? '';
			if ('' === $name) {
				return $matches[0];
			}

			$quote = $parts[1] ?? '';
			if ('' === $quote) {
				return sprintf('[specificbookquote book="%s"]', esc_attr($name));
			}

			return sprintf('[specificbookquote book="%s" quote="%s"]', esc_attr($name), esc_attr($quote));
		},
		$content
	) ?? $content;
}
add_filter('the_content', 'bbb_specificquote_content_tokens', 8);
