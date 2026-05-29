<?php
/**
 * Copy a file from WP-CLI visible temp storage to the current WordPress root.
 */

$source = $args[0] ?? '';
$dest = $args[1] ?? '';

if (!$source || !$dest || !is_readable($source)) {
	fwrite(STDERR, "Source missing or unreadable: {$source}\n");
	exit(1);
}

if (!copy($source, $dest)) {
	fwrite(STDERR, "Could not copy {$source} to {$dest}\n");
	exit(1);
}

echo "Copied {$source} to {$dest}\n";

