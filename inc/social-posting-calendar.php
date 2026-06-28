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

function bbb_social_calendar_pinterest_boards(): array {
	return array(
		'kindle aesthetic',
		'printable kindle inserts',
		'kindle inserts',
		'the smut & sentiment society — book aesthetics',
		'fictional men who ruined me',
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

function bbb_social_calendar_clean_entry(array $entry): array {
	$assets = isset($entry['assets']) && is_array($entry['assets']) ? $entry['assets'] : array();

	return array(
		'draft'     => isset($entry['draft']) && is_scalar($entry['draft']) ? sanitize_textarea_field((string) $entry['draft']) : '',
		'board'     => isset($entry['board']) && is_scalar($entry['board']) ? sanitize_text_field((string) $entry['board']) : '',
		'scheduled' => !empty($entry['scheduled']),
		'assets'    => array_values(
			array_filter(
				array_map(
					static fn($asset): array => is_array($asset) ? bbb_social_calendar_clean_asset($asset) : array(),
					$assets
				)
			)
		),
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
		$clean[$key] = bbb_social_calendar_clean_entry($entry);
	}

	return $clean;
}

function bbb_social_calendar_ajax_save_entry(): void {
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Not allowed.'), 403);
	}

	check_ajax_referer('bbb_social_calendar_state', 'nonce');

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
		)
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

function bbb_social_calendar_ajax_upload_asset(): void {
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Not allowed.'), 403);
	}

	check_ajax_referer('bbb_social_calendar_state', 'nonce');

	if (empty($_FILES['asset']) || !is_array($_FILES['asset'])) {
		wp_send_json_error(array('message' => 'No file uploaded.'), 400);
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$file     = $_FILES['asset']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passed to wp_handle_upload.
	$uploaded = wp_handle_upload($file, array('test_form' => false));
	if (!empty($uploaded['error'])) {
		wp_send_json_error(array('message' => (string) $uploaded['error']), 400);
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => (string) ($uploaded['type'] ?? ''),
			'post_title'     => sanitize_file_name(pathinfo((string) ($uploaded['file'] ?? ''), PATHINFO_FILENAME)),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		(string) ($uploaded['file'] ?? '')
	);

	if (is_wp_error($attachment_id) || !$attachment_id) {
		wp_send_json_error(array('message' => 'Could not save image.'), 500);
	}

	$metadata = wp_generate_attachment_metadata($attachment_id, (string) ($uploaded['file'] ?? ''));
	wp_update_attachment_metadata($attachment_id, $metadata);

	$url   = wp_get_attachment_image_url($attachment_id, 'medium_large') ?: wp_get_attachment_url($attachment_id);
	$asset = bbb_social_calendar_clean_asset(
		array(
			'attachmentId' => $attachment_id,
			'type'         => 'image',
			'name'         => basename((string) ($uploaded['file'] ?? 'image')),
			'url'          => $url,
		)
	);

	wp_send_json_success(array('asset' => $asset));
}
add_action('wp_ajax_bbb_social_calendar_upload_asset', 'bbb_social_calendar_ajax_upload_asset');

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

function bbb_social_calendar_pinterest_slot_time(string $date, int $slot, array $asset): string {
	if (!empty($asset['pinTime']) && is_scalar($asset['pinTime'])) {
		return sanitize_text_field((string) $asset['pinTime']);
	}

	$is_sunday = 0 === (int) (new DateTimeImmutable($date, new DateTimeZone('America/Los_Angeles')))->format('w');
	$times     = $is_sunday ? array('10am', '12pm', '9pm') : array('8am', '12pm', '9pm');

	return $times[$slot] ?? '8am';
}

function bbb_social_calendar_pinterest_scheduled_iso(string $date, string $time): string {
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return '';
	}

	$hour = match (strtolower(trim($time))) {
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
		foreach ($assets as $asset) {
			if (is_array($asset) && isset($asset['note']) && is_scalar($asset['note'])) {
				$note_chars += strlen(trim((string) $asset['note']));
			}
		}
		$instagram[] = array(
			'key'          => $key,
			'date'         => $date,
			'captionChars' => strlen($caption),
			'noteChars'    => $note_chars,
			'assets'       => count($assets),
			'images'       => count($images),
			'scheduled'    => !empty($entry['scheduled']),
			'scheduledFor' => bbb_social_calendar_instagram_scheduled_iso($date),
			'ready'        => '' !== $caption && !empty($images),
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

		$state[$key]['assets'][$slot] = bbb_social_calendar_clean_asset($asset);
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

		$state[$key]['scheduled'] = true;
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
	return (bool) preg_match('/\.(?:avif|gif|jpe?g|png|webp)(?:\?.*)?$/i', $url);
}

function bbb_social_calendar_lower_pin_copy(string $copy): string {
	$copy = trim(wp_strip_all_tags($copy));
	return function_exists('mb_strtolower') ? mb_strtolower($copy) : strtolower($copy);
}

function bbb_social_calendar_add_boyfriend_pin_asset(array &$assets, array &$seen, int $post_id, array $asset): void {
	$url = isset($asset['url']) && is_scalar($asset['url']) ? esc_url_raw((string) $asset['url']) : '';
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
	$default_description = function_exists('bbb_fictional_boyfriend_pinterest_description') ? bbb_fictional_boyfriend_pinterest_description($post_id) : $default_title;
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
			$pin_media_url = esc_url_raw((string) ($pin_parts[0] ?? ''));
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

	return array_slice($assets, 0, 12);
}

function bbb_social_calendar_ajax_boyfriend_pins(): void {
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Not allowed.'), 403);
	}

	check_ajax_referer('bbb_social_calendar_state', 'nonce');

	$query = isset($_POST['query']) ? sanitize_text_field((string) wp_unslash($_POST['query'])) : '';
	$posts = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => 'publish',
			'posts_per_page'         => 12,
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

	wp_send_json_success(array('results' => $results));
}
add_action('wp_ajax_bbb_social_calendar_boyfriend_pins', 'bbb_social_calendar_ajax_boyfriend_pins');

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
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Not allowed.'), 403);
	}

	check_ajax_referer('bbb_social_calendar_state', 'nonce');

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
