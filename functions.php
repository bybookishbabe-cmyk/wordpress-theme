<?php
/**
 * Theme setup.
 *
 * @package WordPressTheme
 */

declare(strict_types=1);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'wordpress-theme-main',
			get_theme_file_uri('assets/css/main.css'),
			array(),
			wp_get_theme()->get('Version')
		);
	}
);

