<?php
/**
 * Article book-card shortcodes.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_article_field(string $key, int $post_id, $default = '') {
	if (function_exists('get_field')) {
		$value = get_field($key, $post_id);
		if (null !== $value && '' !== $value && false !== $value) {
			return $value;
		}
	}

	$value = function_exists('bbb_get_field') ? bbb_get_field($key, $post_id, null) : get_post_meta($post_id, $key, true);
	if (null !== $value && '' !== $value && false !== $value) {
		return $value;
	}

	if ('bbb_book' === get_post_type($post_id)) {
		$meta_map = array(
			'author'                  => '_bbb_author',
			'cover'                   => '_bbb_cover_url',
			'amazon_link'             => '_bbb_amazon_url',
			'bookshop_link'           => '_bbb_bookshop_url',
			'newsletter_url'          => '_bbb_newsletter_url',
			'spice_level'             => '_bbb_spice',
			'tension_score'           => '_bbb_tension',
			'emotional_damage_score'  => '_bbb_damage',
			'yearning_level'          => '_bbb_yearning',
			'boyfriend_type'          => '_bbb_boyfriend_type',
			'boyfriend_name'          => '_bbb_boyfriend_name',
			'reread_badge'            => '_bbb_reread',
			'darkness_level'          => '_bbb_darkness',
			'on_kindle_unlimited'     => '_bbb_ku',
			'read_as_standalone'      => '_bbb_standalone',
			'hide_from_library'       => '_bbb_hide_from_library',
			'mini_note'               => '_bbb_mini_note',
			'why_i_loved_it'          => '_bbb_why',
			'series_number'           => '_bbb_series_number',
			'series_handle'           => '_bbb_series_handle',
			'shelf'                   => '_bbb_shelf_name',
		);
		if (isset($meta_map[$key])) {
			$mapped = get_post_meta($post_id, $meta_map[$key], true);
			if ($mapped !== '' && $mapped !== null) {
				return $mapped;
			}
		}
	}

	if ('sss_series' === get_post_type($post_id)) {
		$meta_map = array(
			'author'             => '_bbb_series_author',
			'linked_blog_post'   => '_bbb_series_linked_blog_post_id',
			'linked_blog_handle' => '_bbb_series_linked_blog_post_handle',
			'linked_blog_title'  => '_bbb_series_linked_blog_post_title',
			'linked_blog_url'    => '_bbb_series_linked_blog_post_url',
		);
		if (isset($meta_map[$key])) {
			$mapped = get_post_meta($post_id, $meta_map[$key], true);
			if ($mapped !== '' && $mapped !== null) {
				return $mapped;
			}
		}
	}

	return $default;
}

function sss_article_bool($value): bool {
	if (function_exists('bbb_truthy')) {
		return bbb_truthy($value);
	}

	return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
}

function sss_article_post($value): ?WP_Post {
	if ($value instanceof WP_Post) {
		return $value;
	}
	if (is_array($value)) {
		if (isset($value['ID'])) {
			$post = get_post((int) $value['ID']);
			return $post instanceof WP_Post ? $post : null;
		}
		if (isset($value[0])) {
			return sss_article_post($value[0]);
		}
	}
	if (is_numeric($value)) {
		$post = get_post((int) $value);
		return $post instanceof WP_Post ? $post : null;
	}

	return null;
}

function sss_article_book_from_slug(string $slug): ?WP_Post {
	$slug = sanitize_title($slug);
	if (!$slug) {
		return null;
	}

	foreach (array('bbb_book', 'sss_book') as $post_type) {
		$post = get_page_by_path($slug, OBJECT, $post_type);
		if ($post instanceof WP_Post) {
			return $post;
		}
	}

	return null;
}

function sss_article_book_from_name(string $name): ?WP_Post {
	$name = trim(wp_strip_all_tags($name));
	if ('' === $name) {
		return null;
	}

	if (function_exists('bbb_books_like_find_book')) {
		$book = bbb_books_like_find_book($name);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	$slug_book = sss_article_book_from_slug($name);
	if ($slug_book instanceof WP_Post) {
		return $slug_book;
	}

	$normalized_name = sss_article_match_text($name);
	$compact_name    = preg_replace('/[^a-z0-9]+/', '', $normalized_name) ?: $normalized_name;
	foreach (sss_article_all_visible_books() as $book) {
		$title_match = sss_article_match_text(get_the_title($book));
		$slug_match  = sss_article_match_text((string) get_post_field('post_name', $book->ID));
		$handle_match = 'bbb_book' === get_post_type($book)
			? sss_article_match_text((string) get_post_meta($book->ID, '_bbb_handle', true))
			: '';
		$candidates = array_filter(array($title_match, $slug_match, $handle_match));

		foreach ($candidates as $candidate) {
			$compact_candidate = preg_replace('/[^a-z0-9]+/', '', $candidate) ?: $candidate;
			if ($candidate === $normalized_name || $compact_candidate === $compact_name) {
				return $book;
			}
		}

		$aliases = get_post_meta($book->ID, '_bbb_book_aliases', true);
		$aliases = is_array($aliases) ? $aliases : preg_split('/[\r\n,]+/', (string) $aliases);
		foreach ($aliases ?: array() as $alias) {
			$alias_match = sss_article_match_text((string) $alias);
			$compact_alias = preg_replace('/[^a-z0-9]+/', '', $alias_match) ?: $alias_match;
			if ('' !== $alias_match && ($alias_match === $normalized_name || $compact_alias === $compact_name)) {
				return $book;
			}
		}
	}

	foreach (sss_article_all_visible_books() as $book) {
		$title_match = sss_article_match_text(get_the_title($book));
		if ('' !== $normalized_name && str_contains(' ' . $title_match . ' ', ' ' . $normalized_name . ' ')) {
			return $book;
		}
	}

	return null;
}

function sss_article_posts($value): array {
	if (!is_array($value)) {
		$post = sss_article_post($value);
		if ($post) {
			return array($post);
		}

		if (is_string($value)) {
			$posts = array();
			foreach (preg_split('/[\s,]+/', $value) ?: array() as $slug) {
				$post = sss_article_book_from_slug($slug);
				if ($post instanceof WP_Post) {
					$posts[$post->ID] = $post;
				}
			}
			return array_values($posts);
		}

		return array();
	}

	$posts = array();
	foreach ($value as $item) {
		$post = sss_article_post($item);
		if (!$post && is_string($item)) {
			$post = sss_article_book_from_slug($item);
		}
		if ($post instanceof WP_Post) {
			$posts[$post->ID] = $post;
		}
	}

	return array_values($posts);
}

function sss_article_match_text(string $text): string {
	$text = html_entity_decode(wp_strip_all_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = strtolower($text);
	$text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;

	return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
}

function sss_article_books_mentioned_in_post(int $post_id): array {
	$title_text = ' ' . sss_article_match_text((string) get_the_title($post_id)) . ' ';
	$body_text = ' ' . sss_article_match_text((string) get_post_field('post_content', $post_id)) . ' ';
	$matches = array();

	foreach (sss_article_all_visible_books() as $book) {
		$needle = sss_article_match_text(get_the_title($book));
		if (strlen($needle) < 4) {
			continue;
		}

		$title_pos = strpos($title_text, ' ' . $needle . ' ');
		$body_pos = strpos($body_text, ' ' . $needle . ' ');
		if (false === $title_pos && false === $body_pos) {
			continue;
		}

		$matches[$book->ID] = array(
			'book' => $book,
			'pos'  => false !== $title_pos ? $title_pos : 100000 + (int) $body_pos,
		);
	}

	uasort(
		$matches,
		static fn(array $a, array $b): int => $a['pos'] <=> $b['pos']
	);

	return array_values(array_map(static fn(array $match): WP_Post => $match['book'], $matches));
}

function sss_article_cover_url(int $book_id): string {
	if (function_exists('bbb_get_book_cover_url')) {
		return bbb_get_book_cover_url($book_id);
	}

	$cover = sss_article_field('cover', $book_id, '');
	if (is_array($cover)) {
		if (!empty($cover['url'])) {
			return (string) $cover['url'];
		}
		if (!empty($cover['ID'])) {
			return (string) wp_get_attachment_image_url((int) $cover['ID'], 'large');
		}
	}
	if (is_numeric($cover)) {
		$url = wp_get_attachment_image_url((int) $cover, 'large');
		if ($url) {
			return (string) $url;
		}
	}
	if ($cover && !(function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url((string) $cover))) {
		return (string) $cover;
	}

	$thumbnail = (string) (get_the_post_thumbnail_url($book_id, 'large') ?: '');

	return function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($thumbnail) ? '' : $thumbnail;
}

function sss_article_tropes(int $book_id): array {
	$tropes = array();
	foreach (sss_article_posts(sss_article_field('tropes', $book_id, array())) as $trope) {
		$tropes[] = array(
			'id'    => $trope->ID,
			'name'  => get_the_title($trope),
			'slug'  => $trope->post_name,
			'emoji' => (string) sss_article_field('emoji', $trope->ID, ''),
			'bg'    => (string) sss_article_field('trope_bg', $trope->ID, '#f3bfd5'),
			'text'  => (string) sss_article_field('trope_text', $trope->ID, '#4b112d'),
		);
	}

	if (!$tropes) {
		$terms = get_the_terms($book_id, 'sss_trope');
		if ($terms && !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$colors = function_exists('sss_get_trope_colors') ? sss_get_trope_colors($term->slug) : array('#f3bfd5', '#4b112d');
				$tropes[] = array(
					'id'    => 0,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'emoji' => function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($term->term_id, 'emoji', true)) : (string) get_term_meta($term->term_id, 'emoji', true),
					'bg'    => $colors[0],
					'text'  => $colors[1],
				);
			}
		}
	}

	if (!$tropes) {
		$terms = get_the_terms($book_id, 'bbb_trope');
		if ($terms && !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$colors = function_exists('bbb_get_trope_colors') ? bbb_get_trope_colors($term->slug) : array('#f3bfd5', '#4b112d');
				$tropes[] = array(
					'id'    => 0,
					'name'  => $term->name,
					'slug'  => $term->slug,
					'emoji' => function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($term->term_id, 'trope_emoji', true)) : (string) get_term_meta($term->term_id, 'trope_emoji', true),
					'bg'    => $colors[0],
					'text'  => $colors[1],
				);
			}
		}
	}

	return $tropes;
}

function sss_article_shelf(int $book_id): array {
	$shelf_raw = sss_article_field('shelf', $book_id, null);
	if ($shelf_raw instanceof WP_Term) {
		return array('name' => $shelf_raw->name, 'slug' => $shelf_raw->slug);
	}
	if (is_array($shelf_raw) && isset($shelf_raw[0]) && $shelf_raw[0] instanceof WP_Term) {
		return array('name' => $shelf_raw[0]->name, 'slug' => $shelf_raw[0]->slug);
	}
	$shelf = sss_article_post($shelf_raw);
	if ($shelf) {
		return array('name' => get_the_title($shelf), 'slug' => $shelf->post_name);
	}

	$terms = get_the_terms($book_id, 'sss_shelf');
	if ($terms && !is_wp_error($terms)) {
		return array('name' => $terms[0]->name, 'slug' => $terms[0]->slug);
	}

	$terms = get_the_terms($book_id, 'bbb_shelf');
	if ($terms && !is_wp_error($terms)) {
		return array('name' => $terms[0]->name, 'slug' => $terms[0]->slug);
	}

	$raw = is_string($shelf_raw) ? $shelf_raw : (string) get_post_meta($book_id, '_bbb_shelf_name', true);
	return array('name' => $raw, 'slug' => sanitize_title($raw));
}

function sss_article_book_data(int $book_id): array {
	$series = sss_article_post(sss_article_field('series', $book_id, null));
	$tropes = sss_article_tropes($book_id);
	$series_handle = '';
	$series_name   = '';
	if ($series) {
		$series_handle = $series->post_name;
		$series_name   = get_the_title($series);
	} elseif ('bbb_book' === get_post_type($book_id)) {
		$series_handle = (string) get_post_meta($book_id, '_bbb_series_handle', true);
		if ($series_handle) {
			$series_term = get_term_by('slug', $series_handle, 'bbb_series');
			if ($series_term instanceof WP_Term) {
				$series_name = $series_term->name;
			}
		}
	}

	return array(
		'id'            => $book_id,
		'handle'        => get_post_field('post_name', $book_id),
		'url'           => get_permalink($book_id) ?: home_url('/books/' . get_post_field('post_name', $book_id) . '/'),
		'title'         => get_the_title($book_id),
		'author'        => (string) sss_article_field('author', $book_id, ''),
		'cover'         => sss_article_cover_url($book_id),
		'amazon'        => function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(sss_article_field('amazon_link', $book_id, '')) : (string) sss_article_field('amazon_link', $book_id, ''),
		'bookshop'      => function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(sss_article_field('bookshop_link', $book_id, '')) : (string) sss_article_field('bookshop_link', $book_id, ''),
		'newsletter'    => function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(sss_article_field('newsletter_url', $book_id, '')) : (string) sss_article_field('newsletter_url', $book_id, ''),
		'spice'         => (int) sss_article_field('spice_level', $book_id, 0),
		'darkness'      => (int) sss_article_field('darkness_level', $book_id, 0),
		'ku'            => sss_article_bool(sss_article_field('on_kindle_unlimited', $book_id, false)),
		'standalone'    => sss_article_bool(sss_article_field('read_as_standalone', $book_id, false)),
		'mini'          => (string) sss_article_field('mini_note', $book_id, ''),
		'why'           => (string) sss_article_field('why_i_loved_it', $book_id, ''),
		'tropes'        => $tropes,
		'shelf'         => sss_article_shelf($book_id),
		'series'        => $series,
		'series_handle' => $series_handle,
		'series_name'   => $series_name,
		'series_number' => (string) sss_article_field('series_number', $book_id, ''),
	);
}

function sss_article_data_attrs(array $book): string {
	$trope_names = wp_list_pluck($book['tropes'], 'name');
	$trope_display = array_map(
		static fn(array $trope): string => function_exists('bbb_trope_label') ? bbb_trope_label($trope['name'], $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $trope['name']),
		$book['tropes']
	);
	$attrs = array(
		'data-handle'         => $book['handle'],
		'data-url'            => $book['url'],
		'data-title'          => $book['title'],
		'data-author'         => $book['author'],
		'data-cover'          => $book['cover'],
		'data-amazon'         => $book['amazon'],
		'data-bookshop'       => $book['bookshop'],
		'data-newsletter'     => $book['newsletter'],
		'data-spice'          => (string) $book['spice'],
		'data-ku'             => $book['ku'] ? 'true' : 'false',
		'data-tropes'         => implode(', ', $trope_names),
		'data-tropes-display' => implode(', ', $trope_display),
		'data-mini'           => $book['mini'],
		'data-why'            => $book['why'],
		'data-series'         => $book['series_handle'],
		'data-series-name'    => $book['series_name'],
		'data-series-number'  => $book['series_number'],
	);

	$out = array();
	foreach ($attrs as $key => $value) {
		$out[] = $key . '="' . esc_attr((string) $value) . '"';
	}

	return implode(' ', $out);
}

function sss_render_article_book_card(int $book_id, bool $show_why = false): string {
	if ('bbb_book' === get_post_type($book_id) && function_exists('bbb_render_article_book_card')) {
		return bbb_render_article_book_card($book_id, $show_why);
	}

	$book = sss_article_book_data($book_id);
	ob_start();
	?>
<div class="article-book-card" data-book-preview <?php echo sss_article_data_attrs($book); ?>>

  <div class="article-book-card__header">
    <?php if (!empty($book['shelf']['name'])) : ?>
    <div class="article-book-card__genreRow">
      <span class="article-book-card__genreLine" aria-hidden="true"></span>
      <span class="article-book-card__genre"><?php echo esc_html($book['shelf']['name']); ?></span>
    </div>
    <?php endif; ?>

    <h3><?php echo esc_html($book['title']); ?></h3>

    <?php if ($book['author']) : ?>
    <div class="article-book-card__author"><?php echo esc_html($book['author']); ?></div>
    <?php endif; ?>

    <?php if ($book['series_name'] || $book['series_number']) : ?>
    <div class="article-book-card__series">
      <?php echo esc_html(($book['series_number'] ? '#' . $book['series_number'] . ' • ' : '') . ($book['series_name'] ? $book['series_name'] . ' series' : '')); ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="article-book-card__image">
    <button type="button" class="article-book-card__heart" data-blog-heart
      data-title="<?php echo esc_attr($book['title']); ?>" data-author="<?php echo esc_attr($book['author']); ?>" data-cover="<?php echo esc_attr($book['cover']); ?>"
      data-amazon="<?php echo esc_attr($book['amazon']); ?>" data-bookshop="<?php echo esc_attr($book['bookshop']); ?>"
      aria-label="save to your bookshelf">
      <span class="article-book-card__heartIcon" aria-hidden="true">♡</span>
      <span class="article-book-card__heartLabel">save</span>
    </button>

    <?php if ($book['spice'] > 0) : ?>
    <div class="article-book-card__spice"><?php echo esc_html(str_repeat('🌶', $book['spice'])); ?></div>
    <?php endif; ?>

    <?php if ($book['cover']) : ?>
    <img src="<?php echo esc_url($book['cover']); ?>" alt="<?php echo esc_attr($book['title']); ?>" loading="lazy">
    <?php endif; ?>
  </div>

  <div class="article-book-card__content">
    <?php if ($book['mini']) : ?>
    <p class="book-pitch"><?php echo esc_html($book['mini']); ?></p>
    <?php endif; ?>

    <?php if ($show_why && $book['why']) : ?>
    <p class="book-pitch book-pitch--why">
      <span class="book-pitch__label">why i loved it</span>
      <?php echo esc_html($book['why']); ?>
    </p>
    <?php endif; ?>

    <?php if ($book['tropes']) : ?>
	    <div class="article-book-card__tropes">
	      <?php foreach ($book['tropes'] as $trope) : ?>
	      <?php $trope_url = function_exists('bbb_trope_page_url') ? bbb_trope_page_url((string) $trope['name'], (string) ($trope['slug'] ?? $trope['handle'] ?? '')) : home_url('/' . sanitize_title((string) ($trope['slug'] ?? $trope['handle'] ?? $trope['name'])) . '-books/'); ?>
	      <a class="article-book-card__trope" href="<?php echo esc_url($trope_url); ?>">
	        <?php echo function_exists('bbb_trope_label_html') ? bbb_trope_label_html((string) $trope['name'], $trope['emoji'] ?? '', (string) ($trope['slug'] ?? $trope['handle'] ?? '')) : esc_html(trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . (string) $trope['name'])); ?>
	      </a>
	      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="article-book-card__ratings">
      <?php if ($book['ku']) : ?>
      <span class="article-book-card__ku article-book-card__ku--yes">✓ on kindle unlimited</span>
      <?php else : ?>
      <span class="article-book-card__ku article-book-card__ku--no">✕ not on kindle unlimited</span>
      <?php endif; ?>
    </div>

	    <div class="article-book-card__buttons">
	      <?php if ($book['amazon'] && $book['ku']) : ?>
	      <a class="article-book-card__button article-book-card__button--ku" href="<?php echo esc_url($book['amazon']); ?>" target="_blank" rel="noopener">read free on kindle unlimited</a>
	      <?php endif; ?>
	      <?php if ($book['amazon']) : ?>
	      <a class="article-book-card__button article-book-card__button--amazon" href="<?php echo esc_url($book['amazon']); ?>" target="_blank" rel="noopener">buy on amazon <span>· own it forever</span></a>
	      <?php endif; ?>
	      <?php if ($book['bookshop']) : ?>
	      <a class="article-book-card__button article-book-card__button--bookshop" href="<?php echo esc_url($book['bookshop']); ?>" target="_blank" rel="noopener">prefer indie? bookshop.org →</a>
	      <?php endif; ?>
	    </div>
  </div>

</div>
	<?php
	return ob_get_clean();
}

function sss_article_books_for_source(string $source, string $value, int $limit = 24): array {
	$source = sanitize_key($source);
	$value  = sanitize_title($value);
	$limit  = max(1, min(48, $limit));

	if (!in_array($source, array('trope', 'shelf', 'series'), true) || '' === $value) {
		return array();
	}

	$books = array_values(
		array_filter(
			sss_article_all_visible_books(),
			static function (WP_Post $book) use ($source, $value): bool {
				$data = sss_article_book_data($book->ID);

				if ('trope' === $source) {
					foreach ($data['tropes'] as $trope) {
						if ($value === ($trope['slug'] ?? '') || $value === sanitize_title((string) ($trope['name'] ?? ''))) {
							return true;
						}
					}

					return false;
				}

				if ('shelf' === $source) {
					return $value === ($data['shelf']['slug'] ?? '') || $value === sanitize_title((string) ($data['shelf']['name'] ?? ''));
				}

				return $value === ($data['series_handle'] ?? '') || $value === sanitize_title((string) ($data['series_name'] ?? ''));
			}
		)
	);

	usort(
		$books,
		static function (WP_Post $a, WP_Post $b) use ($source): int {
			if ('series' === $source) {
				$a_number = (int) sss_article_field('series_number', $a->ID, 999);
				$b_number = (int) sss_article_field('series_number', $b->ID, 999);
				if ($a_number !== $b_number) {
					return $a_number <=> $b_number;
				}
			}

			return strcasecmp(get_the_title($a), get_the_title($b));
		}
	);

	return array_slice($books, 0, $limit);
}

function sss_article_books_for_selected_source(int $post_id): array {
	$source = (string) get_post_meta($post_id, '_bbb_article_book_source', true);
	$value  = (string) get_post_meta($post_id, '_bbb_article_book_source_value', true);
	$limit  = (int) get_post_meta($post_id, '_bbb_article_book_source_limit', true);

	return sss_article_books_for_source($source, $value, $limit ?: 24);
}

function sss_article_post_books(int $post_id, bool $include_mentions = true): array {
	foreach (array('book', 'books', 'library_book', 'library_books', 'featured_books', 'article_books') as $field) {
		$books = sss_article_posts(sss_article_field($field, $post_id, array()));
		if ($books) {
			return $books;
		}
	}

	$book_ids = get_post_meta($post_id, '_bbb_article_books', true);
	if (is_array($book_ids)) {
		$books = sss_article_posts($book_ids);
		if ($books) {
			return $books;
		}
	}

	$books = array();
	for ($index = 1; $index <= 24; $index++) {
		$value = get_post_meta($post_id, '_bbb_article_book_' . $index, true);
		if (!$value) {
			$value = get_post_meta($post_id, 'book_' . $index, true);
		}
		$book = sss_article_post($value);
		if (!$book && is_string($value)) {
			$book = sss_article_book_from_slug($value);
		}
		if ($book instanceof WP_Post) {
			$books[] = $book;
		}
	}

	return $books ?: ($include_mentions ? sss_article_books_mentioned_in_post($post_id) : array());
}

function sss_article_book_visible(WP_Post $book): bool {
	if ('bbb_book' === $book->post_type && function_exists('bbb_is_book_visible')) {
		return bbb_is_book_visible($book->ID);
	}

	$is_visible = function_exists('get_field') ? get_field('is_visible', $book->ID) : null;
	if (null === $is_visible || '' === $is_visible) {
		$is_visible = get_post_meta($book->ID, 'is_visible', true);
	}
	if ('' !== $is_visible && null !== $is_visible && !sss_article_bool($is_visible)) {
		return false;
	}
	if (function_exists('bbb_book_is_publicly_visible')) {
		return bbb_book_is_publicly_visible($book->ID);
	}
	if ('' === $is_visible || null === $is_visible) {
		$is_visible = true;
	}
	return sss_article_bool($is_visible) && !sss_article_bool(sss_article_field('hide_from_library', $book->ID, false));
}

function sss_article_all_visible_books(): array {
	$books = get_posts(array('post_type' => array('sss_book', 'bbb_book'), 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
	return array_values(array_filter($books, 'sss_article_book_visible'));
}

function sss_article_books_for_trope(WP_Post $trope): array {
	return array_values(
		array_filter(
			sss_article_all_visible_books(),
			static function (WP_Post $book) use ($trope): bool {
				foreach (sss_article_tropes($book->ID) as $book_trope) {
					if ((int) $book_trope['id'] === (int) $trope->ID || $book_trope['slug'] === $trope->post_name || strtolower($book_trope['name']) === strtolower(get_the_title($trope))) {
						return true;
					}
				}
				return false;
			}
		)
	);
}

function sss_article_books_for_guide_category(WP_Post $category): array {
	$slug = $category->post_name;
	$name = strtolower(get_the_title($category));

	return array_values(
		array_filter(
			sss_article_all_visible_books(),
			static function (WP_Post $book) use ($slug, $name): bool {
				$data = sss_article_book_data($book->ID);
				$series_number = (int) $data['series_number'];
				if ($series_number !== 0 && $series_number !== 1 && !$data['standalone']) {
					return false;
				}
				if (($data['shelf']['slug'] ?? '') === $slug || strtolower((string) ($data['shelf']['name'] ?? '')) === $name) {
					return true;
				}
				foreach ($data['tropes'] as $trope) {
					if ($trope['slug'] === $slug || strtolower($trope['name']) === $name) {
						return true;
					}
				}
				return false;
			}
		)
	);
}

function sss_article_books_for_inferred_context(int $post_id): array {
	$taxonomies = array_values(array_filter(array('category', 'post_tag', 'bbb_trope', 'bbb_shelf'), 'taxonomy_exists'));
	$term_names = $taxonomies ? wp_get_post_terms($post_id, $taxonomies, array('fields' => 'names')) : array();
	$term_names = (!is_wp_error($term_names) && is_array($term_names)) ? $term_names : array();
	$primary_haystack = sss_article_match_text(
		implode(' ', array_filter(array((string) get_the_title($post_id), (string) get_post_field('post_name', $post_id), implode(' ', $term_names))))
	);
	$fallback_haystack = sss_article_match_text((string) get_post_field('post_content', $post_id));
	if ('' === $primary_haystack && '' === $fallback_haystack) {
		return array();
	}

	$generic_terms = array('book', 'books', 'romance', 'romances', 'read', 'reads', 'guide', 'guides', 'best', 'ultimate', 'list');
	$matches = array();

	foreach (sss_article_all_visible_books() as $book) {
		$data = sss_article_book_data($book->ID);
		$series_number = (int) $data['series_number'];
		if ($series_number !== 0 && $series_number !== 1 && !$data['standalone']) {
			continue;
		}

		$score = 0;
		$matched_primary = false;
		$terms = array();
		if (!empty($data['shelf']['name'])) {
			$terms[] = (string) $data['shelf']['name'];
		}
		if (!empty($data['shelf']['slug'])) {
			$terms[] = (string) $data['shelf']['slug'];
		}
		foreach ($data['tropes'] as $trope) {
			$terms[] = (string) ($trope['name'] ?? '');
			$terms[] = (string) ($trope['slug'] ?? '');
		}

		foreach (array_unique(array_filter($terms)) as $term) {
			$needle = sss_article_match_text($term);
			if ('' === $needle || in_array($needle, $generic_terms, true)) {
				continue;
			}
			if ('' !== $primary_haystack && str_contains(' ' . $primary_haystack . ' ', ' ' . $needle . ' ')) {
				$score += str_contains($needle, ' ') ? 6 : 2;
				$matched_primary = true;
			} elseif ('' !== $fallback_haystack && str_contains(' ' . $fallback_haystack . ' ', ' ' . $needle . ' ')) {
				$score += str_contains($needle, ' ') ? 2 : 1;
			}
		}

		if ($score > 0) {
			$matches[$book->ID] = array('book' => $book, 'score' => $score, 'primary' => $matched_primary);
		}
	}

	$has_primary_matches = false;
	foreach ($matches as $match) {
		if (!empty($match['primary'])) {
			$has_primary_matches = true;
			break;
		}
	}
	if ($has_primary_matches) {
		$matches = array_filter($matches, static fn(array $match): bool => !empty($match['primary']));
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

	return array_values(array_map(static fn(array $match): WP_Post => $match['book'], $matches));
}

function sss_article_books_for_post(int $post_id): array {
	$books = sss_article_books_for_selected_source($post_id);
	if ($books) {
		return $books;
	}

	$include_mentions = function_exists('sss_content_has_pillar') ? !sss_content_has_pillar((string) get_post_field('post_content', $post_id)) : true;
	$books = sss_article_post_books($post_id, $include_mentions);
	if ($books) {
		return $books;
	}

	$trope = sss_article_post(sss_article_field('trope', $post_id, null));
	if ($trope) {
		return sss_article_books_for_trope($trope);
	}

	$trope_terms = get_the_terms($post_id, 'bbb_trope');
	if ($trope_terms && !is_wp_error($trope_terms)) {
		$book_ids = get_posts(
			array(
				'post_type'      => array('bbb_book', 'sss_book'),
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'bbb_trope',
						'field'    => 'term_id',
						'terms'    => wp_list_pluck($trope_terms, 'term_id'),
					),
				),
			)
		);
		$books = sss_article_posts($book_ids);
		if ($books) {
			return $books;
		}
	}

	$category = sss_article_post(sss_article_field('guide_category', $post_id, null));
	if (!$category) {
		$category_slug = sanitize_title((string) get_post_meta($post_id, '_bbb_guide_category', true));
		$category = $category_slug ? get_page_by_path($category_slug, OBJECT, 'page') : null;
	}

	if ($category) {
		$books = sss_article_books_for_guide_category($category);
		if ($books) {
			return $books;
		}
	}

	return sss_article_books_for_inferred_context($post_id);
}

function sss_render_weekly_obsession_banner(): string {
	if (!function_exists('sss_get_current_newsletter_issue') || !function_exists('sss_get_obsession_book')) {
		return '';
	}
	$issue = sss_get_current_newsletter_issue();
	$book = null;
	if ($issue) {
		$book = sss_article_post(sss_article_field('book', $issue->ID, null));
		if (!$book) {
			$book = sss_article_post(sss_article_field('library_book', $issue->ID, null));
		}
		if (!$book) {
			$book = sss_get_obsession_book($issue);
		}
	}
	if (!$book) {
		return '';
	}
	$tropes = array_slice(sss_article_tropes($book->ID), 0, 2);
	if (!$tropes) {
		return '';
	}
	$url = function_exists('bbb_resolve_page_url') ? bbb_resolve_page_url('weekly-obsession') : home_url('/pages/weekly-obsession/');

	ob_start();
	?>
<a class="blog-obsession-banner blog-obsession-banner--article" href="<?php echo esc_url($url); ?>" aria-label="see this week's weekly obsession">
  <p class="blog-obsession-banner__text">
    see the book everyone is talking about...
    think
    <?php foreach ($tropes as $i => $trope) : ?>
	    <?php echo 1 === $i ? 'and' : ''; ?>
	    <?php $trope_url = function_exists('bbb_trope_page_url') ? bbb_trope_page_url((string) ($trope['name'] ?? ''), (string) ($trope['slug'] ?? $trope['handle'] ?? '')) : home_url('/' . sanitize_title((string) ($trope['slug'] ?? $trope['handle'] ?? $trope['name'] ?? '')) . '-books/'); ?>
	    <span class="blog-obsession-banner__trope" data-trope-url="<?php echo esc_url($trope_url); ?>" role="link" tabindex="0">
	      <span class="blog-obsession-banner__tropeEmoji"><?php echo function_exists('bbb_trope_emoji_html') ? bbb_trope_emoji_html((string) ($trope['name'] ?? ''), $trope['emoji'] ?? '', (string) ($trope['slug'] ?? $trope['handle'] ?? '')) : esc_html(((string) ($trope['emoji'] ?? '') ?: '🖤')); ?></span>
	      <?php echo esc_html($trope['name']); ?>
	    </span>
    <?php endforeach; ?>
    <span class="blog-obsession-banner__cta">see the weekly obsession</span>
  </p>
</a>
	<?php
	return ob_get_clean();
}

function sss_weekly_obsession_shortcode(): string {
	return sss_render_weekly_obsession_banner();
}
add_shortcode('sss_weekly_obsession', 'sss_weekly_obsession_shortcode');

function sss_book_shortcode($atts): string {
	$atts = shortcode_atts(array('index' => 1, 'name' => '', 'post_id' => get_the_ID()), $atts, 'sss_book');
	if ('' !== trim((string) $atts['name'])) {
		$book = sss_article_book_from_name((string) $atts['name']);

		return $book instanceof WP_Post ? sss_render_article_book_card($book->ID) : '';
	}

	$books = sss_article_post_books((int) $atts['post_id']);
	if (!$books) {
		$books = sss_article_books_for_post((int) $atts['post_id']);
	}

	$book = $books[max(0, (int) $atts['index'] - 1)] ?? null;

	return $book instanceof WP_Post ? sss_render_article_book_card($book->ID) : '';
}
add_shortcode('sss_book', 'sss_book_shortcode');

function sss_bookpage_suggestions_source(int $post_id, string $source = ''): ?WP_Post {
	if ('' !== trim($source)) {
		$book = sss_article_book_from_name($source);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	$books = sss_article_post_books($post_id);
	if (!$books) {
		$books = sss_article_books_for_post($post_id);
	}
	if (!$books) {
		$books = sss_article_books_mentioned_in_post($post_id);
	}

	$book = $books[0] ?? null;

	return $book instanceof WP_Post ? $book : null;
}

function sss_bookpage_suggestions_shortcode($atts): string {
	$atts = shortcode_atts(
		array(
			'post_id' => get_the_ID(),
			'source'  => '',
			'count'   => 3,
		),
		$atts,
		'sss_bookpage_suggestions'
	);

	$source = sss_bookpage_suggestions_source((int) $atts['post_id'], (string) $atts['source']);
	if (!$source instanceof WP_Post || !function_exists('bbb_books_like_recommendations')) {
		return '';
	}

	$cards = '';
	$count = 0;
	foreach (bbb_books_like_recommendations((int) $source->ID) as $suggestion) {
		if (empty($suggestion['id'])) {
			continue;
		}

		$cards .= sss_render_article_book_card((int) $suggestion['id']);
		$count++;
		if ($count >= max(1, (int) $atts['count'])) {
			break;
		}
	}

	return $cards;
}
add_shortcode('sss_bookpage_suggestions', 'sss_bookpage_suggestions_shortcode');

function sss_book_trope_shortcode($atts): string {
	$atts = shortcode_atts(array('index' => 1, 'post_id' => get_the_ID()), $atts, 'sss_book_trope');
	$trope = sss_article_post(sss_article_field('trope', (int) $atts['post_id'], null));
	if (!$trope) {
		$terms = get_the_terms((int) $atts['post_id'], 'bbb_trope');
		if (!$terms || is_wp_error($terms)) {
			return '';
		}
		$book_ids = get_posts(
			array(
				'post_type'      => array('bbb_book', 'sss_book'),
				'post_status'    => 'publish',
				'posts_per_page' => max(1, (int) $atts['index']),
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'bbb_trope',
						'field'    => 'term_id',
						'terms'    => wp_list_pluck($terms, 'term_id'),
					),
				),
			)
		);
		$book = sss_article_posts($book_ids)[max(0, (int) $atts['index'] - 1)] ?? null;

		return $book instanceof WP_Post ? sss_render_article_book_card($book->ID) : '';
	}
	$books = sss_article_books_for_trope($trope);
	$book = $books[max(0, (int) $atts['index'] - 1)] ?? null;

	return $book instanceof WP_Post ? sss_render_article_book_card($book->ID) : '';
}
add_shortcode('sss_book_trope', 'sss_book_trope_shortcode');

function sss_bookcard_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_bookcard');
	$post_id = (int) $atts['post_id'];
	if (sss_content_has_pillar((string) get_post_field('post_content', $post_id))) {
		return sss_pillar_bookcard_shortcode(array('post_id' => $post_id));
	}

	$books = sss_article_books_for_post($post_id);
	if (!$books) {
		return '';
	}

	$midpoint = (int) floor(count($books) / 2);
	$obsession = sss_render_weekly_obsession_banner();
	ob_start();
	?>
<div class="guide-bookcard" data-guide-bookcard data-guide-title="<?php echo esc_attr(get_the_title($post_id)); ?>">
  <div class="guide-bookcard__head">
    <div class="guide-bookcard__header">books mentioned in this guide</div>
    <button type="button" class="sss-lib__exportBtn guide-bookcard__export" data-guide-export>save this list</button>
  </div>
  <div class="guide-bookcard__affiliate-note">some links may be affiliate links, so thank you for supporting the recs. &lt;3</div>
  <div class="guide-bookcard__list">
    <?php foreach ($books as $i => $book) : ?>
      <?php if ($obsession && $i === $midpoint) : ?>
      <div class="guide-bookcard__obsession"><?php echo $obsession; ?></div>
      <?php endif; ?>
      <div class="guide-bookcard__item">
        <?php echo sss_render_article_book_card($book->ID); ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_bookcard', 'sss_bookcard_shortcode');
add_shortcode('bookcard', 'sss_bookcard_shortcode');

function sss_pillar_bookcard_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_pillar_bookcard');
	$post_id = (int) $atts['post_id'];
	$books = sss_article_books_for_post($post_id);
	if (!$books) {
		return '';
	}

	$groups = array(1 => array(), 2 => array(), 3 => array(), 4 => array(), 5 => array());
	foreach ($books as $book) {
		$level = max(1, min(5, (int) sss_article_field('spice_level', $book->ID, 0)));
		$groups[$level][] = $book;
	}
	$obsession = sss_render_weekly_obsession_banner();

	ob_start();
	?>
<div class="guide-bookcard guide-bookcard--pillar" data-guide-bookcard data-guide-title="<?php echo esc_attr(get_the_title($post_id)); ?>">
  <div class="guide-bookcard__head">
    <div class="guide-bookcard__header">books by spice level</div>
    <button type="button" class="sss-lib__exportBtn guide-bookcard__export" data-guide-export>save this list</button>
  </div>
  <div class="guide-bookcard__affiliate-note">some links may be affiliate links, so thank you for supporting the recs. &lt;3</div>
  <div class="pillar-bookcard">
    <?php foreach ($groups as $level => $level_books) : ?>
      <?php if (!$level_books) { continue; } ?>
      <div class="pillar-bookcard__section">
        <?php echo do_shortcode('[sss_spice level="' . (int) $level . '"]'); ?>
        <div class="guide-bookcard__list pillar-bookcard__list">
          <?php foreach ($level_books as $book) : ?>
          <div class="guide-bookcard__item guide-bookcard__item--pillar">
            <?php echo sss_render_article_book_card($book->ID, true); ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if (3 === (int) $level && $obsession) : ?>
      <div class="guide-bookcard__obsession"><?php echo $obsession; ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_pillar_bookcard', 'sss_pillar_bookcard_shortcode');
add_shortcode('pillarbookcard', 'sss_pillar_bookcard_shortcode');

function sss_ku_shortcode($atts): string {
	return '<div class="blog-ku-cta">
		<p class="blog-ku-cta__label">✓ on kindle unlimited</p>
		<p class="blog-ku-cta__intro">most recs in the library are on kindle unlimited.</p>
		<a class="blog-ku-cta__accent" href="https://amzn.to/4uZ8Y3a" target="_blank" rel="noopener sponsored">try kindle unlimited now &rarr;</a>
	</div>';
}
add_shortcode('sss_ku', 'sss_ku_shortcode');
add_shortcode('ku', 'sss_ku_shortcode');
