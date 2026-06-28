const BBB_PWA = {
	cacheName: 'bbb-pwa-bybookishbabe-20260627204820',
	homeUrl: 'https://bybookishbabe.com/bybookishbabe-app/?source=pwa-bybookishbabe',
	homePath: '/bybookishbabe-app/',
	offlineUrl: 'https://bybookishbabe.com/?source=pwa-bybookishbabe-offline',
	precacheUrls: [
		'https://bybookishbabe.com/bybookishbabe-app/?source=pwa-bybookishbabe',
		'https://bybookishbabe.com/?source=pwa-bybookishbabe-offline',
		'https://bybookishbabe.com/shop/',
		'https://bybookishbabe.com/books/',
		'https://bybookishbabe.com/library/',
		'https://bybookishbabe.com/reader-quizzes/',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-icon-192.png?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-apple-touch-icon.png?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/js/bbb-pwa.js?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/css/pwa-promos.css?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/css/shop-page.css?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/css/shop-drop-popup.css?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/js/shop-edd-cart.js?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/js/shop-filters.js?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/js/shop-drop-popup.js?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/css/book-breakdown-page.css?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/css/society-content-cta.css?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/css/fictional-boyfriends.css?v=bybookishbabe-20260627204820',
		'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/js/book-page-rating.js?v=bybookishbabe-20260627204820',
	],
	themeName: 'bybookishbabe',
	defaultIcon: 'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-icon-192.png?v=bybookishbabe-20260627204820',
};

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
