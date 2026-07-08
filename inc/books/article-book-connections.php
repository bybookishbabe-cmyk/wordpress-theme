<?php
/**
 * Admin UI for connecting blog posts to library books.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_article_book_connection_post_types(): array {
	return array_values(
		array_filter(
			array('bbb_book', 'sss_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);
}

function bbb_article_book_connection_selected_ids(int $post_id): array {
	$stored = get_post_meta($post_id, '_bbb_article_books', true);
	$ids    = array();

	if (is_array($stored)) {
		$ids = array_merge($ids, array_map('absint', $stored));
	}

	for ($index = 1; $index <= 24; $index++) {
		$value = get_post_meta($post_id, '_bbb_article_book_' . $index, true);
		if ($value) {
			$ids[] = absint($value);
		}
	}

	foreach (array('book', 'books', 'library_book', 'library_books', 'featured_books', 'article_books') as $field) {
		$value = get_post_meta($post_id, $field, true);
		if (is_array($value)) {
			foreach ($value as $item) {
				if (is_numeric($item)) {
					$ids[] = absint($item);
				} elseif (is_array($item) && isset($item['ID'])) {
					$ids[] = absint($item['ID']);
				}
			}
		} elseif (is_numeric($value)) {
			$ids[] = absint($value);
		}
	}

	return array_values(array_unique(array_filter($ids)));
}

function bbb_article_book_connections_write_ids(int $post_id, array $ids): void {
	$ids = array_values(array_unique(array_filter(array_map('absint', $ids))));

	if ($ids) {
		update_post_meta($post_id, '_bbb_article_books', $ids);
	} else {
		delete_post_meta($post_id, '_bbb_article_books');
	}

	for ($index = 1; $index <= 24; $index++) {
		if (isset($ids[$index - 1])) {
			update_post_meta($post_id, '_bbb_article_book_' . $index, $ids[$index - 1]);
		} else {
			delete_post_meta($post_id, '_bbb_article_book_' . $index);
		}
	}
}

function bbb_article_auto_link_defaults(): array {
	return array(
		'faq_books'      => true,
		'faq_series'     => false,
		'auto_connect'   => true,
		'library_context' => true,
	);
}

function bbb_article_auto_link_setting(int $post_id, string $key): bool {
	$defaults = bbb_article_auto_link_defaults();
	if (!array_key_exists($key, $defaults)) {
		return false;
	}

	$value = get_post_meta($post_id, '_bbb_auto_link_' . $key, true);
	if ('' === $value || null === $value) {
		return (bool) $defaults[$key];
	}

	return '1' === (string) $value;
}

function bbb_article_auto_link_write_settings(int $post_id, array $settings): void {
	foreach (bbb_article_auto_link_defaults() as $key => $default) {
		$value = !empty($settings[$key]) ? '1' : '0';
		update_post_meta($post_id, '_bbb_auto_link_' . $key, $value);
	}
}

function bbb_article_auto_link_series_key(array $match): string {
	return sanitize_title((string) ($match['title'] ?? ''));
}

function bbb_article_auto_link_allowed_series_keys(int $post_id): array {
	$stored = get_post_meta($post_id, '_bbb_auto_link_series_allowed', true);
	if (!is_array($stored)) {
		return array();
	}

	return array_values(array_unique(array_filter(array_map('sanitize_title', $stored))));
}

function bbb_article_auto_link_write_allowed_series_keys(int $post_id, array $keys): void {
	$keys = array_values(array_unique(array_filter(array_map('sanitize_title', $keys))));
	if ($keys) {
		update_post_meta($post_id, '_bbb_auto_link_series_allowed', $keys);
	} else {
		delete_post_meta($post_id, '_bbb_auto_link_series_allowed');
	}
}

function bbb_article_auto_link_detected_series_links(WP_Post $post): array {
	if (!function_exists('sss_faq_series_title_matches')) {
		return array();
	}

	$text = wp_strip_all_tags(
		implode(
			"\n",
			array(
				get_the_title($post),
				(string) $post->post_excerpt,
				(string) $post->post_content,
			)
		)
	);
	if ('' === trim($text)) {
		return array();
	}

	$detected = array();
	foreach (sss_faq_series_title_matches() as $match) {
		if (empty($match['title']) || empty($match['pattern']) || !preg_match((string) $match['pattern'], $text, $found)) {
			continue;
		}

		$key = bbb_article_auto_link_series_key($match);
		if ('' === $key) {
			continue;
		}

		$detected[$key] = array(
			'key'      => $key,
			'title'    => (string) $match['title'],
			'detected' => (string) ($found[1] ?? $match['title']),
			'url'      => (string) ($match['url'] ?? ''),
		);
	}

	return array_values($detected);
}

function bbb_article_auto_link_series_match_allowed(int $post_id, array $match): bool {
	$key = bbb_article_auto_link_series_key($match);
	if ('' === $key) {
		return false;
	}

	return in_array($key, bbb_article_auto_link_allowed_series_keys($post_id), true);
}

function bbb_article_book_connections_normalize_match_text(string $value): string {
	if (function_exists('sss_article_match_text')) {
		return sss_article_match_text($value);
	}

	$value = strtolower(wp_strip_all_tags($value));
	$value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

	return trim($value);
}

function bbb_article_book_connection_book_from_token(string $token): ?WP_Post {
	$token = trim(wp_strip_all_tags($token));
	if ('' === $token) {
		return null;
	}

	$post_types = bbb_article_book_connection_post_types();
	if (!$post_types) {
		return null;
	}

	if (is_numeric($token)) {
		$post = get_post(absint($token));
		return $post instanceof WP_Post && in_array($post->post_type, $post_types, true) ? $post : null;
	}

	if (function_exists('sss_article_book_from_name')) {
		$book = sss_article_book_from_name($token);
		if ($book instanceof WP_Post && in_array($book->post_type, $post_types, true)) {
			return $book;
		}
	}

	$slug = sanitize_title($token);
	if ('' !== $slug) {
		$book = get_page_by_path($slug, OBJECT, $post_types);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	$needle = bbb_article_book_connections_normalize_match_text($token);
	if ('' === $needle) {
		return null;
	}

	foreach (bbb_article_book_connection_books() as $book) {
		$title = bbb_article_book_connections_normalize_match_text(get_the_title($book));
		$slug  = bbb_article_book_connections_normalize_match_text((string) get_post_field('post_name', $book->ID));
		if ($needle === $title || $needle === $slug) {
			return $book;
		}
	}

	return null;
}

function bbb_article_book_connections_inferred_ids_for_post(WP_Post $post): array {
	$ids     = array();
	$content = (string) $post->post_content;

	if (preg_match_all('/\[(?:book|bookreview):([^\]\r\n]+)\]/i', $content, $matches)) {
		foreach ($matches[1] as $token) {
			$book = bbb_article_book_connection_book_from_token((string) $token);
			if ($book instanceof WP_Post) {
				$ids[] = (int) $book->ID;
			}
		}
	}

	if (!$ids && bbb_article_book_connections_is_review_post($post)) {
		$title = wp_strip_all_tags(get_the_title($post));
		$title = preg_replace('/\s+review\b.*$/i', '', $title) ?? $title;
		$book  = bbb_article_book_connection_book_from_token($title);
		if ($book instanceof WP_Post) {
			$ids[] = (int) $book->ID;
		}
	}

	return array_values(array_unique(array_filter($ids)));
}

function bbb_article_book_connection_books(): array {
	$post_types = bbb_article_book_connection_post_types();
	if (!$post_types) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
}

function bbb_article_book_connection_source_options(): array {
	$options = array();

	foreach (
		array(
			'trope'  => array('label' => __('Tropes', 'bybookishbabe-shopify-port'), 'taxonomies' => array('bbb_trope', 'sss_trope')),
			'shelf'  => array('label' => __('Shelves / genres', 'bybookishbabe-shopify-port'), 'taxonomies' => array('bbb_shelf', 'sss_shelf')),
			'series' => array('label' => __('Series', 'bybookishbabe-shopify-port'), 'taxonomies' => array('bbb_series')),
		) as $source => $config
	) {
		$terms = array();
		foreach ($config['taxonomies'] as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$found = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);
			if (is_wp_error($found)) {
				continue;
			}

			foreach ($found as $term) {
				if ($term instanceof WP_Term) {
					$terms[$term->slug] = $term->name;
				}
			}
		}

		if ($terms) {
			asort($terms, SORT_NATURAL | SORT_FLAG_CASE);
			$options[$source] = array(
				'label' => (string) $config['label'],
				'terms' => $terms,
			);
		}
	}

	if (post_type_exists('sss_series')) {
		$series_posts = get_posts(
			array(
				'post_type'      => 'sss_series',
				'post_status'    => array('publish', 'draft', 'pending', 'private'),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ($series_posts as $series) {
			$handle = sanitize_title((string) get_post_meta($series->ID, '_bbb_series_handle', true));
			$handle = '' !== $handle ? $handle : $series->post_name;
			if ('' === $handle) {
				continue;
			}

			$options['series']['label'] = __('Series', 'bybookishbabe-shopify-port');
			$options['series']['terms'][$handle] = get_the_title($series);
		}

		if (!empty($options['series']['terms'])) {
			asort($options['series']['terms'], SORT_NATURAL | SORT_FLAG_CASE);
		}
	}

	return $options;
}

function bbb_article_book_connections_add_meta_boxes(): void {
	add_meta_box(
		'bbb_article_books',
		__('Books used in this blog post', 'bybookishbabe-shopify-port'),
		'bbb_article_book_connections_render_post_box',
		'post',
		'side',
		'default'
	);

	add_meta_box(
		'bbb_article_auto_links',
		__('Auto links in post', 'bybookishbabe-shopify-port'),
		'bbb_article_auto_links_render_post_box',
		'post',
		'side',
		'default'
	);

	foreach (bbb_article_book_connection_post_types() as $post_type) {
		add_meta_box(
			'bbb_book_posts',
			__('Blog posts using this book', 'bybookishbabe-shopify-port'),
			'bbb_article_book_connections_render_book_box',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action('add_meta_boxes', 'bbb_article_book_connections_add_meta_boxes');

function bbb_article_book_connections_render_post_box(WP_Post $post): void {
	$selected       = bbb_article_book_connection_selected_ids((int) $post->ID);
	$books          = bbb_article_book_connection_books();
	$source         = (string) get_post_meta($post->ID, '_bbb_article_book_source', true);
	$source_value   = (string) get_post_meta($post->ID, '_bbb_article_book_source_value', true);
	$source_ref     = $source && $source_value ? $source . ':' . $source_value : 'manual';
	$source_limit   = (int) get_post_meta($post->ID, '_bbb_article_book_source_limit', true);
	$source_options = bbb_article_book_connection_source_options();
	if ($source_limit < 1) {
		$source_limit = 24;
	}

	wp_nonce_field('bbb_article_books_save', 'bbb_article_books_nonce');
	?>
	<p style="margin-top:0;">
		<?php esc_html_e('Choose books for [bookcard], [book:1], [quickstats:1], [pillar], and related blog tokens. You can also use [book:insatiable] to render a book by name.', 'bybookishbabe-shopify-port'); ?>
	</p>
	<p>
		<label for="bbb_article_book_source"><strong><?php esc_html_e('Bookcard source', 'bybookishbabe-shopify-port'); ?></strong></label>
		<select id="bbb_article_book_source" name="bbb_article_book_source" style="width:100%;">
			<option value="manual" <?php selected('manual', $source_ref); ?>>
				<?php esc_html_e('Manual selected books below', 'bybookishbabe-shopify-port'); ?>
			</option>
			<?php foreach ($source_options as $source_key => $group) : ?>
				<optgroup label="<?php echo esc_attr((string) $group['label']); ?>">
					<?php foreach ($group['terms'] as $term_slug => $term_name) : ?>
						<?php $option_value = $source_key . ':' . $term_slug; ?>
						<option value="<?php echo esc_attr($option_value); ?>" <?php selected($option_value, $source_ref); ?>>
							<?php echo esc_html((string) $term_name); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="bbb_article_book_source_limit"><strong><?php esc_html_e('Limit', 'bybookishbabe-shopify-port'); ?></strong></label>
		<input id="bbb_article_book_source_limit" name="bbb_article_book_source_limit" type="number" min="1" max="48" value="<?php echo esc_attr((string) $source_limit); ?>" style="width:80px;">
	</p>
	<p class="description">
		<?php esc_html_e('Use manual for hand-picked books, or choose a trope, shelf/genre, or series to let [bookcard] pull matching books automatically. If you choose a series, [series] will use that same source.', 'bybookishbabe-shopify-port'); ?>
	</p>
	<select name="bbb_article_books[]" multiple size="9" style="width:100%;">
		<?php foreach ($books as $book) : ?>
			<option value="<?php echo esc_attr((string) $book->ID); ?>" <?php selected(in_array((int) $book->ID, $selected, true)); ?>>
				<?php echo esc_html(get_the_title($book) . ' (' . $book->post_type . ')'); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e('Hold Command/Ctrl to select more than one. The order here controls [book:1], [book:2], etc. Direct name tokens like [book:insatiable] ignore this order.', 'bybookishbabe-shopify-port'); ?>
	</p>
	<?php
}

function bbb_article_auto_links_render_post_box(WP_Post $post): void {
	$options = array(
		'faq_books'       => array(
			'label' => __('Link book titles in FAQ answers', 'bybookishbabe-shopify-port'),
			'help'  => __('Turns exact book titles in FAQ answers into book links.', 'bybookishbabe-shopify-port'),
		),
		'auto_connect'    => array(
			'label' => __('Auto-connect books from review titles and [book:] tokens', 'bybookishbabe-shopify-port'),
			'help'  => __('Adds detected books to the Books used box when the post saves.', 'bybookishbabe-shopify-port'),
		),
		'library_context' => array(
			'label' => __('Let [library] use this post’s connected books', 'bybookishbabe-shopify-port'),
			'help'  => __('Keeps the blog library strip contextual for this post.', 'bybookishbabe-shopify-port'),
		),
	);
	$detected_series = bbb_article_auto_link_detected_series_links($post);
	$allowed_series  = bbb_article_auto_link_allowed_series_keys((int) $post->ID);

	wp_nonce_field('bbb_article_auto_links_save', 'bbb_article_auto_links_nonce');
	?>
	<style>
		#bbb_article_auto_links .bbb-auto-links__intro {
			margin-top: 0;
		}
		#bbb_article_auto_links .bbb-auto-links__row {
			display: grid;
			grid-template-columns: 32px minmax(0, 1fr);
			gap: 8px;
			align-items: start;
			margin: 0 0 14px;
		}
		#bbb_article_auto_links .bbb-auto-links__input {
			position: absolute;
			opacity: 0;
			pointer-events: none;
		}
		#bbb_article_auto_links .bbb-auto-links__mark {
			display: grid;
			place-items: center;
			width: 28px;
			height: 28px;
			border: 2px solid #d9a1bd;
			border-radius: 8px;
			background: #fdf2f8;
			color: transparent;
			box-shadow: inset 0 0 0 3px #2b1722;
			font-size: 18px;
			font-weight: 800;
			line-height: 1;
		}
		#bbb_article_auto_links .bbb-auto-links__input:checked + .bbb-auto-links__mark {
			border-color: #ff8fca;
			background: #ff8fca;
			color: #1a0f16;
			box-shadow: 0 0 0 2px rgba(255, 143, 202, 0.22);
		}
		#bbb_article_auto_links .bbb-auto-links__input:focus + .bbb-auto-links__mark {
			outline: 2px solid #fff;
			outline-offset: 2px;
		}
		#bbb_article_auto_links .bbb-auto-links__text {
			display: block;
			min-width: 0;
		}
		#bbb_article_auto_links .bbb-auto-links__state {
			display: inline-block;
			margin-left: 4px;
			padding: 1px 6px;
			border-radius: 999px;
			background: #4b263a;
			color: #fff;
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			vertical-align: 1px;
		}
		#bbb_article_auto_links .bbb-auto-links__input:checked ~ .bbb-auto-links__text .bbb-auto-links__state::before {
			content: "On";
		}
		#bbb_article_auto_links .bbb-auto-links__input:not(:checked) ~ .bbb-auto-links__text .bbb-auto-links__state::before {
			content: "Off";
		}
		#bbb_article_auto_links .bbb-auto-links__help {
			display: block;
			margin: 4px 0 0;
		}
	</style>
	<p class="bbb-auto-links__intro">
		<?php esc_html_e('Choose which automatic links this post is allowed to create.', 'bybookishbabe-shopify-port'); ?>
	</p>
	<?php foreach ($options as $key => $option) : ?>
		<input type="hidden" name="bbb_article_auto_links[<?php echo esc_attr($key); ?>]" value="0">
		<label class="bbb-auto-links__row">
			<input class="bbb-auto-links__input" type="checkbox" name="bbb_article_auto_links[<?php echo esc_attr($key); ?>]" value="1" <?php checked(bbb_article_auto_link_setting((int) $post->ID, (string) $key)); ?>>
			<span class="bbb-auto-links__mark" aria-hidden="true">✓</span>
			<span class="bbb-auto-links__text">
				<strong><?php echo esc_html((string) $option['label']); ?></strong>
				<span class="bbb-auto-links__state" aria-hidden="true"></span>
				<span class="description bbb-auto-links__help">
				<?php echo esc_html((string) $option['help']); ?>
				</span>
			</span>
		</label>
	<?php endforeach; ?>
	<hr>
	<p style="margin:0 0 8px;">
		<strong><?php esc_html_e('Detected series links', 'bybookishbabe-shopify-port'); ?></strong>
		<span class="description" style="display:block;margin-top:4px;">
			<?php esc_html_e('Check only the exact series phrases you want linked in this post.', 'bybookishbabe-shopify-port'); ?>
		</span>
	</p>
	<?php if ($detected_series) : ?>
		<?php foreach ($detected_series as $series) : ?>
			<label class="bbb-auto-links__row">
				<input class="bbb-auto-links__input" type="checkbox" name="bbb_article_auto_link_series_allowed[]" value="<?php echo esc_attr((string) $series['key']); ?>" <?php checked(in_array((string) $series['key'], $allowed_series, true)); ?>>
				<span class="bbb-auto-links__mark" aria-hidden="true">✓</span>
				<span class="bbb-auto-links__text">
					<strong><?php echo esc_html((string) $series['detected']); ?></strong>
					<span class="bbb-auto-links__state" aria-hidden="true"></span>
					<span class="description bbb-auto-links__help">
					<?php
					printf(
						/* translators: %s: series title. */
						esc_html__('links to series: %s', 'bybookishbabe-shopify-port'),
						esc_html((string) $series['title'])
					);
					?>
					</span>
				</span>
			</label>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="description" style="margin:0;">
			<?php esc_html_e('No series phrases detected in this post yet.', 'bybookishbabe-shopify-port'); ?>
		</p>
	<?php endif; ?>
	<?php
}

function bbb_article_book_connections_save_post(int $post_id): void {
	if (!isset($_POST['bbb_article_books_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_article_books_nonce']), 'bbb_article_books_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw_ids = isset($_POST['bbb_article_books']) && is_array($_POST['bbb_article_books'])
		? wp_unslash($_POST['bbb_article_books'])
		: array();
	$ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));

	$source_ref = isset($_POST['bbb_article_book_source']) && is_scalar($_POST['bbb_article_book_source'])
		? sanitize_text_field((string) wp_unslash($_POST['bbb_article_book_source']))
		: 'manual';
	$source_limit = isset($_POST['bbb_article_book_source_limit']) && is_scalar($_POST['bbb_article_book_source_limit'])
		? max(1, min(48, absint(wp_unslash($_POST['bbb_article_book_source_limit']))))
		: 24;

	if ('manual' !== $source_ref && preg_match('/^(trope|shelf|series):([a-z0-9_-]+)$/', $source_ref, $matches)) {
		update_post_meta($post_id, '_bbb_article_book_source', $matches[1]);
		update_post_meta($post_id, '_bbb_article_book_source_value', $matches[2]);
		update_post_meta($post_id, '_bbb_article_book_source_limit', $source_limit);
	} else {
		delete_post_meta($post_id, '_bbb_article_book_source');
		delete_post_meta($post_id, '_bbb_article_book_source_value');
		delete_post_meta($post_id, '_bbb_article_book_source_limit');
	}

	bbb_article_book_connections_write_ids($post_id, $ids);
}
add_action('save_post_post', 'bbb_article_book_connections_save_post');

function bbb_article_auto_links_save_post(int $post_id): void {
	if (!isset($_POST['bbb_article_auto_links_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_article_auto_links_nonce']), 'bbb_article_auto_links_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw_settings = isset($_POST['bbb_article_auto_links']) && is_array($_POST['bbb_article_auto_links'])
		? wp_unslash($_POST['bbb_article_auto_links'])
		: array();
	$raw_series = isset($_POST['bbb_article_auto_link_series_allowed']) && is_array($_POST['bbb_article_auto_link_series_allowed'])
		? wp_unslash($_POST['bbb_article_auto_link_series_allowed'])
		: array();

	bbb_article_auto_link_write_settings($post_id, is_array($raw_settings) ? $raw_settings : array());
	bbb_article_auto_link_write_allowed_series_keys($post_id, is_array($raw_series) ? $raw_series : array());
}
add_action('save_post_post', 'bbb_article_auto_links_save_post');

function bbb_article_book_connections_autolink_post(int $post_id, WP_Post $post, bool $update): void {
	unset($update);

	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
		return;
	}

	if ('post' !== $post->post_type) {
		return;
	}

	if (!bbb_article_auto_link_setting($post_id, 'auto_connect')) {
		return;
	}

	$inferred_ids = bbb_article_book_connections_inferred_ids_for_post($post);
	if (!$inferred_ids) {
		return;
	}

	$existing_ids = bbb_article_book_connection_selected_ids($post_id);
	$merged_ids   = array_values(array_unique(array_merge($existing_ids, $inferred_ids)));
	if ($merged_ids === $existing_ids) {
		return;
	}

	bbb_article_book_connections_write_ids($post_id, $merged_ids);
}
add_action('save_post_post', 'bbb_article_book_connections_autolink_post', 20, 3);

function bbb_article_book_connections_posts_for_book(int $book_id): array {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	return array_values(
		array_filter(
			$posts,
			static fn(WP_Post $post): bool => in_array($book_id, bbb_article_book_connection_selected_ids((int) $post->ID), true)
		)
	);
}

function bbb_article_book_connections_is_review_post(WP_Post $post): bool {
	$review_handle = strtolower(trim((string) get_post_meta($post->ID, '_shopify_blog_handle', true)));
	$review_terms = taxonomy_exists('book_review_category') ? get_the_terms($post, 'book_review_category') : false;
	$review_title_slug = strtolower((string) $post->post_name . ' ' . get_the_title($post));

	return has_category('book-reviews', $post)
		|| 'book-reviews' === $review_handle
		|| ($review_terms && !is_wp_error($review_terms))
		|| str_contains($review_title_slug, 'review');
}

function bbb_article_book_connections_review_post_for_book(int $book_id): ?WP_Post {
	foreach (bbb_article_book_connections_posts_for_book($book_id) as $review_candidate) {
		if ($review_candidate instanceof WP_Post && 'publish' === $review_candidate->post_status && bbb_article_book_connections_is_review_post($review_candidate)) {
			return $review_candidate;
		}
	}

	$book = get_post($book_id);
	if (!$book instanceof WP_Post) {
		return null;
	}

	$book_title = trim((string) get_the_title($book));
	$book_slug  = trim((string) $book->post_name);
	$candidates = array();

	if ('' !== $book_slug) {
		$slug_candidate = get_page_by_path($book_slug . '-review', OBJECT, 'post');
		if ($slug_candidate instanceof WP_Post) {
			$candidates[$slug_candidate->ID] = $slug_candidate;
		}
	}

	if ('' !== $book_title) {
		foreach (
			get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 10,
					's'              => $book_title . ' review',
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			) as $search_candidate
		) {
			if ($search_candidate instanceof WP_Post) {
				$candidates[$search_candidate->ID] = $search_candidate;
			}
		}
	}

	foreach ($candidates as $candidate) {
		if (!$candidate instanceof WP_Post || !bbb_article_book_connections_is_review_post($candidate)) {
			continue;
		}

		$candidate_text = strtolower((string) $candidate->post_name . ' ' . get_the_title($candidate) . ' ' . wp_strip_all_tags($candidate->post_content));
		$title_match    = '' !== $book_title && str_contains($candidate_text, strtolower($book_title));
		$slug_match     = '' !== $book_slug && str_contains($candidate_text, strtolower(str_replace('-', ' ', $book_slug)));

		if ($title_match || $slug_match) {
			return $candidate;
		}
	}

	return null;
}

function bbb_article_book_connections_render_book_box(WP_Post $book): void {
	$posts = bbb_article_book_connections_posts_for_book((int) $book->ID);

	if (!$posts) {
		?>
		<p style="margin-top:0;">
			<?php esc_html_e('No blog posts are explicitly connected to this book yet.', 'bybookishbabe-shopify-port'); ?>
		</p>
		<p class="description">
			<?php esc_html_e('Edit a blog post and use the "Books used in this blog post" box to connect it.', 'bybookishbabe-shopify-port'); ?>
		</p>
		<?php
		return;
	}
	?>
	<ul style="margin:0;">
		<?php foreach ($posts as $post) : ?>
			<li>
				<a href="<?php echo esc_url(get_edit_post_link($post->ID, '')); ?>">
					<?php echo esc_html(get_the_title($post)); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
