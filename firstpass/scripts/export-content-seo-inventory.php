<?php
/**
 * Export ByBookishBabe WordPress content inventory for SEO planning.
 *
 * This is a read-only local database export. It does not write to WordPress.
 */

declare(strict_types=1);

$output  = getenv('BBB_EXPORT_OUTPUT') ?: __DIR__ . '/bybookishbabe-content-seo-inventory.csv';
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

$post_types = array(
	'post',
	'page',
	'bbb_book',
	'sss_book',
	'sss_series',
	'newsletter_issue',
	'bbb_newsletter_issue',
	'sss_quote',
	'bbb_quote',
	'download',
	'sss_drop',
);

$type_labels = array(
	'post'                 => 'Blog post',
	'page'                 => 'Page',
	'bbb_book'             => 'Book',
	'sss_book'             => 'Private/member book',
	'sss_series'           => 'Series',
	'newsletter_issue'     => 'Newsletter issue',
	'bbb_newsletter_issue' => 'Legacy newsletter issue',
	'sss_quote'            => 'Quote',
	'bbb_quote'            => 'Legacy quote',
	'download'             => 'Digital product/download',
	'sss_drop'             => 'Society drop',
);

$seo_keys = array(
	'rank_math_title',
	'rank_math_description',
	'rank_math_focus_keyword',
	'rank_math_seo_score',
);

$meta_keys = array_merge(
	$seo_keys,
	array(
		'author',
		'sss_author',
		'_bbb_author',
		'_bbb_series_handle',
		'_bbb_series_number',
		'series_number',
		'sss_series_handle',
		'sss_series_number',
		'_bbb_series_author',
		'_bbb_series_books_in_series',
		'_bbb_series_book_handles',
		'_bbb_series_linked_blog_post_url',
		'_bbb_newsletter_url',
		'newsletter_url',
		'sss_newsletter',
		'issue_url',
		'_issue_publish_date',
		'publish_date',
		'_issue_subtitle',
		'_issue_book_handle',
		'_issue_book_title',
		'_bbb_book_handle',
		'_bbb_book_title',
		'_quote_book_handle',
		'_quote_book_title',
		'_bbb_quote',
		'_quote_text',
		'quote_text',
		'quote',
		'book_title',
		'book_handle',
		'sss_shelf',
		'_bbb_shelf_handle',
		'hide_from_library',
		'_bbb_hide_from_library',
		'is_private',
		'sss_is_private',
	)
);

$post_type_sql = "'" . implode("','", array_map([$mysqli, 'real_escape_string'], $post_types)) . "'";
$posts_result  = $mysqli->query(
	"SELECT ID, post_type, post_status, post_title, post_name, post_parent, post_date, post_modified, post_excerpt, post_content, menu_order
	FROM wp_posts
	WHERE post_type IN ({$post_type_sql})
		AND post_status IN ('publish', 'future', 'draft', 'pending', 'private')
	ORDER BY FIELD(post_type, {$post_type_sql}), post_title ASC, ID ASC"
);

$posts = array();
while ($row = $posts_result->fetch_assoc()) {
	$row['ID']          = (int) $row['ID'];
	$row['post_parent'] = (int) $row['post_parent'];
	$posts[$row['ID']]  = $row;
}

$post_ids = array_keys($posts);
if (!$post_ids) {
	fwrite(STDERR, "No posts found.\n");
	exit(1);
}

$id_sql       = implode(',', array_map('intval', $post_ids));
$meta_key_sql = "'" . implode("','", array_map([$mysqli, 'real_escape_string'], $meta_keys)) . "'";

$meta_result = $mysqli->query(
	"SELECT post_id, meta_key, meta_value
	FROM wp_postmeta
	WHERE post_id IN ({$id_sql})
		AND meta_key IN ({$meta_key_sql})
	ORDER BY meta_id ASC"
);

$meta_by_post = array();
while ($row = $meta_result->fetch_assoc()) {
	$id  = (int) $row['post_id'];
	$key = (string) $row['meta_key'];
	if (!isset($meta_by_post[$id][$key])) {
		$meta_by_post[$id][$key] = (string) $row['meta_value'];
	}
}

$terms_result = $mysqli->query(
	"SELECT tr.object_id, tt.taxonomy, t.name
	FROM wp_term_relationships tr
	INNER JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
	INNER JOIN wp_terms t ON t.term_id = tt.term_id
	WHERE tr.object_id IN ({$id_sql})
	ORDER BY tt.taxonomy, t.name"
);

$terms_by_post = array();
while ($row = $terms_result->fetch_assoc()) {
	$id       = (int) $row['object_id'];
	$taxonomy = (string) $row['taxonomy'];
	$terms_by_post[$id][$taxonomy][] = (string) $row['name'];
}

function bbb_inventory_clean_text(string $value, int $limit = 0): string {
	$value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$value = preg_replace('/\s+/u', ' ', $value) ?? $value;
	$value = trim($value);

	if ($limit > 0 && mb_strlen($value) > $limit) {
		$value = mb_substr($value, 0, $limit - 1) . '…';
	}

	return $value;
}

function bbb_inventory_first_meta(array $meta, array $keys): string {
	foreach ($keys as $key) {
		$value = trim((string) ($meta[$key] ?? ''));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_inventory_page_path(array $post, array $posts): string {
	$parts = array($post['post_name']);
	$seen  = array($post['ID'] => true);

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

function bbb_inventory_url_path(array $post, array $posts): string {
	$slug = trim((string) $post['post_name'], '/');
	if ('' === $slug) {
		return '';
	}

	switch ($post['post_type']) {
		case 'page':
			return '/' . trim(bbb_inventory_page_path($post, $posts), '/') . '/';
		case 'bbb_book':
			return '/books/' . $slug . '/';
		case 'sss_series':
			return '/series/' . $slug . '/';
		case 'sss_quote':
			return '/quotes/' . $slug . '/';
		case 'download':
			return '/downloads/' . $slug . '/';
		case 'post':
			return '/' . $slug . '/';
		default:
			return '';
	}
}

function bbb_inventory_meta_bool(string $value): string {
	$value = strtolower(trim($value));
	if (in_array($value, array('1', 'true', 'yes', 'on'), true)) {
		return 'yes';
	}
	if (in_array($value, array('0', 'false', 'no', 'off'), true)) {
		return 'no';
	}
	return '';
}

$handle = fopen($output, 'w');
if (!$handle) {
	fwrite(STDERR, "Could not open {$output} for writing\n");
	exit(1);
}

fputcsv(
	$handle,
	array(
		'id',
		'wordpress_post_type',
		'wordpress_type_label',
		'status',
		'title',
		'slug',
		'public_url',
		'source_or_external_url',
		'published_date',
		'modified_date',
		'author_or_creator',
		'series_name_or_handle',
		'series_number',
		'shelf_or_category',
		'tropes_or_tags',
		'linked_book',
		'newsletter_issue_date',
		'quote_text',
		'content_preview',
		'rank_math_title',
		'rank_math_description',
		'rank_math_focus_keyword',
		'rank_math_seo_score',
		'all_wordpress_terms',
		'hidden_from_library',
		'private_or_member_only',
		'seo_notes',
	)
);

foreach ($posts as $post) {
	$id    = (int) $post['ID'];
	$meta  = $meta_by_post[$id] ?? array();
	$terms = $terms_by_post[$id] ?? array();

	foreach ($terms as $taxonomy => $names) {
		$names = array_values(array_unique($names));
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$terms[$taxonomy] = $names;
	}

	$url_path = bbb_inventory_url_path($post, $posts);
	$public_url = '' !== $url_path ? $baseurl . $url_path : '';
	$external_url = bbb_inventory_first_meta(
		$meta,
		array('_bbb_newsletter_url', 'issue_url', 'newsletter_url', 'sss_newsletter', '_bbb_series_linked_blog_post_url')
	);

	$all_terms = array();
	foreach ($terms as $taxonomy => $names) {
		$all_terms[] = $taxonomy . ': ' . implode(' | ', $names);
	}

	$categories = array_merge($terms['category'] ?? array(), $terms['download_category'] ?? array());
	$shelves    = array_merge($terms['bbb_shelf'] ?? array(), $terms['sss_shelf'] ?? array());
	$tropes     = array_merge($terms['bbb_trope'] ?? array(), $terms['sss_trope'] ?? array(), $terms['post_tag'] ?? array());
	$series     = array_merge($terms['bbb_series'] ?? array(), $terms['sss_series'] ?? array());

	$author = bbb_inventory_first_meta($meta, array('author', 'sss_author', '_bbb_author', '_bbb_series_author'));
	$series_value = bbb_inventory_first_meta($meta, array('_bbb_series_handle', 'sss_series_handle'));
	if ('' === $series_value && $series) {
		$series_value = implode(' | ', $series);
	}

	$quote_text = bbb_inventory_first_meta($meta, array('_bbb_quote', '_quote_text', 'quote_text', 'quote'));
	if ('' === $quote_text && in_array($post['post_type'], array('sss_quote', 'bbb_quote'), true)) {
		$quote_text = bbb_inventory_clean_text((string) $post['post_content'], 300);
	}

	$preview_source = '' !== (string) $post['post_excerpt'] ? (string) $post['post_excerpt'] : (string) $post['post_content'];
	$seo_notes = array();
	if ('' === trim((string) ($meta['rank_math_title'] ?? ''))) {
		$seo_notes[] = 'missing Rank Math title';
	}
	if ('' === trim((string) ($meta['rank_math_description'] ?? ''))) {
		$seo_notes[] = 'missing Rank Math description';
	}
	if ('' === $public_url && '' === $external_url) {
		$seo_notes[] = 'no public URL detected';
	}

	fputcsv(
		$handle,
		array(
			$id,
			$post['post_type'],
			$type_labels[$post['post_type']] ?? $post['post_type'],
			$post['post_status'],
			bbb_inventory_clean_text((string) $post['post_title']),
			$post['post_name'],
			$public_url,
			$external_url,
			$post['post_date'],
			$post['post_modified'],
			$author,
			$series_value,
			bbb_inventory_first_meta($meta, array('_bbb_series_number', 'series_number', 'sss_series_number')),
			implode(' | ', array_filter(array_merge($shelves, $categories, array(bbb_inventory_first_meta($meta, array('sss_shelf', '_bbb_shelf_handle')))))),
			implode(' | ', array_values(array_unique($tropes))),
			bbb_inventory_first_meta($meta, array('_issue_book_title', '_bbb_book_title', '_quote_book_title', 'book_title', '_issue_book_handle', '_bbb_book_handle', '_quote_book_handle', 'book_handle')),
			bbb_inventory_first_meta($meta, array('_issue_publish_date', 'publish_date')),
			$quote_text,
			bbb_inventory_clean_text($preview_source, 320),
			(string) ($meta['rank_math_title'] ?? ''),
			(string) ($meta['rank_math_description'] ?? ''),
			(string) ($meta['rank_math_focus_keyword'] ?? ''),
			(string) ($meta['rank_math_seo_score'] ?? ''),
			implode('; ', $all_terms),
			bbb_inventory_meta_bool(bbb_inventory_first_meta($meta, array('hide_from_library', '_bbb_hide_from_library'))),
			bbb_inventory_meta_bool(bbb_inventory_first_meta($meta, array('is_private', 'sss_is_private'))),
			implode('; ', $seo_notes),
		)
	);
}

fclose($handle);

printf("Exported %d rows to %s\n", count($posts), $output);
