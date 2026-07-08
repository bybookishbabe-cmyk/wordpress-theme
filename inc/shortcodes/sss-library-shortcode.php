<?php
/**
 * Article library strip shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_library_shortcode_normalize_key(string $value): string {
	return sanitize_title($value);
}

function sss_library_shortcode_is_review_post(int $post_id): bool {
	$haystack = sss_library_shortcode_normalize_key(
		implode(
			' ',
			array_filter(
				array(
					(string) get_the_title($post_id),
					(string) get_post_field('post_name', $post_id),
					(string) get_post_field('post_excerpt', $post_id),
				)
			)
		)
	);

	if (str_contains($haystack, 'review')) {
		return true;
	}

	$terms = wp_get_post_terms($post_id, array_values(array_filter(array('category', 'post_tag', 'book_review_category'), 'taxonomy_exists')), array('fields' => 'slugs'));
	if (is_wp_error($terms) || !is_array($terms)) {
		return false;
	}

	foreach ($terms as $term) {
		if (str_contains((string) $term, 'review')) {
			return true;
		}
	}

	return false;
}

function sss_library_shortcode_is_classic(WP_Post $book): bool {
	if (function_exists('sss_book_is_top_shelf') && sss_book_is_top_shelf($book->ID)) {
		return true;
	}

	$data  = function_exists('sss_article_book_data') ? sss_article_book_data($book->ID) : array();
	$shelf = $data['shelf'] ?? array();
	$name  = is_array($shelf) ? (string) ($shelf['name'] ?? '') : (string) $shelf;
	$slug  = is_array($shelf) ? (string) ($shelf['slug'] ?? '') : sanitize_title($name);

	return 'society-classics' === sanitize_title($slug ?: $name);
}

function sss_library_shortcode_first_in_series_or_standalone(WP_Post $book): bool {
	$data          = function_exists('sss_article_book_data') ? sss_article_book_data($book->ID) : array();
	$series_handle = trim((string) ($data['series_handle'] ?? ''));
	$series_number = (int) ($data['series_number'] ?? 0);

	return '' === $series_handle || $series_number <= 1;
}

function sss_library_shortcode_unique_books(array $books): array {
	$selected = array();
	$seen     = array();

	foreach ($books as $book) {
		if (!$book instanceof WP_Post || isset($seen[(int) $book->ID])) {
			continue;
		}

		$seen[(int) $book->ID] = true;
		$selected[]           = $book;
	}

	return $selected;
}

function sss_library_shortcode_rotate_books(array $books, int $post_id, int $count): array {
	$books = array_values($books);
	$total = count($books);
	if (0 === $total) {
		return array();
	}

	$offset   = $post_id % $total;
	$selected = array();
	for ($i = 0; $i < min($count, $total); $i++) {
		$selected[] = $books[($offset + $i) % $total];
	}

	return $selected;
}

function sss_library_shortcode_book_profile(WP_Post $book): array {
	$data   = function_exists('sss_article_book_data') ? sss_article_book_data($book->ID) : array();
	$shelf  = is_array($data['shelf'] ?? null) ? $data['shelf'] : array();
	$tropes = array();

	foreach (($data['tropes'] ?? array()) as $trope) {
		if (!is_array($trope)) {
			continue;
		}

		$slug = sss_library_shortcode_normalize_key((string) ($trope['slug'] ?? $trope['handle'] ?? $trope['name'] ?? ''));
		if ('' !== $slug) {
			$tropes[] = $slug;
		}
	}

	return array(
		'shelf'  => sss_library_shortcode_normalize_key((string) ($shelf['slug'] ?? $shelf['name'] ?? '')),
		'tropes' => array_values(array_unique($tropes)),
	);
}

function sss_library_shortcode_similar_books(array $source_books, array $all_books, int $count): array {
	$shelves = array();
	$tropes  = array();
	$exclude = array();

	foreach ($source_books as $source_book) {
		if (!$source_book instanceof WP_Post) {
			continue;
		}

		$exclude[(int) $source_book->ID] = true;
		$profile = sss_library_shortcode_book_profile($source_book);
		if ('' !== $profile['shelf']) {
			$shelves[$profile['shelf']] = true;
		}
		foreach ($profile['tropes'] as $trope) {
			$tropes[$trope] = true;
		}
	}

	$matches = array();
	foreach ($all_books as $book) {
		if (!$book instanceof WP_Post || isset($exclude[(int) $book->ID])) {
			continue;
		}

		$representative = function_exists('sss_article_required_series_representative') ? sss_article_required_series_representative($book) : $book;
		if (!$representative instanceof WP_Post || isset($exclude[(int) $representative->ID])) {
			continue;
		}

		$profile = sss_library_shortcode_book_profile($representative);
		$score   = 0;
		if ('' !== $profile['shelf'] && isset($shelves[$profile['shelf']])) {
			$score += 12;
		}
		foreach ($profile['tropes'] as $trope) {
			if (isset($tropes[$trope])) {
				$score += 4;
			}
		}
		if (function_exists('sss_book_is_top_shelf') && sss_book_is_top_shelf($representative->ID)) {
			$score += 2;
		}
		if (function_exists('sss_book_is_starter_pack') && sss_book_is_starter_pack($representative->ID)) {
			$score += 1;
		}

		if ($score <= 0 || !sss_library_shortcode_first_in_series_or_standalone($representative)) {
			continue;
		}

		$matches[(int) $representative->ID] = array(
			'book'  => $representative,
			'score' => $score,
		);
	}

	uasort(
		$matches,
		static function (array $a, array $b): int {
			if ($a['score'] !== $b['score']) {
				return $b['score'] <=> $a['score'];
			}

			return strcasecmp(get_the_title($a['book']), get_the_title($b['book']));
		}
	);

	return array_slice(array_values(array_map(static fn(array $match): WP_Post => $match['book'], $matches)), 0, $count);
}

function sss_library_shortcode_selected_books(int $post_id, int $count): array {
	$all_books = sss_article_all_visible_books();
	if (!$all_books) {
		return array();
	}

	if (function_exists('bbb_article_auto_link_setting') && $post_id && !bbb_article_auto_link_setting($post_id, 'library_context')) {
		$selected = array_values(array_filter($all_books, 'sss_library_shortcode_is_classic'));
		if (count($selected) < $count) {
			$selected = array_merge(
				$selected,
				sss_library_shortcode_rotate_books(
					array_values(
						array_filter(
							$all_books,
							static fn(WP_Post $book): bool => sss_library_shortcode_first_in_series_or_standalone($book)
						)
					),
					$post_id,
					$count
				)
			);
		}

		return array_slice(sss_library_shortcode_unique_books($selected), 0, $count);
	}

	$explicit_books = function_exists('sss_article_post_books') ? sss_article_post_books($post_id, false) : array();
	$context_books  = $explicit_books;
	if (!$context_books && function_exists('sss_article_books_mentioned_in_post')) {
		$context_books = sss_article_books_mentioned_in_post($post_id);
	}

	$book_heavy_threshold = (int) apply_filters('bbb_library_shortcode_book_heavy_threshold', 8, $post_id);
	$is_book_heavy       = count($context_books) >= $book_heavy_threshold;
	$is_single_or_review = count($context_books) <= 1 || sss_library_shortcode_is_review_post($post_id);

	if ($is_book_heavy) {
		$selected = array_values(array_filter($all_books, 'sss_library_shortcode_is_classic'));
	} elseif ($context_books && $is_single_or_review) {
		$selected = sss_library_shortcode_similar_books(array_slice($context_books, 0, 3), $all_books, $count);
	} elseif ($context_books) {
		$selected = function_exists('sss_article_collapse_required_series_books') ? sss_article_collapse_required_series_books($context_books) : $context_books;
		$selected = array_values(array_filter($selected, 'sss_library_shortcode_first_in_series_or_standalone'));
	} else {
		$selected = function_exists('sss_article_books_for_inferred_context') ? sss_article_books_for_inferred_context($post_id) : array();
		$selected = function_exists('sss_article_collapse_required_series_books') ? sss_article_collapse_required_series_books($selected) : $selected;
	}

	$selected = function_exists('sss_article_collapse_required_series_books') ? sss_article_collapse_required_series_books($selected) : $selected;
	$selected = array_values(array_filter($selected, 'sss_library_shortcode_first_in_series_or_standalone'));
	$selected = sss_library_shortcode_unique_books($selected);
	if (count($selected) < $count && $context_books) {
		$selected = array_merge($selected, sss_library_shortcode_similar_books($context_books, $all_books, $count));
	}
	if (count($selected) < $count) {
		$selected = array_merge($selected, array_values(array_filter($all_books, 'sss_library_shortcode_is_classic')));
	}
	if (count($selected) < $count) {
		$starter_or_firsts = array_values(
			array_filter(
				$all_books,
				static fn(WP_Post $book): bool => sss_library_shortcode_first_in_series_or_standalone($book)
			)
		);
		$selected = array_merge($selected, sss_library_shortcode_rotate_books($starter_or_firsts, $post_id, $count));
	}

	$selected = function_exists('sss_article_collapse_required_series_books') ? sss_article_collapse_required_series_books($selected) : $selected;
	$selected = array_values(array_filter($selected, 'sss_library_shortcode_first_in_series_or_standalone'));

	return array_slice(sss_library_shortcode_unique_books($selected), 0, $count);
}

function sss_library_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID(), 'count' => 5), $atts, 'sss_library');
	$post_id = (int) $atts['post_id'];
	$count   = max(1, min(8, (int) $atts['count']));
	$selected = sss_library_shortcode_selected_books($post_id, $count);
	if (!$selected) {
		return '';
	}
	$library_url = function_exists('bbb_resolve_page_url') ? bbb_resolve_page_url('library') : home_url('/library/');

	ob_start();
	?>
<div class="sss-blog-library">
  <div class="sss-blog-library__header">
    <h3>peek inside the society library</h3>
    <a href="<?php echo esc_url($library_url); ?>" class="sss-blog-library__cta">take me to the library →</a>
  </div>
  <div class="sss-blog-library__row">
    <?php foreach ($selected as $book_post) : ?>
      <?php $book = sss_article_book_data($book_post->ID); ?>
      <button class="sss-blog-library__card" type="button" data-book-preview <?php echo sss_article_data_attrs($book); ?>>
        <?php if ($book['cover']) : ?>
        <img src="<?php echo esc_url($book['cover']); ?>" alt="<?php echo esc_attr(function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt((string) $book['title'], (string) $book['author'], (string) ($book['shelf']['name'] ?? '')) : (string) $book['title'] . ' book cover'); ?>" loading="lazy">
        <?php endif; ?>
        <div class="sss-blog-library__title"><?php echo esc_html($book['title']); ?></div>
        <div class="sss-blog-library__author"><?php echo esc_html($book['author']); ?></div>
      </button>
    <?php endforeach; ?>
  </div>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_library', 'sss_library_shortcode');
add_shortcode('library', 'sss_library_shortcode');
