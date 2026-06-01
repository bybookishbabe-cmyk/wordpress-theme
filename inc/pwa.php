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
	return 'bybookishbabe-20260601-2';
}

function bbb_pwa_asset_uri(string $relative_path): string {
	return add_query_arg('v', bbb_pwa_version(), get_theme_file_uri($relative_path));
}

function bbb_pwa_install_url(): string {
	return home_url('/bybookishbabe-app/?install=1');
}

function bbb_pwa_vapid_public_key(): string {
	$key = defined('BBB_PWA_VAPID_PUBLIC_KEY')
		? (string) BBB_PWA_VAPID_PUBLIC_KEY
		: 'BPQhYTst7vQE468FOTU4Q2hVDTR5g3QJs-1EG13Z4RjVmXWvZA-wZe650NeqD8xuFR8_ikDnApZ7AaMBWo4PTLs';

	return trim((string) apply_filters('bbb_pwa_vapid_public_key', $key));
}

function bbb_pwa_manifest(): array {
	return array(
		'name'             => 'bybookishbabe',
		'short_name'       => 'bybookishbabe',
		'description'      => get_bloginfo('description') ?: 'Romance book recs, reader goodies, and Smut Sentiment Society updates.',
		'id'               => home_url('/?app=bybookishbabe'),
		'start_url'        => home_url('/bybookishbabe-app/?source=pwa-bybookishbabe'),
		'scope'            => home_url('/'),
		'display'          => 'standalone',
		'display_override' => array('window-controls-overlay', 'standalone', 'browser'),
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
	nocache_headers();
	header('Content-Type: ' . $content_type . '; charset=' . get_option('blog_charset'));
	echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

function bbb_pwa_request_path_is(string $path): bool {
	$request_uri  = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
	$request_path = (string) parse_url($request_uri, PHP_URL_PATH);

	return untrailingslashit($request_path) === '/' . ltrim($path, '/');
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

add_action(
	'init',
	static function (): void {
		add_rewrite_rule('^manifest\.webmanifest$', 'index.php?bbb_pwa_manifest=1', 'top');
		add_rewrite_rule('^sw\.js$', 'index.php?bbb_pwa_sw=1', 'top');
	}
);

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
		if (bbb_pwa_request_path_is('bybookishbabe-app')) {
			$is_install_request = bbb_pwa_is_install_request();

			if (!$is_install_request) {
				$template = get_theme_file_path('page-bybookishbabe-app.php');

				if (file_exists($template)) {
					if (function_exists('bbb_mark_virtual_route_found')) {
						bbb_mark_virtual_route_found(false);
					}

					require $template;
					exit;
				}

				return;
			}

			$device         = isset($_GET['device']) ? sanitize_key((string) wp_unslash($_GET['device'])) : '';
			$is_ipad        = 'ipad' === $device;
			$primary_copy   = $is_ipad
				? 'On iPad, tap the square Share icon in Safari\'s top bar, then tap Add to Home Screen.'
				: 'Tap Share, then Add to Home Screen.';
			$secondary_copy = $is_ipad
				? 'Apple does not allow websites to open that iPad install sheet directly, but this saves bybookishbabe like an app.'
				: 'After you open it from your Home Screen, bybookishbabe can ask if you want bookish alerts.';
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

		if (!get_query_var('bbb_pwa_sw') && !bbb_pwa_request_path_is('sw.js')) {
			return;
		}

		nocache_headers();
		status_header(200);
		header('Content-Type: application/javascript; charset=' . get_option('blog_charset'));
		header('Service-Worker-Allowed: /');
		$theme_version = wp_get_theme()->get('Version') ?: '1.0.0';
		$payload       = array(
			'cacheName'   => 'bbb-pwa-' . sanitize_key((string) $theme_version) . '-' . bbb_pwa_version(),
			'homeUrl'     => home_url('/'),
			'offlineUrl'  => home_url('/?source=pwa-bybookishbabe-offline'),
			'themeName'   => get_bloginfo('name') ?: 'By Bookish Babe',
			'defaultIcon' => bbb_pwa_asset_uri('assets/pwa/bybookishbabe-icon-192.png'),
		);
		?>
const BBB_PWA = <?php echo wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(BBB_PWA.cacheName)
			.then((cache) => cache.addAll([BBB_PWA.homeUrl, BBB_PWA.offlineUrl]))
			.then(() => self.skipWaiting())
	);
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys()
			.then((keys) => Promise.all(keys.filter((key) => key.startsWith('bbb-pwa-') && key !== BBB_PWA.cacheName).map((key) => caches.delete(key))))
			.then(() => self.clients.claim())
	);
});

self.addEventListener('fetch', (event) => {
	const request = event.request;

	if (request.method !== 'GET') {
		return;
	}

	if (request.mode === 'navigate') {
		event.respondWith(
			fetch(request)
				.then((response) => {
					const copy = response.clone();
					caches.open(BBB_PWA.cacheName).then((cache) => cache.put(request, copy));
					return response;
				})
				.catch(() => caches.match(request).then((cached) => cached || caches.match(BBB_PWA.offlineUrl)))
		);
		return;
	}

	event.respondWith(
		caches.match(request).then((cached) => cached || fetch(request).then((response) => {
			if (!response || response.status !== 200 || response.type === 'opaque') {
				return response;
			}

			const copy = response.clone();
			caches.open(BBB_PWA.cacheName).then((cache) => cache.put(request, copy));
			return response;
		}))
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
<meta name="apple-mobile-web-app-status-bar-style" content="default">
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
				'serviceWorkerUrl' => home_url('/sw.js'),
				'vapidPublicKey'   => bbb_pwa_vapid_public_key(),
				'subscribeUrl'     => esc_url_raw(rest_url('bbb/v1/push-subscriptions')),
				'nonce'            => wp_create_nonce('wp_rest'),
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
			<button class="bbb-pwa-promo__button" type="button" data-bbb-pwa-install>show me</button>
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
			<button class="bbb-pwa-promo__button" type="button" data-bbb-pwa-install>show me</button>
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
		<button class="bbb-pwa-promo__button" type="button" data-bbb-pwa-install><?php echo esc_html($promo['cta']); ?></button>
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
		'endpoint'   => $endpoint,
		'keys'       => isset($subscription['keys']) && is_array($subscription['keys']) ? array_map('sanitize_text_field', $subscription['keys']) : array(),
		'user_id'    => get_current_user_id(),
		'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
		'updated_at' => current_time('mysql', true),
	);

	update_option('bbb_pwa_push_subscriptions', $records, false);

	return new WP_REST_Response(array('ok' => true), 201);
}
