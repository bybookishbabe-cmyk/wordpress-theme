<?php
/**
 * SSS Series custom post type.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_series_meta_fields(): array {
	return array(
		array('label' => 'Shopify Handle', 'key' => '_bbb_series_handle', 'type' => 'text', 'readonly' => true),
		array('label' => 'Shopify ID', 'key' => '_bbb_series_shopify_id', 'type' => 'text', 'readonly' => true),
		array('label' => 'Shopify Updated At', 'key' => '_bbb_series_shopify_updated_at', 'type' => 'text', 'readonly' => true),
		array('label' => 'Author', 'key' => '_bbb_series_author', 'type' => 'text'),
		array('label' => 'Books In Series', 'key' => '_bbb_series_books_in_series', 'type' => 'number'),
		array('label' => 'Linked Book Handles', 'key' => '_bbb_series_book_handles', 'type' => 'textarea'),
		array('label' => 'Linked Book IDs', 'key' => '_bbb_series_book_ids', 'type' => 'textarea'),
		array('label' => 'Linked Blog Post ID', 'key' => '_bbb_series_linked_blog_post_id', 'type' => 'number', 'readonly' => true),
		array('label' => 'Linked Blog Post Handle', 'key' => '_bbb_series_linked_blog_post_handle', 'type' => 'text'),
		array('label' => 'Linked Blog Post Title', 'key' => '_bbb_series_linked_blog_post_title', 'type' => 'text', 'readonly' => true),
		array('label' => 'Linked Blog Post Shopify ID', 'key' => '_bbb_series_linked_blog_post_shopify_id', 'type' => 'text', 'readonly' => true),
		array('label' => 'Linked Blog Post URL', 'key' => '_bbb_series_linked_blog_post_url', 'type' => 'url'),
		array('label' => 'Raw Shopify Entry JSON', 'key' => '_bbb_series_shopify_entry_json', 'type' => 'textarea', 'readonly' => true),
	);
}

function bbb_series_book_post_types(): array {
	return array_values(
		array_filter(
			array('bbb_book', 'sss_book'),
			static function (string $post_type): bool {
				return post_type_exists($post_type);
			}
		)
	);
}

function bbb_series_admin_books(): array {
	$post_types = bbb_series_book_post_types();
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

function bbb_series_current_book_ids(WP_Post $post): array {
	$ids = array_filter(array_map('absint', preg_split('/[\s,]+/', (string) get_post_meta($post->ID, '_bbb_series_book_ids', true)) ?: array()));
	if ($ids) {
		return array_values(array_unique($ids));
	}

	$handles = array_filter(array_map('sanitize_title', preg_split('/[\s,]+/', (string) get_post_meta($post->ID, '_bbb_series_book_handles', true)) ?: array()));
	foreach ($handles as $handle) {
		foreach (bbb_series_book_post_types() as $post_type) {
			$book = get_page_by_path($handle, OBJECT, $post_type);
			if ($book instanceof WP_Post) {
				$ids[] = (int) $book->ID;
				break;
			}
		}
	}

	if ($ids) {
		return array_values(array_unique($ids));
	}

	$handle = bbb_series_handle_for_post($post);
	if ('' === $handle) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => bbb_series_book_post_types(),
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_bbb_series_handle',
					'value' => $handle,
				),
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_bbb_series_number',
			'order'          => 'ASC',
		)
	);
}

function bbb_series_handle_for_post(WP_Post $post): string {
	$handle = sanitize_title((string) get_post_meta($post->ID, '_bbb_series_handle', true));
	return '' !== $handle ? $handle : sanitize_title($post->post_name ?: $post->post_title);
}

add_action(
	'init',
	static function (): void {
		register_post_type(
			'sss_series',
			array(
				'labels'       => array(
					'name'          => __('Series', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Series', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-book-alt',
				'supports'     => array('title', 'editor', 'thumbnail', 'custom-fields'),
				'has_archive'  => 'series',
				'rewrite'      => array('slug' => 'series'),
			)
		);

		foreach (bbb_series_meta_fields() as $field) {
			register_post_meta(
				'sss_series',
				$field['key'],
				array(
					'type'              => 'number' === ($field['type'] ?? '') ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'number' === ($field['type'] ?? '') ? 'absint' : 'sanitize_textarea_field',
					'auth_callback'     => static fn(): bool => current_user_can('edit_posts'),
				)
			);
		}
	}
);

function bbb_series_add_meta_box(): void {
	add_meta_box(
		'bbb_series_books',
		__('Books in this series', 'bybookishbabe-shopify-port'),
		'bbb_series_render_books_meta_box',
		'sss_series',
		'normal',
		'high'
	);

	add_meta_box(
		'bbb_series_metafields',
		__('Series Metafields', 'bybookishbabe-shopify-port'),
		'bbb_series_render_meta_box',
		'sss_series',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes_sss_series', 'bbb_series_add_meta_box');

function bbb_series_render_books_meta_box(WP_Post $post): void {
	wp_nonce_field('bbb_save_series_books', 'bbb_series_books_nonce');
	$books        = bbb_series_admin_books();
	$current_ids  = bbb_series_current_book_ids($post);
	$slot_count   = max(12, count($current_ids) + 3);
	$current_ids += array_fill(count($current_ids), $slot_count, 0);
	?>
	<style>
		.bbb-series-books { display: grid; gap: 10px; }
		.bbb-series-books__row { display: grid; grid-template-columns: 54px minmax(0, 1fr); gap: 10px; align-items: center; }
		.bbb-series-books__number { font-weight: 700; color: #646970; }
		.bbb-series-books select { width: 100%; max-width: 620px; }
		.bbb-series-books__help { margin: 8px 0 0; color: #646970; font-size: 12px; }
	</style>
	<div class="bbb-series-books">
		<?php for ($index = 0; $index < $slot_count; $index++) : ?>
			<?php $selected_id = (int) ($current_ids[$index] ?? 0); ?>
			<div class="bbb-series-books__row">
				<div class="bbb-series-books__number"><?php echo esc_html('#' . (string) ($index + 1)); ?></div>
				<select name="bbb_series_book_ids[]">
					<option value="0"><?php esc_html_e('No book selected', 'bybookishbabe-shopify-port'); ?></option>
					<?php foreach ($books as $book) : ?>
						<?php if ($book instanceof WP_Post) : ?>
							<option value="<?php echo esc_attr((string) $book->ID); ?>" <?php selected($selected_id, $book->ID); ?>>
								<?php echo esc_html(get_the_title($book) . ' (' . $book->post_type . ')'); ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endfor; ?>
		<p class="bbb-series-books__help"><?php esc_html_e('Pick books in reading order. Saving updates the series record and each selected book’s series handle and number.', 'bybookishbabe-shopify-port'); ?></p>
	</div>
	<?php
}

function bbb_series_render_meta_box(WP_Post $post): void {
	wp_nonce_field('bbb_save_series_metafields', 'bbb_series_metafields_nonce');
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<?php foreach (bbb_series_meta_fields() as $field) : ?>
				<?php
				$key      = (string) $field['key'];
				$type     = (string) ($field['type'] ?? 'text');
				$value    = (string) get_post_meta($post->ID, $key, true);
				$readonly = !empty($field['readonly']);
				?>
				<tr>
					<th scope="row">
						<label for="<?php echo esc_attr($key); ?>"><?php echo esc_html((string) $field['label']); ?></label>
					</th>
					<td>
						<?php if ('textarea' === $type) : ?>
							<textarea
								id="<?php echo esc_attr($key); ?>"
								name="bbb_series_meta[<?php echo esc_attr($key); ?>]"
								rows="<?php echo '_bbb_series_shopify_entry_json' === $key ? '10' : '4'; ?>"
								class="large-text"
								<?php readonly($readonly); ?>
							><?php echo esc_textarea($value); ?></textarea>
						<?php else : ?>
							<input
								id="<?php echo esc_attr($key); ?>"
								name="bbb_series_meta[<?php echo esc_attr($key); ?>]"
								type="<?php echo esc_attr($type); ?>"
								value="<?php echo esc_attr($value); ?>"
								class="regular-text"
								<?php readonly($readonly); ?>
							>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

function bbb_series_save_meta_box(int $post_id): void {
	if (!isset($_POST['bbb_series_metafields_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_series_metafields_nonce']), 'bbb_save_series_metafields')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$fields = array_column(bbb_series_meta_fields(), null, 'key');
	$raw    = isset($_POST['bbb_series_meta']) && is_array($_POST['bbb_series_meta'])
		? wp_unslash($_POST['bbb_series_meta'])
		: array();

	foreach ($fields as $key => $field) {
		if (!empty($field['readonly'])) {
			continue;
		}

		$value = $raw[$key] ?? '';
		if ('number' === ($field['type'] ?? '')) {
			update_post_meta($post_id, (string) $key, absint($value));
			continue;
		}

		update_post_meta($post_id, (string) $key, sanitize_textarea_field((string) $value));
	}
}
add_action('save_post_sss_series', 'bbb_series_save_meta_box');

function bbb_series_save_books_meta_box(int $post_id): void {
	if (!isset($_POST['bbb_series_books_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_series_books_nonce']), 'bbb_save_series_books')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$post = get_post($post_id);
	if (!$post instanceof WP_Post || 'sss_series' !== $post->post_type) {
		return;
	}

	$raw_ids = isset($_POST['bbb_series_book_ids']) && is_array($_POST['bbb_series_book_ids'])
		? wp_unslash($_POST['bbb_series_book_ids'])
		: array();

	$book_ids = array();
	foreach ($raw_ids as $raw_id) {
		$book_id = absint($raw_id);
		if ($book_id > 0 && in_array(get_post_type($book_id), bbb_series_book_post_types(), true) && !in_array($book_id, $book_ids, true)) {
			$book_ids[] = $book_id;
		}
	}

	$handle = bbb_series_handle_for_post($post);
	$previous_ids = bbb_series_current_book_ids($post);

	$handles = array();
	foreach ($book_ids as $index => $book_id) {
		$book = get_post($book_id);
		if (!$book instanceof WP_Post) {
			continue;
		}

		$handles[] = $book->post_name ?: sanitize_title(get_the_title($book));
		update_post_meta($book_id, '_bbb_series_handle', $handle);
		update_post_meta($book_id, '_bbb_series_number', (string) ($index + 1));

		if (taxonomy_exists('bbb_series')) {
			$term = get_term_by('slug', $handle, 'bbb_series');
			if (!$term instanceof WP_Term) {
				$inserted = wp_insert_term(get_the_title($post), 'bbb_series', array('slug' => $handle));
				if (!is_wp_error($inserted)) {
					$term = get_term((int) $inserted['term_id'], 'bbb_series');
				}
			}
			if ($term instanceof WP_Term) {
				wp_set_object_terms($book_id, (int) $term->term_id, 'bbb_series', false);
			}
		}
	}

	foreach (array_diff($previous_ids, $book_ids) as $removed_id) {
		if ((string) get_post_meta((int) $removed_id, '_bbb_series_handle', true) === $handle) {
			delete_post_meta((int) $removed_id, '_bbb_series_handle');
			delete_post_meta((int) $removed_id, '_bbb_series_number');
			if (taxonomy_exists('bbb_series')) {
				wp_set_object_terms((int) $removed_id, array(), 'bbb_series', false);
			}
		}
	}

	update_post_meta($post_id, '_bbb_series_book_ids', implode("\n", $book_ids));
	update_post_meta($post_id, '_bbb_series_book_handles', implode("\n", $handles));
	update_post_meta($post_id, '_bbb_series_books_in_series', count($book_ids));
}
add_action('save_post_sss_series', 'bbb_series_save_books_meta_box');
