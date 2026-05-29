<?php
/**
 * Dynamic trope and shelf pages for imported book data.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_book_taxonomy_kind_for_taxonomy(string $taxonomy): string {
	return false !== strpos($taxonomy, 'shelf') ? 'shelf' : 'trope';
}

function bbb_book_taxonomies_for_kind(string $kind): array {
	return 'shelf' === $kind
		? array('bbb_shelf', 'sss_shelf')
		: array('bbb_trope', 'sss_trope');
}

function bbb_book_taxonomy_slug_candidates(string $slug): array {
	$slug       = sanitize_title($slug);
	$candidates = array($slug);

	foreach (array('-books', '-book', '-romance-books', '-romance-book') as $suffix) {
		if (substr($slug, -strlen($suffix)) === $suffix) {
			$candidates[] = substr($slug, 0, -strlen($suffix));
		}
	}

	if (substr($slug, -6) !== '-books') {
		$candidates[] = $slug . '-books';
	}

	return array_values(array_unique(array_filter($candidates)));
}

function bbb_book_taxonomy_match_key(string $value): string {
	$tokens = preg_split('/[^a-z0-9]+/', strtolower(sanitize_title($value)));
	$tokens = array_filter((array) $tokens, static function ($token): bool {
		return !in_array($token, array('book', 'books', 'romance', 'x', 'and'), true);
	});

	return implode('', $tokens);
}

function bbb_find_book_taxonomy_term(string $slug, string $kind = ''): ?WP_Term {
	$kinds = $kind ? array($kind) : array('shelf', 'trope');
	$fallback_term = null;
	$match_keys    = array_map('bbb_book_taxonomy_match_key', bbb_book_taxonomy_slug_candidates($slug));
	$match_keys    = array_values(array_unique(array_filter($match_keys)));

	foreach ($kinds as $candidate_kind) {
		foreach (bbb_book_taxonomies_for_kind($candidate_kind) as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			foreach (bbb_book_taxonomy_slug_candidates($slug) as $candidate_slug) {
				$term = get_term_by('slug', $candidate_slug, $taxonomy);
				if ($term instanceof WP_Term) {
					if ((int) $term->count > 0) {
						return $term;
					}
					$fallback_term = $fallback_term ?: $term;
				}
			}

			if ($match_keys) {
				$terms = get_terms(array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				));

				if (is_wp_error($terms)) {
					continue;
				}

				foreach ($terms as $term) {
					if (!$term instanceof WP_Term) {
						continue;
					}

					$term_keys = array_unique(array_filter(array(
						bbb_book_taxonomy_match_key($term->slug),
						bbb_book_taxonomy_match_key($term->name),
					)));

					if (!array_intersect($match_keys, $term_keys)) {
						continue;
					}

					if ((int) $term->count > 0) {
						return $term;
					}

					$fallback_term = $fallback_term ?: $term;
				}
			}
		}
	}

	return $fallback_term;
}

function bbb_get_page_taxonomy_term(string $kind): ?WP_Term {
	$route_term = $GLOBALS['bbb_book_taxonomy_route_term'] ?? null;
	$route_kind_override = (string) ($GLOBALS['bbb_book_taxonomy_route_kind_override'] ?? '');
	if ($route_term instanceof WP_Term && (bbb_book_taxonomy_kind_for_taxonomy($route_term->taxonomy) === $kind || $route_kind_override === $kind)) {
		return $route_term;
	}

	$page_id = get_the_ID();
	$field   = 'shelf' === $kind ? 'shelf_term' : 'trope_term';
	$value   = $page_id ? bbb_get_field($field, $page_id) : null;

	if (is_array($value) && isset($value['term_id'])) {
		$term = get_term((int) $value['term_id']);
		if ($term instanceof WP_Term) {
			return $term;
		}
	}

	if ($value instanceof WP_Term) {
		return $value;
	}

	$slug = $page_id ? get_post_field('post_name', $page_id) : bbb_current_route_slug();

	return bbb_find_book_taxonomy_term((string) $slug, $kind);
}

function bbb_book_taxonomy_term_emoji(WP_Term $term): string {
	foreach (array('trope_emoji', 'shelf_emoji', 'emoji') as $key) {
		$value = (string) get_term_meta($term->term_id, $key, true);
		if ('' !== $value) {
			return $value;
		}
	}

	return bbb_book_taxonomy_fallback_emoji($term->slug, $term->name);
}

function bbb_book_taxonomy_fallback_emoji(string $slug, string $name = ''): string {
	$key = sanitize_title($slug ?: $name);
	$map = array(
		'contemporary-romance' => '💋',
		'dark-romance'        => '🖤',
		'fantasy-romance'     => '🔮',
		'hockey-romance'      => '🏒',
		'mafia-romance'       => '🥀',
		'monster-romance'     => '🐺',
		'paranormal-romance'  => '🌙',
		'romantasy'           => '🔮',
		'romantic-suspense'   => '🕯',
		'sci-fi-romance'      => '🚀',
		'small-town-romance'  => '🌲',
		'sports-romance'      => '🏒',
		'western-romance'     => '🤠',
	);

	if (isset($map[$key])) {
		return $map[$key];
	}

	if (str_contains($key, 'dark') || str_contains($key, 'mafia')) {
		return '🖤';
	}

	if (str_contains($key, 'fantasy') || str_contains($key, 'romantasy')) {
		return '🔮';
	}

	if (str_contains($key, 'sport') || str_contains($key, 'hockey') || str_contains($key, 'football') || str_contains($key, 'baseball')) {
		return '🏒';
	}

	if (str_contains($key, 'small-town')) {
		return '🌲';
	}

	if (str_contains($key, 'paranormal') || str_contains($key, 'vampire') || str_contains($key, 'wolf')) {
		return '🌙';
	}

	return '📚';
}

function bbb_book_taxonomy_term_description(WP_Term $term): string {
	$description = trim((string) $term->description);
	if ('' !== $description) {
		return $description;
	}

	foreach (array('trope_description', 'shelf_description', 'description') as $key) {
		$value = trim((string) get_term_meta($term->term_id, $key, true));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_book_taxonomy_term_colors(WP_Term $term): array {
	$bg = (string) get_term_meta($term->term_id, '_trope_bg', true);
	$fg = (string) get_term_meta($term->term_id, '_trope_text', true);

	if ('' === $bg || '' === $fg) {
		$colors = function_exists('bbb_get_trope_colors') ? bbb_get_trope_colors($term->slug) : array('#f3bfd5', '#4b112d');
		$bg     = '' !== $bg ? $bg : $colors[0];
		$fg     = '' !== $fg ? $fg : $colors[1];
	}

	return array($bg, $fg);
}

function bbb_book_taxonomy_term_url(WP_Term $term): string {
	$slug = $term->slug;
	if (substr($slug, -6) !== '-books') {
		$slug .= '-books';
	}

	return home_url('/' . $slug . '/');
}

function bbb_get_book_ids_for_taxonomy_term(WP_Term $term): array {
	$kind = bbb_book_taxonomy_kind_for_taxonomy($term->taxonomy);
	$ids  = array();

	$queries = array(
		array('post_type' => 'bbb_book', 'taxonomy' => 'bbb_' . $kind),
		array('post_type' => 'sss_book', 'taxonomy' => 'sss_' . $kind),
	);

	foreach ($queries as $query_def) {
		if (!post_type_exists($query_def['post_type']) || !taxonomy_exists($query_def['taxonomy'])) {
			continue;
		}

		$query = new WP_Query(
			array(
				'post_type'              => $query_def['post_type'],
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => $query_def['taxonomy'],
						'field'    => 'slug',
						'terms'    => $term->slug,
					),
				),
			)
		);

		foreach ($query->posts as $post_id) {
			$post_id = (int) $post_id;
			if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($post_id)) {
				continue;
			}
			$ids[] = $post_id;
		}
	}

	return array_values(array_unique($ids));
}

function bbb_get_book_taxonomy_discovery_items(string $kind): array {
	$items = array();
	foreach (bbb_book_taxonomies_for_kind($kind) as $taxonomy) {
		if (!taxonomy_exists($taxonomy)) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if (is_wp_error($terms)) {
			continue;
		}

		foreach ($terms as $term) {
			if (!$term instanceof WP_Term || isset($items[$term->slug])) {
				continue;
			}

			list($bg, $fg) = bbb_book_taxonomy_term_colors($term);
			$items[$term->slug] = array(
				'url'         => bbb_book_taxonomy_term_url($term),
				'name'        => $term->name,
				'emoji'       => bbb_book_taxonomy_term_emoji($term),
				'description' => bbb_book_taxonomy_term_description($term),
				'bg'          => $bg,
				'text'        => $fg,
				'term'        => $term,
			);
		}
	}

	return array_values($items);
}

function bbb_book_taxonomy_related_terms(WP_Term $current_term, array $book_ids, int $limit = 6): array {
	$related           = array();
	$current_match_key = bbb_book_taxonomy_match_key($current_term->slug ?: $current_term->name);

	foreach ($book_ids as $book_id) {
		$book_id = (int) $book_id;
		if (!$book_id) {
			continue;
		}

		foreach (array('bbb_trope', 'sss_trope', 'bbb_shelf', 'sss_shelf') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = wp_get_post_terms($book_id, $taxonomy);
			if (is_wp_error($terms)) {
				continue;
			}

			foreach ($terms as $term) {
				if (!$term instanceof WP_Term) {
					continue;
				}

				$match_key = bbb_book_taxonomy_match_key($term->slug ?: $term->name);
				if (!$match_key || $match_key === $current_match_key || (int) $term->term_id === (int) $current_term->term_id) {
					continue;
				}

				$key = bbb_book_taxonomy_kind_for_taxonomy($term->taxonomy) . ':' . $match_key;
				if (!isset($related[$key])) {
					$related[$key] = array(
						'term'  => $term,
						'count' => 0,
					);
				}

				$related[$key]['count']++;
			}
		}
	}

	usort(
		$related,
		static function (array $first, array $second): int {
			if ((int) $first['count'] !== (int) $second['count']) {
				return (int) $second['count'] <=> (int) $first['count'];
			}

			return strcasecmp($first['term']->name, $second['term']->name);
		}
	);

	return array_slice(array_column($related, 'term'), 0, $limit);
}

function bbb_render_book_taxonomy_related_terms(WP_Term $term, array $book_ids): string {
	$related_terms = bbb_book_taxonomy_related_terms($term, $book_ids);
	if (!$related_terms) {
		return '';
	}

	ob_start();
	?>
	<div class="sss-trope__related" aria-labelledby="bbb-book-taxonomy-recommendations-title">
		<div class="sss-trope__relatedTitle" id="bbb-book-taxonomy-recommendations-title">
			<?php esc_html_e('the society also recommends', 'bybookishbabe-shopify-port'); ?>
		</div>
		<div class="sss-trope__relatedGrid">
			<?php foreach ($related_terms as $related_term) : ?>
				<?php
				$emoji      = bbb_book_taxonomy_term_emoji($related_term);
				$emoji_html = function_exists('bbb_trope_emoji_html') ? bbb_trope_emoji_html($related_term->name, $emoji, $related_term->slug) : esc_html($emoji);
				?>
				<a class="sss-trope__relatedItem" href="<?php echo esc_url(bbb_book_taxonomy_term_url($related_term)); ?>">
					<span aria-hidden="true"><?php echo $emoji_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span><?php echo esc_html(strtolower($related_term->name)); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

function bbb_render_book_taxonomy_page(WP_Term $term): void {
	$route_kind_override = (string) ($GLOBALS['bbb_book_taxonomy_route_kind_override'] ?? '');
	$kind        = in_array($route_kind_override, array('shelf', 'trope'), true) ? $route_kind_override : bbb_book_taxonomy_kind_for_taxonomy($term->taxonomy);
	$emoji       = bbb_book_taxonomy_term_emoji($term);
	$emoji_html  = function_exists('bbb_trope_emoji_html') ? bbb_trope_emoji_html($term->name, $emoji, $term->slug) : esc_html($emoji);
	$description = bbb_book_taxonomy_term_description($term);
	$book_ids    = bbb_get_book_ids_for_taxonomy_term($term);
	$eyebrow     = 'shelf' === $kind ? 'the society shelves' : 'the trope archive';
	$count_text  = 'shelf' === $kind
		? sprintf('%d %s books in the society library', count($book_ids), strtolower($term->name))
		: sprintf('%d books shelved under this trope', count($book_ids));
	?>
	<section class="sss-lib" data-sss-lib="public">
		<div class="sss-lib__wrap">
			<div class="sss-tropeTop">
				<div class="sss-tropeTop__left">
					<div class="sss-trope__header">
							<div class="sss-trope__eyebrow"><?php echo esc_html($eyebrow); ?></div>
							<h1 class="sss-trope__title">
								<?php echo esc_html($term->name); ?> books <?php echo $emoji_html; ?>
							</h1>
						<?php if ('' !== $description) : ?>
							<p class="sss-trope__desc"><?php echo esc_html($description); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<div class="sss-tropeTop__right">
					<div class="sss-tropeInvite">
						<div class="sss-tropeInvite__kicker">the private layer</div>
						<div class="sss-tropeInvite__title">one romance recommendation.<br>every sunday.</div>
						<div class="sss-tropeInvite__sub">morally gray men delivered straight to you</div>
						<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-tropeInvite__btn" target="_blank" rel="noopener">join the society →</a>
					</div>
				</div>
			</div>

			<div class="sss-trope__divider"></div>
			<div class="sss-trope__count"><?php echo esc_html($count_text); ?></div>

			<div class="sss-lib__seriesDisclaimer">
				<span class="sss-lib__seriesBadge sss-lib__seriesBadge--demo">1</span>
				<span class="sss-lib__seriesText">the pink # badge means the book is part of a connected series.</span>
				<span class="sss-lib__seriesBadge sss-lib__seriesBadge--demo sss-lib__seriesBadge--standalone">1</span>
				<span class="sss-lib__seriesText">the white # badge means the book is part of a series but can be read as a standalone.</span>
			</div>

			<div class="sss-lib__grid sss-lib__grid--browsePage">
				<?php foreach ($book_ids as $book_id) : ?>
					<?php bbb_render_component('sss-book-card', array('book' => $book_id)); ?>
				<?php endforeach; ?>
			</div>

			<div class="sss-trope__actions">
				<a href="<?php echo esc_url(home_url('/library/')); ?>" class="sss-trope__actionLink">see the full romance library →</a>
				<a href="<?php echo esc_url(home_url('/romance-trope-dictionary/')); ?>" class="sss-trope__actionLink">see all tropes →</a>
				<a href="<?php echo esc_url(home_url('/reader-quizzes/')); ?>" class="sss-trope__actionLink">find your fictional boyfriend →</a>
			</div>

			<?php echo bbb_render_book_taxonomy_related_terms($term, $book_ids); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<div id="sssTropePopup" class="sss-tropePopup" hidden>
			<div class="sss-tropePopup__title">i want books by trope</div>
			<div class="sss-tropePopup__list" id="sssTropePopupList"></div>
			<button class="sss-tropePopup__close" id="sssTropePopupClose">×</button>
		</div>
	</section>

	<div id="sssFloatingShare">
		<button id="sssShareLibrary" class="sss-lib__floatingShareBtn">📲</button>
	</div>

	<div id="sssSaveToast" class="sss-saveToast">
		<span>added to your society shelf 🖤</span>
		<a href="#" id="sssToastShelfLink" target="_blank" rel="noopener" class="sss-saveToast__link">view your shelf →</a>
	</div>
	<?php get_template_part('template-parts/library/library-modal'); ?>
	<?php
}
