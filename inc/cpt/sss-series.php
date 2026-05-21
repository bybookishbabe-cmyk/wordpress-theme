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
		'bbb_series_metafields',
		__('Series Metafields', 'bybookishbabe-shopify-port'),
		'bbb_series_render_meta_box',
		'sss_series',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes_sss_series', 'bbb_series_add_meta_box');

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
