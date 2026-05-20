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
				'supports'     => array('title', 'custom-fields'),
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
