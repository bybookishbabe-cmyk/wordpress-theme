<?php
/**
 * SEO defaults for single book pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_book_seo_current_id(): int {
	if (!is_singular('bbb_book')) {
		return 0;
	}

	return (int) get_queried_object_id();
}

function bbb_book_seo_clean_text(string $text): string {
	$text = wp_strip_all_tags(strip_shortcodes($text), true);
	$text = preg_replace('/\s+/', ' ', $text);

	return trim((string) $text);
}

function bbb_book_seo_trim(string $text, int $limit = 155): string {
	$text = bbb_book_seo_clean_text($text);
	if (strlen($text) <= $limit) {
		return $text;
	}

	$trimmed = substr($text, 0, $limit - 1);
	$trimmed = preg_replace('/\s+\S*$/', '', $trimmed);

	return rtrim((string) $trimmed, " \t\n\r\0\x0B,.") . '.';
}

function bbb_book_seo_trope_names(int $post_id, int $limit = 3): array {
	$names = array();
	foreach (array('bbb_trope', 'sss_trope') as $taxonomy) {
		$terms = get_the_terms($post_id, $taxonomy);
		if (!is_array($terms)) {
			continue;
		}

		foreach ($terms as $term) {
			if ($term instanceof WP_Term && '' !== trim($term->name)) {
				$names[] = strtolower(trim($term->name));
			}
		}
	}

	return array_slice(array_values(array_unique($names)), 0, $limit);
}

function bbb_book_seo_author(int $post_id): string {
	if (function_exists('bbb_get_book_author')) {
		return bbb_book_seo_clean_text(bbb_get_book_author($post_id));
	}

	return bbb_book_seo_clean_text((string) get_post_meta($post_id, '_bbb_author', true));
}

function bbb_book_seo_book_data(int $post_id): array {
	if (function_exists('bbb_books_like_book_data')) {
		return bbb_books_like_book_data($post_id);
	}

	return array(
		'title'    => get_the_title($post_id),
		'author'   => bbb_book_seo_author($post_id),
		'spice'    => (int) get_post_meta($post_id, '_bbb_spice', true),
		'tension'  => (int) get_post_meta($post_id, '_bbb_tension', true),
		'damage'   => (int) get_post_meta($post_id, '_bbb_damage', true),
		'darkness' => (int) get_post_meta($post_id, '_bbb_darkness', true),
		'tropes'   => array_map(
			static fn(string $name): array => array('name' => $name),
			bbb_book_seo_trope_names($post_id, 8)
		),
	);
}

function bbb_book_seo_data_trope_names(array $data, int $limit = 8): array {
	$names = array();
	foreach ((array) ($data['tropes'] ?? array()) as $trope) {
		$name = strtolower(trim((string) ($trope['name'] ?? '')));
		if ('' !== $name) {
			$names[] = $name;
		}
	}

	return array_slice(array_values(array_unique($names)), 0, $limit);
}

function bbb_book_seo_primary_trope_text(array $trope_names): string {
	$primary = array_slice($trope_names, 0, 2);
	if (!$primary) {
		return 'romance';
	}

	$primary = array_map(
		static function (string $trope): string {
			$trope = preg_replace('/\s+romance$/', '', $trope);

			return trim((string) $trope);
		},
		$primary
	);

	return trim(implode(' ', array_filter($primary)) . ' romance');
}

function bbb_book_seo_indefinite_article(string $phrase): string {
	$phrase = strtolower(trim($phrase));
	if ('' === $phrase) {
		return 'a';
	}

	return preg_match('/^[aeiou]/', $phrase) ? 'an' : 'a';
}

function bbb_book_seo_highest_vibe(array $data): array {
	$vibes = array(
		'tension'          => (int) ($data['tension'] ?? 0),
		'emotional damage' => (int) ($data['damage'] ?? 0),
		'darkness'         => (int) ($data['darkness'] ?? 0),
	);

	arsort($vibes, SORT_NUMERIC);
	$label = (string) array_key_first($vibes);

	return array('label' => $label, 'score' => (int) $vibes[$label]);
}

function bbb_book_seo_kindle_unlimited_text(int $post_id): string {
	$raw = get_post_meta($post_id, '_bbb_ku', true);
	if ('1' === (string) $raw) {
		return 'on kindle unlimited';
	}
	if ('0' === (string) $raw) {
		return 'not on kindle unlimited';
	}

	return '';
}

function bbb_book_seo_verdict(array $data, array $trope_names): string {
	$top_shelf = !empty($data['top_shelf']) || '1' === (string) get_post_meta((int) ($data['id'] ?? 0), '_bbb_top_shelf', true);
	$reread    = !empty($data['reread']) && 'false' !== strtolower((string) $data['reread']);
	if ($top_shelf || $reread) {
		return 'yes';
	}

	$high_intensity = array('bully romance', 'captor x captive', 'dark romance', 'stalker romance', 'trauma bonding');
	foreach ($trope_names as $trope) {
		if (in_array($trope, $high_intensity, true)) {
			return 'only if you like ' . $trope;
		}
	}

	return 'depends';
}

function bbb_book_seo_lead_sentence(string $text, int $limit): string {
	$text = bbb_book_seo_clean_text($text);
	if ('' === $text) {
		return '';
	}

	if (preg_match('/^(.+?[.!?])(?:\s|$)/', $text, $matches)) {
		$sentence = trim((string) $matches[1]);
		if (strlen($sentence) <= $limit) {
			return $sentence;
		}
	}

	return bbb_book_seo_trim($text, $limit);
}

function bbb_book_seo_title(int $post_id): string {
	$title  = bbb_book_seo_clean_text(get_the_title($post_id));
	$author = bbb_book_seo_author($post_id);

	if ('' === $title) {
		return '';
	}

	$book_label = '' !== $author ? sprintf('%s by %s', $title, $author) : $title;

	return $book_label . ' — spice, tropes & verdict';
}

function bbb_book_seo_description(int $post_id): string {
	$data        = bbb_book_seo_book_data($post_id);
	$title       = bbb_book_seo_clean_text((string) ($data['title'] ?? get_the_title($post_id)));
	$trope_names = bbb_book_seo_data_trope_names($data, 4);
	$spice       = max(0, min(5, (int) ($data['spice'] ?? get_post_meta($post_id, '_bbb_spice', true))));
	$vibe        = bbb_book_seo_highest_vibe($data);
	$ku_text     = bbb_book_seo_kindle_unlimited_text($post_id);
	$verdict     = bbb_book_seo_verdict(array_merge($data, array('id' => $post_id)), $trope_names);
	$primary_set = array(
		bbb_book_seo_primary_trope_text($trope_names),
		bbb_book_seo_primary_trope_text(array_slice($trope_names, 0, 1)),
		'romance',
	);
	$primary_set = array_values(array_unique(array_filter($primary_set)));

	foreach ($primary_set as $primary) {
		foreach (array(3, 2, 1, 0) as $tag_count) {
			$tags = $tag_count > 0 && $trope_names ? implode(', ', array_slice($trope_names, 0, $tag_count)) : '';
			$text = sprintf(
				'%s is %s %s with spice %d/5 and %s %d/5.%s%s is it worth it? %s.',
				$title,
				bbb_book_seo_indefinite_article($primary),
				$primary,
				$spice,
				$vibe['label'],
				$vibe['score'],
				'' !== $tags ? ' ' . $tags . '.' : '',
				'' !== $ku_text ? ' ' . $ku_text . '.' : '',
				$verdict
			);

			if (strlen($text) <= 155) {
				return $text;
			}
		}
	}

	return bbb_book_seo_trim(
		sprintf(
			'%s: spice %d/5, %s %d/5. is it worth it? %s.',
			$title,
			$spice,
			$vibe['label'],
			$vibe['score'],
			$verdict
		),
		155
	);
}

function bbb_book_seo_cover_url(int $post_id): string {
	if (!function_exists('bbb_get_book_cover_url')) {
		return '';
	}

	return esc_url_raw(bbb_get_book_cover_url($post_id, 'full'));
}

function bbb_book_seo_focus_keyword(int $post_id): string {
	$title  = bbb_book_seo_clean_text(get_the_title($post_id));
	$author = bbb_book_seo_author($post_id);
	$keyword = trim($title . ' ' . $author);

	return '' !== $keyword ? strtolower($keyword) : '';
}

function bbb_book_seo_sync_meta(int $post_id): void {
	if ('bbb_book' !== get_post_type($post_id) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}

	$title       = bbb_book_seo_title($post_id);
	$description = bbb_book_seo_description($post_id);
	$cover       = bbb_book_seo_cover_url($post_id);
	$keyword     = bbb_book_seo_focus_keyword($post_id);

	$meta = array(
		'rank_math_title'                => $title,
		'rank_math_description'          => $description,
		'rank_math_focus_keyword'        => $keyword,
		'rank_math_facebook_title'       => $title,
		'rank_math_facebook_description' => $description,
		'rank_math_twitter_title'        => $title,
		'rank_math_twitter_description'  => $description,
		'_yoast_wpseo_title'             => $title,
		'_yoast_wpseo_metadesc'          => $description,
		'_yoast_wpseo_focuskw'           => $keyword,
		'_yoast_wpseo_opengraph-title'   => $title,
		'_yoast_wpseo_opengraph-description' => $description,
		'_yoast_wpseo_twitter-title'     => $title,
		'_yoast_wpseo_twitter-description' => $description,
	);

	if ('' !== $cover) {
		$meta['rank_math_facebook_image']     = $cover;
		$meta['rank_math_twitter_image']      = $cover;
		$meta['_yoast_wpseo_opengraph-image'] = $cover;
		$meta['_yoast_wpseo_twitter-image']   = $cover;
	}

	foreach ($meta as $key => $value) {
		if ('' === $value) {
			delete_post_meta($post_id, $key);
			continue;
		}

		update_post_meta($post_id, $key, $value);
	}
}
add_action('save_post_bbb_book', 'bbb_book_seo_sync_meta', 30);

function bbb_book_seo_filter_title(string $title): string {
	$post_id = bbb_book_seo_current_id();

	return $post_id ? bbb_book_seo_title($post_id) : $title;
}
add_filter('pre_get_document_title', 'bbb_book_seo_filter_title', 99);
add_filter('rank_math/frontend/title', 'bbb_book_seo_filter_title', 99);
add_filter('rank_math/opengraph/facebook/title', 'bbb_book_seo_filter_title', 99);
add_filter('rank_math/opengraph/twitter/title', 'bbb_book_seo_filter_title', 99);
add_filter('wpseo_title', 'bbb_book_seo_filter_title', 99);
add_filter('wpseo_opengraph_title', 'bbb_book_seo_filter_title', 99);
add_filter('wpseo_twitter_title', 'bbb_book_seo_filter_title', 99);

function bbb_book_seo_filter_description(string $description): string {
	$post_id = bbb_book_seo_current_id();

	return $post_id ? bbb_book_seo_description($post_id) : $description;
}
add_filter('rank_math/frontend/description', 'bbb_book_seo_filter_description', 99);
add_filter('rank_math/opengraph/facebook/description', 'bbb_book_seo_filter_description', 99);
add_filter('rank_math/opengraph/twitter/description', 'bbb_book_seo_filter_description', 99);
add_filter('wpseo_metadesc', 'bbb_book_seo_filter_description', 99);
add_filter('wpseo_opengraph_desc', 'bbb_book_seo_filter_description', 99);
add_filter('wpseo_twitter_description', 'bbb_book_seo_filter_description', 99);

function bbb_book_seo_filter_image(string $image): string {
	$post_id = bbb_book_seo_current_id();
	$cover   = $post_id ? bbb_book_seo_cover_url($post_id) : '';

	return '' !== $cover ? $cover : $image;
}
add_filter('rank_math/opengraph/facebook/image', 'bbb_book_seo_filter_image', 99);
add_filter('rank_math/opengraph/twitter/image', 'bbb_book_seo_filter_image', 99);
add_filter('wpseo_opengraph_image', 'bbb_book_seo_filter_image', 99);
add_filter('wpseo_twitter_image', 'bbb_book_seo_filter_image', 99);

function bbb_book_seo_add_rank_math_image($opengraph_image): void {
	$post_id = bbb_book_seo_current_id();
	$cover   = $post_id ? bbb_book_seo_cover_url($post_id) : '';

	if ('' !== $cover && is_object($opengraph_image) && method_exists($opengraph_image, 'add_image')) {
		$opengraph_image->add_image($cover);
	}
}
add_action('rank_math/opengraph/facebook/add_additional_images', 'bbb_book_seo_add_rank_math_image', 5);
add_action('rank_math/opengraph/twitter/add_additional_images', 'bbb_book_seo_add_rank_math_image', 5);
