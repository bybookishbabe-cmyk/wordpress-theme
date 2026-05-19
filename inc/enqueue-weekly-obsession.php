<?php
/**
 * Weekly Obsession assets.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if (is_front_page() || is_home()) {
			wp_enqueue_style(
				'sss-weekly-obsession',
				get_template_directory_uri() . '/assets/css/weekly-obsession.css',
				array(),
				'1.0.0'
			);
		}
	}
);
