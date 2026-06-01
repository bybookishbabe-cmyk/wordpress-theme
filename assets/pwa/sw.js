const BBB_PWA = {
	cacheName: 'bbb-pwa-bybookishbabe-20260531-2',
	homeUrl: 'https://bybookishbabe.com/',
	offlineUrl: 'https://bybookishbabe.com/?source=pwa-bybookishbabe-offline',
	themeName: 'bybookishbabe',
	defaultIcon: 'https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-icon-192.png?v=bybookishbabe-20260531-2',
};

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
