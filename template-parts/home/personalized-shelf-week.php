<?php
/**
 * Personalized homepage shelf for Society readers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_home_personalized_book_key')) {
	function bbb_home_personalized_book_key(array $book): string {
		return strtolower(trim(sanitize_text_field((string) ($book['handle'] ?? $book['book_handle'] ?? $book['title'] ?? $book['book_title'] ?? ''))));
	}
}

if (!function_exists('bbb_home_personalized_subscriber_is_active')) {
	function bbb_home_personalized_subscriber_is_active(array $subscriber): bool {
		$weekly_opt_in = $subscriber['weekly_email_opt_in'] ?? null;
		if (false === $weekly_opt_in || 'false' === strtolower((string) $weekly_opt_in) || '0' === (string) $weekly_opt_in) {
			return false;
		}

		$status = strtolower(trim((string) ($subscriber['account_status'] ?? '')));
		if ('' !== $status && preg_match('/\b(unsubscribed|inactive|cancell?ed|expired|paused|deleted)\b/', $status)) {
			return false;
		}

		return true;
	}
}

if (!function_exists('bbb_home_personalized_reader_is_subscribed')) {
	function bbb_home_personalized_reader_is_subscribed(string $email, int $user_id = 0): bool {
		if (function_exists('bbb_reader_is_society') && bbb_reader_is_society()) {
			return true;
		}

		if ('' === $email || !function_exists('bbb_reader_supabase_request')) {
			return false;
		}

		$or = $user_id
			? sprintf('(wordpress_user_id.eq.%1$d,email_normalized.eq.%2$s,email.eq.%2$s,customer_email.eq.%2$s)', $user_id, $email)
			: sprintf('(email_normalized.eq.%1$s,email.eq.%1$s,customer_email.eq.%1$s)', $email);

		$rows = bbb_reader_supabase_request(
			'GET',
			'bookshelf_subscribers',
			array(
				'select' => 'email,email_normalized,customer_email,access_tier,society_key_used_at,weekly_email_opt_in,account_status',
				'or'     => $or,
				'limit'  => 10,
			)
		);

		if (is_wp_error($rows)) {
			return false;
		}

		foreach ((array) $rows as $row) {
			if (is_array($row) && bbb_home_personalized_subscriber_is_active($row)) {
				return true;
			}
		}

		return false;
	}
}

$identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$reader_email = is_array($identity) ? (string) ($identity['email'] ?? '') : '';
$reader_user_id = is_array($identity) ? (int) ($identity['userId'] ?? 0) : 0;
$bbb_home_mfy_local_preview = function_exists('bbb_reader_is_local_request')
	&& bbb_reader_is_local_request()
	&& isset($_GET['mfy_preview'])
	&& in_array((string) wp_unslash($_GET['mfy_preview']), array('1', 'free', 'paid'), true);
$bbb_home_mfy_is_paid = function_exists('bbb_reader_is_society') && bbb_reader_is_society();
$bbb_home_mfy_is_free = !$bbb_home_mfy_is_paid
	&& function_exists('bbb_society_reader_has_member_access')
	&& bbb_society_reader_has_member_access();
$bbb_home_mfy_access_tier = $bbb_home_mfy_is_paid ? 'society' : 'free';

if (!$bbb_home_mfy_local_preview && !$bbb_home_mfy_is_paid && !$bbb_home_mfy_is_free) {
	return;
}

if (function_exists('bbb_reader_access_tier_for_email') && '' !== $reader_email) {
	$bbb_home_mfy_access_tier = 'society' === bbb_reader_access_tier_for_email($reader_email, $reader_user_id) ? 'society' : 'free';
} elseif (function_exists('bbb_reader_access_tier')) {
	$bbb_home_mfy_access_tier = 'society' === bbb_reader_access_tier($reader_user_id) ? 'society' : 'free';
}

if ($bbb_home_mfy_local_preview && isset($_GET['mfy_preview']) && 'paid' === (string) wp_unslash($_GET['mfy_preview'])) {
	$bbb_home_mfy_access_tier = 'society';
} elseif ($bbb_home_mfy_local_preview && isset($_GET['mfy_preview']) && 'free' === (string) wp_unslash($_GET['mfy_preview'])) {
	$bbb_home_mfy_access_tier = 'free';
}

$bbb_home_mfy_member_label = 'society' === $bbb_home_mfy_access_tier ? 'paid member dashboard' : 'free member dashboard';
$bbb_home_mfy_profile = function_exists('bbb_reader_mfy_profile_for_identity')
	? bbb_reader_mfy_profile_for_identity((array) ($identity ?: array()))
	: array();
$bbb_home_mfy_profile_is_complete = function_exists('bbb_reader_mfy_profile_is_complete')
	? bbb_reader_mfy_profile_is_complete($bbb_home_mfy_profile)
	: (!empty($bbb_home_mfy_profile['dashboard_built']));

if (!$bbb_home_mfy_local_preview && !$bbb_home_mfy_profile_is_complete) {
	return;
}

if (!function_exists('bbb_reader_fetch_account_books_for_identity')) {
	return;
}

if (!function_exists('bbb_home_personalized_split_tropes')) {
	function bbb_home_personalized_split_tropes($value): array {
		if (function_exists('bbb_reader_split_book_tropes')) {
			return bbb_reader_split_book_tropes($value);
		}

		$items = is_array($value) ? $value : (preg_split('/[,|]/', (string) $value) ?: array());
		return array_values(
			array_filter(
				array_map(
					static function ($item): string {
						if (is_array($item)) {
							$item = $item['name'] ?? $item['label'] ?? $item['title'] ?? '';
						}

						return strtolower(trim(sanitize_text_field((string) $item)));
					},
					$items
				)
			)
		);
	}
}

if (!function_exists('bbb_home_personalized_title_case')) {
	function bbb_home_personalized_title_case(string $value): string {
		return ucwords(str_replace(array('-', '_'), ' ', strtolower(trim($value))));
	}
}

if (!function_exists('bbb_home_personalized_bool_attr')) {
	function bbb_home_personalized_bool_attr($value): string {
		if (function_exists('bbb_truthy')) {
			return bbb_truthy($value) ? 'true' : 'false';
		}

		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}

		$normalized = strtolower(trim((string) $value));
		return in_array($normalized, array('1', 'true', 'yes', 'y', 'on'), true) ? 'true' : 'false';
	}
}

if (!function_exists('bbb_home_personalized_local_url')) {
	function bbb_home_personalized_local_url(string $url): string {
		if ('' === trim($url)) {
			return '';
		}

		return set_url_scheme($url, is_ssl() ? 'https' : 'http');
	}
}

if (!function_exists('bbb_home_personalized_book_post')) {
	function bbb_home_personalized_book_post(array $book): ?WP_Post {
		$post_types = array_values(
			array_filter(
				array('bbb_book', 'sss_book'),
				static fn(string $post_type): bool => post_type_exists($post_type)
			)
		);

		if (!$post_types) {
			return null;
		}

		$handle = sanitize_title((string) ($book['handle'] ?? $book['book_handle'] ?? ''));
		if ('' !== $handle) {
			foreach ($post_types as $post_type) {
				$post = get_page_by_path($handle, OBJECT, $post_type);
				if ($post instanceof WP_Post) {
					return $post;
				}
			}
		}

		$title = trim((string) ($book['title'] ?? $book['book_title'] ?? ''));
		if ('' === $title) {
			return null;
		}

		$matches = get_posts(
			array(
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'title'                  => $title,
				'posts_per_page'         => 1,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);

		return $matches && $matches[0] instanceof WP_Post ? $matches[0] : null;
	}
}

if (!function_exists('bbb_home_personalized_enrich_book')) {
	function bbb_home_personalized_enrich_book(array $book): array {
		$post = bbb_home_personalized_book_post($book);
		if (!$post instanceof WP_Post || !function_exists('sss_book_data')) {
			return $book;
		}

		$data = sss_book_data($post);
		$full = array(
			'handle'              => $data['handle'] ?? $post->post_name,
			'title'               => $data['title'] ?? get_the_title($post),
			'author'              => $data['author'] ?? '',
			'cover'               => $data['cover'] ?? '',
			'amazon'              => $data['amazon'] ?? '',
			'bookshop'            => $data['bookshop'] ?? '',
			'spice'               => $data['spice'] ?? '',
			'darkness'            => $data['darkness'] ?? '',
			'tropes'              => $data['tropes'] ?? array(),
			'why'                 => $data['why'] ?? '',
			'mini'                => $data['mini'] ?? '',
			'newsletter'          => $data['newsletter'] ?? '',
			'series'              => $data['series_handle'] ?? '',
			'seriesName'          => $data['series_name'] ?? '',
			'seriesNumber'        => $data['series_number'] ?? '',
			'series_handle'       => $data['series_handle'] ?? '',
			'series_name'         => $data['series_name'] ?? '',
			'series_number'       => $data['series_number'] ?? '',
			'standalone'          => !empty($data['standalone']) ? 'true' : 'false',
			'tension'             => $data['tension'] ?? '',
			'damage'              => $data['damage'] ?? '',
			'yearning'            => $data['yearning'] ?? '',
			'boyfriend'           => $data['boyfriend'] ?? '',
			'boyfriend_name'      => $data['boyfriend_name'] ?? '',
			'boyfriendName'       => $data['boyfriend_name'] ?? '',
			'reread'              => !empty($data['reread']) ? 'true' : 'false',
			'ku'                  => !empty($data['ku']) ? 'true' : 'false',
			'on_ku'               => !empty($data['ku']) ? 'true' : 'false',
			'on_kindle_unlimited' => !empty($data['ku']) ? 'true' : 'false',
		);

		return array_merge($book, $full);
	}
}

if (!function_exists('bbb_home_personalized_enrich_books')) {
	function bbb_home_personalized_enrich_books(array $books): array {
		return array_map(
			static fn($book): array => is_array($book) ? bbb_home_personalized_enrich_book($book) : array(),
			$books
		);
	}
}

if (!function_exists('bbb_home_personalized_recommendations')) {
	function bbb_home_personalized_recommendations(array $saved_books, array $reader_type, int $limit = 3): array {
		if (!function_exists('bbb_reader_quiz_books')) {
			return array();
		}

		$saved_keys = array();
		foreach ($saved_books as $book) {
			if (!is_array($book)) {
				continue;
			}

			$key = bbb_home_personalized_book_key($book);
			if ('' !== $key) {
				$saved_keys[$key] = true;
			}
		}

		$top_tropes = is_array($reader_type['topTropes'] ?? null) ? array_map('strtolower', (array) $reader_type['topTropes']) : array();
		$library_books = bbb_reader_quiz_books();
		$scored = array();

		foreach ($library_books as $book) {
			if (!is_array($book)) {
				continue;
			}

			$key = bbb_home_personalized_book_key($book);
			if ('' === $key || isset($saved_keys[$key])) {
				continue;
			}

			$book_tropes = bbb_home_personalized_split_tropes($book['tropes'] ?? array());
			$score = 0;
			foreach ($top_tropes as $trope) {
				if (in_array($trope, $book_tropes, true)) {
					$score += 4;
				}
			}

			$score += (int) ($book['spice'] ?? 0) > 0 ? 1 : 0;
			$scored[] = array(
				'book'  => $book,
				'score' => $score,
			);
		}

		usort(
			$scored,
			static function (array $a, array $b): int {
				if ($a['score'] === $b['score']) {
					return strcmp((string) ($a['book']['title'] ?? ''), (string) ($b['book']['title'] ?? ''));
				}

				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice(array_column($scored, 'book'), 0, $limit);
	}
}

if (!function_exists('bbb_home_personalized_reader_emojis')) {
	function bbb_home_personalized_reader_emojis(array $reader_type, string $lead_trope): array {
		$signature = strtolower((string) ($reader_type['title'] ?? '') . ' ' . $lead_trope . ' ' . implode(' ', (array) ($reader_type['topTropes'] ?? array())));

		if (str_contains($signature, 'dark') || str_contains($signature, 'obsession') || str_contains($signature, 'morally') || str_contains($signature, 'villain')) {
			return array('🖤', '🗡️', '🌹', '🔥', '💋', '🕯️', '⛓️', '✨');
		}

		if (str_contains($signature, 'slow') || str_contains($signature, 'yearning') || str_contains($signature, 'forbidden')) {
			return array('💌', '🌙', '🕯️', '🥀', '✨', '📖', '💫', '🤍');
		}

		if (str_contains($signature, 'ache') || str_contains($signature, 'angst') || str_contains($signature, 'second chance')) {
			return array('🥀', '💔', '🌧️', '💌', '🕯️', '✨', '🤍', '📖');
		}

		if (str_contains($signature, 'romantasy') || str_contains($signature, 'fated') || str_contains($signature, 'magic') || str_contains($signature, 'fae')) {
			return array('⚔️', '✨', '🌙', '🗝️', '🔥', '👑', '💫', '📜');
		}

		if (str_contains($signature, 'heat') || str_contains($signature, 'spicy')) {
			return array('🌶️', '🔥', '💋', '🖤', '✨', '🥂', '🌹', '🕯️');
		}

		if (str_contains($signature, 'collector') || str_contains($signature, 'future')) {
			return array('📚', '✨', '🔖', '💌', '🌙', '🛒', '🖤', '📖');
		}

		return array('📚', '💌', '✨', '🌙', '🖤', '🔖', '📖', '🌹');
	}
}

if (!function_exists('bbb_home_personalized_trope_emoji')) {
	function bbb_home_personalized_trope_emoji(array $reader_type, string $lead_trope): string {
		$signature = strtolower((string) ($reader_type['title'] ?? '') . ' ' . $lead_trope);

		if (str_contains($signature, 'dark') || str_contains($signature, 'obsession') || str_contains($signature, 'morally') || str_contains($signature, 'villain')) {
			return '🖤';
		}

		if (str_contains($signature, 'slow') || str_contains($signature, 'yearning') || str_contains($signature, 'forbidden')) {
			return '💌';
		}

		if (str_contains($signature, 'ache') || str_contains($signature, 'angst') || str_contains($signature, 'second chance')) {
			return '🥀';
		}

		if (str_contains($signature, 'romantasy') || str_contains($signature, 'fated') || str_contains($signature, 'magic') || str_contains($signature, 'fae')) {
			return '⚔️';
		}

		if (str_contains($signature, 'heat') || str_contains($signature, 'spicy')) {
			return '🌶️';
		}

		if (str_contains($signature, 'collector') || str_contains($signature, 'future')) {
			return '📚';
		}

		return '✨';
	}
}

if (!function_exists('bbb_home_personalized_reader_type_from_tropes')) {
	function bbb_home_personalized_reader_type_from_tropes(array $reader_type, array $top_tropes, string $lead_trope): array {
		$registry = function_exists('bbb_reader_type_registry') ? bbb_reader_type_registry() : array();
		if (!$registry) {
			return array();
		}

		$signals = array_values(array_unique(array_filter(array_map(
			static fn($trope): string => sanitize_title((string) $trope),
			array_merge(array($lead_trope), $top_tropes)
		))));
		$signature = sanitize_title((string) ($reader_type['title'] ?? '') . ' ' . (string) ($reader_type['summary'] ?? ''));
		$best      = null;
		$best_score = 0;

		foreach ($registry as $type) {
			$score = 0;
			foreach ((array) ($type['triggers'] ?? array()) as $trigger) {
				$trigger_key = sanitize_title((string) $trigger);
				if ('' === $trigger_key) {
					continue;
				}

				if (in_array($trigger_key, $signals, true)) {
					$score += 4;
				} elseif (str_contains($signature, $trigger_key)) {
					$score += 2;
				}
			}

			if ($score > $best_score) {
				$best       = $type;
				$best_score = $score;
			}
		}

		if (is_array($best) && $best_score > 0) {
			return $best;
		}

		return function_exists('bbb_reader_type_by_key') ? (bbb_reader_type_by_key('romance_reader') ?: array()) : array();
	}
}

if (!function_exists('bbb_home_personalized_reader_type_from_mfy_profile')) {
	function bbb_home_personalized_reader_type_from_mfy_profile(array $profile): array {
		$key = sanitize_key((string) ($profile['reader_type_prior'] ?? $profile['theme'] ?? ''));
		if ('' === $key || !function_exists('bbb_reader_type_by_key')) {
			return array();
		}

		return bbb_reader_type_by_key($key) ?: array();
	}
}

if (!function_exists('bbb_home_personalized_theme_profiles')) {
	function bbb_home_personalized_theme_profiles(): array {
		return array(
			'dark_hearts'   => array('label' => 'curated in black tabs', 'emojis' => array('🖤')),
			'obsession_red' => array('label' => 'curated for high heat', 'emojis' => array('🌶️')),
			'rose_ribbon'   => array('label' => 'curated for soft yearning', 'emojis' => array('💌')),
			'stormy_blue'   => array('label' => 'curated for broody tension', 'emojis' => array('🌧️')),
			'pearl_white'   => array('label' => 'curated for comfort reads', 'emojis' => array('📚')),
			'royal_violet'  => array('label' => 'curated for dramatic magic', 'emojis' => array('⚔️')),
		);
	}
}

if (!function_exists('bbb_home_personalized_theme_from_signature')) {
	function bbb_home_personalized_theme_from_signature(string $signature): string {
		$themes = array_keys(bbb_home_personalized_theme_profiles());
		$index = abs((int) crc32('bbb-home-mfy-' . strtolower($signature))) % count($themes);
		return $themes[$index];
	}
}

$saved_books = '' !== $reader_email ? bbb_home_personalized_enrich_books(bbb_reader_fetch_account_books_for_identity($reader_email, $reader_user_id)) : array();
$statuses = '' !== $reader_email && function_exists('bbb_reader_fetch_account_book_statuses_for_identity')
	? bbb_reader_fetch_account_book_statuses_for_identity($reader_email, $reader_user_id)
	: array();
$insights = function_exists('bbb_reader_account_insights')
	? bbb_reader_account_insights($saved_books, $statuses)
	: array('books' => $saved_books, 'readerType' => array(), 'nextRead' => null);
$books = is_array($insights['books'] ?? null) ? (array) $insights['books'] : $saved_books;
$reader_type = is_array($insights['readerType'] ?? null) ? (array) $insights['readerType'] : array();
$top_tropes = is_array($reader_type['topTropes'] ?? null) ? array_values(array_filter((array) $reader_type['topTropes'])) : array();
$lead_trope = $top_tropes ? (string) $top_tropes[0] : 'romance';
$lead_trope_count = 0;

foreach ($books as $book) {
	if (!is_array($book)) {
		continue;
	}

	if (in_array(strtolower($lead_trope), bbb_home_personalized_split_tropes($book['tropes'] ?? array()), true)) {
		++$lead_trope_count;
	}
}

$saved_count = count($books);
$lead_count = $lead_trope_count > 0 ? $lead_trope_count : $saved_count;
$recommendations = bbb_home_personalized_recommendations($books, $reader_type, 3);
$next_read = is_array($insights['nextRead'] ?? null) ? (array) $insights['nextRead'] : null;

if (!$recommendations && $next_read) {
	$recommendations = array(
		array(
			'handle' => (string) ($next_read['book_handle'] ?? ''),
			'title'  => (string) ($next_read['book_title'] ?? ''),
			'author' => (string) ($next_read['author'] ?? ''),
			'cover'  => (string) ($next_read['cover'] ?? ''),
			'tropes' => $next_read['tropes'] ?? array(),
			'url'    => !empty($next_read['book_handle']) ? home_url('/library/?book=' . rawurlencode((string) $next_read['book_handle'])) : home_url('/library/'),
		),
	);
}

	$what_to_read_next = $recommendations && is_array($recommendations[0]) ? $recommendations[0] : null;
	if (!$what_to_read_next && $next_read) {
		$what_to_read_next = array(
		'handle' => (string) ($next_read['book_handle'] ?? $next_read['handle'] ?? ''),
		'title'  => (string) ($next_read['book_title'] ?? $next_read['title'] ?? ''),
		'author' => (string) ($next_read['author'] ?? ''),
		'cover'  => (string) ($next_read['cover'] ?? ''),
		'tropes' => $next_read['tropes'] ?? array(),
			'url'    => !empty($next_read['book_handle']) ? home_url('/library/?book=' . rawurlencode((string) $next_read['book_handle'])) : home_url('/library/'),
		);
	}

	$dashboard_picks = array(
		array(
			'label'       => 'top book rec',
			'book'        => null,
			'empty_title' => 'profile-matched picks',
			'empty_note'  => 'answer made for you to tune this pick',
		),
		array(
			'label'       => 'fictional bf match',
			'book'        => null,
			'empty_title' => 'fictional boyfriend match',
			'empty_note'  => 'answer made for you to lock this in',
		),
	);
$reader_name = is_array($identity) ? trim((string) ($identity['displayName'] ?? '')) : '';
$account_storage_key = $reader_user_id > 0
	? 'user-' . $reader_user_id
	: ('' !== $reader_email ? 'email-' . md5(strtolower($reader_email)) : '');
if (is_user_logged_in()) {
	$current_user = wp_get_current_user();
	$reader_name = trim((string) ($current_user->display_name ?: $current_user->user_firstname));
}
	$reader_name = str_contains($reader_name, '@') ? '' : $reader_name;
	$reader_first_name = '' !== $reader_name ? strtok($reader_name, ' ') : '';
	$headline = '' !== $reader_first_name ? sprintf('made for %s', strtolower($reader_first_name)) : 'made for you';
	$dashboard_url = bbb_home_personalized_local_url(function_exists('bbb_page_url') ? bbb_page_url('made-for-you') : home_url('/made-for-you/'));
	$monthly_theme_url = bbb_home_personalized_local_url(function_exists('bbb_page_url') ? bbb_page_url('monthly-theme') : home_url('/monthly-theme/'));
$read_count = 0;
foreach ($statuses as $status) {
	if (!is_array($status)) {
		continue;
	}

	if ('read' === strtolower(trim((string) ($status['status'] ?? '')))) {
		++$read_count;
	}
}
$next_read_title = $recommendations && is_array($recommendations[0])
	? trim((string) ($recommendations[0]['title'] ?? $recommendations[0]['book_title'] ?? ''))
	: '';
$home_reader_type = bbb_home_personalized_reader_type_from_mfy_profile($bbb_home_mfy_profile);
if (!$home_reader_type) {
	$home_reader_type = bbb_home_personalized_reader_type_from_tropes($reader_type, $top_tropes, $lead_trope);
}
$reader_type_title = trim((string) ($home_reader_type['label'] ?? $reader_type['title'] ?? 'mood-led romance reader'));
$reader_type_summary = trim((string) ($home_reader_type['bio'] ?? $home_reader_type['signal'] ?? $reader_type['summary'] ?? 'your saved shelf is still learning your pattern.'));
$theme_profiles = bbb_home_personalized_theme_profiles();
$fallback_theme_signature = $reader_email . '|' . $reader_user_id . '|' . $reader_type_title . '|' . implode(',', $top_tropes) . '|' . $lead_trope;
$dashboard_theme = bbb_home_personalized_theme_from_signature($fallback_theme_signature);
$theme_label = (string) ($home_reader_type['theme']['name'] ?? $theme_profiles[$dashboard_theme]['label'] ?? $theme_profiles['rose_ribbon']['label']);
$theme_emojis = (array) ($theme_profiles[$dashboard_theme]['emojis'] ?? $theme_profiles['rose_ribbon']['emojis']);
$emoji_fall = $theme_emojis;
$reader_type_emoji = (string) ($theme_emojis[0] ?? bbb_home_personalized_trope_emoji($reader_type, $lead_trope));
$top_trope_pool = $top_tropes;
foreach (array_merge($books, $recommendations, array_filter(array($what_to_read_next))) as $trope_book) {
	if (!is_array($trope_book)) {
		continue;
	}

	foreach (bbb_home_personalized_split_tropes($trope_book['tropes'] ?? array()) as $trope) {
		if ('' !== $trope && !in_array($trope, $top_trope_pool, true)) {
			$top_trope_pool[] = $trope;
		}
	}
}

foreach (array('romance', 'slow burn', 'forbidden romance') as $fallback_trope) {
	if (count($top_trope_pool) >= 3) {
		break;
	}

	if (!in_array($fallback_trope, $top_trope_pool, true)) {
		$top_trope_pool[] = $fallback_trope;
	}
}

$top_trope_labels = array_slice(
	array_map(
		static fn($trope): string => strtolower(bbb_home_personalized_title_case((string) $trope)),
		$top_trope_pool
	),
	0,
	3
);
?>
<section
	class="bbb-home-shelf-week bbb-home-shelf-week--dashboard bbb-home-shelf-week--<?php echo esc_attr($bbb_home_mfy_access_tier); ?> is-home-mfy-locked"
	id="bbbHomeDashboard"
	aria-label="made for you member dashboard"
	data-sss-lib="society"
	data-home-mfy-dashboard
	data-home-mfy-locked="true"
	data-home-mfy-server-ready="<?php echo esc_attr($bbb_home_mfy_profile_is_complete ? 'true' : 'false'); ?>"
	data-home-mfy-tier="<?php echo esc_attr($bbb_home_mfy_access_tier); ?>"
	data-server-reader-type="<?php echo esc_attr((string) ($home_reader_type['key'] ?? '')); ?>"
	data-dashboard-url="<?php echo esc_url($dashboard_url); ?>"
>
	<div class="bbb-home-shelf-week__inner">
		<div class="bbb-home-shelf-week__emojiFall" aria-hidden="true">
			<?php for ($index = 0; $index < 8; ++$index) : ?>
				<span style="--fall-index: <?php echo esc_attr((string) $index); ?>;"><?php echo esc_html($reader_type_emoji); ?></span>
			<?php endfor; ?>
		</div>
			<div class="bbb-home-shelf-week__copy">
				<p class="bbb-home-shelf-week__kicker"><?php echo esc_html($bbb_home_mfy_member_label); ?></p>
				<h2><?php echo esc_html($headline); ?></h2>
				<div class="bbb-home-shelf-week__readerCard" aria-label="reader dashboard stat">
				<div class="bbb-home-shelf-week__readerCardTop">
					<span class="bbb-home-shelf-week__readerEmoji" aria-hidden="true"><?php echo esc_html($reader_type_emoji); ?></span>
					<div>
						<span class="bbb-home-shelf-week__readerCardLabel">reader type</span>
						<strong><?php echo esc_html($reader_type_title); ?></strong>
						<small><?php echo esc_html($theme_label); ?></small>
					</div>
					</div>
					<p><?php echo esc_html($reader_type_summary); ?></p>
					<div class="bbb-home-shelf-week__statGrid" aria-label="dashboard stats">
					<div class="bbb-home-shelf-week__miniStat">
						<span>saved</span>
						<strong><?php echo esc_html((string) $saved_count); ?></strong>
						<small><?php echo esc_html(1 === $saved_count ? 'book' : 'books'); ?></small>
					</div>
					<div class="bbb-home-shelf-week__miniStat">
						<span>read</span>
						<strong><?php echo esc_html((string) $read_count); ?></strong>
						<small><?php echo esc_html(1 === $read_count ? 'finished' : 'finished'); ?></small>
					</div>
					<div class="bbb-home-shelf-week__miniStat bbb-home-shelf-week__miniStat--wide">
						<span>top trope</span>
						<strong><?php echo esc_html((string) ($top_trope_labels[0] ?? 'romance')); ?></strong>
						<small><?php echo esc_html($lead_count > 0 ? ((string) $lead_count . ' matching ' . (1 === $lead_count ? 'save' : 'saves')) : 'learning'); ?></small>
					</div>
				</div>
			</div>
			<div class="bbb-home-shelf-week__actions">
				<a class="bbb-home-shelf-week__action bbb-home-shelf-week__action--mfy" href="<?php echo esc_url($dashboard_url); ?>">open dashboard</a>
			</div>
		</div>

			<div class="bbb-home-shelf-week__panel<?php echo 'free' === $bbb_home_mfy_access_tier ? ' bbb-home-shelf-week__panel--free' : ''; ?>" aria-label="made for you preview">
				<div class="bbb-home-shelf-week__panelHead">
					<div>
						<span>from your dashboard</span>
						<strong>top book rec + fictional bf match</strong>
					</div>
					<a href="<?php echo esc_url($dashboard_url); ?>">view all</a>
				</div>
			<div class="bbb-home-shelf-week__rail" aria-label="your made for you bookshelf">
				<?php foreach ($dashboard_picks as $index => $pick) : ?>
					<?php
					if (!is_array($pick['book'])) :
					?>
					<div class="bbb-home-shelf-week__book bbb-home-shelf-week__pick bbb-home-shelf-week__pick--empty" style="--i: <?php echo esc_attr((string) $index); ?>;">
						<span class="bbb-home-shelf-week__pickLabel"><?php echo esc_html((string) $pick['label']); ?></span>
						<span class="bbb-home-shelf-week__cover" aria-hidden="true"><span>+</span></span>
						<span class="bbb-home-shelf-week__meta">
							<strong><?php echo esc_html((string) $pick['empty_title']); ?></strong>
							<span><?php echo esc_html((string) $pick['empty_note']); ?></span>
						</span>
					</div>
					<?php
						continue;
					endif;

					$book = (array) $pick['book'];
					$title = trim((string) ($book['title'] ?? $book['book_title'] ?? ''));
					$author = trim((string) ($book['author'] ?? ''));
					$cover = trim((string) ($book['cover'] ?? ''));
					$handle = trim((string) ($book['handle'] ?? $book['book_handle'] ?? ''));
					$tropes = bbb_home_personalized_split_tropes($book['tropes'] ?? array());
					$tropes_display = implode(', ', array_map('bbb_home_personalized_title_case', $tropes));
					$ku_value = $book['ku'] ?? $book['on_ku'] ?? $book['on_kindle_unlimited'] ?? $book['kindle_unlimited'] ?? $book['is_ku'] ?? false;
					$spice_count = (int) ($book['spice'] ?? $book['spice_level'] ?? 0);
					$book_post = bbb_home_personalized_book_post($book);
					$book_url = $book_post instanceof WP_Post
						? (string) get_permalink($book_post)
						: (string) ($book['url'] ?? ('' !== $handle ? home_url('/books/' . rawurlencode($handle) . '/') : ''));
					$book_url = bbb_home_personalized_local_url($book_url);
					?>
					<button
						class="sss-lib__book bbb-home-shelf-week__book bbb-home-shelf-week__pick"
						type="button"
						style="--i: <?php echo esc_attr((string) $index); ?>;"
						data-handle="<?php echo esc_attr($handle); ?>"
						data-url="<?php echo esc_url($book_url); ?>"
						data-title="<?php echo esc_attr($title); ?>"
						data-author="<?php echo esc_attr($author); ?>"
						data-cover="<?php echo esc_url($cover); ?>"
						data-amazon="<?php echo esc_url((string) ($book['amazon'] ?? '')); ?>"
						data-bookshop="<?php echo esc_url((string) ($book['bookshop'] ?? '')); ?>"
						data-newsletter="<?php echo esc_url((string) ($book['newsletter'] ?? '')); ?>"
						data-spice="<?php echo esc_attr((string) ($book['spice'] ?? $book['spice_level'] ?? '')); ?>"
						data-ku="<?php echo esc_attr(bbb_home_personalized_bool_attr($ku_value)); ?>"
						data-tropes="<?php echo esc_attr(implode(', ', $tropes)); ?>"
						data-tropes-display="<?php echo esc_attr($tropes_display); ?>"
						data-mini="<?php echo esc_attr((string) ($book['mini'] ?? $book['mini_note'] ?? '')); ?>"
						data-why="<?php echo esc_attr((string) ($book['why'] ?? $book['review_note'] ?? '')); ?>"
						data-tension="<?php echo esc_attr((string) ($book['tension'] ?? '')); ?>"
						data-damage="<?php echo esc_attr((string) ($book['damage'] ?? $book['emotional_damage'] ?? '')); ?>"
						data-yearning="<?php echo esc_attr((string) ($book['yearning'] ?? '')); ?>"
						data-boyfriend="<?php echo esc_attr((string) ($book['boyfriend'] ?? $book['book_boyfriend'] ?? '')); ?>"
						data-boyfriend-name="<?php echo esc_attr((string) ($book['boyfriendName'] ?? $book['boyfriend_name'] ?? '')); ?>"
						data-darkness="<?php echo esc_attr((string) ($book['darkness'] ?? '')); ?>"
						data-reread="<?php echo esc_attr((string) ($book['reread'] ?? $book['reread_worthy'] ?? '')); ?>"
						data-series="<?php echo esc_attr((string) ($book['series'] ?? $book['series_handle'] ?? '')); ?>"
						data-series-name="<?php echo esc_attr((string) ($book['seriesName'] ?? $book['series_name'] ?? '')); ?>"
						data-series-number="<?php echo esc_attr((string) ($book['seriesNumber'] ?? $book['series_number'] ?? '')); ?>"
						data-standalone="<?php echo esc_attr((string) ($book['standalone'] ?? '')); ?>"
						aria-label="<?php echo esc_attr(sprintf('open details for %s', $title ?: 'this society pick')); ?>"
					>
						<span class="bbb-home-shelf-week__pickLabel"><?php echo esc_html((string) $pick['label']); ?></span>
						<span class="bbb-home-shelf-week__cover" aria-hidden="true">
							<?php if ('' !== $cover) : ?>
								<img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy">
							<?php else : ?>
								<span><?php echo esc_html(substr($title ?: 'b', 0, 1)); ?></span>
							<?php endif; ?>
							<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
								<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
								<span class="sss-lib__heartLabel" data-heart-label>save</span>
							</span>
							<?php if ($spice_count > 0) : ?>
								<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice_count)); ?></span>
							<?php endif; ?>
						</span>
						<span class="bbb-home-shelf-week__meta">
							<strong><?php echo esc_html($title ?: 'society pick'); ?></strong>
							<?php if ('' !== $author) : ?>
								<span><?php echo esc_html($author); ?></span>
							<?php endif; ?>
						</span>
					</button>
					<?php endforeach; ?>
				</div>
				</div>
				<?php if ('society' === $bbb_home_mfy_access_tier) : ?>
				<div class="bbb-home-shelf-week__lockedPrompt" data-home-mfy-lock-prompt>
					<span>reader profile locked</span>
					<strong>answer made for you</strong>
				<p>finish the questions to unlock your reader type, trope pattern, theme, and dashboard picks.</p>
				<a href="<?php echo esc_url($dashboard_url); ?>">answer made for you</a>
			</div>
			<?php endif; ?>
		</div>
	</section>
<style>
#bbbHomeDashboard[data-mfy-theme="dark_hearts"]  { --dash-accent: #c4a882; --dash-glow: rgba(196,168,130,.09); }
#bbbHomeDashboard[data-mfy-theme="obsession_red"] { --dash-accent: #e8525a; --dash-glow: rgba(232,82,90,.13); }
#bbbHomeDashboard[data-mfy-theme="rose_ribbon"]   { --dash-accent: #e891b8; --dash-glow: rgba(232,145,184,.13); }
#bbbHomeDashboard[data-mfy-theme="stormy_blue"]   { --dash-accent: #7bb8e8; --dash-glow: rgba(123,184,232,.13); }
#bbbHomeDashboard[data-mfy-theme="pearl_white"]   { --dash-accent: #e8d5b0; --dash-glow: rgba(232,213,176,.09); }
#bbbHomeDashboard[data-mfy-theme="royal_violet"]  { --dash-accent: #b48de8; --dash-glow: rgba(180,141,232,.13); }
#bbbHomeDashboard[data-mfy-theme] .bbb-home-shelf-week__kicker,
#bbbHomeDashboard[data-mfy-theme] .bbb-home-shelf-week__miniStat > span { color: var(--dash-accent); }
	#bbbHomeDashboard[data-mfy-theme] .bbb-home-shelf-week__action--mfy { background: var(--dash-accent); border-color: var(--dash-accent); color: #160912; }
	#bbbHomeDashboard[data-mfy-theme] .bbb-home-shelf-week__pickLabel { color: var(--dash-accent); opacity: .85; }
	#bbbHomeDashboard .bbb-home-shelf-week__readerEmoji .bbb-custom-emoji { width: 100%; height: 100%; object-fit: contain; display: block; }
	#bbbHomeDashboard .bbb-home-shelf-week__emojiFall .bbb-custom-emoji { width: 1.35em; height: 1.35em; object-fit: contain; display: block; }
	#bbbHomeDashboard[data-reader-theme] { color: var(--home-mfy-heading); }
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__inner {
	background:
		radial-gradient(circle at 9% 8%, var(--home-mfy-accent-soft), transparent 28rem),
		radial-gradient(circle at 92% 10%, var(--home-mfy-accent-cool), transparent 24rem),
		linear-gradient(135deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.018)),
		var(--home-mfy-panel) !important;
}
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__readerCard,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__miniStat,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__panel,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__book {
	background:
		radial-gradient(circle at 12% 0%, var(--home-mfy-accent-soft), transparent 18rem),
		linear-gradient(180deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.018)),
		var(--home-mfy-card) !important;
}
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__cover,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__pick--empty .bbb-home-shelf-week__cover {
	background:
		linear-gradient(135deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.015)),
		var(--home-mfy-panel) !important;
}
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__inner,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__readerCard,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__miniStat,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__panel,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__book { border-color: var(--home-mfy-border); }
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__kicker,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__readerCardLabel,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__miniStat > span,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__pickLabel { color: var(--home-mfy-accent); }
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__readerCard strong,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__miniStat strong { color: var(--home-mfy-heading); }
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__copy > p:not(.bbb-home-shelf-week__kicker),
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__readerCard p { color: var(--home-mfy-body); }
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__miniStat small,
#bbbHomeDashboard[data-reader-theme] .bbb-home-shelf-week__meta span { color: var(--home-mfy-muted); }
</style>
<script>
(function () {
	var el = document.getElementById('bbbHomeDashboard');
	if (!el) { return; }
		var readerTypes = <?php echo wp_json_encode(function_exists('bbb_reader_type_registry') ? array_values(bbb_reader_type_registry()) : array()); ?> || [];
		var quizBooks = <?php echo wp_json_encode(function_exists('bbb_reader_quiz_books') ? array_values(bbb_reader_quiz_books()) : array()); ?> || [];
		var boyfriendProfiles = <?php echo wp_json_encode(function_exists('bbb_reader_quiz_boyfriend_profiles') ? array_values(bbb_reader_quiz_boyfriend_profiles()) : array()); ?> || [];
		var accountProfile = <?php echo wp_json_encode($bbb_home_mfy_profile); ?> || {};
	var profileVersion = '<?php echo esc_js(function_exists('bbb_reader_mfy_profile_version') ? bbb_reader_mfy_profile_version() : 'mfy-2026-06-11-reader-types'); ?>';
	var accountStorageKey = '<?php echo esc_js($account_storage_key); ?>';
	var themes = {
		dark_hearts: { label: 'curated in black tabs', emojis: ['🖤'] },
		obsession_red: { label: 'curated for high heat', emojis: ['🌶️'] },
		rose_ribbon: { label: 'curated for soft yearning', emojis: ['💌'] },
		stormy_blue: { label: 'curated for broody tension', emojis: ['🌧️'] },
		pearl_white: { label: 'curated for comfort reads', emojis: ['📚'] },
		royal_violet: { label: 'curated for dramatic magic', emojis: ['⚔️'] }
	};

	function bookDataFromButton(button) {
		var data = button && button.dataset ? button.dataset : {};
		return {
			handle: data.handle || '',
			url: data.url || '',
			title: data.title || '',
			author: data.author || '',
			cover: data.cover || '',
			amazon: data.amazon || '',
			bookshop: data.bookshop || '',
			newsletter: data.newsletter || '',
			spice: data.spice || '',
			ku: data.ku || '',
			tropes: data.tropes || '',
			tropesDisplay: data.tropesDisplay || data.tropes || '',
			mini: data.mini || '',
			why: data.why || '',
			tension: data.tension || '',
			damage: data.damage || '',
			yearning: data.yearning || '',
			boyfriend: data.boyfriend || '',
			boyfriendName: data.boyfriendName || '',
			darkness: data.darkness || '',
			reread: data.reread || '',
			series: data.series || '',
			seriesName: data.seriesName || '',
			seriesNumber: data.seriesNumber || '',
			standalone: data.standalone || '',
			privateShelf: data.privateShelf || 'false'
		};
	}

	el.addEventListener('click', function(event){
		var button = event.target && event.target.closest ? event.target.closest('.bbb-home-shelf-week__book.sss-lib__book[data-title]') : null;
		if (!button || !el.contains(button)) { return; }
		if (event.target.closest('[data-heart]')) { return; }
		if (typeof window.sssOpenBookModal !== 'function') { return; }

		event.preventDefault();
		event.stopPropagation();
		if (typeof event.stopImmediatePropagation === 'function') {
			event.stopImmediatePropagation();
		}
		window.sssOpenBookModal(bookDataFromButton(button), button);
	}, true);

	function getReaderType(key) {
		key = String(key || '').trim();
		return readerTypes.find(function(type){
			return String(type && type.key || '') === key;
		}) || readerTypes.find(function(type){
			return String(type && type.key || '') === 'romance_reader';
		}) || null;
	}

	function customEmojiUrl(slug) {
		slug = String(slug || '').trim();
		return slug ? '/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + slug + '.png' : '';
	}

	function renderCustomEmoji(target, slug) {
		if (!target || !slug) { return; }
		target.textContent = '';
		var img = document.createElement('img');
		img.className = 'bbb-custom-emoji';
		img.src = customEmojiUrl(slug);
		img.alt = '';
		img.setAttribute('aria-hidden', 'true');
		target.appendChild(img);
	}

	function applyReaderTypeEmoji(readerType) {
		if (!readerType || !readerType.emoji) { return; }
		renderCustomEmoji(el.querySelector('.bbb-home-shelf-week__readerEmoji'), readerType.emoji);
		el.querySelectorAll('.bbb-home-shelf-week__emojiFall span').forEach(function(node){
			renderCustomEmoji(node, readerType.emoji);
		});
	}

	function applyReaderType(readerType) {
		if (!readerType) { return false; }
		var theme = readerType.theme || {};
		el.classList.remove('is-home-mfy-locked');
		el.classList.add('is-home-mfy-ready');
		el.setAttribute('data-home-mfy-locked', 'false');
		el.setAttribute('data-reader-theme', readerType.key || 'romance_reader');
		el.setAttribute('data-mfy-theme', readerType.key || 'romance_reader');
		el.style.setProperty('--dash-accent', theme.accent || '#D4C2CE');
		el.style.setProperty('--dash-glow', theme.glow || 'rgba(212,194,206,.08)');
		el.style.setProperty('--home-mfy-accent', theme.accent || '#D4C2CE');
		el.style.setProperty('--home-mfy-accent-soft', theme.glow || 'rgba(212,194,206,.10)');
		el.style.setProperty('--home-mfy-accent-cool', theme.glow || 'rgba(212,194,206,.08)');
		el.style.setProperty('--home-mfy-panel', theme.surface || '#131013');
		el.style.setProperty('--home-mfy-card', theme.surface || 'rgba(0,0,0,.22)');
		el.style.setProperty('--home-mfy-border', theme.border || '#2E282C');
		el.style.setProperty('--home-mfy-heading', theme.textHeading || '#FAF6F8');
		el.style.setProperty('--home-mfy-body', theme.textBody || '#EAE2E6');
		el.style.setProperty('--home-mfy-muted', theme.textMuted || '#A89AA1');

		var typeName = el.querySelector('.bbb-home-shelf-week__readerCard strong');
		if (typeName) {
			typeName.textContent = readerType.label || 'the romance reader';
		}
		var label = el.querySelector('.bbb-home-shelf-week__readerCard small');
		if (label) {
			label.textContent = theme.name || 'unsorted silver';
		}
		var summary = el.querySelector('.bbb-home-shelf-week__readerCard > p');
		if (summary) {
			summary.textContent = readerType.bio || readerType.signal || summary.textContent;
		}
		applyReaderTypeEmoji(readerType);
		return true;
	}

	function applyTheme(theme) {
		if (!theme || !themes[theme]) { return; }
		el.classList.remove('is-home-mfy-locked');
		el.classList.add('is-home-mfy-ready');
		el.setAttribute('data-home-mfy-locked', 'false');
		el.setAttribute('data-home-mfy-theme', theme);
		el.setAttribute('data-mfy-theme', theme);
		var badge = el.querySelector('.bbb-home-shelf-week__readerEmoji');
		if (badge) {
			badge.textContent = themes[theme].emojis[0] || badge.textContent;
		}
		var label = el.querySelector('.bbb-home-shelf-week__readerCard small');
		if (label) {
			label.textContent = themes[theme].label;
		}
		var emojiNodes = el.querySelectorAll('.bbb-home-shelf-week__emojiFall span');
		emojiNodes.forEach(function(node){
			node.textContent = themes[theme].emojis[0] || '';
		});
	}

	function normalize(value) {
		return String(value || '').trim().toLowerCase();
	}

	function profileTime(profile) {
		var raw = profile && (profile.updatedAt || profile.updated_at || '');
		var time = raw ? Date.parse(String(raw)) : 0;
		return Number.isFinite(time) ? time : 0;
	}

	function isNewerProfile(candidate, current) {
		if (!candidate || typeof candidate !== 'object' || !Object.keys(candidate).length) { return false; }
		if (!current || typeof current !== 'object' || !Object.keys(current).length) { return true; }
		return profileTime(candidate) > profileTime(current);
	}

	function isCurrentProfile(candidate) {
		if (!candidate || typeof candidate !== 'object' || !Object.keys(candidate).length) { return false; }
		return String(candidate.mfy_profile_version || candidate.profile_version || '') === profileVersion;
	}

	function scopedStorageKey(key) {
		return accountStorageKey ? key + '::' + accountStorageKey : key;
	}

	function readJsonStorage(key, fallback) {
		try {
			return JSON.parse(localStorage.getItem(scopedStorageKey(key)) || '') || fallback;
		} catch (e) {
			return fallback;
		}
	}

	function writeJsonStorage(key, value) {
		localStorage.setItem(scopedStorageKey(key), JSON.stringify(value));
		if (accountStorageKey) {
			localStorage.removeItem(key);
		}
	}

	function slug(value) {
		return normalize(value).replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
	}

	function getBookKey(book) {
		return normalize(book && (book.handle || book.book_handle || book.title || book.book_title));
	}

	function getStoredShelfKeys() {
		var keys = {};
		['sssMyShelf', 'sssShelf'].forEach(function(storageKey){
			try {
				(JSON.parse(localStorage.getItem(storageKey) || '[]') || []).forEach(function(item){
					var key = getBookKey(item);
					if (key) {
						keys[key] = true;
					}
				});
			} catch (e) {}
		});
		return keys;
	}

	function getProfileSpice(profile) {
		var map = {
			soft_open_door: 1,
			some_heat: 2,
			balanced: 3,
			high_spice: 4,
			wreck_me: 5,
			sweet: 1,
			medium: 3,
			hot: 4,
			dark: 5
		};
		var direct = parseInt(profile.spice_profile || '', 10);
		var stored = parseInt(localStorage.getItem(scopedStorageKey('bbbReaderSpiceProfile')) || (!accountStorageKey ? localStorage.getItem('bbbReaderSpiceProfile') : '') || '', 10);
		var dial = profile.spice_dial || profile.heat_lane || '';
		return direct || stored || map[dial] || 0;
	}

	function getProfileFallbackBooks(profile, limit) {
		if (!quizBooks.length || !profile) {
			return [];
		}

		var savedKeys = getStoredShelfKeys();
		if (!profile.dashboard_built) {
			return [];
		}

		var readerType = getReaderType(profile.reader_type_prior || '');
		var triggers = readerType ? (readerType.triggers || []).map(slug) : [];
		var favoriteTrope = slug(profile.favorite_trope || '');
		var spice = getProfileSpice(profile);
		var quizRecs = Array.isArray(profile.quiz_recommendations) ? profile.quiz_recommendations : [];
		var quizRecKeys = {};
		quizRecs.forEach(function(rec){
			var recKey = getBookKey(rec);
			if (recKey) {
				quizRecKeys[recKey] = true;
			}
		});
		var isDarkReader = (readerType && readerType.key === 'dark_romance_girlie') || favoriteTrope.indexOf('dark') > -1;
		var darkSignals = ['dark-romance', 'dark', 'mafia-romance', 'stalker-romance', 'touch-her-and-die', 'morally-gray'];
		var sportsSignals = ['sports', 'hockey-romance', 'baseball-romance', 'football-romance'];

		return quizBooks.map(function(book, index){
			var key = getBookKey(book);
			var bookTropes = (book.tropes || []).map(slug);
			var shelf = slug(book.shelfSlug || book.shelf || '');
			var score = 0;

			if (!key || savedKeys[key]) {
				return null;
			}

			if (favoriteTrope && bookTropes.indexOf(favoriteTrope) > -1) {
				score += 12;
			}

			triggers.forEach(function(trigger){
				if (!trigger) {
					return;
				}
				if (bookTropes.indexOf(trigger) > -1) {
					score += 8;
				}
				if (shelf && (shelf === trigger || shelf.indexOf(trigger) > -1 || trigger.indexOf(shelf) > -1)) {
					score += 5;
				}
			});

			if (spice && book.spice) {
				score += Math.max(0, 5 - Math.abs(Number(book.spice || 0) - spice));
			}

			if (readerType && readerType.key === 'chaos_reader' && Number(book.spice || 0) >= 4) score += 6;
			if (readerType && readerType.key === 'dark_romance_girlie' && (Number(book.darkness || 0) >= 3 || shelf.indexOf('dark') > -1)) score += 16;
			if (readerType && readerType.key === 'fantasy_girlie' && (shelf.indexOf('fantasy') > -1 || shelf.indexOf('romantasy') > -1)) score += 6;
			if (readerType && readerType.key === 'jersey_chaser' && (shelf.indexOf('sports') > -1 || bookTropes.indexOf('hockey-romance') > -1)) score += 6;
			if (readerType && readerType.key === 'slow_burn_girlie' && (Number(book.yearning || 0) >= 3 || bookTropes.indexOf('slow-burn') > -1)) score += 6;
			if (readerType && readerType.key === 'tension_addict' && (Number(book.tension || 0) >= 3 || bookTropes.indexOf('enemies-to-lovers') > -1)) score += 6;
			if (readerType && readerType.key === 'fake_dating_fanatic' && (bookTropes.indexOf('fake-dating') > -1 || bookTropes.indexOf('marriage-of-convenience') > -1)) score += 7;
			if (readerType && readerType.key === 'sweet_romance_devotee' && (Number(book.spice || 0) <= 2 || bookTropes.indexOf('friends-to-lovers') > -1)) score += 5;
			if (isDarkReader && darkSignals.some(function(signal){ return shelf.indexOf(signal) > -1 || bookTropes.indexOf(signal) > -1; })) score += 12;
			if (isDarkReader && sportsSignals.some(function(signal){ return shelf.indexOf(signal) > -1 || bookTropes.indexOf(signal) > -1; })) score -= 12;
			if (quizRecKeys[key] && score > 0) score += 1;

			return { book: book, score: score + Math.max(0, quizBooks.length - index) / 1000 };
		}).filter(function(entry){
			return entry && entry.score > 0;
		}).sort(function(a, b){
			return b.score - a.score;
		}).map(function(entry){
			return entry.book;
		}).slice(0, limit || 2);
	}

	function esc(value) {
		return String(value || '').replace(/[&<>"']/g, function(char){
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
		});
	}

		function renderFallbackPick(book, label) {
		var title = book.title || book.book_title || 'society pick';
		var author = book.author || '';
		var cover = book.cover || '';
		var handle = book.handle || book.book_handle || '';
		var tropes = Array.isArray(book.tropes) ? book.tropes.join(', ') : String(book.tropes || '');
		var tropesDisplay = Array.isArray(book.tropesDisplay) ? book.tropesDisplay.join(', ') : (book.tropesDisplay || tropes);
		var spice = Math.max(0, Math.min(5, parseInt(book.spice || book.spice_level || '0', 10) || 0));

		return '<button class="sss-lib__book bbb-home-shelf-week__book bbb-home-shelf-week__pick" type="button"' +
			' data-handle="' + esc(handle) + '"' +
			' data-url="' + esc(book.url || (handle ? '/library/?book=' + encodeURIComponent(handle) : '/library/')) + '"' +
			' data-title="' + esc(title) + '"' +
			' data-author="' + esc(author) + '"' +
			' data-cover="' + esc(cover) + '"' +
			' data-amazon="' + esc(book.amazon || '') + '"' +
			' data-bookshop="' + esc(book.bookshop || '') + '"' +
			' data-newsletter="' + esc(book.newsletter || '') + '"' +
			' data-spice="' + esc(book.spice || '') + '"' +
			' data-ku="' + esc(book.ku || '') + '"' +
			' data-tropes="' + esc(tropes) + '"' +
			' data-tropes-display="' + esc(tropesDisplay) + '"' +
			' data-mini="' + esc(book.mini || '') + '"' +
			' data-why="' + esc(book.why || '') + '"' +
			' data-tension="' + esc(book.tension || '') + '"' +
			' data-damage="' + esc(book.damage || '') + '"' +
			' data-yearning="' + esc(book.yearning || '') + '"' +
			' data-boyfriend="' + esc(book.boyfriend || '') + '"' +
			' data-boyfriend-name="' + esc(book.boyfriendName || book.boyfriend_name || '') + '"' +
			' data-darkness="' + esc(book.darkness || '') + '"' +
			' data-reread="' + esc(book.reread || '') + '"' +
			' data-series="' + esc(book.series || '') + '"' +
			' data-series-name="' + esc(book.seriesName || '') + '"' +
			' data-series-number="' + esc(book.seriesNumber || '') + '"' +
			' data-standalone="' + esc(book.standalone || '') + '"' +
			' aria-label="' + esc('open details for ' + title) + '">' +
			'<span class="bbb-home-shelf-week__pickLabel">' + esc(label) + '</span>' +
			'<span class="bbb-home-shelf-week__cover" aria-hidden="true">' +
				(cover ? '<img src="' + esc(cover) + '" alt="" loading="lazy">' : '<span>' + esc(title.charAt(0) || 'b') + '</span>') +
				(spice ? '<span class="sss-lib__floatSpice">' + '🌶'.repeat(spice) + '</span>' : '') +
			'</span>' +
			'<span class="bbb-home-shelf-week__meta"><strong>' + esc(title) + '</strong>' +
				(author ? '<span>' + esc(author) + '</span>' : '<span>based on your made for you results</span>') +
			'</span>' +
			'</button>';
		}

		function matchText(value) {
			return normalize(value).replace(/[^a-z0-9]+/g, ' ').trim();
		}

		function looseMatch(candidate, target) {
			var candidateKey = matchText(candidate);
			var targetKey = matchText(target);
			if (!candidateKey || !targetKey) { return false; }
			return candidateKey === targetKey || candidateKey.indexOf(targetKey) > -1 || targetKey.indexOf(candidateKey) > -1;
		}

		function profileSearchText(profile) {
			return [
				profile && profile.name,
				profile && (profile.bookTitle || profile.book_title),
				profile && profile.descriptor,
				profile && profile.hook,
				profile && profile.shelf,
				((profile && profile.traits) || []).join(' '),
				((profile && (profile.traitLabels || profile.trait_labels)) || []).join(' '),
				((profile && profile.tropes) || []).join(' ')
			].map(matchText).join(' ');
		}

		function getBoyfriendProfileForBook(book) {
			var name = matchText(book && (book.boyfriendName || book.boyfriend_name));
			var title = matchText(book && (book.title || book.book_title));
			var type = matchText(book && (book.boyfriend || book.book_boyfriend || book.boyfriend_type));

			return boyfriendProfiles.find(function(profile){
				return name && looseMatch(profile && profile.name, name);
			}) || boyfriendProfiles.find(function(profile){
				return title && looseMatch(profile && (profile.bookTitle || profile.book_title), title);
			}) || boyfriendProfiles.find(function(profile){
				var haystack = profileSearchText(profile);
				return type && type.split(' ').filter(function(word){ return word.length >= 4; }).some(function(word){
					return haystack.indexOf(word) > -1;
				});
			}) || boyfriendProfiles[0] || null;
		}

		function getSavedBoyfriendProfile(profile) {
			var saved = profile && (profile.fictional_boyfriend || profile.fictionalBoyfriend || profile.latest_fictional_boyfriend);
			if (!saved || typeof saved !== 'object') {
				return null;
			}

			var name = saved.name || saved.title || saved.boyfriend_name || '';
			var bookTitle = saved.bookTitle || saved.book_title || saved.book || '';
			var descriptor = saved.descriptor || saved.type || saved.boyfriend || profile.fictional_man || '';
			var matched = boyfriendProfiles.find(function(item){
				return name && looseMatch(item && item.name, name);
			}) || boyfriendProfiles.find(function(item){
				return bookTitle && looseMatch(item && (item.bookTitle || item.book_title), bookTitle);
			}) || boyfriendProfiles.find(function(item){
				var haystack = profileSearchText(item);
				return descriptor && matchText(descriptor).split(' ').filter(function(word){ return word.length >= 4; }).some(function(word){
					return haystack.indexOf(word) > -1;
				});
			});

			return Object.assign({}, matched || {}, {
				name: name || (matched && matched.name) || 'fictional boyfriend match',
				descriptor: descriptor || (matched && matched.descriptor) || '',
				bookTitle: bookTitle || (matched && (matched.bookTitle || matched.book_title)) || '',
				image: saved.image || saved.image_full || saved.imageFull || (matched && matched.image) || '',
				imageFull: saved.image_full || saved.imageFull || saved.image || (matched && matched.imageFull) || (matched && matched.image) || '',
				url: saved.url || (matched && matched.url) || '/fictional-boyfriends/'
			});
		}

		function renderBoyfriendProfile(profile, label) {
			var title = (profile && profile.name) || 'fictional boyfriend match';
			var note = (profile && (profile.descriptor || profile.bookTitle)) || 'saved from your latest quiz';
			var image = profile && (profile.imageFull || profile.image) ? (profile.imageFull || profile.image) : '';
			var url = profile && profile.url ? profile.url : '/fictional-boyfriends/';

			return '<a class="bbb-home-shelf-week__book bbb-home-shelf-week__pick bbb-home-shelf-week__pick--boyfriend" href="' + esc(url) + '">' +
				'<span class="bbb-home-shelf-week__pickLabel">' + esc(label) + '</span>' +
				'<span class="bbb-home-shelf-week__cover" aria-hidden="true">' +
					(image ? '<img src="' + esc(image) + '" alt="" loading="lazy" decoding="async">' : '<span>' + esc(String(title).split(' ').slice(0, 2).join(' ')) + '</span>') +
				'</span>' +
				'<span class="bbb-home-shelf-week__meta"><strong>' + esc(title) + '</strong><span>' + esc(note) + '</span></span>' +
			'</a>';
		}

		function renderBoyfriendPick(book, label) {
			var profile = getBoyfriendProfileForBook(book);
			var title = (profile && profile.name) || (book && (book.boyfriendName || book.boyfriend_name || book.boyfriend || book.book_boyfriend || book.boyfriend_type)) || 'fictional boyfriend match';
			var note = (profile && (profile.descriptor || profile.bookTitle)) || (book && (book.title || book.book_title)) || 'matched from your reader profile';
			var image = profile && (profile.imageFull || profile.image) ? (profile.imageFull || profile.image) : '';
			var url = profile && profile.url ? profile.url : '/fictional-boyfriends/';

			return '<a class="bbb-home-shelf-week__book bbb-home-shelf-week__pick bbb-home-shelf-week__pick--boyfriend" href="' + esc(url) + '">' +
				'<span class="bbb-home-shelf-week__pickLabel">' + esc(label) + '</span>' +
				'<span class="bbb-home-shelf-week__cover" aria-hidden="true">' +
					(image ? '<img src="' + esc(image) + '" alt="" loading="lazy" decoding="async">' : '<span>' + esc(String(title).split(' ').slice(0, 2).join(' ')) + '</span>') +
				'</span>' +
				'<span class="bbb-home-shelf-week__meta"><strong>' + esc(title) + '</strong><span>' + esc(note) + '</span></span>' +
			'</a>';
		}

	function hydrateResultFallbackPicks(profile) {
		var rail = el.querySelector('.bbb-home-shelf-week__rail');
		if (!rail) {
			return;
		}

		var fallbackBooks = getProfileFallbackBooks(profile, 2);
		var savedBoyfriend = getSavedBoyfriendProfile(profile);
		if (!fallbackBooks.length && !savedBoyfriend) {
			return;
		}

			var picks = Array.prototype.slice.call(rail.querySelectorAll('.bbb-home-shelf-week__pick'));
			var topBookPick = picks.find(function(pick){
				var label = pick.querySelector('.bbb-home-shelf-week__pickLabel');
				return label && normalize(label.textContent).indexOf('top book rec') > -1;
			});
			var boyfriendPick = picks.find(function(pick){
				var label = pick.querySelector('.bbb-home-shelf-week__pickLabel');
				return label && normalize(label.textContent).indexOf('fictional bf match') > -1;
			});

			if (topBookPick) {
				if (fallbackBooks[0]) {
					topBookPick.outerHTML = renderFallbackPick(fallbackBooks[0], 'top book rec');
				}
				if (boyfriendPick) {
					boyfriendPick.outerHTML = savedBoyfriend
						? renderBoyfriendProfile(savedBoyfriend, 'fictional bf match')
						: renderBoyfriendPick(fallbackBooks[0], 'fictional bf match');
				}
				return;
			}

			if (boyfriendPick) {
				boyfriendPick.outerHTML = savedBoyfriend
					? renderBoyfriendProfile(savedBoyfriend, 'fictional bf match')
					: renderBoyfriendPick(fallbackBooks[0], 'fictional bf match');
			}
			if (picks.length === 1 && (savedBoyfriend || fallbackBooks[0])) {
				rail.insertAdjacentHTML('beforeend', savedBoyfriend
					? renderBoyfriendProfile(savedBoyfriend, 'fictional bf match')
					: renderBoyfriendPick(fallbackBooks[0], 'fictional bf match'));
			}
	}

	try {
		var taste = readJsonStorage('bbbReaderTasteProfile', {});
		var mfy   = readJsonStorage('sssMadeForYouProfile', {});
		var serverReady = el.getAttribute('data-home-mfy-server-ready') === 'true' && isCurrentProfile(accountProfile) && !!accountProfile.dashboard_built;
		if (!isCurrentProfile(mfy)) {
			mfy = {};
			localStorage.removeItem(scopedStorageKey('sssMadeForYouProfile'));
		}
		if (!isCurrentProfile(accountProfile)) {
			accountProfile = {};
		}
		if (serverReady) {
			mfy = accountProfile;
			writeJsonStorage('sssMadeForYouProfile', mfy);
			taste = {};
		} else if (isNewerProfile(accountProfile, mfy)) {
			mfy = accountProfile;
			writeJsonStorage('sssMadeForYouProfile', mfy);
		}
		var readerState = readJsonStorage('bbbReaderTypeState', {});
		var serverReaderTypeKey = el.getAttribute('data-server-reader-type') || '';
		var storedReaderTypeKey = accountProfile.reader_type_prior || mfy.reader_type_prior || (serverReady ? '' : (taste.reader_type || readerState.key || ''));
		var readerTypeKey = storedReaderTypeKey;
		if ((!readerTypeKey || readerTypeKey === 'romance_reader') && serverReaderTypeKey && serverReaderTypeKey !== 'romance_reader') {
			readerTypeKey = serverReaderTypeKey;
		}
		var isReady = !!(isCurrentProfile(mfy) && mfy.dashboard_built && (readerTypeKey || mfy.theme));
		if (!isReady && serverReaderTypeKey) {
			isReady = isCurrentProfile(accountProfile) && !!accountProfile.dashboard_built;
		}
		var readerType = isReady ? getReaderType(readerTypeKey) : null;
		if (isReady && readerType && applyReaderType(readerType)) {
			hydrateResultFallbackPicks(mfy);
			return;
		}
		var theme = isReady ? ((serverReady ? '' : taste.dashboard_theme) || mfy.theme || '').trim() : '';
		if (!isReady || !theme || !themes[theme]) {
			el.classList.add('is-home-mfy-locked');
			el.classList.remove('is-home-mfy-ready');
			el.setAttribute('data-home-mfy-locked', 'true');
			el.removeAttribute('data-home-mfy-theme');
			el.removeAttribute('data-mfy-theme');
			return;
		}
		applyTheme(theme);
		hydrateResultFallbackPicks(mfy);
	} catch (e) {}
}());
</script>
