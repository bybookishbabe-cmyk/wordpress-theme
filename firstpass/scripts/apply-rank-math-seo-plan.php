<?php
/**
 * Apply Rank Math SEO fields from the ByBookishBabe SEO plan workbook.
 *
 * This updates a local WordPress database only. Run without BBB_APPLY=1 for a
 * dry run. Set BBB_APPLY=1 to write postmeta.
 */

declare(strict_types=1);

$xlsx_path = getenv('BBB_SEO_PLAN_XLSX') ?: '/Users/autumnmarie/Downloads/bybookishbabe-rank-math-seo-plan.xlsx';
$backup_path = getenv('BBB_SEO_BACKUP') ?: '/Users/autumnmarie/Documents/bybookishbabe-rank-math-seo-backup-' . date('Y-m-d-His') . '.csv';
$apply = '1' === (string) getenv('BBB_APPLY');
$baseurl = rtrim(getenv('BBB_PUBLIC_BASE_URL') ?: 'https://bybookishbabe.com', '/');

if (!is_file($xlsx_path)) {
	fwrite(STDERR, "Workbook not found: {$xlsx_path}\n");
	exit(1);
}

if (!class_exists('ZipArchive')) {
	fwrite(STDERR, "PHP ZipArchive is required to read XLSX files.\n");
	exit(1);
}

$sheet_configs = array(
	'✅ Blog Posts (Done)' => array(
		'expected_type' => 'post',
		'focus'        => 'Focus Keyword',
		'title'        => 'Rank Math Title (60 chars)',
		'description'  => 'Rank Math Description (160 chars)',
	),
	'📄 Pages — Needs SEO' => array(
		'expected_type' => 'page',
		'focus'        => 'Suggested Focus Keyword',
		'title'        => 'Suggested RM Title (60 chars)',
		'description'  => 'Suggested RM Description (160 chars)',
	),
	'📚 Books — Needs SEO' => array(
		'expected_type' => 'bbb_book',
		'focus'        => 'Suggested Focus Keyword',
		'title'        => 'Suggested RM Title (60 chars)',
		'description'  => 'Suggested RM Description (160 chars)',
	),
	'📖 Series — Needs SEO' => array(
		'expected_type' => 'sss_series',
		'focus'        => 'Suggested Focus Keyword',
		'title'        => 'Suggested RM Title (60 chars)',
		'description'  => 'Suggested RM Description (160 chars)',
	),
	'📥 Downloads — Needs SEO' => array(
		'expected_type' => 'download',
		'focus'        => 'Suggested Focus Keyword',
		'title'        => 'Suggested RM Title (60 chars)',
		'description'  => 'Suggested RM Description (160 chars)',
	),
);

function bbb_column_index(string $cell): int {
	$letters = preg_replace('/[^A-Z]/', '', strtoupper($cell)) ?: '';
	$index   = 0;
	for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
		$index = ($index * 26) + (ord($letters[$i]) - 64);
	}
	return $index - 1;
}

function bbb_xlsx_xml(ZipArchive $zip, string $path): SimpleXMLElement {
	$xml = $zip->getFromName($path);
	if (false === $xml) {
		throw new RuntimeException("Missing XLSX part: {$path}");
	}
	return new SimpleXMLElement($xml);
}

function bbb_xlsx_text(SimpleXMLElement $node): string {
	try {
		$dom = dom_import_simplexml($node);
	} catch (ValueError $e) {
		return trim((string) $node);
	}
	return $dom ? (string) $dom->textContent : '';
}

function bbb_xlsx_read_workbook(string $path): array {
	$zip = new ZipArchive();
	if (true !== $zip->open($path)) {
		throw new RuntimeException("Could not open XLSX: {$path}");
	}

	$shared = array();
	if (false !== $zip->locateName('xl/sharedStrings.xml')) {
		$shared_xml = bbb_xlsx_xml($zip, 'xl/sharedStrings.xml');
		foreach ($shared_xml->si as $si) {
			$shared[] = bbb_xlsx_text($si);
		}
	}

	$workbook = bbb_xlsx_xml($zip, 'xl/workbook.xml');
	$rels     = bbb_xlsx_xml($zip, 'xl/_rels/workbook.xml.rels');
	$rel_map  = array();
	foreach ($rels->Relationship as $rel) {
		$attrs = $rel->attributes();
		$target = (string) $attrs['Target'];
		$rel_map[(string) $attrs['Id']] = str_starts_with($target, '/xl/')
			? ltrim($target, '/')
			: 'xl/' . ltrim($target, '/');
	}

	$sheets = array();
	foreach ($workbook->sheets->sheet as $sheet) {
		$attrs = $sheet->attributes();
		$rattrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
		$rid   = (string) ($rattrs['id'] ?? '');
		$name = (string) $attrs['name'];
		$sheet_path = $rel_map[$rid] ?? '';
		if (!$sheet_path) {
			continue;
		}

		$sheet_xml = bbb_xlsx_xml($zip, $sheet_path);
		$rows = array();
		foreach ($sheet_xml->sheetData->row as $row) {
			$row_values = array();
			foreach ($row->c as $cell) {
				$attrs = $cell->attributes();
				$idx   = bbb_column_index((string) $attrs['r']);
				$type  = (string) ($attrs['t'] ?? '');
				$value = '';
				if ('inlineStr' === $type) {
					$value = bbb_xlsx_text($cell->is);
				} elseif ('s' === $type) {
					$shared_idx = (int) $cell->v;
					$value = $shared[$shared_idx] ?? '';
				} elseif (isset($cell->v)) {
					$value = (string) $cell->v;
				}
				$row_values[$idx] = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			}
			if ($row_values) {
				ksort($row_values);
				$rows[] = $row_values;
			}
		}
		$sheets[$name] = $rows;
	}

	$zip->close();
	return $sheets;
}

function bbb_header_map(array $row): array {
	$map = array();
	foreach ($row as $idx => $value) {
		if ('' !== (string) $value) {
			$map[(string) $value] = (int) $idx;
		}
	}
	return $map;
}

function bbb_cell(array $row, array $headers, string $name): string {
	if (!isset($headers[$name])) {
		return '';
	}
	return trim((string) ($row[$headers[$name]] ?? ''));
}

function bbb_meta_trim(string $value, int $limit): string {
	$value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
	if ($limit > 0 && mb_strlen($value) > $limit) {
		return rtrim(mb_substr($value, 0, $limit - 1)) . '…';
	}
	return $value;
}

function bbb_book_library_seo(string $title, string $author): array {
	$title = trim($title);
	$author = trim($author);
	$author_suffix = '' !== $author ? ' ' . $author : '';
	$byline = trim($title . ('' !== $author ? ' by ' . $author : ''));
	$focus = function_exists('mb_strtolower') ? mb_strtolower(trim($title . $author_suffix), 'UTF-8') : strtolower(trim($title . $author_suffix));
	$seo_title = trim($byline . ' | Tropes & Spice');
	if (mb_strlen($seo_title) > 60) {
		$seo_title = $byline;
	}

	$description = trim(
		sprintf(
			'Find %s in the ByBookishBabe library with tropes, spice level, content notes, series info, and where to read it.',
			$byline
		)
	);

	return array(
		'focus'       => bbb_meta_trim($focus, 120),
		'title'       => bbb_meta_trim($seo_title, 60),
		'description' => bbb_meta_trim($description, 160),
	);
}

function bbb_upsert_postmeta(mysqli $mysqli, int $post_id, string $key, string $value): void {
	$select = $mysqli->prepare('SELECT meta_id FROM wp_postmeta WHERE post_id = ? AND meta_key = ? LIMIT 1');
	$select->bind_param('is', $post_id, $key);
	$select->execute();
	$result = $select->get_result();
	$row    = $result ? $result->fetch_assoc() : null;
	$select->close();

	if ($row) {
		$meta_id = (int) $row['meta_id'];
		$update = $mysqli->prepare('UPDATE wp_postmeta SET meta_value = ? WHERE meta_id = ?');
		$update->bind_param('si', $value, $meta_id);
		$update->execute();
		$update->close();
		return;
	}

	$insert = $mysqli->prepare('INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)');
	$insert->bind_param('iss', $post_id, $key, $value);
	$insert->execute();
	$insert->close();
}

$sheets = bbb_xlsx_read_workbook($xlsx_path);
$updates = array();
$errors  = array();

foreach ($sheet_configs as $sheet_name => $config) {
	if (empty($sheets[$sheet_name])) {
		$errors[] = "Missing sheet: {$sheet_name}";
		continue;
	}

	$rows = $sheets[$sheet_name];
	$headers = bbb_header_map($rows[0]);
	foreach (array('ID', $config['focus'], $config['title'], $config['description']) as $required) {
		if (!isset($headers[$required])) {
			$errors[] = "Missing column {$required} in {$sheet_name}";
		}
	}

	for ($i = 1, $count = count($rows); $i < $count; $i++) {
		$row = $rows[$i];
		$id  = (int) bbb_cell($row, $headers, 'ID');
		if ($id <= 0) {
			continue;
		}

		$updates[$id] = array(
			'id'           => $id,
			'sheet'        => $sheet_name,
			'expected'     => $config['expected_type'],
			'focus'        => bbb_cell($row, $headers, $config['focus']),
			'title'        => bbb_cell($row, $headers, $config['title']),
			'description'  => bbb_cell($row, $headers, $config['description']),
		);

		if ('bbb_book' === $config['expected_type']) {
			$book_seo = bbb_book_library_seo(
				bbb_cell($row, $headers, 'Book Title'),
				bbb_cell($row, $headers, 'Author')
			);
			$updates[$id]['focus']       = $book_seo['focus'];
			$updates[$id]['title']       = $book_seo['title'];
			$updates[$id]['description'] = $book_seo['description'];
		}
	}
}

if ($errors) {
	fwrite(STDERR, implode("\n", $errors) . "\n");
	exit(1);
}

$mysqli = mysqli_init();
$socket = getenv('BBB_DB_SOCKET') ?: '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
$mysqli->real_connect(
	getenv('BBB_DB_HOST') ?: 'localhost',
	getenv('BBB_DB_USER') ?: 'root',
	getenv('BBB_DB_PASSWORD') ?: 'root',
	getenv('BBB_DB_NAME') ?: 'local',
	(int) (getenv('BBB_DB_PORT') ?: 10004),
	$socket
);
$mysqli->set_charset('utf8mb4');

$ids = array_keys($updates);
sort($ids, SORT_NUMERIC);
$id_sql = implode(',', array_map('intval', $ids));
$posts_result = $mysqli->query("SELECT ID, post_type, post_status, post_title, post_name FROM wp_posts WHERE ID IN ({$id_sql})");
$posts = array();
while ($post = $posts_result->fetch_assoc()) {
	$posts[(int) $post['ID']] = $post;
}

$meta_result = $mysqli->query(
	"SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE post_id IN ({$id_sql}) AND meta_key IN ('rank_math_title','rank_math_description','rank_math_focus_keyword')"
);
$old_meta = array();
while ($meta = $meta_result->fetch_assoc()) {
	$old_meta[(int) $meta['post_id']][(string) $meta['meta_key']] = (string) $meta['meta_value'];
}

$backup = fopen($backup_path, 'w');
if (!$backup) {
	fwrite(STDERR, "Could not write backup CSV: {$backup_path}\n");
	exit(1);
}
fputcsv($backup, array('id', 'post_type', 'post_status', 'post_title', 'post_name', 'old_rank_math_title', 'old_rank_math_description', 'old_rank_math_focus_keyword', 'new_rank_math_title', 'new_rank_math_description', 'new_rank_math_focus_keyword'));

$changed = 0;
$skipped = 0;
$type_mismatches = array();
$missing = array();

foreach ($ids as $id) {
	$update = $updates[$id];
	$post   = $posts[$id] ?? null;
	if (!$post) {
		$missing[] = $id;
		$skipped++;
		continue;
	}
	if ($post['post_type'] !== $update['expected']) {
		$type_mismatches[] = "{$id}: expected {$update['expected']}, found {$post['post_type']}";
		$skipped++;
		continue;
	}

	$old = $old_meta[$id] ?? array();
	fputcsv($backup, array(
		$id,
		$post['post_type'],
		$post['post_status'],
		$post['post_title'],
		$post['post_name'],
		$old['rank_math_title'] ?? '',
		$old['rank_math_description'] ?? '',
		$old['rank_math_focus_keyword'] ?? '',
		$update['title'],
		$update['description'],
		$update['focus'],
	));

	$needs_change = ($old['rank_math_title'] ?? '') !== $update['title']
		|| ($old['rank_math_description'] ?? '') !== $update['description']
		|| ($old['rank_math_focus_keyword'] ?? '') !== $update['focus'];

	if (!$needs_change) {
		continue;
	}

	$changed++;
	if ($apply) {
		bbb_upsert_postmeta($mysqli, $id, 'rank_math_title', $update['title']);
		bbb_upsert_postmeta($mysqli, $id, 'rank_math_description', $update['description']);
		bbb_upsert_postmeta($mysqli, $id, 'rank_math_focus_keyword', $update['focus']);
	}
}

fclose($backup);

if ($type_mismatches || $missing) {
	fwrite(STDERR, "Skipped {$skipped} rows.\n");
	if ($missing) {
		fwrite(STDERR, "Missing post IDs: " . implode(', ', $missing) . "\n");
	}
	if ($type_mismatches) {
		fwrite(STDERR, "Type mismatches:\n" . implode("\n", $type_mismatches) . "\n");
	}
	exit(1);
}

printf(
	"%s: %d workbook rows checked; %d rows %s; backup: %s\n",
	$apply ? 'APPLIED' : 'DRY RUN',
	count($updates),
	$changed,
	$apply ? 'updated' : 'would update',
	$backup_path
);
