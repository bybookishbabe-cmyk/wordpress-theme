<?php
/**
 * Minimal WordPress bootstrap for the Shopify-faithful port.
 *
 * The prior WordPress rebuild lives in /firstpass. The Shopify theme copy now
 * lives at the repository root and should be converted section/snippet-first.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('custom-logo');

		register_nav_menus(
			array(
				'main-menu' => __('Main Navigation', 'bybookishbabe-shopify-port'),
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$version = wp_get_theme()->get('Version');

		wp_enqueue_style(
			'bbb-shopify-fonts',
			'https://fonts.googleapis.com/css2?family=Allura&family=Assistant:wght@400;500;600;700&family=Cormorant:wght@500;600&family=Cormorant+Garamond:wght@400;500;600;700&family=Great+Vibes&family=Kaushan+Script&family=Libre+Baskerville:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400;1,600&display=swap',
			array(),
			null
		);

		$styles = array(
			'base',
			'component-list-menu',
			'component-menu-drawer',
			'component-mega-menu',
			'component-search',
			'component-list-social',
			'section-blog-post',
			'blog-system',
			'blog-signoff',
			'sss-library',
			'bookshelf-signup',
			'site-custom-overrides',
		);

		$deps = array('bbb-shopify-fonts');
		foreach ($styles as $style) {
			$path = get_theme_file_path('assets/' . $style . '.css');
			if (!file_exists($path)) {
				continue;
			}

			$handle = 'bbb-' . $style;
			wp_enqueue_style(
				$handle,
				get_theme_file_uri('assets/' . $style . '.css'),
				$deps,
				$version
			);
			$deps = array($handle);
		}

		wp_enqueue_script(
			'bbb-supabase',
			'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
			array(),
			null,
			true
		);

		$scripts = array(
			'constants',
			'global',
			'details-disclosure',
			'details-modal',
			'search-form',
			'predictive-search',
			'animations',
			'sss-library',
			'blog-system',
			'bookshelf-signup',
		);

		$script_deps = array('bbb-supabase');
		foreach ($scripts as $script) {
			$path = get_theme_file_path('assets/' . $script . '.js');
			if (!file_exists($path)) {
				continue;
			}

			$handle = 'bbb-' . $script;
			wp_enqueue_script(
				$handle,
				get_theme_file_uri('assets/' . $script . '.js'),
				$script_deps,
				$version,
				true
			);
			$script_deps = array($handle);
		}

		wp_add_inline_script(
			'bbb-sss-library',
			'window.BBBReaderAccount = window.BBBReaderAccount || {"loggedIn":false,"customerId":null,"email":"","firstName":"","isSociety":false,"bookshelfUrl":"' . esc_js(home_url('/my-bookshelf/')) . '","accountUrl":"' . esc_js(home_url('/my-account/')) . '","loginUrl":"' . esc_js(wp_login_url()) . '"}; window.bbbUrls = window.bbbUrls || {"library":"' . esc_js(home_url('/library/')) . '","societyLibrary":"' . esc_js(home_url('/society-library/')) . '","myBookshelf":"' . esc_js(home_url('/my-bookshelf/')) . '","seriesBase":"' . esc_js(home_url('/book-series/')) . '"};',
			'before'
		);
	}
);
