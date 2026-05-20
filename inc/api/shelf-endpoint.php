<?php
/**
 * Backward-compatible reader shelf REST route.
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
						if (is_user_logged_in() && function_exists('bbb_reader_account_response')) {
							return rest_ensure_response(bbb_reader_account_response(wp_get_current_user()));
						}

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
					'callback'            => static function (WP_REST_Request $request) {
						if (function_exists('bbb_reader_sync_current_shelf')) {
							return bbb_reader_sync_current_shelf($request);
						}

						return rest_ensure_response(array('saved' => true));
					},
					'permission_callback' => static fn() => is_user_logged_in(),
				),
			)
		);
	}
);
