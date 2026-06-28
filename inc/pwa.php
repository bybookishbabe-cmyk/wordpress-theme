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
		header('Cache-Control: public, max-age=0, must-revalidate');
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
					home_url('/library/'),
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
		|| /^\/(account|cart|checkout|my-bookshelf|my-notes|my-vault)(\/|$)/.test(url.pathname);
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
	const precacheUrls = BBB_PWA.precacheUrls || [BBB_PWA.offlineUrl];

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

	if (bbbPwaIsDocumentRequest(request)) {
		if (!bbbPwaShouldCachePage(request)) {
			event.respondWith(bbbPwaNetworkOnly(request, event.preloadResponse));
			return;
		}

		event.respondWith(bbbPwaDocumentStaleWhileRevalidate(request, event.preloadResponse));
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
	'wp_head',
	static function (): void {
		if (!is_front_page()) {
			return;
		}
		?>
<link rel="prefetch" href="<?php echo esc_url(home_url('/library/')); ?>" as="document">
		<?php
	},
	8
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
				'vapidPublicKey'   => bbb_pwa_vapid_public_key(),
				'subscribeUrl'     => esc_url_raw(rest_url('bbb/v1/push-subscriptions')),
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
		'endpoint'   => $endpoint,
		'keys'       => isset($subscription['keys']) && is_array($subscription['keys']) ? array_map('sanitize_text_field', $subscription['keys']) : array(),
		'user_id'    => get_current_user_id(),
		'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
		'updated_at' => current_time('mysql', true),
	);

	update_option('bbb_pwa_push_subscriptions', $records, false);

	return new WP_REST_Response(array('ok' => true), 201);
}
