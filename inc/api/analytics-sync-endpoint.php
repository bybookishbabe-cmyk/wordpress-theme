<?php
/**
 * Private analytics bridge for the local content dashboard.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_analytics_sync_secret(): string {
	if (defined('BBB_ANALYTICS_SYNC_SECRET')) {
		return (string) BBB_ANALYTICS_SYNC_SECRET;
	}

	if (defined('SUBSTACK_SYNC_SECRET')) {
		return (string) SUBSTACK_SYNC_SECRET;
	}

	return (string) getenv('BBB_ANALYTICS_SYNC_SECRET') ?: (string) getenv('SUBSTACK_SYNC_SECRET');
}

function bbb_analytics_sync_permission(WP_REST_Request $request): bool {
	$secret = bbb_analytics_sync_secret();
	$provided = (string) ($request->get_header('x-bbb-analytics-secret') ?: $request->get_param('secret'));
	if ('' !== $secret) {
		return hash_equals($secret, $provided);
	}

	$allowed_hash = '5bbf49ff315b54165c345a1261e6760b056b52099e1d1fff4434af9e49bfae74';
	return '' !== $provided && hash_equals($allowed_hash, hash('sha256', $provided));
}

function bbb_analytics_sync_request_has_valid_secret(): bool {
	$provided = '';
	if (isset($_SERVER['HTTP_X_BBB_ANALYTICS_SECRET'])) {
		$provided = (string) wp_unslash($_SERVER['HTTP_X_BBB_ANALYTICS_SECRET']);
	} elseif (isset($_GET['secret'])) {
		$provided = (string) wp_unslash($_GET['secret']);
	}

	$secret = bbb_analytics_sync_secret();
	if ('' !== $secret) {
		return '' !== $provided && hash_equals($secret, $provided);
	}

	$allowed_hash = '5bbf49ff315b54165c345a1261e6760b056b52099e1d1fff4434af9e49bfae74';
	return '' !== $provided && hash_equals($allowed_hash, hash('sha256', $provided));
}

add_action(
	'init',
	static function (): void {
		$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
		if (false === strpos($request_uri, '/wp-json/bbb/v1/analytics-sync')) {
			return;
		}

		if (!bbb_analytics_sync_request_has_valid_secret()) {
			return;
		}

		$user_id = bbb_analytics_sync_sitekit_user_id();
		if ($user_id > 0) {
			wp_set_current_user($user_id);
		}
	},
	-10000
);

function bbb_analytics_sync_sitekit_user_id(): int {
	if (defined('BBB_ANALYTICS_SYNC_USER_ID')) {
		return (int) BBB_ANALYTICS_SYNC_USER_ID;
	}

	$users = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
			'fields'  => 'ID',
		)
	);

	return $users ? (int) $users[0] : 0;
}

function bbb_analytics_sync_sitekit_request(string $module, string $datapoint, array $params) {
	$last_error = '';
	$routes = array('/google-site-kit/v1/modules/' . $module . '/data/' . $datapoint);

	foreach ($routes as $route) {
		$request = new WP_REST_Request(WP_REST_Server::READABLE, $route);
		$request->set_query_params($params);
		$response = rest_do_request($request);

		if (is_wp_error($response)) {
			continue;
		}

		$status = (int) $response->get_status();
		$data   = $response->get_data();
		if ($status >= 200 && $status < 300) {
			return $data;
		}
		if (is_array($data)) {
			$last_error = (string) ($data['message'] ?? $data['code'] ?? wp_json_encode($data));
		}
	}

	return new WP_Error(
		'bbb_sitekit_request_failed',
		$last_error ?: 'Site Kit did not return data for ' . $module . ':' . $datapoint,
		array('status' => 502)
	);
}

function bbb_analytics_sync_report_rows($report): array {
	if (!is_array($report)) {
		return array();
	}

	if (isset($report['rows']) && is_array($report['rows'])) {
		return $report['rows'];
	}

	if (isset($report[0]['rows']) && is_array($report[0]['rows'])) {
		return $report[0]['rows'];
	}

	if (isset($report['reports'][0]['rows']) && is_array($report['reports'][0]['rows'])) {
		return $report['reports'][0]['rows'];
	}

	return array();
}

function bbb_analytics_sync_metric($row, int $index, string $name): float {
	if (isset($row['metricValues'][$index]['value'])) {
		return (float) $row['metricValues'][$index]['value'];
	}

	if (isset($row['metrics'][$name])) {
		return (float) $row['metrics'][$name];
	}

	if (isset($row[$name])) {
		return (float) $row[$name];
	}

	return 0.0;
}

function bbb_analytics_sync_dimension($row, int $index, string $name): string {
	if (isset($row['dimensionValues'][$index]['value'])) {
		return (string) $row['dimensionValues'][$index]['value'];
	}

	if (isset($row['dimensions'][$name])) {
		return (string) $row['dimensions'][$name];
	}

	if (isset($row[$name])) {
		return (string) $row[$name];
	}

	if (isset($row['keys'][$index])) {
		return (string) $row['keys'][$index];
	}

	return '';
}

function bbb_analytics_sync_collect_google(string $start_date, string $end_date): array {
	$previous_user_id = get_current_user_id();
	$sitekit_user_id  = bbb_analytics_sync_sitekit_user_id();
	if ($sitekit_user_id > 0) {
		wp_set_current_user($sitekit_user_id);
	}

	$normalized = array();
	$raw        = array();
	$errors     = array();

	$summary = bbb_analytics_sync_sitekit_request(
		'analytics-4',
		'report',
		array(
			'startDate' => $start_date,
			'endDate'   => $end_date,
			'metrics'   => array('activeUsers'),
		)
	);
	if (is_wp_error($summary)) {
		$errors['ga4_summary'] = $summary->get_error_message();
	} else {
		$raw['ga4_summary'] = $summary;
		$rows = bbb_analytics_sync_report_rows($summary);
		if (isset($rows[0])) {
			$normalized['ga4_users'] = (string) round(bbb_analytics_sync_metric($rows[0], 0, 'activeUsers'));
		}
	}

	$sources = bbb_analytics_sync_sitekit_request(
		'analytics-4',
		'report',
		array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => array('sessionDefaultChannelGroup'),
			'metrics'    => array('sessions'),
			'limit'      => 5,
		)
	);
	if (is_wp_error($sources)) {
		$errors['ga4_sources'] = $sources->get_error_message();
	} else {
		$raw['ga4_sources'] = $sources;
		$rows = bbb_analytics_sync_report_rows($sources);
		usort(
			$rows,
			static fn($a, $b): int => bbb_analytics_sync_metric($b, 0, 'sessions') <=> bbb_analytics_sync_metric($a, 0, 'sessions')
		);
		if (isset($rows[0])) {
			$normalized['ga4_top_source'] = bbb_analytics_sync_dimension($rows[0], 0, 'sessionDefaultChannelGroup');
		}
	}

	$pages = bbb_analytics_sync_sitekit_request(
		'analytics-4',
		'report',
		array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => array('pageTitle', 'pagePath'),
			'metrics'    => array('screenPageViews'),
			'limit'      => 5,
		)
	);
	if (is_wp_error($pages)) {
		$errors['ga4_pages'] = $pages->get_error_message();
	} else {
		$raw['ga4_pages'] = $pages;
		$rows = bbb_analytics_sync_report_rows($pages);
		usort(
			$rows,
			static fn($a, $b): int => bbb_analytics_sync_metric($b, 0, 'screenPageViews') <=> bbb_analytics_sync_metric($a, 0, 'screenPageViews')
		);
		$top_pages = array();
		foreach (array_slice($rows, 0, 5) as $index => $row) {
			$title = bbb_analytics_sync_dimension($row, 0, 'pageTitle') ?: bbb_analytics_sync_dimension($row, 1, 'pagePath');
			$views = round(bbb_analytics_sync_metric($row, 0, 'screenPageViews'));
			$top_pages[] = ((int) $index + 1) . '. ' . $title . ' (' . number_format_i18n($views) . ' views)';
		}
		if ($top_pages) {
			$normalized['ga4_top_pages'] = implode('; ', $top_pages);
		}
	}

	$gsc_total = bbb_analytics_sync_sitekit_request(
		'search-console',
		'searchanalytics',
		array(
			'startDate' => $start_date,
			'endDate'   => $end_date,
			'rowLimit'  => 1,
		)
	);
	if (is_wp_error($gsc_total)) {
		$errors['gsc_total'] = $gsc_total->get_error_message();
	} else {
		$raw['gsc_total'] = $gsc_total;
		$rows = bbb_analytics_sync_report_rows($gsc_total);
		if (isset($rows[0])) {
			$normalized['gsc_clicks']       = (string) round(bbb_analytics_sync_metric($rows[0], 0, 'clicks'));
			$normalized['gsc_impressions']  = (string) round(bbb_analytics_sync_metric($rows[0], 1, 'impressions'));
			$normalized['gsc_avg_position'] = number_format_i18n(bbb_analytics_sync_metric($rows[0], 3, 'position'), 1);
		}
	}

	$gsc_queries = bbb_analytics_sync_sitekit_request(
		'search-console',
		'searchanalytics',
		array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => array('query'),
			'rowLimit'   => 20,
		)
	);
	if (is_wp_error($gsc_queries)) {
		$errors['gsc_queries'] = $gsc_queries->get_error_message();
	} else {
		$raw['gsc_queries'] = $gsc_queries;
		foreach (bbb_analytics_sync_report_rows($gsc_queries) as $row) {
			$query = bbb_analytics_sync_dimension($row, 0, 'query');
			if ($query && !preg_match('/bybookishbabe|bookish babe|smut and sentiment/i', $query)) {
				$normalized['gsc_top_keyword'] = $query;
				break;
			}
		}
	}

	wp_set_current_user($previous_user_id);

	return array(
		'fields' => $normalized,
		'errors' => $errors,
		'raw'    => $raw,
	);
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/analytics-sync',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => 'bbb_analytics_sync_permission',
				'callback'            => static function (WP_REST_Request $request) {
					$start_date = sanitize_text_field((string) $request->get_param('startDate'));
					$end_date   = sanitize_text_field((string) $request->get_param('endDate'));

					if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
						return new WP_Error('bbb_analytics_bad_dates', 'Use startDate and endDate as YYYY-MM-DD.', array('status' => 400));
					}

					return rest_ensure_response(bbb_analytics_sync_collect_google($start_date, $end_date));
				},
			)
		);
	}
);
