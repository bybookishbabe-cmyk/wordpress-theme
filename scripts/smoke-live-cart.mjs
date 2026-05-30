#!/usr/bin/env node
import { spawn } from 'node:child_process';
import { mkdtemp } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const liveUrl = process.env.BBB_LIVE_URL || 'https://bybookishbabe.com';
const chromePath = process.env.BBB_CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const port = Number(process.env.BBB_CHROME_PORT || 9361);
const targetUrl = `${liveUrl.replace(/\/$/, '')}/shop/?bbb_cart_smoke=${Date.now()}`;

function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function cdp(path, options) {
  const res = await fetch(`http://127.0.0.1:${port}${path}`, options);
  if (!res.ok) throw new Error(`${path} ${res.status}`);
  return res.json();
}

async function waitForChrome() {
  const started = Date.now();
  while (Date.now() - started < 12000) {
    try {
      await cdp('/json/version');
      return;
    } catch {
      await wait(250);
    }
  }
  throw new Error('Chrome debugging port did not become ready.');
}

function createCdpClient(webSocketDebuggerUrl) {
  const ws = new WebSocket(webSocketDebuggerUrl);
  let id = 0;
  const pending = new Map();
  const events = [];

  function send(method, params = {}, timeout = 30000) {
    const callId = ++id;
    ws.send(JSON.stringify({ id: callId, method, params }));
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        pending.delete(callId);
        reject(new Error(`timeout ${method}`));
      }, timeout);
      pending.set(callId, {
        resolve: (value) => {
          clearTimeout(timer);
          resolve(value);
        },
        reject: (error) => {
          clearTimeout(timer);
          reject(error);
        },
      });
    });
  }

  ws.addEventListener('message', (event) => {
    const msg = JSON.parse(event.data);
    if (msg.id && pending.has(msg.id)) {
      const { resolve, reject } = pending.get(msg.id);
      pending.delete(msg.id);
      if (msg.error) reject(new Error(JSON.stringify(msg.error)));
      else resolve(msg.result);
      return;
    }
    if (msg.method === 'Runtime.exceptionThrown' || msg.method === 'Network.loadingFailed') {
      events.push(msg);
    }
  });

  return {
    events,
    ws,
    send,
    close: () => ws.close(),
    ready: new Promise((resolve) => ws.addEventListener('open', resolve, { once: true })),
  };
}

async function waitForLoad(ws, timeout = 18000) {
  await new Promise((resolve) => {
    const timer = setTimeout(resolve, timeout);
    const handler = (event) => {
      const msg = JSON.parse(event.data);
      if (msg.method === 'Page.loadEventFired') {
        clearTimeout(timer);
        ws.removeEventListener('message', handler);
        resolve();
      }
    };
    ws.addEventListener('message', handler);
  });
}

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

const userDataDir = await mkdtemp(join(tmpdir(), 'bbb-cart-smoke-'));
const chrome = spawn(chromePath, [
  '--headless=new',
  `--remote-debugging-port=${port}`,
  `--user-data-dir=${userDataDir}`,
  '--disable-gpu',
  '--no-first-run',
  '--no-default-browser-check',
  'about:blank',
], { stdio: ['ignore', 'pipe', 'pipe'] });

try {
  await waitForChrome();
  const pageTarget = await cdp(`/json/new?${encodeURIComponent('about:blank')}`, { method: 'PUT' });
  const client = createCdpClient(pageTarget.webSocketDebuggerUrl);
  await client.ready;

  await client.send('Runtime.enable');
  await client.send('Page.enable');
  await client.send('Network.enable');
  await client.send('Network.clearBrowserCookies');
  await client.send('Emulation.setDeviceMetricsOverride', {
    width: 390,
    height: 844,
    deviceScaleFactor: 3,
    mobile: true,
  });
  await client.send('Emulation.setTouchEmulationEnabled', { enabled: true });

  await client.send('Page.navigate', { url: targetUrl });
  await waitForLoad(client.ws);
  await wait(3500);

  const setup = await client.send('Runtime.evaluate', {
    returnByValue: true,
    expression: `(() => {
      if (document.body.innerText.includes('There has been a critical error on this website')) {
        return { ok: false, reason: 'critical error page' };
      }
      const card = Array.from(document.querySelectorAll('.bbb-shop-card')).find((candidate) => {
        const select = candidate.querySelector('.bbb-shop-card__sizeSelect');
        const button = candidate.querySelector('.edd-add-to-cart:not(.edd-no-js)');
        return select && button && getComputedStyle(button).display !== 'none' && button.offsetParent !== null;
      });
      if (!card) return { ok: false, reason: 'no selectable visible product' };
      const select = card.querySelector('.bbb-shop-card__sizeSelect');
      const option = Array.from(select.options).find((candidate) => /11th gen/i.test(candidate.textContent)) || select.options[select.options.length - 1];
      select.value = option.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      const button = card.querySelector('.edd-add-to-cart:not(.edd-no-js)');
      button.scrollIntoView({ block: 'center' });
      const rect = button.getBoundingClientRect();
      return {
        ok: true,
        title: card.querySelector('.bbb-shop-card__title')?.textContent.trim() || card.querySelector('h2,h3')?.textContent.trim() || '',
        selectedText: option.textContent.trim(),
        hiddenValue: card.querySelector('input[type="hidden"][name="edd_options[price_id][]"]')?.value || '',
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2
      };
    })()`,
  });

  const tap = setup.result.value;
  assert(tap.ok, `Cart smoke setup failed: ${JSON.stringify(tap)}`);

  await client.send('Input.dispatchTouchEvent', {
    type: 'touchStart',
    touchPoints: [{ x: tap.x, y: tap.y, radiusX: 5, radiusY: 5, id: 1 }],
  });
  await client.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
  await wait(5500);

  const cardState = await client.send('Runtime.evaluate', {
    returnByValue: true,
    expression: `(() => Array.from(document.querySelectorAll('.bbb-shop-card')).slice(0, 9).map((card, index) => {
      const button = card.querySelector('.edd-add-to-cart:not(.edd-no-js)');
      const checkout = card.querySelector('.edd_go_to_checkout');
      const status = card.querySelector('.bbb-edd-cart-status');
      return {
        index,
        status: status ? status.textContent.trim() : '',
        buttonDisplay: button ? getComputedStyle(button).display : null,
        checkoutDisplay: checkout ? getComputedStyle(checkout).display : null
      };
    }))()`,
  });

  const cards = cardState.result.value;
  assert(cards[0]?.status === 'added to cart. checkout is ready.', 'Tapped card did not show the expected success status.');
  assert(cards[0]?.checkoutDisplay !== 'none', 'Tapped card did not expose checkout.');
  assert(cards.slice(1).every((card) => card.status === ''), 'A non-tapped card showed an added-to-cart status.');
  assert(cards.slice(1).every((card) => card.buttonDisplay !== 'none'), 'A non-tapped card hid its add-to-cart button.');

  const checkoutClick = await client.send('Runtime.evaluate', {
    returnByValue: true,
    expression: `(() => {
      const link = Array.from(document.querySelectorAll('.bbb-shop-card .edd_go_to_checkout')).find((candidate) => getComputedStyle(candidate).display !== 'none' && candidate.offsetParent !== null);
      if (!link) return { ok: false };
      link.click();
      return { ok: true, href: link.href };
    })()`,
  });
  assert(checkoutClick.result.value.ok, 'No checkout link was available after adding to cart.');
  await wait(5500);

  const checkout = await client.send('Runtime.evaluate', {
    returnByValue: true,
    expression: `(() => {
      const text = document.body.innerText;
      return {
        url: location.href,
        hasCriticalError: text.includes('There has been a critical error on this website'),
        hasCheckout: !!document.querySelector('#edd_checkout_form_wrap, .edd_checkout_cart, #edd_checkout_cart'),
        selectedTextFound: text.toLowerCase().includes(${JSON.stringify(tap.selectedText.toLowerCase())}),
        snippet: text.slice(0, 600)
      };
    })()`,
  });

  assert(!checkout.result.value.hasCriticalError, 'Checkout opened a WordPress critical-error page.');
  assert(checkout.result.value.hasCheckout, 'Checkout form was not present.');
  assert(checkout.result.value.selectedTextFound, `Checkout did not include selected size: ${tap.selectedText}`);

  console.log(`OK cart smoke: ${tap.selectedText} variant reached checkout and only the tapped card changed.`);
  client.close();
} finally {
  chrome.kill('SIGTERM');
}
