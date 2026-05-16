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
			'wordpress-theme-fonts',
			'https://fonts.googleapis.com/css2?family=Allura&family=Cormorant:wght@500;600&family=Cormorant+Garamond:wght@400;500;600;700&family=Great+Vibes&family=Kaushan+Script&family=Playfair+Display:ital,wght@0,400;0,600;1,400;1,600&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'wordpress-theme-main',
			get_theme_file_uri('assets/css/main.css'),
			array('wordpress-theme-fonts'),
			wp_get_theme()->get('Version')
		);
	}
);
