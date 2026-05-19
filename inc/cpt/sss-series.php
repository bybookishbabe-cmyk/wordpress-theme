<?php
/**
 * SSS Series custom post type.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

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
	}
);
