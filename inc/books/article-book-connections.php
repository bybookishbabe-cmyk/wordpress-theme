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
add_action('save_post_post', 'bbb_article_book_connections_save_post');

function bbb_article_book_connections_posts_for_book(int $book_id): array {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => 200,
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
