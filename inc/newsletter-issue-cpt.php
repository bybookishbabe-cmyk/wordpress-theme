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
