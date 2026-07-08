<?php
/**
 * Books REST endpoint.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/books',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static fn() => rest_ensure_response(bbb_get_all_books_json()),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'bbb/v1',
			'/library/archive',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'bbb_rest_library_archive_batch',
				'permission_callback' => '__return_true',
				'args'                => array(
					'offset' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'limit'  => array(
						'type'              => 'integer',
						'default'           => 24,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'bbb/v1',
			'/book-links-sheet.csv',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'bbb_rest_book_links_sheet_csv',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_filter('rest_pre_serve_request', 'bbb_rest_serve_book_links_sheet_csv', 10, 4);

add_action('template_redirect', 'bbb_serve_book_links_sheet_csv_request');

function bbb_library_archive_books(): array {
	$books = function_exists('sss_get_all_books') ? sss_get_all_books() : array();
	$current_month = wp_date('Y-m');

	return array_values(
		array_filter(
			$books,
			static function ($book) use ($current_month): bool {
				if (!$book instanceof WP_Post) {
					return false;
				}

				if (function_exists('sss_book_is_private') && sss_book_is_private($book->ID)) {
					return false;
				}

				$data = function_exists('sss_book_data') ? sss_book_data($book) : array();

				return ($data['featured_month'] ?? '') !== $current_month;
			}
		)
	);
}

function bbb_render_library_archive_cards(array $books): string {
	ob_start();

	foreach ($books as $book) {
		if ($book instanceof WP_Post) {
			get_template_part('template-parts/library/book-card', null, array('post' => $book));
		}
	}

	return (string) ob_get_clean();
}

function bbb_rest_library_archive_batch(WP_REST_Request $request): WP_REST_Response {
	$offset = max(0, (int) $request->get_param('offset'));
	$limit  = min(48, max(1, (int) $request->get_param('limit')));
	$books  = bbb_library_archive_books();
	$total  = count($books);
	$batch  = array_slice($books, $offset, $limit);
	$next   = $offset + count($batch);

	return rest_ensure_response(
		array(
			'html'       => bbb_render_library_archive_cards($batch),
			'count'      => count($batch),
			'nextOffset' => $next,
			'total'      => $total,
			'hasMore'    => $next < $total,
		)
	);
}

function bbb_book_links_sheet_headers(): array {
	return array(
		'post_id',
		'post_type',
		'post_status',
		'slug',
		'title',
		'author',
		'Libby link',
		'Audible link',
		'KU link to fill',
		'Amazon link',
		'Bookshop link',
		'site_url',
		'current KU flag reference',
		'ku_meta_key',
		'amazon_meta_key',
		'bookshop_meta_key',
		'post_modified',
		'audible_meta_key',
		'libby_meta_key',
	);
}

function bbb_book_links_sheet_meta(int $post_id, string $key): string {
	$value = get_post_meta($post_id, $key, true);

	return is_scalar($value) ? trim((string) $value) : '';
}

function bbb_book_links_sheet_url_meta(int $post_id, string $key): string {
	$value = bbb_book_links_sheet_meta($post_id, $key);

	return function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($value) : $value;
}

function bbb_book_links_sheet_rows(): array {
	$books = get_posts(
		array(
			'post_type'              => 'bbb_book',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => array(
				'modified' => 'DESC',
				'ID'       => 'DESC',
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	return array_map(
		static function (WP_Post $book): array {
			$post_id = (int) $book->ID;
			$ku_url  = bbb_book_links_sheet_url_meta($post_id, '_bbb_ku_url');

			return array(
				$post_id,
				$book->post_type,
				$book->post_status,
				$book->post_name,
				get_the_title($book),
				bbb_book_links_sheet_meta($post_id, '_bbb_author'),
				bbb_book_links_sheet_url_meta($post_id, '_bbb_libby_url'),
				bbb_book_links_sheet_url_meta($post_id, '_bbb_audible_url'),
				$ku_url,
				bbb_book_links_sheet_url_meta($post_id, '_bbb_amazon_url'),
				bbb_book_links_sheet_url_meta($post_id, '_bbb_bookshop_url'),
				get_permalink($book),
				'' !== $ku_url ? '1' : '0',
				'_bbb_ku',
				'_bbb_amazon_url',
				'_bbb_bookshop_url',
				$book->post_modified,
				'_bbb_audible_url',
				'_bbb_libby_url',
			);
		},
		$books
	);
}

function bbb_book_links_sheet_csv(): string {
	$handle = fopen('php://temp', 'r+');

	if (false === $handle) {
		return '';
	}

	fputcsv($handle, bbb_book_links_sheet_headers());

	foreach (bbb_book_links_sheet_rows() as $row) {
		fputcsv($handle, $row);
	}

	rewind($handle);
	$csv = stream_get_contents($handle);
	fclose($handle);

	return false === $csv ? '' : $csv;
}

function bbb_rest_book_links_sheet_csv(): WP_REST_Response {
	$response = new WP_REST_Response(bbb_book_links_sheet_csv(), 200);
	$response->header('Content-Type', 'text/csv; charset=' . get_option('blog_charset'));
	$response->header('Content-Disposition', 'inline; filename="bbb-book-links-sheet.csv"');
	$response->header('Cache-Control', 'no-cache, must-revalidate, max-age=0');

	return $response;
}

function bbb_rest_serve_book_links_sheet_csv(bool $served, WP_HTTP_Response $result, WP_REST_Request $request, WP_REST_Server $server): bool {
	if ('/bbb/v1/book-links-sheet.csv' !== $request->get_route()) {
		return $served;
	}

	$server->send_headers($result);
	echo (string) $result->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	return true;
}

function bbb_serve_book_links_sheet_csv_request(): void {
	if (!isset($_GET['bbb_book_links_sheet'])) {
		return;
	}

	nocache_headers();
	header('Content-Type: text/csv; charset=' . get_option('blog_charset'));
	header('Content-Disposition: inline; filename="bbb-book-links-sheet.csv"');
	echo bbb_book_links_sheet_csv(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
