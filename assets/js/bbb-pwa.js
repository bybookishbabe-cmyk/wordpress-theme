(function () {
	'use strict';

	var settings = window.bbbPwaSettings || {};
	var registrationPromise = null;
	var deferredInstallPrompt = null;
	var promptStorageKey = 'bbbPwaNotificationPromptedV2';
	var promoDismissedKey = 'bbbPwaPromoDismissed';
	var appBackReadyKey = 'bbbPwaBackReady';
	var analyticsSessionKey = 'bbbPwaAnalyticsSessionId';
	var firstStandaloneOpenKey = 'bbbPwaFirstStandaloneOpenTracked';
	var prefetchedUrls = {};
	var observedWarmLinks = [];
	var linkWarmObserver = null;
	var supabaseClient = null;
	var supabaseScriptPromise = null;

	function closestFromTarget(target, selector) {
		return target && typeof target.closest === 'function' ? target.closest(selector) : null;
	}

	function analyticsSessionId() {
		try {
			var existing = window.localStorage.getItem(analyticsSessionKey);
			if (existing) {
				return existing;
			}

			var created = 'pwa-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
			window.localStorage.setItem(analyticsSessionKey, created);
			return created;
		} catch (error) {
			return 'pwa-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
		}
	}

	function pwaAnalyticsClient() {
		if (supabaseClient) {
			return Promise.resolve(supabaseClient);
		}

		if (!window.supabase || !settings.supabaseUrl || !settings.supabaseKey) {
			return loadSupabaseClient().then(function () {
				if (!window.supabase || !settings.supabaseUrl || !settings.supabaseKey) {
					return null;
				}

				supabaseClient = window.supabase.createClient(settings.supabaseUrl, settings.supabaseKey);
				return supabaseClient;
			});
		}

		supabaseClient = window.supabase.createClient(settings.supabaseUrl, settings.supabaseKey);
		return Promise.resolve(supabaseClient);
	}

	function loadSupabaseClient() {
		if (window.supabase) {
			return Promise.resolve(window.supabase);
		}

		if (!settings.supabaseScriptUrl) {
			return Promise.resolve(null);
		}

		if (!supabaseScriptPromise) {
			supabaseScriptPromise = new Promise(function (resolve) {
				var script = document.createElement('script');
				script.src = settings.supabaseScriptUrl;
				script.async = true;
				script.onload = function () {
					resolve(window.supabase || null);
				};
				script.onerror = function () {
					resolve(null);
				};
				document.head.appendChild(script);
			});
		}

		return supabaseScriptPromise;
	}

	function trackPwaEvent(eventType, metadata) {
		var table = settings.siteEventsTable || 'site_events';

		if (!eventType) {
			return Promise.resolve(null);
		}

		return pwaAnalyticsClient().then(function (client) {
			if (!client) {
				return null;
			}

			return client.from(table).insert([
				{
					session_id: analyticsSessionId(),
					event_type: eventType,
					page_path: window.location.pathname,
					page_title: document.title,
					ui_location: 'pwa',
					metadata: Object.assign(
						{
							is_standalone: isStandalone(),
							display_mode: isStandalone() ? 'standalone' : 'browser',
							user_agent: window.navigator.userAgent || '',
						},
						metadata || {}
					),
				},
			]);
		}).catch(function (error) {
			if (window.console && window.console.log) {
				window.console.log('PWA analytics failed', error);
			}
		});
	}

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

	function uint8ArrayToUrlBase64(value) {
		var bytes = value instanceof ArrayBuffer ? new Uint8Array(value) : new Uint8Array(value || []);
		var binary = '';

		for (var i = 0; i < bytes.byteLength; i += 1) {
			binary += String.fromCharCode(bytes[i]);
		}

		return window.btoa(binary).replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');
	}

	function subscriptionUsesCurrentKey(subscription) {
		if (!subscription || !subscription.options || !subscription.options.applicationServerKey || !settings.vapidPublicKey) {
			return true;
		}

		return uint8ArrayToUrlBase64(subscription.options.applicationServerKey) === settings.vapidPublicKey;
	}

	function registerServiceWorker() {
		if (!('serviceWorker' in navigator) || !settings.serviceWorkerUrl) {
			return Promise.resolve(null);
		}

		if (!registrationPromise) {
			registrationPromise = navigator.serviceWorker.getRegistration('/')
				.then(function (registration) {
					if (registration) {
						registration.update().catch(function () {});
						return registration;
					}

					return navigator.serviceWorker.register(settings.serviceWorkerUrl, {
						scope: '/',
						updateViaCache: 'none',
					});
				});
		}

		return registrationPromise;
	}

	function requestNotificationPermission() {
		if (!('Notification' in window)) {
			return Promise.resolve('unsupported');
		}

		if (window.Notification.permission === 'granted' || window.Notification.permission === 'denied') {
			return Promise.resolve(window.Notification.permission);
		}

		return new Promise(function (resolve) {
			var settled = false;

			function finish(permission) {
				if (settled) {
					return;
				}

				settled = true;
				resolve(permission || window.Notification.permission || 'default');
			}

			try {
				var request = window.Notification.requestPermission(finish);
				if (request && typeof request.then === 'function') {
					request.then(finish).catch(function () {
						finish(window.Notification.permission || 'default');
					});
				}
			} catch (error) {
				finish(window.Notification.permission || 'default');
			}
		});
	}

	function withTimeout(promise, timeoutMs, reason) {
		var timeoutId = null;
		var timeout = new Promise(function (resolve) {
			timeoutId = window.setTimeout(function () {
				resolve({ ok: false, reason: reason || 'timed out' });
			}, timeoutMs);
		});

		return Promise.race([promise, timeout]).then(function (result) {
			if (timeoutId) {
				window.clearTimeout(timeoutId);
			}
			return result;
		});
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
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('subscription-save-failed-' + response.status);
			}
			return subscription;
		});
	}

	function requestNotifications() {
		if (!('Notification' in window) || !('PushManager' in window)) {
			return Promise.resolve({ ok: false, reason: 'unsupported' });
		}

		return withTimeout(registerServiceWorker(), 30000, 'service worker timed out')
			.then(function (registration) {
				if (registration && registration.ok === false) {
					return registration;
				}

				if (!registration) {
					return { ok: false, reason: 'no-service-worker' };
				}

				return withTimeout(requestNotificationPermission(), 12000, 'permission timed out').then(function (permissionResult) {
					if (permissionResult && permissionResult.ok === false) {
						return permissionResult;
					}

					var permission = permissionResult;
					if (permission !== 'granted') {
						return { ok: false, reason: permission };
					}

					try {
						window.localStorage.setItem(promptStorageKey, 'accepted');
					} catch (error) {}

					if (!settings.vapidPublicKey) {
						return { ok: true, reason: 'permission-granted-no-vapid-key' };
					}

					return withTimeout(registration.pushManager.getSubscription(), 8000, 'subscription check timed out').then(function (existingSubscription) {
						if (existingSubscription && existingSubscription.ok === false) {
							return existingSubscription;
						}

						if (existingSubscription && !subscriptionUsesCurrentKey(existingSubscription)) {
							return existingSubscription.unsubscribe().then(function () {
								return null;
							});
						}

						return existingSubscription;
					}).then(function (subscription) {
						if (subscription && subscription.ok === false) {
							return subscription;
						}

						return subscription || withTimeout(registration.pushManager.subscribe({
							userVisibleOnly: true,
							applicationServerKey: urlBase64ToUint8Array(settings.vapidPublicKey),
						}), 12000, 'subscribe timed out');
					}).then(function (subscription) {
						if (subscription && subscription.ok === false) {
							return subscription;
						}

						return withTimeout(saveSubscription(subscription), 8000, 'save timed out');
					}).then(function (result) {
						if (result && result.ok === false) {
							return result;
						}

						return { ok: true, reason: 'subscribed' };
					});
				});
			});
	}

	function previewMonthlyThemeNotification(trigger) {
		var payload = settings.monthlyThemeNotificationPreview;

		if (!payload || !payload.title) {
			return Promise.resolve({ ok: false, reason: 'missing-payload' });
		}

		function showPreviewNotification() {
			return registerServiceWorker().then(function (registration) {
				if (!registration || !registration.showNotification) {
					return { ok: false, reason: 'no-service-worker' };
				}

				return registration.showNotification(payload.title, {
					body: payload.body || '',
					icon: payload.icon || settings.defaultIcon || '/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-icon-192.png',
					badge: payload.badge || payload.icon || '/wp-content/themes/wordpress-theme/assets/pwa/bybookishbabe-icon-192.png',
					tag: payload.tag || 'bbb-monthly-theme-preview',
					data: { url: payload.url || '/monthly-theme/' },
				}).then(function () {
					return { ok: true, reason: 'preview-shown' };
				});
			});
		}

		if (!('Notification' in window)) {
			return Promise.resolve({ ok: false, reason: 'unsupported' });
		}

		if (window.Notification.permission === 'granted') {
			return showPreviewNotification();
		}

		if (window.Notification.permission === 'denied') {
			return Promise.resolve({ ok: false, reason: 'denied' });
		}

		if (trigger) {
			trigger.setAttribute('aria-busy', 'true');
		}

		return window.Notification.requestPermission().then(function (permission) {
			if (trigger) {
				trigger.removeAttribute('aria-busy');
			}

			if (permission !== 'granted') {
				return { ok: false, reason: permission };
			}

			return showPreviewNotification();
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
			'<h2>want bookish alerts?</h2>',
			'<p>get a little nudge when new recs, freebies, or society updates drop.</p>',
			'<p class="bbb-pwa-notification-panel__diagnostics" data-bbb-pwa-notification-diagnostics></p>',
			'<div class="bbb-pwa-notification-panel__actions">',
			'<button type="button" class="bbb-pwa-notification-panel__primary" data-bbb-pwa-notifications>turn on notifications</button>',
			'<button type="button" class="bbb-pwa-notification-panel__secondary" data-bbb-pwa-dismiss-notifications>not now</button>',
			'</div>',
			'</div>',
		].join('');
		document.body.appendChild(panel);
		updateNotificationDiagnostics();
	}

	function maybeShowInstalledNotificationPrompt() {
		var forcePrompt = shouldForceNotificationPrompt();

		if (isSocialPlannerRoute()) {
			return;
		}

		if (!('Notification' in window)) {
			return;
		}

		if (!forcePrompt && !looksLikePwaLaunch()) {
			return;
		}

		if (!forcePrompt && window.Notification.permission !== 'default') {
			return;
		}

		if (!forcePrompt) {
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

	function notificationDiagnosticsText() {
		var parts = [];

		parts.push('permission: ' + (('Notification' in window) ? window.Notification.permission : 'missing'));
		parts.push('service worker: ' + (('serviceWorker' in navigator) ? 'yes' : 'no'));
		parts.push('push: ' + (('PushManager' in window) ? 'yes' : 'no'));
		parts.push('mode: ' + (looksLikePwaLaunch() ? 'app' : 'browser'));

		return parts.join(' | ');
	}

	function updateNotificationDiagnostics() {
		Array.prototype.slice.call(document.querySelectorAll('[data-bbb-pwa-notification-diagnostics]')).forEach(function (element) {
			element.textContent = notificationDiagnosticsText();
		});
	}

	function updateNotificationButtons(result) {
		var label = 'try again';

		if (result && result.ok) {
			label = 'connected';
		} else if (result && result.reason === 'denied') {
			label = 'blocked';
		} else if (result && result.reason === 'unsupported') {
			label = 'unsupported';
		} else if (result && result.reason) {
			label = result.reason;
		}

		Array.prototype.slice.call(document.querySelectorAll('[data-bbb-pwa-notifications]')).forEach(function (button) {
			button.textContent = label;
			button.removeAttribute('aria-busy');
		});

		updateNotificationDiagnostics();
	}

	function repairGrantedNotificationSubscription() {
		if (!('Notification' in window) || !('PushManager' in window) || window.Notification.permission !== 'granted' || !settings.vapidPublicKey) {
			return;
		}

		withTimeout(registerServiceWorker(), 30000, 'service worker timed out')
			.then(function (registration) {
				if (!registration || registration.ok === false) {
					return registration;
				}

				return withTimeout(registration.pushManager.getSubscription(), 8000, 'subscription check timed out').then(function (existingSubscription) {
					if (existingSubscription && existingSubscription.ok === false) {
						return existingSubscription;
					}

					if (existingSubscription && !subscriptionUsesCurrentKey(existingSubscription)) {
						return existingSubscription.unsubscribe().then(function () {
							return null;
						});
					}

					return existingSubscription;
				}).then(function (subscription) {
					if (subscription && subscription.ok === false) {
						return subscription;
					}

					return subscription || withTimeout(registration.pushManager.subscribe({
						userVisibleOnly: true,
						applicationServerKey: urlBase64ToUint8Array(settings.vapidPublicKey),
					}), 12000, 'subscribe timed out');
				}).then(function (subscription) {
					if (subscription && subscription.ok === false) {
						return subscription;
					}

					return withTimeout(saveSubscription(subscription), 8000, 'save timed out');
				}).then(function (result) {
					if (result && result.ok === false) {
						return result;
					}

					document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', {
						detail: { ok: true, reason: 'subscribed' },
					}));
					return { ok: true, reason: 'subscribed' };
				});
			}).catch(function (error) {
				if (window.console && window.console.log) {
					window.console.log('PWA notification repair failed', error);
				}
			});
	}

	function trackFirstStandaloneOpen() {
		if (!isStandalone()) {
			return;
		}

		try {
			if (window.localStorage.getItem(firstStandaloneOpenKey)) {
				return;
			}
			window.localStorage.setItem(firstStandaloneOpenKey, new Date().toISOString());
		} catch (error) {}

		trackPwaEvent('pwa_first_standalone_open', {
			source: new URLSearchParams(window.location.search).get('source') || '',
		});
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
		document.documentElement.classList.toggle('bbb-is-pwa-app', looksLikePwaLaunch());
	}

	function appBackIsReady() {
		try {
			return window.sessionStorage.getItem(appBackReadyKey) === '1';
		} catch (error) {
			return window.history.length > 1;
		}
	}

	function markAppBackReady() {
		try {
			window.sessionStorage.setItem(appBackReadyKey, '1');
		} catch (error) {}
	}

	function shareUrlForCurrentPage() {
		var url = new URL(window.location.href);
		['source', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'bbb_cache_bust', 'bbb_pwa_launch', 'bbb_pwa_refresh'].forEach(function (param) {
			url.searchParams.delete(param);
		});

		return url.href;
	}

	function showPwaShareToast(message) {
		var toast = document.querySelector('[data-bbb-pwa-share-toast]');
		if (!toast) {
			return;
		}

		toast.textContent = message || 'link copied';
		toast.classList.add('is-visible');
		window.clearTimeout(showPwaShareToast.timeout);
		showPwaShareToast.timeout = window.setTimeout(function () {
			toast.classList.remove('is-visible');
		}, 1800);
	}

	function copyShareUrl(url) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(url);
		}

		var field = document.createElement('textarea');
		field.value = url;
		field.setAttribute('readonly', '');
		field.style.position = 'fixed';
		field.style.left = '-9999px';
		document.body.appendChild(field);
		field.select();

		try {
			document.execCommand('copy');
		} finally {
			document.body.removeChild(field);
		}

		return Promise.resolve();
	}

	function ensurePwaShareButton() {
		if (!looksLikePwaLaunch() || document.querySelector('[data-bbb-pwa-share]')) {
			return;
		}

		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'bbb-pwa-share';
		button.setAttribute('data-bbb-pwa-share', '');
		button.setAttribute('aria-label', 'share this page');
		button.textContent = '📲';

		var toast = document.createElement('span');
		toast.className = 'bbb-pwa-share-toast';
		toast.setAttribute('data-bbb-pwa-share-toast', '');
		toast.setAttribute('role', 'status');
		toast.setAttribute('aria-live', 'polite');
		toast.textContent = 'link copied';

		document.body.appendChild(button);
		document.body.appendChild(toast);
	}

	function ensureAppBackButton() {
		if (!isStandalone() || !appBackIsReady() || document.querySelector('[data-bbb-pwa-back]')) {
			return;
		}

		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'bbb-pwa-back';
		button.setAttribute('data-bbb-pwa-back', '');
		button.setAttribute('aria-label', 'go back');
		button.style.bottom = 'calc(8.2rem + env(safe-area-inset-bottom))';
		button.textContent = '←';
		document.body.appendChild(button);
	}

	function goBackOrHome() {
		if (window.history.length > 1) {
			window.history.back();
			return;
		}

		window.location.assign('/');
	}

	function shouldForceNotificationPrompt() {
		return new URLSearchParams(window.location.search).get('show-pwa-notifications') === '1';
	}

	function isSocialPlannerRoute() {
		return window.location.pathname.indexOf('/social-planner') === 0;
	}

	function looksLikePwaLaunch() {
		var source = new URLSearchParams(window.location.search).get('source') || '';

		return isStandalone() || source.indexOf('pwa-bybookishbabe') === 0 || shouldForceNotificationPrompt();
	}

	function shouldPrefetchAppUrl(url) {
		return url.origin === window.location.origin
			&& url.href !== window.location.href
			&& !/^\/(account|cart|checkout|my-bookshelf|my-notes|my-vault|my-kindle-inserts|wp-admin|wp-login|wp-json)(\/|$)/.test(url.pathname);
	}

	function appLinkPriority(url) {
		if (/^\/(shop|books|library|reader-quizzes)(\/|$)/.test(url.pathname)) {
			return 0;
		}

		if (/^\/(books-like|series|what-to-read-next|book-reviews)(\/|$)/.test(url.pathname) || /-books\/?$/.test(url.pathname)) {
			return 1;
		}

		return 2;
	}

	function installHelpDevice() {
		if (isIpad()) {
			return 'ipad';
		}

		if (/iphone|ipod/i.test(window.navigator.userAgent)) {
			return 'iphone';
		}

		if (/android/i.test(window.navigator.userAgent)) {
			return 'android';
		}

		return 'other';
	}

	function installHelpUrl() {
		return '/bybookishbabe-app/?install=1&device=' + encodeURIComponent(installHelpDevice());
	}

	function prefetchAppUrl(url) {
		if (!('fetch' in window) || prefetchedUrls[url.href]) {
			return;
		}

		prefetchedUrls[url.href] = true;

		window.fetch(url.href, {
			cache: 'force-cache',
			credentials: 'same-origin',
		}).catch(function () {});
	}

	function warmVisibleAppLinks() {
		if (!isStandalone() || !('fetch' in window)) {
			return;
		}

		if (window.navigator.connection && window.navigator.connection.saveData) {
			return;
		}

		var warmed = 0;
		var links = Array.prototype.slice.call(document.querySelectorAll('main a[href], [role="main"] a[href], nav a[href], a[href]'));
		var viewportLimit = window.innerHeight ? window.innerHeight * 1.8 : 1400;
		var candidates = [];

		links.forEach(function (link, index) {
			if (link.target || link.hasAttribute('download')) {
				return;
			}

			try {
				var targetUrl = new URL(link.href, window.location.href);
				var rect = link.getBoundingClientRect();

				if (shouldPrefetchAppUrl(targetUrl) && rect.bottom >= -80 && rect.top <= viewportLimit) {
					candidates.push({
						link: link,
						url: targetUrl,
						priority: appLinkPriority(targetUrl),
						index: index,
					});
				}
			} catch (error) {}
		});

		candidates.sort(function (a, b) {
			return a.priority - b.priority || a.index - b.index;
		});

		candidates.some(function (candidate) {
			if (warmed >= 18) {
				return true;
			}

			warmed += 1;
			prefetchAppUrl(candidate.url);
			return false;
		});
	}

	function scheduleAppPrefetch() {
		if (!isStandalone()) {
			return;
		}

		window.setTimeout(warmVisibleAppLinks, 250);

		if ('requestIdleCallback' in window) {
			window.requestIdleCallback(warmVisibleAppLinks, { timeout: 1600 });
			return;
		}

		window.setTimeout(warmVisibleAppLinks, 900);
	}

	function setupVisibleLinkWarmObserver() {
		if (!isStandalone() || !('IntersectionObserver' in window) || !('fetch' in window)) {
			return;
		}

		if (window.navigator.connection && window.navigator.connection.saveData) {
			return;
		}

		if (linkWarmObserver) {
			linkWarmObserver.disconnect();
		}

		observedWarmLinks = [];
		linkWarmObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) {
					return;
				}

				linkWarmObserver.unobserve(entry.target);

				try {
					var targetUrl = new URL(entry.target.href, window.location.href);
					if (shouldPrefetchAppUrl(targetUrl)) {
						prefetchAppUrl(targetUrl);
					}
				} catch (error) {}
			});
		}, { rootMargin: '420px 0px' });

		Array.prototype.slice.call(document.querySelectorAll('main a[href], [role="main"] a[href]')).some(function (link) {
			if (observedWarmLinks.length >= 36 || link.target || link.hasAttribute('download')) {
				return observedWarmLinks.length >= 36;
			}

			try {
				var targetUrl = new URL(link.href, window.location.href);

				if (shouldPrefetchAppUrl(targetUrl)) {
					observedWarmLinks.push(link);
					linkWarmObserver.observe(link);
				}
			} catch (error) {}

			return observedWarmLinks.length >= 36;
		});
	}

	function showInstallHelp(trigger) {
		var helpId = trigger ? trigger.getAttribute('aria-describedby') : '';
		var help = helpId ? document.getElementById(helpId) : null;
		var device = installHelpDevice();

		if (help) {
			help.hidden = false;
		}

		trackPwaEvent('pwa_manual_install_help_opened', { device: device });
		window.location.assign(installHelpUrl());
	}

	function requestInstall(trigger) {
		if (isStandalone()) {
			trackPwaEvent('pwa_install_button_clicked', { state: 'already_standalone' });
			showInstallHelp(trigger);
			return Promise.resolve({ ok: true, reason: 'already-installed' });
		}

		trackPwaEvent('pwa_install_button_clicked', {
			state: deferredInstallPrompt ? 'native_prompt_available_help_opened' : 'manual_install_required',
		});
		showInstallHelp(trigger);
		return Promise.resolve({ ok: false, reason: 'manual-install-help-opened' });
	}

	window.addEventListener('beforeinstallprompt', function (event) {
		event.preventDefault();
		deferredInstallPrompt = event;
		trackPwaEvent('pwa_install_prompt_shown');
	});

	window.addEventListener('appinstalled', function () {
		trackPwaEvent('pwa_appinstalled');
	});

	var watchRegistrationForUpdates = function () {};

	// Let the service worker update quietly. Forcing a reload during activation
	// makes the installed app feel like it opens twice, especially on first launch
	// after a deploy.
	if ('serviceWorker' in navigator) {
		var initialController = navigator.serviceWorker.controller;

		function activateWaitingWorker(registration) {
			if (registration && registration.waiting) {
				registration.waiting.postMessage({ type: 'SKIP_WAITING' });
			}
		}

		watchRegistrationForUpdates = function (registration) {
			if (!registration) {
				return;
			}

			activateWaitingWorker(registration);

			registration.addEventListener('updatefound', function () {
				var installing = registration.installing;

				if (!installing) {
					return;
				}

				installing.addEventListener('statechange', function () {
					if (installing.state === 'installed' && navigator.serviceWorker.controller) {
						activateWaitingWorker(registration);
					}
				});
			});
		};

		navigator.serviceWorker.addEventListener('controllerchange', function () {
			if (!initialController) {
				initialController = navigator.serviceWorker.controller;
				return;
			}

			initialController = navigator.serviceWorker.controller;
		});

		navigator.serviceWorker.addEventListener('message', function (event) {
			if (event.data && event.data.type === 'BBB_PWA_UPDATED') {
				initialController = navigator.serviceWorker.controller || initialController;
			}
		});

		window.addEventListener('visibilitychange', function () {
			if (document.visibilityState !== 'visible') {
				return;
			}

			registerServiceWorker().then(function (registration) {
				if (registration) {
					registration.update().catch(function () {});
					watchRegistrationForUpdates(registration);
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		syncAppModeClass();
		document.addEventListener('DOMContentLoaded', function () {
			syncAppModeClass();
			ensurePwaShareButton();
			ensureAppBackButton();
			trackFirstStandaloneOpen();
			registerServiceWorker().then(watchRegistrationForUpdates);
			restoreDismissedPromo();
			scheduleAppPrefetch();
			setupVisibleLinkWarmObserver();
			repairGrantedNotificationSubscription();
			maybeShowInstalledNotificationPrompt();
		});
	} else {
		syncAppModeClass();
		ensurePwaShareButton();
		ensureAppBackButton();
		trackFirstStandaloneOpen();
		registerServiceWorker().then(watchRegistrationForUpdates);
		restoreDismissedPromo();
		scheduleAppPrefetch();
		setupVisibleLinkWarmObserver();
		repairGrantedNotificationSubscription();
		maybeShowInstalledNotificationPrompt();
	}

	document.addEventListener('click', function (event) {
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-back]');

		if (!trigger) {
			return;
		}

		event.preventDefault();
		goBackOrHome();
	});

	document.addEventListener('click', function (event) {
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-share]');

		if (!trigger) {
			return;
		}

		event.preventDefault();

		var shareUrl = shareUrlForCurrentPage();
		var shareData = {
			title: document.title || 'bybookishbabe',
			url: shareUrl,
		};

		if (navigator.share) {
			navigator.share(shareData).catch(function (error) {
				if (!error || error.name === 'AbortError') {
					return;
				}
				copyShareUrl(shareUrl).then(function () {
					showPwaShareToast('link copied');
				});
			});
			return;
		}

		copyShareUrl(shareUrl).then(function () {
			showPwaShareToast('link copied');
		}).catch(function () {
			showPwaShareToast('copy failed');
		});
	});

	document.addEventListener('click', function (event) {
		var link = closestFromTarget(event.target, 'a[href]');

		if (!link || link.target || link.hasAttribute('download')) {
			return;
		}

		try {
			var targetUrl = new URL(link.href, window.location.href);

			if (isStandalone() && targetUrl.origin === window.location.origin && targetUrl.href !== window.location.href) {
				markAppBackReady();
			}

			if (shouldPrefetchAppUrl(targetUrl)) {
				prefetchAppUrl(targetUrl);
			}
		} catch (error) {}
	}, true);

	document.addEventListener('touchstart', function (event) {
		var link = closestFromTarget(event.target, 'a[href]');

		if (!link || link.target || link.hasAttribute('download')) {
			return;
		}

		try {
			var targetUrl = new URL(link.href, window.location.href);

			if (shouldPrefetchAppUrl(targetUrl)) {
				prefetchAppUrl(targetUrl);
			}
		} catch (error) {}
	}, { passive: true });

	['pointerenter', 'mousedown'].forEach(function (eventName) {
		document.addEventListener(eventName, function (event) {
			var link = closestFromTarget(event.target, 'a[href]');

			if (!link || link.target || link.hasAttribute('download')) {
				return;
			}

			try {
				var targetUrl = new URL(link.href, window.location.href);

				if (shouldPrefetchAppUrl(targetUrl)) {
					prefetchAppUrl(targetUrl);
				}
			} catch (error) {}
		}, true);
	});

	window.addEventListener('pageshow', function () {
		scheduleAppPrefetch();
		setupVisibleLinkWarmObserver();
	});

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
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-notifications]');

		if (!trigger) {
			return;
		}

		event.preventDefault();
		trigger.setAttribute('aria-busy', 'true');
		trigger.textContent = 'connecting...';
		withTimeout(requestNotifications(), 12000, 'timed out').then(function (result) {
			var panel = document.querySelector('[data-bbb-pwa-notification-panel]');
			if (panel && result && result.ok) {
				panel.remove();
			}
			document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', { detail: result }));
		}).catch(function (error) {
			document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', {
				detail: { ok: false, reason: error && error.message ? error.message : 'subscription-failed' },
			}));
		});
	});

	document.addEventListener('click', function (event) {
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-monthly-preview]');
		var panel = trigger ? trigger.closest('[data-bbb-pwa-monthly-preview-panel]') : null;

		if (!trigger) {
			return;
		}

		event.preventDefault();
		previewMonthlyThemeNotification(trigger).then(function (result) {
			if (!panel) {
				return;
			}

			panel.setAttribute('data-preview-result', result && result.ok ? 'ok' : 'error');
			if (result && result.ok) {
				trigger.textContent = 'sent preview';
				return;
			}

			trigger.textContent = result && result.reason === 'denied' ? 'blocked' : 'try again';
		});
	});

	document.addEventListener('click', function (event) {
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-dismiss-notifications]');
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
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-dismiss]');
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
		withTimeout(requestNotifications(), 12000, 'timed out').then(function (result) {
			document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', { detail: result }));
		}).catch(function (error) {
			document.dispatchEvent(new CustomEvent('bbb:pwa-notification-result', {
				detail: { ok: false, reason: error && error.message ? error.message : 'subscription-failed' },
			}));
		});
	});

	document.addEventListener('bbb:pwa-notification-result', function (event) {
		updateNotificationButtons(event.detail || {});
	});

	document.addEventListener('click', function (event) {
		var trigger = closestFromTarget(event.target, '[data-bbb-pwa-install], [data-bbb-pwa-install-link]');

		if (!trigger) {
			return;
		}

		if (trigger.tagName === 'A' && trigger.href) {
			trigger.href = installHelpUrl();
			trackPwaEvent('pwa_install_button_clicked', {
				state: deferredInstallPrompt ? 'native_prompt_available_link_opened' : 'manual_install_link_opened',
			});
			return;
		}

		event.preventDefault();
		requestInstall(trigger).then(function (result) {
			document.dispatchEvent(new CustomEvent('bbb:pwa-install-result', { detail: result }));
		});
	});
}());
