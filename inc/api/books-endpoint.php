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
	}
);
