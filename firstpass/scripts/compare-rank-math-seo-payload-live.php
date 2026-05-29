<?php
/**
 * Compare a prepared Rank Math SEO JSON payload against live postmeta.
 */

$payload_path = $args[0] ?? '';
if (!$payload_path || !is_readable($payload_path)) {
	fwrite(STDERR, "Payload is missing or unreadable: {$payload_path}\n");
	exit(1);
}

$payload = json_decode((string) file_get_contents($payload_path), true);
$keys = array('rank_math_title', 'rank_math_description', 'rank_math_focus_keyword');
$diffs = array();

foreach ($payload as $row) {
	$id = (int) ($row['id'] ?? 0);
	if (!$id || !get_post($id)) {
		continue;
	}
	foreach ($keys as $key) {
		$old = (string) get_post_meta($id, $key, true);
		$new = (string) ($row[$key] ?? '');
		if ($old !== $new) {
			$diffs[] = array(
				'id' => $id,
				'key' => $key,
				'old' => $old,
				'new' => $new,
				'old_hex' => bin2hex($old),
				'new_hex' => bin2hex($new),
			);
		}
	}
}

echo wp_json_encode(array_slice($diffs, 0, 60), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
fprintf(STDERR, "Diff count: %d\n", count($diffs));

