<?php
/**
 * Export WordPress posts/pages with links and grouping metadata.
 *
 * Run from the repo root with Local's PHP binary.
 */

$output  = getenv('BBB_EXPORT_OUTPUT') ?: __DIR__ . '/posts-pages-structure-export.csv';
$baseurl = rtrim(getenv('BBB_PUBLIC_BASE_URL') ?: 'https://bybookishbabe.com', '/');

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

$posts_result = $mysqli->query(
	"SELECT p.ID, p.post_type, p.post_status, p.post_title, p.post_name, p.post_parent,
		p.post_date, p.post_modified, COALESCE(pm.meta_value, '') AS page_template
	FROM wp_posts p
	LEFT JOIN wp_postmeta pm
		ON pm.post_id = p.ID
		AND pm.meta_key = '_wp_page_template'
	WHERE p.post_type IN ('post', 'page')
		AND p.post_status IN ('publish', 'future', 'draft', 'pending', 'private')
	ORDER BY p.post_type ASC, p.menu_order ASC, p.post_title ASC"
);

$posts = [];
while ($row = $posts_result->fetch_assoc()) {
	$row['ID']          = (int) $row['ID'];
	$row['post_parent'] = (int) $row['post_parent'];
	$posts[$row['ID']]  = $row;
}

$terms_result = $mysqli->query(
	"SELECT tr.object_id, tt.taxonomy, tx.labels, t.name
	FROM wp_term_relationships tr
	INNER JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
	INNER JOIN wp_terms t ON t.term_id = tt.term_id
	LEFT JOIN (
		SELECT 'category' taxonomy, 'Categories' labels
		UNION SELECT 'post_tag', 'Tags'
	) tx ON tx.taxonomy = tt.taxonomy
	WHERE tr.object_id IN (
		SELECT ID FROM wp_posts
		WHERE post_type IN ('post', 'page')
			AND post_status IN ('publish', 'future', 'draft', 'pending', 'private')
	)
	ORDER BY tt.taxonomy, t.name"
);

$terms_by_post = [];
while ($row = $terms_result->fetch_assoc()) {
	$id       = (int) $row['object_id'];
	$taxonomy = $row['taxonomy'];
	$label    = $row['labels'] ?: ucwords(str_replace(['_', '-'], ' ', $taxonomy));

	$terms_by_post[$id][$taxonomy]['label']   = $label;
	$terms_by_post[$id][$taxonomy]['terms'][] = $row['name'];
}

function bbb_export_page_path(array $post, array $posts): string {
	$parts = [$post['post_name']];
	$seen  = [$post['ID'] => true];

	while (!empty($post['post_parent']) && isset($posts[$post['post_parent']])) {
		$post = $posts[$post['post_parent']];
		if (isset($seen[$post['ID']])) {
			break;
		}

		$parts[] = $post['post_name'];
		$seen[$post['ID']] = true;
	}

	return implode('/', array_reverse(array_filter($parts)));
}

function bbb_export_clean_text(string $value): string {
	return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

$handle = fopen($output, 'w');
if (!$handle) {
	fwrite(STDERR, "Could not open {$output} for writing\n");
	exit(1);
}

fputcsv($handle, [
	'id',
	'type',
	'status',
	'title',
	'current_link',
	'slug',
	'url_path',
	'parent_title',
	'page_template',
	'published_date',
	'modified_date',
	'categories',
	'tags',
	'all_wordpress_groupings',
	'primary_structure_group',
]);

foreach ($posts as $post) {
	$slug = 'page' === $post['post_type'] ? bbb_export_page_path($post, $posts) : $post['post_name'];
	$link = $baseurl . '/' . trim($slug, '/') . '/';
	$path = '/' . trim($slug, '/') . '/';
	$all_groupings = [];
	$terms_by_taxonomy = [];

	foreach (($terms_by_post[$post['ID']] ?? []) as $taxonomy => $data) {
		$names = array_values(array_unique($data['terms']));
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$terms_by_taxonomy[$taxonomy] = $names;
		$all_groupings[] = $data['label'] . ': ' . implode(' | ', $names);
	}

	$categories = $terms_by_taxonomy['category'] ?? [];
	$tags       = $terms_by_taxonomy['post_tag'] ?? [];
	$parent     = $post['post_parent'] ? ($posts[$post['post_parent']] ?? null) : null;
	$template   = 'page' === $post['post_type'] ? ($post['page_template'] ?: 'Default') : '';
	$primary    = '';

	if ('page' === $post['post_type']) {
		$primary = $parent ? 'Child page of: ' . $parent['post_title'] : 'Top-level page';
	} elseif (!empty($categories)) {
		$primary = 'Category: ' . $categories[0];
	} elseif (!empty($tags)) {
		$primary = 'Tag: ' . $tags[0];
	} else {
		$primary = 'Uncategorized post';
	}

	fputcsv($handle, [
		$post['ID'],
		$post['post_type'],
		$post['post_status'],
		bbb_export_clean_text($post['post_title']),
		$link,
		$post['post_name'],
		$path,
		$parent ? bbb_export_clean_text($parent['post_title']) : '',
		$template,
		$post['post_date'],
		$post['post_modified'],
		implode(' | ', $categories),
		implode(' | ', $tags),
		implode('; ', $all_groupings),
		$primary,
	]);
}

fclose($handle);

printf("Exported %d rows to %s\n", count($posts), $output);
