<?php
/**
 * Template Name: Book Series
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

require_once get_theme_file_path('template-parts/sss-book-card.php');

if (!function_exists('bbb_series_visible_books')) {
	function bbb_series_visible_books(): array {
		$post_types = array_values(
			array_filter(
				array('sss_book', 'bbb_book'),
				static fn(string $post_type): bool => post_type_exists($post_type)
			)
		);

		$books = get_posts(
			array(
				'post_type'      => $post_types ?: 'sss_book',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		return array_values(
			array_filter(
				$books,
				static function (WP_Post $book): bool {
					if ('bbb_book' === $book->post_type && function_exists('bbb_book_is_publicly_visible')) {
						return bbb_book_is_publicly_visible($book->ID);
					}

					return function_exists('sss_book_is_visible') ? sss_book_is_visible($book->ID) : true;
				}
			)
		);
	}
}

if (!function_exists('bbb_series_terms')) {
	function bbb_series_terms(): array {
		if (taxonomy_exists('sss_series')) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'sss_series',
					'hide_empty' => false,
				)
			);

			return ($terms && !is_wp_error($terms)) ? array_values($terms) : array();
		}

		if (!post_type_exists('sss_series')) {
			return array();
		}

		return get_posts(
			array(
				'post_type'      => 'sss_series',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}
}

if (!function_exists('bbb_series_entity_slug')) {
	function bbb_series_entity_slug($series): string {
		if ($series instanceof WP_Term) {
			return $series->slug;
		}

		return $series instanceof WP_Post ? $series->post_name : '';
	}
}

if (!function_exists('bbb_series_entity_title')) {
	function bbb_series_entity_title($series): string {
		if ($series instanceof WP_Term) {
			return $series->name;
		}

		return $series instanceof WP_Post ? get_the_title($series) : '';
	}
}

if (!function_exists('bbb_series_entity_meta')) {
	function bbb_series_entity_meta($series, string $key, $default = '') {
		if ($series instanceof WP_Term) {
			$value = get_term_meta($series->term_id, $key, true);
			if ('' !== $value && null !== $value) {
				return $value;
			}

			if (function_exists('get_field')) {
				$field = get_field($key, 'term_' . $series->term_id);
				if ('' !== $field && null !== $field && false !== $field) {
					return $field;
				}
			}

			return $default;
		}

		return $series instanceof WP_Post ? bbb_series_field($series->ID, $key, $default) : $default;
	}
}

if (!function_exists('bbb_series_term_tropes')) {
	function bbb_series_term_tropes($series): array {
		$raw    = bbb_series_entity_meta($series, 'series_tropes', array());
		$tropes = array();

		if (!is_array($raw)) {
			$raw = '' !== (string) $raw ? explode(',', (string) $raw) : array();
		}

		foreach ($raw as $item) {
			if ($item instanceof WP_Term) {
				$tropes[] = $item->name;
			} elseif (is_numeric($item)) {
				$term = get_term((int) $item, 'sss_trope');
				if ($term instanceof WP_Term) {
					$tropes[] = $term->name;
				}
			} elseif (is_array($item)) {
				$name = (string) ($item['name'] ?? $item['title'] ?? $item['sss_trope_name'] ?? '');
				if ('' !== trim($name)) {
					$tropes[] = $name;
				}
			} elseif ('' !== trim((string) $item)) {
				$tropes[] = trim((string) $item);
			}
		}

		return array_values(array_unique($tropes));
	}
}

if (!function_exists('bbb_series_book_matches')) {
	function bbb_series_book_matches(WP_Post $book, $series): bool {
		$slug = bbb_series_entity_slug($series);
		$name = bbb_series_entity_title($series);

		if ($series instanceof WP_Term && has_term($series->term_id, 'sss_series', $book)) {
			return true;
		}

		$data = bbb_series_book_data($book);

		return $data['series_handle'] === $slug || strtolower($data['series_name']) === strtolower($name);
	}
}

if (!function_exists('bbb_series_books_for_series')) {
	function bbb_series_books_for_series($series, array $books): array {
		$matches = array_values(
			array_filter(
				$books,
				static fn(WP_Post $book): bool => bbb_series_book_matches($book, $series)
			)
		);

		usort(
			$matches,
			static function (WP_Post $a, WP_Post $b): int {
				$a_number = (int) bbb_series_book_data($a)['series_number'];
				$b_number = (int) bbb_series_book_data($b)['series_number'];

				return $a_number <=> $b_number;
			}
		);

		return $matches;
	}
}

if (!function_exists('bbb_series_lead_book')) {
	function bbb_series_lead_book($series, array $books, bool $require_one = false): ?WP_Post {
		$series_books = bbb_series_books_for_series($series, $books);
		$fallback     = $series_books[0] ?? null;

		foreach ($series_books as $book) {
			if ('1' === trim((string) bbb_series_book_data($book)['series_number'])) {
				return $book;
			}
		}

		return $require_one ? null : $fallback;
	}
}

if (!function_exists('bbb_series_find_entity_by_slug')) {
	function bbb_series_find_entity_by_slug(string $slug, array $series_list) {
		$slug = sanitize_title($slug);
		foreach ($series_list as $series) {
			if (sanitize_title(bbb_series_entity_slug($series)) === $slug) {
				return $series;
			}
		}

		return null;
	}
}

if (!function_exists('bbb_series_normalized_book_data')) {
	function bbb_series_normalized_book_data(WP_Post $book): array {
		$data = bbb_series_book_data($book);
		foreach (array('cover', 'amazon', 'bookshop', 'newsletter') as $url_key) {
			if (isset($data[$url_key]) && function_exists('bbb_normalize_url_value')) {
				$data[$url_key] = bbb_normalize_url_value($data[$url_key]);
			}
		}

		return $data;
	}
}

if (!function_exists('bbb_series_trope_url')) {
	function bbb_series_trope_url(array $trope): string {
		$name   = (string) ($trope['name'] ?? '');
		$handle = sanitize_title((string) ($trope['handle'] ?? $name));

		return function_exists('bbb_trope_page_url') ? bbb_trope_page_url($name, $handle) : home_url('/' . $handle . '-books/');
	}
}

if (!function_exists('bbb_series_trope_label')) {
	function bbb_series_trope_label_html(array $trope): string {
		$name  = strtolower(trim((string) ($trope['name'] ?? '')));
		$emoji = $trope['emoji'] ?? '';
		$slug  = (string) ($trope['handle'] ?? $name);

		return function_exists('bbb_trope_label_html')
			? bbb_trope_label_html($name, $emoji, $slug)
			: esc_html(trim(((string) $emoji ?: '🖤') . ' ' . $name));
	}
}

if (!function_exists('bbb_series_trope_name_label_html')) {
	function bbb_series_trope_name_label_html(string $name): string {
		$name = trim($name);
		if ('' === $name) {
			return '';
		}

		return function_exists('bbb_trope_label_html')
			? bbb_trope_label_html($name, '', sanitize_title($name))
			: esc_html(trim('🖤 ' . $name));
	}
}

if (!function_exists('bbb_series_shelf_emoji')) {
	function bbb_series_shelf_emoji(string $shelf_name): string {
		$slug = sanitize_title($shelf_name);

		if (function_exists('bbb_book_taxonomy_fallback_emoji')) {
			return bbb_book_taxonomy_fallback_emoji($slug, $shelf_name);
		}

		$emoji_by_shelf = array(
			'dark-romance'         => '🖤',
			'contemporary-romance' => '🤍',
			'fantasy-romance'      => '⚔️',
			'romantasy'            => '⚔️',
			'sports-romance'       => '🏒',
			'mafia-romance'        => '🥀',
			'college-romance'      => '🎓',
			'small-town-romance'   => '🏡',
			'paranormal-romance'   => '🌙',
			'why-choose'           => '✨',
		);

		return $emoji_by_shelf[$slug] ?? '📚';
	}
}

if (!function_exists('bbb_series_shelf_label_html')) {
	function bbb_series_shelf_label_html(string $shelf_name): string {
		$name = strtolower(trim($shelf_name));
		$slug = sanitize_title($name);

		if (
			'' !== $name
			&& function_exists('bbb_trope_label_html')
			&& function_exists('bbb_custom_trope_emoji_asset')
			&& '' !== bbb_custom_trope_emoji_asset($name, $slug)
		) {
			return bbb_trope_label_html($name, '', $slug);
		}

		return esc_html(trim(bbb_series_shelf_emoji($shelf_name) . ' ' . $name));
	}
}

if (!function_exists('bbb_render_series_order_detail_page')) {
	function bbb_render_series_order_detail_page($series, array $series_books, array $series_list, array $all_books): void {
		$series_slug  = bbb_series_entity_slug($series);
		$series_title = bbb_series_entity_title($series);
		$first_book   = $series_books[0] ?? null;
		$first_data   = $first_book instanceof WP_Post ? bbb_series_normalized_book_data($first_book) : array();
		$author       = (string) bbb_series_entity_meta($series, '_bbb_series_author', '');
		if ('' === $author) {
			$author = (string) ($first_data['author'] ?? '');
		}

		$all_ku     = true;
		$max_spice  = 0;
		$all_tropes = array();
		$shelf_name = (string) ($first_data['shelf'] ?? 'series');

		foreach ($series_books as $book) {
			$data      = bbb_series_normalized_book_data($book);
			$all_ku    = $all_ku && !empty($data['ku']);
			$max_spice = max($max_spice, (int) ($data['spice'] ?? 0));
			foreach ((array) ($data['tropes'] ?? array()) as $trope) {
				$name = trim((string) ($trope['name'] ?? ''));
				if ('' !== $name) {
					$all_tropes[sanitize_title($name)] = $trope;
				}
			}
		}

		$genre_tropes = array_slice(array_values($all_tropes), 0, 2);
		$blurb        = $series instanceof WP_Post ? trim(wp_strip_all_tags((string) $series->post_content)) : '';
		if ('' === $blurb) {
			$blurb = sprintf(
				'%s follows interconnected romance chaos across %d %s. Each book has its own couple, but the character connections land harder when read in order.',
				strtolower($series_title),
				count($series_books),
				1 === count($series_books) ? 'book' : 'books'
			);
		}

		$related = array();
		foreach ($series_list as $other_series) {
			if (count($related) >= 3 || bbb_series_entity_slug($other_series) === $series_slug) {
				continue;
			}

			$related_book = bbb_series_lead_book($other_series, $all_books, true);
			if (!$related_book instanceof WP_Post) {
				continue;
			}

			$related_data = bbb_series_normalized_book_data($related_book);
			$related_cover = (string) ($related_data['cover'] ?? '');
			$related_sub_parts = array_filter(
				array(
					bbb_series_entity_title($other_series),
					(string) ($related_data['author'] ?? ''),
					((string) ($related_data['shelf'] ?? '') ?: 'series'),
				)
			);
			$related_book_url = get_permalink($related_book);
			$related[]    = array(
				'handle'  => (string) ($related_data['handle'] ?? $related_book->post_name),
				'title'   => (string) ($related_data['title'] ?? get_the_title($related_book)),
				'sub'     => implode(' · ', $related_sub_parts),
				'cover'   => $related_cover,
				'book'    => (string) ($related_data['title'] ?? get_the_title($related_book)),
				'author'  => (string) ($related_data['author'] ?? ''),
				'amazon'  => (string) ($related_data['amazon'] ?? ''),
				'bookshop' => (string) ($related_data['bookshop'] ?? ''),
				'spice'   => (int) ($related_data['spice'] ?? 0),
				'shelf'   => (string) ($related_data['shelf'] ?? ''),
				'mini'    => (string) ($related_data['mini'] ?? ''),
				'ku'      => !empty($related_data['ku']),
				'url'     => $related_book_url ?: home_url('/library/?book=' . rawurlencode((string) ($related_data['handle'] ?? $related_book->post_name))),
			);
		}

		get_header();
		?>
		<main class="bbb-seriesOrderPage">
			<div class="bbb-seriesOrderPage__inner">
				<nav class="bbb-seriesOrderPage__breadcrumb" aria-label="breadcrumb">
					<a href="<?php echo esc_url(home_url('/')); ?>">home</a>
					<span>›</span>
					<a href="<?php echo esc_url(home_url('/series-reading-orders/')); ?>">series reading orders</a>
					<span>›</span>
					<span><?php echo esc_html(strtolower($series_title)); ?></span>
				</nav>

				<header class="bbb-seriesOrderPage__header">
					<p class="bbb-seriesOrderPage__genrePill">
						<span class="bbb-seriesOrderPage__genreShelf"><?php echo wp_kses_post(bbb_series_shelf_label_html($shelf_name)); ?></span>
						<?php if ($genre_tropes) : ?>
							<span class="bbb-seriesOrderPage__genreSep">·</span>
							<span class="bbb-seriesOrderPage__genreTrope"><?php echo wp_kses_post(bbb_series_trope_label_html($genre_tropes[0])); ?></span>
						<?php endif; ?>
					</p>
					<h1 class="bbb-seriesOrderPage__title"><?php echo esc_html(strtolower($series_title)); ?> series reading order</h1>
					<?php if ($author) : ?>
						<p class="bbb-seriesOrderPage__author">by <span><?php echo esc_html($author); ?></span></p>
					<?php endif; ?>
					<div class="bbb-seriesOrderPage__metaRow">
						<span><?php echo esc_html((string) count($series_books)); ?> <?php echo 1 === count($series_books) ? 'book' : 'books'; ?></span>
						<?php if ($max_spice > 0) : ?>
							<span><?php echo esc_html(str_repeat('🌶', $max_spice)); ?> spice</span>
						<?php endif; ?>
						<?php if ($all_ku && $series_books) : ?>
							<span class="is-ku">✓ all on kindle unlimited</span>
						<?php endif; ?>
					</div>
				</header>

				<p class="bbb-seriesOrderPage__blurb"><?php echo esc_html($blurb); ?></p>

				<div class="bbb-seriesOrderPage__orderHead">
					<h2>reading order</h2>
					<span>read in order recommended</span>
				</div>

				<div class="bbb-seriesOrderPage__bookList" role="list">
					<?php foreach ($series_books as $index => $book) : ?>
						<?php
						$data          = bbb_series_normalized_book_data($book);
						$book_url      = get_permalink($book);
						$book_num      = (string) ($data['series_number'] ?: ($index + 1));
						$book_title    = (string) ($data['title'] ?? get_the_title($book));
						$is_standalone = !empty($data['standalone']);
						$tropes        = array_slice((array) ($data['tropes'] ?? array()), 0, 4);
						$trope_names   = array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array()));
						$trope_display = array_map(
							static fn(array $trope): string => function_exists('bbb_trope_label') ? bbb_trope_label((string) ($trope['name'] ?? ''), $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . (string) ($trope['name'] ?? '')),
							(array) ($data['tropes'] ?? array())
						);
						$trope_urls    = array_map(static fn(array $trope): string => bbb_series_trope_url($trope), (array) ($data['tropes'] ?? array()));
						?>
						<article
							class="bbb-seriesOrderPage__bookRow sss-lib__book"
							role="listitem"
							data-handle="<?php echo esc_attr($book->post_name); ?>"
							data-title="<?php echo esc_attr($book_title); ?>"
							data-author="<?php echo esc_attr((string) ($data['author'] ?? '')); ?>"
							data-cover="<?php echo esc_url((string) ($data['cover'] ?? '')); ?>"
							data-amazon="<?php echo esc_url((string) ($data['amazon'] ?? '')); ?>"
							data-bookshop="<?php echo esc_url((string) ($data['bookshop'] ?? '')); ?>"
							data-shelf="<?php echo esc_attr((string) ($data['shelf'] ?? '')); ?>"
							data-spice="<?php echo esc_attr((string) ($data['spice'] ?? '')); ?>"
							data-tropes="<?php echo esc_attr(implode(', ', array_filter($trope_names))); ?>"
							data-tropes-display="<?php echo esc_attr(implode(', ', array_filter($trope_display))); ?>"
							data-trope-urls="<?php echo esc_attr(implode(', ', array_filter($trope_urls))); ?>"
							data-mini="<?php echo esc_attr((string) ($data['mini'] ?? '')); ?>"
							data-series="<?php echo esc_attr((string) ($data['series_handle'] ?? $series_slug)); ?>"
							data-series-name="<?php echo esc_attr((string) ($data['series_name'] ?? $series_title)); ?>"
							data-series-number="<?php echo esc_attr($book_num); ?>"
							data-standalone="<?php echo $is_standalone ? 'true' : 'false'; ?>"
							data-ku="<?php echo !empty($data['ku']) ? 'true' : 'false'; ?>"
						>
							<span class="bbb-seriesOrderPage__bookNum"><?php echo esc_html($book_num); ?></span>
							<span class="bbb-seriesOrderPage__coverWrap sss-lib__coverWrap">
								<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
									<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
									<span class="sss-lib__heartLabel" data-heart-label>save</span>
								</span>
								<span class="sss-lib__seriesBadge<?php echo $is_standalone ? ' sss-lib__seriesBadge--standalone' : ''; ?>" aria-label="book <?php echo esc_attr($book_num); ?>"><?php echo esc_html($book_num); ?></span>
								<?php if ((int) ($data['spice'] ?? 0) > 0) : ?>
									<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', (int) $data['spice'])); ?></span>
								<?php endif; ?>
								<a class="bbb-seriesOrderPage__coverLink" href="<?php echo esc_url($book_url); ?>">
									<?php if (!empty($data['cover'])) : ?>
										<img class="bbb-seriesOrderPage__cover" src="<?php echo esc_url((string) $data['cover']); ?>" alt="<?php echo esc_attr($book_title . ' book cover'); ?>" loading="lazy">
									<?php else : ?>
										<span class="bbb-seriesOrderPage__coverPlaceholder"><?php echo esc_html(strtolower($book_title)); ?></span>
									<?php endif; ?>
								</a>
							</span>
							<div class="bbb-seriesOrderPage__bookInfo">
								<h3><a href="<?php echo esc_url($book_url); ?>"><?php echo esc_html(strtolower($book_title)); ?></a></h3>
								<?php if ((int) ($data['spice'] ?? 0) > 0 || !empty($data['ku'])) : ?>
									<p class="bbb-seriesOrderPage__bookSpice">
										<?php echo (int) ($data['spice'] ?? 0) > 0 ? esc_html(str_repeat('🌶', (int) $data['spice'])) : ''; ?>
										<?php if (!empty($data['ku'])) : ?><span>✓ kindle unlimited</span><?php endif; ?>
									</p>
								<?php endif; ?>
								<?php if (!empty($data['mini'])) : ?>
									<p class="bbb-seriesOrderPage__bookBlurb"><?php echo esc_html((string) $data['mini']); ?></p>
								<?php endif; ?>
								<?php if ($tropes) : ?>
									<div class="bbb-seriesOrderPage__bookTropes">
										<?php foreach ($tropes as $trope) : ?>
											<a href="<?php echo esc_url(bbb_series_trope_url($trope)); ?>"><?php echo bbb_series_trope_label_html($trope); ?></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="bbb-seriesOrderPage__bookCta">
								<?php if (!empty($data['amazon'])) : ?>
									<?php if (!empty($data['ku'])) : ?>
										<a class="is-ku" href="<?php echo esc_url((string) $data['amazon']); ?>" target="_blank" rel="noopener">read free on kindle unlimited</a>
									<?php endif; ?>
									<a class="is-amazon" href="<?php echo esc_url((string) $data['amazon']); ?>" target="_blank" rel="noopener">buy on amazon<?php echo !empty($data['ku']) ? ' · own it forever' : ''; ?></a>
								<?php endif; ?>
								<?php if (!empty($data['bookshop'])) : ?>
									<a class="is-bookshop" href="<?php echo esc_url((string) $data['bookshop']); ?>" target="_blank" rel="noopener">prefer indie? bookshop.org →</a>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<div class="bbb-seriesOrderPage__note">
					<span>follow the books in order for the cleanest timeline, character connections, and series payoff.</span>
				</div>

				<section class="bbb-seriesOrderPage__faq" aria-label="frequently asked questions">
					<p class="bbb-seriesOrderPage__sectionLabel">frequently asked questions</p>
					<div class="bbb-seriesOrderPage__faqItem">
						<h2>what order should i read <?php echo esc_html(strtolower($series_title)); ?>?</h2>
						<p><?php echo esc_html(implode(' → ', array_map(static fn(WP_Post $book): string => strtolower(get_the_title($book)), $series_books))); ?>.</p>
					</div>
					<div class="bbb-seriesOrderPage__faqItem">
						<h2>do i have to read this series in order?</h2>
						<p>yes, this guide is arranged in the recommended reading order so the timeline, character connections, and series payoff stay clear.</p>
					</div>
					<div class="bbb-seriesOrderPage__faqItem">
						<h2>is <?php echo esc_html(strtolower($series_title)); ?> on kindle unlimited?</h2>
						<p><?php echo $all_ku ? 'yes, the listed books are marked as kindle unlimited.' : 'some books may be on kindle unlimited; check each book link for the current retailer status.'; ?></p>
					</div>
				</section>

				<?php if ($related) : ?>
					<section class="bbb-seriesOrderPage__also" aria-label="read these next">
						<p class="bbb-seriesOrderPage__sectionLabel">if you liked this series, read these next</p>
						<div class="bbb-seriesOrderPage__alsoGrid">
							<?php foreach ($related as $related_card) : ?>
								<article
									class="bbb-seriesOrderPage__alsoCard sss-lib__book"
									data-handle="<?php echo esc_attr($related_card['handle']); ?>"
									data-title="<?php echo esc_attr($related_card['book']); ?>"
									data-author="<?php echo esc_attr($related_card['author']); ?>"
									data-cover="<?php echo esc_url($related_card['cover']); ?>"
									data-amazon="<?php echo esc_url($related_card['amazon']); ?>"
									data-bookshop="<?php echo esc_url($related_card['bookshop']); ?>"
									data-spice="<?php echo esc_attr((string) $related_card['spice']); ?>"
									data-shelf="<?php echo esc_attr($related_card['shelf']); ?>"
									data-mini="<?php echo esc_attr($related_card['mini']); ?>"
									data-ku="<?php echo $related_card['ku'] ? 'true' : 'false'; ?>"
									data-url="<?php echo esc_url($related_card['url']); ?>"
								>
									<?php if (!empty($related_card['cover'])) : ?>
										<span class="bbb-seriesOrderPage__alsoCoverWrap">
											<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
												<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
												<span class="sss-lib__heartLabel" data-heart-label>save</span>
											</span>
											<?php if ($related_card['spice'] > 0) : ?>
												<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $related_card['spice'])); ?></span>
											<?php endif; ?>
											<a href="<?php echo esc_url($related_card['url']); ?>" aria-label="<?php echo esc_attr('open ' . $related_card['book']); ?>">
												<img class="bbb-seriesOrderPage__alsoCover" src="<?php echo esc_url($related_card['cover']); ?>" alt="<?php echo esc_attr($related_card['book'] . ' book cover'); ?>" loading="lazy">
											</a>
										</span>
									<?php endif; ?>
									<a class="bbb-seriesOrderPage__alsoTitle" href="<?php echo esc_url($related_card['url']); ?>"><?php echo esc_html(strtolower($related_card['title'])); ?></a>
									<small><?php echo esc_html($related_card['sub']); ?></small>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="bbb-seriesOrderPage__seo" aria-label="explore more">
					<p class="bbb-seriesOrderPage__sectionLabel">explore more</p>
					<div class="bbb-seriesOrderPage__seoLinks">
						<?php if ($author) : ?>
							<a href="<?php echo esc_url(home_url('/?s=' . rawurlencode($author))); ?>">→ all <?php echo esc_html($author); ?> books</a>
						<?php endif; ?>
						<?php foreach (array_slice(array_values($all_tropes), 0, 4) as $trope) : ?>
							<a href="<?php echo esc_url(bbb_series_trope_url($trope)); ?>">→ <?php echo bbb_series_trope_label_html($trope); ?> books</a>
						<?php endforeach; ?>
						<?php if (!empty($first_data['title'])) : ?>
							<a href="<?php echo esc_url(home_url('/books-like-' . sanitize_title((string) $first_data['title']) . '/')); ?>">→ books like <?php echo esc_html(strtolower((string) $first_data['title'])); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url(home_url('/series-reading-orders/')); ?>">→ all series reading orders</a>
					</div>
				</section>
			</div>
		</main>
		<?php
		get_footer();
	}
}

$series_list = bbb_series_terms();
$books       = bbb_series_visible_books();
$archive     = array();

$requested_series_handle = sanitize_title((string) get_query_var('bbb_series_handle'));
if ('' !== $requested_series_handle) {
	$requested_series = bbb_series_find_entity_by_slug($requested_series_handle, $series_list);
	if ($requested_series) {
		bbb_enqueue_css('bbb-series-order-page', 'assets/css/series-order-page.css', array('bbb-bookshelf-signup'));
		bbb_render_series_order_detail_page($requested_series, bbb_series_books_for_series($requested_series, $books), $series_list, $books);
		return;
	}
}

foreach ($series_list as $series) {
	$lead_book = bbb_series_lead_book($series, $books);
	if (!$lead_book) {
		continue;
	}

	$lead_data = bbb_series_book_data($lead_book);
	$shelf     = '' !== $lead_data['shelf'] ? $lead_data['shelf'] : 'series';

	$archive[$shelf][] = array(
		'series' => $series,
		'book'   => $lead_book,
		'data'   => $lead_data,
	);
}

get_header();
?>

<section class="series-container">
	<section class="series-archive" data-series-archive>
		<div class="series-header series-header--archive">
			<div class="series-subtitle">the series index</div>
			<h1>every series worth keeping together</h1>
			<div class="series-archive-intro">browse the full series archive by shelf, then open the individual series file when you’re ready to binge.</div>
		</div>

		<?php foreach ($archive as $shelf_name => $cards) : ?>
			<section class="series-archive-shelfGroup">
				<div class="series-archive-shelfKicker">shelf</div>
				<h2 class="series-archive-shelfTitle"><?php echo esc_html($shelf_name); ?></h2>
				<div class="series-archive-grid">
					<?php foreach ($cards as $card) : ?>
						<?php
						$series = $card['series'];
						$data   = $card['data'];
						$slug   = bbb_series_entity_slug($series);
						$title  = bbb_series_entity_title($series);
						$tropes = array_slice((array) ($data['tropes'] ?: bbb_series_term_tropes($series)), 0, 3);
						?>
						<a class="series-archive-card" href="/series/<?php echo esc_attr($slug); ?>/" data-series-card="<?php echo esc_attr($slug); ?>">
							<div class="series-archive-coverWrap">
								<?php if ($data['spice'] > 0) : ?>
									<div class="series-archive-spice"><?php echo esc_html(str_repeat('🌶', $data['spice'])); ?></div>
								<?php endif; ?>
								<?php if ('' !== $data['cover']) : ?>
									<img class="series-archive-cover" src="<?php echo esc_url($data['cover']); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
								<?php endif; ?>
							</div>
							<div class="series-archive-meta">
								<div class="series-archive-title"><?php echo esc_html($title); ?></div>
								<?php if ($tropes) : ?>
									<div class="series-archive-tropes">
										<?php foreach ($tropes as $trope) : ?>
											<span><?php echo is_array($trope) ? wp_kses_post(bbb_series_trope_label_html($trope)) : wp_kses_post(bbb_series_trope_name_label_html((string) $trope)); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<div class="series-archive-cta">open series file →</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	</section>

	<div class="series-shelf-banner" id="seriesShelfBanner" hidden>
		<span>you’ve saved books to your shelf ♥</span>
		<a href="<?php echo esc_url(home_url('/library/?shelf=open')); ?>">view your list</a>
	</div>

	<?php foreach ($series_list as $series) : ?>
		<?php
		$series_slug          = bbb_series_entity_slug($series);
		$series_title         = bbb_series_entity_title($series);
		$series_display_title = false === stripos($series_title, 'series') ? $series_title . ' series' : $series_title;
		$series_tropes        = bbb_series_term_tropes($series);
		$series_books         = bbb_series_books_for_series($series, $books);
		?>
		<section class="series-page" data-series="<?php echo esc_attr($series_slug); ?>" style="display:none;">
			<div class="series-header">
				<h1><?php echo esc_html($series_display_title); ?></h1>
				<div class="series-reading-note" data-series-reading></div>
				<div class="series-subtitle">the society-approved books in this series</div>
				<?php if ($series_tropes) : ?>
					<div class="series-tropes">
						<?php
						echo wp_kses_post(
							implode(
								', ',
								array_map(static fn(string $trope): string => bbb_series_trope_name_label_html($trope), $series_tropes)
							)
						);
						?>
					</div>
				<?php endif; ?>
				<a href="/series-reading-orders/" class="series-back" data-series-back data-default-href="/series-reading-orders/" data-default-label="← back to series reading orders">← back to series reading orders</a>
			</div>

			<div class="series-grid">
				<?php foreach ($series_books as $book) : ?>
					<?php get_template_part('template-parts/sss-book-card', null, array('book' => $book)); ?>
				<?php endforeach; ?>
			</div>

			<div class="related-series">
				<h2>more series to binge</h2>
				<div class="related-series-grid">
					<?php
					$related_count = 0;
					foreach ($series_list as $other_series) :
						if ($related_count >= 5 || bbb_series_entity_slug($other_series) === $series_slug) {
							continue;
						}

						$related_book = bbb_series_lead_book($other_series, $books, true);
						if (!$related_book) {
							continue;
						}

						$related_data = bbb_series_book_data($related_book);
						$related_slug = bbb_series_entity_slug($other_series);
						$related_count++;
						?>
						<a class="related-series-card" href="/series/<?php echo esc_attr($related_slug); ?>/">
							<div class="related-series-coverWrap">
								<?php if ('' !== $related_data['cover']) : ?>
									<img class="related-series-cover" src="<?php echo esc_url($related_data['cover']); ?>" alt="<?php echo esc_attr($related_data['title']); ?>">
								<?php endif; ?>
							</div>
							<div class="related-series-meta">
								<div class="related-series-title"><?php echo esc_html(bbb_series_entity_title($other_series)); ?></div>
								<?php if ($related_data['tropes']) : ?>
									<div class="related-series-trope">
										<?php
										echo wp_kses_post(
											implode(
												', ',
												array_map(static fn(array $trope): string => bbb_series_trope_label_html($trope), (array) $related_data['tropes'])
											)
										);
										?>
									</div>
								<?php endif; ?>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endforeach; ?>
</section>

<?php
get_template_part('template-parts/sss/library-modal');
get_footer();
