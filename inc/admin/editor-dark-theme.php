<?php
/**
 * Dark admin/editor polish for ByBookishBabe content screens.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_admin_editor_dark_theme_path(): string {
	return get_theme_file_path('assets/css/admin-editor-dark.css');
}

function bbb_admin_editor_dark_theme_assets(string $hook): void {
	if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
		return;
	}

	$asset_path = bbb_admin_editor_dark_theme_path();
	if (!file_exists($asset_path)) {
		return;
	}

	wp_enqueue_style(
		'bbb-admin-editor-dark',
		get_theme_file_uri('assets/css/admin-editor-dark.css'),
		array(),
		(string) filemtime($asset_path)
	);
}
add_action('admin_enqueue_scripts', 'bbb_admin_editor_dark_theme_assets', 99);

function bbb_admin_editor_dark_theme_block_assets(): void {
	$asset_path = bbb_admin_editor_dark_theme_path();
	if (!file_exists($asset_path)) {
		return;
	}

	wp_enqueue_style(
		'bbb-admin-editor-dark',
		get_theme_file_uri('assets/css/admin-editor-dark.css'),
		array(),
		(string) filemtime($asset_path)
	);
}
add_action('enqueue_block_editor_assets', 'bbb_admin_editor_dark_theme_block_assets', 99);

function bbb_admin_editor_dark_theme_inline(): void {
	$asset_path = bbb_admin_editor_dark_theme_path();
	if (!file_exists($asset_path)) {
		return;
	}

	printf(
		"\n<style id=\"bbb-admin-editor-dark-inline\">\n%s\n</style>\n",
		(string) file_get_contents($asset_path)
	);
}
add_action('admin_footer-post.php', 'bbb_admin_editor_dark_theme_inline', 99);
add_action('admin_footer-post-new.php', 'bbb_admin_editor_dark_theme_inline', 99);
