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

function bbb_import_field_reference_handle(array $fields, string $key): string {
	$reference = $fields[$key]['reference'] ?? null;
	if (is_array($reference) && !empty($reference['handle'])) {
		return (string) $reference['handle'];
	}

	$value = $fields[$key]['value'] ?? '';
	return is_string($value) ? sanitize_title($value) : '';
}

function bbb_import_books_from_data(array $data, ?callable $logger = null): array {
	$books    = bbb_import_books_edges_from_export($data);
	$count    = 0;
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

		$shelf_ref = $fields['shelf']['reference'] ?? null;
		if (is_array($shelf_ref)) {
			$shelf_name = '';
			foreach (($shelf_ref['fields'] ?? array()) as $shelf_field) {
				if (($shelf_field['key'] ?? '') === 'name') {
					$shelf_name = (string) ($shelf_field['value'] ?? '');
				}
			}

			if ($shelf_name !== '') {
				wp_set_object_terms((int) $post_id, $shelf_name, 'bbb_shelf');
			}
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

		$publish_date = (string) (
			$fields['publish_date']['value']
			?? $fields['date']['value']
			?? ''
		);

		$post_date = $publish_date;
		if (preg_match('/^\d{8}$/', $post_date)) {
			$post_date = substr($post_date, 0, 4) . '-' . substr($post_date, 4, 2) . '-' . substr($post_date, 6, 2);
		}

		$existing = get_page_by_path($handle, OBJECT, 'newsletter_issue');
		$postarr  = array(
			'post_type'   => 'newsletter_issue',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $handle,
		);

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $post_date)) {
			$postarr['post_date'] = $post_date . ' 10:00:00';
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

		foreach (
			array(
				'url'         => '_bbb_newsletter_url',
				'issue_url'   => '_bbb_newsletter_url',
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

		$book_handle = bbb_import_field_reference_handle($fields, 'book');
		if ('' === $book_handle) {
			$book_handle = bbb_import_field_reference_handle($fields, 'library_book');
		}

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

			$data  = json_decode((string) file_get_contents($file), true);
			$books = is_array($data) ? bbb_import_books_edges_from_export($data) : array();
			$count = 0;

			foreach ($books as $edge) {
				$node   = $edge['node'] ?? array();
				$handle = (string) ($node['handle'] ?? '');
				if ($handle === '') {
					WP_CLI::warning('Skipped a book without a handle.');
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
					WP_CLI::warning('Failed: ' . $handle . ' - ' . $post_id->get_error_message());
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

				$url_map = array(
					'amazon_link'                 => '_bbb_amazon_url',
					'bookshop_link'               => '_bbb_bookshop_url',
					'newsletter_url'              => '_bbb_newsletter_url',
					'featured_in_newsletter_date' => '_bbb_newsletter_date',
				);

				foreach ($url_map as $shopify_key => $wp_meta) {
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

				$shelf_ref = $fields['shelf']['reference'] ?? null;
				if (is_array($shelf_ref)) {
					$shelf_name = '';
					foreach (($shelf_ref['fields'] ?? array()) as $shelf_field) {
						if (($shelf_field['key'] ?? '') === 'name') {
							$shelf_name = (string) ($shelf_field['value'] ?? '');
						}
					}

					if ($shelf_name !== '') {
						wp_set_object_terms((int) $post_id, $shelf_name, 'bbb_shelf');
					}
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
				WP_CLI::log('Imported: ' . $handle);
			}

			WP_CLI::success("Done. $count books imported.");
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
			$issues = is_array($data) ? bbb_import_metaobject_edges_from_export($data) : array();
			$count  = 0;

			foreach ($issues as $edge) {
				$node   = $edge['node'] ?? array();
				$handle = (string) ($node['handle'] ?? '');
				if ('' === $handle) {
					WP_CLI::warning('Skipped a newsletter issue without a handle.');
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

				$publish_date = (string) (
					$fields['publish_date']['value']
					?? $fields['date']['value']
					?? ''
				);

				$post_date = $publish_date;
				if (preg_match('/^\d{8}$/', $post_date)) {
					$post_date = substr($post_date, 0, 4) . '-' . substr($post_date, 4, 2) . '-' . substr($post_date, 6, 2);
				}

				$existing = get_page_by_path($handle, OBJECT, 'newsletter_issue');
				$postarr  = array(
					'post_type'   => 'newsletter_issue',
					'post_status' => 'publish',
					'post_title'  => $title,
					'post_name'   => $handle,
				);

				if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $post_date)) {
					$postarr['post_date'] = $post_date . ' 10:00:00';
				}

				if ($existing instanceof WP_Post) {
					$postarr['ID'] = $existing->ID;
					$post_id       = wp_update_post($postarr, true);
				} else {
					$post_id = wp_insert_post($postarr, true);
				}

				if (is_wp_error($post_id)) {
					WP_CLI::warning('Failed newsletter issue: ' . $handle . ' - ' . $post_id->get_error_message());
					continue;
				}

				if ('' !== $publish_date) {
					update_post_meta((int) $post_id, 'publish_date', $publish_date);
					update_post_meta((int) $post_id, '_issue_publish_date', $publish_date);
				}

				foreach (
					array(
						'url'        => '_bbb_newsletter_url',
						'issue_url'  => '_bbb_newsletter_url',
						'excerpt'    => '_issue_excerpt',
						'subtitle'   => '_issue_subtitle',
						'issue_no'   => '_issue_no',
						'issue_label'=> '_issue_label',
						'label'      => '_issue_label',
						'tropes'     => '_issue_tropes',
					) as $shopify_key => $wp_meta
				) {
					$value = bbb_import_metaobject_field_value($fields, $shopify_key);
					if ('' !== $value) {
						update_post_meta((int) $post_id, $wp_meta, $value);
					}
				}

				$book_handle = bbb_import_field_reference_handle($fields, 'book');
				if ('' === $book_handle) {
					$book_handle = bbb_import_field_reference_handle($fields, 'library_book');
				}

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
					}
				}

				$count++;
				WP_CLI::log('Imported newsletter issue: ' . $handle);
			}

			WP_CLI::success("Done. $count newsletter issues imported.");
		}
	);
}
