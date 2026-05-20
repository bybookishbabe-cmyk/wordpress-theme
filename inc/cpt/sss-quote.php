<?php
/**
 * SSS Quote custom post type.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'init',
	static function (): void {
		register_post_type(
			'sss_quote',
			array(
				'labels'       => array(
					'name'          => __('Quotes', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Quote', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array('title', 'editor', 'custom-fields'),
				'has_archive'  => 'quotes',
				'rewrite'      => array('slug' => 'quotes'),
			)
		);

		register_post_type(
			'bbb_quote',
			array(
				'labels'       => array(
					'name'          => __('Quotes', 'bybookishbabe-shopify-port'),
					'singular_name' => __('Quote', 'bybookishbabe-shopify-port'),
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'supports'     => array('title', 'editor', 'custom-fields'),
				'has_archive'  => false,
				'rewrite'      => false,
			)
		);
	}
);
