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
	return 'wordpress_bbb_reader_email_access';
}

function bbb_reader_legacy_email_session_cookie_name(): string {
	return 'bbb_reader_email_access';
}

function bbb_reader_email_session_lifetime(): int {
	return 30 * DAY_IN_SECONDS;
}

function bbb_reader_request_has_email_session(): bool {
	return isset($_COOKIE[bbb_reader_email_session_cookie_name()])
		|| isset($_COOKIE[bbb_reader_legacy_email_session_cookie_name()]);
}

function bbb_reader_request_is_pwa_home(): bool {
	$source = isset($_GET['source']) ? sanitize_key((string) wp_unslash($_GET['source'])) : '';

	return 'pwa-bybookishbabe' === $source;
}

add_action(
	'init',
	static function (): void {
		if (!bbb_reader_request_has_email_session() && !bbb_reader_request_is_pwa_home()) {
			return;
		}

		if (!defined('DONOTCACHEPAGE')) {
			define('DONOTCACHEPAGE', true);
		}
		if (!defined('DONOTCACHEOBJECT')) {
			define('DONOTCACHEOBJECT', true);
		}
		if (!defined('DONOTCACHEDB')) {
			define('DONOTCACHEDB', true);
		}
	},
	0
);

add_action(
	'send_headers',
	static function (): void {
		if (!bbb_reader_request_has_email_session() && !bbb_reader_request_is_pwa_home()) {
			return;
		}

		nocache_headers();
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
	},
	0
);

function bbb_reader_is_local_request(): bool {
	if (function_exists('wp_get_environment_type') && 'local' === wp_get_environment_type()) {
		return true;
	}

	$host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) wp_unslash($_SERVER['HTTP_HOST'])) : '';
	$host = preg_replace('/:\d+$/', '', $host) ?: '';

	return in_array($host, array('localhost', '127.0.0.1', '::1'), true) || str_ends_with($host, '.local');
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
			'path'     => '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

function bbb_reader_clear_email_session(): void {
	$cookie_names = array(
		bbb_reader_email_session_cookie_name(),
		bbb_reader_legacy_email_session_cookie_name(),
	);
	$cookie_paths = array_unique(
		array_filter(
			array(
				'/',
				COOKIEPATH ?: '',
				SITECOOKIEPATH ?: '',
			)
		)
	);

	foreach ($cookie_names as $cookie_name) {
		foreach ($cookie_paths as $cookie_path) {
			setcookie(
				$cookie_name,
				'',
				array(
					'expires'  => time() - DAY_IN_SECONDS,
					'path'     => $cookie_path,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
	}

	unset($_COOKIE[bbb_reader_email_session_cookie_name()]);
	unset($_COOKIE[bbb_reader_legacy_email_session_cookie_name()]);
}

function bbb_reader_email_from_session(): string {
	$raw = isset($_COOKIE[bbb_reader_email_session_cookie_name()]) ? (string) wp_unslash($_COOKIE[bbb_reader_email_session_cookie_name()]) : '';
	if ('' === $raw && isset($_COOKIE[bbb_reader_legacy_email_session_cookie_name()])) {
		$raw = (string) wp_unslash($_COOKIE[bbb_reader_legacy_email_session_cookie_name()]);
	}
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

	$result = bbb_reader_supabase_request(
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

	if (!is_wp_error($result) && function_exists('bbb_society_clear_subscriber_count_caches')) {
		bbb_society_clear_subscriber_count_caches();
	}

	return $result;
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
			'select'    => 'book_key,book_handle,book_title,author,cover,amazon,bookshop,spice_level,darkness_level,tropes,saved_at',
			'is_active' => 'eq.true',
			'or'        => $or,
			'order'     => 'saved_at.desc',
			'limit'     => 100,
		)
	);

	if (is_wp_error($rows)) {
		return array();
	}

	$seen   = array();
	$unique = array();
	foreach ((array) $rows as $row) {
		if (!is_array($row)) {
			continue;
		}

		$aliases = array_values(
			array_filter(
				array_unique(
					array_map(
						static fn($value): string => strtolower(trim(sanitize_text_field((string) $value))),
						array(
							$row['book_key'] ?? '',
							$row['book_handle'] ?? '',
							trim((string) ($row['book_title'] ?? '')) . '|' . trim((string) ($row['author'] ?? '')),
							$row['book_title'] ?? '',
						)
					)
				)
			)
		);

		if (!$aliases) {
			continue;
		}

		$already_seen = false;
		foreach ($aliases as $alias) {
			if (isset($seen[$alias])) {
				$already_seen = true;
				break;
			}
		}
		if ($already_seen) {
			continue;
		}

		foreach ($aliases as $alias) {
			$seen[$alias] = true;
		}
		$unique[] = $row;
	}

	return $unique;
}

function bbb_reader_fetch_account_books(WP_User $user): array {
	return bbb_reader_fetch_account_books_for_identity((string) $user->user_email, (int) $user->ID);
}

function bbb_reader_fetch_account_book_statuses_for_identity(string $email, int $user_id = 0): array {
	$email = bbb_reader_normalize_email($email);
	$or    = $user_id
		? sprintf('(wordpress_user_id.eq.%1$d,email_normalized.eq.%2$s,shopify_customer_id.eq.%1$d)', $user_id, $email)
		: sprintf('(email_normalized.eq.%1$s,customer_email.eq.%1$s)', $email);

	$rows = bbb_reader_supabase_request(
		'GET',
		'bookshelf_book_statuses',
		array(
			'select' => 'book_key,book_handle,book_title,status,metadata',
			'or'     => $or,
			'limit'  => 250,
		)
	);

	return is_wp_error($rows) ? array() : (array) $rows;
}

function bbb_reader_book_status_key(array $book): string {
	return strtolower(trim(sanitize_text_field((string) ($book['book_key'] ?? $book['book_handle'] ?? $book['handle'] ?? $book['book_title'] ?? $book['title'] ?? ''))));
}

function bbb_reader_sanitize_book_status_row(array $row, string $email, int $user_id): ?array {
	$key = bbb_reader_book_status_key($row);
	$status = sanitize_key((string) ($row['status'] ?? ''));
	if ('' === $key || !in_array($status, array('read', 'reading', 'tbr', 'dnf'), true)) {
		return null;
	}

	$rating = isset($row['rating']) ? absint($row['rating']) : 0;
	if (($rating < 1 || $rating > 5) && isset($row['metadata']) && is_array($row['metadata'])) {
		$rating = absint($row['metadata']['rating'] ?? 0);
	}

	$metadata = isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : array();
	if ($rating >= 1 && $rating <= 5) {
		$metadata['rating'] = $rating;
	} else {
		unset($metadata['rating']);
	}

	return array(
		'email_normalized'    => $email,
		'wordpress_user_id'   => $user_id ? (string) $user_id : null,
		'shopify_customer_id' => $user_id ? (string) $user_id : null,
		'customer_email'      => $email,
		'book_key'            => $key,
		'book_handle'         => sanitize_title((string) ($row['handle'] ?? $row['book_handle'] ?? '')),
		'book_title'          => sanitize_text_field((string) ($row['title'] ?? $row['book_title'] ?? $key)),
		'status'              => $status,
		'source'              => 'wordpress_bookshelf',
		'metadata'            => $metadata,
	);
}

function bbb_reader_enrich_books_with_statuses(array $books, array $statuses): array {
	$status_by_key = array();
	$rating_by_key = array();
	foreach ($statuses as $status_row) {
		if (!is_array($status_row)) {
			continue;
		}

		$key    = bbb_reader_book_status_key($status_row);
		$status = sanitize_key((string) ($status_row['status'] ?? ''));
		if ('' !== $key && '' !== $status) {
			$status_by_key[$key] = $status;
		}

		$metadata = isset($status_row['metadata']) && is_array($status_row['metadata']) ? $status_row['metadata'] : array();
		$rating = isset($status_row['rating']) ? absint($status_row['rating']) : absint($metadata['rating'] ?? 0);
		if ('' !== $key && $rating >= 1 && $rating <= 5) {
			$rating_by_key[$key] = $rating;
		}
	}

	foreach ($books as $index => $book) {
		if (!is_array($book)) {
			continue;
		}

		$key = bbb_reader_book_status_key($book);
		if ('' !== $key && isset($status_by_key[$key])) {
			$books[$index]['status'] = $status_by_key[$key];
		}
		if ('' !== $key && isset($rating_by_key[$key])) {
			$books[$index]['rating'] = $rating_by_key[$key];
		}
	}

	return $books;
}

function bbb_reader_split_book_tropes($value): array {
	if (is_array($value)) {
		$tropes = $value;
	} else {
		$tropes = preg_split('/[,|]/', (string) $value) ?: array();
	}

	return array_values(
		array_filter(
			array_map(
				static function ($trope): string {
					if (is_array($trope)) {
						$trope = $trope['name'] ?? $trope['label'] ?? $trope['title'] ?? $trope['slug'] ?? $trope['handle'] ?? '';
					}

					return strtolower(trim(sanitize_text_field((string) $trope)));
				},
				$tropes
			)
		)
	);
}

function bbb_reader_account_status_counts(array $books, array $statuses): array {
	$counts = array(
		'saved'   => count($books),
		'read'    => 0,
		'reading' => 0,
		'tbr'     => 0,
		'dnf'     => 0,
	);
	$seen_status_keys = array();

	foreach ($statuses as $status_row) {
		if (!is_array($status_row)) {
			continue;
		}

		$key = bbb_reader_book_status_key($status_row);
		$status = sanitize_key((string) ($status_row['status'] ?? ''));
		if (isset($counts[$status])) {
			++$counts[$status];
			if ('' !== $key) {
				$seen_status_keys[$key] = true;
			}
		}
	}

	foreach ($books as $book) {
		if (!is_array($book)) {
			continue;
		}

		$key = bbb_reader_book_status_key($book);
		if ('' !== $key && isset($seen_status_keys[$key])) {
			continue;
		}

		$status = sanitize_key((string) ($book['status'] ?? ''));
		if (isset($counts[$status])) {
			++$counts[$status];
		}
	}

	return $counts;
}

function bbb_reader_type_rules(): array {
	return array(
		'slow burn' => array(
			'title'   => 'the patient one',
			'summary' => 'patient in fiction. chaotic in real life. lives for the almost-moment. deep down just wants to be perceived that carefully by someone.',
			'flag'    => 'has cried at a forehead touch before. no notes.',
			'aliases' => array('slow burn', 'yearning'),
		),
		'forced proximity' => array(
			'title'   => 'the one-bed truther',
			'summary' => 'loves the idea that love finds you, not the other way around. probably romanticises being stuck somewhere with someone.',
			'flag'    => 'has thought "if only we were snowed in" about someone who did not deserve it.',
			'aliases' => array('forced proximity', 'one bed', 'only one bed'),
		),
		'fake dating' => array(
			'title'   => 'the plausible deniability fan',
			'summary' => 'comes for the fake intimacy that accidentally becomes real. that is the only kind of love story that makes sense to them and they know it.',
			'flag'    => 'has entertained a fake-dating plan. has not acted on it. yet.',
			'aliases' => array('fake dating', 'fake relationship', 'marriage of convenience'),
		),
		'second chance' => array(
			'title'   => 'the door-holder',
			'summary' => 'believes in people maybe a little too much. reads this trope for permission to try again, or to feel okay about not.',
			'flag'    => 'definitely has an unsent message somewhere.',
			'aliases' => array('second chance', 'exes to lovers'),
		),
		'sports romance' => array(
			'title'   => 'here for the man, not the sport',
			'summary' => 'cannot name a single real player. is here because athletes are written with a very specific kind of devotion: the dedication, the discipline, the biceps.',
			'flag'    => 'has very strong opinions about ryan shay.',
			'aliases' => array('sports romance', 'sports', 'hockey romance', 'football romance', 'baseball romance'),
		),
		'grumpy sunshine' => array(
			'title'   => 'secretly both',
			'summary' => 'either brings light into difficult spaces and gets called too much, or is the grumpy one who needs someone to soften them and will not admit it.',
			'flag'    => 'they are grumpy about being assigned sunshine.',
			'aliases' => array('grumpy sunshine', 'grumpy / sunshine', 'grumpy/sunshine', 'grumpy x sunshine'),
		),
		'enemies to lovers' => array(
			'title'   => 'loves the problem',
			'summary' => 'does not want easy. wants someone who challenges them and then loves them so hard it breaks the whole thing open. the argument is the intimacy.',
			'flag'    => 'has confused conflict with chemistry at least once. this trope did not help.',
			'aliases' => array('enemies to lovers', 'rivals to lovers', 'hate to love'),
		),
		'forbidden romance' => array(
			'title'   => 'the rule is the problem, not them',
			'summary' => 'not here for easy love. wants the kind that costs something. boss/employee, rival families, that is the only love that feels real to them.',
			'flag'    => 'deeply committed to the idea that circumstance is the only obstacle here.',
			'aliases' => array('forbidden romance', 'forbidden love', 'forbidden'),
		),
		'dark romance' => array(
			'title'   => 'the villain is the love interest',
			'summary' => 'wants to feel something, not be told what they are allowed to feel. they are not advocating. they are escaping. and they do not need to explain that to anyone.',
			'flag'    => 'has a type in fiction they would absolutely never date in real life.',
			'aliases' => array('dark romance', 'dark', 'stalker romance', 'stalker', 'touch her and die', 'villain gets the girl'),
		),
		'mafia romance' => array(
			'title'   => 'the suit is doing something to them',
			'summary' => 'not about crime. it is about devotion without limit. someone who would destroy the world for this one person. the protection. the contradiction.',
			'flag'    => 'has a ranked list of fictional dons. it is detailed.',
			'aliases' => array('mafia romance', 'mafia', 'bratva', 'cartel romance'),
		),
		'bully romance' => array(
			'title'   => 'obsession decoder',
			'summary' => 'does not want to be bullied. wants to be the person someone hates so specifically it reads as obsession. that is not cruelty. that is being perceived. completely.',
			'flag'    => 'would describe their ideal relationship as "he hates me in a way that feels personal."',
			'aliases' => array('bully romance', 'bully', 'bully romance books'),
		),
		'morally gray mmc' => array(
			'title'   => 'the problem is the point',
			'summary' => 'tired of being told who to root for. wants a man who does the wrong thing for the right reason, or the wrong reason with extremely good hair.',
			'flag'    => 'their book boyfriend list reads like a restraining order waiting to happen. they are fine.',
			'aliases' => array('morally gray mmc', 'morally gray', 'morally grey', 'morally grey mmc', 'antihero'),
		),
		'age gap' => array(
			'title'   => 'experience is a vibe',
			'summary' => 'here for the dynamic. the knowing. someone who has done something with their life loving someone still becoming. experience reading as devotion.',
			'flag'    => 'googles age gaps in fictional couples. then googles it again.',
			'aliases' => array('age gap', 'age-gap'),
		),
		'reverse harem' => array(
			'title'   => 'why choose is not a question',
			'summary' => 'less about the fantasy of many, more about the fantasy of being completely loved by multiple people who just accept all of you. the logistics are not the point.',
			'flag'    => 'finds monogamy limitations in fiction genuinely boring at this point.',
			'aliases' => array('reverse harem', 'why choose', 'poly romance'),
		),
		'instalove' => array(
			'title'   => 'vibes over evidence',
			'summary' => 'when it is right it is right. that is the whole philosophy. refuses to feel bad about wanting to skip to the good part.',
			'flag'    => 'has said "i just knew" about someone they knew for four days.',
			'aliases' => array('instalove', 'insta love', 'love at first sight'),
		),
		'paranormal monster romance' => array(
			'title'   => 'not here to explain herself',
			'summary' => 'has simply decided that fictional men available in reality are not meeting the brief. the spec: dangerous, ancient, non-human, completely devoted to one woman specifically.',
			'flag'    => 'claws are negotiable. the obsession is not.',
			'aliases' => array('paranormal / monster romance', 'paranormal romance', 'monster romance', 'monster', 'vampire romance', 'shifter romance'),
		),
		'bodyguard protector' => array(
			'title'   => 'safety is the love language',
			'summary' => 'wants to feel safe in a specific, consuming, slightly-overwhelming way. someone who would rearrange the world so nothing bad reaches her. that is it.',
			'flag'    => 'has asked "but would he keep me safe" as a genuine compatibility metric.',
			'aliases' => array('bodyguard / protector', 'bodyguard', 'protector', 'protector romance', 'bodyguard romance'),
		),
		'he falls first' => array(
			'title'   => 'watching him spiral is the plot',
			'summary' => 'needs the man to be completely undone before she knows it. wants to watch the exact moment it shifts, when she becomes the whole thing for him and she is still just getting there.',
			'flag'    => 'has a list of scenes where the man realises first. it is long.',
			'aliases' => array('he falls first', 'he falls first romance', 'hero falls first', 'he falls first/her falls harder'),
		),
	);
}

function bbb_reader_normalize_trope_key(string $trope): string {
	$trope = strtolower(trim(wp_strip_all_tags($trope)));
	$trope = str_replace(array('&', '+', ' x '), array(' and ', ' ', ' '), $trope);
	$trope = preg_replace('/[\/_-]+/', ' ', $trope) ?: $trope;
	$trope = preg_replace('/\s+/', ' ', $trope) ?: $trope;

	return trim($trope);
}

function bbb_reader_type_rule_key_for_trope(string $trope): string {
	$normalized = bbb_reader_normalize_trope_key($trope);
	if ('' === $normalized) {
		return '';
	}

	foreach (bbb_reader_type_rules() as $rule_key => $rule) {
		$aliases = array_merge(array($rule_key), (array) ($rule['aliases'] ?? array()));
		foreach ($aliases as $alias) {
			$alias = bbb_reader_normalize_trope_key((string) $alias);
			if ('' === $alias) {
				continue;
			}
			if ($normalized === $alias || str_contains($normalized, $alias) || str_contains($alias, $normalized)) {
				return (string) $rule_key;
			}
		}
	}

	return $normalized;
}

function bbb_reader_type_display_trope(string $rule_key): string {
	$labels = array(
		'grumpy sunshine' => 'grumpy / sunshine',
		'paranormal monster romance' => 'paranormal / monster romance',
		'bodyguard protector' => 'bodyguard / protector',
	);

	return $labels[$rule_key] ?? $rule_key;
}

function bbb_reader_account_reader_type(array $books, array $statuses): array {
	$counts       = bbb_reader_account_status_counts($books, $statuses);
	$trope_counts = array();

	foreach ($books as $book) {
		if (!is_array($book)) {
			continue;
		}

		$status = sanitize_key((string) ($book['status'] ?? ''));
		$weight = match ($status) {
			'read' => 5,
			'reading' => 4,
			'tbr' => 3,
			'dnf' => 1,
			default => 2,
		};
		foreach (bbb_reader_split_book_tropes($book['tropes'] ?? '') as $trope) {
			$rule_key = bbb_reader_type_rule_key_for_trope($trope);
			if ('' === $rule_key) {
				continue;
			}
			$trope_counts[$rule_key] = ($trope_counts[$rule_key] ?? 0) + $weight;
		}
	}

	arsort($trope_counts);
	$top_trope_keys = array_slice(array_keys($trope_counts), 0, 3);
	$top_tropes = array_map('bbb_reader_type_display_trope', $top_trope_keys);
	$rules      = bbb_reader_type_rules();
	$top_key    = (string) ($top_trope_keys[0] ?? '');
	$top_rule   = isset($rules[$top_key]) && is_array($rules[$top_key]) ? $rules[$top_key] : array();
	$title      = (string) ($top_rule['title'] ?? 'mood-led romance reader');
	$body       = (string) ($top_rule['summary'] ?? 'your shelf is giving a little bit of everything, with mood doing more steering than one fixed trope.');
	$red_flag   = (string) ($top_rule['flag'] ?? '');

	if (0 === $counts['saved'] && 0 === count($statuses)) {
		$title = 'fresh shelf romantic';
		$body  = 'save or tag a few books and this will start calling your pattern.';
		$red_flag = '';
	}

	return array(
		'title'     => $title,
		'summary'   => $body,
		'redFlag'   => $red_flag,
		'primaryTrope' => bbb_reader_type_display_trope($top_key),
		'topTropes' => $top_tropes,
		'counts'    => $counts,
	);
}

function bbb_reader_account_next_read(array $books): ?array {
	if (!$books) {
		return null;
	}

	$priority = array('tbr' => 0, 'reading' => 1, '' => 2, 'saved' => 2, 'read' => 8, 'dnf' => 9);
	$candidates = array_values(
		array_filter(
			$books,
			static fn($book): bool => is_array($book) && '' !== trim((string) ($book['book_title'] ?? $book['title'] ?? ''))
		)
	);

	usort(
		$candidates,
		static function (array $a, array $b) use ($priority): int {
			$a_status = sanitize_key((string) ($a['status'] ?? ''));
			$b_status = sanitize_key((string) ($b['status'] ?? ''));
			$a_rank   = $priority[$a_status] ?? 3;
			$b_rank   = $priority[$b_status] ?? 3;
			if ($a_rank === $b_rank) {
				return strcmp((string) ($b['saved_at'] ?? ''), (string) ($a['saved_at'] ?? ''));
			}

			return $a_rank <=> $b_rank;
		}
	);

	return $candidates[0] ?? null;
}

function bbb_reader_spice_profiles(): array {
	return array(
		1 => array(
			'level'       => 1,
			'label'       => 'soft spice',
			'peppers'     => '🌶',
			'description' => 'closed-door, fade-to-black, or just a little heat around the edges.',
		),
		2 => array(
			'level'       => 2,
			'label'       => 'some heat',
			'peppers'     => '🌶🌶',
			'description' => 'a little open-door energy without letting the plot disappear.',
		),
		3 => array(
			'level'       => 3,
			'label'       => 'balanced',
			'peppers'     => '🌶🌶🌶',
			'description' => 'chemistry, feelings, and spice all pulling their weight.',
		),
		4 => array(
			'level'       => 4,
			'label'       => 'high spice',
			'peppers'     => '🌶🌶🌶🌶',
			'description' => 'open-door, high-heat romance with the tension turned up.',
		),
		5 => array(
			'level'       => 5,
			'label'       => 'wreck me',
			'peppers'     => '🌶🌶🌶🌶🌶',
			'description' => 'maximum heat, maximum chaos, no pretending otherwise.',
		),
	);
}

function bbb_reader_normalize_spice_profile($value): int {
	$level = absint($value);
	return min(max($level, 1), 5);
}

function bbb_reader_spice_profile_payload(int $level = 0): array {
	$profiles = bbb_reader_spice_profiles();
	$level    = $level > 0 ? bbb_reader_normalize_spice_profile($level) : 0;

	if (0 === $level || !isset($profiles[$level])) {
		return array(
			'level'       => 0,
			'label'       => '',
			'peppers'     => '',
			'description' => '',
		);
	}

	return $profiles[$level];
}

function bbb_reader_spice_profile_for_identity(array $identity): array {
	$user = $identity['user'] ?? null;
	if ($user instanceof WP_User && $user->ID) {
		return bbb_reader_spice_profile_payload((int) get_user_meta((int) $user->ID, 'bbb_reader_spice_profile', true));
	}

	return bbb_reader_spice_profile_payload(0);
}

function bbb_reader_save_spice_profile_for_identity(array $identity, int $level): array {
	$level = bbb_reader_normalize_spice_profile($level);
	$user  = $identity['user'] ?? null;

	if ($user instanceof WP_User && $user->ID) {
		update_user_meta((int) $user->ID, 'bbb_reader_spice_profile', $level);
	}

	return bbb_reader_spice_profile_payload($level);
}

function bbb_reader_mfy_profile_option_key_for_email(string $email): string {
	return 'bbb_reader_mfy_profile_' . md5(bbb_reader_normalize_email($email));
}

function bbb_reader_mfy_profile_version(): string {
	return 'mfy-2026-06-11-reader-types';
}

function bbb_reader_sanitize_mfy_profile_value($value) {
	if (is_bool($value)) {
		return $value;
	}

	if (is_int($value) || is_float($value)) {
		return $value;
	}

	if (is_array($value)) {
		$clean = array();
		foreach ($value as $key => $item) {
			$clean_key = is_int($key) ? $key : sanitize_key((string) $key);
			if ('' === (string) $clean_key && !is_int($clean_key)) {
				continue;
			}
			$clean[$clean_key] = bbb_reader_sanitize_mfy_profile_value($item);
		}
		return $clean;
	}

	return sanitize_text_field((string) $value);
}

function bbb_reader_sanitize_mfy_profile($profile): array {
	$profile = is_array($profile) ? $profile : array();
	if (!$profile) {
		return array();
	}

	$clean   = array();

	foreach ($profile as $key => $value) {
		$clean_key = sanitize_key((string) $key);
		if ('' === $clean_key) {
			continue;
		}
		$clean[$clean_key] = bbb_reader_sanitize_mfy_profile_value($value);
	}

	$clean['updatedAt'] = sanitize_text_field((string) ($profile['updatedAt'] ?? gmdate('c')));

	return $clean;
}

function bbb_reader_mfy_profile_is_current(array $profile): bool {
	$version = (string) ($profile['mfy_profile_version'] ?? $profile['profile_version'] ?? '');
	return bbb_reader_mfy_profile_version() === $version;
}

function bbb_reader_mfy_profile_is_complete(array $profile): bool {
	if (!bbb_reader_mfy_profile_is_current($profile)) {
		return false;
	}

	foreach (array('name', 'heat_lane', 'group_chat_text', 'love_interest', 'wall_line') as $key) {
		if ('' === trim((string) ($profile[$key] ?? ''))) {
			return false;
		}
	}

	return !empty($profile['dashboard_built']);
}

function bbb_reader_mfy_profile_updated_at(array $profile): int {
	$raw = (string) ($profile['updatedAt'] ?? $profile['updated_at'] ?? '');
	$time = '' !== $raw ? strtotime($raw) : false;

	return false === $time ? 0 : (int) $time;
}

function bbb_reader_preferred_mfy_profile(array $profiles): array {
	$profiles = array_values(
		array_filter(
			array_map(
				static function ($profile): array {
					$profile = is_array($profile) && $profile ? bbb_reader_sanitize_mfy_profile($profile) : array();
					return bbb_reader_mfy_profile_is_current($profile) ? $profile : array();
				},
				$profiles
			)
		)
	);

	if (!$profiles) {
		return array();
	}

	usort(
		$profiles,
		static function (array $a, array $b): int {
			$a_complete = bbb_reader_mfy_profile_is_complete($a) ? 1 : 0;
			$b_complete = bbb_reader_mfy_profile_is_complete($b) ? 1 : 0;

			if ($a_complete !== $b_complete) {
				return $b_complete <=> $a_complete;
			}

			return bbb_reader_mfy_profile_updated_at($b) <=> bbb_reader_mfy_profile_updated_at($a);
		}
	);

	return $profiles[0];
}

function bbb_reader_mfy_profile_for_identity(array $identity): array {
	$user = $identity['user'] ?? null;
	if ($user instanceof WP_User && $user->ID) {
		$email = bbb_reader_normalize_email((string) $user->user_email);
		$user_profile = get_user_meta((int) $user->ID, 'bbb_reader_mfy_profile', true);
		$email_profile = '' !== $email && is_email($email)
			? get_option(bbb_reader_mfy_profile_option_key_for_email($email), array())
			: array();
		$profile = bbb_reader_preferred_mfy_profile(array($user_profile, $email_profile));

		if ($profile) {
			update_user_meta((int) $user->ID, 'bbb_reader_mfy_profile', $profile);
			if ('' !== $email && is_email($email)) {
				update_option(bbb_reader_mfy_profile_option_key_for_email($email), $profile, false);
			}
		}

		return $profile;
	}

	$email = bbb_reader_normalize_email((string) ($identity['email'] ?? ''));
	if ('' === $email || !is_email($email)) {
		return array();
	}

	$profile = get_option(bbb_reader_mfy_profile_option_key_for_email($email), array());
	return bbb_reader_preferred_mfy_profile(array($profile));
}

function bbb_reader_save_mfy_profile_for_identity(array $identity, array $profile): array {
	$profile = bbb_reader_sanitize_mfy_profile($profile);
	if ($profile) {
		$profile['mfy_profile_version'] = bbb_reader_mfy_profile_version();
	}
	$user    = $identity['user'] ?? null;

	if ($user instanceof WP_User && $user->ID) {
		update_user_meta((int) $user->ID, 'bbb_reader_mfy_profile', $profile);
		$email = bbb_reader_normalize_email((string) $user->user_email);
		if ('' !== $email && is_email($email)) {
			update_option(bbb_reader_mfy_profile_option_key_for_email($email), $profile, false);
		}
		return $profile;
	}

	$email = bbb_reader_normalize_email((string) ($identity['email'] ?? ''));
	if ('' !== $email && is_email($email)) {
		update_option(bbb_reader_mfy_profile_option_key_for_email($email), $profile, false);
	}

	return $profile;
}

function bbb_reader_get_current_mfy_profile(WP_REST_Request $request) {
	$identity = bbb_reader_current_identity();
	if (!$identity) {
		return new WP_Error('bbb_reader_auth_required', 'Enter your reader email first.', array('status' => 401));
	}

	return rest_ensure_response(array('profile' => bbb_reader_mfy_profile_for_identity((array) $identity)));
}

function bbb_reader_update_current_mfy_profile(WP_REST_Request $request) {
	$identity = bbb_reader_current_identity();
	if (!$identity) {
		return new WP_Error('bbb_reader_auth_required', 'Enter your reader email first.', array('status' => 401));
	}

	$params = $request->get_json_params();
	if (!is_array($params)) {
		$params = $request->get_params();
	}

	$profile = bbb_reader_save_mfy_profile_for_identity((array) $identity, is_array($params['profile'] ?? null) ? $params['profile'] : array());

	return rest_ensure_response(array('profile' => $profile));
}

function bbb_reader_notes_option_key_for_email(string $email): string {
	return 'bbb_reader_private_notes_' . md5(bbb_reader_normalize_email($email));
}

function bbb_reader_sanitize_note_item($note): ?array {
	if (!is_array($note)) {
		return null;
	}

	$key = substr(trim((string) ($note['key'] ?? $note['handle'] ?? $note['title'] ?? '')), 0, 180);
	if ('' === $key) {
		return null;
	}

	$text = sanitize_textarea_field((string) ($note['text'] ?? ''));
	if ('' === trim($text)) {
		return null;
	}

	return array(
		'key'       => $key,
		'handle'    => sanitize_text_field((string) ($note['handle'] ?? '')),
		'title'     => sanitize_text_field((string) ($note['title'] ?? '')),
		'author'    => sanitize_text_field((string) ($note['author'] ?? '')),
		'cover'     => esc_url_raw((string) ($note['cover'] ?? '')),
		'text'      => $text,
		'updatedAt' => sanitize_text_field((string) ($note['updatedAt'] ?? gmdate('c'))),
	);
}

function bbb_reader_sanitize_notes_payload($notes): array {
	$clean = array();
	$items = is_array($notes) ? $notes : array();

	foreach ($items as $key => $note) {
		if (is_array($note) && !isset($note['key'])) {
			$note['key'] = (string) $key;
		}

		$item = bbb_reader_sanitize_note_item($note);
		if (!$item) {
			continue;
		}

		$clean[$item['key']] = $item;
	}

	return $clean;
}

function bbb_reader_notes_for_identity(array $identity): array {
	$user = $identity['user'] ?? null;
	if ($user instanceof WP_User && $user->ID) {
		$notes = get_user_meta((int) $user->ID, 'bbb_reader_private_notes', true);
		return bbb_reader_sanitize_notes_payload(is_array($notes) ? $notes : array());
	}

	$email = bbb_reader_normalize_email((string) ($identity['email'] ?? ''));
	if ('' === $email || !is_email($email)) {
		return array();
	}

	$notes = get_option(bbb_reader_notes_option_key_for_email($email), array());
	return bbb_reader_sanitize_notes_payload(is_array($notes) ? $notes : array());
}

function bbb_reader_save_notes_for_identity(array $identity, array $notes): array {
	$notes = bbb_reader_sanitize_notes_payload($notes);
	$user  = $identity['user'] ?? null;

	if ($user instanceof WP_User && $user->ID) {
		update_user_meta((int) $user->ID, 'bbb_reader_private_notes', $notes);
		return $notes;
	}

	$email = bbb_reader_normalize_email((string) ($identity['email'] ?? ''));
	if ('' !== $email && is_email($email)) {
		update_option(bbb_reader_notes_option_key_for_email($email), $notes, false);
	}

	return $notes;
}

function bbb_reader_account_notes_response(array $identity): array {
	return array(
		'notes' => bbb_reader_notes_for_identity($identity),
	);
}

function bbb_reader_get_current_notes(WP_REST_Request $request) {
	$identity = bbb_reader_current_identity();
	if (!$identity) {
		return new WP_Error('bbb_reader_auth_required', 'Enter your reader email first.', array('status' => 401));
	}

	if (function_exists('bbb_reader_can_use_notes') && !bbb_reader_can_use_notes()) {
		return new WP_Error('bbb_reader_member_required', 'Notes are a member feature.', array('status' => 403));
	}

	return rest_ensure_response(bbb_reader_account_notes_response((array) $identity));
}

function bbb_reader_update_current_notes(WP_REST_Request $request) {
	$identity = bbb_reader_current_identity();
	if (!$identity) {
		return new WP_Error('bbb_reader_auth_required', 'Enter your reader email first.', array('status' => 401));
	}

	if (function_exists('bbb_reader_can_use_notes') && !bbb_reader_can_use_notes()) {
		return new WP_Error('bbb_reader_member_required', 'Notes are a member feature.', array('status' => 403));
	}

	$params = $request->get_json_params();
	if (!is_array($params)) {
		$params = $request->get_params();
	}

	$notes = bbb_reader_save_notes_for_identity((array) $identity, is_array($params['notes'] ?? null) ? $params['notes'] : array());

	return rest_ensure_response(array('notes' => $notes));
}

function bbb_reader_account_insights(array $books, array $statuses): array {
	$books = bbb_reader_enrich_books_with_statuses($books, $statuses);

	return array(
		'books'        => $books,
		'bookStatuses' => $statuses,
		'readerType'   => bbb_reader_account_reader_type($books, $statuses),
		'nextRead'     => bbb_reader_account_next_read($books),
	);
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

if (!function_exists('bbb_reader_june_2026_daily_prompts')) {
	function bbb_reader_june_2026_daily_prompts(): array {
		return array(
			'what do i want june to feel like?',
			'what am i finally ready to let go of?',
			'where in my life am i playing small?',
			'what does my ideal summer day look like?',
			'what version of myself am i becoming?',
			'what am i pretending not to know?',
			'what would i do if i was not afraid of being seen?',
			'what does my aura actually feel like right now?',
			'where am i giving energy that is not returned?',
			'what do i keep almost saying out loud?',
			'what does "golden & unbothered" mean to me personally?',
			'what am i most proud of that nobody knows about?',
			'who inspires me and what specifically is it about them?',
			'what part of me is ready to bloom?',
			'what habits make me feel most like myself?',
			'what do i need to stop apologizing for?',
			'what would i do differently if i trusted myself completely?',
			'what does my morning routine say about how i see myself?',
			'what is my relationship with rest?',
			'what does luxury mean to me, not money, but feeling?',
			'what am i tolerating that i should not be?',
			'what book changed how i see myself and why?',
			'what conversation do i keep avoiding?',
			'what would my most radiant self do today?',
			'what do i want people to feel when they are around me?',
			'what does this version of me deserve?',
			'what am i learning to want without guilt?',
			'how have i grown since january?',
			'what was the best moment of june so far?',
			'what do i want to carry into july?',
		);
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

		if (!$prompts && function_exists('bbb_reader_june_2026_daily_prompts')) {
			$timestamp = (int) current_time('timestamp');
			$year      = (int) date_i18n('Y', $timestamp);
			$month     = (int) date_i18n('n', $timestamp);
			if (2026 === $year && 6 === $month) {
				$prompts = bbb_reader_june_2026_daily_prompts();
				$journal_start = '2026-06-01';
			}
		}

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
	$insights = bbb_reader_account_insights(
		bbb_reader_fetch_account_books($user),
		bbb_reader_fetch_account_book_statuses_for_identity((string) $user->user_email, (int) $user->ID)
	);
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
		'books'         => $insights['books'],
		'bookStatuses'  => $insights['bookStatuses'],
		'readerType'    => $insights['readerType'],
		'nextRead'      => $insights['nextRead'],
		'spiceProfile'  => bbb_reader_spice_profile_for_identity(array('user' => $user)),
		'madeForYouProfile' => bbb_reader_mfy_profile_for_identity(array('user' => $user)),
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
			'spiceProfile' => bbb_reader_spice_profile_payload(0),
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
	$insights = bbb_reader_account_insights(
		bbb_reader_fetch_account_books_for_identity($email),
		bbb_reader_fetch_account_book_statuses_for_identity($email)
	);
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
		'books'         => $insights['books'],
		'bookStatuses'  => $insights['bookStatuses'],
		'readerType'    => $insights['readerType'],
		'nextRead'      => $insights['nextRead'],
		'spiceProfile'  => bbb_reader_spice_profile_for_identity($identity),
		'madeForYouProfile' => bbb_reader_mfy_profile_for_identity($identity),
	);
}

function bbb_reader_start_email_access_session(string $email) {
	$email = bbb_reader_normalize_email($email);
	if ('' === $email || !is_email($email)) {
		return new WP_Error('bbb_reader_invalid_email', 'Enter a valid email address.', array('status' => 400));
	}

	$subscriber = bbb_reader_fetch_subscriber_by_email($email);
	if (is_wp_error($subscriber) && bbb_reader_is_local_request()) {
		$subscriber = null;
	}
	if (is_wp_error($subscriber)) {
		return $subscriber;
	}

	$has_manual_vault_access = function_exists('bbb_vault_identity_has_manual_access') && bbb_vault_identity_has_manual_access($email, 0);
	if (!is_array($subscriber) && !$has_manual_vault_access && !bbb_reader_is_local_request()) {
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

	$has_status_payload = null !== $request->get_param('statuses');
	$statuses = $request->get_param('statuses');
	$ratings = $request->get_param('ratings');
	$statuses = is_array($statuses) ? $statuses : array();
	$ratings = is_array($ratings) ? $ratings : array();
	$status_rows = array();

	foreach ($statuses as $key => $status_value) {
		$row = is_array($status_value)
			? $status_value
			: array(
				'book_key' => (string) $key,
				'status'   => (string) $status_value,
			);

		$status_key = bbb_reader_book_status_key($row);
		if ('' !== $status_key && isset($ratings[$status_key])) {
			$row['rating'] = $ratings[$status_key];
		}

		$status_row = bbb_reader_sanitize_book_status_row($row, $email, $user_id);
		if ($status_row) {
			$status_rows[] = $status_row;
		}
	}

	if ($has_status_payload) {
		$delete_statuses = bbb_reader_supabase_request(
			'DELETE',
			'bookshelf_book_statuses',
			array('email_normalized' => 'eq.' . $email)
		);

		if (is_wp_error($delete_statuses)) {
			return $delete_statuses;
		}
	}

	if ($status_rows) {
		$save_statuses = bbb_reader_supabase_request(
			'POST',
			'bookshelf_book_statuses',
			array(),
			$status_rows
		);

		if (is_wp_error($save_statuses)) {
			return $save_statuses;
		}
	}

	return rest_ensure_response(bbb_reader_account_response_for_identity($identity));
}

function bbb_reader_update_current_spice_profile(WP_REST_Request $request) {
	$identity = bbb_reader_current_identity();
	if (!$identity) {
		return new WP_Error('bbb_reader_auth_required', 'Enter your reader email first.', array('status' => 401));
	}

	$params = $request->get_json_params();
	if (!is_array($params)) {
		$params = $request->get_params();
	}

	$profile = bbb_reader_save_spice_profile_for_identity((array) $identity, (int) ($params['level'] ?? 0));

	return rest_ensure_response(
		array(
			'spiceProfile' => $profile,
			'account'      => bbb_reader_account_response_for_identity((array) $identity),
		)
	);
}

add_action(
	'template_redirect',
	static function (): void {
		$request_path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

		if (
			'POST' === (string) ($_SERVER['REQUEST_METHOD'] ?? '')
			&& 'account' === $request_path
			&& isset($_POST['bbb_reader_email_access'])
		) {
			$email = isset($_POST['email']) ? bbb_reader_normalize_email((string) wp_unslash($_POST['email'])) : '';
			$result = bbb_reader_start_email_access_session($email);
			$return_to = isset($_POST['return_to']) ? esc_url_raw((string) wp_unslash($_POST['return_to'])) : home_url('/account/');
			$return_to = wp_validate_redirect($return_to, home_url('/account/'));
			$redirect_url = is_wp_error($result)
				? add_query_arg('reader_email_error', rawurlencode($result->get_error_message()), $return_to)
				: add_query_arg('reader_opened', (string) time(), $return_to);

			wp_safe_redirect($redirect_url . '#reader-email-access');
			exit;
		}

		if (isset($_GET['bbb_local_reader_access']) && bbb_reader_is_local_request()) {
			$email = bbb_reader_normalize_email((string) wp_unslash($_GET['bbb_local_reader_access']));
			if ('' !== $email && is_email($email)) {
				bbb_reader_set_email_session($email);
			}

			wp_safe_redirect(remove_query_arg('bbb_local_reader_access'));
			exit;
		}

		if (!isset($_GET['bbb_reader_logout'])) {
			return;
		}

		bbb_reader_clear_email_session();
		if (is_user_logged_in()) {
			wp_logout();
		}

		$redirect_url = add_query_arg(
			'bbb_reader_logged_out',
			'1',
			remove_query_arg('bbb_reader_logout')
		);
		wp_safe_redirect($redirect_url . '#reader-email-access');
		exit;
	},
	-10
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
				'/reader-account/spice-profile',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => static fn(): bool => (bool) bbb_reader_current_identity(),
				'callback'            => 'bbb_reader_update_current_spice_profile',
				)
			);

			register_rest_route(
				'bbb/v1',
				'/reader-account/made-for-you',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'permission_callback' => static fn(): bool => (bool) bbb_reader_current_identity(),
						'callback'            => 'bbb_reader_get_current_mfy_profile',
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'permission_callback' => static fn(): bool => (bool) bbb_reader_current_identity(),
						'callback'            => 'bbb_reader_update_current_mfy_profile',
					),
				)
			);

			register_rest_route(
				'bbb/v1',
				'/reader-account/notes',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'permission_callback' => static fn(): bool => function_exists('bbb_reader_can_use_notes') && bbb_reader_can_use_notes(),
					'callback'            => 'bbb_reader_get_current_notes',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'permission_callback' => static fn(): bool => function_exists('bbb_reader_can_use_notes') && bbb_reader_can_use_notes(),
					'callback'            => 'bbb_reader_update_current_notes',
				),
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
