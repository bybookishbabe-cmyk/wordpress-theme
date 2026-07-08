<?php
/**
 * Private social posting calendar helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_social_calendar_defaults(): array {
	return array(
		'ical_url'      => defined('BBB_SOCIAL_CALENDAR_ICAL_URL') ? (string) BBB_SOCIAL_CALENDAR_ICAL_URL : '',
		'dashboard_url' => '',
		'google_embed'  => '',
		'pinterest_url' => '',
		'notion_url'    => '',
		'trello_url'    => '',
		'threads_url'   => 'https://www.threads.net/',
		'instagram_url' => 'https://www.instagram.com/',
		'tiktok_url'    => 'https://www.tiktok.com/',
		'pinterest_app' => 'https://www.pinterest.com/',
		'facebook_url'  => 'https://www.facebook.com/',
		'canva_url'     => 'https://www.canva.com/',
	);
}

function bbb_social_calendar_settings(): array {
	$saved = get_option('bbb_social_calendar_settings', array());
	if (!is_array($saved)) {
		$saved = array();
	}

	return array_merge(bbb_social_calendar_defaults(), array_intersect_key($saved, bbb_social_calendar_defaults()));
}

function bbb_social_calendar_planner_token(): string {
	return hash_hmac('sha256', 'bbb-social-calendar-planner-sync', wp_salt('auth'));
}

function bbb_social_calendar_request_has_access(): bool {
	if (current_user_can('manage_options')) {
		return true;
	}

	$token = isset($_POST['plannerToken']) && is_scalar($_POST['plannerToken']) ? (string) wp_unslash($_POST['plannerToken']) : '';
	return '' !== $token && hash_equals(bbb_social_calendar_planner_token(), $token);
}

function bbb_social_calendar_check_ajax_access(): void {
	if (!bbb_social_calendar_request_has_access()) {
		wp_send_json_error(array('message' => 'Not allowed.'), 403);
	}

	if (current_user_can('manage_options')) {
		check_ajax_referer('bbb_social_calendar_state', 'nonce');
	}
}

function bbb_social_calendar_sanitize_settings(array $settings): array {
	$defaults = bbb_social_calendar_defaults();
	$clean    = array();

	foreach ($defaults as $key => $default) {
		$value       = isset($settings[$key]) && is_scalar($settings[$key]) ? trim((string) $settings[$key]) : (string) $default;
		$clean[$key] = '' === $value ? '' : esc_url_raw($value);
	}

	if (defined('BBB_SOCIAL_CALENDAR_ICAL_URL') && '' !== (string) BBB_SOCIAL_CALENDAR_ICAL_URL) {
		$clean['ical_url'] = (string) BBB_SOCIAL_CALENDAR_ICAL_URL;
	}

	return $clean;
}

function bbb_social_calendar_save_settings_from_request(): bool {
	if (empty($_POST['bbb_social_calendar_save']) || !current_user_can('manage_options')) {
		return false;
	}

	check_admin_referer('bbb_social_calendar_save');

	$raw = isset($_POST['bbb_social_calendar']) && is_array($_POST['bbb_social_calendar'])
		? wp_unslash($_POST['bbb_social_calendar'])
		: array();

	update_option('bbb_social_calendar_settings', bbb_social_calendar_sanitize_settings($raw), false);

	wp_safe_redirect(add_query_arg('saved', '1', remove_query_arg('saved')));
	exit;
}

function bbb_social_calendar_newsletter_schedule(): array {
	$timezone = new DateTimeZone('America/Los_Angeles');
	$today    = new DateTimeImmutable('today', $timezone);
	$start    = $today->modify('first day of -6 months');
	$end      = $today->modify('first day of +18 months');
	$schedule = array();

	for ($month = $start; $month <= $end; $month = $month->modify('first day of next month')) {
		$days    = (int) $month->format('t');
		$sundays = array();

		for ($day = 1; $day <= $days; $day++) {
			$date = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), $day);
			if ('0' === $date->format('w')) {
				$sundays[] = $date;
			}
		}

		$issue_types = count($sundays) >= 5
			? array('smutty sunday', 'sentimental sunday', 'trope report', 'extra extra', "chapter's end")
			: array('smutty sunday', 'sentimental sunday', 'trope report', "chapter's end");

		foreach ($sundays as $index => $sunday) {
			if (!empty($issue_types[$index])) {
				$schedule[$sunday->format('Y-m-d')] = $issue_types[$index];
			}
		}
	}

	return $schedule;
}

function bbb_social_calendar_insert_is_printable(WP_Post $post): bool {
	$term_names = '';
	if (taxonomy_exists('download_category')) {
		$terms      = get_the_terms($post, 'download_category');
		$term_names = is_array($terms) ? implode(' ', wp_list_pluck($terms, 'name')) : '';
	}

	$haystack = strtolower(
		get_the_title($post) . ' ' .
		(string) get_post_meta($post->ID, '_bbb_shopify_product_type', true) . ' ' .
		$term_names
	);

	return str_contains($haystack, 'kindle insert')
		&& !str_contains($haystack, 'vault')
		&& !str_contains($haystack, 'canva')
		&& !str_contains($haystack, 'template');
}

function bbb_social_calendar_insert_title(string $title): string {
	$title = preg_replace('/\s+[—-]\s+printable kindle insert\s*$/i', '', $title);
	$title = preg_replace('/\s+printable kindle insert\s*$/i', '', (string) $title);
	return trim((string) $title);
}

function bbb_social_calendar_insert_image_url(int $post_id): string {
	if (function_exists('bbb_society_product_image_url')) {
		$image = bbb_society_product_image_url($post_id);
		if ('' !== $image) {
			return $image;
		}
	}

	$image = (string) (get_the_post_thumbnail_url($post_id, 'medium') ?: '');
	if ('' !== $image) {
		return esc_url_raw($image);
	}

	$candidates = array((string) get_post_meta($post_id, '_bbb_source_image_url', true));
	$media_urls = get_post_meta($post_id, '_bbb_product_media_urls', true);
	if (is_string($media_urls) && '' !== trim($media_urls)) {
		$decoded    = json_decode($media_urls, true);
		$media_urls = is_array($decoded) ? $decoded : preg_split('/[|,]/', $media_urls);
	}

	foreach ((array) $media_urls as $url) {
		$candidates[] = (string) $url;
	}

	foreach ($candidates as $candidate) {
		$candidate = trim((string) $candidate);
		if ('' === $candidate) {
			continue;
		}

		if (function_exists('bbb_society_product_importer_media_url')) {
			$mapped = bbb_society_product_importer_media_url($candidate);
			if ('' !== $mapped) {
				$candidate = $mapped;
			}
		}

		if (str_starts_with($candidate, '/wp-content/')) {
			$candidate = home_url($candidate);
		}

		return esc_url_raw($candidate);
	}

	return '';
}

function bbb_social_calendar_insert_with_image(array $insert): array {
	$post_id = (int) ($insert['id'] ?? 0);
	if ($post_id > 0 && empty($insert['image'])) {
		$insert['image'] = bbb_social_calendar_insert_image_url($post_id);
	}

	return $insert;
}

function bbb_social_calendar_insert_title_key(string $title): string {
	$title = html_entity_decode(wp_strip_all_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$title = preg_replace('/&(?:#\d+|#x[0-9a-f]+|[a-z]+);?/i', ' ', $title);
	return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $title)));
}

function bbb_social_calendar_theme_insert_library(): array {
	$monthly_theme_url = function_exists('bbb_page_url')
		? bbb_page_url('monthly-bybookishbabe-romance-theme')
		: home_url('/monthly-bybookishbabe-romance-theme/');

	return array(
		array(
			'id'    => -20260701,
			'title' => 'midnight swim —',
			'url'   => $monthly_theme_url . '#download-swim',
			'image' => set_url_scheme(get_theme_file_uri('assets/monthly-themes/july-2026/display/midnight-swim-insert-preview.jpg'), 'https'),
			'month' => '2026-07',
		),
		array(
			'id'    => -20260702,
			'title' => 'midnight movie —',
			'url'   => $monthly_theme_url . '#download-movie',
			'image' => set_url_scheme(get_theme_file_uri('assets/monthly-themes/july-2026/display/midnight-movie-insert-preview.jpg'), 'https'),
			'month' => '2026-07',
		),
		array(
			'id'    => -20260703,
			'title' => 'midnight drive —',
			'url'   => $monthly_theme_url . '#download-drive',
			'image' => set_url_scheme(get_theme_file_uri('assets/monthly-themes/july-2026/display/midnight-drive-insert-preview.jpg'), 'https'),
			'month' => '2026-07',
		),
		array(
			'id'    => -20260704,
			'title' => 'midnight makeout —',
			'url'   => $monthly_theme_url . '#download-makeout',
			'image' => set_url_scheme(get_theme_file_uri('assets/monthly-themes/july-2026/display/midnight-makeout-insert-preview.jpg'), 'https'),
			'month' => '2026-07',
		),
	);
}

function bbb_social_calendar_insert_library(): array {
	$post_type = post_type_exists('download') ? 'download' : (post_type_exists('product') ? 'product' : 'post');
	$query     = new WP_Query(
		array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => 300,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'meta_query'             => array(
				array(
					'key'     => '_bbb_import_source',
					'value'   => 'society_product_importer',
					'compare' => '=',
				),
			),
		)
	);

	if (!$query->posts) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 300,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);
	}

	$inserts = array();
	foreach ($query->posts as $post) {
		if (!$post instanceof WP_Post || !bbb_social_calendar_insert_is_printable($post)) {
			continue;
		}

		$title = bbb_social_calendar_insert_title(get_the_title($post));
		if ('' === $title) {
			continue;
		}

		$month = mysql2date('Y-m', $post->post_date);
		if (preg_match('/^midnight (swim|movie|drive|makeout)\b/i', wp_strip_all_tags($title))) {
			$month = '2026-07';
		}

			$inserts[(int) $post->ID] = array(
				'id'    => (int) $post->ID,
				'title' => $title,
				'url'   => get_permalink($post),
				'image' => bbb_social_calendar_insert_image_url((int) $post->ID),
				'month' => $month,
			);
		}

	$existing_titles = array();
	foreach ($inserts as $insert) {
		$title_key = bbb_social_calendar_insert_title_key((string) ($insert['title'] ?? ''));
		if ('' !== $title_key) {
			$existing_titles[$title_key] = true;
		}
	}

	foreach (bbb_social_calendar_theme_insert_library() as $theme_insert) {
		$title_key = bbb_social_calendar_insert_title_key((string) ($theme_insert['title'] ?? ''));
		if ('' === $title_key || isset($existing_titles[$title_key])) {
			continue;
		}
		$inserts[(int) $theme_insert['id']] = $theme_insert;
	}

	return array_values($inserts);
}

function bbb_social_calendar_insert_week_start(DateTimeImmutable $date): DateTimeImmutable {
	return $date->modify('-' . $date->format('w') . ' days')->setTime(0, 0);
}

function bbb_social_calendar_insert_month_sundays(DateTimeImmutable $week_start): array {
	$first   = $week_start->modify('first day of this month');
	$cursor  = bbb_social_calendar_insert_week_start($first);
	$sundays = array();

	if ($cursor->format('m') !== $first->format('m')) {
		$cursor = $cursor->modify('+7 days');
	}

	while ($cursor->format('m') === $first->format('m')) {
		$sundays[] = $cursor->format('Y-m-d');
		$cursor    = $cursor->modify('+7 days');
	}

	return $sundays;
}

function bbb_social_calendar_pick_inserts(array $pool, int $needed, array $exclude_ids, array &$used_ids, string $seed): array {
	$picks = array();

	while (count($picks) < $needed) {
		$available = array_values(
			array_filter(
				$pool,
				static fn(array $insert): bool => !in_array((int) $insert['id'], $exclude_ids, true)
					&& !in_array((int) $insert['id'], $used_ids, true)
			)
		);

		if (!$available) {
			$used_ids = array_values(array_intersect($used_ids, $exclude_ids));
			$available = array_values(
				array_filter(
					$pool,
					static fn(array $insert): bool => !in_array((int) $insert['id'], $exclude_ids, true)
						&& !in_array((int) $insert['id'], $used_ids, true)
				)
			);
		}

		if (!$available) {
			break;
		}

		usort(
			$available,
			static fn(array $a, array $b): int => strcmp(
				md5($seed . ':' . $a['id'] . ':' . count($used_ids)),
				md5($seed . ':' . $b['id'] . ':' . count($used_ids))
			)
		);

		$next          = $available[0];
		$picks[]       = $next;
		$exclude_ids[] = (int) $next['id'];
		$used_ids[]    = (int) $next['id'];
	}

	return $picks;
}

function bbb_social_calendar_insert_spotlight(): array {
	$inserts = bbb_social_calendar_insert_library();
	if (!$inserts) {
		return array();
	}

	$now = current_datetime();
	if (!$now instanceof DateTimeImmutable) {
		$now = DateTimeImmutable::createFromMutable($now);
	}

	$week_start = bbb_social_calendar_insert_week_start($now);
	$week_key   = $week_start->format('Y-m-d');
	$month_key  = $week_start->format('Y-m');
	$option     = get_option('bbb_social_calendar_insert_spotlight', array());
	$option     = is_array($option) ? $option : array();
	$weeks      = isset($option['weeks']) && is_array($option['weeks']) ? $option['weeks'] : array();
	$insert_months = array_values(
		array_unique(
			array_filter(
				array_map(
					static fn(array $insert): string => (string) ($insert['month'] ?? ''),
					$inserts
				)
			)
		)
	);
	rsort($insert_months, SORT_STRING);
	$spotlight_month = in_array($month_key, $insert_months, true) ? $month_key : (string) ($insert_months[0] ?? $month_key);
	$new_inserts = array_values(
		array_filter(
			$inserts,
			static fn(array $insert): bool => (string) ($insert['month'] ?? '') === $spotlight_month
		)
	);
	usort($new_inserts, static fn(array $a, array $b): int => strcmp((string) $a['title'], (string) $b['title']));

	$sundays    = bbb_social_calendar_insert_month_sundays($week_start);
	$week_index = array_search($week_key, $sundays, true);
	$forced     = $new_inserts ? $new_inserts[(false !== $week_index ? (int) $week_index : 0) % count($new_inserts)] : null;

	if (isset($weeks[$week_key]) && is_array($weeks[$week_key]) && !empty($weeks[$week_key]['items'])) {
		$cached_items = array_values((array) $weeks[$week_key]['items']);
		$library_by_id = array_column($inserts, null, 'id');
		$cached_items = array_map(
			static function ($item) use ($library_by_id): array {
				$item = is_array($item) ? $item : array();
				$item_id = (int) ($item['id'] ?? 0);
				return isset($library_by_id[$item_id]) ? array_merge($item, $library_by_id[$item_id]) : $item;
			},
			$cached_items
		);
		$seen_ids     = array();
		$seen_monthly_titles = array();
		$cached_items = array_values(
			array_filter(
				$cached_items,
				static function ($item) use (&$seen_ids, &$seen_monthly_titles, $spotlight_month): bool {
					if (!is_array($item)) {
						return false;
					}
					$item_id = (int) ($item['id'] ?? 0);
					if (0 === $item_id) {
						return true;
					}
					if (isset($seen_ids[$item_id])) {
						return false;
					}
					$seen_ids[$item_id] = true;
					if ((string) ($item['month'] ?? '') === $spotlight_month) {
						$title_key = bbb_social_calendar_insert_title_key((string) ($item['title'] ?? ''));
						if ('' !== $title_key && isset($seen_monthly_titles[$title_key])) {
							return false;
						}
						$seen_monthly_titles[$title_key] = true;
					}
					return true;
				}
			)
		);
		if (is_array($forced)) {
			$forced_id      = (int) ($forced['id'] ?? 0);
			$has_forced     = 0 !== $forced_id && array_filter(
				$cached_items,
				static fn($item): bool => is_array($item) && (int) ($item['id'] ?? 0) === $forced_id
			);
			if (!$has_forced) {
				$forced['isNew'] = true;
				array_unshift($cached_items, $forced);
				$cached_items = array_slice($cached_items, 0, 4);
			}
		}
		if (count($cached_items) < 4) {
			$repair_used_ids = isset($option['used_ids']) && is_array($option['used_ids'])
				? array_map('intval', $option['used_ids'])
				: array();
			$cached_items = array_merge(
				$cached_items,
				bbb_social_calendar_pick_inserts(
					$inserts,
					4 - count($cached_items),
					array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $cached_items),
					$repair_used_ids,
					$week_key . ':repair'
				)
			);
			$option['used_ids'] = array_values(array_unique(array_merge($repair_used_ids, array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $cached_items))));
		}
		$cached_items = array_map(
			static function ($item) use ($spotlight_month): array {
				$item = is_array($item) ? $item : array();
				$item['isNew'] = (string) ($item['month'] ?? '') === $spotlight_month;
				return $item;
			},
			$cached_items
		);
		$weeks[$week_key]['items'] = array_map('bbb_social_calendar_insert_with_image', $cached_items);
		$weeks[$week_key]['month'] = $spotlight_month;
		$option['weeks']          = $weeks;
		update_option('bbb_social_calendar_insert_spotlight', $option, false);
		return $weeks[$week_key];
	}

	$insert_ids = array_map(static fn(array $insert): int => (int) $insert['id'], $inserts);
	$used_ids   = isset($option['used_ids']) && is_array($option['used_ids'])
		? array_values(array_intersect(array_map('intval', $option['used_ids']), $insert_ids))
		: array();

	if (count(array_unique($used_ids)) >= count($insert_ids)) {
		$used_ids = array();
	}

	$selected   = array();
	$exclude    = array();

	if (is_array($forced)) {
		$forced['isNew'] = true;
		$selected[]     = $forced;
		$exclude[]      = (int) $forced['id'];
		if (!in_array((int) $forced['id'], $used_ids, true)) {
			$used_ids[] = (int) $forced['id'];
		}
	}

	$picks = bbb_social_calendar_pick_inserts($inserts, 4 - count($selected), $exclude, $used_ids, $week_key);
	foreach ($picks as $pick) {
		$pick['isNew'] = (string) ($pick['month'] ?? '') === $spotlight_month;
		$selected[]   = $pick;
	}

	$payload = array(
		'weekStart' => $week_key,
		'weekLabel' => $week_start->format('M j') . ' - ' . $week_start->modify('+6 days')->format('M j'),
		'month'     => $spotlight_month,
		'items'     => array_values(array_map('bbb_social_calendar_insert_with_image', $selected)),
	);

	$weeks[$week_key] = $payload;
	$option['weeks']    = array_slice($weeks, -80, null, true);
	$option['used_ids'] = array_values(array_unique($used_ids));
	update_option('bbb_social_calendar_insert_spotlight', $option, false);

	return $payload;
}

function bbb_social_calendar_pinterest_boards(): array {
	return array(
		'kindle aesthetic',
		'printable kindle inserts',
		'kindle inserts',
		'the smut & sentiment society — book aesthetics',
		'fictional men who ruined me',
		array('label' => 'Fictional Men section - Matteo Leone / Phantom Mine', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/matteo-leone-phantom-mine/'),
		array('label' => 'Fictional Men section - Christian Reeves / Under Your Scars', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/christian-reeves-under-your-scars/'),
		array('label' => 'Fictional Men section - Death / Crown Me Dead', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/death-crown-me-dead/'),
		array('label' => 'Fictional Men section - Rhys Mackley / Pay For Your Lies', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/rhys-mackley-pay-for-your-lies/'),
		array('label' => 'Fictional Men section - Zevayr / Between Tides & Thunder', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/zevayr-between-tides-thunder/'),
		array('label' => 'Fictional Men section - Aero Westwood', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/aero-westwood/'),
		array('label' => 'Fictional Men section - Alex Corbeau Green', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/alex-corbeau-green/'),
		array('label' => 'Fictional Men section - Dane Dalton / Satanic Shadows', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/dane-dalton-satanic-shadows/'),
		array('label' => 'Fictional Men section - Malachi Vize / Little Stranger', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/malachi-vize-little-stranger/'),
		array('label' => 'Fictional Men section - Greyson Serel / Daggermouth', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/greyson-serel-daggermouth/'),
		array('label' => 'Fictional Men section - Emmett Montgomery / In Her Own League', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/emmett-montgomery-in-her-own-league/'),
		array('label' => 'Fictional Men section - Ryker Bennett / The Endless Fall', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/ryker-bennett-the-endless-fall/'),
		array('label' => 'Fictional Men section - Xaden Riorson / Fourth Wing', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/xaden-riorson-fourth-wing/'),
		array('label' => 'Fictional Men section - Thiago El Diablo Da Silva', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/thiago-el-diablo-da-silva/'),
		array('label' => 'Fictional Men section - Kade Mitchell / Insatiable', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/kade-mitchell-insatiable/'),
		array('label' => 'Fictional Men section - Nathan White / Lawless God', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/nathan-white-lawless-god/'),
		array('label' => 'Fictional Men section - Kai Azer / Powerless', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/kai-azer-powerless/'),
		array('label' => 'Fictional Men section - Dreadful Sharpe / My Dreadful Darling', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/dreadful-sharpe-my-dreadful-darling/'),
		array('label' => 'Fictional Men section - Zade Meadows / Haunting Adeline', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/zade-meadows-haunting-adeline/'),
		array('label' => 'Fictional Men section - Aaron Warner / Shatter Me', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/aaron-warner-shatter-me/'),
		array('label' => 'Fictional Men section - Ryan Shay / The Right Move', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/ryan-shay-the-right-move/'),
		array('label' => 'Fictional Men section - Tatum Blackthorn / Handsome Devil', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/tatum-blackthorn-handsome-devil/'),
		array('label' => 'Fictional Men section - Achilles Ferrante / Twisted Pawn', 'value' => 'https://www.pinterest.com/bybookishbabe/fictional-men-who-ruined-me/achilles-ferrante-twisted-pawn/'),
		'romance book quotes',
		'books you won’t stop thinking about',
		'bookish art prints',
		'Social',
		'reader aesthetic',
		'bookish templates',
	);
}

function bbb_social_calendar_clean_asset(array $asset): array {
	$id            = isset($asset['id']) && is_scalar($asset['id']) ? sanitize_text_field((string) $asset['id']) : '';
	$attachment_id = isset($asset['attachmentId']) ? absint($asset['attachmentId']) : 0;
	$url           = isset($asset['url']) && is_scalar($asset['url']) ? esc_url_raw((string) $asset['url']) : '';
	$data_url      = isset($asset['dataUrl']) && is_scalar($asset['dataUrl']) ? (string) $asset['dataUrl'] : '';
	$name          = isset($asset['name']) && is_scalar($asset['name']) ? sanitize_text_field((string) $asset['name']) : 'image';
	$type          = isset($asset['type']) && 'image' === (string) $asset['type'] ? 'image' : 'image';
	$board         = isset($asset['board']) && is_scalar($asset['board']) ? sanitize_text_field((string) $asset['board']) : '';
	$note          = isset($asset['note']) && is_scalar($asset['note']) ? sanitize_textarea_field((string) $asset['note']) : '';
	$source_url    = isset($asset['sourceUrl']) && is_scalar($asset['sourceUrl']) ? esc_url_raw((string) $asset['sourceUrl']) : '';
	$pin_title     = isset($asset['pinTitle']) && is_scalar($asset['pinTitle']) ? sanitize_text_field((string) $asset['pinTitle']) : '';
	$pin_description = isset($asset['pinDescription']) && is_scalar($asset['pinDescription']) ? sanitize_textarea_field((string) $asset['pinDescription']) : '';
	$pin_time      = isset($asset['pinTime']) && is_scalar($asset['pinTime']) ? sanitize_text_field((string) $asset['pinTime']) : '';
	$boyfriend_id  = isset($asset['boyfriendId']) ? absint($asset['boyfriendId']) : 0;
	$boyfriend_title = isset($asset['boyfriendTitle']) && is_scalar($asset['boyfriendTitle']) ? sanitize_text_field((string) $asset['boyfriendTitle']) : '';
	$profile_url   = isset($asset['profileUrl']) && is_scalar($asset['profileUrl']) ? esc_url_raw((string) $asset['profileUrl']) : '';
	$scheduler_id  = isset($asset['schedulerId']) && is_scalar($asset['schedulerId']) ? sanitize_text_field((string) $asset['schedulerId']) : '';
	$scheduler_queued_at = isset($asset['schedulerQueuedAt']) && is_scalar($asset['schedulerQueuedAt']) ? sanitize_text_field((string) $asset['schedulerQueuedAt']) : '';
	$scheduler_status = isset($asset['schedulerStatus']) && is_scalar($asset['schedulerStatus']) ? sanitize_key((string) $asset['schedulerStatus']) : '';
	$published_at = isset($asset['publishedAt']) && is_scalar($asset['publishedAt']) ? sanitize_text_field((string) $asset['publishedAt']) : '';
	$pinterest_pin_id = isset($asset['pinterestPinId']) && is_scalar($asset['pinterestPinId']) ? sanitize_text_field((string) $asset['pinterestPinId']) : '';
	$pinterest_pin_url = isset($asset['pinterestPinUrl']) && is_scalar($asset['pinterestPinUrl']) ? esc_url_raw((string) $asset['pinterestPinUrl']) : '';

	if ($attachment_id > 0) {
		$id  = 'attachment-' . $attachment_id;
		$url = wp_get_attachment_image_url($attachment_id, 'medium_large') ?: wp_get_attachment_url($attachment_id);
	}

	if ('' === $url && str_starts_with($data_url, 'data:image/')) {
		$url = $data_url;
	}

	if ($boyfriend_id > 0) {
		if ('' === $profile_url) {
			$profile_url = (string) get_permalink($boyfriend_id);
		}
		if ('' === $source_url) {
			$source_url = $profile_url;
		}
		if ('' === $boyfriend_title) {
			$boyfriend_title = trim(wp_strip_all_tags((string) get_the_title($boyfriend_id)));
		}

		$is_stale_moodboard_copy = (bool) preg_match('/moodboard\s+pin\s*\d*/i', $pin_title . ' ' . $pin_description . ' ' . $name);
		if ($is_stale_moodboard_copy) {
			$source_title = function_exists('bbb_fictional_boyfriend_seo_source') ? trim(wp_strip_all_tags(bbb_fictional_boyfriend_seo_source($boyfriend_id))) : '';
			$aesthetic_title = trim($boyfriend_title . ' aesthetic' . ('' !== $source_title ? ' from ' . $source_title : ''));
			if ('' !== $aesthetic_title) {
				$name      = $aesthetic_title;
				$pin_title = $aesthetic_title;
			}
			$pin_description = 'check out his profile for all the details.';
		}

		$is_main_profile_pin = (bool) preg_match('/\bfictional boyfriend profile\b/i', $pin_title . ' ' . $name);
		$is_placeholder_description = '' === $pin_description || (bool) preg_match('/^(fictional man|check out his profile for all the details\.?)$/i', trim($pin_description));
		if ($is_main_profile_pin && $is_placeholder_description && function_exists('bbb_fictional_boyfriend_main_image_pinterest_description')) {
			$pin_description = bbb_fictional_boyfriend_main_image_pinterest_description($boyfriend_id);
		}

		$pin_title       = bbb_social_calendar_lower_pin_copy($pin_title);
		$pin_description = bbb_social_calendar_lower_pin_copy($pin_description);
	}

	return array_filter(
		array(
			'id'           => $id,
			'attachmentId' => $attachment_id,
			'type'         => $type,
			'name'         => $name,
			'board'        => $board,
			'note'         => $note,
			'sourceUrl'    => $source_url,
			'pinTitle'     => $pin_title,
			'pinDescription' => $pin_description,
			'pinTime'      => $pin_time,
			'boyfriendId'  => $boyfriend_id,
			'boyfriendTitle' => $boyfriend_title,
				'profileUrl'   => $profile_url,
				'schedulerId'  => $scheduler_id,
				'schedulerQueuedAt' => $scheduler_queued_at,
				'schedulerStatus' => $scheduler_status,
				'publishedAt' => $published_at,
				'pinterestPinId' => $pinterest_pin_id,
				'pinterestPinUrl' => $pinterest_pin_url,
				'url'          => $url,
				'dataUrl'      => $url,
			),
		static fn($value): bool => '' !== $value && 0 !== $value
	);
}

function bbb_social_calendar_clean_entry(array $entry, string $platform = ''): array {
	$assets = isset($entry['assets']) && is_array($entry['assets']) ? $entry['assets'] : array();
	$clean_assets = array_values(
		array_filter(
			array_map(
				static fn($asset): array => is_array($asset) ? bbb_social_calendar_clean_asset($asset) : array(),
				$assets
			)
		)
	);

	if ('pinterest' === $platform) {
		$clean_assets = array_fill(0, 3, array());
		foreach ($assets as $index => $asset) {
			if (!is_array($asset)) {
				continue;
			}

			$clean_asset = bbb_social_calendar_clean_asset($asset);
			if (!$clean_asset) {
				continue;
			}

			$slot = is_int($index) ? $index : (int) $index;
			$id   = isset($clean_asset['id']) && is_scalar($clean_asset['id']) ? (string) $clean_asset['id'] : '';
			if (preg_match('/^slot-([0-2])$/', $id, $matches)) {
				$slot = (int) $matches[1];
			}

			if ($slot < 0 || $slot > 2) {
				continue;
			}

			$clean_assets[$slot] = $clean_asset;
		}
	}

	return array(
		'draft'     => isset($entry['draft']) && is_scalar($entry['draft']) ? sanitize_textarea_field((string) $entry['draft']) : '',
		'board'     => isset($entry['board']) && is_scalar($entry['board']) ? sanitize_text_field((string) $entry['board']) : '',
		'scheduled' => !empty($entry['scheduled']),
		'assets'    => $clean_assets,
	);
}

function bbb_social_calendar_post_state(): array {
	$state = get_option('bbb_social_calendar_post_state', array());
	if (!is_array($state)) {
		return array();
	}

	$clean = array();
	foreach ($state as $key => $entry) {
		if (!is_string($key) || !preg_match('/^\d{4}-\d{2}-\d{2}:[a-z0-9_-]+$/', $key) || !is_array($entry)) {
			continue;
		}
		$key_parts = explode(':', $key, 2);
		$clean[$key] = bbb_social_calendar_clean_entry($entry, (string) ($key_parts[1] ?? ''));
	}

	return $clean;
}

function bbb_social_calendar_ajax_save_entry(): void {
	bbb_social_calendar_check_ajax_access();

	$date     = isset($_POST['date']) ? sanitize_text_field((string) wp_unslash($_POST['date'])) : '';
	$platform = isset($_POST['platform']) ? sanitize_key((string) wp_unslash($_POST['platform'])) : '';
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || '' === $platform) {
		wp_send_json_error(array('message' => 'Invalid entry.'), 400);
	}

	$assets = array();
	if (isset($_POST['assets'])) {
		$decoded = json_decode((string) wp_unslash($_POST['assets']), true);
		$assets  = is_array($decoded) ? $decoded : array();
	}

	$entry = bbb_social_calendar_clean_entry(
		array(
			'draft'     => isset($_POST['draft']) ? wp_unslash($_POST['draft']) : '',
			'board'     => isset($_POST['board']) ? wp_unslash($_POST['board']) : '',
			'scheduled' => !empty($_POST['scheduled']) && 'false' !== (string) $_POST['scheduled'],
			'assets'    => $assets,
		),
		$platform
	);

	if ('pinterest' === $platform) {
		$entry['assets'] = array_slice($entry['assets'], 0, 3);
	}

	$state                        = bbb_social_calendar_post_state();
	$state[$date . ':' . $platform] = $entry;
	update_option('bbb_social_calendar_post_state', $state, false);

	wp_send_json_success(array('entry' => $entry));
}
add_action('wp_ajax_bbb_social_calendar_save_entry', 'bbb_social_calendar_ajax_save_entry');
add_action('wp_ajax_nopriv_bbb_social_calendar_save_entry', 'bbb_social_calendar_ajax_save_entry');

function bbb_social_calendar_ajax_get_state(): void {
	bbb_social_calendar_check_ajax_access();

	wp_send_json_success(array('state' => bbb_social_calendar_post_state()));
}
add_action('wp_ajax_bbb_social_calendar_get_state', 'bbb_social_calendar_ajax_get_state');
add_action('wp_ajax_nopriv_bbb_social_calendar_get_state', 'bbb_social_calendar_ajax_get_state');

function bbb_social_calendar_slot_time_for_date(string $date, int $slot): string {
	$timestamp = strtotime($date . ' 00:00:00');
	$is_sunday = false !== $timestamp && 0 === (int) gmdate('w', $timestamp);
	$times = $is_sunday ? array('4pm', '8pm', '11pm') : array('8am', '12pm', '9pm');

	return (string) ($times[$slot] ?? '');
}

function bbb_social_calendar_uploaded_asset_request_context(): array {
	$date     = isset($_POST['plannerDate']) ? sanitize_text_field((string) wp_unslash($_POST['plannerDate'])) : '';
	$platform = isset($_POST['plannerPlatform']) ? sanitize_key((string) wp_unslash($_POST['plannerPlatform'])) : '';
	$slot_raw = isset($_POST['plannerSlot']) && is_scalar($_POST['plannerSlot']) ? (string) wp_unslash($_POST['plannerSlot']) : '';
	$slot     = '' !== $slot_raw && is_numeric($slot_raw) ? (int) $slot_raw : -1;

	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || '' === $platform) {
		return array();
	}

	return array(
		'date'     => $date,
		'platform' => $platform,
		'slot'     => $slot,
	);
}

function bbb_social_calendar_attach_uploaded_asset_to_entry(array $asset): array {
	$context = bbb_social_calendar_uploaded_asset_request_context();
	if (empty($context)) {
		return array();
	}

	$date     = (string) $context['date'];
	$platform = (string) $context['platform'];
	$slot     = (int) $context['slot'];
	$key      = $date . ':' . $platform;
	$state    = bbb_social_calendar_post_state();
	$entry    = isset($state[$key]) && is_array($state[$key]) ? $state[$key] : array('draft' => '', 'board' => '', 'scheduled' => false, 'assets' => array());
	$assets   = isset($entry['assets']) && is_array($entry['assets']) ? $entry['assets'] : array();

	if ('pinterest' === $platform) {
		$slot = max(0, min(2, $slot));
		$assets = array_replace(array_fill(0, 3, array()), array_slice($assets, 0, 3));
		$existing = isset($assets[$slot]) && is_array($assets[$slot]) ? $assets[$slot] : array();
		$assets[$slot] = array_merge(
			$asset,
			array_filter(
				array(
					'board'          => (string) ($existing['board'] ?? ''),
					'note'           => (string) ($existing['note'] ?? ''),
					'pinTitle'       => (string) ($existing['pinTitle'] ?? ''),
					'pinDescription' => (string) ($existing['pinDescription'] ?? ''),
					'sourceUrl'      => (string) ($existing['sourceUrl'] ?? ''),
					'pinTime'        => (string) ($existing['pinTime'] ?? bbb_social_calendar_slot_time_for_date($date, $slot)),
				),
				static fn($value): bool => '' !== $value
			)
		);
	} else {
		if ($slot >= 0) {
			$assets[$slot] = $asset;
		} else {
			$assets[] = $asset;
		}
	}

	$entry['assets'] = $assets;
	$entry = bbb_social_calendar_clean_entry($entry, $platform);
	$state[$key] = $entry;
	update_option('bbb_social_calendar_post_state', $state, false);

	return array('key' => $key, 'entry' => $entry);
}

function bbb_social_calendar_planner_upload_dir(array $uploads): array {
	$time   = current_time('Y/m');
	$subdir = '/social-planner/' . $time;

	$uploads['subdir'] = $subdir;
	$uploads['path']   = trailingslashit((string) $uploads['basedir']) . ltrim($subdir, '/');
	$uploads['url']    = trailingslashit((string) $uploads['baseurl']) . ltrim($subdir, '/');

	return $uploads;
}

function bbb_social_calendar_upload_dir_index_file(string $path): void {
	if (!wp_mkdir_p($path)) {
		return;
	}

	$index_file = trailingslashit($path) . 'index.html';
	if (!file_exists($index_file)) {
		// Keep folder indexes quiet without blocking direct image URLs needed by schedulers.
		file_put_contents($index_file, '');
	}
}

function bbb_social_calendar_planner_uploaded_asset(string $url, string $file, string $fallback_name = 'planner-image'): array {
	$name = basename($file ?: $fallback_name);

	return bbb_social_calendar_clean_asset(
		array(
			'id'      => 'planner-' . substr(md5($url . '|' . $file), 0, 16),
			'type'    => 'image',
			'name'    => $name,
			'url'     => $url,
			'dataUrl' => $url,
		)
	);
}

function bbb_social_calendar_asset_is_processed(array $asset): bool {
	$status       = isset($asset['schedulerStatus']) && is_scalar($asset['schedulerStatus']) ? sanitize_key((string) $asset['schedulerStatus']) : '';
	$published_at = isset($asset['publishedAt']) && is_scalar($asset['publishedAt']) ? trim((string) $asset['publishedAt']) : '';

	return '' !== $published_at || in_array($status, array('published', 'processed'), true);
}

function bbb_social_calendar_planner_upload_file_from_url(string $url): string {
	if ('' === trim($url) || str_starts_with($url, 'data:image/')) {
		return '';
	}

	$uploads   = wp_upload_dir(null, false);
	$base_dir  = isset($uploads['basedir']) && is_scalar($uploads['basedir']) ? (string) $uploads['basedir'] : '';
	$base_url  = isset($uploads['baseurl']) && is_scalar($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';
	$url_path  = (string) wp_parse_url($url, PHP_URL_PATH);
	$base_path = (string) wp_parse_url($base_url, PHP_URL_PATH);

	if ('' === $base_dir || '' === $base_url || '' === $url_path) {
		return '';
	}

	$base_path = '/' . trim($base_path, '/');
	$relative  = '';
	if (str_starts_with($url_path, trailingslashit($base_path) . 'social-planner/')) {
		$relative = ltrim(substr($url_path, strlen($base_path)), '/');
	} elseif (str_starts_with($url_path, '/wp-content/uploads/social-planner/')) {
		$relative = ltrim(substr($url_path, strlen('/wp-content/uploads/')), '/');
	}

	if ('' === $relative || !str_starts_with($relative, 'social-planner/')) {
		return '';
	}

	$file = trailingslashit($base_dir) . $relative;
	if (!file_exists($file)) {
		return '';
	}

	$root_real = realpath(trailingslashit($base_dir) . 'social-planner');
	$file_real = realpath($file);
	if (!is_string($root_real) || !is_string($file_real) || !str_starts_with($file_real, trailingslashit($root_real))) {
		return '';
	}

	return $file_real;
}

function bbb_social_calendar_delete_processed_planner_asset(array $asset): bool {
	if (!bbb_social_calendar_asset_is_processed($asset)) {
		return false;
	}

	$url = isset($asset['url']) && is_scalar($asset['url']) ? (string) $asset['url'] : '';
	$file = bbb_social_calendar_planner_upload_file_from_url($url);
	if ('' === $file) {
		return false;
	}

	return wp_delete_file($file);
}

function bbb_social_calendar_ajax_upload_asset(): void {
	bbb_social_calendar_check_ajax_access();

	if (empty($_FILES['asset']) || !is_array($_FILES['asset'])) {
		wp_send_json_error(array('message' => 'No file uploaded.'), 400);
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	add_filter('upload_dir', 'bbb_social_calendar_planner_upload_dir');
	$file     = $_FILES['asset']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passed to wp_handle_upload.
	$uploaded = wp_handle_upload($file, array('test_form' => false));
	remove_filter('upload_dir', 'bbb_social_calendar_planner_upload_dir');

	if (!empty($uploaded['error'])) {
		wp_send_json_error(array('message' => (string) $uploaded['error']), 400);
	}

	$file_path = isset($uploaded['file']) && is_scalar($uploaded['file']) ? (string) $uploaded['file'] : '';
	$url       = isset($uploaded['url']) && is_scalar($uploaded['url']) ? esc_url_raw((string) $uploaded['url']) : '';
	$type      = isset($uploaded['type']) && is_scalar($uploaded['type']) ? (string) $uploaded['type'] : '';
	if ('' === $file_path || '' === $url || !str_starts_with($type, 'image/')) {
		if ('' !== $file_path && file_exists($file_path)) {
			wp_delete_file($file_path);
		}
		wp_send_json_error(array('message' => 'Upload a valid image file.'), 400);
	}

	bbb_social_calendar_upload_dir_index_file(dirname($file_path));

	$asset = bbb_social_calendar_planner_uploaded_asset($url, $file_path);
	$attached = bbb_social_calendar_attach_uploaded_asset_to_entry($asset);

	wp_send_json_success(array('asset' => $asset, 'attached' => $attached));
}
add_action('wp_ajax_bbb_social_calendar_upload_asset', 'bbb_social_calendar_ajax_upload_asset');
add_action('wp_ajax_nopriv_bbb_social_calendar_upload_asset', 'bbb_social_calendar_ajax_upload_asset');

function bbb_social_calendar_ajax_upload_data_url(): void {
	bbb_social_calendar_check_ajax_access();

	$data_url = isset($_POST['dataUrl']) && is_scalar($_POST['dataUrl']) ? (string) wp_unslash($_POST['dataUrl']) : '';
	$name     = isset($_POST['name']) && is_scalar($_POST['name']) ? sanitize_file_name((string) wp_unslash($_POST['name'])) : 'planner-image.jpg';
	if (!preg_match('#^data:(image/(?:avif|gif|jpe?g|png|webp));base64,([A-Za-z0-9+/=\\r\\n]+)$#i', $data_url, $matches)) {
		wp_send_json_error(array('message' => 'Invalid image data.'), 400);
	}

	$mime_type = strtolower((string) $matches[1]);
	$bytes     = base64_decode(preg_replace('/\\s+/', '', (string) $matches[2]), true);
	if (false === $bytes || '' === $bytes) {
		wp_send_json_error(array('message' => 'Could not decode image.'), 400);
	}

	$extension = match ($mime_type) {
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
		'image/gif'  => 'gif',
		'image/avif' => 'avif',
		default      => 'jpg',
	};

	if (!preg_match('/\\.(?:avif|gif|jpe?g|png|webp)$/i', $name)) {
		$name .= '.' . $extension;
	}

	add_filter('upload_dir', 'bbb_social_calendar_planner_upload_dir');
	$upload = wp_upload_bits($name, null, $bytes);
	remove_filter('upload_dir', 'bbb_social_calendar_planner_upload_dir');

	if (!empty($upload['error'])) {
		wp_send_json_error(array('message' => (string) $upload['error']), 400);
	}

	$file_path = isset($upload['file']) && is_scalar($upload['file']) ? (string) $upload['file'] : '';
	$url       = isset($upload['url']) && is_scalar($upload['url']) ? esc_url_raw((string) $upload['url']) : '';
	if ('' === $file_path || '' === $url) {
		wp_send_json_error(array('message' => 'Could not save image.'), 500);
	}

	bbb_social_calendar_upload_dir_index_file(dirname($file_path));

	$asset = bbb_social_calendar_planner_uploaded_asset($url, $file_path, $name);
	$attached = bbb_social_calendar_attach_uploaded_asset_to_entry($asset);

	wp_send_json_success(array('asset' => $asset, 'attached' => $attached));
}
add_action('wp_ajax_bbb_social_calendar_upload_data_url', 'bbb_social_calendar_ajax_upload_data_url');
add_action('wp_ajax_nopriv_bbb_social_calendar_upload_data_url', 'bbb_social_calendar_ajax_upload_data_url');

function bbb_social_calendar_meta_content(string $html, string $key): string {
	if (preg_match('/<meta\b[^>]*(?:property|name)=["\']' . preg_quote($key, '/') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
		return html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES | ENT_HTML5);
	}
	if (preg_match('/<meta\b[^>]*content=["\']([^"\']*)["\'][^>]*(?:property|name)=["\']' . preg_quote($key, '/') . '["\'][^>]*>/i', $html, $matches)) {
		return html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES | ENT_HTML5);
	}
	return '';
}

function bbb_social_calendar_clean_scraped_text(string $value): string {
	$value = html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5);
	$value = preg_replace('/\s+/', ' ', $value);
	return trim((string) $value);
}

function bbb_social_calendar_ajax_pin_generator_page(): void {
	bbb_social_calendar_check_ajax_access();

	$url = isset($_POST['url']) && is_scalar($_POST['url']) ? esc_url_raw((string) wp_unslash($_POST['url'])) : '';
	if ('' === $url || !preg_match('#^https?://#i', $url)) {
		wp_send_json_error(array('message' => 'Enter a valid page URL.'), 400);
	}

	$host = (string) wp_parse_url($url, PHP_URL_HOST);
	if ('' === $host || (!str_ends_with(strtolower($host), 'bybookishbabe.com') && !in_array(strtolower($host), array('localhost', '127.0.0.1'), true))) {
		wp_send_json_error(array('message' => 'Use a bybookishbabe page URL.'), 400);
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 15,
			'redirection' => 5,
			'user-agent'  => 'ByBookishBabe Social Planner; ' . home_url('/'),
		)
	);

	if (is_wp_error($response)) {
		wp_send_json_error(array('message' => $response->get_error_message()), 400);
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	$html = (string) wp_remote_retrieve_body($response);
	if ($code < 200 || $code >= 300 || '' === trim($html)) {
		wp_send_json_error(array('message' => 'Could not read that page.'), 400);
	}

	$title = bbb_social_calendar_meta_content($html, 'og:title');
	if ('' === $title && preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
		$title = bbb_social_calendar_clean_scraped_text($matches[1]);
	}
	if ('' === $title && preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $matches)) {
		$title = bbb_social_calendar_clean_scraped_text($matches[1]);
	}
	$title = preg_replace('/\s*[-|–]\s*bybookishbabe.*$/i', '', (string) $title);
	$title = bbb_social_calendar_clean_scraped_text((string) $title);

	$description = bbb_social_calendar_meta_content($html, 'og:description');
	if ('' === $description) {
		$description = bbb_social_calendar_meta_content($html, 'description');
	}
	$category = bbb_social_calendar_meta_content($html, 'article:section');

	$text_chunks = array();
	if (preg_match_all('/<(h2|h3|p|li)\b[^>]*>(.*?)<\/\1>/is', $html, $matches)) {
		foreach ($matches[2] as $raw_chunk) {
			$chunk = bbb_social_calendar_clean_scraped_text((string) $raw_chunk);
			if (strlen($chunk) > 25) {
				$text_chunks[] = $chunk;
			}
			if (count($text_chunks) >= 80) {
				break;
			}
		}
	}

	$books = array();
	if (preg_match_all('/<h3\b[^>]*>(.*?)<\/h3>/is', $html, $matches)) {
		foreach ($matches[1] as $raw_book) {
			$book = bbb_social_calendar_clean_scraped_text((string) $raw_book);
			if ('' !== $book && strlen($book) < 70 && !in_array($book, $books, true)) {
				$books[] = $book;
			}
			if (count($books) >= 10) {
				break;
			}
		}
	}

	$slug = basename((string) wp_parse_url($url, PHP_URL_PATH));
	$body = strtolower(implode(' ', $text_chunks));
	$known_tropes = array('enemies to lovers', 'slow burn', 'forced proximity', 'dark romance', 'morally gray', 'captor x captive', 'fated mates', 'sports romance', 'paranormal romance', 'romantasy', 'second chance', 'forbidden romance', 'grumpy sunshine', 'touch her and die', 'villain gets the girl', 'age gap', 'bully romance', 'reverse harem');
	$tropes = array_values(array_filter($known_tropes, static fn(string $trope): bool => str_contains($body, $trope)));
	$page_type = str_contains(strtolower($slug . ' ' . $category), 'review') ? 'review' : (count($books) >= 3 ? 'guide' : (preg_match('/enemies|slow-burn|dark-romance|trope|spice/i', $slug) ? 'trope' : 'general'));
	$boyfriend = array();
	$path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
	if (preg_match('#(?:^|/)fictional-boyfriends/([^/]+)/?$#i', $path, $matches) && post_type_exists('bbb_boyfriend')) {
		$boyfriend_post = get_page_by_path(sanitize_title((string) $matches[1]), OBJECT, 'bbb_boyfriend');
		if ($boyfriend_post instanceof WP_Post) {
			$page_type = 'boyfriend_profile';
			$book_id = function_exists('bbb_fictional_boyfriend_primary_book_id') ? bbb_fictional_boyfriend_primary_book_id((int) $boyfriend_post->ID) : 0;
			$book_title = $book_id > 0 ? get_the_title($book_id) : '';
			$boyfriend_tropes = function_exists('bbb_fictional_boyfriend_tropes') ? bbb_fictional_boyfriend_tropes((int) $boyfriend_post->ID) : array();
			$boyfriend_traits = function_exists('bbb_fictional_boyfriend_traits') ? bbb_fictional_boyfriend_traits((int) $boyfriend_post->ID) : array();
			$boyfriend_spice = function_exists('bbb_fictional_boyfriend_spice') ? bbb_fictional_boyfriend_spice((int) $boyfriend_post->ID) : 0;
			$boyfriend = array(
				'id'          => (int) $boyfriend_post->ID,
				'name'        => get_the_title($boyfriend_post),
				'book'        => $book_title,
				'tropes'      => array_values(array_filter(array_map('strval', $boyfriend_tropes))),
				'traits'      => array_values(array_filter(array_map('strval', $boyfriend_traits))),
				'spice'       => $boyfriend_spice,
				'pinTitle'    => function_exists('bbb_fictional_boyfriend_pinterest_title') ? bbb_fictional_boyfriend_pinterest_title((int) $boyfriend_post->ID) : '',
				'pinDescription' => function_exists('bbb_fictional_boyfriend_pinterest_description') ? bbb_fictional_boyfriend_pinterest_description((int) $boyfriend_post->ID) : '',
			);
			if (!empty($boyfriend_tropes)) {
				$tropes = array_values(array_unique(array_merge($tropes, array_map('strval', $boyfriend_tropes))));
			}
			if ('' !== $book_title && !in_array($book_title, $books, true)) {
				array_unshift($books, $book_title);
			}
		}
	}

	wp_send_json_success(
		array(
			'page' => array(
				'url'         => $url,
				'title'       => $title ?: $url,
				'description' => bbb_social_calendar_clean_scraped_text($description),
				'category'    => bbb_social_calendar_clean_scraped_text($category),
				'body'        => substr(implode(' ', $text_chunks), 0, 3000),
				'books'       => $books,
				'tropes'      => $tropes,
				'pageType'    => $page_type,
				'boyfriend'   => $boyfriend,
				'slug'        => sanitize_title($slug ?: 'page'),
			),
		)
	);
}
add_action('wp_ajax_bbb_social_calendar_pin_generator_page', 'bbb_social_calendar_ajax_pin_generator_page');
add_action('wp_ajax_nopriv_bbb_social_calendar_pin_generator_page', 'bbb_social_calendar_ajax_pin_generator_page');

function bbb_social_calendar_scheduler_secret(): string {
	if (defined('BBB_SOCIAL_CALENDAR_SCHEDULER_SECRET')) {
		return (string) BBB_SOCIAL_CALENDAR_SCHEDULER_SECRET;
	}

	return 'bbb-quote-admin-9f43c2c7f61b';
}

function bbb_social_calendar_rest_secret_is_valid(WP_REST_Request $request): bool {
	$secret = bbb_social_calendar_scheduler_secret();
	if ('' === $secret) {
		return false;
	}

	$provided = (string) ($request->get_header('x-bbb-social-planner-secret') ?: $request->get_param('secret'));
	return hash_equals($secret, $provided);
}

function bbb_social_calendar_pinterest_image_is_queueable(string $value): bool {
	return (bool) preg_match('#^https?://#i', $value) || (bool) preg_match('#^data:image/[a-z0-9.+-]+;base64,#i', $value);
}

function bbb_social_calendar_planner_image_file_missing(string $value): bool {
	if (!bbb_social_calendar_pinterest_image_is_queueable($value) || str_starts_with($value, 'data:image/')) {
		return false;
	}

	$url_path = (string) wp_parse_url($value, PHP_URL_PATH);
	if (!str_contains($url_path, '/wp-content/uploads/social-planner/')) {
		return false;
	}

	return '' === bbb_social_calendar_planner_upload_file_from_url($value);
}

function bbb_social_calendar_pinterest_slot_time(string $date, int $slot, array $asset): string {
	if (!empty($asset['pinTime']) && is_scalar($asset['pinTime'])) {
		return sanitize_text_field((string) $asset['pinTime']);
	}

	$is_sunday = 0 === (int) (new DateTimeImmutable($date, new DateTimeZone('America/Los_Angeles')))->format('w');
	$times     = $is_sunday ? array('4pm', '8pm', '11pm') : array('8am', '12pm', '9pm');

	return $times[$slot] ?? '8am';
}

function bbb_social_calendar_pinterest_scheduled_iso(string $date, string $time): string {
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return '';
	}

	$hour = match (strtolower(trim($time))) {
		'4pm' => 16,
		'8pm' => 20,
		'11pm' => 23,
		'10am' => 10,
		'12pm' => 12,
		'9pm' => 21,
		default => 8,
	};

	$local = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date . ' ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00:00', new DateTimeZone('America/Los_Angeles'));
	return $local instanceof DateTimeImmutable ? $local->setTimezone(new DateTimeZone('UTC'))->format('c') : '';
}

function bbb_social_calendar_instagram_scheduled_iso(string $date): string {
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return '';
	}

	$day = (int) (new DateTimeImmutable($date, new DateTimeZone('America/Los_Angeles')))->format('w');
	$time = match ($day) {
		2 => '09:30:00',
		3 => '16:00:00',
		5 => '11:00:00',
		6 => '15:30:00',
		default => '10:00:00',
	};

	$local = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date . ' ' . $time, new DateTimeZone('America/Los_Angeles'));
	return $local instanceof DateTimeImmutable ? $local->setTimezone(new DateTimeZone('UTC'))->format('c') : '';
}

function bbb_social_calendar_ready_pinterest_pins(): array {
	$state = bbb_social_calendar_post_state();
	$pins  = array();

	foreach ($state as $key => $entry) {
		if (!str_ends_with($key, ':pinterest') || !is_array($entry)) {
			continue;
		}

		$date   = substr($key, 0, 10);
		$assets = isset($entry['assets']) && is_array($entry['assets']) ? array_slice($entry['assets'], 0, 3) : array();
		foreach ($assets as $slot => $asset) {
			if (!is_array($asset)) {
				continue;
			}

			$image       = isset($asset['url']) && is_scalar($asset['url']) ? (string) $asset['url'] : '';
			$title       = isset($asset['pinTitle']) && is_scalar($asset['pinTitle']) ? trim((string) $asset['pinTitle']) : '';
			$description = isset($asset['pinDescription']) && is_scalar($asset['pinDescription']) ? trim((string) $asset['pinDescription']) : '';
			$link        = isset($asset['sourceUrl']) && is_scalar($asset['sourceUrl']) ? trim((string) $asset['sourceUrl']) : '';
			$board       = isset($asset['board']) && is_scalar($asset['board']) ? trim((string) $asset['board']) : '';

			if (!bbb_social_calendar_pinterest_image_is_queueable($image) || '' === $title || '' === $description || '' === $link || '' === $board || !empty($asset['schedulerId'])) {
				continue;
			}

			$time = bbb_social_calendar_pinterest_slot_time($date, (int) $slot, $asset);
			$pins[] = array(
				'date'         => $date,
				'slot'         => (int) $slot,
				'time'         => $time,
				'scheduledFor' => bbb_social_calendar_pinterest_scheduled_iso($date, $time),
				'image'        => $image,
				'title'        => $title,
				'description'  => $description,
				'link'         => $link,
				'board'        => $board,
				'note'         => isset($asset['note']) && is_scalar($asset['note']) ? sanitize_textarea_field((string) $asset['note']) : '',
			);
		}
	}

	return $pins;
}

function bbb_social_calendar_ready_instagram_posts(): array {
	$state = bbb_social_calendar_post_state();
	$posts = array();

	foreach ($state as $key => $entry) {
		if (!str_ends_with($key, ':instagram') || !is_array($entry)) {
			continue;
		}

		$date    = substr($key, 0, 10);
		$caption = isset($entry['draft']) && is_scalar($entry['draft']) ? trim((string) $entry['draft']) : '';
		$assets  = isset($entry['assets']) && is_array($entry['assets']) ? $entry['assets'] : array();
		$images  = array();

			foreach ($assets as $index => $asset) {
				if (!is_array($asset)) {
					continue;
				}
				$image = isset($asset['url']) && is_scalar($asset['url']) ? (string) $asset['url'] : '';
				if (!bbb_social_calendar_pinterest_image_is_queueable($image)) {
					continue;
				}
				if (bbb_social_calendar_planner_image_file_missing($image)) {
					$images = array();
					break;
				}
				$images[] = array(
					'url'   => $image,
					'name'  => isset($asset['name']) && is_scalar($asset['name']) ? sanitize_text_field((string) $asset['name']) : 'slide ' . (string) ($index + 1),
					'note'  => isset($asset['note']) && is_scalar($asset['note']) ? sanitize_textarea_field((string) $asset['note']) : '',
				'slide' => count($images) + 1,
			);
		}

		$today = (new DateTimeImmutable('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d');
		if ('' === $caption || empty($images) || $date < $today) {
			continue;
		}

		$posts[] = array(
			'date'         => $date,
			'slot'         => 'instagram',
			'time'         => '',
			'scheduledFor' => bbb_social_calendar_instagram_scheduled_iso($date),
			'caption'      => $caption,
			'note'         => '',
			'images'       => $images,
		);
	}

	return $posts;
}

function bbb_social_calendar_rest_pinterest_ready(WP_REST_Request $request): WP_REST_Response {
	if (!bbb_social_calendar_rest_secret_is_valid($request)) {
		return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
	}

	return new WP_REST_Response(
		array(
			'ok'   => true,
			'pins' => bbb_social_calendar_ready_pinterest_pins(),
		),
		200
	);
}

function bbb_social_calendar_rest_instagram_ready(WP_REST_Request $request): WP_REST_Response {
	if (!bbb_social_calendar_rest_secret_is_valid($request)) {
		return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
	}

	return new WP_REST_Response(
		array(
			'ok'    => true,
			'posts' => bbb_social_calendar_ready_instagram_posts(),
		),
		200
	);
}

function bbb_social_calendar_rest_debug(WP_REST_Request $request): WP_REST_Response {
	if (!bbb_social_calendar_rest_secret_is_valid($request)) {
		return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
	}

	$state     = bbb_social_calendar_post_state();
	$instagram = array();
	foreach ($state as $key => $entry) {
		if (!str_ends_with($key, ':instagram') || !is_array($entry)) {
			continue;
		}

		$date    = substr($key, 0, 10);
		$assets  = isset($entry['assets']) && is_array($entry['assets']) ? $entry['assets'] : array();
		$images  = array_values(
			array_filter(
				$assets,
				static function ($asset): bool {
					if (!is_array($asset)) {
						return false;
					}
					$image = isset($asset['url']) && is_scalar($asset['url']) ? (string) $asset['url'] : '';
					return bbb_social_calendar_pinterest_image_is_queueable($image);
				}
			)
		);
		$caption = isset($entry['draft']) && is_scalar($entry['draft']) ? trim((string) $entry['draft']) : '';
			$note_chars = 0;
			$broken_images = array();
			foreach ($assets as $asset) {
				if (is_array($asset) && isset($asset['note']) && is_scalar($asset['note'])) {
					$note_chars += strlen(trim((string) $asset['note']));
				}
				if (is_array($asset)) {
					$image = isset($asset['url']) && is_scalar($asset['url']) ? (string) $asset['url'] : '';
					if (bbb_social_calendar_planner_image_file_missing($image)) {
						$broken_images[] = array(
							'name' => isset($asset['name']) && is_scalar($asset['name']) ? sanitize_text_field((string) $asset['name']) : '',
							'url'  => esc_url_raw($image),
						);
					}
				}
			}
			$instagram[] = array(
				'key'          => $key,
				'date'         => $date,
				'captionChars' => strlen($caption),
			'noteChars'    => $note_chars,
			'assets'       => count($assets),
				'images'       => count($images),
				'brokenImages' => $broken_images,
				'scheduled'    => !empty($entry['scheduled']),
				'scheduledFor' => bbb_social_calendar_instagram_scheduled_iso($date),
				'ready'        => '' !== $caption && !empty($images) && empty($broken_images),
			);
	}

	return new WP_REST_Response(
		array(
			'ok'        => true,
			'total'     => count($state),
			'instagram' => $instagram,
		),
		200
	);
}

function bbb_social_calendar_rest_pinterest_status(WP_REST_Request $request): WP_REST_Response {
	if (!bbb_social_calendar_rest_secret_is_valid($request)) {
		return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
	}

	$items = $request->get_json_params();
	$items = isset($items['items']) && is_array($items['items']) ? $items['items'] : array();
	$state = bbb_social_calendar_post_state();
	$count = 0;

	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}

		$date = isset($item['date']) && is_scalar($item['date']) ? sanitize_text_field((string) $item['date']) : '';
		$slot = isset($item['slot']) ? (int) $item['slot'] : -1;
		$key  = $date . ':pinterest';
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $slot < 0 || $slot > 2 || empty($state[$key]['assets'][$slot]) || !is_array($state[$key]['assets'][$slot])) {
			continue;
		}

		$asset = $state[$key]['assets'][$slot];
		foreach (array('schedulerId', 'schedulerQueuedAt', 'schedulerStatus', 'publishedAt', 'pinterestPinId', 'pinterestPinUrl') as $field) {
			if (isset($item[$field]) && is_scalar($item[$field])) {
				$asset[$field] = (string) $item[$field];
			}
		}

		$clean_asset = bbb_social_calendar_clean_asset($asset);
		bbb_social_calendar_delete_processed_planner_asset($clean_asset);
		$state[$key]['assets'][$slot] = $clean_asset;
		$count++;
	}

	if ($count > 0) {
		update_option('bbb_social_calendar_post_state', $state, false);
	}

	return new WP_REST_Response(array('ok' => true, 'updated' => $count), 200);
}

function bbb_social_calendar_rest_instagram_status(WP_REST_Request $request): WP_REST_Response {
	if (!bbb_social_calendar_rest_secret_is_valid($request)) {
		return new WP_REST_Response(array('error' => 'Unauthorized'), 401);
	}

	$items = $request->get_json_params();
	$items = isset($items['items']) && is_array($items['items']) ? $items['items'] : array();
	$state = bbb_social_calendar_post_state();
	$count = 0;

	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}

		$date = isset($item['date']) && is_scalar($item['date']) ? sanitize_text_field((string) $item['date']) : '';
		$key  = $date . ':instagram';
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || empty($state[$key]) || !is_array($state[$key])) {
			continue;
		}

		foreach (array('schedulerId', 'schedulerQueuedAt', 'schedulerStatus', 'publishedAt') as $field) {
			if (isset($item[$field]) && is_scalar($item[$field])) {
				$state[$key][$field] = sanitize_text_field((string) $item[$field]);
			}
		}

		$processed = !empty($state[$key]['publishedAt']) || (isset($state[$key]['schedulerStatus']) && in_array(sanitize_key((string) $state[$key]['schedulerStatus']), array('published', 'processed'), true));
		$state[$key]['scheduled'] = true;
		if ($processed && !empty($state[$key]['assets']) && is_array($state[$key]['assets'])) {
			foreach ($state[$key]['assets'] as $asset) {
				if (is_array($asset)) {
					bbb_social_calendar_delete_processed_planner_asset(
						array_merge(
							$asset,
							array(
								'schedulerStatus' => (string) ($state[$key]['schedulerStatus'] ?? 'published'),
								'publishedAt'     => (string) ($state[$key]['publishedAt'] ?? current_time('mysql')),
							)
						)
					);
				}
			}
		}
		$count++;
	}

	if ($count > 0) {
		update_option('bbb_social_calendar_post_state', $state, false);
	}

	return new WP_REST_Response(array('ok' => true, 'updated' => $count), 200);
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/social-planner/pinterest-ready',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'bbb_social_calendar_rest_pinterest_ready',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'bbb/v1',
			'/social-planner/instagram-ready',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'bbb_social_calendar_rest_instagram_ready',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'bbb/v1',
			'/social-planner/pinterest-status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'bbb_social_calendar_rest_pinterest_status',
				'permission_callback' => '__return_true',
			)
		);

			register_rest_route(
				'bbb/v1',
				'/social-planner/instagram-status',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'bbb_social_calendar_rest_instagram_status',
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'bbb/v1',
				'/social-planner/debug',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => 'bbb_social_calendar_rest_debug',
					'permission_callback' => '__return_true',
				)
			);
		}
	);

function bbb_social_calendar_image_url_is_pin_asset(string $url): bool {
	return (bool) preg_match('/\.(?:avif|gif|jpe?g|png|webp)(?:\?.*)?$/i', $url)
		|| (bool) preg_match('#^https?://(?:www\.)?dropbox\.com/#i', $url)
		|| (bool) preg_match('#^https?://dl\.dropboxusercontent\.com/#i', $url);
}

function bbb_social_calendar_lower_pin_copy(string $copy): string {
	$copy = trim(wp_strip_all_tags($copy));
	return function_exists('mb_strtolower') ? mb_strtolower($copy) : strtolower($copy);
}

function bbb_social_calendar_direct_image_url(string $url): string {
	$url = esc_url_raw(trim($url));
	if ('' === $url) {
		return '';
	}

	$parts = wp_parse_url($url);
	$host  = strtolower((string) ($parts['host'] ?? ''));
	if (in_array($host, array('dropbox.com', 'www.dropbox.com'), true)) {
		$query = array();
		if (!empty($parts['query'])) {
			parse_str((string) $parts['query'], $query);
		}
		unset($query['dl'], $query['raw']);
		$path = (string) ($parts['path'] ?? '');
		$url  = 'https://dl.dropboxusercontent.com' . $path;
		if ($query) {
			$url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
		}
	}

	return esc_url_raw($url);
}

function bbb_social_calendar_extract_image_urls_from_value($value): array {
	$urls = array();
	if (is_array($value)) {
		foreach ($value as $nested) {
			$urls = array_merge($urls, bbb_social_calendar_extract_image_urls_from_value($nested));
		}
		return $urls;
	}

	if (!is_scalar($value)) {
		return $urls;
	}

	$text = (string) $value;
	if ('' === trim($text)) {
		return $urls;
	}

	$decoded = maybe_unserialize($text);
	if (is_array($decoded)) {
		return bbb_social_calendar_extract_image_urls_from_value($decoded);
	}

	if (preg_match_all('/https?:\/\/[^\s"\'<>]+/i', $text, $matches)) {
		foreach ($matches[0] as $candidate) {
			$candidate = bbb_social_calendar_direct_image_url(html_entity_decode((string) $candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			if (bbb_social_calendar_image_url_is_pin_asset($candidate)) {
				$urls[] = $candidate;
			}
		}
	}

	return array_values(array_unique($urls));
}

function bbb_social_calendar_add_boyfriend_pin_asset(array &$assets, array &$seen, int $post_id, array $asset): void {
	$url = isset($asset['url']) && is_scalar($asset['url']) ? bbb_social_calendar_direct_image_url((string) $asset['url']) : '';
	if ('' === $url || isset($seen[$url])) {
		return;
	}

	$seen[$url] = true;
	$profile_url = (string) get_permalink($post_id);
	$boyfriend_title = trim(wp_strip_all_tags((string) get_the_title($post_id)));
	$pin_title = isset($asset['pinTitle']) && is_scalar($asset['pinTitle']) ? sanitize_text_field((string) $asset['pinTitle']) : '';
	if ('' === $pin_title && function_exists('bbb_fictional_boyfriend_pinterest_title')) {
		$pin_title = bbb_fictional_boyfriend_pinterest_title($post_id);
	}
	if ('' === $pin_title) {
		$pin_title = $boyfriend_title . ' fictional boyfriend profile';
	}
	$pin_description = isset($asset['pinDescription']) && is_scalar($asset['pinDescription']) ? sanitize_textarea_field((string) $asset['pinDescription']) : '';
	if ('' === $pin_description && function_exists('bbb_fictional_boyfriend_pinterest_description')) {
		$pin_description = bbb_fictional_boyfriend_pinterest_description($post_id);
	}
	$pin_title       = bbb_social_calendar_lower_pin_copy($pin_title);
	$pin_description = bbb_social_calendar_lower_pin_copy($pin_description);

	$assets[] = bbb_social_calendar_clean_asset(
		array(
			'id'             => 'boyfriend-' . $post_id . '-' . substr(md5($url), 0, 12),
			'type'           => 'image',
			'name'           => isset($asset['name']) && is_scalar($asset['name']) ? (string) $asset['name'] : $pin_title,
			'url'            => $url,
			'dataUrl'        => $url,
			'sourceUrl'      => isset($asset['sourceUrl']) && is_scalar($asset['sourceUrl']) ? (string) $asset['sourceUrl'] : $profile_url,
			'profileUrl'     => $profile_url,
			'pinTitle'       => $pin_title,
			'pinDescription' => $pin_description,
			'boyfriendId'    => $post_id,
			'boyfriendTitle' => $boyfriend_title,
		)
	);
}

function bbb_social_calendar_boyfriend_pin_assets(int $post_id): array {
	$post = get_post($post_id);
	if (!$post instanceof WP_Post || 'bbb_boyfriend' !== $post->post_type || 'publish' !== $post->post_status) {
		return array();
	}

	$assets = array();
	$seen   = array();
	$profile_url = (string) get_permalink($post_id);
	$boyfriend_title = trim(wp_strip_all_tags((string) get_the_title($post_id)));
	$source_title = function_exists('bbb_fictional_boyfriend_seo_source') ? trim(wp_strip_all_tags(bbb_fictional_boyfriend_seo_source($post_id))) : '';
	$default_title = function_exists('bbb_fictional_boyfriend_pinterest_title') ? bbb_fictional_boyfriend_pinterest_title($post_id) : $boyfriend_title . ' fictional boyfriend profile';
	$default_description = function_exists('bbb_fictional_boyfriend_main_image_pinterest_description')
		? bbb_fictional_boyfriend_main_image_pinterest_description($post_id)
		: (function_exists('bbb_fictional_boyfriend_pinterest_description') ? bbb_fictional_boyfriend_pinterest_description($post_id) : $default_title);
	$aesthetic_title = trim($boyfriend_title . ' aesthetic' . ('' !== $source_title ? ' from ' . $source_title : ''));
	$aesthetic_description = 'check out his profile for all the details.';

	$profile_image = (string) get_the_post_thumbnail_url($post_id, 'full');
	if ('' !== $profile_image) {
		bbb_social_calendar_add_boyfriend_pin_asset(
			$assets,
			$seen,
			$post_id,
				array(
					'url'            => $profile_image,
					'sourceUrl'      => $profile_url,
					'name'           => $default_title,
					'pinTitle'       => $default_title,
					'pinDescription' => $default_description,
				)
		);
	}

	if (function_exists('bbb_fictional_boyfriend_pinterest_links')) {
		foreach (bbb_fictional_boyfriend_pinterest_links($post_id) as $index => $pin_url) {
			$pin_parts = array_map('trim', explode('|', (string) $pin_url, 2));
			$pin_media_url = bbb_social_calendar_direct_image_url((string) ($pin_parts[0] ?? ''));
			if (!bbb_social_calendar_image_url_is_pin_asset($pin_media_url)) {
				continue;
			}
			bbb_social_calendar_add_boyfriend_pin_asset(
				$assets,
				$seen,
				$post_id,
				array(
					'url'            => $pin_media_url,
					'sourceUrl'      => $profile_url,
					'name'           => $aesthetic_title ?: $boyfriend_title . ' aesthetic',
					'pinTitle'       => $aesthetic_title ?: $default_title,
					'pinDescription' => $aesthetic_description,
				)
			);
		}
	}

	$attached_images = get_posts(
		array(
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'post_parent'            => $post_id,
			'post_mime_type'         => 'image',
			'posts_per_page'         => 50,
			'orderby'                => 'menu_order ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	foreach ($attached_images as $attachment) {
		if (!$attachment instanceof WP_Post) {
			continue;
		}
		$image_url = (string) wp_get_attachment_image_url((int) $attachment->ID, 'full');
		if ('' === $image_url) {
			$image_url = (string) wp_get_attachment_url((int) $attachment->ID);
		}
		if ('' === $image_url) {
			continue;
		}
		$attachment_name = trim(wp_strip_all_tags((string) get_the_title($attachment)));
		$is_collage      = (bool) preg_match('/collage|aesthetic|mood/i', $attachment_name . ' ' . $image_url);
		bbb_social_calendar_add_boyfriend_pin_asset(
			$assets,
			$seen,
			$post_id,
			array(
				'url'            => $image_url,
				'sourceUrl'      => $profile_url,
				'name'           => $attachment_name ?: ($is_collage ? $boyfriend_title . ' collage' : $boyfriend_title . ' profile image'),
				'pinTitle'       => $is_collage ? ($aesthetic_title ?: $boyfriend_title . ' aesthetic') : $default_title,
				'pinDescription' => $is_collage ? $aesthetic_description : $default_description,
			)
		);
	}

	$content_image_urls = bbb_social_calendar_extract_image_urls_from_value($post->post_content);
	$meta_image_urls    = array();
	foreach (get_post_meta($post_id) as $meta_values) {
		$meta_image_urls = array_merge($meta_image_urls, bbb_social_calendar_extract_image_urls_from_value($meta_values));
	}
	foreach (array_values(array_unique(array_merge($content_image_urls, $meta_image_urls))) as $image_url) {
		$is_collage = (bool) preg_match('/collage|aesthetic|mood/i', $image_url);
		bbb_social_calendar_add_boyfriend_pin_asset(
			$assets,
			$seen,
			$post_id,
			array(
				'url'            => $image_url,
				'sourceUrl'      => $profile_url,
				'name'           => $is_collage ? $boyfriend_title . ' collage' : $boyfriend_title . ' profile image',
				'pinTitle'       => $is_collage ? ($aesthetic_title ?: $boyfriend_title . ' aesthetic') : $default_title,
				'pinDescription' => $is_collage ? $aesthetic_description : $default_description,
			)
		);
	}

	$book_id = function_exists('bbb_fictional_boyfriend_primary_book_id') ? bbb_fictional_boyfriend_primary_book_id($post_id) : 0;
	if ($book_id) {
		$book_cover = (string) get_the_post_thumbnail_url($book_id, 'full');
		if ('' === $book_cover && function_exists('bbb_get_book_cover_url')) {
			$book_cover = (string) bbb_get_book_cover_url($book_id);
		}
		if ('' !== $book_cover) {
			$book_title = trim(wp_strip_all_tags((string) get_the_title($book_id)));
			bbb_social_calendar_add_boyfriend_pin_asset(
				$assets,
				$seen,
				$post_id,
				array(
					'url'            => $book_cover,
					'sourceUrl'      => $profile_url,
					'name'           => ($book_title ?: $boyfriend_title) . ' book cover',
					'pinTitle'       => ($book_title ?: $boyfriend_title) . ' book cover',
					'pinDescription' => 'save this book cover from ' . $boyfriend_title . '.',
				)
			);
		}
	}

	if (function_exists('bbb_fictional_boyfriend_quotes') && function_exists('bbb_quote_pin_card_url')) {
		foreach (bbb_fictional_boyfriend_quotes($post_id, 4) as $quote) {
			if (!$quote instanceof WP_Post) {
				continue;
			}
			$quote_pin_args = array(
				'context'   => 'boyfriend',
				'source_id' => $post_id,
			);
			$quote_url = bbb_quote_pin_card_url($quote, $quote_pin_args);
			if ('' === $quote_url) {
				continue;
			}
			bbb_social_calendar_add_boyfriend_pin_asset(
				$assets,
				$seen,
				$post_id,
				array(
					'url'            => $quote_url,
					'sourceUrl'      => $profile_url,
					'name'           => function_exists('bbb_quote_pin_title') ? bbb_quote_pin_title($quote, $quote_pin_args) : $boyfriend_title . ' quote',
					'pinTitle'       => function_exists('bbb_quote_pin_title') ? bbb_quote_pin_title($quote, $quote_pin_args) : $boyfriend_title . ' quote',
					'pinDescription' => function_exists('bbb_quote_pin_description') ? bbb_quote_pin_description($quote, $quote_pin_args) : $default_description,
				)
			);
		}
	}

	return array_slice($assets, 0, 40);
}

function bbb_social_calendar_boyfriend_pin_results(string $query = ''): array {
	$posts = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => 'publish',
			'posts_per_page'         => 24,
			's'                      => $query,
			'orderby'                => 'modified',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$results = array();
	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}
		$assets = bbb_social_calendar_boyfriend_pin_assets((int) $post->ID);
		if (!$assets) {
			continue;
		}
		$results[] = array(
			'id'     => (int) $post->ID,
			'title'  => get_the_title($post),
			'url'    => get_permalink($post),
			'assets' => $assets,
		);
	}

	return $results;
}

function bbb_social_calendar_ajax_boyfriend_pins(): void {
	bbb_social_calendar_check_ajax_access();

	$query = isset($_POST['query']) ? sanitize_text_field((string) wp_unslash($_POST['query'])) : '';
	wp_send_json_success(array('results' => bbb_social_calendar_boyfriend_pin_results($query)));
}
add_action('wp_ajax_bbb_social_calendar_boyfriend_pins', 'bbb_social_calendar_ajax_boyfriend_pins');
add_action('wp_ajax_nopriv_bbb_social_calendar_boyfriend_pins', 'bbb_social_calendar_ajax_boyfriend_pins');

function bbb_social_calendar_share_key(string $token): string {
	return 'bbb_social_calendar_share_' . $token;
}

function bbb_social_calendar_clean_share_row(array $row): array {
	return array(
		'date'        => isset($row['date']) && is_scalar($row['date']) ? sanitize_text_field((string) $row['date']) : '',
		'dateLabel'   => isset($row['dateLabel']) && is_scalar($row['dateLabel']) ? sanitize_text_field((string) $row['dateLabel']) : '',
		'pin'         => isset($row['pin']) && is_scalar($row['pin']) ? sanitize_text_field((string) $row['pin']) : '',
		'time'        => isset($row['time']) && is_scalar($row['time']) ? sanitize_text_field((string) $row['time']) : '',
		'image'       => isset($row['image']) && is_scalar($row['image']) ? esc_url_raw((string) $row['image']) : '',
		'title'       => isset($row['title']) && is_scalar($row['title']) ? sanitize_text_field((string) $row['title']) : '',
		'description' => isset($row['description']) && is_scalar($row['description']) ? sanitize_textarea_field((string) $row['description']) : '',
		'link'        => isset($row['link']) && is_scalar($row['link']) ? esc_url_raw((string) $row['link']) : '',
		'board'       => isset($row['board']) && is_scalar($row['board']) ? sanitize_text_field((string) $row['board']) : '',
		'note'        => isset($row['note']) && is_scalar($row['note']) ? sanitize_textarea_field((string) $row['note']) : '',
	);
}

function bbb_social_calendar_shared_view(string $token): array {
	$token = sanitize_key($token);
	if ('' === $token) {
		return array();
	}

	$data = get_transient(bbb_social_calendar_share_key($token));
	return is_array($data) ? $data : array();
}

function bbb_social_calendar_ajax_create_share(): void {
	bbb_social_calendar_check_ajax_access();

	$decoded = isset($_POST['rows']) ? json_decode((string) wp_unslash($_POST['rows']), true) : array();
	$rows    = is_array($decoded) ? array_slice($decoded, 0, 24) : array();
	$rows    = array_values(array_filter(array_map(static fn($row): array => is_array($row) ? bbb_social_calendar_clean_share_row($row) : array(), $rows)));
	if (!$rows) {
		wp_send_json_error(array('message' => 'No pins to share.'), 400);
	}

	$token = strtolower(str_replace('-', '', wp_generate_uuid4()));
	$payload = array(
		'createdAt' => current_time('mysql'),
		'range'     => isset($_POST['range']) ? sanitize_text_field((string) wp_unslash($_POST['range'])) : '',
		'rows'      => $rows,
	);
	set_transient(bbb_social_calendar_share_key($token), $payload, 30 * DAY_IN_SECONDS);

	wp_send_json_success(
		array(
			'url' => add_query_arg('token', rawurlencode($token), home_url('/social-planner-share/')),
		)
	);
}
add_action('wp_ajax_bbb_social_calendar_create_share', 'bbb_social_calendar_ajax_create_share');
add_action('wp_ajax_nopriv_bbb_social_calendar_create_share', 'bbb_social_calendar_ajax_create_share');

function bbb_social_calendar_ics_proxy(): void {
	if (!current_user_can('manage_options')) {
		status_header(404);
		exit;
	}

	check_ajax_referer('bbb_social_calendar_ics', 'nonce');

	$settings = bbb_social_calendar_settings();
	$ical_url = trim((string) ($settings['ical_url'] ?? ''));
	if ('' === $ical_url) {
		status_header(204);
		exit;
	}

	$cache_key = 'bbb_social_calendar_ics_' . md5($ical_url);
	$body      = get_transient($cache_key);

	if (!is_string($body)) {
		$response = wp_remote_get(
			$ical_url,
			array(
				'timeout'     => 12,
				'redirection' => 3,
				'user-agent'  => 'ByBookishBabe Social Calendar; ' . home_url('/'),
			)
		);

		if (is_wp_error($response)) {
			status_header(502);
			echo esc_html($response->get_error_message());
			exit;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = (string) wp_remote_retrieve_body($response);
		if ($code < 200 || $code >= 300 || '' === trim($body)) {
			status_header(502);
			echo 'Calendar feed unavailable.';
			exit;
		}

		set_transient($cache_key, $body, 10 * MINUTE_IN_SECONDS);
	}

	if (!headers_sent()) {
		header('Content-Type: text/calendar; charset=utf-8');
		header('Cache-Control: private, max-age=300');
	}

	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Proxied iCal content, served as text/calendar.
	exit;
}
add_action('wp_ajax_bbb_social_calendar_ics', 'bbb_social_calendar_ics_proxy');
