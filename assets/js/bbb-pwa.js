(function () {
	'use strict';

	var settings = window.bbbPwaSettings || {};
	var registrationPromise = null;
	var deferredInstallPrompt = null;
	var promptStorageKey = 'bbbPwaNotificationPromptedV2';
	var promoDismissedKey = 'bbbPwaPromoDismissed';

	function urlBase64ToUint8Array(base64String) {
		var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
		var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
		var rawData = window.atob(base64);
		var outputArray = new Uint8Array(rawData.length);

		for (var i = 0; i < rawData.length; i += 1) {
			outputArray[i] = rawData.charCodeAt(i);
		}

		return outputArray;
	}

	function registerServiceWorker() {
		if (!('serviceWorker' in navigator) || !settings.serviceWorkerUrl) {
			return Promise.resolve(null);
		}

		if (!registrationPromise) {
			registrationPromise = navigator.serviceWorker.register(settings.serviceWorkerUrl, { scope: '/' });
		}

		return registrationPromise;
	}

	function saveSubscription(subscription) {
		if (!settings.subscribeUrl || !subscription) {
			return Promise.resolve(subscription);
		}

		return window.fetch(settings.subscribeUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': settings.nonce || '',
			},
			body: JSON.stringify(subscription),
		}).then(function () {
			return subscription;
		});
	}

	function requestNotifications() {
		if (!('Notification' in window) || !('PushManager' in window)) {
			return Promise.resolve({ ok: false, reason: 'unsupported' });
		}

		return registerServiceWorker()
			.then(function (registration) {
				if (!registration) {
					return { ok: false, reason: 'no-service-worker' };
				}

				return window.Notification.requestPermission().then(function (permission) {
					if (permission !== 'granted') {
						return { ok: false, reason: permission };
					}

					try {
						window.localStorage.setItem(promptStorageKey, 'accepted');
					} catch (error) {}

					if (!settings.vapidPublicKey) {
						return { ok: true, reason: 'permission-granted-no-vapid-key' };
					}

					return registration.pushManager.subscribe({
						userVisibleOnly: true,
						applicationServerKey: urlBase64ToUint8Array(settings.vapidPublicKey),
					}).then(saveSubscription).then(function () {
						return { ok: true, reason: 'subscribed' };
					});
				});
			});
	}

	function createNotificationPrompt() {
		if (document.querySelector('[data-bbb-pwa-notification-panel]')) {
			return;
		}

		var panel = document.createElement('div');
		panel.setAttribute('data-bbb-pwa-notification-panel', '');
		panel.className = 'bbb-pwa-notification-panel';
		panel.innerHTML = [
			'<div class="bbb-pwa-notification-panel__inner">',
			'<p class="bbb-pwa-notification-panel__eyebrow">bybookishbabe app</p>',
			'<h2>Want bookish alerts?</h2>',
			'<p>Get a little nudge when new recs, freebies, or Society updates drop.</p>',
			'<div class="bbb-pwa-notification-panel__actions">',
			'<button type="button" class="bbb-pwa-notification-panel__primary" data-bbb-pwa-notifications>Turn on notifications</button>',
			'<button type="button" class="bbb-pwa-notification-panel__secondary" data-bbb-pwa-dismiss-notifications>Not now</button>',
			'</div>',
			'</div>',
		].join('');
		document.body.appendChild(panel);
	}

	function maybeShowInstalledNotificationPrompt() {
		if (!looksLikePwaLaunch() || !('Notification' in window) || window.Notification.permission !== 'default') {
			return;
		}

		if (!shouldForceNotificationPrompt()) {
			try {
				if (window.localStorage.getItem(promptStorageKey)) {
					return;
				}
			} catch (error) {
				return;
			}
		}

		window.setTimeout(createNotificationPrompt, 1200);
	}

	window.bbbPwa = {
		register: registerServiceWorker,
		install: requestInstall,
		requestNotifications: requestNotifications,
	};

	function isStandalone() {
		return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
	}

	function isIpad() {
		return /ipad/i.test(window.navigator.userAgent) || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
	}

	function syncAppModeClass() {
		document.documentElement.classList.toggle('bbb-is-pwa-app', isStandalone());
	}

	function shouldForceNotificationPrompt() {
		return new URLSearchParams(window.location.search).get('show-pwa-notifications') === '1';
	}

	function looksLikePwaLaunch() {
		var source = new URLSearchParams(window.location.search).get('source') || '';

		return isStandalone() || source.indexOf('pwa-bybookishbabe') === 0 || shouldForceNotificationPrompt();
	}

	function showInstallHelp(trigger) {
		var helpId = trigger ? trigger.getAttribute('aria-describedby') : '';
		var help = helpId ? document.getElementById(helpId) : null;

		if (help) {
			help.hidden = false;
		}

		if (isIpad()) {
			window.location.href = '/bybookishbabe-app/?install=1&device=ipad';
			return;
		}

		if (/iphone|ipod/i.test(window.navigator.userAgent)) {
			window.location.href = '/bybookishbabe-app/?install=1&device=iphone';
		}
	}

	function requestInstall(trigger) {
		if (isStandalone()) {
			showInstallHelp(trigger);
			return Promise.resolve({ ok: true, reason: 'already-installed' });
		}

		if (!deferredInstallPrompt) {
			showInstallHelp(trigger);
			return Promise.resolve({ ok: false, reason: 'manual-install-required' });
		}

		deferredInstallPrompt.prompt();

		return deferredInstallPrompt.userChoice.then(function (choice) {
			var accepted = choice && choice.outcome === 'accepted';
			deferredInstallPrompt = null;

			return { ok: accepted, reason: accepted ? 'installed' : 'dismissed' };
		});
	}

	window.addEventListener('beforeinstallprompt', function (event) {
		event.preventDefault();
		deferredInstallPrompt = event;
	});

	if (document.readyState === 'loading') {
		syncAppModeClass();
		document.addEventListener('DOMContentLoaded', function () {
			syncAppModeClass();
			registerServiceWorker();
			restoreDismissedPromo();
			maybeShowInstalledNotificationPrompt();
		});
	} else {
		syncAppModeClass();
		registerServiceWorker();
		restoreDismissedPromo();
		maybeShowInstalledNotificationPrompt();
	}

	function restoreDismissedPromo() {
		var sticky = document.querySelector('[data-bbb-pwa-sticky]');

		if (!sticky) {
			return;
		}

		try {
			if (window.localStorage.getItem(promoDismissedKey)) {
				sticky.classList.add('is-hidden');
			}
		} catch (error) {}
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-bbb-pwa-notifications]');

		if (!trigger) {
			return;
		}

		event.preventDefault();
		requestNotifications().then(function (result) {
			var panel = document.querySelector('[data-bbb-pwa-notification-panel]');
			if (panel && result && result.ok) {
				panel.remove();
			}
			document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', { detail: result }));
		});
	});

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-bbb-pwa-dismiss-notifications]');
		var panel = document.querySelector('[data-bbb-pwa-notification-panel]');

		if (!trigger || !panel) {
			return;
		}

		event.preventDefault();
		try {
			window.localStorage.setItem(promptStorageKey, 'dismissed');
		} catch (error) {}
		panel.remove();
	});

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-bbb-pwa-dismiss]');
		var sticky = trigger ? trigger.closest('[data-bbb-pwa-sticky]') : null;

		if (!trigger || !sticky) {
			return;
		}

		event.preventDefault();
		sticky.classList.add('is-hidden');
		try {
			window.localStorage.setItem(promoDismissedKey, '1');
		} catch (error) {}
	});

	document.addEventListener('bbb:pwa-request-notifications', function () {
		requestNotifications().then(function (result) {
			document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', { detail: result }));
		});
	});

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-bbb-pwa-install]');

		if (!trigger) {
			return;
		}

		event.preventDefault();
		requestInstall(trigger).then(function (result) {
			document.dispatchEvent(new CustomEvent('bbb:pwa-install-result', { detail: result }));
		});
	});
}());
