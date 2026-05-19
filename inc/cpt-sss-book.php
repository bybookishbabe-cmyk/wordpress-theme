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
		if (!get_role('sss_member')) {
			add_role(
				'sss_member',
				__('SSS Member', 'bybookishbabe-shopify-port'),
				array(
					'read'           => true,
					'sss_member'     => true,
					'bbb_sss_access' => true,
				)
			);
		}

		register_post_type(
			'sss_book',
			array(
				'labels'       => array(
					'name'          => __('Books', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Book', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-book-alt',
				'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
				'has_archive'  => 'romance-library',
				'rewrite'      => array('slug' => 'books'),
			)
		);

		foreach (array('spice_level', '_book_spice_level', 'series_number', 'tension_score', 'emotional_damage_score', 'darkness_level', 'yearning_level') as $meta_key) {
			register_post_meta(
				'sss_book',
				$meta_key,
				array(
					'type'              => 'integer',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => static fn(): bool => current_user_can('edit_posts'),
				)
			);
		}

		foreach (array('author', 'amazon_link', 'bookshop_link', 'boyfriend_name', 'boyfriend_type', 'mini_note', 'newsletter_url') as $meta_key) {
			register_post_meta(
				'sss_book',
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static fn(): bool => current_user_can('edit_posts'),
				)
			);
		}

		foreach (array('why_i_loved_it') as $meta_key) {
			register_post_meta(
				'sss_book',
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'wp_kses_post',
					'auth_callback'     => static fn(): bool => current_user_can('edit_posts'),
				)
			);
		}

		foreach (array('starter_pack', 'hide_from_library', 'is_private', 'read_as_standalone', 'standalone', 'on_kindle_unlimited', 'reread_badge', 'top_shelf') as $meta_key) {
			register_post_meta(
				'sss_book',
				$meta_key,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => static fn(): bool => current_user_can('edit_posts'),
				)
			);
		}
	}
);
