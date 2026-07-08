const BBB_PWA_VERSION = '20260706191200';

self.addEventListener('install', (event) => {
	event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys()
			.then((keys) => Promise.all(keys.filter((key) => key.startsWith('bbb-pwa-')).map((key) => caches.delete(key))))
			.then(() => self.registration.navigationPreload ? self.registration.navigationPreload.disable() : undefined)
			.then(() => self.clients.claim())
			.then(() => self.clients.matchAll({ type: 'window', includeUncontrolled: true }))
			.then((clients) => {
				clients.forEach((client) => {
					client.postMessage({
						type: 'BBB_PWA_UPDATED',
						version: BBB_PWA_VERSION,
					});
				});
			})
	);
});

self.addEventListener('message', (event) => {
	if (event.data && event.data.type === 'SKIP_WAITING') {
		self.skipWaiting();
	}
});
