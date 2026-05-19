<?php
/**
 * SSS Book custom post type.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_post_type(
			'sss_book',
			array(
				'label'        => 'Books',
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => array('title', 'thumbnail', 'custom-fields'),
			)
		);

		register_post_meta(
			'sss_book',
			'_book_spice_level',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => static function (): bool {
					return current_user_can('edit_posts');
				},
			)
		);
	}
);
