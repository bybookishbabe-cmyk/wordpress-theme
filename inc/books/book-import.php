<?php
/**
 * WP-CLI importer for Shopify sss_library metaobject exports.
 *
 * Usage: wp bbb import-books --file=books.json
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_import_bool_to_meta($value): string {
	if (is_bool($value)) {
		return $value ? '1' : '0';
	}

	return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true) ? '1' : '0';
}

function bbb_import_field_title(array $fields, string $fallback): string {
	return (string) ($fields['title']['value'] ?? $fields['name']['value'] ?? $fallback);
}

function bbb_import_metaobject_field_value(array $fields, string $key): string {
	return isset($fields[$key]['value']) ? (string) $fields[$key]['value'] : '';
}

function bbb_import_metaobject_fields_map(array $reference): array {
	$map = array();

	foreach (($reference['fields'] ?? array()) as $field) {
		if (is_array($field) && isset($field['key'])) {
			$map[(string) $field['key']] = $field;
		}
	}

	return $map;
}

function bbb_import_field_reference_text(array $fields, string $key, array $reference_keys = array('name', 'title')): string {
	$reference = $fields[$key]['reference'] ?? null;

	if (is_array($reference)) {
		$reference_fields = bbb_import_metaobject_fields_map($reference);
		foreach ($reference_keys as $reference_key) {
			$value = $reference_fields[$reference_key]['value'] ?? '';
			if (is_string($value) && trim($value) !== '') {
				return trim($value);
			}
		}

		foreach ($reference_keys as $reference_key) {
			$value = $reference[$reference_key] ?? '';
			if (is_string($value) && trim($value) !== '') {
				return trim($value);
			}
		}
	}

	$value = $fields[$key]['value'] ?? '';
	if (is_string($value) && trim($value) !== '' && strpos($value, 'gid://') !== 0) {
		return trim($value);
	}

	return '';
}

function bbb_import_books_edges_from_export(array $data): array {
	if (isset($data['data']['metaobjects']['edges']) && is_array($data['data']['metaobjects']['edges'])) {
		return $data['data']['metaobjects']['edges'];
	}

	if (isset($data['data']['metaobjects']['nodes']) && is_array($data['data']['metaobjects']['nodes'])) {
		return array_map(
			static fn(array $node): array => array('node' => $node),
			$data['data']['metaobjects']['nodes']
		);
	}

	if (isset($data['edges']) && is_array($data['edges'])) {
		return $data['edges'];
	}

	if (isset($data['nodes']) && is_array($data['nodes'])) {
		return array_map(
			static fn(array $node): array => array('node' => $node),
			$data['nodes']
		);
	}

	if (isset($data['metaobjects']) && is_array($data['metaobjects'])) {
		return bbb_import_books_edges_from_export($data['metaobjects']);
	}

	if (isset($data['handle'], $data['fields'])) {
		return array(array('node' => $data));
	}

	$edges = array();
	foreach ($data as $page) {
		if (!is_array($page)) {
			continue;
		}

		$edges = array_merge($edges, bbb_import_books_edges_from_export($page));
	}

	return $edges;
}

function bbb_import_metaobject_edges_from_export(array $data): array {
	return bbb_import_books_edges_from_export($data);
}

function bbb_import_is_list(array $data): bool {
	if (function_exists('array_is_list')) {
		return array_is_list($data);
	}

	$expected = 0;
	foreach (array_keys($data) as $key) {
		if ($key !== $expected++) {
			return false;
		}
	}

	return true;
}

function bbb_import_field_reference_handle(array $fields, string $key): string {
	$reference = $fields[$key]['reference'] ?? null;
	if (is_array($reference) && !empty($reference['handle'])) {
		return (string) $reference['handle'];
	}

	$value = $fields[$key]['value'] ?? '';
	return is_string($value) && strpos($value, 'gid://') !== 0 ? sanitize_title($value) : '';
}

function bbb_import_newsletter_issue_url(array $fields): string {
	foreach (array('url', 'issue_url', 'newsletter_url') as $key) {
		$value = bbb_import_metaobject_field_value($fields, $key);
		if ('' !== $value) {
			return function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($value) : $value;
		}
	}

	return '';
}

function bbb_import_newsletter_preview_image(array $fields): array {
	foreach (array('preview', 'preview_image', 'image') as $key) {
		$reference = $fields[$key]['reference'] ?? null;
		if (is_array($reference)) {
			$image = $reference['image'] ?? null;
			if (is_array($image) && !empty($image['url'])) {
				return array(
					'url' => (string) $image['url'],
					'alt' => isset($image['altText']) ? (string) $image['altText'] : '',
				);
			}

			foreach (array('url', 'previewUrl', 'originalSource') as $reference_key) {
				if (!empty($reference[$reference_key]) && is_string($reference[$reference_key])) {
					return array(
						'url' => $reference[$reference_key],
						'alt' => '',
					);
				}
			}
		}

		$value = bbb_import_metaobject_field_value($fields, $key);
		if (filter_var($value, FILTER_VALIDATE_URL)) {
			return array(
				'url' => $value,
				'alt' => '',
			);
		}
	}

	return array('url' => '', 'alt' => '');
}

function bbb_import_normalize_date($value): string {
	if (!is_scalar($value)) {
		return '';
	}

	$raw = trim((string) $value);
	if ('' === $raw) {
		return '';
	}

	if (preg_match('/^\d{8}$/', $raw)) {
		return substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
	}

	if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $matches)) {
		return $matches[1];
	}

	$timestamp = strtotime($raw);
	if (false === $timestamp) {
		return '';
	}

	return wp_date('Y-m-d', $timestamp);
}

function bbb_import_wp_datetime($value, string $date_only_time = '10:00:00'): array {
	if (!is_scalar($value)) {
		return array('', '');
	}

	$raw = trim((string) $value);
	if ('' === $raw) {
		return array('', '');
	}

	$wp_tz = wp_timezone();
	$la_tz = new DateTimeZone('America/Los_Angeles');

	try {
		if (preg_match('/^\d{8}$/', $raw)) {
			$raw = substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
		}

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
			$dt = new DateTimeImmutable($raw . ' ' . $date_only_time, $la_tz);
		} else {
			$dt = new DateTimeImmutable($raw);
		}
	} catch (Exception $e) {
		return array('', '');
	}

	$local = $dt->setTimezone($wp_tz);
	$gmt   = $dt->setTimezone(new DateTimeZone('UTC'));

	return array(
		$local->format('Y-m-d H:i:s'),
		$gmt->format('Y-m-d H:i:s'),
	);
}

function bbb_import_newsletter_book_handle(array $fields): string {
	foreach (
		array(
			'book',
			'library_book',
			'featured_book',
			'featured_library_book',
			'obsession_book',
			'weekly_obsession_book',
		) as $key
	) {
		$handle = bbb_import_field_reference_handle($fields, $key);
		if ('' !== $handle) {
			return $handle;
		}
	}

	foreach (array('book_handle', 'library_book_handle', 'featured_book_handle') as $key) {
		$value = sanitize_title(bbb_import_metaobject_field_value($fields, $key));
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_import_blog_article_groups_from_export(array $data): array {
	if (isset($data['articles']) && is_array($data['articles'])) {
		return array(array('blog' => $data['blog'] ?? array(), 'articles' => $data['articles']));
	}

	if (isset($data['data']['blogs']['edges']) && is_array($data['data']['blogs']['edges'])) {
		$groups = array();
		foreach ($data['data']['blogs']['edges'] as $blog_edge) {
			$blog     = $blog_edge['node'] ?? array();
			$articles = array();
			foreach (($blog['articles']['edges'] ?? array()) as $article_edge) {
				if (isset($article_edge['node']) && is_array($article_edge['node'])) {
					$articles[] = $article_edge['node'];
				}
			}
			$groups[] = array('blog' => $blog, 'articles' => $articles);
		}

		return $groups;
	}

	$is_group_list = bbb_import_is_list($data) && isset($data[0]) && is_array($data[0]) && isset($data[0]['articles']);
	if ($is_group_list) {
		return $data;
	}

	if (bbb_import_is_list($data) && isset($data[0]) && is_array($data[0]) && (isset($data[0]['handle']) || isset($data[0]['slug']))) {
		return array(array('blog' => array(), 'articles' => $data));
	}

	return array();
}

function bbb_import_blog_post_dates_from_data(array $data, ?callable $logger = null): array {
	$groups   = bbb_import_blog_article_groups_from_export($data);
	$count    = 0;
	$missing  = 0;
	$messages = array();
	$log      = static function (string $message) use (&$messages, $logger): void {
		$messages[] = $message;
		if ($logger) {
			$logger($message);
		}
	};

	foreach ($groups as $group) {
		foreach (($group['articles'] ?? array()) as $article) {
			if (!is_array($article)) {
				continue;
			}

			$handle = sanitize_title((string) ($article['handle'] ?? $article['slug'] ?? ''));
			if ('' === $handle) {
				continue;
			}

			$post = get_page_by_path($handle, OBJECT, 'post');
			if (!$post instanceof WP_Post) {
				++$missing;
				$log('Skipped missing blog post: ' . $handle);
				continue;
			}

			$published = (string) ($article['published_at'] ?? $article['publishedAt'] ?? $article['created_at'] ?? $article['createdAt'] ?? '');
			$modified  = (string) ($article['updated_at'] ?? $article['updatedAt'] ?? $published);
			[$post_date, $post_date_gmt]         = bbb_import_wp_datetime($published, '00:00:00');
			[$modified_date, $modified_date_gmt] = bbb_import_wp_datetime($modified, '00:00:00');

			if ('' === $post_date) {
				$log('Skipped blog post without a valid date: ' . $handle);
				continue;
			}

			$postarr = array(
				'ID'            => $post->ID,
				'post_date'     => $post_date,
				'post_date_gmt' => $post_date_gmt,
			);

			if ('' !== $modified_date) {
				$postarr['post_modified']     = $modified_date;
				$postarr['post_modified_gmt'] = $modified_date_gmt;
			}

			$updated = wp_update_post($postarr, true);
			if (is_wp_error($updated)) {
				$log('Failed blog date update: ' . $handle . ' - ' . $updated->get_error_message());
				continue;
			}

			update_post_meta((int) $post->ID, '_shopify_published_at', $published);
			if ('' !== $modified) {
				update_post_meta((int) $post->ID, '_shopify_updated_at', $modified);
			}

			++$count;
			$log('Updated blog date: ' . $handle . ' → ' . $post_date);
		}
	}

	if (0 === $count) {
		$log('No blog post dates were updated. Upload the Shopify blog-articles.json export from the content export.');
	}
	if ($missing > 0) {
		array_unshift($messages, sprintf('%d exported blog posts were not found in WordPress by slug.', $missing));
	}

	return array(
		'count'    => $count,
		'messages' => $messages,
	);
}

function bbb_import_series_book_refs(array $fields, string $series_handle = ''): array {
	$handles = array();
	$ids     = array();
	$edges   = $fields['sss_books']['references']['edges'] ?? array();

	foreach ($edges as $edge) {
		$node   = is_array($edge) ? ($edge['node'] ?? array()) : array();
		$handle = is_array($node) ? sanitize_title((string) ($node['handle'] ?? '')) : '';
		if ('' === $handle) {
			continue;
		}

		$handles[] = $handle;
		$book      = get_page_by_path($handle, OBJECT, array('bbb_book', 'sss_book'));
		if ($book instanceof WP_Post) {
			$ids[] = (string) $book->ID;
		}
	}

	if (!$handles && '' !== $series_handle) {
		$books = get_posts(
			array(
				'post_type'      => array('bbb_book', 'sss_book'),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => '_bbb_series_number',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_bbb_series_handle',
						'value' => $series_handle,
					),
				),
			)
		);

		foreach ($books as $book) {
			if (!$book instanceof WP_Post) {
				continue;
			}

			$handles[] = (string) $book->post_name;
			$ids[]     = (string) $book->ID;
		}
	}

	return array(
		'handles' => array_values(array_unique($handles)),
		'ids'     => array_values(array_unique($ids)),
	);
}

function bbb_import_series_from_data(array $data, ?callable $logger = null): array {
	$entries  = bbb_import_metaobject_edges_from_export($data);
	$count    = 0;
	$messages = array();

	$log = static function (string $message) use (&$messages, $logger): void {
		$messages[] = $message;
		if ($logger) {
			$logger($message);
		}
	};

	foreach ($entries as $edge) {
		$node = $edge['node'] ?? array();
		if (!is_array($node)) {
			continue;
		}

		$handle = sanitize_title((string) ($node['handle'] ?? ''));
		if ('' === $handle) {
			$log('Skipped a series without a handle.');
			continue;
		}

		$fields = bbb_import_metaobject_fields_map($node);
		$title  = bbb_import_field_title($fields, (string) ($node['displayName'] ?? $handle));

		$existing = get_page_by_path($handle, OBJECT, 'sss_series');
		$post_id  = $existing instanceof WP_Post
			? wp_update_post(
				array(
					'ID'           => $existing->ID,
					'post_title'   => $title,
					'post_name'    => $handle,
					'post_content' => bbb_import_metaobject_field_value($fields, 'description'),
				),
				true
			)
			: wp_insert_post(
				array(
					'post_type'    => 'sss_series',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $handle,
					'post_content' => bbb_import_metaobject_field_value($fields, 'description'),
				),
				true
			);

		if (is_wp_error($post_id)) {
			$log('Failed: ' . $handle . ' - ' . $post_id->get_error_message());
			continue;
		}

		$book_refs = bbb_import_series_book_refs($fields, $handle);
		$article   = $fields['linked_blog_post']['reference'] ?? array();
		$article_handle = is_array($article) ? sanitize_title((string) ($article['handle'] ?? '')) : '';
		$article_url    = '' !== $article_handle ? home_url('/' . $article_handle . '/') : '';
		$article_post   = '' !== $article_handle ? get_page_by_path($article_handle, OBJECT, 'post') : null;
		if ($article_post instanceof WP_Post) {
			$permalink = get_permalink($article_post);
			if ($permalink) {
				$article_url = $permalink;
			}
		}

		update_post_meta((int) $post_id, '_bbb_series_handle', $handle);
		update_post_meta((int) $post_id, '_bbb_series_shopify_id', (string) ($node['id'] ?? ''));
		update_post_meta((int) $post_id, '_bbb_series_shopify_updated_at', (string) ($node['updatedAt'] ?? ''));
		update_post_meta((int) $post_id, '_bbb_series_author', bbb_import_metaobject_field_value($fields, 'author'));
		$books_in_series = bbb_import_metaobject_field_value($fields, 'books_in_series');
		update_post_meta((int) $post_id, '_bbb_series_books_in_series', '' !== $books_in_series ? absint($books_in_series) : count($book_refs['handles']));
		update_post_meta((int) $post_id, '_bbb_series_book_handles', implode("\n", $book_refs['handles']));
		update_post_meta((int) $post_id, '_bbb_series_book_ids', implode("\n", $book_refs['ids']));
		update_post_meta((int) $post_id, '_bbb_series_linked_blog_post_handle', $article_handle);
		update_post_meta((int) $post_id, '_bbb_series_linked_blog_post_url', $article_url);
		update_post_meta((int) $post_id, '_bbb_series_shopify_entry_json', wp_json_encode($node, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

		if (taxonomy_exists('bbb_series')) {
			$term = get_term_by('slug', $handle, 'bbb_series');
			if (!$term) {
				wp_insert_term($title, 'bbb_series', array('slug' => $handle));
			} elseif ($term instanceof WP_Term && $term->name !== $title) {
				wp_update_term($term->term_id, 'bbb_series', array('name' => $title));
			}
		}

		++$count;
	}

	return array(
		'count'    => $count,
		'messages' => array_merge(array(sprintf('Series import processed %d entries.', $count)), $messages),
	);
}

function bbb_import_assign_book_shelf(int $post_id, array $fields): bool {
	$shelf_name   = bbb_import_field_reference_text($fields, 'shelf', array('name', 'title'));
	$shelf_handle = bbb_import_field_reference_handle($fields, 'shelf');

	if ('' === $shelf_name && '' !== $shelf_handle) {
		$shelf_name = $shelf_handle;
	}

	if ('' === $shelf_name) {
		return false;
	}

	if ('' === $shelf_handle) {
		$shelf_handle = sanitize_title($shelf_name);
	}

	update_post_meta($post_id, '_bbb_shelf_name', $shelf_name);
	update_post_meta($post_id, '_bbb_shelf_handle', $shelf_handle);

	$term = get_term_by('slug', $shelf_handle, 'bbb_shelf');
	if (!$term) {
		$inserted = wp_insert_term($shelf_name, 'bbb_shelf', array('slug' => $shelf_handle));
		if (!is_wp_error($inserted)) {
			$term = get_term((int) $inserted['term_id'], 'bbb_shelf');
		}
	}

	if ($term instanceof WP_Term) {
		wp_set_object_terms($post_id, (int) $term->term_id, 'bbb_shelf');
		return true;
	}

	wp_set_object_terms($post_id, $shelf_name, 'bbb_shelf');
	return true;
}

function bbb_import_books_from_data(array $data, ?callable $logger = null): array {
	$books    = bbb_import_books_edges_from_export($data);
	$count    = 0;
	$shelves  = 0;
	$missing_shelves = 0;
	$messages = array();
	$log      = static function (string $message) use (&$messages, $logger): void {
		$messages[] = $message;
		if ($logger) {
			$logger($message);
		}
	};

	foreach ($books as $edge) {
		$node   = $edge['node'] ?? array();
		$handle = (string) ($node['handle'] ?? '');
		if ($handle === '') {
			$log('Skipped a book without a handle.');
			continue;
		}

		$fields = array();
		foreach (($node['fields'] ?? array()) as $field) {
			if (isset($field['key'])) {
				$fields[$field['key']] = $field;
			}
		}

		$title    = bbb_import_field_title($fields, $handle);
		$existing = get_page_by_path($handle, OBJECT, 'bbb_book');
		$post_id  = $existing instanceof WP_Post
			? wp_update_post(
				array(
					'ID'         => $existing->ID,
					'post_title' => $title,
					'post_name'  => $handle,
				),
				true
			)
			: wp_insert_post(
				array(
					'post_type'   => 'bbb_book',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => $handle,
				),
				true
			);

		if (is_wp_error($post_id)) {
			$log('Failed: ' . $handle . ' - ' . $post_id->get_error_message());
			continue;
		}

		$meta_map = array(
			'author'                 => '_bbb_author',
			'spice_level'            => '_bbb_spice',
			'tension_score'          => '_bbb_tension',
			'emotional_damage_score' => '_bbb_damage',
			'yearning_level'         => '_bbb_yearning',
			'boyfriend_type'         => '_bbb_boyfriend_type',
			'boyfriend_name'         => '_bbb_boyfriend_name',
			'reread_badge'           => '_bbb_reread',
			'darkness_level'         => '_bbb_darkness',
			'mini_note'              => '_bbb_mini_note',
			'why_i_loved_it'         => '_bbb_why',
			'series_number'          => '_bbb_series_number',
		);

		foreach ($meta_map as $shopify_key => $wp_meta) {
			if (isset($fields[$shopify_key]['value'])) {
				update_post_meta((int) $post_id, $wp_meta, $fields[$shopify_key]['value']);
			}
		}

		foreach (
			array(
				'on_kindle_unlimited' => '_bbb_ku',
				'read_as_standalone'  => '_bbb_standalone',
				'hide_from_library'   => '_bbb_hide_from_library',
				'private_shelf'       => '_bbb_private_shelf',
				'top_shelf'           => '_bbb_top_shelf',
				'starter_pack'        => '_bbb_starter_pack',
			) as $shopify_key => $wp_meta
		) {
			if (isset($fields[$shopify_key]['value'])) {
				update_post_meta((int) $post_id, $wp_meta, bbb_import_bool_to_meta($fields[$shopify_key]['value']));
			}
		}

		$cover_url = $fields['cover']['reference']['image']['url'] ?? '';
		if ($cover_url) {
			update_post_meta((int) $post_id, '_bbb_cover_url', $cover_url);
		}

		foreach (
			array(
				'amazon_link'                 => '_bbb_amazon_url',
				'bookshop_link'               => '_bbb_bookshop_url',
				'newsletter_url'              => '_bbb_newsletter_url',
				'featured_in_newsletter_date' => '_bbb_newsletter_date',
			) as $shopify_key => $wp_meta
		) {
			$value = bbb_import_metaobject_field_value($fields, $shopify_key);
			if ($value !== '') {
				update_post_meta((int) $post_id, $wp_meta, $value);
			}
		}

		$series_ref = $fields['series']['reference'] ?? null;
		if (is_array($series_ref)) {
			$series_handle = (string) ($series_ref['handle'] ?? '');
			$series_title  = '';
			foreach (($series_ref['fields'] ?? array()) as $series_field) {
				if (($series_field['key'] ?? '') === 'title') {
					$series_title = (string) ($series_field['value'] ?? '');
				}
			}

			if ($series_handle !== '') {
				update_post_meta((int) $post_id, '_bbb_series_handle', $series_handle);
				$series_term = get_term_by('slug', $series_handle, 'bbb_series');
				if (!$series_term) {
					$inserted = wp_insert_term($series_title ?: $series_handle, 'bbb_series', array('slug' => $series_handle));
					if (!is_wp_error($inserted)) {
						$series_term = get_term((int) $inserted['term_id'], 'bbb_series');
					}
				}
				if ($series_term instanceof WP_Term) {
					wp_set_object_terms((int) $post_id, (int) $series_term->term_id, 'bbb_series');
				}
			}
		}

		if (bbb_import_assign_book_shelf((int) $post_id, $fields)) {
			++$shelves;
		} else {
			++$missing_shelves;
		}

		$trope_refs  = $fields['tropes']['references']['edges'] ?? array();
		$trope_slugs = array();
		foreach ($trope_refs as $trope_edge) {
			$trope_node   = $trope_edge['node'] ?? array();
			$trope_handle = (string) ($trope_node['handle'] ?? '');
			$trope_name   = '';
			$trope_emoji  = '';

			foreach (($trope_node['fields'] ?? array()) as $trope_field) {
				if (($trope_field['key'] ?? '') === 'name') {
					$trope_name = (string) ($trope_field['value'] ?? '');
				}
				if (($trope_field['key'] ?? '') === 'emoji') {
					$trope_emoji = (string) ($trope_field['value'] ?? '');
				}
			}

			if ($trope_handle !== '') {
				$term = get_term_by('slug', $trope_handle, 'bbb_trope');
				if (!$term) {
					$inserted = wp_insert_term($trope_name ?: $trope_handle, 'bbb_trope', array('slug' => $trope_handle));
					if (!is_wp_error($inserted)) {
						$term = get_term((int) $inserted['term_id'], 'bbb_trope');
					}
				}

				if ($term instanceof WP_Term) {
					if ($trope_emoji !== '') {
						update_term_meta((int) $term->term_id, 'trope_emoji', $trope_emoji);
					}
					$trope_slugs[] = (int) $term->term_id;
				}
			}
		}

		if ($trope_slugs) {
			wp_set_object_terms((int) $post_id, $trope_slugs, 'bbb_trope');
		}

		$count++;
		$log('Imported: ' . $handle);
	}

	if ($count === 0) {
		$log('No book records were found in this JSON. Check that the export contains metaobjects.edges or metaobjects.nodes.');
	} else {
		array_unshift($messages, sprintf('Shelf terms assigned to %d of %d imported books.', $shelves, $count));
		if ($missing_shelves > 0) {
			array_unshift($messages, sprintf('%d imported books did not include shelf reference data in the uploaded JSON.', $missing_shelves));
		}
	}

	return array(
		'count'    => $count,
		'messages' => $messages,
	);
}

function bbb_import_newsletter_issues_from_data(array $data, ?callable $logger = null): array {
	$issues   = bbb_import_metaobject_edges_from_export($data);
	$count    = 0;
	$messages = array();
	$log      = static function (string $message) use (&$messages, $logger): void {
		$messages[] = $message;
		if ($logger) {
			$logger($message);
		}
	};

	foreach ($issues as $edge) {
		$node   = $edge['node'] ?? array();
		$handle = (string) ($node['handle'] ?? '');
		if ('' === $handle) {
			$log('Skipped a newsletter issue without a handle.');
			continue;
		}

		$fields = array();
		foreach (($node['fields'] ?? array()) as $field) {
			if (isset($field['key'])) {
				$fields[$field['key']] = $field;
			}
		}

		$title = (string) (
			$fields['title']['value']
			?? $fields['subject']['value']
			?? $fields['name']['value']
			?? $handle
		);

		$raw_publish_date = (string) (
			$fields['publish_date']['value']
			?? $fields['date']['value']
			?? ''
		);
		$publish_date = bbb_import_normalize_date($raw_publish_date);
		[$post_date, $post_date_gmt] = bbb_import_wp_datetime($raw_publish_date ?: $publish_date);

		$existing = get_page_by_path($handle, OBJECT, 'newsletter_issue');
		$postarr  = array(
			'post_type'   => 'newsletter_issue',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $handle,
		);

		if ('' !== $post_date) {
			$postarr['post_date']     = $post_date;
			$postarr['post_date_gmt'] = $post_date_gmt;
		}

		if ($existing instanceof WP_Post) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post($postarr, true);
		} else {
			$post_id = wp_insert_post($postarr, true);
		}

		if (is_wp_error($post_id)) {
			$log('Failed newsletter issue: ' . $handle . ' - ' . $post_id->get_error_message());
			continue;
		}

		if ('' !== $publish_date) {
			update_post_meta((int) $post_id, 'publish_date', $publish_date);
			update_post_meta((int) $post_id, '_issue_publish_date', $publish_date);
		}

		$issue_url = bbb_import_newsletter_issue_url($fields);
		if ('' !== $issue_url) {
			update_post_meta((int) $post_id, '_bbb_newsletter_url', $issue_url);
			update_post_meta((int) $post_id, 'issue_url', $issue_url);
		}

		$preview_image = bbb_import_newsletter_preview_image($fields);
		if ('' !== $preview_image['url']) {
			update_post_meta((int) $post_id, '_issue_preview_url', esc_url_raw($preview_image['url']));
			update_post_meta((int) $post_id, '_issue_preview_alt', sanitize_text_field($preview_image['alt']));
		}

		foreach (
			array(
				'excerpt'     => '_issue_excerpt',
				'subtitle'    => '_issue_subtitle',
				'issue_no'    => '_issue_no',
				'issue_label' => '_issue_label',
				'label'       => '_issue_label',
				'tropes'      => '_issue_tropes',
			) as $shopify_key => $wp_meta
		) {
			$value = bbb_import_metaobject_field_value($fields, $shopify_key);
			if ('' !== $value) {
				update_post_meta((int) $post_id, $wp_meta, $value);
			}
		}

		$book_handle = bbb_import_newsletter_book_handle($fields);

		if ('' !== $book_handle) {
			update_post_meta((int) $post_id, '_issue_book_handle', $book_handle);
			$book = get_page_by_path($book_handle, OBJECT, array('bbb_book', 'sss_book'));
			if ($book instanceof WP_Post) {
				update_post_meta((int) $post_id, '_issue_book_id', (int) $book->ID);
				update_post_meta((int) $post_id, '_issue_library_book_id', (int) $book->ID);

				if ('' !== $publish_date) {
					if ('bbb_book' === $book->post_type) {
						update_post_meta((int) $book->ID, '_bbb_newsletter_date', $publish_date);
					} else {
						update_post_meta((int) $book->ID, 'featured_in_newsletter_date', $publish_date);
					}
				}

				if ('' !== $issue_url) {
					if ('bbb_book' === $book->post_type) {
						update_post_meta((int) $book->ID, '_bbb_newsletter_url', $issue_url);
					} else {
						update_post_meta((int) $book->ID, 'newsletter_url', $issue_url);
					}
				}
			}
		}

		$count++;
		$log('Imported newsletter issue: ' . $handle);
	}

	if ($count === 0) {
		$log('No newsletter issue records were found in this JSON. Check that the export contains newsletter_issue metaobjects with edges or nodes.');
	}

	return array(
		'count'    => $count,
		'messages' => $messages,
	);
}

if (defined('WP_CLI') && WP_CLI) {
	WP_CLI::add_command(
		'bbb import-books',
		static function ($args, $assoc_args): void {
			$file = $assoc_args['file'] ?? '';
			if (!file_exists($file)) {
				WP_CLI::error("File not found: $file");
			}

			$data   = json_decode((string) file_get_contents($file), true);
			$result = is_array($data)
				? bbb_import_books_from_data($data)
				: array('count' => 0, 'messages' => array('The uploaded file is not valid JSON.'));

			foreach (($result['messages'] ?? array()) as $message) {
				WP_CLI::log((string) $message);
			}

			WP_CLI::success('Done. ' . (int) ($result['count'] ?? 0) . ' books imported.');
		}
	);

	WP_CLI::add_command(
		'bbb import-newsletter-issues',
		static function ($args, $assoc_args): void {
			$file = $assoc_args['file'] ?? '';
			if (!file_exists($file)) {
				WP_CLI::error("File not found: $file");
			}

			$data   = json_decode((string) file_get_contents($file), true);
			$result = is_array($data)
				? bbb_import_newsletter_issues_from_data($data)
				: array('count' => 0, 'messages' => array('The uploaded file is not valid JSON.'));

			foreach (($result['messages'] ?? array()) as $message) {
				WP_CLI::log((string) $message);
			}

			WP_CLI::success('Done. ' . (int) ($result['count'] ?? 0) . ' newsletter issues imported.');
		}
	);

	WP_CLI::add_command(
		'bbb import-blog-post-dates',
		static function ($args, $assoc_args): void {
			$file = $assoc_args['file'] ?? '';
			if (!file_exists($file)) {
				WP_CLI::error("File not found: $file");
			}

			$data   = json_decode((string) file_get_contents($file), true);
			$result = is_array($data)
				? bbb_import_blog_post_dates_from_data($data)
				: array('count' => 0, 'messages' => array('The uploaded file is not valid JSON.'));

			foreach (($result['messages'] ?? array()) as $message) {
				WP_CLI::log((string) $message);
			}

			WP_CLI::success('Done. ' . (int) ($result['count'] ?? 0) . ' blog post dates updated.');
		}
	);
}
