<?php
/**
 * Progressive Web App support.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_pwa_theme_color(): string {
	return (string) apply_filters('bbb_pwa_theme_color', '#f6d7df');
}

function bbb_pwa_version(): string {
	$version_file = get_theme_file_path('assets/pwa/version.txt');
	if (is_readable($version_file)) {
		$version = trim((string) file_get_contents($version_file));
		if ('' !== $version && preg_match('/^[A-Za-z0-9_.-]+$/', $version)) {
			return $version;
		}
	}

	return 'bybookishbabe-20260614-pwa-home';
}

function bbb_pwa_asset_uri(string $relative_path): string {
	return add_query_arg('v', bbb_pwa_version(), get_theme_file_uri($relative_path));
}

function bbb_pwa_base64url_encode(string $value): string {
	return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function bbb_pwa_base64url_decode(string $value): string {
	$padded = strtr($value, '-_', '+/');
	$padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);

	return (string) base64_decode($padded, true);
}

function bbb_pwa_install_url(): string {
	return home_url('/bybookishbabe-app/?install=1');
}

function bbb_pwa_generate_vapid_keypair(): array {
	$key = openssl_pkey_new(
		array(
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name'       => 'prime256v1',
		)
	);

	if (false === $key || !openssl_pkey_export($key, $private_pem)) {
		return array();
	}

	$details = openssl_pkey_get_details($key);
	$x       = isset($details['ec']['x']) ? (string) $details['ec']['x'] : '';
	$y       = isset($details['ec']['y']) ? (string) $details['ec']['y'] : '';

	if ('' === $x || '' === $y) {
		return array();
	}

	return array(
		'public_key'  => bbb_pwa_base64url_encode("\x04" . $x . $y),
		'public_x'    => bbb_pwa_base64url_encode($x),
		'public_y'    => bbb_pwa_base64url_encode($y),
		'private_pem' => (string) $private_pem,
		'created_at'  => current_time('mysql', true),
	);
}

function bbb_pwa_vapid_keypair(): array {
	$keys = get_option('bbb_pwa_vapid_keypair', array());

	if (is_array($keys) && !empty($keys['public_key']) && !empty($keys['private_pem'])) {
		return $keys;
	}

	$keys = bbb_pwa_generate_vapid_keypair();
	if (!empty($keys['public_key']) && !empty($keys['private_pem'])) {
		update_option('bbb_pwa_vapid_keypair', $keys, false);
		return $keys;
	}

	return array();
}

function bbb_pwa_vapid_public_key(): string {
	$key = defined('BBB_PWA_VAPID_PUBLIC_KEY')
		? (string) BBB_PWA_VAPID_PUBLIC_KEY
		: (string) (bbb_pwa_vapid_keypair()['public_key'] ?? '');

	return trim((string) apply_filters('bbb_pwa_vapid_public_key', $key));
}

function bbb_pwa_vapid_private_pem(): string {
	$key = defined('BBB_PWA_VAPID_PRIVATE_PEM')
		? (string) BBB_PWA_VAPID_PRIVATE_PEM
		: (string) (bbb_pwa_vapid_keypair()['private_pem'] ?? '');

	return trim((string) apply_filters('bbb_pwa_vapid_private_pem', $key));
}

function bbb_pwa_monthly_theme_notification_payload(): array {
	$url = add_query_arg(
		array(
			'source'       => 'pwa-monthly-theme-july-2026',
			'utm_source'   => 'pwa',
			'utm_medium'   => 'push',
			'utm_campaign' => 'july-2026-monthly-theme',
		),
		home_url('/monthly-theme/')
	);

	return array(
		'title' => 'you voted for it. we built it.',
		'body'  => 'midnight summer, after hours is officially here.',
		'url'   => $url,
		'tag'   => 'bbb-monthly-theme-july-2026',
	);
}

function bbb_pwa_monthly_theme_notification_send_at(): int {
	$timezone = new DateTimeZone('America/Los_Angeles');
	$send_at  = new DateTimeImmutable('2026-07-01 15:00:00', $timezone);

	return $send_at->getTimestamp();
}

function bbb_pwa_schedule_monthly_theme_notification(): void {
	$hook      = 'bbb_pwa_send_monthly_theme_notification';
	$timestamp = bbb_pwa_monthly_theme_notification_send_at();

	if (wp_next_scheduled($hook)) {
		return;
	}

	if (time() >= $timestamp) {
		update_option(
			'bbb_pwa_monthly_theme_notification_schedule',
			array(
				'status'       => 'missed',
				'scheduled_at' => gmdate('c', $timestamp),
				'checked_at'   => current_time('mysql', true),
			),
			false
		);
		return;
	}

	if (wp_schedule_single_event($timestamp, $hook)) {
		update_option(
			'bbb_pwa_monthly_theme_notification_schedule',
			array(
				'status'       => 'scheduled',
				'scheduled_at' => gmdate('c', $timestamp),
				'checked_at'   => current_time('mysql', true),
			),
			false
		);
	}
}

function bbb_pwa_der_signature_to_raw(string $signature): string {
	$offset = 0;
	if (ord($signature[$offset] ?? "\0") !== 0x30) {
		return '';
	}
	$offset += 2;
	if (ord($signature[$offset] ?? "\0") !== 0x02) {
		return '';
	}
	$r_length = ord($signature[$offset + 1] ?? "\0");
	$offset  += 2;
	$r        = substr($signature, $offset, $r_length);
	$offset  += $r_length;
	if (ord($signature[$offset] ?? "\0") !== 0x02) {
		return '';
	}
	$s_length = ord($signature[$offset + 1] ?? "\0");
	$offset  += 2;
	$s        = substr($signature, $offset, $s_length);

	$r = substr(str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT), -32);
	$s = substr(str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT), -32);

	return $r . $s;
}

function bbb_pwa_vapid_jwt(string $endpoint): string {
	$private_pem = bbb_pwa_vapid_private_pem();
	$public_key  = bbb_pwa_vapid_public_key();
	$parts       = wp_parse_url($endpoint);

	if ('' === $private_pem || '' === $public_key || empty($parts['scheme']) || empty($parts['host'])) {
		return '';
	}

	$audience = $parts['scheme'] . '://' . $parts['host'];
	$header   = bbb_pwa_base64url_encode((string) wp_json_encode(array('typ' => 'JWT', 'alg' => 'ES256')));
	$claims   = bbb_pwa_base64url_encode(
		(string) wp_json_encode(
			array(
				'aud' => $audience,
				'exp' => time() + 12 * HOUR_IN_SECONDS,
				'sub' => 'mailto:bybookishbabe@gmail.com',
			)
		)
	);
	$input    = $header . '.' . $claims;
	$signed   = openssl_sign($input, $signature, $private_pem, OPENSSL_ALGO_SHA256);
	$raw_sig  = $signed ? bbb_pwa_der_signature_to_raw((string) $signature) : '';

	return '' !== $raw_sig ? $input . '.' . bbb_pwa_base64url_encode($raw_sig) : '';
}

function bbb_pwa_raw_public_key_to_pem(string $raw_key): string {
	$der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw_key;

	return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode((string) $der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}

function bbb_pwa_hkdf_extract(string $salt, string $ikm): string {
	return hash_hmac('sha256', $ikm, $salt, true);
}

function bbb_pwa_hkdf_expand(string $prk, string $info, int $length): string {
	$output = '';
	$block  = '';
	$index  = 1;

	while (strlen($output) < $length) {
		$block   = hash_hmac('sha256', $block . $info . chr($index), $prk, true);
		$output .= $block;
		$index  += 1;
	}

	return substr($output, 0, $length);
}

function bbb_pwa_encrypt_push_payload(array $subscription, array $payload): string {
	$user_public = bbb_pwa_base64url_decode((string) ($subscription['keys']['p256dh'] ?? ''));
	$auth_secret = bbb_pwa_base64url_decode((string) ($subscription['keys']['auth'] ?? ''));

	if (65 !== strlen($user_public) || '' === $auth_secret) {
		return '';
	}

	$local_key = openssl_pkey_new(
		array(
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name'       => 'prime256v1',
		)
	);
	if (false === $local_key || !openssl_pkey_export($local_key, $local_private_pem)) {
		return '';
	}

	$details       = openssl_pkey_get_details($local_key);
	$server_public = isset($details['ec']['x'], $details['ec']['y']) ? "\x04" . (string) $details['ec']['x'] . (string) $details['ec']['y'] : '';
	$peer_public   = openssl_pkey_get_public(bbb_pwa_raw_public_key_to_pem($user_public));
	$shared_secret = $peer_public ? openssl_pkey_derive($peer_public, $local_private_pem, 32) : false;

	if (65 !== strlen($server_public) || false === $shared_secret) {
		return '';
	}

	$salt      = random_bytes(16);
	$key_info  = 'WebPush: info' . "\0" . $user_public . $server_public;
	$prk_key   = bbb_pwa_hkdf_extract($auth_secret, (string) $shared_secret);
	$ikm       = bbb_pwa_hkdf_expand($prk_key, $key_info, 32);
	$prk       = bbb_pwa_hkdf_extract($salt, $ikm);
	$cek       = bbb_pwa_hkdf_expand($prk, 'Content-Encoding: aes128gcm' . "\0", 16);
	$nonce     = bbb_pwa_hkdf_expand($prk, 'Content-Encoding: nonce' . "\0", 12);
	$plaintext = (string) wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\x02";
	$tag       = '';
	$encrypted = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

	if (false === $encrypted) {
		return '';
	}

	return $salt . pack('N', 4096) . chr(strlen($server_public)) . $server_public . $encrypted . $tag;
}

function bbb_pwa_send_push_to_subscription(array $subscription, array $payload): array {
	$endpoint = esc_url_raw((string) ($subscription['endpoint'] ?? ''));
	$jwt      = '' !== $endpoint ? bbb_pwa_vapid_jwt($endpoint) : '';
	$body     = '' !== $jwt ? bbb_pwa_encrypt_push_payload($subscription, $payload) : '';

	if ('' === $endpoint || '' === $jwt || '' === $body) {
		return array('ok' => false, 'status' => 0, 'message' => 'push-payload-build-failed');
	}

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization'    => 'vapid t=' . $jwt . ', k=' . bbb_pwa_vapid_public_key(),
				'TTL'              => '86400',
				'Urgency'          => 'normal',
				'Content-Encoding' => 'aes128gcm',
				'Content-Type'     => 'application/octet-stream',
			),
			'body'    => $body,
		)
	);

	if (is_wp_error($response)) {
		return array('ok' => false, 'status' => 0, 'message' => $response->get_error_message());
	}

	$status = (int) wp_remote_retrieve_response_code($response);

	return array(
		'ok'      => $status >= 200 && $status < 300,
		'status'  => $status,
		'message' => wp_remote_retrieve_response_message($response),
	);
}

function bbb_pwa_send_monthly_theme_notification(): void {
	$payload = bbb_pwa_monthly_theme_notification_payload();
	$records = get_option('bbb_pwa_push_subscriptions', array());
	$records = is_array($records) ? $records : array();
	$current_public_key = bbb_pwa_vapid_public_key();
	$sent = 0;
	$failed = 0;
	$stale = 0;
	$results = array();

	foreach ($records as $key => $subscription) {
		if (!is_array($subscription)) {
			continue;
		}

		if (($subscription['vapid_public_key'] ?? '') !== $current_public_key) {
			$stale += 1;
			continue;
		}

		$result = bbb_pwa_send_push_to_subscription($subscription, $payload);
		if (!empty($result['ok'])) {
			$sent += 1;
		} else {
			$failed += 1;
		}

		$results[(string) $key] = $result;
	}

	update_option(
		'bbb_pwa_monthly_theme_notification_last_run',
		array(
			'status'  => $sent > 0 && 0 === $failed ? 'sent' : 'completed-with-errors',
			'sent'    => $sent,
			'failed'  => $failed,
			'stale'   => $stale,
			'total'   => count($records),
			'payload' => $payload,
			'results' => $results,
			'ran_at'  => current_time('mysql', true),
		),
		false
	);
}

function bbb_pwa_manifest(): array {
	$start_url = add_query_arg(
		array(
			'source'          => 'pwa-bybookishbabe',
			'bbb_pwa_launch' => bbb_pwa_version(),
		),
		home_url('/bybookishbabe-app/')
	);

	return array(
		'name'             => 'bybookishbabe',
		'short_name'       => 'bybookishbabe',
		'description'      => get_bloginfo('description') ?: 'Romance book recs, reader goodies, and Smut Sentiment Society updates.',
		'id'               => home_url('/bybookishbabe-app/?app=bybookishbabe'),
		'start_url'        => $start_url,
		'scope'            => home_url('/'),
		'display'          => 'fullscreen',
		'display_override' => array('fullscreen', 'standalone', 'browser'),
		'orientation'      => 'portrait',
		'background_color' => '#fff7fa',
		'theme_color'      => bbb_pwa_theme_color(),
		'categories'       => array('books', 'lifestyle', 'shopping'),
		'icons'            => array(
			array(
				'src'     => bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-192.png'),
				'sizes'   => '192x192',
				'type'    => 'image/png',
				'purpose' => 'any maskable',
			),
			array(
				'src'     => bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-512.png'),
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'any maskable',
			),
		),
	);
}

function bbb_pwa_send_json(array $data, string $content_type): void {
	status_header(200);
	header_remove('Pragma');
	header_remove('Expires');
	header('Cache-Control: public, max-age=300, stale-while-revalidate=86400');
	header('Content-Type: ' . $content_type . '; charset=' . get_option('blog_charset'));
	echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

function bbb_pwa_request_path_is(string $path): bool {
	$request_uri  = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
	$request_path = (string) parse_url($request_uri, PHP_URL_PATH);

	return untrailingslashit($request_path) === '/' . ltrim($path, '/');
}

function bbb_pwa_is_sw_request(): bool {
	$request_uri  = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
	$request_path = (string) parse_url($request_uri, PHP_URL_PATH);

	return bbb_pwa_request_path_is('sw.js') || 1 === preg_match('/^\/sw-[A-Za-z0-9_-]+\.js$/', $request_path);
}

function bbb_pwa_is_install_request(): bool {
	if (isset($_GET['install']) && '1' === (string) wp_unslash($_GET['install'])) {
		return true;
	}

	$request_uri   = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
	$request_query = (string) parse_url($request_uri, PHP_URL_QUERY);
	parse_str($request_query, $query_args);

	return isset($query_args['install']) && '1' === (string) $query_args['install'];
}

function bbb_pwa_render_install_page(): void {
	$device         = isset($_GET['device']) ? sanitize_key((string) wp_unslash($_GET['device'])) : '';
	$is_ipad        = 'ipad' === $device;
	$is_android     = 'android' === $device;
	$primary_copy   = 'Tap Share, then Add to Home Screen.';
	$secondary_copy = 'After you open it from your Home Screen, bybookishbabe can ask if you want bookish alerts.';

	if ($is_ipad) {
		$primary_copy   = 'On iPad, tap the square Share icon in Safari\'s top bar, then tap Add to Home Screen.';
		$secondary_copy = 'Apple does not allow websites to open that iPad install sheet directly, but this saves bybookishbabe like an app.';
	} elseif ($is_android) {
		$primary_copy   = 'Tap the three-dot menu, then tap Add to Home screen or Install app.';
		$secondary_copy = 'If Chrome shows an install pop-up instead, tap Install and open bybookishbabe from your Home Screen.';
	}

	status_header(200);
	nocache_headers();
	header('Content-Type: text/html; charset=' . get_option('blog_charset'));
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Install bybookishbabe</title>
	<link rel="manifest" href="<?php echo esc_url(add_query_arg('v', bbb_pwa_version(), home_url('/bybookishbabe.webmanifest'))); ?>">
	<meta name="theme-color" content="<?php echo esc_attr(bbb_pwa_theme_color()); ?>">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="bybookishbabe">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<link rel="apple-touch-icon-precomposed" sizes="180x180" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<style>
		body{align-items:center;background:#fff7fa;color:#171417;display:grid;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:0;min-height:100vh;padding:24px;text-align:center}
		img{border-radius:24px;display:block;height:132px;margin:0 auto 24px;width:132px}
		h1{font-size:28px;margin:0 0 12px}
		p{color:#6d5965;font-size:17px;line-height:1.45;margin:0 auto;max-width:320px}
		.bbb-pwa-install-note{margin-top:14px}
		.bbb-pwa-install-emphasis{color:#2f7df6;font-weight:700}
	</style>
</head>
<body>
	<main>
		<img src="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>" alt="bybookishbabe">
		<h1>bybookishbabe</h1>
		<p><?php echo wp_kses_post(str_replace(array('Share', 'Add to Home Screen'), array('<span class="bbb-pwa-install-emphasis">Share</span>', '<span class="bbb-pwa-install-emphasis">Add to Home Screen</span>'), esc_html($primary_copy))); ?></p>
		<p class="bbb-pwa-install-note"><?php echo esc_html($secondary_copy); ?></p>
	</main>
</body>
</html>
	<?php
	exit;
}

add_action(
	'init',
	static function (): void {
		if (bbb_pwa_request_path_is('bybookishbabe-app') && bbb_pwa_is_install_request()) {
			bbb_pwa_render_install_page();
		}

			add_rewrite_rule('^manifest\.webmanifest$', 'index.php?bbb_pwa_manifest=1', 'top');
		add_rewrite_rule('^sw\.js$', 'index.php?bbb_pwa_sw=1', 'top');
		add_rewrite_rule('^sw-[A-Za-z0-9_-]+\.js$', 'index.php?bbb_pwa_sw=1', 'top');
	}
);

add_action('init', 'bbb_pwa_schedule_monthly_theme_notification');
add_action('bbb_pwa_send_monthly_theme_notification', 'bbb_pwa_send_monthly_theme_notification');

add_action('after_switch_theme', 'flush_rewrite_rules');

add_filter(
	'query_vars',
	static function (array $vars): array {
		$vars[] = 'bbb_pwa_manifest';
		$vars[] = 'bbb_pwa_sw';

		return $vars;
	}
);

add_action(
	'template_redirect',
	static function (): void {
		if (!is_front_page() || empty($_GET['source'])) {
			return;
		}

		$source = sanitize_text_field((string) wp_unslash($_GET['source']));
		if ('pwa-bybookishbabe' !== $source) {
			return;
		}

		wp_safe_redirect(remove_query_arg('source'), 302);
		exit;
	},
	0
);

add_action(
	'template_redirect',
	static function (): void {
		if (bbb_pwa_request_path_is('bybookishbabe-app')) {
			$is_install_request = bbb_pwa_is_install_request();

			if (!$is_install_request) {
				return;
			}

			$device         = isset($_GET['device']) ? sanitize_key((string) wp_unslash($_GET['device'])) : '';
			$is_ipad        = 'ipad' === $device;
			$is_android     = 'android' === $device;
			$primary_copy   = 'Tap Share, then Add to Home Screen.';
			$secondary_copy = 'After you open it from your Home Screen, bybookishbabe can ask if you want bookish alerts.';

			if ($is_ipad) {
				$primary_copy   = 'On iPad, tap the square Share icon in Safari\'s top bar, then tap Add to Home Screen.';
				$secondary_copy = 'Apple does not allow websites to open that iPad install sheet directly, but this saves bybookishbabe like an app.';
			} elseif ($is_android) {
				$primary_copy   = 'Tap the three-dot menu, then tap Add to Home screen or Install app.';
				$secondary_copy = 'If Chrome shows an install pop-up instead, tap Install and open bybookishbabe from your Home Screen.';
			}
			status_header(200);
			nocache_headers();
			header('Content-Type: text/html; charset=' . get_option('blog_charset'));
			?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Install bybookishbabe</title>
	<link rel="manifest" href="<?php echo esc_url(add_query_arg('v', bbb_pwa_version(), home_url('/bybookishbabe.webmanifest'))); ?>">
	<meta name="theme-color" content="<?php echo esc_attr(bbb_pwa_theme_color()); ?>">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="bybookishbabe">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<link rel="apple-touch-icon-precomposed" sizes="180x180" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
	<style>
		body {
			align-items: center;
			background: #fff7fa;
			color: #171417;
			display: grid;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
			margin: 0;
			min-height: 100vh;
			padding: 24px;
			text-align: center;
		}

		img {
			border-radius: 24px;
			display: block;
			height: 132px;
			margin: 0 auto 24px;
			width: 132px;
		}

		h1 {
			font-size: 28px;
			margin: 0 0 12px;
		}

		p {
			color: #6d5965;
			font-size: 17px;
			line-height: 1.45;
			margin: 0 auto;
			max-width: 320px;
		}

		button {
			appearance: none;
			background: #171417;
			border: 0;
			border-radius: 999px;
			color: #fff;
			cursor: pointer;
			font: inherit;
			font-size: 15px;
			font-weight: 600;
			margin-top: 24px;
			padding: 13px 20px;
		}

		.bbb-pwa-install-note {
			margin-top: 14px;
		}

		#bbb-pwa-preview-status {
			font-size: 14px;
			margin-top: 14px;
		}

		.bbb-pwa-install-emphasis {
			color: #2f7df6;
			font-weight: 700;
		}
	</style>
</head>
<body>
	<main>
		<img src="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>" alt="bybookishbabe">
		<h1>bybookishbabe</h1>
		<p><?php echo wp_kses_post(str_replace(array('Share', 'Add to Home Screen'), array('<span class="bbb-pwa-install-emphasis">Share</span>', '<span class="bbb-pwa-install-emphasis">Add to Home Screen</span>'), esc_html($primary_copy))); ?></p>
		<p class="bbb-pwa-install-note"><?php echo esc_html($secondary_copy); ?></p>
	</main>
</body>
</html>
			<?php
			exit;
		}

		if (get_query_var('bbb_pwa_manifest') || bbb_pwa_request_path_is('manifest.webmanifest') || bbb_pwa_request_path_is('bybookishbabe.webmanifest')) {
			bbb_pwa_send_json(bbb_pwa_manifest(), 'application/manifest+json');
		}

		if (!get_query_var('bbb_pwa_sw') && !bbb_pwa_is_sw_request()) {
			return;
		}

		status_header(200);
		header_remove('Pragma');
		header_remove('Expires');
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Surrogate-Control: no-store');
		header('Content-Type: application/javascript; charset=' . get_option('blog_charset'));
		header('Service-Worker-Allowed: /');
		$theme_version = wp_get_theme()->get('Version') ?: '1.0.0';
		$payload       = array(
			'cacheName'   => 'bbb-pwa-' . sanitize_key((string) $theme_version) . '-' . bbb_pwa_version(),
			'homeUrl'     => home_url('/bybookishbabe-app/?source=pwa-bybookishbabe'),
			'homePath'    => wp_parse_url(home_url('/bybookishbabe-app/'), PHP_URL_PATH) ?: '/bybookishbabe-app/',
			'offlineUrl'  => home_url('/?source=pwa-bybookishbabe-offline'),
				'precacheUrls' => array(
					home_url('/bybookishbabe-app/?source=pwa-bybookishbabe'),
					home_url('/?source=pwa-bybookishbabe-offline'),
					home_url('/shop/'),
					home_url('/books/'),
					home_url('/reader-quizzes/'),
					bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-192.png'),
					bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png'),
					bbb_pwa_asset_uri('assets/js/bbb-pwa.js'),
					bbb_pwa_asset_uri('assets/css/pwa-promos.css'),
					bbb_pwa_asset_uri('assets/css/shop-page.css'),
					bbb_pwa_asset_uri('assets/css/shop-drop-popup.css'),
					bbb_pwa_asset_uri('assets/js/shop-edd-cart.js'),
					bbb_pwa_asset_uri('assets/js/shop-filters.js'),
					bbb_pwa_asset_uri('assets/js/shop-drop-popup.js'),
					bbb_pwa_asset_uri('assets/css/book-breakdown-page.css'),
					bbb_pwa_asset_uri('assets/css/society-content-cta.css'),
					bbb_pwa_asset_uri('assets/css/fictional-boyfriends.css'),
					bbb_pwa_asset_uri('assets/js/book-page-rating.js'),
				),
			'themeName'   => get_bloginfo('name') ?: 'By Bookish Babe',
			'defaultIcon' => bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-192.png'),
		);
		?>
const BBB_PWA = <?php echo wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

function bbbPwaShouldCachePage(request) {
	const url = new URL(request.url);

	if (url.origin !== self.location.origin) {
		return false;
	}

	if (request.method !== 'GET') {
		return false;
	}

	return !bbbPwaIsSensitiveRoute(url);
}

function bbbPwaIsDocumentRequest(request) {
	const accept = request.headers.get('Accept') || '';

	return request.mode === 'navigate'
		|| request.destination === 'document'
		|| accept.indexOf('text/html') !== -1;
}

function bbbPwaIsSensitiveRoute(url) {
	return url.pathname.startsWith('/wp-admin')
		|| url.pathname.startsWith('/wp-login')
		|| url.pathname.startsWith('/wp-json')
		|| url.pathname.startsWith('/monthly-theme')
		|| url.pathname.startsWith('/monthly-bybookishbabe-romance-theme')
		|| url.pathname.startsWith('/social-planner')
		|| /^\/(account|cart|checkout|my-bookshelf|my-notes|my-vault)(\/|$)/.test(url.pathname);
}

function bbbPwaIsShellDocument(url) {
	const offlinePath = new URL(BBB_PWA.offlineUrl).pathname;

	return url.pathname === BBB_PWA.homePath || url.pathname === offlinePath;
}

function bbbPwaIsNetworkOnlyAsset(url) {
	return url.pathname.indexOf('/assets/js/social-posting-calendar.js') !== -1
		|| url.pathname.indexOf('/assets/css/social-posting-calendar.css') !== -1
		|| url.pathname.indexOf('/assets/pwa/social-planner.webmanifest') !== -1;
}

function bbbPwaCacheKey(request) {
	const url = new URL(request.url);
	const dropParams = ['source', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid'];

	dropParams.forEach((param) => url.searchParams.delete(param));

	return new Request(url.href, {
		credentials: 'same-origin',
	});
}

function bbbPwaCanCacheResponse(response, allowNoStore) {
	if (!response || response.status !== 200 || response.type === 'opaque') {
		return false;
	}

	const cacheControl = response.headers.get('Cache-Control') || '';
	return allowNoStore || !/no-store/i.test(cacheControl);
}

function bbbPwaNetworkThenCache(request, preloadResponse, cacheKey, allowNoStore) {
	const network = preloadResponse ? preloadResponse.then((response) => response || fetch(request)) : fetch(request);
	const targetKey = cacheKey || bbbPwaCacheKey(request);

	return network.then((response) => {
		if (bbbPwaCanCacheResponse(response, allowNoStore)) {
			const copy = response.clone();
			caches.open(BBB_PWA.cacheName).then((cache) => cache.put(targetKey, copy));
		}

		return response;
	});
}

function bbbPwaNetworkThenCacheOrFallback(request, preloadResponse) {
	return bbbPwaNetworkThenCache(request, preloadResponse).catch(() => caches.match(request).then((cached) => cached || caches.match(bbbPwaCacheKey(request))));
}

function bbbPwaCacheFirstThenRefresh(request) {
	const cacheKey = bbbPwaCacheKey(request);

	return caches.match(cacheKey)
		.then((cached) => {
			const refresh = bbbPwaNetworkThenCache(request, null, cacheKey).catch(() => null);
			return cached || refresh;
		});
}

function bbbPwaDocumentStaleWhileRevalidate(request, preloadResponse) {
	const cacheKey = bbbPwaCacheKey(request);
	const offlineKey = bbbPwaCacheKey(new Request(BBB_PWA.offlineUrl, { credentials: 'same-origin' }));

	return caches.match(cacheKey)
		.then((cached) => {
			const refresh = bbbPwaNetworkThenCache(request, preloadResponse, cacheKey, true).catch(() => null);
			return cached || refresh.then((response) => response || caches.match(offlineKey));
		});
}

function bbbPwaDocumentNetworkFirst(request, preloadResponse) {
	const cacheKey = bbbPwaCacheKey(request);
	const offlineKey = bbbPwaCacheKey(new Request(BBB_PWA.offlineUrl, { credentials: 'same-origin' }));

	return bbbPwaNetworkThenCache(request, preloadResponse, cacheKey, true)
		.catch(() => caches.match(cacheKey).then((cached) => cached || caches.match(offlineKey)));
}

function bbbPwaNetworkOnly(request, preloadResponse) {
	const network = preloadResponse ? preloadResponse.then((response) => response || fetch(request)) : fetch(request);

	return network.catch(() => caches.match(bbbPwaCacheKey(new Request(BBB_PWA.offlineUrl, { credentials: 'same-origin' }))));
}

function bbbPwaNotifyClientsOfUpdate() {
	return self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
		clientList.forEach((client) => {
			client.postMessage({
				type: 'BBB_PWA_UPDATED',
				cacheName: BBB_PWA.cacheName,
			});
		});
	});
}

self.addEventListener('install', (event) => {
	const precacheUrls = [
		BBB_PWA.homeUrl,
		BBB_PWA.offlineUrl,
		BBB_PWA.defaultIcon,
	].filter(Boolean);

	event.waitUntil(
		caches.open(BBB_PWA.cacheName)
			.then((cache) => Promise.all(precacheUrls.map((url) => {
				const request = new Request(url, { credentials: 'same-origin' });
				return fetch(request).then((response) => {
					if (bbbPwaCanCacheResponse(response, bbbPwaIsDocumentRequest(request))) {
						return cache.put(bbbPwaCacheKey(request), response);
					}
					return undefined;
				}).catch(() => undefined);
			})))
			.then(() => self.skipWaiting())
	);
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys()
			.then((keys) => Promise.all(keys.filter((key) => key.startsWith('bbb-pwa-') && key !== BBB_PWA.cacheName).map((key) => caches.delete(key))))
			.then(() => self.registration.navigationPreload ? self.registration.navigationPreload.enable() : undefined)
			.then(() => self.clients.claim())
			.then(() => bbbPwaNotifyClientsOfUpdate())
	);
});

self.addEventListener('message', (event) => {
	if (event.data && event.data.type === 'SKIP_WAITING') {
		self.skipWaiting();
	}
});

self.addEventListener('fetch', (event) => {
	const request = event.request;
	const url = new URL(request.url);

	if (request.method !== 'GET') {
		return;
	}

	if (bbbPwaIsSensitiveRoute(url) || bbbPwaIsNetworkOnlyAsset(url)) {
		event.respondWith(bbbPwaNetworkOnly(request, event.preloadResponse));
		return;
	}

	if (bbbPwaIsDocumentRequest(request)) {
		if (!bbbPwaShouldCachePage(request)) {
			event.respondWith(bbbPwaNetworkOnly(request, event.preloadResponse));
			return;
		}

		event.respondWith(bbbPwaIsShellDocument(url) ? bbbPwaDocumentNetworkFirst(request, event.preloadResponse) : bbbPwaNetworkOnly(request, event.preloadResponse));
		return;
	}

	if (request.destination === 'style' || request.destination === 'script') {
		event.respondWith(bbbPwaCacheFirstThenRefresh(request));
		return;
	}

	if (request.destination === 'image') {
		event.respondWith(bbbPwaCacheFirstThenRefresh(request));
		return;
	}

	event.respondWith(
		caches.match(request).then((cached) => cached || bbbPwaNetworkThenCacheOrFallback(request))
	);
});

self.addEventListener('push', (event) => {
	let data = {};

	try {
		data = event.data ? event.data.json() : {};
	} catch (error) {
		data = { title: BBB_PWA.themeName, body: event.data ? event.data.text() : '' };
	}

	const title = data.title || BBB_PWA.themeName;
	const options = {
		body: data.body || 'New from By Bookish Babe',
		icon: data.icon || BBB_PWA.defaultIcon,
		badge: data.badge || BBB_PWA.defaultIcon,
		data: { url: data.url || BBB_PWA.homeUrl },
	};

	event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
	event.notification.close();
	const targetUrl = event.notification.data && event.notification.data.url ? event.notification.data.url : BBB_PWA.homeUrl;

	event.waitUntil(
		clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
			for (const client of clientList) {
				if ('focus' in client && client.url === targetUrl) {
					return client.focus();
				}
			}

			return clients.openWindow(targetUrl);
		})
	);
});
		<?php
		exit;
	},
	5
);

add_action(
	'wp_head',
	static function (): void {
		$theme_color = bbb_pwa_theme_color();
		?>
<link rel="manifest" href="<?php echo esc_url(add_query_arg('v', bbb_pwa_version(), home_url('/bybookishbabe.webmanifest'))); ?>">
<meta name="theme-color" content="<?php echo esc_attr($theme_color); ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="bybookishbabe">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
<link rel="apple-touch-icon-precomposed" sizes="180x180" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-apple-touch-icon.png')); ?>">
<link rel="apple-touch-icon" sizes="192x192" href="<?php echo esc_url(bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-192.png')); ?>">
		<?php
	},
	1
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		bbb_enqueue_css('bbb-pwa-promos', 'assets/css/pwa-promos.css', array('bbb-base'));
		bbb_enqueue_js('bbb-pwa', 'assets/js/bbb-pwa.js', array(), true);
		wp_localize_script(
			'bbb-pwa',
			'bbbPwaSettings',
			array(
				'serviceWorkerUrl' => add_query_arg('v', bbb_pwa_version(), home_url('/sw.js')),
				'version'          => bbb_pwa_version(),
				'appHomePath'      => wp_parse_url(home_url('/bybookishbabe-app/'), PHP_URL_PATH) ?: '/bybookishbabe-app/',
				'defaultIcon'      => bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-192.png'),
				'vapidPublicKey'   => bbb_pwa_vapid_public_key(),
				'subscribeUrl'     => esc_url_raw(rest_url('bbb/v1/push-subscriptions')),
				'monthlyThemeNotificationPreview' => current_user_can('manage_options') ? bbb_pwa_monthly_theme_notification_payload() : null,
				'nonce'            => wp_create_nonce('wp_rest'),
				'supabaseUrl'      => defined('SUPABASE_URL') ? SUPABASE_URL : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
				'supabaseKey'      => defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk',
				'supabaseScriptUrl' => 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2',
				'siteEventsTable'  => 'site_events',
			)
		);
	}
);

function bbb_render_pwa_promo(string $placement): void {
	$install_url = bbb_pwa_install_url();

	if ('header' === $placement) {
		?>
		<div class="bbb-pwa-promo bbb-pwa-promo--header bbb-pwa-browser-only" data-bbb-pwa-sticky>
			<div class="bbb-pwa-promo__text">
				<strong>save bybookishbabe to your phone</strong>
				<span>one tap back to the recs, quizzes, and reader tools</span>
			</div>
			<a class="bbb-pwa-promo__button" href="<?php echo esc_url($install_url); ?>" data-bbb-pwa-install-link>show me</a>
			<button class="bbb-pwa-promo__close" type="button" data-bbb-pwa-dismiss aria-label="hide app prompt">×</button>
		</div>
		<?php
		return;
	}

	if ('sticky' === $placement) {
		?>
		<div class="bbb-pwa-promo bbb-pwa-promo--sticky bbb-pwa-browser-only" data-bbb-pwa-sticky>
			<div class="bbb-pwa-promo__icon" aria-hidden="true">📲</div>
			<div class="bbb-pwa-promo__text">
				<strong>save bybookishbabe to your phone</strong>
				<span>one tap back to the recs, quizzes, and reader tools</span>
			</div>
			<a class="bbb-pwa-promo__button" href="<?php echo esc_url($install_url); ?>" data-bbb-pwa-install-link>show me</a>
			<button class="bbb-pwa-promo__close" type="button" data-bbb-pwa-dismiss aria-label="hide app prompt">×</button>
		</div>
		<?php
		return;
	}

	$config = array(
		'society' => array(
			'class' => 'bbb-pwa-promo--society',
			'icon'  => '📲',
			'title' => 'your bookshelf deserves a shortcut',
			'copy'  => 'add bybookishbabe to your home screen so the member tools are right there.',
			'cta'   => 'add the shortcut',
		),
		'footer'  => array(
			'class' => 'bbb-pwa-promo--footer',
			'icon'  => '📲',
			'title' => 'save bybookishbabe to your phone',
			'copy'  => 'no app store. just your bookish corner, one tap away.',
			'cta'   => 'how to',
		),
	);

	if (empty($config[$placement])) {
		return;
	}

	$promo = $config[$placement];
	?>
	<div class="bbb-pwa-promo <?php echo esc_attr($promo['class']); ?> bbb-pwa-browser-only">
		<div class="bbb-pwa-promo__icon" aria-hidden="true"><?php echo esc_html($promo['icon']); ?></div>
		<div class="bbb-pwa-promo__text">
			<strong><?php echo esc_html($promo['title']); ?></strong>
			<span><?php echo esc_html($promo['copy']); ?></span>
		</div>
		<a class="bbb-pwa-promo__button" href="<?php echo esc_url($install_url); ?>" data-bbb-pwa-install-link><?php echo esc_html($promo['cta']); ?></a>
	</div>
	<?php
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'bbb/v1',
			'/push-subscriptions',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'bbb_pwa_save_push_subscription',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_shortcode(
	'bbb_pwa_notifications_button',
	static function (): string {
		if (is_admin()) {
			return '';
		}

		return '<button type="button" class="button bbb-pwa-notifications-button" data-bbb-pwa-notifications>Enable notifications</button>';
	}
);

function bbb_pwa_save_push_subscription(WP_REST_Request $request): WP_REST_Response {
	$subscription = $request->get_json_params();

	if (!is_array($subscription) || empty($subscription['endpoint'])) {
		return new WP_REST_Response(array('ok' => false, 'message' => 'Missing push endpoint.'), 400);
	}

	$endpoint = esc_url_raw((string) $subscription['endpoint']);
	$records  = get_option('bbb_pwa_push_subscriptions', array());
	$records  = is_array($records) ? $records : array();
	$key      = hash('sha256', $endpoint);

	$records[$key] = array(
		'endpoint'         => $endpoint,
		'keys'             => isset($subscription['keys']) && is_array($subscription['keys']) ? array_map('sanitize_text_field', $subscription['keys']) : array(),
		'vapid_public_key' => bbb_pwa_vapid_public_key(),
		'user_id'          => get_current_user_id(),
		'user_agent'       => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
		'updated_at'       => current_time('mysql', true),
	);

	update_option('bbb_pwa_push_subscriptions', $records, false);

	return new WP_REST_Response(array('ok' => true), 201);
}
