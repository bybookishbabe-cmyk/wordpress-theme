<?php
/**
 * WordPress reader account and Supabase bookshelf sync.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_reader_normalize_email(string $email): string {
	return strtolower(trim($email));
}

function bbb_reader_supabase_config(): array {
	return array(
		'url' => defined('SUPABASE_URL') ? rtrim((string) SUPABASE_URL, '/') : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
		'key' => defined('SUPABASE_SERVICE_ROLE_KEY') ? (string) SUPABASE_SERVICE_ROLE_KEY : (string) getenv('SUPABASE_SERVICE_ROLE_KEY'),
	);
}

function bbb_reader_supabase_request(string $method, string $table, array $query = array(), $body = null) {
	$config = bbb_reader_supabase_config();
	if ('' === $config['url'] || '' === $config['key']) {
		return new WP_Error(
			'bbb_supabase_not_configured',
			'Supabase service role key is not configured.',
			array('status' => 503)
		);
	}

	$url = $config['url'] . '/rest/v1/' . ltrim($table, '/');
	if ($query) {
		$url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
	}

	$args = array(
		'method'  => strtoupper($method),
		'timeout' => 15,
		'headers' => array(
			'apikey'        => $config['key'],
			'Authorization' => 'Bearer ' . $config['key'],
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'Prefer'        => 'resolution=merge-duplicates,return=representation',
		),
	);

	if (null !== $body) {
		$args['body'] = wp_json_encode($body);
	}

	$response = wp_remote_request($url, $args);
	if (is_wp_error($response)) {
		return $response;
	}

	$code     = (int) wp_remote_retrieve_response_code($response);
	$raw_body = (string) wp_remote_retrieve_body($response);
	$decoded  = '' !== $raw_body ? json_decode($raw_body, true) : array();

	if ($code < 200 || $code >= 300) {
		return new WP_Error(
			'bbb_supabase_request_failed',
			'Supabase request failed.',
			array(
				'status' => $code,
				'body'   => $decoded ?: $raw_body,
			)
		);
	}

	return is_array($decoded) ? $decoded : array();
}

function bbb_reader_access_tier(int $user_id = 0): string {
	$user_id = $user_id ?: get_current_user_id();

	return (function_exists('bbb_user_is_society') && bbb_user_is_society($user_id))
		|| '1' === get_user_meta($user_id, 'bbb_society_member', true)
		|| '1' === get_user_meta($user_id, '_bbb_society_member_active', true)
		? 'society'
		: 'free';
}

function bbb_reader_account_payload(WP_User $user, string $source = 'wordpress_account'): array {
	$email = bbb_reader_normalize_email((string) $user->user_email);

	return array(
		'email'             => (string) $user->user_email,
		'email_normalized'  => $email,
		'wordpress_user_id' => (string) $user->ID,
		'shopify_customer_id' => (string) $user->ID,
		'customer_email'    => (string) $user->user_email,
		'account_status'    => 'logged_in',
		'access_tier'       => bbb_reader_access_tier((int) $user->ID),
		'source'            => $source,
		'last_synced_at'    => gmdate('c'),
		'metadata'          => array(
			'wordpress_user_id' => (int) $user->ID,
			'display_name'      => (string) $user->display_name,
			'roles'             => array_values((array) $user->roles),
		),
	);
}

function bbb_reader_sync_user_to_supabase(int $user_id, string $source = 'wordpress_account') {
	$user = get_user_by('id', $user_id);
	if (!$user instanceof WP_User || !is_email((string) $user->user_email)) {
		return new WP_Error('bbb_reader_invalid_user', 'A valid WordPress user is required.');
	}

	return bbb_reader_supabase_request(
		'POST',
		'bookshelf_subscribers',
		array('on_conflict' => 'email_normalized'),
		array(bbb_reader_account_payload($user, $source))
	);
}

function bbb_reader_book_key(array $book): string {
	return strtolower(trim(sanitize_text_field((string) ($book['handle'] ?? $book['book_handle'] ?? $book['title'] ?? $book['book_title'] ?? ''))));
}

function bbb_reader_sanitize_book(array $book): ?array {
	$title = sanitize_text_field((string) ($book['title'] ?? $book['book_title'] ?? ''));
	$key   = bbb_reader_book_key($book);
	if ('' === $title || '' === $key) {
		return null;
	}

	return array(
		'book_key'       => $key,
		'book_handle'    => sanitize_title((string) ($book['handle'] ?? $book['book_handle'] ?? '')),
		'book_title'     => $title,
		'author'         => sanitize_text_field((string) ($book['author'] ?? '')),
		'cover'          => esc_url_raw((string) ($book['cover'] ?? '')),
		'amazon'         => esc_url_raw((string) ($book['amazon'] ?? '')),
		'bookshop'       => esc_url_raw((string) ($book['bookshop'] ?? '')),
		'spice_level'    => isset($book['spice']) ? absint($book['spice']) : null,
		'darkness_level' => isset($book['darkness']) ? absint($book['darkness']) : null,
		'tropes'         => array_values(
			array_filter(
				array_map(
					'sanitize_text_field',
					is_array($book['tropes'] ?? null) ? $book['tropes'] : explode(',', (string) ($book['tropes'] ?? ''))
				)
			)
		),
	);
}

function bbb_reader_fetch_account_books(WP_User $user): array {
	$email = bbb_reader_normalize_email((string) $user->user_email);
	$or    = sprintf(
		'(wordpress_user_id.eq.%1$d,email_normalized.eq.%2$s,shopify_customer_id.eq.%1$d)',
		(int) $user->ID,
		$email
	);

	$rows = bbb_reader_supabase_request(
		'GET',
		'bookshelf_saved_books',
		array(
			'select'    => 'book_handle,book_title,author,cover,amazon,bookshop,spice_level,darkness_level,tropes,saved_at',
			'is_active' => 'eq.true',
			'or'        => $or,
			'order'     => 'saved_at.desc',
			'limit'     => 100,
		)
	);

	return is_wp_error($rows) ? array() : (array) $rows;
}

function bbb_reader_account_response(WP_User $user): array {
	$sync = bbb_reader_sync_user_to_supabase((int) $user->ID, 'wordpress_account_api');

	return array(
		'wordpressUser' => array(
			'id'          => (int) $user->ID,
			'email'       => (string) $user->user_email,
			'displayName' => (string) $user->display_name,
		),
		'accessTier'    => bbb_reader_access_tier((int) $user->ID),
		'supabaseReady' => !is_wp_error($sync),
		'books'         => bbb_reader_fetch_account_books($user),
	);
}

function bbb_reader_sync_current_shelf(WP_REST_Request $request) {
	$user = wp_get_current_user();
	if (!$user instanceof WP_User || !$user->ID) {
		return new WP_Error('bbb_reader_auth_required', 'You must be logged in.', array('status' => 401));
	}

	bbb_reader_sync_user_to_supabase((int) $user->ID, 'wordpress_bookshelf');

	$email = bbb_reader_normalize_email((string) $user->user_email);
	$items = $request->get_param('items');
	$items = is_array($items) ? $items : array();
	$rows  = array();

	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}

		$book = bbb_reader_sanitize_book($item);
		if (!$book) {
			continue;
		}

		$rows[] = array_merge(
			$book,
			array(
				'email_normalized'    => $email,
				'wordpress_user_id'   => (string) $user->ID,
				'shopify_customer_id' => (string) $user->ID,
				'customer_email'      => (string) $user->user_email,
				'source'              => 'wordpress_bookshelf',
				'is_active'           => true,
				'removed_at'          => null,
				'saved_at'            => gmdate('c'),
			)
		);
	}

	if ($rows) {
		$save = bbb_reader_supabase_request(
			'POST',
			'bookshelf_saved_books',
			array('on_conflict' => 'email_normalized,book_key'),
			$rows
		);

		if (is_wp_error($save)) {
			return $save;
		}
	}

	return rest_ensure_response(bbb_reader_account_response($user));
}

add_action('user_register', static fn(int $user_id) => bbb_reader_sync_user_to_supabase($user_id, 'wordpress_register'));
add_action('profile_update', static fn(int $user_id) => bbb_reader_sync_user_to_supabase($user_id, 'wordpress_profile_update'));
add_action('set_user_role', static fn(int $user_id) => bbb_reader_sync_user_to_supabase($user_id, 'wordpress_role_update'));
add_action(
	'wp_login',
	static function (string $user_login, WP_User $user): void {
		bbb_reader_sync_user_to_supabase((int) $user->ID, 'wordpress_login');
	},
	10,
	2
);

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/reader-account',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => static fn(): bool => is_user_logged_in(),
				'callback'            => static function (): WP_REST_Response {
					return rest_ensure_response(bbb_reader_account_response(wp_get_current_user()));
				},
			)
		);

		register_rest_route(
			'bbb/v1',
			'/reader-account/shelf',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => static fn(): bool => is_user_logged_in(),
				'callback'            => 'bbb_reader_sync_current_shelf',
			)
		);
	}
);
