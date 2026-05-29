<?php
/**
 * Apply a prepared Rank Math SEO JSON payload via WP-CLI.
 *
 * Usage:
 *   wp eval-file apply-rank-math-seo-payload-live.php /tmp/payload.json
 *
 * Set BBB_APPLY=1 to write changes. Without it, this is a dry run.
 */

$payload_path = $args[0] ?? getenv('BBB_SEO_PAYLOAD') ?: '';
$apply = '1' === (string) getenv('BBB_APPLY');
$backup_path = getenv('BBB_SEO_BACKUP') ?: '/tmp/bybookishbabe-live-rank-math-seo-backup-' . gmdate('Ymd-His') . '.csv';

if (!$payload_path || !is_readable($payload_path)) {
	fwrite(STDERR, "Payload is missing or unreadable: {$payload_path}\n");
	exit(1);
}

$payload = json_decode((string) file_get_contents($payload_path), true);
if (!is_array($payload)) {
	fwrite(STDERR, "Payload is not valid JSON.\n");
	exit(1);
}

$keys = array(
	'rank_math_title',
	'rank_math_description',
	'rank_math_focus_keyword',
);

$backup = fopen($backup_path, 'w');
if (!$backup) {
	fwrite(STDERR, "Could not write backup: {$backup_path}\n");
	exit(1);
}

fputcsv(
	$backup,
	array(
		'id',
		'post_type',
		'post_status',
		'post_title',
		'post_name',
		'old_rank_math_title',
		'old_rank_math_description',
		'old_rank_math_focus_keyword',
		'new_rank_math_title',
		'new_rank_math_description',
		'new_rank_math_focus_keyword',
	)
);

$checked = 0;
$would_change = 0;
$changed = 0;
$errors = array();
$warnings = array();

foreach ($payload as $row) {
	$id = (int) ($row['id'] ?? 0);
	if ($id <= 0) {
		$errors[] = 'Invalid row with missing ID.';
		continue;
	}

	$post = get_post($id);
	if (!$post) {
		$errors[] = "{$id}: post not found";
		continue;
	}

	$expected_type = (string) ($row['post_type'] ?? '');
	if ($expected_type && $post->post_type !== $expected_type) {
		$errors[] = "{$id}: expected type {$expected_type}, found {$post->post_type}";
		continue;
	}

	$expected_slug = (string) ($row['post_name'] ?? '');
	if ($expected_slug && $post->post_name !== $expected_slug) {
		$warnings[] = "{$id}: slug differs, payload={$expected_slug}, live={$post->post_name}";
	}

	$checked++;
	$old = array();
	foreach ($keys as $key) {
		$old[$key] = (string) get_post_meta($id, $key, true);
	}

	fputcsv(
		$backup,
		array(
			$id,
			$post->post_type,
			$post->post_status,
			$post->post_title,
			$post->post_name,
			$old['rank_math_title'],
			$old['rank_math_description'],
			$old['rank_math_focus_keyword'],
			(string) ($row['rank_math_title'] ?? ''),
			(string) ($row['rank_math_description'] ?? ''),
			(string) ($row['rank_math_focus_keyword'] ?? ''),
		)
	);

	$needs_change = false;
	foreach ($keys as $key) {
		if ($old[$key] !== (string) ($row[$key] ?? '')) {
			$needs_change = true;
			break;
		}
	}

	if (!$needs_change) {
		continue;
	}

	$would_change++;
	if ($apply) {
		foreach ($keys as $key) {
			update_post_meta($id, $key, wp_slash((string) ($row[$key] ?? '')));
		}
		$changed++;
	}
}

fclose($backup);

if ($warnings) {
	fwrite(STDERR, "Warnings:\n" . implode("\n", array_slice($warnings, 0, 50)) . "\n");
	if (count($warnings) > 50) {
		fwrite(STDERR, '... ' . (count($warnings) - 50) . " more warnings\n");
	}
}

if ($errors) {
	fwrite(STDERR, "Errors:\n" . implode("\n", array_slice($errors, 0, 100)) . "\n");
	fwrite(STDERR, "Backup written: {$backup_path}\n");
	exit(1);
}

printf(
	"%s: %d rows checked; %d rows %s; backup: %s\n",
	$apply ? 'APPLIED' : 'DRY RUN',
	$checked,
	$apply ? $changed : $would_change,
	$apply ? 'updated' : 'would update',
	$backup_path
);
