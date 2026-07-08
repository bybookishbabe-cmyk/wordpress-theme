<?php
/**
 * Force attachment downloads for protected theme assets.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_july_2026_monthly_theme_is_released')) {
	function bbb_july_2026_monthly_theme_is_released(): bool {
		$release_timezone = new DateTimeZone('America/Los_Angeles');
		$release_at       = new DateTimeImmutable('2026-07-01 00:00:00', $release_timezone);
		$now              = new DateTimeImmutable('now', $release_timezone);

		return $now >= $release_at;
	}
}

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

function bbb_forced_theme_asset_download_log_table(): string {
	global $wpdb;

	return $wpdb->prefix . 'bbb_theme_download_logs';
}

function bbb_forced_theme_asset_download_ensure_log_table(): void {
	global $wpdb;

	$table_name = bbb_forced_theme_asset_download_log_table();
	$exists     = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));

	if ($exists === $table_name) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			relative_path text NOT NULL,
			filename varchar(255) NOT NULL DEFAULT '',
			theme_month varchar(32) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_email varchar(191) NOT NULL DEFAULT '',
			ip_hash varchar(64) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			referer text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY theme_month (theme_month),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};"
	);
}

function bbb_forced_theme_asset_download_client_ip(): string {
	$headers = array(
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_FORWARDED_FOR',
		'REMOTE_ADDR',
	);

	foreach ($headers as $header) {
		$value = isset($_SERVER[$header]) ? (string) wp_unslash($_SERVER[$header]) : '';

		if ('' === $value) {
			continue;
		}

		$ip = trim(explode(',', $value)[0]);

		if (filter_var($ip, FILTER_VALIDATE_IP)) {
			return $ip;
		}
	}

	return '';
}

function bbb_forced_theme_asset_download_month(string $relative_path): string {
	if (preg_match('#assets/monthly-themes/([^/]+)/#', $relative_path, $matches)) {
		return sanitize_key((string) $matches[1]);
	}

	return '';
}

function bbb_forced_theme_asset_download_log(string $relative_path, string $filename): void {
	global $wpdb;

	bbb_forced_theme_asset_download_ensure_log_table();

	$user    = wp_get_current_user();
	$user_id = $user instanceof WP_User ? (int) $user->ID : 0;
	$email   = $user_id > 0 ? sanitize_email((string) $user->user_email) : '';
	$ip      = bbb_forced_theme_asset_download_client_ip();

	$wpdb->insert(
		bbb_forced_theme_asset_download_log_table(),
		array(
			'relative_path' => $relative_path,
			'filename'      => $filename,
			'theme_month'   => bbb_forced_theme_asset_download_month($relative_path),
			'user_id'       => $user_id,
			'user_email'    => $email,
			'ip_hash'       => '' !== $ip ? hash_hmac('sha256', $ip, wp_salt('auth')) : '',
			'user_agent'    => substr(sanitize_text_field((string) wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255),
			'referer'       => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw((string) wp_unslash($_SERVER['HTTP_REFERER'])) : '',
			'created_at'    => current_time('mysql'),
		),
		array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
	);
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

	if (
		str_starts_with($relative_path, 'assets/monthly-themes/july-2026/') &&
		function_exists('bbb_july_2026_monthly_theme_is_released') &&
		! bbb_july_2026_monthly_theme_is_released() &&
		! current_user_can('manage_options')
	) {
		status_header(403);
		exit;
	}

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

	if ('GET' === strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))) {
		bbb_forced_theme_asset_download_log($relative_path, $filename);
	}

	readfile($file_path);
	exit;
}
add_action('template_redirect', 'bbb_handle_forced_theme_asset_download', 0);
