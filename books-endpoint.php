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
	}
);

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
