<?php
/**
 * Friendly admin fields for imported BBB books.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_book_admin_fields(): array {
	return array(
		array('label' => 'Author', 'key' => '_bbb_author', 'type' => 'text'),
		array(
			'label'       => 'Book Cover Image',
			'key'         => '_bbb_cover_attachment_id',
			'type'        => 'image',
			'description' => 'Upload or choose a 2:3 cover image from the media library. 1200 x 1800px WebP/JPG is ideal.',
		),
		array('label' => 'Amazon Link', 'key' => '_bbb_amazon_url', 'type' => 'url'),
		array('label' => 'Bookshop Link', 'key' => '_bbb_bookshop_url', 'type' => 'url'),
		array('label' => 'Newsletter URL', 'key' => '_bbb_newsletter_url', 'type' => 'url'),
		array(
			'label'       => 'Moodboard Pin Embed',
			'key'         => '_bbb_moodboard_pin_url',
			'type'        => 'textarea',
			'description' => 'Paste the full Pinterest iframe, the embed URL, or a pinterest.com/pin URL. The book saves the clean embed link automatically.',
		),
		array('label' => 'Featured in Newsletter Date', 'key' => '_bbb_newsletter_date', 'type' => 'date'),
		array('label' => 'Mini Note', 'key' => '_bbb_mini_note', 'type' => 'textarea'),
		array('label' => 'Why I Loved It', 'key' => '_bbb_why', 'type' => 'textarea'),
		array(
			'label'       => 'Verdict',
			'key'         => '_bbb_verdict',
			'type'        => 'textarea',
			'description' => '2-3 sentence reader-facing verdict for the single book page.',
		),
		array(
			'label'       => 'Vibe Description',
			'key'         => '_bbb_vibe_description',
			'type'        => 'textarea',
			'description' => 'Short mood/trope description for the book page.',
		),
		array(
			'label'       => 'Spice',
			'key'         => '_bbb_spice_words',
			'type'        => 'textarea',
			'description' => 'Plain-English heat description for readers and search intent.',
		),
		array(
			'label'       => 'Read This If',
			'key'         => '_bbb_read_this_if',
			'type'        => 'textarea',
			'description' => 'Reader-fit line for who should pick this up.',
		),
		array(
			'label'       => 'Skip This If',
			'key'         => '_bbb_skip_this_if',
			'type'        => 'textarea',
			'description' => 'Reader-fit line for who should pass or wait.',
		),
		array(
			'label'       => 'Content Warnings',
			'key'         => '_bbb_content_warnings',
			'type'        => 'textarea',
			'description' => 'Content notes shown on the single book page.',
		),
		array(
			'label'       => 'Standalone + HEA Line',
			'key'         => '_bbb_standalone_hea',
			'type'        => 'textarea',
			'description' => 'Standalone, cliffhanger, and HEA status line.',
		),
		array('label' => 'Spice Level', 'key' => '_bbb_spice', 'type' => 'number', 'min' => 0, 'max' => 5),
		array('label' => 'Tension Score', 'key' => '_bbb_tension', 'type' => 'number', 'min' => 0, 'max' => 5),
		array('label' => 'Emotional Damage Score', 'key' => '_bbb_damage', 'type' => 'number', 'min' => 0, 'max' => 5),
		array('label' => 'Darkness Level', 'key' => '_bbb_darkness', 'type' => 'number', 'min' => 0, 'max' => 5),
		array('label' => 'Yearning Level', 'key' => '_bbb_yearning', 'type' => 'text'),
		array('label' => 'Book Boyfriend Name', 'key' => '_bbb_boyfriend_name', 'type' => 'text'),
		array('label' => 'Book Boyfriend Type', 'key' => '_bbb_boyfriend_type', 'type' => 'text'),
		array('label' => 'Series Handle', 'key' => '_bbb_series_handle', 'type' => 'text'),
		array('label' => 'Series Number', 'key' => '_bbb_series_number', 'type' => 'text'),
		array('label' => 'Shelf Name', 'key' => '_bbb_shelf_name', 'type' => 'text'),
		array('label' => 'On Kindle Unlimited', 'key' => '_bbb_ku', 'type' => 'checkbox'),
		array('label' => 'Reread Badge', 'key' => '_bbb_reread', 'type' => 'checkbox'),
		array('label' => 'Standalone', 'key' => '_bbb_standalone', 'type' => 'checkbox'),
		array('label' => 'Starter Pack', 'key' => '_bbb_starter_pack', 'type' => 'checkbox'),
		array('label' => 'Top Shelf', 'key' => '_bbb_top_shelf', 'type' => 'checkbox'),
		array('label' => 'Hide From Library', 'key' => '_bbb_hide_from_library', 'type' => 'checkbox'),
		array('label' => 'Private Shelf', 'key' => '_bbb_private_shelf', 'type' => 'checkbox'),
	);
}

function bbb_book_admin_taxonomy_fields(): array {
	return array(
		'bbb_trope'  => array(
			'label'       => __('Tropes', 'bybookishbabe-shopify-port'),
			'placeholder' => __('Select tropes', 'bybookishbabe-shopify-port'),
			'multiple'    => true,
			'description' => __('Hold Command/Ctrl to select more than one.', 'bybookishbabe-shopify-port'),
		),
		'bbb_shelf'  => array(
			'label'       => __('Shelves', 'bybookishbabe-shopify-port'),
			'placeholder' => __('Select shelves', 'bybookishbabe-shopify-port'),
			'multiple'    => true,
			'description' => __('Hold Command/Ctrl to select more than one.', 'bybookishbabe-shopify-port'),
		),
		'bbb_series' => array(
			'label'       => __('Series', 'bybookishbabe-shopify-port'),
			'placeholder' => __('No series', 'bybookishbabe-shopify-port'),
			'multiple'    => false,
			'description' => __('Choose an existing series term. Leave blank for standalone books.', 'bybookishbabe-shopify-port'),
		),
	);
}

function bbb_normalize_moodboard_pin_url(string $value): string {
	$value = trim(html_entity_decode($value, ENT_QUOTES));
	if ('' === $value) {
		return '';
	}

	if (preg_match('/\bsrc=["\']([^"\']+)["\']/i', $value, $matches)) {
		$value = trim(html_entity_decode((string) $matches[1], ENT_QUOTES));
	}

	if (preg_match('/\b(?:id|data-pin-id)=["\']?(\d+)["\']?/i', $value, $matches)) {
		return esc_url_raw('https://assets.pinterest.com/ext/embed.html?id=' . (string) $matches[1]);
	}

	$parts = wp_parse_url($value);
	if (
		is_array($parts)
		&& 'https' === strtolower((string) ($parts['scheme'] ?? ''))
		&& 'assets.pinterest.com' === strtolower((string) ($parts['host'] ?? ''))
		&& '/ext/embed.html' === (string) ($parts['path'] ?? '')
	) {
		parse_str((string) ($parts['query'] ?? ''), $query);
		if (!empty($query['id']) && preg_match('/^\d+$/', (string) $query['id'])) {
			return esc_url_raw('https://assets.pinterest.com/ext/embed.html?id=' . (string) $query['id']);
		}
	}

	if (
		is_array($parts)
		&& 'https' === strtolower((string) ($parts['scheme'] ?? ''))
		&& str_ends_with(strtolower((string) ($parts['host'] ?? '')), 'pinterest.com')
		&& preg_match('#/pin/(\d+)#', (string) ($parts['path'] ?? ''), $matches)
	) {
		return esc_url_raw('https://assets.pinterest.com/ext/embed.html?id=' . (string) $matches[1]);
	}

	if (preg_match('/\b(\d{10,})\b/', $value, $matches)) {
		return esc_url_raw('https://assets.pinterest.com/ext/embed.html?id=' . (string) $matches[1]);
	}

	return '';
}

function bbb_normalize_book_admin_url(string $value): string {
	$value = trim(html_entity_decode($value, ENT_QUOTES));
	if ('' === $value) {
		return '';
	}

	if (
		(strlen($value) >= 2)
		&& (('"' === $value[0] && '"' === substr($value, -1)) || ("'" === $value[0] && "'" === substr($value, -1)))
	) {
		$value = trim(substr($value, 1, -1));
	}

	if (str_starts_with($value, '//')) {
		$value = 'https:' . $value;
	} elseif (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
		$value = 'https://' . ltrim($value, '/');
	}

	return esc_url_raw($value);
}

function bbb_add_book_admin_fields_meta_box(): void {
	add_meta_box(
		'bbb_book_details',
		__('Book Details', 'bybookishbabe-shopify-port'),
		'bbb_render_book_admin_fields_meta_box',
		'bbb_book',
		'normal',
		'high'
	);

	add_meta_box(
		'bbb_book_taxonomy_picks',
		__('Book Categories', 'bybookishbabe-shopify-port'),
		'bbb_render_book_admin_taxonomy_meta_box',
		'bbb_book',
		'side',
		'high'
	);
}
add_action('add_meta_boxes_bbb_book', 'bbb_add_book_admin_fields_meta_box');

function bbb_render_book_admin_fields_meta_box(WP_Post $post): void {
	wp_nonce_field('bbb_save_book_admin_fields', 'bbb_book_admin_fields_nonce');
	?>
	<style>
		.bbb-book-fields { display: grid; gap: 14px; }
		.bbb-book-fields__row { display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 12px; align-items: start; }
		.bbb-book-fields__row label { font-weight: 600; padding-top: 7px; }
		.bbb-book-fields__row input[type="text"],
		.bbb-book-fields__row input[type="url"],
		.bbb-book-fields__row input[type="date"],
		.bbb-book-fields__row input[type="number"],
		.bbb-book-fields__row textarea { width: 100%; }
		.bbb-book-fields__row textarea { min-height: 88px; }
		.bbb-book-fields__help { color: #646970; font-size: 12px; margin: 6px 0 0; }
		.bbb-book-fields__cover { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; }
		.bbb-book-fields__cover-preview {
			width: 120px;
			aspect-ratio: 2 / 3;
			border: 1px solid #c3c4c7;
			background: #f6f7f7;
			object-fit: cover;
		}
		.bbb-book-fields__cover-actions { display: flex; gap: 8px; margin-top: 8px; }
		.bbb-book-fields__select { width: 100%; max-width: 520px; }
		.bbb-book-fields__select[multiple] { min-height: 132px; }
		#bbb_book_taxonomy_picks .bbb-book-fields__row { display: block; margin: 0 0 12px; }
		#bbb_book_taxonomy_picks .bbb-book-fields__row label { display: block; padding: 0 0 5px; }
		#bbb_book_taxonomy_picks .bbb-book-fields__select { max-width: 100%; }
		#bbb_book_taxonomy_picks .bbb-book-fields__select[multiple] { min-height: 104px; }
		.bbb-book-fields__save { margin-top: 12px; padding-top: 12px; border-top: 1px solid #dcdcde; }
		.bbb-book-fields__save .button { width: 100%; text-align: center; }
		.bbb-book-fields__section-title {
			margin: 10px 0 0;
			padding-top: 16px;
			border-top: 1px solid #dcdcde;
			font-size: 13px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #3c434a;
		}
		.bbb-book-fields__checks { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px 16px; }
		.bbb-book-fields__check label { font-weight: 600; }
	</style>
	<div class="bbb-book-fields">
		<?php foreach (bbb_book_admin_fields() as $field) : ?>
			<?php
			$key   = (string) $field['key'];
			$type  = (string) $field['type'];
			$value = get_post_meta($post->ID, $key, true);
			if ('checkbox' === $type) {
				continue;
			}
			?>
			<div class="bbb-book-fields__row">
				<label for="<?php echo esc_attr($key); ?>"><?php echo esc_html((string) $field['label']); ?></label>
				<div>
					<?php if ('textarea' === $type) : ?>
						<textarea id="<?php echo esc_attr($key); ?>" name="bbb_book_fields[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea((string) $value); ?></textarea>
					<?php elseif ('image' === $type) : ?>
						<?php
						$attachment_id = absint($value);
						$preview_url   = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
						?>
						<div class="bbb-book-fields__cover" data-bbb-book-cover-field>
							<img
								class="bbb-book-fields__cover-preview"
								src="<?php echo esc_url((string) $preview_url); ?>"
								alt=""
								data-bbb-book-cover-preview
								<?php if (!$preview_url) : ?>hidden<?php endif; ?>
							>
							<div>
								<input
									id="<?php echo esc_attr($key); ?>"
									name="bbb_book_fields[<?php echo esc_attr($key); ?>]"
									type="hidden"
									value="<?php echo esc_attr((string) $attachment_id); ?>"
									data-bbb-book-cover-id
								>
								<div class="bbb-book-fields__cover-actions">
									<button type="button" class="button" data-bbb-book-cover-pick><?php esc_html_e('Choose cover image', 'bybookishbabe-shopify-port'); ?></button>
									<button type="button" class="button" data-bbb-book-cover-clear <?php disabled(!$attachment_id); ?>><?php esc_html_e('Clear image', 'bybookishbabe-shopify-port'); ?></button>
								</div>
								<?php if (!empty($field['description'])) : ?>
									<p class="bbb-book-fields__help"><?php echo esc_html((string) $field['description']); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php else : ?>
						<input
							id="<?php echo esc_attr($key); ?>"
							name="bbb_book_fields[<?php echo esc_attr($key); ?>]"
							type="<?php echo esc_attr('url' === $type ? 'text' : $type); ?>"
							value="<?php echo esc_attr((string) $value); ?>"
							<?php if ('url' === $type) : ?>inputmode="url" autocomplete="url" placeholder="https://..."<?php endif; ?>
							<?php if (isset($field['min'])) : ?>min="<?php echo esc_attr((string) $field['min']); ?>"<?php endif; ?>
							<?php if (isset($field['max'])) : ?>max="<?php echo esc_attr((string) $field['max']); ?>"<?php endif; ?>
						>
					<?php endif; ?>
					<?php if ('image' !== $type && !empty($field['description'])) : ?>
						<p class="bbb-book-fields__help"><?php echo esc_html((string) $field['description']); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
		<div class="bbb-book-fields__checks">
			<?php foreach (bbb_book_admin_fields() as $field) : ?>
				<?php
				$key  = (string) $field['key'];
				$type = (string) $field['type'];
				if ('checkbox' !== $type) {
					continue;
				}
				?>
				<div class="bbb-book-fields__check">
					<label>
						<input
							name="bbb_book_fields[<?php echo esc_attr($key); ?>]"
							type="checkbox"
							value="1"
							<?php checked('1', (string) get_post_meta($post->ID, $key, true)); ?>
						>
						<?php echo esc_html((string) $field['label']); ?>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<script>
		(function($) {
			var frame;

			$(document).on('click', '[data-bbb-book-cover-pick]', function(e) {
				e.preventDefault();

				var $field = $(this).closest('[data-bbb-book-cover-field]');

				frame = wp.media({
					title: '<?php echo esc_js(__('Choose book cover image', 'bybookishbabe-shopify-port')); ?>',
					button: { text: '<?php echo esc_js(__('Use this cover', 'bybookishbabe-shopify-port')); ?>' },
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					var preview = (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) || '';

					$field.find('[data-bbb-book-cover-id]').val(attachment.id || '');
					$field.find('[data-bbb-book-cover-preview]').attr('src', preview).prop('hidden', !preview);
					$field.find('[data-bbb-book-cover-clear]').prop('disabled', false);
				});

				frame.open();
			});

			$(document).on('click', '[data-bbb-book-cover-clear]', function(e) {
				e.preventDefault();

				var $field = $(this).closest('[data-bbb-book-cover-field]');

				$field.find('[data-bbb-book-cover-id]').val('');
				$field.find('[data-bbb-book-cover-preview]').attr('src', '').prop('hidden', true);
				$(this).prop('disabled', true);
			});
		})(jQuery);
	</script>
	<?php
}

function bbb_render_book_admin_taxonomy_meta_box(WP_Post $post): void {
	$has_terms = false;
	?>
	<div class="bbb-book-fields bbb-book-fields--taxonomies">
		<?php foreach (bbb_book_admin_taxonomy_fields() as $taxonomy => $field) : ?>
			<?php
			$taxonomy = (string) $taxonomy;
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

			if (is_wp_error($terms) || empty($terms)) {
				continue;
			}

			$has_terms    = true;
			$selected_ids = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'ids'));
			$selected_ids = is_wp_error($selected_ids) ? array() : array_map('intval', (array) $selected_ids);
			$is_multiple  = !empty($field['multiple']);
			$name         = 'bbb_book_taxonomy_fields[' . $taxonomy . ']' . ($is_multiple ? '[]' : '');
			?>
			<div class="bbb-book-fields__row">
				<label for="bbb-book-tax-<?php echo esc_attr($taxonomy); ?>"><?php echo esc_html((string) $field['label']); ?></label>
				<select
					id="bbb-book-tax-<?php echo esc_attr($taxonomy); ?>"
					class="bbb-book-fields__select"
					name="<?php echo esc_attr($name); ?>"
					<?php if ($is_multiple) : ?>multiple<?php endif; ?>
				>
					<?php if (!$is_multiple) : ?>
						<option value=""><?php echo esc_html((string) $field['placeholder']); ?></option>
					<?php endif; ?>
					<?php foreach ($terms as $term) : ?>
						<option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected(in_array((int) $term->term_id, $selected_ids, true)); ?>>
							<?php echo esc_html($term->name); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php if (!empty($field['description'])) : ?>
					<p class="bbb-book-fields__help"><?php echo esc_html((string) $field['description']); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php if (!$has_terms) : ?>
			<p><?php esc_html_e('No existing tropes, shelves, or series found yet.', 'bybookishbabe-shopify-port'); ?></p>
		<?php endif; ?>
		<div class="bbb-book-fields__save">
			<?php submit_button(__('Save Book', 'bybookishbabe-shopify-port'), 'primary', 'save', false); ?>
		</div>
	</div>
	<?php
}

function bbb_enqueue_book_admin_fields_assets(string $hook): void {
	if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
		return;
	}

	$screen = get_current_screen();
	if (!$screen || 'bbb_book' !== $screen->post_type) {
		return;
	}

	wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'bbb_enqueue_book_admin_fields_assets');

function bbb_save_book_admin_fields(int $post_id): void {
	if (!isset($_POST['bbb_book_admin_fields_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_book_admin_fields_nonce']), 'bbb_save_book_admin_fields')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw_fields = isset($_POST['bbb_book_fields']) && is_array($_POST['bbb_book_fields'])
		? wp_unslash($_POST['bbb_book_fields'])
		: array();

	foreach (bbb_book_admin_fields() as $field) {
		$key  = (string) $field['key'];
		$type = (string) $field['type'];

		if ('checkbox' === $type) {
			update_post_meta($post_id, $key, isset($raw_fields[$key]) ? '1' : '0');
			continue;
		}

		$value = isset($raw_fields[$key]) && is_scalar($raw_fields[$key]) ? (string) $raw_fields[$key] : '';

		if ('image' === $type) {
			$attachment_id = absint($value);
			if ($attachment_id && 'attachment' === get_post_type($attachment_id)) {
				update_post_meta($post_id, $key, (string) $attachment_id);
				update_post_meta($post_id, '_thumbnail_id', (string) $attachment_id);
			} else {
				$previous_id = absint(get_post_meta($post_id, $key, true));
				delete_post_meta($post_id, $key);
				if ($previous_id && $previous_id === (int) get_post_thumbnail_id($post_id)) {
					delete_post_meta($post_id, '_thumbnail_id');
				}
			}

			continue;
		}

		if ('_bbb_moodboard_pin_url' === $key) {
			$value = bbb_normalize_moodboard_pin_url($value);
		} elseif ('url' === $type) {
			$value = bbb_normalize_book_admin_url($value);
		} elseif ('textarea' === $type) {
			$value = wp_kses_post($value);
		} elseif ('number' === $type) {
			$value = '' === trim($value) ? '' : (string) absint($value);
		} else {
			$value = sanitize_text_field($value);
		}

		if ('' === $value) {
			delete_post_meta($post_id, $key);
		} else {
			update_post_meta($post_id, $key, $value);
		}
	}

	$raw_taxonomy_fields = isset($_POST['bbb_book_taxonomy_fields']) && is_array($_POST['bbb_book_taxonomy_fields'])
		? wp_unslash($_POST['bbb_book_taxonomy_fields'])
		: array();

	foreach (bbb_book_admin_taxonomy_fields() as $taxonomy => $field) {
		$taxonomy = (string) $taxonomy;
		if (!taxonomy_exists($taxonomy)) {
			continue;
		}

		$is_multiple = !empty($field['multiple']);
		$raw_value   = $raw_taxonomy_fields[$taxonomy] ?? ($is_multiple ? array() : '');
		$term_ids    = array();

		foreach ((array) $raw_value as $term_id) {
			$term_id = absint($term_id);
			if ($term_id && term_exists($term_id, $taxonomy)) {
				$term_ids[] = $term_id;
			}
		}

		$term_ids = array_values(array_unique($term_ids));
		wp_set_object_terms($post_id, $term_ids, $taxonomy, false);

		if ('bbb_series' === $taxonomy) {
			$series_term = $term_ids ? get_term($term_ids[0], 'bbb_series') : null;
			if ($series_term instanceof WP_Term) {
				update_post_meta($post_id, '_bbb_series_handle', $series_term->slug);
			} else {
				delete_post_meta($post_id, '_bbb_series_handle');
			}
		}

		if ('bbb_shelf' === $taxonomy) {
			$shelf_term = $term_ids ? get_term($term_ids[0], 'bbb_shelf') : null;
			if ($shelf_term instanceof WP_Term) {
				update_post_meta($post_id, '_bbb_shelf_name', $shelf_term->name);
				update_post_meta($post_id, '_bbb_shelf_handle', $shelf_term->slug);
			} else {
				delete_post_meta($post_id, '_bbb_shelf_name');
				delete_post_meta($post_id, '_bbb_shelf_handle');
			}
		}
	}
}
add_action('save_post_bbb_book', 'bbb_save_book_admin_fields');
