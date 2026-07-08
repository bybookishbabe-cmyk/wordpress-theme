<?php
/**
 * Apply reading links from a JSON export of the book-link sheet.
 *
 * Usage:
 *   php firstpass/scripts/apply-ku-links.php /path/to/book-links.json --dry-run
 *   php firstpass/scripts/apply-ku-links.php /path/to/book-links.json
 */

$script_args = isset($argv) && is_array($argv) ? $argv : array();
$json_path = $script_args[1] ?? (string) getenv('BBB_LINKS_JSON');
$dry_run = in_array('--dry-run', $script_args, true) || '1' === (string) getenv('BBB_DRY_RUN');

if ($json_path === '' || !is_file($json_path)) {
	fwrite(STDERR, "Usage: php apply-ku-links.php /path/to/book-links.json [--dry-run]\n");
	exit(1);
}

$rows = json_decode((string) file_get_contents($json_path), true);
if (!is_array($rows)) {
	fwrite(STDERR, "Could not read JSON rows from {$json_path}\n");
	exit(1);
}

if (defined('ABSPATH') && isset($GLOBALS['wpdb'])) {
	bbb_book_links_apply_with_wpdb($rows, $dry_run);
}

$socket = getenv('BBB_WP_DB_SOCKET') ?: '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
$host = getenv('BBB_DB_HOST') ?: 'localhost';
$user = getenv('BBB_DB_USER') ?: 'root';
$password = getenv('BBB_DB_PASSWORD') ?: 'root';
$database = getenv('BBB_DB_NAME') ?: 'local';
$prefix = getenv('BBB_DB_PREFIX') ?: 'wp_';

$mysqli = mysqli_init();
if (!$mysqli || !mysqli_real_connect($mysqli, $host, $user, $password, $database, null, $socket)) {
	fwrite(STDERR, "Could not connect to MySQL: " . mysqli_connect_error() . "\n");
	exit(1);
}

mysqli_set_charset($mysqli, 'utf8mb4');

$applied = 0;
$skipped = 0;
$errors = 0;

foreach ($rows as $row) {
	$post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
	$expected_type = trim((string) ($row['post_type'] ?? ''));
	$title = trim((string) ($row['title'] ?? ''));
	$has_audible_column = bbb_book_links_row_has_key($row, array('audible_url', 'Audible link', 'audible_link', 'Audible Link', 'Audible URL'));
	$has_libby_column = bbb_book_links_row_has_key($row, array('libby_url', 'Libby link', 'libby_link', 'Libby Link', 'Libby URL'));
	$links = array(
		'ku'      => bbb_book_links_row_value($row, array('ku_url', 'KU link to fill', 'kindle_unlimited_link', 'Kindle Unlimited Link')),
		'audible' => bbb_book_links_row_value($row, array('audible_url', 'Audible link', 'audible_link', 'Audible Link', 'Audible URL')),
		'libby'   => bbb_book_links_row_value($row, array('libby_url', 'Libby link', 'libby_link', 'Libby Link', 'Libby URL')),
	);
	$has_updates = '' !== $links['ku'] || $has_audible_column || $has_libby_column;

	if ($post_id <= 0 || !$has_updates) {
		$skipped++;
		continue;
	}

	foreach (array_filter($links, static fn(string $url): bool => '' !== trim($url)) as $link_type => $url) {
		if (!preg_match('#^https?://#i', $url)) {
			fwrite(STDERR, "Skipping {$post_id} {$title}: {$link_type} URL is not http(s): {$url}\n");
			$errors++;
			continue 2;
		}
	}

	$post = bbb_ku_links_row($mysqli, "SELECT ID, post_type, post_title FROM {$prefix}posts WHERE ID = {$post_id} LIMIT 1");
	if (!$post) {
		fwrite(STDERR, "Skipping {$post_id} {$title}: post not found\n");
		$errors++;
		continue;
	}

	$post_type = (string) $post['post_type'];
	if ($expected_type !== '' && $post_type !== $expected_type) {
		fwrite(STDERR, "Skipping {$post_id} {$title}: expected {$expected_type}, found {$post_type}\n");
		$errors++;
		continue;
	}

	$updates = bbb_ku_links_meta_for_type($post_type, $links, $has_audible_column, $has_libby_column);
	if (!$updates) {
		fwrite(STDERR, "Skipping {$post_id} {$title}: unsupported post type {$post_type}\n");
		$errors++;
		continue;
	}

	printf(
		"%s %d %s: %s\n",
		$dry_run ? 'Would update' : 'Updating',
		$post_id,
		$post['post_title'],
		implode(', ', array_map(static fn(string $key, string $url): string => $key . '=' . ($url === '' ? '[blank]' : $url), array_keys($updates), $updates))
	);

	if (!$dry_run) {
		foreach ($updates as $meta_key => $meta_value) {
			bbb_ku_links_update_meta($mysqli, $prefix, $post_id, $meta_key, $meta_value);
		}
	}

	$applied++;
}

printf(
	"%s complete: %d applied, %d skipped blank/incomplete, %d errors\n",
	$dry_run ? 'Dry run' : 'Import',
	$applied,
	$skipped,
	$errors
);

exit($errors > 0 ? 1 : 0);

function bbb_book_links_apply_with_wpdb(array $rows, bool $dry_run): void {
	$applied = 0;
	$skipped = 0;
	$errors = 0;

	foreach ($rows as $row) {
		$post_id = isset($row['post_id']) ? (int) $row['post_id'] : 0;
		$expected_type = trim((string) ($row['post_type'] ?? ''));
		$title = trim((string) ($row['title'] ?? ''));
		$has_audible_column = bbb_book_links_row_has_key($row, array('audible_url', 'Audible link', 'audible_link', 'Audible Link', 'Audible URL'));
		$has_libby_column = bbb_book_links_row_has_key($row, array('libby_url', 'Libby link', 'libby_link', 'Libby Link', 'Libby URL'));
		$links = array(
			'ku'      => bbb_book_links_row_value($row, array('ku_url', 'KU link to fill', 'kindle_unlimited_link', 'Kindle Unlimited Link')),
			'audible' => bbb_book_links_row_value($row, array('audible_url', 'Audible link', 'audible_link', 'Audible Link', 'Audible URL')),
			'libby'   => bbb_book_links_row_value($row, array('libby_url', 'Libby link', 'libby_link', 'Libby Link', 'Libby URL')),
		);
		$has_updates = '' !== $links['ku'] || $has_audible_column || $has_libby_column;

		if ($post_id <= 0 || !$has_updates) {
			$skipped++;
			continue;
		}

		foreach (array_filter($links, static fn(string $url): bool => '' !== trim($url)) as $link_type => $url) {
			if (!preg_match('#^https?://#i', $url)) {
				fwrite(STDERR, "Skipping {$post_id} {$title}: {$link_type} URL is not http(s): {$url}\n");
				$errors++;
				continue 2;
			}
		}

		$post = get_post($post_id);
		if (!$post instanceof WP_Post) {
			fwrite(STDERR, "Skipping {$post_id} {$title}: post not found\n");
			$errors++;
			continue;
		}

		$post_type = (string) $post->post_type;
		if ($expected_type !== '' && $post_type !== $expected_type) {
			fwrite(STDERR, "Skipping {$post_id} {$title}: expected {$expected_type}, found {$post_type}\n");
			$errors++;
			continue;
		}

		$updates = bbb_ku_links_meta_for_type($post_type, $links, $has_audible_column, $has_libby_column);
		if (!$updates) {
			fwrite(STDERR, "Skipping {$post_id} {$title}: unsupported post type {$post_type}\n");
			$errors++;
			continue;
		}

		printf(
			"%s %d %s: %s\n",
			$dry_run ? 'Would update' : 'Updating',
			$post_id,
			$post->post_title,
			implode(', ', array_map(static fn(string $key, string $url): string => $key . '=' . ($url === '' ? '[blank]' : $url), array_keys($updates), $updates))
		);

		if (!$dry_run) {
			foreach ($updates as $meta_key => $meta_value) {
				update_post_meta($post_id, $meta_key, $meta_value);
			}
			clean_post_cache($post_id);
		}

		$applied++;
	}

	printf(
		"%s complete: %d applied, %d skipped blank/incomplete, %d errors\n",
		$dry_run ? 'Dry run' : 'Import',
		$applied,
		$skipped,
		$errors
	);

	exit($errors > 0 ? 1 : 0);
}

function bbb_ku_links_meta_for_type(string $post_type, array $links, bool $has_audible_column, bool $has_libby_column): array {
	if ($post_type === 'bbb_book') {
		$updates = array();
		if (!empty($links['ku'])) {
			$updates['_bbb_ku'] = '1';
			$updates['_bbb_ku_url'] = $links['ku'];
		}
		if ($has_audible_column) {
			$updates['_bbb_audible_url'] = $links['audible'];
		}
		if ($has_libby_column) {
			$updates['_bbb_libby_url'] = $links['libby'];
		}
		return $updates;
	}

	if ($post_type === 'sss_book') {
		$updates = array();
		if (!empty($links['ku'])) {
			$updates['sss_ku'] = '1';
			$updates['on_kindle_unlimited'] = '1';
			$updates['sss_ku_url'] = $links['ku'];
			$updates['kindle_unlimited_link'] = $links['ku'];
		}
		if ($has_audible_column) {
			$updates['audible_link'] = $links['audible'];
		}
		if ($has_libby_column) {
			$updates['libby_link'] = $links['libby'];
		}
		return $updates;
	}

	return array();
}

function bbb_book_links_row_value(array $row, array $keys): string {
	foreach ($keys as $key) {
		if (isset($row[$key]) && is_scalar($row[$key])) {
			$value = trim((string) $row[$key]);
			if ('' !== $value) {
				return $value;
			}
		}
	}

	return '';
}

function bbb_book_links_row_has_key(array $row, array $keys): bool {
	foreach ($keys as $key) {
		if (array_key_exists($key, $row)) {
			return true;
		}
	}

	return false;
}

function bbb_ku_links_row(mysqli $mysqli, string $sql): ?array {
	$result = mysqli_query($mysqli, $sql);
	if (!$result) {
		throw new RuntimeException(mysqli_error($mysqli));
	}

	$row = mysqli_fetch_assoc($result);
	return is_array($row) ? $row : null;
}

function bbb_ku_links_update_meta(mysqli $mysqli, string $prefix, int $post_id, string $key, string $value): void {
	$key_sql = mysqli_real_escape_string($mysqli, $key);
	$value_sql = mysqli_real_escape_string($mysqli, $value);
	$table = $prefix . 'postmeta';

	$existing = bbb_ku_links_row(
		$mysqli,
		"SELECT meta_id FROM {$table} WHERE post_id = {$post_id} AND meta_key = '{$key_sql}' ORDER BY meta_id ASC LIMIT 1"
	);

	if ($existing) {
		$meta_id = (int) $existing['meta_id'];
		if (!mysqli_query($mysqli, "UPDATE {$table} SET meta_value = '{$value_sql}' WHERE meta_id = {$meta_id}")) {
			throw new RuntimeException(mysqli_error($mysqli));
		}
		return;
	}

	if (!mysqli_query($mysqli, "INSERT INTO {$table} (post_id, meta_key, meta_value) VALUES ({$post_id}, '{$key_sql}', '{$value_sql}')")) {
		throw new RuntimeException(mysqli_error($mysqli));
	}
}
