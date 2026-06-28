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
	$match_keys    = array_map('bbb_book_taxonomy_match_key', bbb_book_taxonomy_slug_candidates($slug));
	$match_keys    = array_values(array_unique(array_filter($match_keys)));
	$matches       = array();
	$fallbacks     = array();
	$order         = 0;

	$remember_term = static function (array &$bucket, WP_Term $term, int $priority) use (&$order): void {
		$key = $term->taxonomy . ':' . $term->term_id;
		if (isset($bucket[$key])) {
			$bucket[$key]['priority'] = min((int) $bucket[$key]['priority'], $priority);
			return;
		}

		$bucket[$key] = array(
			'term'     => $term,
			'priority' => $priority,
			'order'    => $order++,
		);
	};

	$pick_term = static function (array $bucket): ?WP_Term {
		if (!$bucket) {
			return null;
		}

		usort(
			$bucket,
			static function (array $first, array $second): int {
				$count_compare = (int) $second['term']->count <=> (int) $first['term']->count;
				if (0 !== $count_compare) {
					return $count_compare;
				}

				$priority_compare = (int) $first['priority'] <=> (int) $second['priority'];
				if (0 !== $priority_compare) {
					return $priority_compare;
				}

				return (int) $first['order'] <=> (int) $second['order'];
			}
		);

		return $bucket[0]['term'];
	};

	foreach ($kinds as $candidate_kind) {
		foreach (bbb_book_taxonomies_for_kind($candidate_kind) as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			foreach (bbb_book_taxonomy_slug_candidates($slug) as $candidate_index => $candidate_slug) {
				$term = get_term_by('slug', $candidate_slug, $taxonomy);
				if ($term instanceof WP_Term) {
					if ((int) $term->count > 0) {
						$remember_term($matches, $term, (int) $candidate_index);
					} else {
						$remember_term($fallbacks, $term, (int) $candidate_index);
					}
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
						$remember_term($matches, $term, 100);
					} else {
						$remember_term($fallbacks, $term, 100);
					}
				}
			}
		}
	}

	return $pick_term($matches) ?: $pick_term($fallbacks);
}

function bbb_book_taxonomy_equivalent_slugs(WP_Term $term, string $taxonomy): array {
	if (!taxonomy_exists($taxonomy)) {
		return array($term->slug);
	}

	$match_key = bbb_book_taxonomy_match_key($term->slug ?: $term->name);
	$slugs     = array($term->slug);
	if (!$match_key) {
		return $slugs;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	if (is_wp_error($terms)) {
		return $slugs;
	}

	foreach ($terms as $candidate) {
		if (!$candidate instanceof WP_Term) {
			continue;
		}

		$candidate_keys = array_unique(array_filter(array(
			bbb_book_taxonomy_match_key($candidate->slug),
			bbb_book_taxonomy_match_key($candidate->name),
		)));

		if (in_array($match_key, $candidate_keys, true)) {
			$slugs[] = $candidate->slug;
		}
	}

	return array_values(array_unique(array_filter($slugs)));
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

		$term_slugs = bbb_book_taxonomy_equivalent_slugs($term, $query_def['taxonomy']);

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
						'terms'    => $term_slugs,
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

function bbb_book_taxonomy_filter_options(array $book_ids, string $filter_kind): array {
	$options = array();
	$taxonomies = 'trope' === $filter_kind
		? array('bbb_book' => 'bbb_trope', 'sss_book' => 'sss_trope')
		: array('bbb_book' => 'bbb_shelf', 'sss_book' => 'sss_shelf');

	foreach ($book_ids as $book_id) {
		$book_id = (int) $book_id;
		if (!$book_id) {
			continue;
		}

		$post_type = (string) get_post_type($book_id);
		$taxonomy  = $taxonomies[$post_type] ?? '';
		$terms     = $taxonomy && taxonomy_exists($taxonomy) ? get_the_terms($book_id, $taxonomy) : false;
		if (!$terms || is_wp_error($terms)) {
			continue;
		}

		foreach ($terms as $term) {
			if (!$term instanceof WP_Term) {
				continue;
			}

			$label = trim((string) $term->name);
			if ('' === $label) {
				continue;
			}

			$key = sanitize_title($label);
			if (!isset($options[$key])) {
				$options[$key] = array(
					'label' => $label,
					'count' => 0,
				);
			}

			$options[$key]['count']++;
		}
	}

	uasort(
		$options,
		static function (array $first, array $second): int {
			return strcasecmp((string) $first['label'], (string) $second['label']);
		}
	);

	return $options;
}

function bbb_render_book_taxonomy_filters(array $book_ids, string $page_kind): string {
	if (!$book_ids) {
		return '';
	}

	$filter_kind    = 'shelf' === $page_kind ? 'trope' : 'genre';
	$filter_options = bbb_book_taxonomy_filter_options($book_ids, 'trope' === $filter_kind ? 'trope' : 'shelf');
	$spice_profiles = function_exists('bbb_reader_spice_profiles') ? bbb_reader_spice_profiles() : array(
		1 => array('label' => 'soft spice', 'peppers' => '🌶'),
		2 => array('label' => 'some heat', 'peppers' => '🌶🌶'),
		3 => array('label' => 'balanced', 'peppers' => '🌶🌶🌶'),
		4 => array('label' => 'high spice', 'peppers' => '🌶🌶🌶🌶'),
		5 => array('label' => 'wreck me', 'peppers' => '🌶🌶🌶🌶🌶'),
	);

	ob_start();
	?>
	<div class="sss-tropeFilters" data-book-taxonomy-filters>
		<p class="sss-tropeFilters__hint">filter by <?php echo esc_html($filter_kind); ?> and spice level.</p>
		<div class="sss-tropeFilters__controls">
			<label class="sss-tropeFilters__genre">
				<span><?php echo esc_html($filter_kind); ?></span>
				<select data-book-taxonomy-term data-filter-kind="<?php echo esc_attr($filter_kind); ?>">
					<option value="">all <?php echo esc_html('trope' === $filter_kind ? 'tropes' : 'genres'); ?></option>
					<?php foreach ($filter_options as $key => $option) : ?>
						<option value="<?php echo esc_attr((string) $key); ?>">
							<?php echo esc_html(strtolower((string) $option['label'])); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<div class="sss-tropeFilters__spiceWrap">
				<div class="sss-tropeFilters__spice" role="radiogroup" aria-label="filter by spice level" data-book-taxonomy-spice>
				<?php foreach ($spice_profiles as $level => $profile) : ?>
					<button
						type="button"
						class="bbb-account-shelf__spiceChoice sss-tropeFilters__spiceChoice"
						role="radio"
						aria-checked="false"
						data-spice-filter="<?php echo esc_attr((string) $level); ?>"
					>
						<span><?php echo esc_html((string) ($profile['peppers'] ?? '')); ?></span>
						<strong><?php echo esc_html((string) ($profile['label'] ?? '')); ?></strong>
					</button>
				<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
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

function bbb_book_taxonomy_should_show_private_layer(): bool {
	if (function_exists('bbb_reader_has_account_identity') && bbb_reader_has_account_identity()) {
		return false;
	}

	if (!function_exists('bbb_reader_has_account_identity') && is_user_logged_in()) {
		return false;
	}

	return true;
}

function bbb_render_book_taxonomy_private_layer_invite(array $society_layer, string $modifier = ''): string {
	if (!bbb_book_taxonomy_should_show_private_layer()) {
		return '';
	}

	$class = trim('sss-tropeInviteSlot ' . $modifier);

	ob_start();
	?>
	<div class="<?php echo esc_attr($class); ?>">
		<div class="sss-tropeInvite">
			<div class="sss-tropeInvite__kicker sss-lib__societyInviteKicker--<?php echo esc_attr((string) $society_layer['class']); ?>"><?php echo esc_html((string) $society_layer['label']); ?></div>
			<div class="sss-tropeInvite__title">one romance recommendation.<br>every sunday.</div>
			<div class="sss-tropeInvite__sub">morally gray men delivered straight to you</div>
			<a href="<?php echo esc_url((string) $society_layer['url']); ?>" class="sss-tropeInvite__btn" target="_blank" rel="noopener">join the society →</a>
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
	$book_count  = count($book_ids);
	$count_text  = 'shelf' === $kind
		? sprintf('%d %s %s in the society library', $book_count, strtolower($term->name), 1 === $book_count ? 'book' : 'books')
		: sprintf('%d %s shelved under this trope', $book_count, 1 === $book_count ? 'book' : 'books');
	$society_layer = function_exists('bbb_society_private_layer_state') ? bbb_society_private_layer_state() : array('label' => 'the private layer', 'class' => 'private', 'url' => 'https://thesmutandsentimentsociety.substack.com/subscribe', 'cta' => 'join the society');
	$show_private_layer = bbb_book_taxonomy_should_show_private_layer();
	?>
	<section class="sss-lib<?php echo $show_private_layer ? '' : ' sss-lib--reader-account'; ?>" data-sss-lib="public">
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

				<?php if ($show_private_layer) : ?>
					<div class="sss-tropeTop__right">
						<?php echo bbb_render_book_taxonomy_private_layer_invite($society_layer, 'sss-tropeInviteSlot--desktop'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="sss-trope__divider"></div>
			<div class="sss-trope__count"><?php echo esc_html($count_text); ?></div>
			<?php echo bbb_render_book_taxonomy_filters($book_ids, $kind); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

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

			<?php if ($show_private_layer) : ?>
				<?php echo bbb_render_book_taxonomy_private_layer_invite($society_layer, 'sss-tropeInviteSlot--mobile'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
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
	<style>
		.sss-tropeFilters{margin:18px 0 26px;padding:18px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.035);border-radius:18px;}
		.sss-tropeFilters__hint{margin:0 0 14px;color:rgba(255,255,255,.78);font:700 12px/1.4 var(--font-body-family,inherit);letter-spacing:.12em;text-transform:uppercase;}
		.sss-tropeInviteSlot--mobile{display:none;}
		.sss-lib--reader-account .sss-tropeTop{grid-template-columns:1fr;max-width:980px;margin:0 auto 22px;}
		.sss-lib--reader-account .sss-tropeTop__left{max-width:none;}
		.sss-lib--reader-account .sss-trope__header{margin-bottom:12px;text-align:center;}
		.sss-lib--reader-account .sss-trope__divider{margin:24px auto 22px;}
		.sss-lib--reader-account .sss-trope__count{margin-top:0;margin-bottom:34px;}
		.sss-lib--reader-account .sss-trope__count::before{margin-bottom:18px;}
		.sss-lib--reader-account .sss-tropeFilters{margin-top:0;}
		.sss-tropeFilters__controls{display:grid;grid-template-columns:minmax(180px,260px) minmax(0,1fr);gap:18px;align-items:center;}
		.sss-tropeFilters__genre{display:grid;gap:8px;color:rgba(255,255,255,.72);font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;}
		.sss-tropeFilters__genre select{appearance:none;width:100%;height:34px;min-height:34px;max-height:34px;padding:0 32px 0 10px;border:1px solid rgba(255,255,255,.18);border-radius:2px;background:linear-gradient(45deg,transparent 50%,rgba(247,243,238,.7) 50%) right 15px center / 6px 6px no-repeat,linear-gradient(135deg,rgba(247,243,238,.7) 50%,transparent 50%) right 11px center / 6px 6px no-repeat,#0d0d0d;color:#fff;font:700 12px/1 var(--font-body-family,inherit);text-transform:lowercase;letter-spacing:0;}
		.sss-tropeFilters__genre select:focus-visible{border-color:rgba(247,243,238,.45);outline:0;}
		.sss-tropeFilters__spiceWrap{display:grid;min-width:0;}
		.sss-tropeFilters__spice{position:relative;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:0;min-width:0;min-height:70px;padding:9px;border-radius:999px;background:linear-gradient(90deg,#2a2a2a 0%,#5b2824 24%,#9d3c24 48%,#d0602a 72%,#ff8a34 100%);box-shadow:inset 0 0 0 1px rgba(255,255,255,.08),0 18px 42px rgba(0,0,0,.34);overflow:hidden;}
		.sss-tropeFilters__spice::before{content:"";position:absolute;inset:9px;border-radius:999px;background:linear-gradient(180deg,rgba(255,255,255,.12),rgba(0,0,0,.22));pointer-events:none;}
		.sss-tropeFilters__spiceChoice{position:relative;z-index:1;display:flex;flex-direction:column;justify-content:center;align-items:center;min-width:0;min-height:52px;padding:7px 5px;border:0;border-radius:999px;background:transparent;color:rgba(255,255,255,.78);box-shadow:none;text-align:center;transition:transform .18s ease,background .18s ease,color .18s ease,box-shadow .18s ease;}
		.sss-tropeFilters__spiceChoice + .sss-tropeFilters__spiceChoice::before{content:"";position:absolute;top:13px;bottom:13px;left:0;width:1px;background:rgba(0,0,0,.28);}
		.sss-tropeFilters__spiceChoice:hover,.sss-tropeFilters__spiceChoice:focus-visible{transform:translateY(-2px);outline:0;}
		.sss-tropeFilters__spiceChoice span{display:block;min-height:16px;color:#fff;font-size:.9rem;line-height:1.1;white-space:nowrap;}
		.sss-tropeFilters__spiceChoice strong{display:block;margin-top:3px;color:inherit;font-size:.78rem;font-weight:800;letter-spacing:.04em;line-height:1.15;text-transform:lowercase;}
		.sss-tropeFilters__spiceChoice.is-active{background:rgba(0,0,0,.48);color:#fff;box-shadow:inset 0 0 0 2px rgba(255,255,255,.82),0 8px 18px rgba(0,0,0,.22);}
		.sss-lib__book[hidden]{display:none !important;}
		@media (max-width: 780px){
			.sss-lib .sss-tropeTop{gap:0;margin-bottom:12px;}
			.sss-lib .sss-trope__header{margin-bottom:0;}
			.sss-lib .sss-trope__title{margin-bottom:0;}
			.sss-lib .sss-trope__title .bbb-custom-emoji{display:block;margin:8px auto 0;}
			.sss-lib .sss-trope__divider{margin:20px auto 16px;max-width:120px;}
			.sss-lib .sss-trope__count{margin:0 0 24px;}
			.sss-lib .sss-trope__count::before{display:none;}
			.sss-tropeInviteSlot--desktop{display:none;}
			.sss-tropeInviteSlot--mobile{display:block;margin:28px 0;}
			.sss-tropeInviteSlot--mobile .sss-tropeInvite{max-width:none;}
			.sss-tropeFilters{margin:16px 0 22px;padding:14px;border-radius:14px;}
			.sss-tropeFilters__controls{grid-template-columns:1fr;}
			.sss-tropeFilters__spice{grid-template-columns:repeat(5,minmax(42px,1fr));border-radius:24px;}
			.sss-tropeFilters__spice::before{border-radius:18px;}
			.sss-tropeFilters__spiceChoice{border-radius:18px;}
			.sss-tropeFilters__spiceChoice strong{font-size:.74rem;}
		}
	</style>
	<script>
	(() => {
		const filters = document.querySelector('[data-book-taxonomy-filters]');
		if (!filters) return;

		const termFilter = filters.querySelector('[data-book-taxonomy-term]');
		const spiceWrap = filters.querySelector('[data-book-taxonomy-spice]');
		const spiceButtons = Array.from(filters.querySelectorAll('[data-spice-filter]'));
		const cards = Array.from(document.querySelectorAll('.sss-lib__grid--browsePage .sss-lib__book'));
		if (!cards.length) return;

		let activeSpice = '';
		const normalize = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

		const update = () => {
			const activeTerm = termFilter ? termFilter.value : '';
			const filterKind = termFilter ? termFilter.dataset.filterKind : 'genre';
			let visible = 0;

			cards.forEach((card) => {
				const cardGenre = normalize(card.dataset.shelf || '');
				const cardTropes = String(card.dataset.tropes || '').split(',').map(normalize).filter(Boolean);
				const cardSpice = String(parseInt(card.dataset.spice || '0', 10) || 0);
				const termMatch = !activeTerm || ('trope' === filterKind ? cardTropes.includes(activeTerm) : cardGenre === activeTerm);
				const spiceMatch = !activeSpice || cardSpice === activeSpice;
				const isVisible = termMatch && spiceMatch;
				card.hidden = !isVisible;
				if (isVisible) visible += 1;
			});

		};

		if (termFilter) {
			termFilter.addEventListener('change', update);
		}

		if (spiceWrap) {
			spiceWrap.addEventListener('click', (event) => {
				const button = event.target.closest('[data-spice-filter]');
				if (!button) return;

				activeSpice = button.dataset.spiceFilter || '';
				spiceButtons.forEach((item) => {
					const active = item === button;
					item.classList.toggle('is-active', active);
					item.setAttribute('aria-checked', active ? 'true' : 'false');
				});
				update();
			});
		}

		update();
	})();
	</script>
	<?php get_template_part('template-parts/library/library-modal'); ?>
	<?php
}
