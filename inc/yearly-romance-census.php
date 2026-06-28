<?php
/**
 * Yearly romance census aggregate data endpoint.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_census_number($value): int {
	return is_numeric($value) ? (int) $value : 0;
}

function bbb_census_float($value): float {
	return is_numeric($value) ? (float) $value : 0.0;
}

function bbb_census_month_key(string $date): string {
	$timestamp = strtotime($date);
	return $timestamp ? gmdate('Y-m', $timestamp) : '';
}

function bbb_census_month_label(string $month_key): string {
	$timestamp = strtotime($month_key . '-01');
	return $timestamp ? strtolower(gmdate('M', $timestamp)) : $month_key;
}

function bbb_census_supabase_get(string $table, array $query = array()): array {
	if (!function_exists('bbb_reader_supabase_request')) {
		return array('rows' => array(), 'error' => 'Supabase helper is not loaded.');
	}

	$rows = bbb_reader_supabase_request('GET', $table, $query);
	if (is_wp_error($rows)) {
		return array('rows' => array(), 'error' => $rows->get_error_message());
	}

	return array('rows' => (array) $rows, 'error' => '');
}

function bbb_census_empty_response(array $errors = array()): array {
	return array(
		'generatedAt' => current_time('mysql'),
		'sourceLabel' => 'real site data',
		'errors' => $errors,
		'stats' => array(),
		'topBook' => null,
		'topTropes' => array(),
		'bookBoyfriend' => null,
		'spice' => array(
			'average' => null,
			'distribution' => array(),
		),
		'readerDistribution' => array(),
		'societyGrowth' => array(),
		'search' => array(),
	);
}

function bbb_census_normalize_trope_name(string $name): string {
	$name = strtolower(trim($name));
	$name = preg_replace('/^[^a-z0-9]+/i', '', $name) ?: $name;
	$name = preg_replace('/\s+/', ' ', $name) ?: $name;

	return trim($name);
}

function bbb_census_site_events_summary(string $year_start): array {
	$events = bbb_census_supabase_get(
		'site_events',
		array(
			'select' => 'created_at,event_type,page_path,book_handle,book_title,metadata,session_id',
			'created_at' => 'gte.' . $year_start,
			'order' => 'created_at.desc',
			'limit' => 10000,
		)
	);
	if ($events['error']) {
		return array(
			'error' => $events['error'],
			'monthlyRows' => array(),
			'topBook' => null,
			'readerDistribution' => array(),
		);
	}

	$month_rows = array();
	$book_rows = array();
	$reader_counts = array(
		'trope/book browsers' => 0,
		'book savers' => 0,
		'book-page investigators' => 0,
		'retailer clickers' => 0,
	);
	$sessions_by_month = array();

	foreach ((array) $events['rows'] as $row) {
		if (!is_array($row)) {
			continue;
		}
		$month = bbb_census_month_key((string) ($row['created_at'] ?? ''));
		if ('' === $month) {
			continue;
		}
		if (!isset($month_rows[$month])) {
			$month_rows[$month] = array(
				'period_start' => $month . '-01',
				'total_events' => 0,
				'unique_sessions' => 0,
				'daily_visits' => 0,
				'book_saves' => 0,
				'book_modal_opens' => 0,
				'outbound_clicks' => 0,
			);
			$sessions_by_month[$month] = array();
		}

		$event_type = (string) ($row['event_type'] ?? '');
		$month_rows[$month]['total_events']++;
		if (!empty($row['session_id'])) {
			$sessions_by_month[$month][(string) $row['session_id']] = true;
		}

		if ('daily_visit' === $event_type) {
			$month_rows[$month]['daily_visits']++;
			$reader_counts['trope/book browsers']++;
		} elseif ('book_saved' === $event_type) {
			$month_rows[$month]['book_saves']++;
			$reader_counts['book savers']++;
		} elseif ('book_modal_opened' === $event_type) {
			$month_rows[$month]['book_modal_opens']++;
			$reader_counts['book-page investigators']++;
		} elseif ('book_link_clicked' === $event_type) {
			$month_rows[$month]['outbound_clicks']++;
			$reader_counts['retailer clickers']++;
		}

		$book_key = strtolower(trim((string) ($row['book_handle'] ?: ($row['book_title'] ?? ''))));
		if ('' === $book_key) {
			continue;
		}
		if (!isset($book_rows[$book_key])) {
			$book_rows[$book_key] = array(
				'title' => (string) ($row['book_title'] ?: $book_key),
				'key' => $book_key,
				'events' => 0,
				'saves' => 0,
				'outboundClicks' => 0,
				'modalOpens' => 0,
			);
		}
		$book_rows[$book_key]['events']++;
		if ('book_saved' === $event_type) {
			$book_rows[$book_key]['saves']++;
		} elseif ('book_link_clicked' === $event_type) {
			$book_rows[$book_key]['outboundClicks']++;
		} elseif ('book_modal_opened' === $event_type) {
			$book_rows[$book_key]['modalOpens']++;
		}
	}

	foreach ($sessions_by_month as $month => $sessions) {
		$month_rows[$month]['unique_sessions'] = count($sessions);
	}
	ksort($month_rows);

	usort(
		$book_rows,
		static function (array $a, array $b): int {
			if ($b['events'] !== $a['events']) {
				return $b['events'] <=> $a['events'];
			}
			if ($b['saves'] !== $a['saves']) {
				return $b['saves'] <=> $a['saves'];
			}
			return strcmp($a['title'], $b['title']);
		}
	);

	$total_reader = array_sum($reader_counts);
	$reader_distribution = array();
	foreach ($reader_counts as $label => $count) {
		$reader_distribution[] = array(
			'label' => $label,
			'count' => (int) $count,
			'share' => $total_reader > 0 ? round(((int) $count / $total_reader) * 100, 1) : 0,
		);
	}

	return array(
		'error' => '',
		'monthlyRows' => array_values($month_rows),
		'topBook' => $book_rows[0] ?? null,
		'readerDistribution' => $reader_distribution,
	);
}

function bbb_census_collect_data(): array {
	$cache_key = 'bbb_yearly_romance_census_v3';
	$cached = get_transient($cache_key);
	if (is_array($cached)) {
		return $cached;
	}

	$year = (int) current_time('Y');
	$year_start = sprintf('%d-01-01', $year);
	$month_start = gmdate('Y-m-01', (int) current_time('timestamp'));
	$today = current_time('Y-m-d');
	$errors = array();

	$response = bbb_census_empty_response();

	$monthly = bbb_census_supabase_get(
		'analytics_monthly',
		array(
			'select' => 'period_start,total_events,unique_sessions,daily_visits,book_saves,book_shares,library_shares,book_modal_opens,outbound_clicks,unique_pages,unique_books',
			'period_start' => 'gte.' . $year_start,
			'order' => 'period_start.asc',
			'limit' => 12,
		)
	);
	if ($monthly['error']) {
		$errors['analytics_monthly'] = $monthly['error'];
	}
	$monthly_rows = (array) $monthly['rows'];
	if (!$monthly_rows) {
		$event_summary = bbb_census_site_events_summary($year_start);
		if (!empty($event_summary['error'])) {
			$errors['site_events'] = $event_summary['error'];
		} else {
			unset($errors['analytics_monthly']);
			$monthly_rows = (array) $event_summary['monthlyRows'];
			$response['readerDistribution'] = (array) $event_summary['readerDistribution'];
			if (is_array($event_summary['topBook'])) {
				$response['topBook'] = $event_summary['topBook'];
			}
		}
	}

	$year_events = 0;
	$year_daily_visits = 0;
	$year_book_saves = 0;
	$year_modal_opens = 0;
	$year_outbound_clicks = 0;
	$current_month_sessions = 0;
	$current_month_growth = null;
	foreach ($monthly_rows as $index => $row) {
		$year_events += bbb_census_number($row['total_events'] ?? 0);
		$year_daily_visits += bbb_census_number($row['daily_visits'] ?? 0);
		$year_book_saves += bbb_census_number($row['book_saves'] ?? 0);
		$year_modal_opens += bbb_census_number($row['book_modal_opens'] ?? 0);
		$year_outbound_clicks += bbb_census_number($row['outbound_clicks'] ?? 0);
		if (bbb_census_month_key((string) ($row['period_start'] ?? '')) === bbb_census_month_key($month_start)) {
			$current_month_sessions = bbb_census_number($row['unique_sessions'] ?? 0);
			$previous = $monthly_rows[$index - 1] ?? null;
			if (is_array($previous)) {
				$previous_sessions = bbb_census_number($previous['unique_sessions'] ?? 0);
				if ($previous_sessions > 0) {
					$current_month_growth = (($current_month_sessions - $previous_sessions) / $previous_sessions) * 100;
				}
			}
		}
	}

	$top_books = bbb_census_supabase_get(
		'analytics_top_books',
		array(
			'select' => 'book_key,book_label,total_book_events,book_saves,outbound_clicks,book_shares,modal_opens',
			'period_granularity' => 'eq.month',
			'period_start' => 'gte.' . $month_start,
			'order' => 'total_book_events.desc',
			'limit' => 1,
		)
	);
	if ($top_books['error']) {
		$errors['analytics_top_books'] = $top_books['error'];
	}
	$top_book_row = isset($top_books['rows'][0]) && is_array($top_books['rows'][0]) ? $top_books['rows'][0] : array();
	if ($top_book_row && null === $response['topBook']) {
		$response['topBook'] = array(
			'title' => (string) ($top_book_row['book_label'] ?? ''),
			'key' => (string) ($top_book_row['book_key'] ?? ''),
			'events' => bbb_census_number($top_book_row['total_book_events'] ?? 0),
			'saves' => bbb_census_number($top_book_row['book_saves'] ?? 0),
			'outboundClicks' => bbb_census_number($top_book_row['outbound_clicks'] ?? 0),
			'modalOpens' => bbb_census_number($top_book_row['modal_opens'] ?? 0),
		);
	}
	if (null !== $response['topBook']) {
		unset($errors['analytics_top_books']);
	}

	$saved_books = bbb_census_supabase_get(
		'bookshelf_saved_books',
		array(
			'select' => 'book_title,spice_level,tropes,saved_at,is_active',
			'is_active' => 'eq.true',
			'saved_at' => 'gte.' . $year_start,
			'order' => 'saved_at.desc',
			'limit' => 10000,
		)
	);
	if ($saved_books['error']) {
		$errors['bookshelf_saved_books'] = $saved_books['error'];
	}
	$saved_rows = (array) $saved_books['rows'];

	$spice_counts = array_fill(1, 5, 0);
	$spice_total = 0;
	$spice_count = 0;
	$trope_counts = array();
	foreach ($saved_rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$spice = max(0, min(5, bbb_census_number($row['spice_level'] ?? 0)));
		if ($spice > 0) {
			$spice_counts[$spice]++;
			$spice_total += $spice;
			$spice_count++;
		}

		$tropes = $row['tropes'] ?? array();
		if (is_string($tropes)) {
			$decoded = json_decode($tropes, true);
			$tropes = is_array($decoded) ? $decoded : explode(',', $tropes);
		}
		foreach ((array) $tropes as $trope) {
			$name = bbb_census_normalize_trope_name(is_array($trope) ? (string) ($trope['name'] ?? '') : (string) $trope);
			if ('' === $name) {
				continue;
			}
			$trope_counts[$name] = ($trope_counts[$name] ?? 0) + 1;
		}
	}
	arsort($trope_counts);
	$total_trope_hits = array_sum($trope_counts);
	foreach (array_slice($trope_counts, 0, 6, true) as $name => $count) {
		$response['topTropes'][] = array(
			'name' => $name,
			'count' => (int) $count,
			'share' => $total_trope_hits > 0 ? round(((int) $count / $total_trope_hits) * 100, 1) : 0,
		);
	}

	$spice_distribution = array();
	foreach ($spice_counts as $level => $count) {
		$spice_distribution[] = array(
			'level' => (int) $level,
			'count' => (int) $count,
			'share' => $spice_count > 0 ? round(((int) $count / $spice_count) * 100, 1) : 0,
		);
	}
	$response['spice'] = array(
		'average' => $spice_count > 0 ? round($spice_total / $spice_count, 1) : null,
		'distribution' => $spice_distribution,
	);

	$boyfriends = bbb_census_supabase_get(
		'boyfriend_votes_current_month',
		array(
			'select' => 'name,vote_count,vote_rank',
			'vote_rank' => 'eq.1',
			'limit' => 1,
		)
	);
	if ($boyfriends['error']) {
		$errors['boyfriend_votes_current_month'] = $boyfriends['error'];
	}
	if (isset($boyfriends['rows'][0]) && is_array($boyfriends['rows'][0])) {
		$response['bookBoyfriend'] = array(
			'name' => (string) ($boyfriends['rows'][0]['name'] ?? ''),
			'votes' => bbb_census_number($boyfriends['rows'][0]['vote_count'] ?? 0),
		);
	}

	$subscribers = bbb_census_supabase_get(
		'bookshelf_subscribers',
		array(
			'select' => 'subscribed_at,access_tier',
			'subscribed_at' => 'gte.' . $year_start,
			'order' => 'subscribed_at.asc',
			'limit' => 10000,
		)
	);
	if ($subscribers['error']) {
		$errors['bookshelf_subscribers'] = $subscribers['error'];
	}
	$month_counts = array();
	foreach ((array) $subscribers['rows'] as $row) {
		if (!is_array($row)) {
			continue;
		}
		$key = bbb_census_month_key((string) ($row['subscribed_at'] ?? ''));
		if ('' === $key) {
			continue;
		}
		if (!isset($month_counts[$key])) {
			$month_counts[$key] = array('new' => 0, 'society' => 0);
		}
		$month_counts[$key]['new']++;
		if ('society' === (string) ($row['access_tier'] ?? '')) {
			$month_counts[$key]['society']++;
		}
	}
	$cumulative = 0;
	foreach ($month_counts as $month => $counts) {
		$cumulative += (int) $counts['new'];
		$response['societyGrowth'][] = array(
			'month' => bbb_census_month_label($month),
			'monthKey' => $month,
			'newMembers' => (int) $counts['new'],
			'societyMembers' => (int) $counts['society'],
			'totalMembers' => $cumulative,
		);
	}

	if (empty($response['readerDistribution'])) {
		$reader_total = $year_daily_visits + $year_book_saves + $year_modal_opens + $year_outbound_clicks;
		$reader_items = array(
			'trope/book browsers' => $year_daily_visits,
			'book savers' => $year_book_saves,
			'book-page investigators' => $year_modal_opens,
			'retailer clickers' => $year_outbound_clicks,
		);
		foreach ($reader_items as $label => $count) {
			$response['readerDistribution'][] = array(
				'label' => $label,
				'count' => (int) $count,
				'share' => $reader_total > 0 ? round(((int) $count / $reader_total) * 100, 1) : 0,
			);
		}
	}

	$search = array();
	if (function_exists('bbb_analytics_sync_collect_google')) {
		$google = bbb_analytics_sync_collect_google($year_start, $today);
		if (is_array($google)) {
			$search = (array) ($google['fields'] ?? array());
			if (!empty($google['errors']) && is_array($google['errors'])) {
				$errors['sitekit'] = $google['errors'];
			}
		}
	}
	$response['search'] = $search;

	$society_growth = end($response['societyGrowth']);
	$response['stats'] = array(
		array(
			'key' => 'gsc_impressions',
			'label' => 'search reach',
			'value' => isset($search['gsc_impressions']) ? bbb_census_number($search['gsc_impressions']) : null,
			'note' => 'reader discovery, year to date',
		),
		array(
			'key' => 'reader_sessions',
			'label' => 'reader visits',
			'value' => $current_month_sessions ?: null,
			'note' => 'this month on bybookishbabe',
		),
		array(
			'key' => 'avg_spice',
			'label' => 'saved spice',
			'value' => $response['spice']['average'],
			'note' => 'average heat readers are saving',
		),
		array(
			'key' => 'member_growth',
			'label' => 'society readers',
			'value' => is_array($society_growth) ? (int) ($society_growth['totalMembers'] ?? 0) : null,
			'note' => 'new readers this year',
		),
	);

	$response['generatedAt'] = current_time('mysql');
	$response['errors'] = $errors;
	$response['sourceLabel'] = empty($errors) ? 'real site data' : 'real site data with gaps';

	set_transient($cache_key, $response, 15 * MINUTE_IN_SECONDS);

	return $response;
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/yearly-romance-census',
			array(
				'methods' => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback' => static fn(): WP_REST_Response => rest_ensure_response(bbb_census_collect_data()),
			)
		);
	}
);
