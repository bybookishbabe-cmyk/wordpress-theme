<?php
/**
 * Newsletter Issue custom post type.
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
				'label'        => 'Newsletter Issues',
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'supports'     => array('title', 'custom-fields'),
			)
		);

		$meta_fields = array(
			'_issue_publish_date'    => 'string',
			'_issue_subtitle'        => 'string',
			'_issue_book_id'         => 'integer',
			'_issue_library_book_id' => 'integer',
			'_issue_title_override'  => 'string',
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
