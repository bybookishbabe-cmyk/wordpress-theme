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
			$weekly_obsession_css_path = get_theme_file_path('assets/css/weekly-obsession.css');
			wp_enqueue_style(
				'sss-weekly-obsession',
				get_template_directory_uri() . '/assets/css/weekly-obsession.css',
				array(),
				file_exists($weekly_obsession_css_path) ? (string) filemtime($weekly_obsession_css_path) : wp_get_theme()->get('Version')
			);
		}
	}
);
