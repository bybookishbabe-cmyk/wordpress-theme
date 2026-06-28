<?php
/**
 * Site-wide capitalization standard.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_brand_lowercase(string $text): string {
	if (function_exists('mb_strtolower')) {
		return mb_strtolower($text, 'UTF-8');
	}

	return strtolower($text);
}

function bbb_brand_proper_phrase_key(string $phrase): string {
	$phrase = html_entity_decode(wp_strip_all_tags($phrase), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
	$phrase = trim((string) preg_replace('/\s+/', ' ', $phrase));

	return bbb_brand_lowercase($phrase);
}

function bbb_brand_is_single_word_phrase(string $phrase): bool {
	return 1 === str_word_count(str_replace(array("'", '’'), '', $phrase), 0);
}

function bbb_brand_add_proper_phrase(array &$phrases, string $phrase, string $type = 'proper'): void {
	$phrase = trim(wp_strip_all_tags($phrase));
	if ('' === $phrase) {
		return;
	}

	if ('book' === $type && function_exists('bbb_bookish_book_title')) {
		$phrase = bbb_bookish_book_title($phrase);
	} elseif (function_exists('bbb_bookish_proper_name')) {
		$phrase = bbb_bookish_proper_name($phrase);
	}

	if ('' === $phrase || strlen($phrase) < 3) {
		return;
	}

	if ('book' === $type && bbb_brand_is_single_word_phrase($phrase)) {
		return;
	}

	$phrases[bbb_brand_proper_phrase_key($phrase)] = $phrase;
}

function bbb_brand_base_proper_phrases(array &$phrases, bool $include_ambiguous_books = false): void {
	foreach (
		array(
			'Crown Me Yours'      => 'book',
			'The Bronze Horseman' => 'book',
			'Shatter Me'          => 'book',
			'The Right Move'      => 'book',
			'Fourth Wing'         => 'book',
			'My Dreadful Darling' => 'book',
			'Please Don\'t Go'    => 'book',
			'Twisted Pawn'        => 'book',
			'Daggermouth'         => 'book',
			'Liz Tomforde'        => 'proper',
			'Liv Zander'          => 'proper',
			'H.D. Carlton'        => 'proper',
			'H.M. Wolfe'          => 'proper',
			'E. Salvador'         => 'proper',
			'Tahereh Mafi'        => 'proper',
			'Heartstring Duet'    => 'proper',
			'Ryan Shay'           => 'proper',
			'Aaron Warner'        => 'proper',
			'Alex Lancaster'      => 'proper',
			'Ryker Bennett'       => 'proper',
			'Xaden Riorson'       => 'proper',
			'BookTok'             => 'proper',
			'Canva'               => 'proper',
			'Kindle Unlimited'    => 'proper',
			'Kindle Paperwhite'   => 'proper',
			'Kindle Color'        => 'proper',
			'Kindle'              => 'proper',
			'PDF'                 => 'proper',
			'WordPress'           => 'proper',
			'WooCommerce'         => 'proper',
			'WP Engine'           => 'proper',
			'Local'               => 'proper',
			'Shopify'             => 'proper',
			'Substack'            => 'proper',
			'Instagram'           => 'proper',
			'TikTok'              => 'proper',
			'Threads'             => 'proper',
			'Pinterest'           => 'proper',
			'Amazon'              => 'proper',
			'Bookshop.org'        => 'proper',
			'Bookshop'            => 'proper',
		) as $phrase => $type
	) {
		if ($include_ambiguous_books && 'book' !== $type) {
			continue;
		}

		bbb_brand_add_proper_phrase($phrases, $phrase, $type);
	}
}

function bbb_brand_dynamic_proper_phrases(array &$phrases, bool $include_ambiguous_books = false): void {
	if (function_exists('get_posts')) {
		$posts = get_posts(
			array(
				'post_type'      => array('bbb_book', 'sss_book', 'sss_series', 'bbb_boyfriend'),
				'post_status'    => array('publish', 'draft', 'private', 'future', 'pending'),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ($posts as $post_id) {
			$post_id   = (int) $post_id;
			$post_type = get_post_type($post_id);
			$title     = get_post_field('post_title', $post_id);

			if (in_array($post_type, array('bbb_book', 'sss_book'), true)) {
				if ($include_ambiguous_books) {
					$book_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title((string) $title) : (string) $title;
					if (bbb_brand_is_single_word_phrase($book_title)) {
						$phrases[bbb_brand_proper_phrase_key($book_title)] = $book_title;
					}
				} else {
					bbb_brand_add_proper_phrase($phrases, (string) $title, 'book');
				}
			} elseif (!$include_ambiguous_books && in_array($post_type, array('sss_series', 'bbb_boyfriend'), true)) {
				bbb_brand_add_proper_phrase($phrases, (string) $title);
			}

			if (!$include_ambiguous_books) {
				foreach (array('_bbb_author', 'sss_author', 'author', '_bbb_series_author', 'series_author') as $meta_key) {
					bbb_brand_add_proper_phrase($phrases, (string) get_post_meta($post_id, $meta_key, true));
				}
			}
		}
	}

	if (!$include_ambiguous_books && function_exists('get_terms')) {
		$terms = get_terms(
			array(
				'taxonomy'   => array('bbb_series', 'sss_series'),
				'hide_empty' => false,
			)
		);

		if (!is_wp_error($terms)) {
			foreach ($terms as $term) {
				if ($term instanceof WP_Term) {
					bbb_brand_add_proper_phrase($phrases, $term->name);
				}
			}
		}
	}
}

function bbb_brand_proper_phrases(): array {
	static $phrases = null;

	if (null !== $phrases) {
		return $phrases;
	}

	$phrases = array();
	bbb_brand_base_proper_phrases($phrases);
	bbb_brand_dynamic_proper_phrases($phrases);

	uksort(
		$phrases,
		static fn(string $a, string $b): int => strlen($b) <=> strlen($a)
	);

	return $phrases;
}

function bbb_brand_ambiguous_book_phrases(): array {
	static $phrases = null;

	if (null !== $phrases) {
		return $phrases;
	}

	$phrases = array();
	bbb_brand_dynamic_proper_phrases($phrases, true);

	ksort($phrases);

	return $phrases;
}

function bbb_brand_restore_ambiguous_book_phrases(string $text): string {
	foreach (bbb_brand_ambiguous_book_phrases() as $lower => $proper) {
		$pattern = '/(?<![\p{L}\p{N}])' . preg_quote($lower, '/') . '(?![\p{L}\p{N}])/iu';
		$text    = preg_replace_callback(
			$pattern,
			static function (array $match) use ($proper, $text): string {
				$offset = isset($match[0][1]) ? (int) $match[0][1] : 0;
				$word   = is_array($match[0]) ? (string) $match[0][0] : (string) $match[0];
				$before = bbb_brand_lowercase(substr($text, max(0, $offset - 64), min(64, $offset)));
				$after  = bbb_brand_lowercase(substr($text, $offset + strlen($word), 64));

				if (preg_match('/(?:\b(?:the|a|an|these|those|this|that|my|your|her|his|their|our)\s+)$/u', $before)) {
					return $word;
				}

				if (
					preg_match('/(?:\b(?:loved|love|like|liked|read|reading|review|reviewing|cover|title|book|series|author|for|about)\s+)$/u', $before)
					|| preg_match('/^\s+(?:by|review|book|series|cover|author)\b/u', $after)
				) {
					return $proper;
				}

				return $word;
			},
			$text,
			-1,
			$count,
			PREG_OFFSET_CAPTURE
		) ?: $text;
	}

	return $text;
}

function bbb_brand_restore_proper_phrases(string $text): string {
	foreach (bbb_brand_proper_phrases() as $lower => $proper) {
		$text = preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($lower, '/') . '(?![\p{L}\p{N}])/iu', $proper, $text) ?: $text;
	}

	$text = bbb_brand_restore_ambiguous_book_phrases($text);
	$text = preg_replace_callback(
		'/\b((?:[a-z]\.){1,3})\s+([a-z][\p{L}\'’-]*)/iu',
		static function (array $match): string {
			$initials = strtoupper($match[1]);
			$name = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($match[2]) : ucfirst(strtolower($match[2]));

			return $initials . ' ' . $name;
		},
		$text
	) ?: $text;

	return $text;
}

function bbb_brand_standard_text($text) {
	if (!is_scalar($text)) {
		return $text;
	}

	return bbb_brand_restore_proper_phrases(bbb_brand_lowercase((string) $text));
}

function bbb_brand_standard_title($title, $post_id = 0) {
	if (is_admin() || !is_scalar($title)) {
		return $title;
	}

	$post = $post_id ? get_post((int) $post_id) : null;
	if (!$post instanceof WP_Post) {
		return $title;
	}

	if (in_array($post->post_type, array('bbb_book', 'sss_book'), true)) {
		return function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title((string) $title) : $title;
	}

	if (in_array($post->post_type, array('sss_series', 'bbb_boyfriend'), true)) {
		return function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name((string) $title) : $title;
	}

	if (function_exists('bbb_brand_should_skip_post') && bbb_brand_should_skip_post($post)) {
		return $title;
	}

	return bbb_brand_standard_text($title);
}

function bbb_brand_should_skip_post(WP_Post $post): bool {
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

	return 'page' === $post->post_type && in_array((string) $post->post_name, $legal_slugs, true);
}

function bbb_brand_standard_menu_title($title): string {
	return (string) bbb_brand_standard_text($title);
}

function bbb_brand_standard_alt_text($attr, WP_Post $attachment, $size) {
	if (!is_array($attr) || empty($attr['alt']) || !is_scalar($attr['alt'])) {
		return $attr;
	}

	$attr['alt'] = bbb_brand_standard_text((string) $attr['alt']);

	return $attr;
}

function bbb_brand_sync_content_on_save(int $post_id, WP_Post $post, bool $update): void {
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}

	if (bbb_brand_should_skip_post($post)) {
		return;
	}

	if (in_array($post->post_type, array('bbb_book', 'sss_book'), true)) {
		$new_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($post->post_title) : (string) $post->post_title;
	} elseif (in_array($post->post_type, array('sss_series', 'bbb_boyfriend'), true)) {
		$new_title = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($post->post_title) : (string) $post->post_title;
	} elseif (in_array($post->post_type, array('post', 'page', 'download', 'newsletter_issue', 'bbb_newsletter_issue'), true)) {
		$new_title = (string) bbb_brand_standard_text($post->post_title);
	} else {
		$new_title = (string) $post->post_title;
	}

	if ($new_title !== $post->post_title) {
		remove_action('save_post', 'bbb_brand_sync_content_on_save', 50);
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $new_title,
			)
		);
		add_action('save_post', 'bbb_brand_sync_content_on_save', 50, 3);
	}

	foreach (
		array(
			'rank_math_title',
			'rank_math_description',
			'rank_math_facebook_title',
			'rank_math_facebook_description',
			'rank_math_twitter_title',
			'rank_math_twitter_description',
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'_yoast_wpseo_opengraph-title',
			'_yoast_wpseo_opengraph-description',
			'_yoast_wpseo_twitter-title',
			'_yoast_wpseo_twitter-description',
			'_issue_title_override',
			'_issue_subtitle',
			'_issue_excerpt',
			'_issue_preview_alt',
		) as $meta_key
	) {
		$value = get_post_meta($post_id, $meta_key, true);
		if (is_scalar($value) && '' !== trim((string) $value)) {
			$standard = bbb_brand_standard_text((string) $value);
			if ($standard !== (string) $value) {
				update_post_meta($post_id, $meta_key, $standard);
			}
		}
	}
}

add_filter('the_title', 'bbb_brand_standard_title', PHP_INT_MAX, 2);
add_filter('single_post_title', 'bbb_brand_standard_title', PHP_INT_MAX, 2);
add_filter('nav_menu_item_title', 'bbb_brand_standard_menu_title', PHP_INT_MAX);
add_filter('wp_get_attachment_image_attributes', 'bbb_brand_standard_alt_text', PHP_INT_MAX, 3);
add_action('save_post', 'bbb_brand_sync_content_on_save', 50, 3);

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
	) as $bbb_seo_brand_filter
) {
	add_filter($bbb_seo_brand_filter, 'bbb_brand_standard_text', PHP_INT_MAX);
}

function bbb_brand_standard_rank_math_schema_text(array $data): array {
	$seo_keys = array('description', 'headline', 'name', 'title');

	$standardize_schema_text = static function ($value) use (&$standardize_schema_text, $seo_keys) {
		if (!is_array($value)) {
			return $value;
		}

		foreach ($value as $key => $child) {
			if (is_string($key) && in_array($key, $seo_keys, true) && is_string($child)) {
				$value[$key] = bbb_brand_standard_text($child);
				continue;
			}

			$value[$key] = $standardize_schema_text($child);
		}

		return $value;
	};

	return $standardize_schema_text($data);
}
add_filter('rank_math/json_ld', 'bbb_brand_standard_rank_math_schema_text', PHP_INT_MAX);

function bbb_brand_start_document_title_standardizer(): void {
	if (is_admin()) {
		return;
	}

	ob_start(
		static function (string $html): string {
			return preg_replace_callback(
				'/(<title>)(.*?)(<\/title>)/isu',
				static fn(array $match): string => $match[1] . esc_html((string) bbb_brand_standard_text(html_entity_decode(wp_strip_all_tags($match[2]), ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8'))) . $match[3],
				$html
			) ?: $html;
		}
	);
}
add_action('template_redirect', 'bbb_brand_start_document_title_standardizer', -1000);

function bbb_brand_start_head_meta_standardizer(): void {
	ob_start();
}

function bbb_brand_flush_head_meta_standardizer(): void {
	$head = ob_get_clean();
	if (!is_string($head) || '' === $head) {
		return;
	}

	$head = preg_replace_callback(
		'/(<meta\s+(?:property|name)=["\'](?:og:title|og:description|og:image:alt|twitter:title|twitter:description|twitter:image:alt)["\']\s+content=["\'])([^"\']*)(["\'][^>]*>)/iu',
		static fn(array $match): string => $match[1] . esc_attr((string) bbb_brand_standard_text(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8'))) . $match[3],
		$head
	) ?: $head;

	echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('wp_head', 'bbb_brand_start_head_meta_standardizer', 0);
add_action('wp_head', 'bbb_brand_flush_head_meta_standardizer', PHP_INT_MAX);

function bbb_brand_standard_content_now(): array {
	$changed = array(
		'titles' => 0,
		'meta'   => 0,
	);

	$posts = get_posts(
		array(
			'post_type'      => array('post', 'page', 'download', 'bbb_book', 'sss_book', 'sss_series', 'bbb_boyfriend', 'newsletter_issue', 'bbb_newsletter_issue'),
			'post_status'    => array('publish', 'draft', 'private', 'future', 'pending'),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ($posts as $post) {
		if (!$post instanceof WP_Post || bbb_brand_should_skip_post($post)) {
			continue;
		}

		if (in_array($post->post_type, array('bbb_book', 'sss_book'), true)) {
			$new_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($post->post_title) : (string) $post->post_title;
		} elseif (in_array($post->post_type, array('sss_series', 'bbb_boyfriend'), true)) {
			$new_title = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($post->post_title) : (string) $post->post_title;
		} elseif (in_array($post->post_type, array('post', 'page', 'download', 'newsletter_issue', 'bbb_newsletter_issue'), true)) {
			$new_title = (string) bbb_brand_standard_text($post->post_title);
		} else {
			$new_title = (string) $post->post_title;
		}
		if ($new_title !== $post->post_title) {
			wp_update_post(
				array(
					'ID'         => $post->ID,
					'post_title' => $new_title,
				)
			);
			$changed['titles']++;
		}

		foreach (
			array(
				'rank_math_title',
				'rank_math_description',
				'rank_math_facebook_title',
				'rank_math_facebook_description',
				'rank_math_twitter_title',
				'rank_math_twitter_description',
				'_yoast_wpseo_title',
				'_yoast_wpseo_metadesc',
				'_yoast_wpseo_opengraph-title',
				'_yoast_wpseo_opengraph-description',
				'_yoast_wpseo_twitter-title',
				'_yoast_wpseo_twitter-description',
				'_issue_title_override',
				'_issue_subtitle',
				'_issue_excerpt',
				'_issue_preview_alt',
			) as $meta_key
		) {
			$value = get_post_meta($post->ID, $meta_key, true);
			if (!is_scalar($value) || '' === trim((string) $value)) {
				continue;
			}

			$standard = bbb_brand_standard_text((string) $value);
			if ($standard !== (string) $value) {
				update_post_meta($post->ID, $meta_key, $standard);
				$changed['meta']++;
			}
		}
	}

	return $changed;
}
