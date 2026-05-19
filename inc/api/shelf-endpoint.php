<?php
/**
 * Reader shelf REST placeholders.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/shelf',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => static function (WP_REST_Request $request) {
						return rest_ensure_response(
							array(
								'email' => sanitize_email((string) $request->get_param('email')),
								'items' => array(),
							)
						);
					},
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => static fn() => rest_ensure_response(array('saved' => true)),
					'permission_callback' => static fn() => is_user_logged_in(),
				),
			)
		);
	}
);
