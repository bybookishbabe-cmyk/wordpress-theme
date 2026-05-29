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
		array('label' => 'Cover URL', 'key' => '_bbb_cover_url', 'type' => 'url'),
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
					<?php if (!empty($field['description'])) : ?>
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
	<?php
}

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
}
add_action('save_post_bbb_book', 'bbb_save_book_admin_fields');
