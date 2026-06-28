<?php
/**
 * Force attachment downloads for protected theme assets.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_forced_theme_asset_download_url(string $relative_path, string $filename = ''): string {
	$relative_path = ltrim($relative_path, '/');
	$expires       = time() + (6 * HOUR_IN_SECONDS);
	$args          = array(
		'bbb_theme_download' => $relative_path,
		'expires'            => $expires,
		'token'              => bbb_forced_theme_asset_download_signature($relative_path, $filename, $expires),
	);

	if ('' !== $filename) {
		$args['name'] = $filename;
	}

	return add_query_arg($args, home_url('/'));
}

function bbb_forced_theme_asset_download_signature(string $relative_path, string $filename, int $expires): string {
	return hash_hmac(
		'sha256',
		ltrim($relative_path, '/') . '|' . $filename . '|' . $expires,
		wp_salt('auth')
	);
}

function bbb_forced_theme_asset_download_token_is_valid(string $relative_path, string $filename): bool {
	$expires = isset($_GET['expires']) ? absint($_GET['expires']) : 0;
	$token   = isset($_GET['token']) ? (string) wp_unslash($_GET['token']) : '';

	if (!$expires || $expires < time() || '' === $token) {
		return false;
	}

	$expected = bbb_forced_theme_asset_download_signature($relative_path, $filename, $expires);

	return hash_equals($expected, $token);
}

function bbb_forced_download_clean_path(string $path): string {
	$path = rawurldecode($path);
	$path = str_replace('\\', '/', $path);
	$path = preg_replace('#/+#', '/', $path) ?: '';

	return ltrim($path, '/');
}

function bbb_handle_forced_theme_asset_download(): void {
	if (empty($_GET['bbb_theme_download'])) {
		return;
	}

	$relative_path = bbb_forced_download_clean_path((string) wp_unslash($_GET['bbb_theme_download']));

	if (
		'' === $relative_path ||
		str_contains($relative_path, '../') ||
		! str_starts_with($relative_path, 'assets/monthly-themes/')
	) {
		status_header(404);
		exit;
	}

	$extension = strtolower((string) pathinfo($relative_path, PATHINFO_EXTENSION));
	$allowed   = array('pdf', 'png', 'jpg', 'jpeg', 'webp');

	if (! in_array($extension, $allowed, true)) {
		status_header(404);
		exit;
	}

	$theme_root = realpath(get_theme_file_path());
	$file_path  = realpath(get_theme_file_path($relative_path));

	if (
		false === $theme_root ||
		false === $file_path ||
		! str_starts_with($file_path, $theme_root . DIRECTORY_SEPARATOR) ||
		! is_file($file_path)
	) {
		status_header(404);
		exit;
	}

	$filename = isset($_GET['name'])
		? sanitize_file_name(bbb_forced_download_clean_path((string) wp_unslash($_GET['name'])))
		: '';

	if ('' === $filename) {
		$filename = basename($file_path);
	}

	$token_is_valid = bbb_forced_theme_asset_download_token_is_valid($relative_path, $filename);

	if (str_starts_with($relative_path, 'assets/monthly-themes/') && ! $token_is_valid && function_exists('bbb_reader_is_society') && ! bbb_reader_is_society()) {
		status_header(403);
		exit;
	}

	status_header(200);
	nocache_headers();
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Content-Length: ' . (string) filesize($file_path));
	header('X-Content-Type-Options: nosniff');

	readfile($file_path);
	exit;
}
add_action('template_redirect', 'bbb_handle_forced_theme_asset_download', 0);
