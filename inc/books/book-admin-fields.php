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
				'label'       => 'Spicy Chapters',
				'key'         => '_bbb_spicy_chapters',
				'type'        => 'textarea',
				'description' => 'One chapter per line. These render on the book page above “tropes in this book.”',
			),
			array(
				'label'       => 'Read This If',
				'key'         => '_bbb_read_this_if',
				'type'        => 'textarea',
				'description' => 'Reader-fit line for who should pick this up.',
		),
		array(
			'label'       => 'Most Like These Books',
			'key'         => '_bbb_most_like_book_ids',
			'type'        => 'book_relationship',
			'description' => 'Manual vibe matches for books that hit the same nerve. These get a strong recommendation boost both ways.',
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
		array(
			'label'       => 'Hide From Public Browsing',
			'key'         => '_bbb_hidden_from_public_browsing',
			'type'        => 'checkbox',
			'description' => 'Direct links still work, but the book stays out of public grids, related sections, search, archives, and SEO indexing.',
		),
		array(
			'label'       => 'Reveal Date',
			'key'         => '_bbb_reveal_date',
			'type'        => 'date',
			'description' => 'Optional. Public browsing unlocks at 10:00 AM Pacific on this date.',
		),
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

function bbb_book_admin_relationship_options(int $current_post_id = 0): array {
	$books = get_posts(
		array(
			'post_type'      => 'bbb_book',
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'exclude'        => $current_post_id > 0 ? array($current_post_id) : array(),
		)
	);

	return array_values(
		array_filter(
			$books,
			static fn($book): bool => $book instanceof WP_Post
		)
	);
}

function bbb_sanitize_book_relationship_ids($value, int $current_post_id = 0): array {
	$ids = array();
	foreach ((array) $value as $item) {
		$id = absint($item);
		if ($id <= 0 || $id === $current_post_id || 'bbb_book' !== get_post_type($id)) {
			continue;
		}
		$ids[] = $id;
	}

	return array_values(array_unique($ids));
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

	add_meta_box(
		'bbb_book_quotes',
		__('Book Quotes', 'bybookishbabe-shopify-port'),
		'bbb_render_book_quotes_meta_box',
		'bbb_book',
		'normal',
		'default'
	);
}
add_action('add_meta_boxes_bbb_book', 'bbb_add_book_admin_fields_meta_box');

function bbb_render_book_admin_fields_meta_box(WP_Post $post): void {
	wp_enqueue_media();
	wp_nonce_field('bbb_save_book_admin_fields', 'bbb_book_admin_fields_nonce');
	$aesthetic_images = array();
	foreach (preg_split('/\r\n|\r|\n/', (string) get_post_meta($post->ID, '_bbb_book_aesthetic_urls', true)) ?: array() as $line) {
		$line = trim((string) $line);
		if ('' === $line) {
			continue;
		}
		$parts = array_map('trim', explode('|', $line, 2));
		$aesthetic_images[] = array(
			'image' => (string) ($parts[0] ?? ''),
			'link'  => (string) ($parts[1] ?? ''),
		);
	}
	for ($slot = count($aesthetic_images); $slot < 3; $slot++) {
		$aesthetic_images[] = array('image' => '', 'link' => '');
	}
	$aesthetic_images = array_slice($aesthetic_images, 0, 3);
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
		.bbb-book-fields__dateControl { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
		.bbb-book-fields__dateControl input[type="date"] { flex: 1 1 220px; min-width: 180px; }
		.bbb-book-fields__dateButton.button {
			flex: 0 0 auto;
			min-height: 34px;
			border-color: #9d5576;
			color: #f7d5e6;
			background: #251820;
		}
		.bbb-book-fields__dateButton.button:hover,
		.bbb-book-fields__dateButton.button:focus {
			border-color: #f2a8cc;
			color: #fff;
			background: #3a2431;
		}
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
		.bbb-book-fields__relationship { width: 100%; max-width: 640px; min-height: 180px; }
		.bbb-book-aesthetic { display: grid; gap: 12px; padding-top: 16px; border-top: 1px solid #dcdcde; }
		.bbb-book-aesthetic__slot { display: grid; grid-template-columns: 112px minmax(0, 1fr); gap: 12px; align-items: start; padding: 12px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
		.bbb-book-aesthetic__preview { display: block; width: 100px; aspect-ratio: 2 / 3; object-fit: cover; border: 1px solid #dcdcde; border-radius: 4px; background: #f6f7f7; }
		.bbb-book-aesthetic__controls { display: grid; gap: 8px; }
		.bbb-book-aesthetic__controls input[type="text"],
		.bbb-book-aesthetic__import input[type="url"],
		.bbb-book-aesthetic__import input[type="password"],
		.bbb-book-aesthetic__import input[type="search"],
		.bbb-book-aesthetic__import select { width: 100%; }
		.bbb-book-aesthetic__buttons,
		.bbb-book-aesthetic__actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
		.bbb-book-aesthetic__import { padding: 12px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; }
		.bbb-book-aesthetic__import summary { cursor: pointer; font-weight: 700; }
		.bbb-book-aesthetic__importFields { display: grid; gap: 10px; margin-top: 12px; }
		.bbb-book-aesthetic__importRow { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px; }
		.bbb-book-aesthetic__status { color: #646970; }
		.bbb-book-aesthetic__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(116px, 1fr)); gap: 10px; margin-top: 8px; max-height: 420px; overflow: auto; }
		.bbb-book-aesthetic__pin { display: grid; gap: 6px; padding: 6px; border: 1px solid #dcdcde; border-radius: 6px; background: #f6f7f7; cursor: pointer; text-align: left; }
		.bbb-book-aesthetic__pin[aria-pressed="true"] { border-color: #d63638; box-shadow: 0 0 0 2px rgba(214, 54, 56, 0.16); }
		.bbb-book-aesthetic__pin img { width: 100%; aspect-ratio: 2 / 3; object-fit: cover; border-radius: 4px; background: #fff; }
		.bbb-book-aesthetic__pin span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }
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
		.bbb-book-fields__check label { display: inline-flex; gap: 8px; align-items: flex-start; position: relative; font-weight: 600; line-height: 1.35; cursor: pointer; }
		.bbb-book-fields__check input[type="checkbox"] {
			position: absolute;
			inset: 0 auto auto 0;
			width: 28px;
			height: 28px;
			margin: 0;
			opacity: 0;
			z-index: 2;
			cursor: pointer;
		}
		.bbb-book-fields__checkBox {
			display: inline-grid;
			place-items: center;
			flex: 0 0 auto;
			width: 28px;
			height: 28px;
			border: 3px solid #9d5576;
			border-radius: 6px;
			background: #251820;
			box-shadow: 0 0 0 1px rgba(242, 168, 204, .28), inset 0 0 0 2px rgba(255, 255, 255, .06);
		}
		.bbb-book-fields__check input[type="checkbox"]:checked + .bbb-book-fields__checkBox {
			border-color: #fff;
			background: #d64f8c;
			box-shadow: 0 0 0 2px #d64f8c, 0 0 0 4px rgba(255, 255, 255, .5);
		}
		.bbb-book-fields__check input[type="checkbox"]:checked + .bbb-book-fields__checkBox::before {
			content: "×";
			color: #fff;
			font-size: 30px;
			font-weight: 900;
			line-height: 1;
			text-align: center;
			text-shadow: 0 1px 2px rgba(0, 0, 0, .35);
		}
		.bbb-book-fields__check input[type="checkbox"]:focus + .bbb-book-fields__checkBox {
			border-color: #f2a8cc;
			box-shadow: 0 0 0 1px #f2a8cc, 0 0 0 3px rgba(214, 79, 140, .28);
		}
		@media (max-width: 782px) {
			.bbb-book-fields__row,
			.bbb-book-aesthetic__importRow { grid-template-columns: 1fr; }
		}
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
					<?php elseif ('book_relationship' === $type) : ?>
						<?php
						$selected_ids = bbb_sanitize_book_relationship_ids(get_post_meta($post->ID, $key, true), $post->ID);
						$options      = bbb_book_admin_relationship_options($post->ID);
						?>
						<select
							id="<?php echo esc_attr($key); ?>"
							class="bbb-book-fields__relationship"
							name="bbb_book_fields[<?php echo esc_attr($key); ?>][]"
							multiple
						>
							<?php foreach ($options as $option_book) : ?>
								<option value="<?php echo esc_attr((string) $option_book->ID); ?>" <?php selected(in_array((int) $option_book->ID, $selected_ids, true)); ?>>
									<?php echo esc_html(get_the_title($option_book) . ' (' . get_post_meta($option_book->ID, '_bbb_author', true) . ')'); ?>
								</option>
							<?php endforeach; ?>
						</select>
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
					<?php elseif ('date' === $type) : ?>
						<div class="bbb-book-fields__dateControl">
							<input
								id="<?php echo esc_attr($key); ?>"
								name="bbb_book_fields[<?php echo esc_attr($key); ?>]"
								type="date"
								value="<?php echo esc_attr((string) $value); ?>"
								data-bbb-book-date-input
							>
							<button type="button" class="button bbb-book-fields__dateButton" data-bbb-book-date-open="<?php echo esc_attr($key); ?>">
								<?php esc_html_e('Calendar', 'bybookishbabe-shopify-port'); ?>
							</button>
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
		<div class="bbb-book-aesthetic" data-bbb-book-aesthetic-field>
			<p>
				<strong><?php esc_html_e('Book aesthetic images', 'bybookishbabe-shopify-port'); ?></strong><br>
				<span class="bbb-book-fields__help"><?php esc_html_e('Add up to three 1000 x 1500 images. Uploaded/site images stay first; Pinterest-sourced images can link to the chosen board or section.', 'bybookishbabe-shopify-port'); ?></span>
			</p>
			<?php foreach ($aesthetic_images as $slot => $image) : ?>
				<?php
				$image_url = (string) ($image['image'] ?? '');
				$link_url  = (string) ($image['link'] ?? '');
				?>
				<div class="bbb-book-aesthetic__slot" data-bbb-book-aesthetic-slot>
					<img class="bbb-book-aesthetic__preview" src="<?php echo esc_url($image_url); ?>" alt="" data-bbb-book-aesthetic-preview <?php echo $image_url ? '' : 'hidden'; ?>>
					<div class="bbb-book-aesthetic__controls">
						<label>
							<?php echo esc_html(sprintf(__('Image %d URL', 'bybookishbabe-shopify-port'), $slot + 1)); ?>
							<input name="bbb_book_aesthetic_images[<?php echo esc_attr((string) $slot); ?>][image]" type="text" value="<?php echo esc_attr($image_url); ?>" placeholder="https://.../image.jpg" data-bbb-book-aesthetic-image>
						</label>
						<label>
							<?php esc_html_e('Click/source URL optional', 'bybookishbabe-shopify-port'); ?>
							<input name="bbb_book_aesthetic_images[<?php echo esc_attr((string) $slot); ?>][link]" type="text" value="<?php echo esc_attr($link_url); ?>" placeholder="https://www.pinterest.com/.../book-section/" data-bbb-book-aesthetic-link>
						</label>
						<div class="bbb-book-aesthetic__buttons">
							<button type="button" class="button" data-bbb-book-aesthetic-pick><?php esc_html_e('Choose/upload image', 'bybookishbabe-shopify-port'); ?></button>
							<button type="button" class="button" data-bbb-book-aesthetic-clear><?php esc_html_e('Clear image', 'bybookishbabe-shopify-port'); ?></button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<details class="bbb-book-aesthetic__import" data-bbb-book-pinterest-import>
				<summary><?php esc_html_e('Import from Pinterest board', 'bybookishbabe-shopify-port'); ?></summary>
				<div class="bbb-book-aesthetic__importFields">
					<div class="bbb-book-aesthetic__importRow">
						<label>
							<?php esc_html_e('Scheduler secret', 'bybookishbabe-shopify-port'); ?>
							<input type="password" autocomplete="off" data-bbb-book-pinterest-secret>
						</label>
						<label>
							<?php esc_html_e('Pinterest board URL', 'bybookishbabe-shopify-port'); ?>
							<input type="url" placeholder="https://www.pinterest.com/bybookishbabe/board-name/" data-bbb-book-pinterest-board-url>
						</label>
					</div>
					<div class="bbb-book-aesthetic__importRow">
						<label>
							<?php esc_html_e('Link selected images to', 'bybookishbabe-shopify-port'); ?>
							<select data-bbb-book-pinterest-section disabled>
								<option value=""><?php esc_html_e('Fetch a board first', 'bybookishbabe-shopify-port'); ?></option>
							</select>
						</label>
						<label>
							<?php esc_html_e('Filter pins', 'bybookishbabe-shopify-port'); ?>
							<input type="search" placeholder="<?php echo esc_attr(get_the_title($post)); ?>" data-bbb-book-pinterest-search disabled>
						</label>
					</div>
					<div class="bbb-book-aesthetic__actions">
						<button type="button" class="button" data-bbb-book-pinterest-fetch><?php esc_html_e('Fetch Pinterest pins', 'bybookishbabe-shopify-port'); ?></button>
						<button type="button" class="button button-primary" data-bbb-book-pinterest-use disabled><?php esc_html_e('Use selected pins', 'bybookishbabe-shopify-port'); ?></button>
						<span class="bbb-book-aesthetic__status" data-bbb-book-pinterest-status></span>
					</div>
					<div class="bbb-book-aesthetic__grid" data-bbb-book-pinterest-grid hidden></div>
				</div>
			</details>
		</div>
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
							<?php checked(function_exists('bbb_truthy') ? bbb_truthy(get_post_meta($post->ID, $key, true)) : '1' === (string) get_post_meta($post->ID, $key, true)); ?>
						>
						<span class="bbb-book-fields__checkBox" aria-hidden="true"></span>
						<?php echo esc_html((string) $field['label']); ?>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<script>
		(function($) {
			var frame;
			var aestheticFrame;
			var bookPinterestState = {
				board: null,
				sections: [],
				pins: [],
				selectedIds: []
			};
			var schedulerPreviewEndpoints = [
				'http://localhost:8787/admin/pinterest-board-preview',
				'http://127.0.0.1:8787/admin/pinterest-board-preview'
			];
			var siteBaseUrl = '<?php echo esc_js(trailingslashit(home_url('/'))); ?>';

			function setBookPinterestStatus(message) {
				$('[data-bbb-book-pinterest-status]').text(message || '');
			}

			function isSiteImageUrl(imageUrl) {
				return String(imageUrl || '').indexOf(siteBaseUrl) === 0;
			}

			function updateAestheticSlot($slot, imageUrl, linkUrl) {
				$slot.find('[data-bbb-book-aesthetic-image]').val(imageUrl || '');
				$slot.find('[data-bbb-book-aesthetic-link]').val(linkUrl || '');
				$slot.find('[data-bbb-book-aesthetic-preview]').attr('src', imageUrl || '').prop('hidden', !imageUrl);
			}

			function currentUploadedLeadImage() {
				var uploaded = null;
				$('[data-bbb-book-aesthetic-slot]').each(function() {
					if (uploaded) {
						return;
					}

					var $slot = $(this);
					var imageUrl = String($slot.find('[data-bbb-book-aesthetic-image]').val() || '').trim();
					var linkUrl = String($slot.find('[data-bbb-book-aesthetic-link]').val() || '').trim();
					if (imageUrl && !linkUrl && isSiteImageUrl(imageUrl)) {
						uploaded = { imageUrl: imageUrl, linkUrl: '' };
					}
				});
				return uploaded;
			}

			function selectedBookPinterestPins() {
				return bookPinterestState.selectedIds
					.map(function(id) {
						return bookPinterestState.pins.find(function(pin) {
							return String(pin.id) === String(id);
						});
					})
					.filter(Boolean);
			}

			function renderBookPinterestSections() {
				var $section = $('[data-bbb-book-pinterest-section]');
				$section.empty();

				if (!bookPinterestState.board) {
					$section.append($('<option>', { value: '', text: '<?php echo esc_js(__('Fetch a board first', 'bybookishbabe-shopify-port')); ?>' }));
					$section.prop('disabled', true);
					return;
				}

				$section.append($('<option>', {
					value: bookPinterestState.board.url || '',
					text: '<?php echo esc_js(__('Board link', 'bybookishbabe-shopify-port')); ?>',
					'data-section-id': ''
				}));

				bookPinterestState.sections.forEach(function(section) {
					$section.append($('<option>', {
						value: section.url || bookPinterestState.board.url || '',
						text: section.name || section.url || '',
						'data-section-id': section.id || ''
					}));
				});

				$section.prop('disabled', false);
			}

			function renderBookPinterestPins() {
				var $grid = $('[data-bbb-book-pinterest-grid]');
				var filter = String($('[data-bbb-book-pinterest-search]').val() || '').trim().toLowerCase();
				var pins = bookPinterestState.pins.filter(function(pin) {
					if (!filter) {
						return true;
					}

					return [pin.title, pin.description, pin.id].join(' ').toLowerCase().indexOf(filter) !== -1;
				});

				$grid.empty().prop('hidden', !pins.length);
				pins.forEach(function(pin) {
					var isSelected = bookPinterestState.selectedIds.indexOf(String(pin.id)) !== -1;
					var label = pin.title || pin.description || pin.id;
					var $button = $('<button>', {
						type: 'button',
						class: 'bbb-book-aesthetic__pin',
						'aria-pressed': isSelected ? 'true' : 'false',
						'data-bbb-book-pinterest-pin': pin.id
					});
					$button.append($('<img>', { src: pin.imageUrl, alt: label }));
					$button.append($('<span>').text(label || '<?php echo esc_js(__('Pinterest pin', 'bybookishbabe-shopify-port')); ?>'));
					$grid.append($button);
				});

				$('[data-bbb-book-pinterest-use]').prop('disabled', !bookPinterestState.selectedIds.length);
				if (bookPinterestState.pins.length && !pins.length) {
					setBookPinterestStatus('<?php echo esc_js(__('No pins match that filter.', 'bybookishbabe-shopify-port')); ?>');
				}
			}

			function fetchBookPinterestPreview(secret, boardUrl, endpointIndex, sectionId) {
				var index = endpointIndex || 0;
				var endpoint = schedulerPreviewEndpoints[index];
				if (!endpoint) {
					return Promise.reject(new Error('<?php echo esc_js(__('Could not reach the local scheduler. Make sure http://localhost:8787 is running, then refresh this editor and try again.', 'bybookishbabe-shopify-port')); ?>'));
				}

				var requestUrl = endpoint + '?secret=' + encodeURIComponent(secret) + '&boardUrl=' + encodeURIComponent(boardUrl);
				if (sectionId) {
					requestUrl += '&sectionId=' + encodeURIComponent(sectionId);
				}

				return fetch(requestUrl, {
					method: 'GET',
					mode: 'cors'
				}).then(function(response) {
					return response.json().then(function(data) {
						if (!response.ok) {
							throw new Error(data && data.error ? data.error : response.statusText);
						}
						return data;
					});
				}).catch(function(error) {
					if (error && error.message && error.message !== 'Failed to fetch') {
						throw error;
					}
					return fetchBookPinterestPreview(secret, boardUrl, index + 1, sectionId);
				});
			}

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

			$(document).on('click', '[data-bbb-book-aesthetic-pick]', function(e) {
				e.preventDefault();

				var $slot = $(this).closest('[data-bbb-book-aesthetic-slot]');
				aestheticFrame = wp.media({
					title: '<?php echo esc_js(__('Choose book aesthetic image', 'bybookishbabe-shopify-port')); ?>',
					button: { text: '<?php echo esc_js(__('Use this image', 'bybookishbabe-shopify-port')); ?>' },
					library: { type: 'image' },
					multiple: false
				});

				aestheticFrame.on('select', function() {
					var attachment = aestheticFrame.state().get('selection').first().toJSON();
					var preview = (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) || '';
					$slot.find('[data-bbb-book-aesthetic-image]').val(attachment.url || '');
					$slot.find('[data-bbb-book-aesthetic-link]').val('');
					$slot.find('[data-bbb-book-aesthetic-preview]').attr('src', preview).prop('hidden', !preview);
				});

				aestheticFrame.open();
			});

			$(document).on('click', '[data-bbb-book-aesthetic-clear]', function(e) {
				e.preventDefault();
				updateAestheticSlot($(this).closest('[data-bbb-book-aesthetic-slot]'), '', '');
			});

			$(document).on('click', '[data-bbb-book-date-open]', function(e) {
				e.preventDefault();
				var targetId = String($(this).attr('data-bbb-book-date-open') || '');
				var input = targetId ? document.getElementById(targetId) : $(this).siblings('[data-bbb-book-date-input]').get(0);
				if (!input) {
					return;
				}

				input.focus();
				if (typeof input.showPicker === 'function') {
					try {
						input.showPicker();
						return;
					} catch (error) {}
				}
				input.click();
			});

			$(document).on('input', '[data-bbb-book-aesthetic-image]', function() {
				var $slot = $(this).closest('[data-bbb-book-aesthetic-slot]');
				var value = $(this).val();
				$slot.find('[data-bbb-book-aesthetic-preview]').attr('src', value).prop('hidden', !value);
			});

			$(document).on('click', '[data-bbb-book-pinterest-fetch]', function(e) {
				e.preventDefault();

				var secret = String($('[data-bbb-book-pinterest-secret]').val() || '').trim();
				var boardUrl = String($('[data-bbb-book-pinterest-board-url]').val() || '').trim();
				if (!secret || !boardUrl) {
					setBookPinterestStatus('<?php echo esc_js(__('Add the scheduler secret and Pinterest board URL first.', 'bybookishbabe-shopify-port')); ?>');
					return;
				}

				var $button = $(this);
				$button.prop('disabled', true);
				setBookPinterestStatus('<?php echo esc_js(__('Fetching Pinterest board...', 'bybookishbabe-shopify-port')); ?>');

				fetchBookPinterestPreview(secret, boardUrl, 0, '')
					.then(function(data) {
						bookPinterestState.board = data.board || null;
						bookPinterestState.sections = Array.isArray(data.sections) ? data.sections : [];
						bookPinterestState.pins = Array.isArray(data.pins) ? data.pins : [];
						bookPinterestState.selectedIds = [];
						$('[data-bbb-book-pinterest-search]').prop('disabled', !bookPinterestState.pins.length);
						renderBookPinterestSections();
						renderBookPinterestPins();
						setBookPinterestStatus(bookPinterestState.pins.length
							? '<?php echo esc_js(__('Choose up to 3 pins, then use selected pins.', 'bybookishbabe-shopify-port')); ?>'
							: '<?php echo esc_js(__('Board loaded, but Pinterest returned no visible pins.', 'bybookishbabe-shopify-port')); ?>'
						);
					})
					.catch(function(error) {
						setBookPinterestStatus(error.message || '<?php echo esc_js(__('Pinterest fetch failed.', 'bybookishbabe-shopify-port')); ?>');
					})
					.finally(function() {
						$button.prop('disabled', false);
					});
			});

			$(document).on('change', '[data-bbb-book-pinterest-section]', function() {
				var secret = String($('[data-bbb-book-pinterest-secret]').val() || '').trim();
				var boardUrl = String($('[data-bbb-book-pinterest-board-url]').val() || '').trim();
				var sectionId = String($(this).find(':selected').attr('data-section-id') || '').trim();
				if (!secret || !boardUrl || !sectionId) {
					return;
				}

				setBookPinterestStatus('<?php echo esc_js(__('Fetching pins for selected section...', 'bybookishbabe-shopify-port')); ?>');
				fetchBookPinterestPreview(secret, boardUrl, 0, sectionId)
					.then(function(data) {
						bookPinterestState.pins = data.sectionPinsUnavailable ? [] : (Array.isArray(data.pins) ? data.pins : []);
						bookPinterestState.selectedIds = [];
						$('[data-bbb-book-pinterest-search]').prop('disabled', !bookPinterestState.pins.length);
						renderBookPinterestPins();
						if (data.sectionPinsUnavailable) {
							setBookPinterestStatus('<?php echo esc_js(__('Pinterest did not expose pins for that section. No board-wide fallback is shown here, so you do not accidentally pick from the wrong section.', 'bybookishbabe-shopify-port')); ?>');
						} else {
							setBookPinterestStatus(bookPinterestState.pins.length
								? '<?php echo esc_js(__('Showing pins from the selected section. Choose up to 3.', 'bybookishbabe-shopify-port')); ?>'
								: '<?php echo esc_js(__('That section returned no visible pins.', 'bybookishbabe-shopify-port')); ?>'
							);
						}
					})
					.catch(function(error) {
						setBookPinterestStatus(error.message || '<?php echo esc_js(__('Pinterest section fetch failed.', 'bybookishbabe-shopify-port')); ?>');
					});
			});

			$(document).on('input', '[data-bbb-book-pinterest-search]', renderBookPinterestPins);

			$(document).on('click', '[data-bbb-book-pinterest-pin]', function(e) {
				e.preventDefault();
				var id = String($(this).data('bbbBookPinterestPin') || $(this).attr('data-bbb-book-pinterest-pin') || '');
				var existing = bookPinterestState.selectedIds.indexOf(id);
				if (existing !== -1) {
					bookPinterestState.selectedIds.splice(existing, 1);
				} else if (bookPinterestState.selectedIds.length < 3) {
					bookPinterestState.selectedIds.push(id);
				} else {
					setBookPinterestStatus('<?php echo esc_js(__('Only 3 carousel images can be selected.', 'bybookishbabe-shopify-port')); ?>');
					return;
				}
				renderBookPinterestPins();
				if (bookPinterestState.selectedIds.length) {
					setBookPinterestStatus(bookPinterestState.selectedIds.length + ' / 3 <?php echo esc_js(__('selected', 'bybookishbabe-shopify-port')); ?>');
				}
			});

			$(document).on('click', '[data-bbb-book-pinterest-use]', function(e) {
				e.preventDefault();
				var pins = selectedBookPinterestPins().slice(0, 3);
				var linkUrl = String($('[data-bbb-book-pinterest-section]').val() || bookPinterestState.board?.url || '').trim();
				var $slots = $('[data-bbb-book-aesthetic-slot]');
				var leadImage = currentUploadedLeadImage();
				var nextImages = leadImage ? [leadImage] : [];

				pins.slice(0, leadImage ? 2 : 3).forEach(function(pin) {
					nextImages.push({ imageUrl: pin.imageUrl, linkUrl: linkUrl });
				});

				$slots.each(function(index) {
					var image = nextImages[index] || null;
					updateAestheticSlot($(this), image ? image.imageUrl : '', image ? image.linkUrl : '');
				});

				setBookPinterestStatus((leadImage ? '<?php echo esc_js(__('Uploaded image kept first. ', 'bybookishbabe-shopify-port')); ?>' : '') + Math.min(pins.length, leadImage ? 2 : 3) + ' <?php echo esc_js(__('pin image(s) added. Save/update the book to keep them.', 'bybookishbabe-shopify-port')); ?>');
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

function bbb_book_admin_linked_quotes(int $book_id): array {
	$quote_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
	if (!$quote_types) {
		return array();
	}

	$book_handle = sanitize_title((string) get_post_field('post_name', $book_id));
	$quote_ids   = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array('key' => '_quote_book_id', 'value' => (string) $book_id),
				array('key' => '_quote_library_book_id', 'value' => (string) $book_id),
				array('key' => 'book_id', 'value' => (string) $book_id),
				array('key' => 'library_book_id', 'value' => (string) $book_id),
				array('key' => '_quote_book_handle', 'value' => $book_handle),
				array('key' => 'book_handle', 'value' => $book_handle),
				array('key' => '_bbb_book_handle', 'value' => $book_handle),
			),
		)
	);

	$quotes = array_filter(
		array_map('get_post', array_map('absint', $quote_ids)),
		static fn($quote): bool => $quote instanceof WP_Post
	);

	$book = get_post($book_id);
	if ($book instanceof WP_Post && function_exists('bbb_bookquote_quote_book_matches')) {
		$quotes = array_filter(
			$quotes,
			static fn($quote): bool => $quote instanceof WP_Post && bbb_bookquote_quote_book_matches($quote, $book)
		);
	}

	return array_values($quotes);
}

function bbb_render_book_quotes_meta_box(WP_Post $post): void {
	wp_nonce_field('bbb_save_book_quotes', 'bbb_book_quotes_nonce');

	$quotes = bbb_book_admin_linked_quotes($post->ID);
	?>
	<style>
		.bbb-book-quotes { display: grid; gap: 14px; }
		.bbb-book-quotes__list { display: grid; gap: 10px; margin: 0; }
		.bbb-book-quotes__item {
			margin: 0;
			padding: 12px;
			border: 1px solid #dcdcde;
			border-radius: 4px;
			background: #fff;
		}
		.bbb-book-quotes__item blockquote { margin: 0 0 8px; font-size: 14px; line-height: 1.45; }
		.bbb-book-quotes__meta { display: flex; flex-wrap: wrap; gap: 8px; color: #646970; font-size: 12px; }
		.bbb-book-quotes textarea { width: 100%; min-height: 130px; }
		.bbb-book-quotes__help { margin: 6px 0 0; color: #646970; font-size: 12px; }
	</style>
	<div class="bbb-book-quotes">
		<?php if ($quotes) : ?>
			<div>
				<p><strong><?php esc_html_e('Linked quotes', 'bybookishbabe-shopify-port'); ?></strong></p>
				<div class="bbb-book-quotes__list">
					<?php foreach ($quotes as $quote) : ?>
						<?php
						$quote_text = function_exists('bbb_bookquote_quote_text')
							? bbb_bookquote_quote_text($quote)
							: trim(wp_strip_all_tags((string) $quote->post_content));
						$status        = (string) get_post_status($quote);
						$status_object = get_post_status_object($status);
						$status_label  = $status_object ? $status_object->label : $status;
						?>
						<div class="bbb-book-quotes__item">
							<blockquote>&ldquo;<?php echo esc_html($quote_text); ?>&rdquo;</blockquote>
							<div class="bbb-book-quotes__meta">
								<span><?php echo esc_html($status_label); ?></span>
								<?php $edit_link = get_edit_post_link($quote->ID); ?>
								<?php if ($edit_link) : ?>
									<a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit quote', 'bybookishbabe-shopify-port'); ?></a>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php else : ?>
			<p><?php esc_html_e('No quotes are linked to this book yet.', 'bybookishbabe-shopify-port'); ?></p>
		<?php endif; ?>

		<div>
			<label for="bbb_book_new_quotes"><strong><?php esc_html_e('Add new quote', 'bybookishbabe-shopify-port'); ?></strong></label>
			<textarea id="bbb_book_new_quotes" name="bbb_book_new_quotes" placeholder="<?php echo esc_attr__('Paste one quote, then save the book. It will appear in Linked quotes.', 'bybookishbabe-shopify-port'); ?>"></textarea>
			<p class="bbb-book-quotes__help"><?php esc_html_e('Saving the book creates one published quote post linked to this book. Add the next quote after this one saves.', 'bybookishbabe-shopify-port'); ?></p>
		</div>
	</div>
	<?php
}

function bbb_book_quote_title_from_text(string $quote_text): string {
	$title = wp_trim_words(wp_strip_all_tags($quote_text), 8, '');
	return '' !== trim($title) ? $title : __('Book quote', 'bybookishbabe-shopify-port');
}

function bbb_book_create_linked_quote(int $book_id, string $quote_text): int {
	$quote_text = trim($quote_text);
	if ('' === $quote_text || 'bbb_book' !== get_post_type($book_id)) {
		return 0;
	}

	$quote_type = post_type_exists('bbb_quote') ? 'bbb_quote' : (post_type_exists('sss_quote') ? 'sss_quote' : '');
	if ('' === $quote_type) {
		return 0;
	}

	$existing_texts = array_map(
		static function (WP_Post $quote): string {
			$text = function_exists('bbb_bookquote_quote_text')
				? bbb_bookquote_quote_text($quote)
				: trim(wp_strip_all_tags((string) $quote->post_content));

			return strtolower(trim(preg_replace('/\s+/', ' ', $text) ?? $text));
		},
		bbb_book_admin_linked_quotes($book_id)
	);
	$normalized_new = strtolower(trim(preg_replace('/\s+/', ' ', $quote_text) ?? $quote_text));
	if (in_array($normalized_new, $existing_texts, true)) {
		return 0;
	}

	$quote_id = wp_insert_post(
		array(
			'post_type'    => $quote_type,
			'post_status'  => 'publish',
			'post_title'   => bbb_book_quote_title_from_text($quote_text),
			'post_content' => $quote_text,
		),
		true
	);

	if (is_wp_error($quote_id) || (int) $quote_id <= 0) {
		return 0;
	}

	$quote_id    = (int) $quote_id;
	$book_handle = (string) get_post_field('post_name', $book_id);
	update_post_meta($quote_id, '_quote_text', $quote_text);
	update_post_meta($quote_id, 'quote_text', $quote_text);
	update_post_meta($quote_id, '_quote_book_id', $book_id);
	update_post_meta($quote_id, '_quote_library_book_id', $book_id);
	update_post_meta($quote_id, 'book_id', $book_id);
	update_post_meta($quote_id, 'library_book_id', $book_id);
	update_post_meta($quote_id, '_quote_book_handle', $book_handle);
	update_post_meta($quote_id, '_bbb_book_handle', $book_handle);
	update_post_meta($quote_id, '_quote_book_title', get_the_title($book_id));

	return $quote_id;
}

function bbb_save_book_quotes(int $post_id): void {
	if (!isset($_POST['bbb_book_quotes_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_book_quotes_nonce']), 'bbb_save_book_quotes')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id) || 'bbb_book' !== get_post_type($post_id)) {
		return;
	}

	$raw_quotes = isset($_POST['bbb_book_new_quotes']) ? (string) wp_unslash($_POST['bbb_book_new_quotes']) : '';
	$raw_quotes = trim($raw_quotes);
	if ('' === $raw_quotes) {
		return;
	}

	$quote_text = sanitize_textarea_field($raw_quotes);
	if ('' !== trim($quote_text)) {
		bbb_book_create_linked_quote($post_id, $quote_text);
	}

	if (function_exists('sss_library_flush_cache')) {
		sss_library_flush_cache();
	}
}
add_action('save_post_bbb_book', 'bbb_save_book_quotes', 20);

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

function bbb_book_label_from_handle(string $handle): string {
	$label = trim(str_replace(array('-', '_'), ' ', sanitize_title($handle)));
	$label = trim((string) preg_replace('/\s+series$/i', '', $label));
	return '' !== $label ? ucwords($label) : __('Series', 'bybookishbabe-shopify-port');
}

function bbb_book_series_label(string $name): string {
	$name = trim($name);
	if ('' === $name) {
		return '';
	}

	return preg_match('/\b(?:series|duet|trilogy|saga)\s*$/i', $name) ? $name : $name . ' series';
}

function bbb_book_ensure_series_term(string $handle, string $name = ''): ?WP_Term {
	$handle = sanitize_title($handle);
	if ('' === $handle || !taxonomy_exists('bbb_series')) {
		return null;
	}

	$term = get_term_by('slug', $handle, 'bbb_series');
	if ($term instanceof WP_Term) {
		return $term;
	}

	$inserted = wp_insert_term(
		'' !== trim($name) ? sanitize_text_field($name) : bbb_book_label_from_handle($handle),
		'bbb_series',
		array('slug' => $handle)
	);

	if (is_wp_error($inserted)) {
		return null;
	}

	$term = get_term((int) $inserted['term_id'], 'bbb_series');
	return $term instanceof WP_Term ? $term : null;
}

function bbb_book_find_series_page_by_handle(string $handle): ?WP_Post {
	$handle = sanitize_title($handle);
	if ('' === $handle || !post_type_exists('sss_series')) {
		return null;
	}

	$page = get_page_by_path($handle, OBJECT, 'sss_series');
	if ($page instanceof WP_Post) {
		return $page;
	}

	$matches = get_posts(
		array(
			'post_type'      => 'sss_series',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_bbb_series_handle',
					'value' => $handle,
				),
			),
		)
	);

	if (empty($matches)) {
		return null;
	}

	$page = get_post((int) $matches[0]);
	return $page instanceof WP_Post ? $page : null;
}

function bbb_book_add_to_series_page(int $series_id, int $book_id): void {
	if ($series_id <= 0 || $book_id <= 0 || 'bbb_book' !== get_post_type($book_id)) {
		return;
	}

	$book_handle = sanitize_title((string) get_post_field('post_name', $book_id));
	$handles     = preg_split('/\R+/', (string) get_post_meta($series_id, '_bbb_series_book_handles', true)) ?: array();
	$ids         = preg_split('/\R+/', (string) get_post_meta($series_id, '_bbb_series_book_ids', true)) ?: array();

	$handles = array_values(array_filter(array_map('sanitize_title', $handles)));
	$ids     = array_values(array_filter(array_map('absint', $ids)));

	if ('' !== $book_handle && !in_array($book_handle, $handles, true)) {
		$handles[] = $book_handle;
		update_post_meta($series_id, '_bbb_series_book_handles', implode("\n", $handles));
	}

	if (!in_array($book_id, $ids, true)) {
		$ids[] = $book_id;
		update_post_meta($series_id, '_bbb_series_book_ids', implode("\n", $ids));
	}

	$book_count = max(count($handles), count($ids), absint(get_post_meta($series_id, '_bbb_series_books_in_series', true)));
	if ($book_count > 0) {
		update_post_meta($series_id, '_bbb_series_books_in_series', (string) $book_count);
	}
}

function bbb_book_ensure_series_page(string $handle, int $book_id = 0, string $title = ''): ?WP_Post {
	$handle = sanitize_title($handle);
	if ('' === $handle || !post_type_exists('sss_series')) {
		return null;
	}

	$page_title = '' !== trim($title) ? sanitize_text_field($title) : bbb_book_label_from_handle($handle);
	$page       = bbb_book_find_series_page_by_handle($handle);

	if ($page instanceof WP_Post) {
		update_post_meta($page->ID, '_bbb_series_handle', $handle);
		if ($page->post_name !== $handle) {
			wp_update_post(
				array(
					'ID'        => $page->ID,
					'post_name' => $handle,
				)
			);
		}
		bbb_book_add_to_series_page($page->ID, $book_id);
		return get_post($page->ID) ?: $page;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'sss_series',
			'post_status'  => 'publish',
			'post_title'   => $page_title,
			'post_name'    => $handle,
			'post_content' => '',
		),
		true
	);

	if (is_wp_error($page_id)) {
		return null;
	}

	update_post_meta((int) $page_id, '_bbb_series_handle', $handle);
	bbb_book_add_to_series_page((int) $page_id, $book_id);

	$page = get_post((int) $page_id);
	return $page instanceof WP_Post ? $page : null;
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

		if ('book_relationship' === $type) {
			$ids = bbb_sanitize_book_relationship_ids($raw_fields[$key] ?? array(), $post_id);
			if ($ids) {
				update_post_meta($post_id, $key, $ids);
			} else {
				delete_post_meta($post_id, $key);
			}
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
			} elseif ('_bbb_spicy_chapters' === $key) {
				$value = sanitize_textarea_field($value);
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

	$aesthetic_uploaded_lines = array();
	$aesthetic_external_lines = array();
	$site_base_url = trailingslashit(home_url('/'));
	$posted_aesthetic_images = isset($_POST['bbb_book_aesthetic_images']) && is_array($_POST['bbb_book_aesthetic_images'])
		? wp_unslash($_POST['bbb_book_aesthetic_images'])
		: array();
	foreach (array_slice($posted_aesthetic_images, 0, 3) as $posted_aesthetic_image) {
		if (!is_array($posted_aesthetic_image)) {
			continue;
		}

		$image_url = esc_url_raw((string) ($posted_aesthetic_image['image'] ?? ''));
		$link_url  = esc_url_raw((string) ($posted_aesthetic_image['link'] ?? ''));
		if ('' === $image_url) {
			continue;
		}

		$aesthetic_line = '' !== $link_url ? $image_url . ' | ' . $link_url : $image_url;
		if ('' === $link_url && 0 === strpos($image_url, $site_base_url)) {
			$aesthetic_uploaded_lines[] = $aesthetic_line;
		} else {
			$aesthetic_external_lines[] = $aesthetic_line;
		}
	}
	$aesthetic_lines = array_slice(array_merge($aesthetic_uploaded_lines, $aesthetic_external_lines), 0, 3);
	if ($aesthetic_lines) {
		update_post_meta($post_id, '_bbb_book_aesthetic_urls', implode("\n", $aesthetic_lines));
	} else {
		delete_post_meta($post_id, '_bbb_book_aesthetic_urls');
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
				bbb_book_ensure_series_page($series_term->slug, $post_id, $series_term->name);
			} else {
				$series_handle = sanitize_title((string) get_post_meta($post_id, '_bbb_series_handle', true));
				if ('' === $series_handle) {
					delete_post_meta($post_id, '_bbb_series_handle');
				} else {
					$created_term = bbb_book_ensure_series_term($series_handle);
					if ($created_term instanceof WP_Term) {
						wp_set_object_terms($post_id, (int) $created_term->term_id, 'bbb_series', false);
					}
					update_post_meta($post_id, '_bbb_series_handle', $series_handle);
					bbb_book_ensure_series_page($series_handle, $post_id, $created_term instanceof WP_Term ? $created_term->name : '');
				}
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
