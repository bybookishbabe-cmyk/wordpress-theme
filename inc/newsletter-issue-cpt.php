<?php
/**
 * Newsletter Issue custom post type and ACF fields.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_post_type(
			'newsletter_issue',
			array(
				'labels'       => array(
					'name'          => __('Newsletter Issues', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Newsletter Issue', 'bybookishbabe-shopify-port'),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-email-alt2',
				'supports'     => array('title', 'editor', 'excerpt', 'custom-fields'),
			)
		);

		$meta_fields = array(
			'_issue_publish_date'    => 'string',
			'_issue_subtitle'        => 'string',
			'_issue_book_id'         => 'integer',
			'_issue_library_book_id' => 'integer',
			'_issue_book_handle'     => 'string',
			'_issue_title_override'  => 'string',
			'_issue_excerpt'         => 'string',
			'_issue_label'           => 'string',
			'_issue_no'              => 'string',
			'_issue_tropes'          => 'string',
			'_issue_preview_url'     => 'string',
			'_issue_preview_alt'     => 'string',
			'_issue_pull_quote'      => 'string',
			'_issue_import_source'   => 'string',
			'_issue_imported_at'     => 'string',
			'_bbb_newsletter_url'    => 'string',
		);

		foreach ($meta_fields as $meta_key => $type) {
			register_post_meta(
				'newsletter_issue',
				$meta_key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'integer' === $type ? 'absint' : 'sanitize_text_field',
					'auth_callback'     => static function (): bool {
						return current_user_can('edit_posts');
					},
				)
			);
		}
	}
);

function bbb_newsletter_seed_find_book_id(string $handle): int {
	if ('' === $handle) {
		return 0;
	}

	$book = get_page_by_path($handle, OBJECT, array('bbb_book', 'sss_book'));

	return $book instanceof WP_Post ? (int) $book->ID : 0;
}

function bbb_newsletter_seed_datetime(string $publish_date): array {
	if ('' === $publish_date) {
		return array('', '');
	}

	try {
		$dt = new DateTimeImmutable($publish_date . ' 10:00:00', new DateTimeZone('America/Los_Angeles'));
	} catch (Exception $e) {
		return array('', '');
	}

	return array(
		$dt->setTimezone(wp_timezone())->format('Y-m-d H:i:s'),
		$dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
	);
}

function bbb_seed_newsletter_issues_from_theme(): void {
	if (!post_type_exists('newsletter_issue')) {
		return;
	}

	$seed_version = '20260520_shopify_22';
	if (get_option('bbb_newsletter_seed_version') === $seed_version) {
		return;
	}

	$seed_file = get_theme_file_path('data/newsletter-issues-seed.json');
	if (!is_readable($seed_file)) {
		return;
	}

	$issues = json_decode((string) file_get_contents($seed_file), true);
	if (!is_array($issues)) {
		return;
	}

	foreach ($issues as $issue) {
		if (!is_array($issue) || empty($issue['handle'])) {
			continue;
		}

		$handle       = sanitize_title((string) $issue['handle']);
		$title        = isset($issue['title']) ? sanitize_text_field((string) $issue['title']) : $handle;
		$publish_date = isset($issue['publish_date']) ? sanitize_text_field((string) $issue['publish_date']) : '';
		$existing     = get_page_by_path($handle, OBJECT, 'newsletter_issue');
		$postarr      = array(
			'post_type'   => 'newsletter_issue',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $handle,
		);

		[$post_date, $post_date_gmt] = bbb_newsletter_seed_datetime($publish_date);
		if ('' !== $post_date) {
			$postarr['post_date']     = $post_date;
			$postarr['post_date_gmt'] = $post_date_gmt;
		}

		if ($existing instanceof WP_Post) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post($postarr, true);
		} else {
			$post_id = wp_insert_post($postarr, true);
		}

		if (is_wp_error($post_id)) {
			continue;
		}

		$post_id = (int) $post_id;
		$url     = isset($issue['url']) ? esc_url_raw((string) $issue['url']) : '';

		if ('' !== $publish_date) {
			update_post_meta($post_id, '_issue_publish_date', $publish_date);
			update_post_meta($post_id, 'publish_date', $publish_date);
		}
		if (!empty($issue['subtitle'])) {
			update_post_meta($post_id, '_issue_subtitle', sanitize_text_field((string) $issue['subtitle']));
		}
		if ('' !== $url) {
			update_post_meta($post_id, '_bbb_newsletter_url', $url);
			update_post_meta($post_id, 'issue_url', $url);
		}
		if (!empty($issue['preview_url'])) {
			update_post_meta($post_id, '_issue_preview_url', esc_url_raw((string) $issue['preview_url']));
			update_post_meta($post_id, '_issue_preview_alt', sanitize_text_field((string) ($issue['preview_alt'] ?? '')));
		}
		if (!empty($issue['book_handle'])) {
			$book_handle = sanitize_title((string) $issue['book_handle']);
			$book_id     = bbb_newsletter_seed_find_book_id($book_handle);
			update_post_meta($post_id, '_issue_book_handle', $book_handle);
			if ($book_id) {
				update_post_meta($post_id, '_issue_book_id', $book_id);
				update_post_meta($post_id, '_issue_library_book_id', $book_id);
			}
		}
	}

	update_option('bbb_newsletter_seed_version', $seed_version, false);
}
add_action('init', 'bbb_seed_newsletter_issues_from_theme', 20);

function bbb_newsletter_issue_admin_book_types(): array {
	return array_values(
		array_filter(
			array('bbb_book', 'sss_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);
}

function bbb_newsletter_issue_admin_books(): array {
	$post_types = bbb_newsletter_issue_admin_book_types();
	if (empty($post_types)) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'all',
		)
	);
}

function bbb_newsletter_issue_admin_selected_book_id(int $issue_id): int {
	foreach (array('_issue_book_id', '_issue_library_book_id', 'book_id', 'library_book_id') as $meta_key) {
		$book_id = absint(get_post_meta($issue_id, $meta_key, true));
		if ($book_id > 0) {
			return $book_id;
		}
	}

	$book_handle = sanitize_title((string) get_post_meta($issue_id, '_issue_book_handle', true));
	if ('' !== $book_handle) {
		$book = get_page_by_path($book_handle, OBJECT, bbb_newsletter_issue_admin_book_types());
		if ($book instanceof WP_Post) {
			return (int) $book->ID;
		}
	}

	return 0;
}

function bbb_newsletter_issue_admin_date_value(int $issue_id): string {
	$date = (string) get_post_meta($issue_id, '_issue_publish_date', true);
	if ('' === $date) {
		$date = (string) get_post_meta($issue_id, 'publish_date', true);
	}

	$date = trim($date);
	if (preg_match('/^\d{8}$/', $date)) {
		return substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
	}

	return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

function bbb_newsletter_issue_admin_url_value(int $issue_id): string {
	foreach (array('_bbb_newsletter_url', 'issue_url', '_issue_url') as $meta_key) {
		$url = trim((string) get_post_meta($issue_id, $meta_key, true));
		if ('' !== $url) {
			return $url;
		}
	}

	return '';
}

function bbb_newsletter_issue_admin_render_details(WP_Post $post): void {
	wp_nonce_field('bbb_newsletter_issue_details', 'bbb_newsletter_issue_details_nonce');

	$selected_book_id = bbb_newsletter_issue_admin_selected_book_id((int) $post->ID);
	$publish_date     = bbb_newsletter_issue_admin_date_value((int) $post->ID);
	$issue_url        = bbb_newsletter_issue_admin_url_value((int) $post->ID);
	$subtitle         = (string) get_post_meta($post->ID, '_issue_subtitle', true);
	$excerpt          = (string) get_post_meta($post->ID, '_issue_excerpt', true);
	$preview_url      = (string) get_post_meta($post->ID, '_issue_preview_url', true);
	$preview_alt      = (string) get_post_meta($post->ID, '_issue_preview_alt', true);
	$books            = bbb_newsletter_issue_admin_books();
	?>
	<style>
		.bbb-newsletter-details {
			display: grid;
			gap: 16px;
			max-width: 960px;
		}
		.bbb-newsletter-details label {
			display: block;
			font-weight: 600;
			margin-bottom: 6px;
		}
		.bbb-newsletter-details input[type="text"],
		.bbb-newsletter-details input[type="url"],
		.bbb-newsletter-details input[type="date"],
		.bbb-newsletter-details select,
		.bbb-newsletter-details textarea {
			max-width: 680px;
			width: 100%;
		}
		.bbb-newsletter-details__hint {
			color: #646970;
			margin: 6px 0 0;
		}
		.bbb-newsletter-details__image {
			align-items: flex-start;
			display: flex;
			flex-wrap: wrap;
			gap: 14px;
		}
		.bbb-newsletter-details__preview {
			background: #f6f7f7;
			border: 1px solid #dcdcde;
			border-radius: 3px;
			max-height: 180px;
			max-width: 280px;
			object-fit: cover;
		}
	</style>
	<div class="bbb-newsletter-details">
		<p class="bbb-newsletter-details__hint">
			<?php esc_html_e('Pick the featured book, set the issue date, and choose the image that appears on the Society/newsletter sections.', 'bybookishbabe-shopify-port'); ?>
		</p>

		<div>
			<label for="bbb_issue_book_id"><?php esc_html_e('Featured book', 'bybookishbabe-shopify-port'); ?></label>
			<select id="bbb_issue_book_id" name="bbb_issue_book_id">
				<option value="0"><?php esc_html_e('No linked book yet', 'bybookishbabe-shopify-port'); ?></option>
				<?php foreach ($books as $book) : ?>
					<?php if (!$book instanceof WP_Post) { continue; } ?>
					<option value="<?php echo esc_attr((string) $book->ID); ?>" <?php selected($selected_book_id, (int) $book->ID); ?>>
						<?php echo esc_html(get_the_title($book) . ' (' . $book->post_type . ')'); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="bbb-newsletter-details__hint">
				<?php esc_html_e('This powers the weekly obsession/book card and updates the book release gating when you save.', 'bybookishbabe-shopify-port'); ?>
			</p>
		</div>

		<div>
			<label for="bbb_issue_publish_date"><?php esc_html_e('Newsletter date', 'bybookishbabe-shopify-port'); ?></label>
			<input id="bbb_issue_publish_date" name="bbb_issue_publish_date" type="date" value="<?php echo esc_attr($publish_date); ?>">
			<p class="bbb-newsletter-details__hint">
				<?php esc_html_e('Books linked here stay hidden until this date if they are newsletter-gated.', 'bybookishbabe-shopify-port'); ?>
			</p>
		</div>

		<div>
			<label for="bbb_issue_url"><?php esc_html_e('Newsletter URL', 'bybookishbabe-shopify-port'); ?></label>
			<input id="bbb_issue_url" name="bbb_issue_url" type="url" value="<?php echo esc_attr($issue_url); ?>" placeholder="https://">
		</div>

		<div>
			<label for="bbb_issue_subtitle"><?php esc_html_e('Subtitle', 'bybookishbabe-shopify-port'); ?></label>
			<textarea id="bbb_issue_subtitle" name="bbb_issue_subtitle" rows="3"><?php echo esc_textarea($subtitle); ?></textarea>
		</div>

		<div>
			<label for="bbb_issue_excerpt"><?php esc_html_e('Short summary', 'bybookishbabe-shopify-port'); ?></label>
			<textarea id="bbb_issue_excerpt" name="bbb_issue_excerpt" rows="3"><?php echo esc_textarea($excerpt); ?></textarea>
		</div>

		<div>
			<label for="bbb_issue_preview_url"><?php esc_html_e('Preview image', 'bybookishbabe-shopify-port'); ?></label>
			<div class="bbb-newsletter-details__image">
				<img
					class="bbb-newsletter-details__preview"
					src="<?php echo esc_url($preview_url); ?>"
					alt=""
					data-bbb-newsletter-image-preview
					<?php echo '' === $preview_url ? 'hidden' : ''; ?>
				>
				<div>
					<input id="bbb_issue_preview_url" name="bbb_issue_preview_url" type="url" value="<?php echo esc_attr($preview_url); ?>" placeholder="https://">
					<p>
						<button type="button" class="button" data-bbb-newsletter-image-pick><?php esc_html_e('Choose image', 'bybookishbabe-shopify-port'); ?></button>
						<button type="button" class="button" data-bbb-newsletter-image-clear><?php esc_html_e('Clear image', 'bybookishbabe-shopify-port'); ?></button>
					</p>
					<label for="bbb_issue_preview_alt"><?php esc_html_e('Image alt text', 'bybookishbabe-shopify-port'); ?></label>
					<input id="bbb_issue_preview_alt" name="bbb_issue_preview_alt" type="text" value="<?php echo esc_attr($preview_alt); ?>">
				</div>
			</div>
		</div>
	</div>
	<?php
}

function bbb_newsletter_issue_admin_add_metabox(): void {
	add_meta_box(
		'bbb_newsletter_issue_details',
		__('Newsletter issue details', 'bybookishbabe-shopify-port'),
		'bbb_newsletter_issue_admin_render_details',
		'newsletter_issue',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes_newsletter_issue', 'bbb_newsletter_issue_admin_add_metabox');

function bbb_newsletter_issue_admin_assets(string $hook): void {
	if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
		return;
	}

	$screen = get_current_screen();
	if (!$screen || 'newsletter_issue' !== $screen->post_type) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){\n" .
		"var frame;\n" .
		"$(document).on('click','[data-bbb-newsletter-image-pick]',function(e){\n" .
		"e.preventDefault();\n" .
		"if(frame){frame.open();return;}\n" .
		"frame=wp.media({title:'Choose newsletter preview image',button:{text:'Use this image'},multiple:false});\n" .
		"frame.on('select',function(){var attachment=frame.state().get('selection').first().toJSON();$('#bbb_issue_preview_url').val(attachment.url || '');$('#bbb_issue_preview_alt').val(attachment.alt || attachment.title || '');$('[data-bbb-newsletter-image-preview]').attr('src',attachment.url || '').prop('hidden',!attachment.url);});\n" .
		"frame.open();\n" .
		"});\n" .
		"$(document).on('click','[data-bbb-newsletter-image-clear]',function(e){e.preventDefault();$('#bbb_issue_preview_url,#bbb_issue_preview_alt').val('');$('[data-bbb-newsletter-image-preview]').attr('src','').prop('hidden',true);});\n" .
		"});"
	);
}
add_action('admin_enqueue_scripts', 'bbb_newsletter_issue_admin_assets');

function bbb_newsletter_issue_admin_save(int $post_id): void {
	if (
		!isset($_POST['bbb_newsletter_issue_details_nonce'])
		|| !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bbb_newsletter_issue_details_nonce'])), 'bbb_newsletter_issue_details')
	) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$publish_date = isset($_POST['bbb_issue_publish_date'])
		? sanitize_text_field(wp_unslash($_POST['bbb_issue_publish_date']))
		: '';
	$publish_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $publish_date) ? $publish_date : '';

	$issue_url = isset($_POST['bbb_issue_url'])
		? esc_url_raw(wp_unslash($_POST['bbb_issue_url']))
		: '';

	$subtitle = isset($_POST['bbb_issue_subtitle'])
		? sanitize_textarea_field(wp_unslash($_POST['bbb_issue_subtitle']))
		: '';

	$excerpt = isset($_POST['bbb_issue_excerpt'])
		? sanitize_textarea_field(wp_unslash($_POST['bbb_issue_excerpt']))
		: '';

	$preview_url = isset($_POST['bbb_issue_preview_url'])
		? esc_url_raw(wp_unslash($_POST['bbb_issue_preview_url']))
		: '';

	$preview_alt = isset($_POST['bbb_issue_preview_alt'])
		? sanitize_text_field(wp_unslash($_POST['bbb_issue_preview_alt']))
		: '';

	foreach (array('_issue_publish_date', 'publish_date') as $meta_key) {
		'' !== $publish_date ? update_post_meta($post_id, $meta_key, $publish_date) : delete_post_meta($post_id, $meta_key);
	}

	foreach (array('_bbb_newsletter_url', 'issue_url') as $meta_key) {
		'' !== $issue_url ? update_post_meta($post_id, $meta_key, $issue_url) : delete_post_meta($post_id, $meta_key);
	}

	'' !== $subtitle ? update_post_meta($post_id, '_issue_subtitle', $subtitle) : delete_post_meta($post_id, '_issue_subtitle');
	'' !== $excerpt ? update_post_meta($post_id, '_issue_excerpt', $excerpt) : delete_post_meta($post_id, '_issue_excerpt');
	'' !== $preview_url ? update_post_meta($post_id, '_issue_preview_url', $preview_url) : delete_post_meta($post_id, '_issue_preview_url');
	'' !== $preview_alt ? update_post_meta($post_id, '_issue_preview_alt', $preview_alt) : delete_post_meta($post_id, '_issue_preview_alt');

	$book_id = isset($_POST['bbb_issue_book_id']) ? absint($_POST['bbb_issue_book_id']) : 0;
	$book    = $book_id > 0 ? get_post($book_id) : null;
	if ($book instanceof WP_Post && in_array($book->post_type, bbb_newsletter_issue_admin_book_types(), true)) {
		$book_handle = $book->post_name ?: sanitize_title(get_the_title($book));
		update_post_meta($post_id, '_issue_book_id', $book_id);
		update_post_meta($post_id, '_issue_library_book_id', $book_id);
		update_post_meta($post_id, 'book_id', $book_id);
		update_post_meta($post_id, 'library_book_id', $book_id);
		update_post_meta($post_id, '_issue_book_handle', $book_handle);
		update_post_meta($post_id, 'book_handle', $book_handle);
		update_post_meta($post_id, 'library_book_handle', $book_handle);

		if ('' !== $publish_date) {
			update_post_meta($book_id, '_bbb_newsletter_date', $publish_date);
			update_post_meta($book_id, 'featured_in_newsletter_date', $publish_date);
		}
		if ('' !== $issue_url) {
			update_post_meta($book_id, '_bbb_newsletter_url', $issue_url);
			update_post_meta($book_id, 'newsletter_url', $issue_url);
		}
	} else {
		foreach (array('_issue_book_id', '_issue_library_book_id', 'book_id', 'library_book_id', '_issue_book_handle', 'book_handle', 'library_book_handle') as $meta_key) {
			delete_post_meta($post_id, $meta_key);
		}
	}
}
add_action('save_post_newsletter_issue', 'bbb_newsletter_issue_admin_save');

add_action(
	'acf/init',
	static function (): void {
		if (!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_bbb_newsletter_issue',
				'title'    => __('Newsletter Issue Fields', 'bybookishbabe-shopify-port'),
				'fields'   => array(
					array(
						'key'            => 'field_bbb_newsletter_publish_date',
						'label'          => __('Publish Date', 'bybookishbabe-shopify-port'),
						'name'           => 'publish_date',
						'type'           => 'date_picker',
						'display_format' => 'M j, Y',
						'return_format'  => 'Ymd',
						'required'       => 1,
					),
					array(
						'key'   => 'field_bbb_newsletter_issue_url',
						'label' => __('Issue URL', 'bybookishbabe-shopify-port'),
						'name'  => 'issue_url',
						'type'  => 'url',
					),
					array(
						'key'   => 'field_bbb_newsletter_issue_no',
						'label' => __('Issue Number', 'bybookishbabe-shopify-port'),
						'name'  => 'issue_no',
						'type'  => 'number',
					),
					array(
						'key'   => 'field_bbb_newsletter_issue_label',
						'label' => __('Label / Kicker', 'bybookishbabe-shopify-port'),
						'name'  => 'issue_label',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_bbb_newsletter_issue_subtitle',
						'label' => __('Subtitle', 'bybookishbabe-shopify-port'),
						'name'  => 'issue_subtitle',
						'type'  => 'textarea',
						'rows'  => 3,
					),
					array(
						'key'           => 'field_bbb_newsletter_preview_image',
						'label'         => __('Preview Image', 'bybookishbabe-shopify-port'),
						'name'          => 'preview_image',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'newsletter_issue',
						),
					),
				),
			)
		);
	}
);
