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

function bbb_reader_substack_sync_secret(): string {
	return defined('SUBSTACK_SYNC_SECRET') ? (string) SUBSTACK_SYNC_SECRET : (string) getenv('SUBSTACK_SYNC_SECRET');
}

function bbb_reader_email_session_cookie_name(): string {
	return 'bbb_reader_email_access';
}

function bbb_reader_email_session_lifetime(): int {
	return 30 * DAY_IN_SECONDS;
}

function bbb_reader_cookie_secret(): string {
	$parts = array_filter(
		array(
			defined('AUTH_KEY') ? (string) AUTH_KEY : '',
			defined('SECURE_AUTH_KEY') ? (string) SECURE_AUTH_KEY : '',
			defined('LOGGED_IN_KEY') ? (string) LOGGED_IN_KEY : '',
			defined('NONCE_KEY') ? (string) NONCE_KEY : '',
		)
	);

	return $parts ? implode('|', $parts) : wp_salt('auth');
}

function bbb_reader_email_session_signature(string $email, int $expires): string {
	return hash_hmac('sha256', $email . '|' . $expires, bbb_reader_cookie_secret());
}

function bbb_reader_set_email_session(string $email): bool {
	$email = bbb_reader_normalize_email($email);
	if ('' === $email || !is_email($email)) {
		return false;
	}

	$expires = time() + bbb_reader_email_session_lifetime();
	$value   = implode('|', array($email, (string) $expires, bbb_reader_email_session_signature($email, $expires)));

	return setcookie(
		bbb_reader_email_session_cookie_name(),
		$value,
		array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ?: '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

function bbb_reader_clear_email_session(): void {
	setcookie(
		bbb_reader_email_session_cookie_name(),
		'',
		array(
			'expires'  => time() - DAY_IN_SECONDS,
			'path'     => COOKIEPATH ?: '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	unset($_COOKIE[bbb_reader_email_session_cookie_name()]);
}

function bbb_reader_email_from_session(): string {
	$raw = isset($_COOKIE[bbb_reader_email_session_cookie_name()]) ? (string) wp_unslash($_COOKIE[bbb_reader_email_session_cookie_name()]) : '';
	if ('' === $raw) {
		return '';
	}

	$parts = explode('|', $raw);
	if (3 !== count($parts)) {
		return '';
	}

	$email   = bbb_reader_normalize_email($parts[0]);
	$expires = absint($parts[1]);
	$hash    = (string) $parts[2];

	if ('' === $email || !is_email($email) || $expires < time()) {
		return '';
	}

	$expected = bbb_reader_email_session_signature($email, $expires);
	return hash_equals($expected, $hash) ? $email : '';
}

function bbb_reader_current_identity(): ?array {
	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		if ($user instanceof WP_User && $user->ID && is_email((string) $user->user_email)) {
			return array(
				'email'       => bbb_reader_normalize_email((string) $user->user_email),
				'displayName' => '' !== trim((string) $user->display_name) ? (string) $user->display_name : bbb_reader_normalize_email((string) $user->user_email),
				'userId'      => (int) $user->ID,
				'user'        => $user,
			);
		}
	}

	$email = bbb_reader_email_from_session();
	if ('' === $email) {
		return null;
	}

	return array(
		'email'       => $email,
		'displayName' => $email,
		'userId'      => 0,
		'user'        => null,
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

function bbb_reader_substack_payload_is_paid(array $payload): bool {
	$signals = array(
		(string) ($payload['access_tier'] ?? ''),
		(string) ($payload['tier'] ?? ''),
		(string) ($payload['status'] ?? ''),
		(string) ($payload['subscription_status'] ?? ''),
		(string) ($payload['subscription_type'] ?? ''),
		(string) ($payload['plan'] ?? ''),
	);

	$text = strtolower(trim(implode(' ', array_filter($signals))));
	if ('' === $text) {
		return false;
	}

	if (preg_match('/\b(free|unpaid|inactive|cancell?ed|expired|paused|trial_ended)\b/', $text)) {
		return false;
	}

	return (bool) preg_match('/\b(paid|active|founding|monthly|annual|yearly|comped|gifted|subscriber|member|society)\b/', $text);
}

function bbb_reader_substack_payload_is_inactive(array $payload): bool {
	$text = strtolower(
		trim(
			implode(
				' ',
				array_filter(
					array(
						(string) ($payload['status'] ?? ''),
						(string) ($payload['subscription_status'] ?? ''),
						(string) ($payload['event'] ?? ''),
						(string) ($payload['action'] ?? ''),
					)
				)
			)
		)
	);

	return (bool) preg_match('/\b(unsubscribed|inactive|cancell?ed|expired|paused|deleted)\b/', $text);
}

function bbb_reader_sync_external_subscriber(array $payload, string $source = 'substack_webhook') {
	$email = bbb_reader_normalize_email((string) ($payload['email'] ?? $payload['subscriber_email'] ?? $payload['customer_email'] ?? ''));
	if ('' === $email || !is_email($email)) {
		return new WP_Error('bbb_substack_missing_email', 'A valid subscriber email is required.', array('status' => 400));
	}

	$is_inactive = bbb_reader_substack_payload_is_inactive($payload);
	$is_paid = !$is_inactive && bbb_reader_substack_payload_is_paid($payload);

	return bbb_reader_supabase_request(
		'POST',
		'bookshelf_subscribers',
		array('on_conflict' => 'email_normalized'),
		array(
			array(
				'email'               => $email,
				'email_normalized'    => $email,
				'customer_email'      => $email,
				'account_status'      => 'email_only',
				'access_tier'         => $is_paid ? 'society' : 'free',
				'society_key_used_at' => $is_paid ? gmdate('c') : null,
				'society_key_source'  => $is_paid ? $source : null,
				'weekly_email_opt_in' => !$is_inactive,
				'source'              => $source,
				'last_synced_at'      => gmdate('c'),
				'metadata'            => array(
					'imported_from' => $source,
					'raw_status'    => array_filter(
						array(
							'event'               => $payload['event'] ?? null,
							'action'              => $payload['action'] ?? null,
							'status'              => $payload['status'] ?? null,
							'subscription_status' => $payload['subscription_status'] ?? null,
							'subscription_type'   => $payload['subscription_type'] ?? null,
							'tier'                => $payload['tier'] ?? null,
							'plan'                => $payload['plan'] ?? null,
						)
					),
				),
			),
		)
	);
}

function bbb_reader_user_has_wp_society_access(int $user_id = 0): bool {
	$user_id = $user_id ?: get_current_user_id();
	if (!$user_id) {
		return false;
	}

	$user = get_user_by('id', $user_id);
	if (!$user instanceof WP_User) {
		return false;
	}

	return in_array('society', (array) $user->roles, true)
		|| in_array('paid', (array) $user->roles, true)
		|| in_array('sss_member', (array) $user->roles, true)
		|| in_array('society_member', (array) $user->roles, true)
		|| (function_exists('bbb_user_is_society') && bbb_user_is_society($user_id))
		|| '1' === get_user_meta($user_id, 'bbb_society_member', true)
		|| '1' === get_user_meta($user_id, '_bbb_society_member_active', true)
		|| (
			function_exists('wc_memberships_is_user_active_member')
			&& wc_memberships_is_user_active_member($user_id, 'smut-sentiment-society')
		);
}

function bbb_reader_subscriber_has_society_access(array $subscriber): bool {
	return 'society' === (string) ($subscriber['access_tier'] ?? '')
		|| !empty($subscriber['society_key_used_at']);
}

function bbb_reader_fetch_subscriber_by_email(string $email) {
	$email = bbb_reader_normalize_email($email);
	if ('' === $email) {
		return null;
	}

	$rows = bbb_reader_supabase_request(
		'GET',
		'bookshelf_subscribers',
		array(
			'select' => 'email,email_normalized,customer_email,access_tier,society_key_used_at',
			'or'     => sprintf('(email_normalized.eq.%1$s,email.eq.%1$s,customer_email.eq.%1$s)', $email),
			'limit'  => 10,
		)
	);

	if (is_wp_error($rows)) {
		return $rows;
	}

	foreach ((array) $rows as $row) {
		if (is_array($row) && bbb_reader_subscriber_has_society_access($row)) {
			return $row;
		}
	}

	return isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
}

function bbb_reader_access_tier(int $user_id = 0, ?array $subscriber = null): string {
	static $subscriber_cache = array();

	$user_id = $user_id ?: get_current_user_id();

	if (bbb_reader_user_has_wp_society_access($user_id)) {
		return 'society';
	}

	if (null === $subscriber && $user_id) {
		if (!array_key_exists($user_id, $subscriber_cache)) {
			$user = get_user_by('id', $user_id);
			$fetched = $user instanceof WP_User ? bbb_reader_fetch_subscriber_by_email((string) $user->user_email) : null;
			$subscriber_cache[$user_id] = is_wp_error($fetched) ? null : $fetched;
		}

		$subscriber = is_array($subscriber_cache[$user_id]) ? $subscriber_cache[$user_id] : null;
	}

	if (is_array($subscriber) && bbb_reader_subscriber_has_society_access($subscriber)) {
		return 'society';
	}

	return 'free';
}

function bbb_reader_access_tier_for_email(string $email, int $user_id = 0, ?array $subscriber = null): string {
	static $email_cache = array();

	$email = bbb_reader_normalize_email($email);
	if ($user_id && bbb_reader_user_has_wp_society_access($user_id)) {
		return 'society';
	}

	if (null === $subscriber) {
		if (!array_key_exists($email, $email_cache)) {
			$fetched = bbb_reader_fetch_subscriber_by_email($email);
			$email_cache[$email] = is_wp_error($fetched) ? null : $fetched;
		}

		$subscriber = is_array($email_cache[$email]) ? $email_cache[$email] : null;
	}

	return is_array($subscriber) && bbb_reader_subscriber_has_society_access($subscriber) ? 'society' : 'free';
}

function bbb_reader_account_payload(WP_User $user, string $source = 'wordpress_account', ?array $subscriber = null): array {
	$email = bbb_reader_normalize_email((string) $user->user_email);

	return array(
		'email'             => (string) $user->user_email,
		'email_normalized'  => $email,
		'wordpress_user_id' => (string) $user->ID,
		'shopify_customer_id' => (string) $user->ID,
		'customer_email'    => (string) $user->user_email,
		'account_status'    => 'logged_in',
		'access_tier'       => bbb_reader_access_tier((int) $user->ID, $subscriber),
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

	$subscriber = bbb_reader_fetch_subscriber_by_email((string) $user->user_email);
	if (is_wp_error($subscriber)) {
		$subscriber = null;
	}

	return bbb_reader_supabase_request(
		'POST',
		'bookshelf_subscribers',
		array('on_conflict' => 'email_normalized'),
		array(bbb_reader_account_payload($user, $source, $subscriber))
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

function bbb_reader_fetch_account_books_for_identity(string $email, int $user_id = 0): array {
	$email = bbb_reader_normalize_email($email);
	$or    = $user_id
		? sprintf('(wordpress_user_id.eq.%1$d,email_normalized.eq.%2$s,shopify_customer_id.eq.%1$d)', $user_id, $email)
		: sprintf('(email_normalized.eq.%1$s,customer_email.eq.%1$s)', $email);

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

function bbb_reader_fetch_account_books(WP_User $user): array {
	return bbb_reader_fetch_account_books_for_identity((string) $user->user_email, (int) $user->ID);
}

if (!function_exists('bbb_reader_drop_field_value')) {
	function bbb_reader_drop_field_map(array $fields): array {
		if (!$fields) {
			return array();
		}

		$first_key = array_key_first($fields);
		if (is_string($first_key)) {
			return $fields;
		}

		$mapped = array();
		foreach ($fields as $field) {
			if (!is_array($field) || empty($field['key'])) {
				continue;
			}

			$mapped[(string) $field['key']] = $field;
		}

		return $mapped;
	}

	function bbb_reader_drop_field_value(array $fields, string $key, string $default = ''): string {
		$fields = bbb_reader_drop_field_map($fields);
		if (!isset($fields[$key]) || !is_array($fields[$key])) {
			return $default;
		}

		$field = $fields[$key];
		$value = $field['jsonValue'] ?? $field['value'] ?? $default;
		if (is_array($value)) {
			$value = $field['value'] ?? $default;
		}

		return trim((string) $value);
	}
}

if (!function_exists('bbb_reader_active_society_drop')) {
	function bbb_reader_active_society_drop(): array {
		if (function_exists('bbb_sss_drop_importer_active_entry')) {
			$entry = bbb_sss_drop_importer_active_entry();
			if (is_array($entry) && !empty($entry)) {
				return $entry;
			}
		}

		if (function_exists('bbb_sss_active_drop')) {
			$entry = bbb_sss_active_drop();
			if (is_array($entry) && !empty($entry)) {
				return $entry;
			}
		}

		return array();
	}
}

if (!function_exists('bbb_reader_active_society_daily_prompt')) {
	function bbb_reader_active_society_daily_prompt(array $drop): array {
		$fields = array();
		if (is_array($drop['fields'] ?? null)) {
			$fields = $drop['fields'];
		}

		$journal_start = bbb_reader_drop_field_value($fields, 'journal_start_date');
		$prompts_raw   = bbb_reader_drop_field_value($fields, 'prompts');
		$prompts       = array_values(array_filter(array_map('trim', preg_split('/\s*\|\|\s*/', $prompts_raw) ?: array())));

		$day = 0;
		$prompt = '';
		$prompt_count = count($prompts);
		if ($prompt_count > 0) {
			$start = strtotime((string) $journal_start . ' 00:00:00');
			$today = (int) current_time('timestamp');

			if (false === $start) {
				$day = 1;
			} else {
				$day = (int) floor(($today - $start) / (60 * 60 * 24)) + 1;
			}

			if ($day < 1) {
				$day = 1;
			} elseif ($day > $prompt_count) {
				$day = $prompt_count;
			}

			$index = $day - 1;
			$prompt = (string) ($prompts[$index] ?? '');
		}

		if ('' === $prompt) {
			$day = 0;
		}

		return array(
			'text' => $prompt,
			'day'  => $day,
			'total' => $prompt_count,
		);
	}
}

function bbb_reader_account_response(WP_User $user): array {
	$sync = bbb_reader_sync_user_to_supabase((int) $user->ID, 'wordpress_account_api');
	$synced_subscriber = !is_wp_error($sync) && isset($sync[0]) && is_array($sync[0]) ? $sync[0] : null;
	$access_tier = bbb_reader_access_tier((int) $user->ID, $synced_subscriber);
	$account_prompt = 'society' === $access_tier
		? bbb_reader_active_society_daily_prompt(bbb_reader_active_society_drop())
		: array(
			'text'  => '',
			'day'   => 0,
			'total' => 0,
		);
	$error = is_wp_error($sync)
		? array(
			'code'    => $sync->get_error_code(),
			'message' => $sync->get_error_message(),
			'status'  => (int) ($sync->get_error_data()['status'] ?? 0),
		)
		: null;

	return array(
		'wordpressUser' => array(
			'id'          => (int) $user->ID,
			'email'       => (string) $user->user_email,
			'displayName' => (string) $user->display_name,
		),
		'accessTier'         => $access_tier,
		'dailyJournalPrompt' => $account_prompt,
		'supabaseReady' => !is_wp_error($sync),
		'supabaseError' => $error,
		'books'         => bbb_reader_fetch_account_books($user),
	);
}

function bbb_reader_account_response_for_identity(array $identity): array {
	$user = $identity['user'] ?? null;
	if ($user instanceof WP_User) {
		return bbb_reader_account_response($user);
	}

	$email = bbb_reader_normalize_email((string) ($identity['email'] ?? ''));
	if ('' === $email || !is_email($email)) {
		return array(
			'wordpressUser' => null,
			'readerEmail'   => '',
			'accessTier'    => 'free',
			'supabaseReady' => false,
			'supabaseError' => array(
				'code'    => 'bbb_reader_missing_email',
				'message' => 'A reader email is required.',
				'status'  => 401,
			),
			'books' => array(),
		);
	}

	$subscriber = bbb_reader_fetch_subscriber_by_email($email);
	$error = is_wp_error($subscriber)
		? array(
			'code'    => $subscriber->get_error_code(),
			'message' => $subscriber->get_error_message(),
			'status'  => (int) ($subscriber->get_error_data()['status'] ?? 0),
		)
		: null;
	$subscriber = is_array($subscriber) ? $subscriber : null;
	$access_tier = bbb_reader_access_tier_for_email($email, 0, $subscriber);
	$account_prompt = 'society' === $access_tier
		? bbb_reader_active_society_daily_prompt(bbb_reader_active_society_drop())
		: array(
			'text'  => '',
			'day'   => 0,
			'total' => 0,
		);

	return array(
		'wordpressUser' => null,
		'readerEmail'   => $email,
		'accessTier'    => $access_tier,
		'dailyJournalPrompt' => $account_prompt,
		'supabaseReady' => null === $error,
		'supabaseError' => $error,
		'books'         => bbb_reader_fetch_account_books_for_identity($email),
	);
}

function bbb_reader_start_email_access_session(string $email) {
	$email = bbb_reader_normalize_email($email);
	if ('' === $email || !is_email($email)) {
		return new WP_Error('bbb_reader_invalid_email', 'Enter a valid email address.', array('status' => 400));
	}

	$subscriber = bbb_reader_fetch_subscriber_by_email($email);
	if (is_wp_error($subscriber)) {
		return $subscriber;
	}

	if (!is_array($subscriber)) {
		return new WP_Error(
			'bbb_reader_subscriber_not_found',
			'That email is not on the reader list yet.',
			array('status' => 404)
		);
	}

	bbb_reader_set_email_session($email);

	return bbb_reader_account_response_for_identity(
		array(
			'email'       => $email,
			'displayName' => $email,
			'userId'      => 0,
			'user'        => null,
		)
	);
}

function bbb_reader_sync_current_shelf(WP_REST_Request $request) {
	$identity = bbb_reader_current_identity();
	if (!$identity) {
		return new WP_Error('bbb_reader_auth_required', 'Enter your reader email first.', array('status' => 401));
	}

	$user = $identity['user'] ?? null;
	$user_id = isset($identity['userId']) ? (int) $identity['userId'] : 0;
	$email = bbb_reader_normalize_email((string) ($identity['email'] ?? ''));

	if ($user instanceof WP_User) {
		bbb_reader_sync_user_to_supabase((int) $user->ID, 'wordpress_bookshelf');
	}

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
				'wordpress_user_id'   => $user_id ? (string) $user_id : null,
				'shopify_customer_id' => $user_id ? (string) $user_id : null,
				'customer_email'      => $email,
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

	return rest_ensure_response(bbb_reader_account_response_for_identity($identity));
}

add_action(
	'template_redirect',
	static function (): void {
		if (!isset($_GET['bbb_reader_logout'])) {
			return;
		}

		bbb_reader_clear_email_session();
		wp_safe_redirect(remove_query_arg('bbb_reader_logout'));
		exit;
	}
);

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
				'permission_callback' => static fn(): bool => (bool) bbb_reader_current_identity(),
				'callback'            => static function (): WP_REST_Response {
					return rest_ensure_response(bbb_reader_account_response_for_identity((array) bbb_reader_current_identity()));
				},
			)
		);

		register_rest_route(
			'bbb/v1',
			'/reader-account/email-session',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function (WP_REST_Request $request) {
					$params = $request->get_json_params();
					if (!is_array($params)) {
						$params = $request->get_params();
					}

					$response = bbb_reader_start_email_access_session((string) ($params['email'] ?? ''));
					if (is_wp_error($response)) {
						return $response;
					}

					return rest_ensure_response($response);
				},
			)
		);

		register_rest_route(
			'bbb/v1',
			'/reader-account/shelf',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => static fn(): bool => (bool) bbb_reader_current_identity(),
				'callback'            => 'bbb_reader_sync_current_shelf',
			)
		);

		register_rest_route(
			'bbb/v1',
			'/substack-subscriber',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => static function (WP_REST_Request $request): bool {
					$secret = bbb_reader_substack_sync_secret();
					if ('' === $secret) {
						return false;
					}

					$provided = (string) ($request->get_header('x-bbb-substack-secret') ?: $request->get_param('secret'));
					return hash_equals($secret, $provided);
				},
				'callback'            => static function (WP_REST_Request $request) {
					$params = $request->get_json_params();
					if (!is_array($params) || !$params) {
						$params = $request->get_params();
					}

					$sync = bbb_reader_sync_external_subscriber($params, 'substack_webhook');
					if (is_wp_error($sync)) {
						return $sync;
					}

					return rest_ensure_response(
						array(
							'ok'      => true,
							'email'   => bbb_reader_normalize_email((string) ($params['email'] ?? $params['subscriber_email'] ?? $params['customer_email'] ?? '')),
							'tier'    => bbb_reader_substack_payload_is_paid($params) ? 'society' : 'free',
							'updated' => gmdate('c'),
						)
					);
				},
			)
		);
	}
);
